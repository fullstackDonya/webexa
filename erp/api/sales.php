<?php
declare(strict_types=1);

require_once __DIR__ . '/common.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? 'view';

if ($action === 'fetch' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $customer_id = $_SESSION['customer_id'] ?? null;
    if (empty($customer_id)) { echo json_encode([]); exit; }
    $stmt = $pdo->prepare("SELECT s.*, e.first_name, e.last_name, p.product_name AS product_name FROM erp_sales s JOIN erp_employees e ON e.id = s.employee_id JOIN erp_stock p ON p.id = s.product_id WHERE s.customer_id = ? ORDER BY s.created_at DESC");
    $stmt->execute([$customer_id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_SESSION['customer_id'] ?? null;
    $data = [
        'product_id' => (int)($_POST['product_id'] ?? 0),
        'employee_id' => (int)($_POST['employee_id'] ?? 0),
        'quantity' => (int)($_POST['quantity'] ?? 0),
        'total_price' => (float)($_POST['total_price'] ?? 0),
    ];

    if (empty($customer_id) || $data['product_id'] <= 0 || $data['employee_id'] <= 0 || $data['quantity'] <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid data']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO erp_sales (product_id, employee_id, quantity, total_price, customer_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$data['product_id'], $data['employee_id'], $data['quantity'], $data['total_price'], $customer_id]);
    echo json_encode(['id' => (int)$pdo->lastInsertId()]);
    exit;
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $customer_id = $_SESSION['customer_id'] ?? null;
    $data = [
        'product_id' => (int)($_POST['product_id'] ?? 0),
        'employee_id' => (int)($_POST['employee_id'] ?? 0),
        'quantity' => (int)($_POST['quantity'] ?? 0),
        'total_price' => (float)($_POST['total_price'] ?? 0),
    ];

    if ($id <= 0 || empty($customer_id) || $data['product_id'] <= 0 || $data['employee_id'] <= 0 || $data['quantity'] <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid data']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE erp_sales SET product_id=?, employee_id=?, quantity=?, total_price=? WHERE id=? AND customer_id=?");
    $stmt->execute([$data['product_id'], $data['employee_id'], $data['quantity'], $data['total_price'], $id, $customer_id]);
    echo json_encode(['ok' => true]);
    exit;
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $customer_id = $_SESSION['customer_id'] ?? null;
    if ($id <= 0 || empty($customer_id)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid id']);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM erp_sales WHERE id=? AND customer_id=?");
    $stmt->execute([$id, $customer_id]);
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>