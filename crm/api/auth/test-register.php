<?php
/**
 * Test Register - Detailed Debug
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

try {
    // Try to get table info
    $tables = $pdo->query("SHOW TABLES LIKE 'users'")->fetchAll(PDO::FETCH_NUM);
    
    if (empty($tables)) {
        echo json_encode(['error' => 'Table users does not exist']);
        exit;
    }
    
    // Try SELECT from users
    $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    
    $first_name = 'TestDebug';
    $last_name = 'User';
    $email = 'debug-' . time() . '@example.com';
    $username = 'user_' . time();
    $password = 'TestPassword123';
    $hashed = password_hash($password, PASSWORD_BCRYPT);
    
    // Start transaction
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("
        INSERT INTO users (username, first_name, last_name, email, password, phone, is_active, role, onboarding_completed)
        VALUES (:username, :first_name, :last_name, :email, :password, :phone, 1, 'sales', 0)
    ");
    
    $result = $stmt->execute([
        ':username' => $username,
        ':first_name' => $first_name,
        ':last_name' => $last_name,
        ':email' => $email,
        ':password' => $hashed,
        ':phone' => null
    ]);
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'email' => $email, 'username' => $username, 'users_count' => $count + 1]);
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'error' => 'PDO Error: ' . $e->getMessage(),
        'code' => $e->getCode()
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'error' => 'Exception: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
