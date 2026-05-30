<?php
session_start();
require_once __DIR__ . '/../../crm/config/database.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) { header('Location: ../../login.php'); exit; }

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$country = trim($_POST['country'] ?? 'France');
$address = trim($_POST['address'] ?? '');
$postal_code = trim($_POST['postal_code'] ?? '');
$validation_code = random_int(100000, 999999);

if ($name === '' || $email === '') {
    header('Location: ../index.php?error=missing_fields');
    exit;
}

// If user already has a customer, just persist to session and continue
$stmt = $pdo->prepare('SELECT customer_id FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$existing = $stmt->fetchColumn();
if ($existing) {
    $_SESSION['customer_id'] = (int)$existing;
    header('Location: ../index.php');
    exit;
}

// Create customer
$stmt = $pdo->prepare('INSERT INTO customers (name, email, phone, address, country, postal_code, validation_code, role, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, "client", "active", NOW(), NOW())');
$stmt->execute([$name, $email, $phone, $address, $country, $postal_code, $validation_code]);
$customer_id = (int)$pdo->lastInsertId();

// Link user
$pdo->prepare('UPDATE users SET customer_id=? WHERE id=?')->execute([$customer_id, $user_id]);
$_SESSION['customer_id'] = $customer_id;

header('Location: ../index.php');
exit;