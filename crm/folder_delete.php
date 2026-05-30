<?php
require_once __DIR__ . '/includes/verify_subscriptions.php';
$customer_id = $_SESSION['customer_id'] ?? null;

if(!isset($_GET['id'])){
    header('Location: folders.php?error=missing_id'); 
    exit;
}

$id = intval($_GET['id']);

// Vérifier que le dossier existe et appartient au client
$sql = 'SELECT id, name FROM folders WHERE id = ?';
$params = [$id];
if($customer_id){ 
    $sql .= ' AND customer_id = ?'; 
    $params[] = $customer_id; 
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$folder = $stmt->fetch();

if(!$folder){
    header('Location: folders.php?error=not_found'); 
    exit;
}

// Vérifier si le dossier contient des sous-dossiers ou des contacts
$checkSubfolders = $pdo->prepare('SELECT COUNT(*) FROM folders WHERE parent_id = ?');
$checkSubfolders->execute([$id]);
$subfoldersCount = $checkSubfolders->fetchColumn();

$checkContacts = $pdo->prepare('SELECT COUNT(*) FROM contacts WHERE folder_id = ?');
$checkContacts->execute([$id]);
$contactsCount = $checkContacts->fetchColumn();

if($subfoldersCount > 0 || $contactsCount > 0){
    header('Location: folders.php?error=folder_not_empty&subfolders=' . $subfoldersCount . '&contacts=' . $contactsCount); 
    exit;
}

try {
    // Supprimer le dossier
    $delFolder = $pdo->prepare('DELETE FROM folders WHERE id = ?');
    $delFolder->execute([$id]);
    
    header('Location: folders.php?success=deleted');
    exit;
    
} catch (Exception $e) {
    header('Location: folders.php?error=delete_failed');
    exit;
}
