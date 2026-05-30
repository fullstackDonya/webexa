<?php
/**
 * API pour mettre à jour les champs inline des clients
 */

header('Content-Type: application/json');

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
$customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
$field = isset($_POST['field']) ? trim($_POST['field']) : '';
$value = isset($_POST['value']) ? trim($_POST['value']) : '';

// Validation
if ($customer_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID client invalide']);
    exit;
}

if (empty($field)) {
    echo json_encode(['success' => false, 'message' => 'Champ non spécifié']);
    exit;
}

// Champs autorisés pour l'édition inline
$allowed_fields = ['name', 'email', 'phone', 'company', 'address', 'city', 'country'];

if (!in_array($field, $allowed_fields)) {
    echo json_encode(['success' => false, 'message' => 'Champ non autorisé']);
    exit;
}

// Validation spécifique par champ
if ($field === 'email' && !empty($value)) {
    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Adresse email invalide']);
        exit;
    }
}

try {
    // Vérifier que le client existe
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE id = ?");
    $stmt->execute([$customer_id]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Client non trouvé']);
        exit;
    }
    
    // Mettre à jour le champ
    $sql = "UPDATE customers SET {$field} = :value WHERE id = :customer_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':value' => $value,
        ':customer_id' => $customer_id
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Client mis à jour avec succès',
        'data' => [
            'customer_id' => $customer_id,
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
