<?php
/**
 * API Endpoint: Register
 * Creates a new user account
 * Returns JSON response with success/error
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../../config/mailer.php';

$autoloaders = [
    __DIR__ . '/../../../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
];
foreach ($autoloaders as $autoloader) {
    if (is_file($autoloader)) {
        require_once $autoloader;
        break;
    }
}

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
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $position = trim($_POST['position'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    // Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        jsonResponse(false, 'Tous les champs requis doivent être remplis');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(false, 'Format email invalide');
    }

    if (strlen($password) < 8) {
        jsonResponse(false, 'Le mot de passe doit contenir au moins 8 caractères');
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    if ($stmt->fetch()) {
        jsonResponse(false, 'Cet email est déjà enregistré');
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    $verify_token = bin2hex(random_bytes(32));

    // Start transaction
    $pdo->beginTransaction();

    // Generate username from email
    $username = strtolower(explode('@', $email)[0]) . '_' . time();

    // Create user
    $stmt = $pdo->prepare("
        INSERT INTO users (username, first_name, last_name, email, password, phone, role, is_active, onboarding_completed, email_verified, email_verify_token)
        VALUES (:username, :first_name, :last_name, :email, :password, :phone, 'sales', 1, 0, 0, :email_verify_token)
    ");
    
    $stmt->execute([
        ':username' => $username,
        ':first_name' => $first_name,
        ':last_name' => $last_name,
        ':email' => $email,
        ':password' => $hashed_password,
        ':phone' => $phone ?: null,
        ':email_verify_token' => $verify_token,
    ]);

    $user_id = $pdo->lastInsertId();

    // Set session
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name'] = "$first_name $last_name";

    $pdo->commit();

    $verify_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
        . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
        . '/forms/verify_email.php?token=' . urlencode($verify_token);
    $safeName = htmlspecialchars($first_name . ' ' . $last_name, ENT_QUOTES, 'UTF-8');
    $safeLink = htmlspecialchars($verify_link, ENT_QUOTES, 'UTF-8');
    $emailSent = sendEmail(
        $email,
        'Vérification de votre adresse email - Webexa By WebItech',
        "<h2>Bienvenue sur Webexa By WebItech !</h2>
         <p>Bonjour <strong>{$safeName}</strong>,</p>
         <p>Merci pour votre inscription sur Webexa By WebItech.</p>
         <p>Pour activer votre compte, veuillez vérifier votre adresse email :</p>
         <p><a href=\"{$safeLink}\" style=\"display:inline-block;padding:12px 24px;background:#007bff;color:white;text-decoration:none;border-radius:5px;\">Vérifier mon email</a></p>
         <p>Ou copiez ce lien dans votre navigateur :<br><code>{$safeLink}</code></p>
         <p>Cordialement,<br>L'équipe Webexa</p>"
    );
    if (!$emailSent) {
        error_log('[REGISTER] Email de vérification non envoyé pour user_id: ' . $user_id);
    }

    jsonResponse(true, 'Inscription réussie. Vérifiez votre adresse email.', [
        'user_id' => $user_id,
        'email' => $email,
        'name' => "$first_name $last_name"
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Register error: ' . $e->getMessage());
    http_response_code(500);
    jsonResponse(false, 'Erreur lors de l\'inscription');
}
