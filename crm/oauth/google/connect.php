<?php
/**
 * OAuth Connect Endpoint - Google Gmail
 * 
 * Initiates OAuth2 flow for Gmail API
 */

session_start();
require_once __DIR__ . '/../../includes/env.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/GoogleOAuth.php';

header('Content-Type: application/json');

try {
    // Verify user is authenticated
    $customerId = $_SESSION['customer_id'] ?? null;
    
    if (!$customerId) {
        throw new Exception('User not authenticated');
    }
    
    // Optional: email hint from request
    $email = $_GET['email'] ?? null;
    
    // Initialize OAuth handler
    $googleOAuth = new GoogleOAuth($pdo);
    
    // Get authorization URL
    $authUrl = $googleOAuth->getAuthorizationUrl($customerId, $email);
    
    // Return URL for redirect
    echo json_encode([
        'success' => true,
        'auth_url' => $authUrl
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    error_log("Google OAuth connect error: " . $e->getMessage());
}
