<?php

require_once __DIR__ . '/verify_subscriptions.php';

$customer_id = $_SESSION['customer_id'] ?? null;

// If an id is provided, load that campaign
$campaign = null;
if(isset($_GET['id'])){
    $id = intval($_GET['id']);
    $sql = 'SELECT * FROM campaigns WHERE id = ?';
    $params = [$id];
    if($customer_id){
        $sql .= ' AND customer_id = ?';
        $params[] = $customer_id;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $campaign = $stmt->fetch();
}

// Récupérer les companies liées au même customer_id pour les listes d'audience
$companies = [];
if($customer_id){
    try{
        $cstmt = $pdo->prepare('SELECT id, name FROM companies WHERE customer_id = ? ORDER BY name ASC');
        $cstmt->execute([$customer_id]);
        $companies = $cstmt->fetchAll();
    }catch(Exception $e){
        // ignore, keep empty
        $companies = [];
    }
}

// Récupérer la liste des campagnes pour ce customer (liste principale)
$campaigns = [];
try{
    $sql = 'SELECT * FROM campaigns';
    $params = [];
    if($customer_id){
        $sql .= ' WHERE customer_id = ?';
        $params[] = $customer_id;
    }
    $sql .= ' ORDER BY created_at DESC LIMIT 500';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $campaigns = $stmt->fetchAll();
}catch(Exception $e){
    $campaigns = [];
}

