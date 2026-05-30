<?php

require_once 'verify_subscriptions.php';

$page_title = "Dossiers - CRM Intelligent";

// Récupérer le customer_id de l'utilisateur connecté
$customer_id = $_SESSION['customer_id'] ?? null;
$user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}
// Charger les dossiers liés aux entreprises du même customer_id
$folders = [];
$customer = null;

$companies = [];
if ($customer_id) {

    // Dossiers
    $stmt = $pdo->prepare("
        SELECT 
            f.id, 
            f.name, 
            f.created_at, 
            f.status_id,
            s.name AS status_name,
            c.name AS company_name
        FROM folders f
        INNER JOIN companies c ON f.company_id = c.id
        LEFT JOIN statuses s ON f.status_id = s.id
        WHERE c.customer_id = :customer_id AND c.interne_customer = 0
        ORDER BY f.created_at DESC
    ");
    $stmt->execute(['customer_id' => $customer_id]);
    $folders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Infos customer
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    // Entreprises liées (externes uniquement)
    $stmt = $pdo->prepare("SELECT * FROM companies WHERE customer_id = ? AND interne_customer = 0");
    $stmt->execute([$customer_id]);
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
}