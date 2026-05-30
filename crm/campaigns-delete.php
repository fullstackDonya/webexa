<?php
require_once __DIR__ . '/includes/verify_subscriptions.php';
require_once __DIR__ . '/config/database.php';
session_start();
$customer_id = $_SESSION['customer_id'] ?? null;

if(!isset($_GET['id'])){
    header('Location: campaigns.php?error=missing_id'); exit;
}
$id = intval($_GET['id']);

// Ensure campaign exists and belongs to customer (if applicable)
$sql = 'SELECT id FROM campaigns WHERE id = ?';
$params = [$id];
if($customer_id){ $sql .= ' AND customer_id = ?'; $params[] = $customer_id; }
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$camp = $stmt->fetch();
if(!$camp){
    header('Location: campaigns.php?error=not_found'); exit;
}

// Delete
$del = $pdo->prepare('DELETE FROM campaigns WHERE id = ?');
$del->execute([$id]);

header('Location: campaigns.php?success=deleted');
exit;
