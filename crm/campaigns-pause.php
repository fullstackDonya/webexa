<?php
require_once __DIR__ . '/config/database.php';
session_start();
$customer_id = $_SESSION['customer_id'] ?? null;

if(!isset($_GET['id'])){ header('Location: campaigns.php?error=missing_id'); exit; }
$id = intval($_GET['id']);

// Verify ownership
$sql = 'SELECT id, status FROM campaigns WHERE id = ?'; $params = [$id];
if($customer_id){ $sql .= ' AND customer_id = ?'; $params[] = $customer_id; }
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$campaign = $stmt->fetch();
if(!$campaign){ header('Location: campaigns.php?error=not_found'); exit; }

$newStatus = ($campaign['status'] === 'paused') ? 'active' : 'paused';
$upd = $pdo->prepare('UPDATE campaigns SET status = ? WHERE id = ?');
$upd->execute([$newStatus, $id]);
header('Location: campaigns-email.php?id=' . $id . '&success=status_updated');
exit;

?>
