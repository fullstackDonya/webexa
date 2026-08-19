<?php
/**
 * API pour mettre à jour les champs inline des dossiers
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
$folder_id = isset($_POST['folder_id']) ? intval($_POST['folder_id']) : 0;
$field = isset($_POST['field']) ? trim($_POST['field']) : '';
$value = isset($_POST['value']) ? trim($_POST['value']) : '';

// Validation
if ($folder_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID dossier invalide']);
    exit;
}

if (empty($field)) {
    echo json_encode(['success' => false, 'message' => 'Champ non spécifié']);
    exit;
}

// Champs autorisés pour l'édition inline
$allowed_fields = ['name', 'description', 'contact_person', 'contact_email', 'contact_phone'];

if (!in_array($field, $allowed_fields)) {
    echo json_encode(['success' => false, 'message' => 'Champ non autorisé']);
    exit;
}

// Validation spécifique par champ
if ($field === 'contact_email' && !empty($value)) {
    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Adresse email invalide']);
        exit;
    }
}

try {
    // Vérifier que le dossier existe
    $stmt = $pdo->prepare("SELECT id FROM folders WHERE id = ?");
    $stmt->execute([$folder_id]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Dossier non trouvé']);
        exit;
    }
    
    // Mettre à jour le champ
    $sql = "UPDATE folders SET {$field} = :value WHERE id = :folder_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':value' => $value,
        ':folder_id' => $folder_id
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Dossier mis à jour avec succès',
        'data' => [
            'folder_id' => $folder_id,
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
