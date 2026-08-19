<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Action invalide']);
    exit;
}

try {
    $action = $input['action'];

    switch ($action) {
        case 'save_profile':
            saveProfile($pdo, $user_id, $input['data']);
            break;
        
        case 'save_company':
            saveCompany($pdo, $user_id, $input['data']);
            break;
        
        case 'save_modules':
            saveModules($pdo, $user_id, $input['modules']);
            break;
        
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
            exit;
    }

} catch (Exception $e) {
    error_log('Settings API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}

function saveProfile($pdo, $user_id, $data) {
    $pdo->beginTransaction();

    try {
        // Update user
        $stmt = $pdo->prepare("
            UPDATE users SET
                first_name = ?,
                last_name = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $data['first_name'] ?? '',
            $data['last_name'] ?? '',
            $user_id
        ]);

        // Get or create customer
        $stmt = $pdo->prepare("SELECT customer_id FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $customer_id = $stmt->fetchColumn();

        if ($customer_id) {
            // Update customer
            $stmt = $pdo->prepare("
                UPDATE customers SET
                    email = ?,
                    phone = ?,
                    position = ?,
                    address = ?,
                    city = ?,
                    postal_code = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $data['email'] ?? '',
                $data['phone'] ?? '',
                $data['position'] ?? '',
                $data['address'] ?? '',
                $data['city'] ?? '',
                $data['postal_code'] ?? '',
                $customer_id
            ]);

            // Sync to companies
            syncToCompanies($pdo, $customer_id, $data);
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Profil enregistré']);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function saveCompany($pdo, $user_id, $data) {
    $pdo->beginTransaction();

    try {
        // Get customer_id
        $stmt = $pdo->prepare("SELECT customer_id FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $customer_id = $stmt->fetchColumn();

        if (!$customer_id) {
            throw new Exception('Aucun customer associé');
        }

        // Update customer name
        $stmt = $pdo->prepare("UPDATE customers SET name = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$data['company_name'] ?? '', $customer_id]);

        // Check if company exists
        $stmt = $pdo->prepare("SELECT id FROM companies WHERE customer_id = ? AND interne_customer = 1 LIMIT 1");
        $stmt->execute([$customer_id]);
        $company_id = $stmt->fetchColumn();

        if ($company_id) {
            // Update company
            $stmt = $pdo->prepare("
                UPDATE companies SET
                    name = ?,
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
                $data['company_name'] ?? '',
                $data['website'] ?? '',
                $data['industry'] ?? '',
                $data['siret'] ?? '',
                $data['vat_number'] ?? '',
                $data['employee_count'] ?? '',
                $data['annual_revenue'] ?? '',
                $company_id
            ]);
        } else {
            // Create company
            // Get customer data for initial values
            $stmt = $pdo->prepare("SELECT email, phone, address, city, postal_code FROM customers WHERE id = ?");
            $stmt->execute([$customer_id]);
            $customerData = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("
                INSERT INTO companies (
                    name, email, phone, address, city, postal_code, website,
                    industry, siret, vat_number, employee_count, annual_revenue,
                    customer_id, status, is_active, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'client', 1, NOW(), NOW())
            ");
            $stmt->execute([
                $data['company_name'] ?? '',
                $customerData['email'] ?? '',
                $customerData['phone'] ?? '',
                $customerData['address'] ?? '',
                $customerData['city'] ?? '',
                $customerData['postal_code'] ?? '',
                $data['website'] ?? '',
                $data['industry'] ?? '',
                $data['siret'] ?? '',
                $data['vat_number'] ?? '',
                $data['employee_count'] ?? '',
                $data['annual_revenue'] ?? '',
                $customer_id
            ]);
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Entreprise enregistrée']);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function saveModules($pdo, $user_id, $modules) {
    $modulesJson = json_encode($modules);
    
    $stmt = $pdo->prepare("
        INSERT INTO user_settings (user_id, setting_key, setting_value, created_at, updated_at)
        VALUES (?, 'enabled_modules', ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
    ");
    $stmt->execute([$user_id, $modulesJson, $modulesJson]);

    echo json_encode(['success' => true, 'message' => 'Modules enregistrés']);
}

function syncToCompanies($pdo, $customer_id, $data) {
    // Sync customer data to companies
    $stmt = $pdo->prepare("
        UPDATE companies SET
            email = ?,
            phone = ?,
            address = ?,
            city = ?,
            postal_code = ?,
            updated_at = NOW()
        WHERE customer_id = ?
    ");
    $stmt->execute([
        $data['email'] ?? '',
        $data['phone'] ?? '',
        $data['address'] ?? '',
        $data['city'] ?? '',
        $data['postal_code'] ?? '',
        $customer_id
    ]);
}
