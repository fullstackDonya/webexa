<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'verify_subscriptions.php';


$customer_id = $_SESSION['customer_id'] ?? null;
if ($customer_id === null) {
    die("Erreur : client non identifié.");
}
$page_title = "Éditer Contact - CRM Intelligent";
$success_message = '';
$error_message = '';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) { header('Location: contacts.php'); exit; }

// récupérer contact
try {
    $stmt = $pdo->prepare("SELECT * FROM leads WHERE id = ? AND (customer_id = ? OR assigned_to = ?) LIMIT 1");
    $stmt->execute([$id, $customer_id, $user_id]);
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$contact) { header('Location: contacts.php'); exit; }
} catch (Throwable $e) {
    error_log('contacts-edit fetch error: '.$e->getMessage());
    header('Location: contacts.php'); exit;
}

// récupérer companies pour select
$companies = [];
try {
    $stmt = $pdo->prepare("SELECT id, name FROM companies WHERE customer_id = ? ORDER BY name");
    $stmt->execute([$customer_id]);
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('companies fetch error: '.$e->getMessage());
}

// traitement POST (mise à jour)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? $contact['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? $contact['last_name'] ?? '');
    $email     = strtolower(trim($_POST['email'] ?? $contact['email'] ?? ''));
    $phone     = trim($_POST['phone'] ?? $contact['phone'] ?? '');
    /*$company_id= $_POST['company_id'] !== '' ? intval($_POST['company_id']) : null;*/
    $poste  = trim($_POST['poste'] ?? $contact['poste'] ?? '');
    $status    = trim($_POST['status'] ?? $contact['status'] ?? '');
    $source    = trim($_POST['source'] ?? $contact['source'] ?? '');
    $notes     = trim($_POST['notes'] ?? $contact['notes'] ?? '');

    if ($first_name === '' || $last_name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Prénom, nom et email valides sont requis.';
    } else {
        // vérifier doublon email autre id pour même customer
        $stmt = $pdo->prepare("SELECT id FROM leads WHERE email = ? AND customer_id = ? AND id <> ? LIMIT 1");
        $stmt->execute([$email, $customer_id, $id]);
        if ($stmt->fetchColumn()) {
            $error_message = 'Un autre contact avec cet email existe déjà pour ce client.';
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE leads SET
                        first_name = ?, last_name = ?, email = ?, phone = ?, poste = ?, status = ?, source = ?, notes = ?, updated_at = NOW()
                    WHERE id = ? AND customer_id = ?
                ");
                $stmt->execute([
                    $first_name,
                    $last_name,
                    $email,
                    $phone !== '' ? $phone : null,
                    $poste !== '' ? $poste : null,
                    $status !== '' ? $status : null,
                    $source !== '' ? $source : null,
                    $notes !== '' ? $notes : null,
                    $id,
                    $customer_id
                ]);
                $success_message = 'Contact mis à jour.';
                header('Location: contacts.php?msg=' . rawurlencode($success_message));
                exit;
            } catch (Throwable $e) {
                 error_log('contacts-edit update error: ' . $e->getMessage());
                $_SESSION['error_message'] = 'Erreur lors de la mise à jour.';
                header('Location: contacts-edit.php');
                exit;
            }
        }
    }
}
$success_message = '';
$error_message = '';