<?php

require_once 'verify_subscriptions.php';

// Récupérer l'utilisateur connecté
$user_id = $_SESSION['user_id'];
$customer_id = $_SESSION['customer_id'];



// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Sécurisation du statut
        $allowed_status = ['prospect', 'client', 'partner', 'inactive'];
        $status = in_array($_POST['status'], $allowed_status) ? $_POST['status'] : 'prospect';

  
        $stmt = $pdo->prepare("
            INSERT INTO companies (
                name, email, phone, industry, address, 
                city, country, website, annual_revenue, 
                employee_count, status, notes, assigned_to, customer_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $_POST['name'],
            $_POST['email'],
            $_POST['phone'],
            $_POST['industry'],
            $_POST['address'],
            $_POST['city'],
            $_POST['country'],
            $_POST['website'],
            !empty($_POST['annual_revenue']) ? $_POST['annual_revenue'] : null,
            !empty($_POST['employee_count']) ? $_POST['employee_count'] : null,
            $status, // On utilise la variable sécurisée ici
            $_POST['notes'],
            $user_id,
            $customer_id
        ]);

        $success_message = "Client ajouté avec succès !";

    } catch (Exception $e) {
        $error_message = "Erreur : " . $e->getMessage();
    }
}

// Récupération des industries pour le select
$industries = [
    'Technology', 'Healthcare', 'Finance', 'Manufacturing', 
    'Retail', 'Education', 'Real Estate', 'Consulting',
    'Marketing', 'Construction', 'Transportation', 'Other'
];