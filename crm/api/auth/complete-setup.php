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

    // 1. Create/Update Company with interne_customer = 1
    $stmt = $pdo->prepare("
        INSERT INTO companies (
            name, email, phone, website, industry, siret, vat_number,
            employee_count, annual_revenue, status, is_active, interne_customer,
            created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'client', 1, 1, NOW(), NOW())
    ");
    
    $stmt->execute([
        $company['company_name'] ?? 'Entreprise',
        $profile['email'] ?? '',
        $profile['phone'] ?? '',
        $company['website'] ?? '',
        $company['industry'] ?? '',
        $company['siret'] ?? '',
        $company['vat_number'] ?? '',
        $company['employee_count'] ?? '',
        $company['annual_revenue'] ?? ''
    ]);

    $company_id = $pdo->lastInsertId();

    // 2. Link user to company
    $stmt = $pdo->prepare("UPDATE users SET onboarding_completed = 1 WHERE id = ?");
    $stmt->execute([$user_id]);

    // 3. Save enabled modules
    $modules_json = json_encode($modules);
    $stmt = $pdo->prepare("
        INSERT INTO user_settings (user_id, setting_key, setting_value, created_at, updated_at)
        VALUES (?, 'enabled_modules', ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
    ");
    $stmt->execute([$user_id, $modules_json, $modules_json]);

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
