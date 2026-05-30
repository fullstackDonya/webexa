<?php
require_once __DIR__ . '/includes/verify_subscriptions.php';
$customer_id = $_SESSION['customer_id'] ?? null;

if(!isset($_GET['id'])){
    header('Location: customers.php?error=missing_id'); 
    exit;
}

$id = intval($_GET['id']);

// Vérifier que le client existe
$sql = 'SELECT id, name FROM customers WHERE id = ?';
$params = [$id];
if($customer_id){ 
    $sql .= ' AND customer_id = ?'; 
    $params[] = $customer_id; 
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$client = $stmt->fetch();

if(!$client){
    header('Location: customers.php?error=not_found'); 
    exit;
}

try {
    // Commencer une transaction
    $pdo->beginTransaction();
    
    // Supprimer les contacts associés à ce client
    $delContacts = $pdo->prepare('UPDATE contacts SET company_id = NULL WHERE company_id = ?');
    $delContacts->execute([$id]);
    
    // Supprimer les opportunités associées
    $delOpportunities = $pdo->prepare('DELETE FROM opportunities WHERE company_id = ?');
    $delOpportunities->execute([$id]);
    
    // Supprimer les leads associés
    $delLeads = $pdo->prepare('UPDATE leads SET company_id = NULL WHERE company_id = ?');
    $delLeads->execute([$id]);
    
    // Supprimer le client
    $delClient = $pdo->prepare('DELETE FROM customers WHERE id = ?');
    $delClient->execute([$id]);
    
    // Valider la transaction
    $pdo->commit();
    
    header('Location: customers.php?success=deleted');
    exit;
    
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    $pdo->rollBack();
    header('Location: customers.php?error=delete_failed');
    exit;
}
