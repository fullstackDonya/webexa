<?php

include("verify_subscriptions.php");

$user = getCurrentUser();
$user_id = $user['id'];
$customer_id = isset($_SESSION['customer_id']) ? (int)$_SESSION['customer_id'] : null;

// Log pour débogage
error_log("opportunities-add.php - user_id: $user_id, customer_id: " . ($customer_id ?? 'NULL'));

$success_message = $error_message = null;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Déterminer le customer_id à utiliser
        $opportunityCustomerId = $customer_id ?: (!empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null);
        
        error_log("Creating opportunity with customer_id: " . ($opportunityCustomerId ?? 'NULL'));

        $stmt = $pdo->prepare("
            INSERT INTO opportunities (
                title, description, company_id, contact_id, customer_id, assigned_to, stage, probability, amount, expected_close_date, source, competitor, loss_reason, next_action, next_action_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $_POST['title'],
            $_POST['description'],
            !empty($_POST['company_id']) ? $_POST['company_id'] : null,
            !empty($_POST['contact_id']) ? $_POST['contact_id'] : null,
            $opportunityCustomerId,
            $user_id,
            $_POST['stage'],
            !empty($_POST['probability']) ? $_POST['probability'] : 0,
            !empty($_POST['amount']) ? $_POST['amount'] : 0,
            !empty($_POST['expected_close_date']) ? $_POST['expected_close_date'] : null,
            $_POST['source'],
            $_POST['competitor'],
            $_POST['loss_reason'],
            $_POST['next_action'],
            !empty($_POST['next_action_date']) ? $_POST['next_action_date'] : null
        ]);

        $success_message = "Opportunité ajoutée avec succès !";
    } catch (Exception $e) {
        $error_message = "Erreur : " . $e->getMessage();
        error_log("Error creating opportunity: " . $e->getMessage());
    }
}

// Récupération des companies pour le select
$companies = [];
if ($customer_id) {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("
            SELECT id, name 
            FROM companies 
            WHERE customer_id = ? AND (interne_customer = 0 OR interne_customer IS NULL)
            ORDER BY name ASC
        ");
        $stmt->execute([$customer_id]);
        $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Found " . count($companies) . " companies for customer_id: $customer_id");
    } catch (Exception $e) {
        error_log("Error fetching companies: " . $e->getMessage());
    }
}

// Liste des étapes possibles
$stages = [
    'prospecting' => 'Prospection',
    'qualification' => 'Qualification',
    'needs_analysis' => 'Analyse',
    'proposal' => 'Proposition',
    'negotiation' => 'Négociation',
    'closed_won' => 'Fermé Gagné',
    'closed_lost' => 'Fermé Perdu'
];