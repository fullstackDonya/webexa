<?php
require_once __DIR__ . '/includes/verify_subscriptions.php';
require_once __DIR__ . '/config/database.php';
$customer_id = $_SESSION['customer_id'] ?? null;
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if($id <= 0){ header('Location: campaigns-automation.php'); exit; }
try{
    $stmt = $pdo->prepare('SELECT customer_id FROM automations WHERE id = ?');
    $stmt->execute([$id]);
    $r = $stmt->fetch();
    if($r && !empty($customer_id) && $r['customer_id'] != $customer_id){ header('HTTP/1.1 403 Forbidden'); echo 'Accès refusé'; exit; }
    // delete steps and logs if tables exist
    try{ $pdo->prepare('DELETE FROM automation_steps WHERE automation_id = ?')->execute([$id]); }catch(Exception $e){}
    try{ $pdo->prepare('DELETE FROM automation_logs WHERE automation_id = ?')->execute([$id]); }catch(Exception $e){}
    $pdo->prepare('DELETE FROM automations WHERE id = ?')->execute([$id]);
}catch(Exception $e){ error_log('automations-delete error: '.$e->getMessage()); }
header('Location: campaigns-automation.php'); exit;
