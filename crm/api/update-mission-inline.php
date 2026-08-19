<?php
/**
 * API pour mettre à jour les champs inline des missions
 */

header('Content-Type: application/json');
require_once __DIR__ . '/permission-bootstrap.php';

// Connexion à la base de données
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=webitech;charset=utf8mb4',
        'root',
        'root',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
    exit;
}

// Vérifier la méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les données POST
$mission_id = isset($_POST['mission_id']) ? intval($_POST['mission_id']) : 0;
$field = isset($_POST['field']) ? trim($_POST['field']) : '';
$value = isset($_POST['value']) ? trim($_POST['value']) : '';

// Validation
if ($mission_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID mission invalide']);
    exit;
}

if (empty($field)) {
    echo json_encode(['success' => false, 'message' => 'Champ non spécifié']);
    exit;
}

// Champs autorisés pour l'édition inline
$allowed_fields = ['departure', 'arrival', 'driver', 'vehicle', 'datetime'];

if (!in_array($field, $allowed_fields)) {
    echo json_encode(['success' => false, 'message' => 'Champ non autorisé']);
    exit;
}

// Validation spécifique par champ
if ($field === 'datetime') {
    // Valider le format datetime
    $datetime = DateTime::createFromFormat('Y-m-d\TH:i', $value);
    if (!$datetime) {
        echo json_encode(['success' => false, 'message' => 'Format de date/heure invalide']);
        exit;
    }
    $value = $datetime->format('Y-m-d H:i:s');
}

try {
    // Vérifier que la mission existe
    $stmt = $pdo->prepare("SELECT id FROM missions WHERE id = ?");
    $stmt->execute([$mission_id]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Mission non trouvée']);
        exit;
    }
    
    // Mettre à jour le champ
    $sql = "UPDATE missions SET {$field} = :value WHERE id = :mission_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':value' => $value,
        ':mission_id' => $mission_id
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Mission mise à jour avec succès',
        'data' => [
            'mission_id' => $mission_id,
            'field' => $field,
            'value' => $value
        ]
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
    ]);
}
