<?php
require_once __DIR__ . '/includes/verify_subscriptions.php';
$customer_id = $_SESSION['customer_id'] ?? null;

if(!isset($_GET['id'])){
    header('Location: leads.php?error=missing_id'); 
    exit;
}

$id = intval($_GET['id']);

// Vérifier que le lead existe et appartient au client
$sql = 'SELECT id, first_name, last_name FROM leads WHERE id = ?';
$params = [$id];
if($customer_id){ 
    $sql .= ' AND customer_id = ?'; 
    $params[] = $customer_id; 
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$lead = $stmt->fetch();

if(!$lead){
    header('Location: leads.php?error=not_found'); 
    exit;
}

try {
    // Commencer une transaction
    $pdo->beginTransaction();
    
    // Supprimer les activités associées au lead
    $delActivities = $pdo->prepare('DELETE FROM activities WHERE lead_id = ?');
    $delActivities->execute([$id]);
    
    // Supprimer les notes associées
    $delNotes = $pdo->prepare('DELETE FROM notes WHERE lead_id = ?');
    $delNotes->execute([$id]);
    
    // Supprimer les scores de lead
    $delScores = $pdo->prepare('DELETE FROM lead_scores WHERE lead_id = ?');
    $delScores->execute([$id]);
    
    // Supprimer le lead
    $delLead = $pdo->prepare('DELETE FROM leads WHERE id = ?');
    $delLead->execute([$id]);
    
    // Valider la transaction
    $pdo->commit();
    
    header('Location: leads.php?success=deleted');
    exit;
    
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    $pdo->rollBack();
    header('Location: leads.php?error=delete_failed');
    exit;
}
