<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Récupérer ou créer le customer
    $stmt = $pdo->prepare("SELECT customer_id FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $existing_customer_id = $stmt->fetchColumn();

    $customer_id = null;

    if ($existing_customer_id) {
        // Update existing customer
        $customer_id = $existing_customer_id;
        
        $stmt = $pdo->prepare("
            UPDATE customers SET
                name = ?,
                email = ?,
                phone = ?,
                address = ?,
                city = ?,
                postal_code = ?,
                position = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $data['company']['company_name'] ?? '',
            $data['profile']['email'] ?? '',
            $data['profile']['phone'] ?? '',
            $data['profile']['address'] ?? '',
            $data['profile']['city'] ?? '',
            $data['profile']['postal_code'] ?? '',
            $data['profile']['position'] ?? '',
            $customer_id
        ]);
    } else {
        // Create new customer
        $stmt = $pdo->prepare("
            INSERT INTO customers (
                name, email, phone, address, city, postal_code, position, 
                status, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())
        ");
        
        $stmt->execute([
            $data['company']['company_name'] ?? '',
            $data['profile']['email'] ?? '',
            $data['profile']['phone'] ?? '',
            $data['profile']['address'] ?? '',
            $data['profile']['city'] ?? '',
            $data['profile']['postal_code'] ?? '',
            $data['profile']['position'] ?? ''
        ]);
        
        $customer_id = $pdo->lastInsertId();
        
        // Link user to customer
        $stmt = $pdo->prepare("UPDATE users SET customer_id = ? WHERE id = ?");
        $stmt->execute([$customer_id, $user_id]);
    }

    // 2. Créer ou mettre à jour la company avec les données du customer
    $stmt = $pdo->prepare("
        SELECT id FROM companies WHERE customer_id = ? LIMIT 1
    ");
    $stmt->execute([$customer_id]);
    $existing_company_id = $stmt->fetchColumn();

    if ($existing_company_id) {
        // Update existing company
        $stmt = $pdo->prepare("
            UPDATE companies SET
                name = ?,
                email = ?,
                phone = ?,
                address = ?,
                city = ?,
                postal_code = ?,
                website = ?,
                industry = ?,
                siret = ?,
                vat_number = ?,
                employee_count = ?,
                annual_revenue = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $data['company']['company_name'] ?? '',
            $data['profile']['email'] ?? '',
            $data['profile']['phone'] ?? '',
            $data['profile']['address'] ?? '',
            $data['profile']['city'] ?? '',
            $data['profile']['postal_code'] ?? '',
            $data['company']['website'] ?? '',
            $data['company']['industry'] ?? '',
            $data['company']['siret'] ?? '',
            $data['company']['vat_number'] ?? '',
            $data['company']['employee_count'] ?? '',
            $data['company']['annual_revenue'] ?? '',
            $existing_company_id
        ]);
    } else {
        // Create new company
        $stmt = $pdo->prepare("
            INSERT INTO companies (
                name, email, phone, address, city, postal_code, website,
                industry, siret, vat_number, employee_count, annual_revenue,
                customer_id, status, is_active, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'client', 1, NOW(), NOW())
        ");
        
        $stmt->execute([
            $data['company']['company_name'] ?? '',
            $data['profile']['email'] ?? '',
            $data['profile']['phone'] ?? '',
            $data['profile']['address'] ?? '',
            $data['profile']['city'] ?? '',
            $data['profile']['postal_code'] ?? '',
            $data['company']['website'] ?? '',
            $data['company']['industry'] ?? '',
            $data['company']['siret'] ?? '',
            $data['company']['vat_number'] ?? '',
            $data['company']['employee_count'] ?? '',
            $data['company']['annual_revenue'] ?? '',
            $customer_id
        ]);
    }

    // 3. Sauvegarder les modules sélectionnés
    $modules = $data['modules'] ?? ['crm'];
    $modulesJson = json_encode($modules);
    
    $stmt = $pdo->prepare("
        INSERT INTO user_settings (user_id, setting_key, setting_value, created_at, updated_at)
        VALUES (?, 'enabled_modules', ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
    ");
    $stmt->execute([$user_id, $modulesJson, $modulesJson]);

    // 4. Update user info
    $stmt = $pdo->prepare("
        UPDATE users SET
            first_name = ?,
            last_name = ?,
            onboarding_completed = 1,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([
        $data['profile']['first_name'] ?? '',
        $data['profile']['last_name'] ?? '',
        $user_id
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Configuration complétée avec succès',
        'customer_id' => $customer_id
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Setup wizard error: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la configuration: ' . $e->getMessage()
    ]);
}
