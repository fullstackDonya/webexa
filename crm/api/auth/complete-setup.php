<?php
/**
 * API Endpoint: Complete Setup Wizard
 * Finalizes user onboarding and creates company with interne_customer = 1
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

function jsonResponse($success, $message, $data = []) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    jsonResponse(false, 'Method not allowed');
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    jsonResponse(false, 'Non authentifié');
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        jsonResponse(false, 'Données invalides');
    }

    $user_id = $_SESSION['user_id'];
    $profile = $input['profile'] ?? [];
    $company = $input['company'] ?? [];
    $modules = $input['modules'] ?? ['crm'];

    $pdo->beginTransaction();

    // 1. Créer ou mettre à jour le customer du compte
    $stmt = $pdo->prepare("SELECT customer_id FROM users WHERE id = ? FOR UPDATE");
    $stmt->execute([$user_id]);
    $customer_id = (int)$stmt->fetchColumn();
    $customerValues = [
        $company['company_name'] ?? 'Entreprise',
        $profile['email'] ?? '',
        $profile['phone'] ?? '',
        $profile['address'] ?? '',
        $profile['city'] ?? '',
        $profile['postal_code'] ?? '',
        $profile['position'] ?? '',
    ];

    if ($customer_id > 0) {
        $stmt = $pdo->prepare("UPDATE customers SET name=?, email=?, phone=?, address=?, city=?, postal_code=?, position=?, updated_at=NOW() WHERE id=?");
        $stmt->execute(array_merge($customerValues, [$customer_id]));
    } else {
        $stmt = $pdo->prepare("INSERT INTO customers (name, email, phone, address, city, postal_code, position, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())");
        $stmt->execute($customerValues);
        $customer_id = (int)$pdo->lastInsertId();
        if ($customer_id <= 0) {
            throw new RuntimeException('Customer non créé');
        }
        $pdo->prepare("UPDATE users SET customer_id=? WHERE id=?")->execute([$customer_id, $user_id]);
    }

    // 2. Créer ou mettre à jour la company interne du customer
    $stmt = $pdo->prepare("SELECT id FROM companies WHERE customer_id = ? LIMIT 1 FOR UPDATE");
    $stmt->execute([$customer_id]);
    $company_id = (int)$stmt->fetchColumn();
    $companyValues = [
        $company['company_name'] ?? 'Entreprise',
        $profile['email'] ?? '',
        $profile['phone'] ?? '',
        $company['website'] ?? '',
        $company['industry'] ?? '',
        $company['siret'] ?? '',
        $company['vat_number'] ?? '',
        $company['employee_count'] ?? '',
        $company['annual_revenue'] ?? '',
        $customer_id,
    ];

    if ($company_id > 0) {
        $stmt = $pdo->prepare("UPDATE companies SET name=?, email=?, phone=?, website=?, industry=?, siret=?, vat_number=?, employee_count=?, annual_revenue=?, customer_id=?, status='client', is_active=1, interne_customer=1, updated_at=NOW() WHERE id=?");
        $stmt->execute(array_merge($companyValues, [$company_id]));
    } else {
        $stmt = $pdo->prepare("INSERT INTO companies (name, email, phone, website, industry, siret, vat_number, employee_count, annual_revenue, customer_id, status, is_active, interne_customer, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'client', 1, 1, NOW(), NOW())");
        $stmt->execute($companyValues);
        $company_id = (int)$pdo->lastInsertId();
    }

    // 3. Sauvegarder les modules et valider l'onboarding après rattachement
    $modules_json = json_encode($modules);
    $stmt = $pdo->prepare("
        INSERT INTO user_settings (user_id, setting_key, setting_value, created_at, updated_at)
        VALUES (?, 'enabled_modules', ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
    ");
    $stmt->execute([$user_id, $modules_json, $modules_json]);

    $stmt = $pdo->prepare("UPDATE users SET first_name=?, last_name=?, customer_id=?, onboarding_completed=1, updated_at=NOW() WHERE id=? AND customer_id IS NOT NULL");
    $stmt->execute([
        $profile['first_name'] ?? '',
        $profile['last_name'] ?? '',
        $customer_id,
        $user_id,
    ]);
    $_SESSION['customer_id'] = $customer_id;

    $pdo->commit();

    jsonResponse(true, 'Configuration complétée', [
        'company_id' => $company_id,
        'interne_customer' => 1
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Complete setup error: ' . $e->getMessage());
    jsonResponse(false, 'Erreur: ' . $e->getMessage());
}
