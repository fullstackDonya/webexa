<?php
require_once __DIR__ . '/../../crm/config/database.php';
header('Content-Type: application/json');

$customer_id = $_SESSION['customer_id'] ?? null;
if ($customer_id) {
    $stmt = $pdo->prepare('SELECT id, first_name, last_name, email, job_title, department, status FROM erp_employees WHERE customer_id = ? ORDER BY last_name, first_name');
    $stmt->execute([$customer_id]);
    $rows = $stmt->fetchAll();
} else {
    $rows = [];
}
echo json_encode(['data' => $rows]);