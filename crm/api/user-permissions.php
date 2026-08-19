<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/permission-bootstrap.php';

$user = getCurrentUser();
if (!$user || ($user['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Réservé aux administrateurs']);
    exit;
}

$fields = array_values(featurePermissions());
$targetId = (int)($_GET['user_id'] ?? $_POST['user_id'] ?? 0);
if ($targetId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'user_id requis']);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->prepare('SELECT id, username, email, role, ' . implode(', ', $fields) . ' FROM users WHERE id = ?');
        $stmt->execute([$targetId]);
        $target = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$target) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable']);
            exit;
        }
        echo json_encode(['success' => true, 'user' => $target, 'fields' => $fields]);
        exit;
    }

    $payload = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $changes = [];
    $values = [];
    foreach ($fields as $field) {
        if (array_key_exists($field, $payload)) {
            $changes[] = "$field = ?";
            $values[] = !empty($payload[$field]) ? 1 : 0;
        }
    }
    if (!$changes) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Aucune permission à modifier']);
        exit;
    }
    $values[] = $targetId;
    $stmt = $pdo->prepare('UPDATE users SET ' . implode(', ', $changes) . ', updated_at = NOW() WHERE id = ?');
    $stmt->execute($values);
    echo json_encode(['success' => true, 'message' => 'Permissions mises à jour']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
}
