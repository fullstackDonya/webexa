<?php
require_once __DIR__ . '/includes/verify_subscriptions.php';
require_once __DIR__ . '/config/database.php';
header('Content-Type: application/json; charset=utf-8');

// ensure session is started by verify_subscriptions.php but guard anyway
if(session_status() === PHP_SESSION_NONE) session_start();
$customer_id = $_SESSION['customer_id'] ?? null;
try{
    if(!empty($customer_id)){
        $stmt = $pdo->prepare('SELECT * FROM automations WHERE customer_id = ? ORDER BY created_at DESC');
        $stmt->execute([$customer_id]);
    }else{
        $stmt = $pdo->query('SELECT * FROM automations ORDER BY created_at DESC');
    }
    $rows = $stmt->fetchAll();
    // enrich with contacts_count
    foreach($rows as &$r){
        $r['contacts_count'] = 0;
        try{
            if(!empty($r['customer_id'])){
                $c = $pdo->prepare('SELECT COUNT(*) as c FROM contacts WHERE customer_id = ?');
                $c->execute([$r['customer_id']]);
            }else{
                $c = $pdo->query('SELECT COUNT(*) as c FROM contacts');
            }
            $cres = $c->fetch();
            $r['contacts_count'] = intval($cres['c'] ?? 0);
        }catch(Exception $ee){ $r['contacts_count'] = 0; }
    }
    echo json_encode(['success'=>true,'data'=>$rows]);
}catch(Exception $e){
    // log and return generic message
    error_log('automations-list error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Erreur serveur lors de la récupération des automatisations']);
}

?>
