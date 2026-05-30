<?php
session_start();
require_once __DIR__ . '/../../crm/config/database.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) { header('Location: ../../login.php'); exit; }

// 1) Sélection d'un customer existant -> redirection vers validation
if (!empty($_POST['existing_customer_id'])) {
    $cid = (int)$_POST['existing_customer_id'];
    $_SESSION['pending_customer_id'] = $cid;
    header('Location: ../index.php?erp_step=customerValidationModal');
    exit;
}

// 2) Validation des infos société
if (!empty($_SESSION['pending_customer_id']) && !empty($_POST['email']) && !empty($_POST['postal_code']) && !empty($_POST['validation_code'])) {
    $cid = (int)$_SESSION['pending_customer_id'];
    $email = trim($_POST['email']);
    $postal = trim($_POST['postal_code']);
    $code = trim($_POST['validation_code']);

    $stmt = $pdo->prepare('SELECT id FROM customers WHERE id=? AND email=? AND postal_code=? AND validation_code=?');
    $stmt->execute([$cid, $email, $postal, $code]);
    $ok = $stmt->fetchColumn();

    if ($ok) {
        // Link user to customer and set session
        $pdo->prepare('UPDATE users SET customer_id=? WHERE id=?')->execute([$cid, $user_id]);
        $_SESSION['customer_id'] = $cid;
        unset($_SESSION['pending_customer_id']);
        header('Location: ../index.php');
        exit;
    } else {
        header('Location: ../index.php?erp_step=customerValidationModal&error=1');
        exit;
    }
}

// Default fallback
header('Location: ../index.php');
exit;