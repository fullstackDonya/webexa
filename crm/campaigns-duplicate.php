<?php
require_once __DIR__ . '/includes/verify_subscriptions.php';
require_once __DIR__ . '/config/database.php';
session_start();
$customer_id = $_SESSION['customer_id'] ?? null;

if(!isset($_GET['id'])){ header('Location: campaigns.php?error=missing_id'); exit; }
$id = intval($_GET['id']);

// Load original
$sql = 'SELECT * FROM campaigns WHERE id = ?';
$params = [$id];
if($customer_id){ $sql .= ' AND customer_id = ?'; $params[] = $customer_id; }
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orig = $stmt->fetch();
if(!$orig){ header('Location: campaigns.php?error=not_found'); exit; }

// Insert duplicate (prefix name)
$newName = $orig['name'] . ' (copie)';
// Insert duplicate, include customer_id and optional metadata columns
$ins = $pdo->prepare('INSERT INTO campaigns (customer_id,name,type,subject,status,scheduled_at,sender_name,sender_email,audience,recipients,open_rate,click_rate) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
$ins->execute([
	$orig['customer_id'] ?? $customer_id ?? 0,
	$newName,
	$orig['type'] ?? null,
	$orig['subject'] ?? null,
	$orig['status'] ?? 'draft',
	$orig['scheduled_at'] ?? null,
	$orig['sender_name'] ?? null,
	$orig['sender_email'] ?? null,
	$orig['audience'] ?? null,
	$orig['recipients'] ?? 0,
	$orig['open_rate'] ?? 0.0,
	$orig['click_rate'] ?? 0.0
]);

header('Location: campaigns.php?success=duplicated');
exit;
