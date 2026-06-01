<?php
/**
 * API Endpoint: Login
 * Handles user login with email/password
 * Returns JSON response with success/error
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/../../config/database.php';

// Security headers
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Response helper
function jsonResponse($success, $message, $data = []) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    jsonResponse(false, 'Method not allowed');
}

try {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        jsonResponse(false, 'Email et mot de passe requis');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(false, 'Format email invalide');
    }

    // Find user by email
    $stmt = $pdo->prepare("
        SELECT id, first_name, last_name, email, password, onboarding_completed, customer_id, is_active
        FROM users
        WHERE email = :email AND is_active = 1
        LIMIT 1
    ");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        jsonResponse(false, 'Email ou mot de passe incorrect');
    }

    // Verify password
    if (!password_verify($password, $user['password'])) {
        jsonResponse(false, 'Email ou mot de passe incorrect');
    }

    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
    
    if ($user['customer_id']) {
        $_SESSION['customer_id'] = $user['customer_id'];
    }

    jsonResponse(true, 'Connexion réussie', [
        'user_id' => $user['id'],
        'email' => $user['email'],
        'name' => $user['first_name'] . ' ' . $user['last_name'],
        'onboarding_completed' => $user['onboarding_completed']
    ]);

} catch (Exception $e) {
    error_log('Login error: ' . $e->getMessage());
    http_response_code(500);
    jsonResponse(false, 'Erreur serveur');
}
