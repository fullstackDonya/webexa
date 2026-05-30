<?php


// inclure correctement verify_subscriptions (remonte d'un niveau depuis includes/forms)
require_once __DIR__ . '/../verify_subscriptions.php';



// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // récupérations sûres
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $industry = trim($_POST['industry'] ?? '');
    $segment = trim($_POST['segment'] ?? '');
    $annual_revenue = isset($_POST['annual_revenue']) ? floatval(str_replace(',', '.', $_POST['annual_revenue'])) : null;
    $status = trim($_POST['status'] ?? '');
    $satisfaction = isset($_POST['satisfaction']) ? floatval(str_replace(',', '.', $_POST['satisfaction'])) : null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    try {
        $stmt = $pdo->prepare("
            UPDATE companies SET
                name = :name,
                email = :email,
                phone = :phone,
                industry = :industry,
                segment = :segment,
                annual_revenue = :annual_revenue,
                status = :status,
                satisfaction = :satisfaction,
                is_active = :is_active,
                updated_at = NOW()
            WHERE id = :id AND customer_id = :customer_id AND interne_customer = 0
            LIMIT 1
        ");
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':phone' => $phone,
            ':industry' => $industry,
            ':segment' => $segment,
            ':annual_revenue' => $annual_revenue,
            ':status' => $status,
            ':satisfaction' => $satisfaction,
            ':is_active' => $is_active,
            ':id' => $id,
            ':customer_id' => $customer_id
        ]);
    } catch (Exception $e) {
        // log minimal et redirection propre
        error_log('customers-edit error: ' . $e->getMessage());
        header('Location: /crm/customers.php?error=update_failed');
        exit;
    }

    // après mise à jour, retourner à la liste customers.php
    header('Location: /crm/customers.php?updated=1');
    exit;
}
// ...existing code...