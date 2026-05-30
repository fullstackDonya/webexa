<?php
require_once __DIR__ . '/verify_subscriptions.php';
header('Content-Type: application/json');

$customer_id = $_SESSION['customer_id'] ?? null;

try {
    $sql = 'SELECT id, name, open_rate, click_rate, opens_count, clicks_count, recipients_count FROM campaigns';
    $params = [];
    if ($customer_id) {
        $sql .= ' WHERE customer_id = ?';
        $params[] = $customer_id;
    }
    $sql .= ' ORDER BY id ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'campaigns' => $rows]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
    exit;
}
?>