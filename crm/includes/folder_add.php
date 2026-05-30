<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'verify_subscriptions.php';


session_start();
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$customer_id = $_SESSION['customer_id'] ?? null;

if ($customer_id) {
    $stmt = $pdo->prepare("SELECT id, name FROM companies WHERE customer_id = :customer_id AND interne_customer = 0 ORDER BY name ASC");
    $stmt->execute(['customer_id' => $customer_id]);
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Récupérer le prochain numéro de dossier
$nextId = 1;
$stmt = $pdo->query("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'folders'");
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $nextId = $row['AUTO_INCREMENT'];
}
$folder_number = 'DOSS-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);



// Traitement du formulaire en POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $folder_number = trim($_POST['folder_number'] ?? '');
    $company_id = !empty($_POST['company_id']) ? intval($_POST['company_id']) : null;
    $description = trim($_POST['description'] ?? '');
    $name = trim($_POST['name'] ?? '');


    // Validation simple
    $errors = [];
    if (empty($folder_number)) $errors[] = "Numéro de dossier manquant.";
    if (empty($company_id)) $errors[] = "Veuillez sélectionner une entreprise.";
    if (empty($name)) $errors[] = "Le nom du dossier est obligatoire."; 
  

    // Vérifier l'unicité du numéro de dossier
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM folders WHERE id = ?");
    $stmt->execute([$folder_number]);
    if ($stmt->fetchColumn() > 0) $errors[] = "Ce numéro de dossier existe déjà.";


    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO folders (company_id, assigned_to, name, description, created_at) VALUES (?, ?, ?, ?, NOW())");
        $success = $stmt->execute([
            $company_id,
            $user_id,
            $name,
            $description,

        
        ]);
        if ($success) {
            $lastId = $pdo->lastInsertId();
            $folder_number = 'DOSS-' . str_pad($lastId, 4, '0', STR_PAD_LEFT);
            $success_message = "Dossier ajouté avec succès. Numéro : $folder_number";
        } else {
            $errors[] = "Erreur lors de l'ajout du dossier.";
        }
    }
}






$page_title = "Ajouter un Dossier - CRM Intelligent";
