<?php
/**
 * API pour la mise à jour inline des leads
 * Permet de modifier les champs directement depuis le tableau
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les données
$lead_id = isset($_POST['lead_id']) ? intval($_POST['lead_id']) : 0;
$field = isset($_POST['field']) ? trim($_POST['field']) : '';
$value = isset($_POST['value']) ? trim($_POST['value']) : '';

// Validation
if ($lead_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de lead invalide']);
    exit;
}

if (empty($field)) {
    echo json_encode(['success' => false, 'message' => 'Champ non spécifié']);
    exit;
}

// Mapping des champs autorisés à éditer
$allowed_fields = [
    'name' => ['first_name', 'last_name'],  // Nom complet nécessite traitement spécial
    'email' => 'email',
    'company' => 'company_name',
    'source' => 'source',
];

if (!array_key_exists($field, $allowed_fields)) {
    echo json_encode(['success' => false, 'message' => 'Champ non autorisé']);
    exit;
}

try {
    // Connexion à la base de données
    $pdo = getDBConnection();
    
    // Traitement spécial pour le nom (first_name + last_name)
    if ($field === 'name') {
        // Diviser le nom complet en prénom et nom
        $parts = explode(' ', $value, 2);
        $first_name = $parts[0];
        $last_name = isset($parts[1]) ? $parts[1] : '';
        
        $stmt = $pdo->prepare("
            UPDATE crm_leads 
            SET first_name = :first_name, 
                last_name = :last_name,
                updated_at = NOW()
            WHERE id = :lead_id
        ");
        
        $stmt->execute([
            ':first_name' => $first_name,
            ':last_name' => $last_name,
            ':lead_id' => $lead_id
        ]);
    } else {
        // Pour les autres champs
        $db_field = $allowed_fields[$field];
        
        // Validation spécifique pour l'email
        if ($field === 'email') {
            if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Email invalide']);
                exit;
            }
        }
        
        $stmt = $pdo->prepare("
            UPDATE crm_leads 
            SET {$db_field} = :value,
                updated_at = NOW()
            WHERE id = :lead_id
        ");
        
        $stmt->execute([
            ':value' => $value,
            ':lead_id' => $lead_id
        ]);
    }
    
    // Vérifier si la mise à jour a réussi
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true, 
            'message' => 'Lead mis à jour avec succès',
            'data' => [
                'lead_id' => $lead_id,
                'field' => $field,
                'value' => $value
            ]
        ]);
    } else {
        // Aucune ligne affectée - peut-être que la valeur est identique
        echo json_encode([
            'success' => true, 
            'message' => 'Aucune modification nécessaire',
            'data' => [
                'lead_id' => $lead_id,
                'field' => $field,
                'value' => $value
            ]
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Erreur update-lead-inline: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Erreur update-lead-inline: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}

/**
 * Fonction pour obtenir la connexion à la base de données
 */
function getDBConnection() {
    // Paramètres de connexion
    $host = 'localhost';
    $dbname = 'webitech';
    $username = 'root';
    $password = 'root';
    
    try {
        $pdo = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4", 
            $username, 
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        throw new Exception("Connexion échouée: " . $e->getMessage());
    }
}
