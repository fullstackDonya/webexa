<?php

session_start();
require_once __DIR__ . '/../config/database.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header('Location: ../../login.php');
    exit;
}

/**
 * 3️⃣ Création d’un nouveau customer
 */
$name        = trim($_POST['name'] ?? '');
$email       = trim($_POST['email'] ?? '');
$phone       = trim($_POST['phone'] ?? '');
$country     = trim($_POST['country'] ?? 'France');
$address     = trim($_POST['address'] ?? '');
$postal_code = trim($_POST['postal_code'] ?? '');
$validation_code = random_int(100000, 999999);

// Vérifie si déjà rattaché
$stmt = $pdo->prepare("SELECT customer_id FROM users WHERE id = ?");
$stmt->execute([$user_id]);
if ($stmt->fetchColumn()) {
    // stocker le customer_id selectionner
    $_SESSION['customer_id'] = $customer_id;

    header('Location: ../index.php?step=companyModal');
    exit;
}

// Crée le nouveau customer
$stmt = $pdo->prepare("
    INSERT INTO customers (name, email, phone, address, country, postal_code, validation_code, role, status, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'client', 'active', NOW(), NOW())
");
$stmt->execute([$name, $email, $phone, $address, $country, $postal_code, $validation_code]);

$customer_id = $pdo->lastInsertId();

// Lie l’utilisateur
$stmt = $pdo->prepare("UPDATE users SET customer_id = ? WHERE id = ?");
$stmt->execute([$customer_id, $user_id]);

$_SESSION['customer_id'] = $customer_id; // Stocke en session

// Redirige vers le modal société
header('Location: ../index.php?step=companyModal');
exit;













