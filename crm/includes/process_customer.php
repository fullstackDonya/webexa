<?php

session_start();
require_once __DIR__ . '/../config/database.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header('Location: ../../login.php');
    exit;
}

/**
 * 1️⃣ Sélection d'un customer existant → Redirige vers la validation
 */
if (isset($_POST['existing_customer_id']) && !empty($_POST['existing_customer_id'])) {
    $customer_id = intval($_POST['existing_customer_id']);
    // Redirige vers le modal de validation société
    $_SESSION['pending_customer_id'] = $customer_id; // Stocke en session
    header('Location: ../index.php?step=customerValidationModal');
    exit;
}

/**
 * 2️⃣ Validation des infos société (customerValidationModal)
 */
if (
    !empty($_SESSION['pending_customer_id']) &&
    !empty($_POST['email']) &&
    !empty($_POST['postal_code']) &&
    !empty($_POST['validation_code'])
) {
    $customer_id = intval($_SESSION['pending_customer_id']);
    $email = trim($_POST['email']);
    $postal_code = trim($_POST['postal_code']);
    $validation_code = trim($_POST['validation_code']);

    // Vérifie dans la table customers
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE id = ? AND email = ? AND postal_code = ? AND validation_code = ?");
    $stmt->execute([$customer_id, $email, $postal_code, $validation_code]);
    $customer_exists = $stmt->fetchColumn();


    if ($customer_exists) {
        // Succès : rattacher le customer à l'utilisateur
        $stmt = $pdo->prepare("UPDATE users SET customer_id = ? WHERE id = ?");
        $stmt->execute([$customer_id, $user_id]);
        unset($_SESSION['pending_customer_id']); // Nettoie la session
        header('Location: ../index.php?step=companyModal');
        exit;
    } else {
        // Erreur : infos incorrectes
        header('Location: ../index.php?step=customerValidationModal&error=1');
        exit;
    }
}