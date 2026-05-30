<?php
/**
 * OAuth Callback Endpoint - Google Gmail
 * 
 * Handles OAuth2 callback from Google
 * Exchanges authorization code for access/refresh tokens
 */

session_start();
require_once __DIR__ . '/../../includes/env.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/GoogleOAuth.php';

try {
    // Get authorization code and state from query params
    $code = $_GET['code'] ?? null;
    $state = $_GET['state'] ?? null;
    $error = $_GET['error'] ?? null;
    
    // Check for OAuth errors
    if ($error) {
        throw new Exception('OAuth error: ' . $error);
    }
    
    if (!$code || !$state) {
        throw new Exception('Missing required parameters');
    }
    
    // Initialize OAuth handler
    $googleOAuth = new GoogleOAuth($pdo);
    
    // Handle callback and save configuration
    $result = $googleOAuth->handleCallback($code, $state);
    
    if ($result['success']) {
        // Redirect to settings page with success message
        $_SESSION['success_message'] = $result['message'];
        header('Location: ../../email-settings.php?connected=google&email=' . urlencode($result['email']));
    } else {
        throw new Exception($result['error']);
    }
    
} catch (Exception $e) {
    error_log("Google OAuth callback error: " . $e->getMessage());
    
    // Redirect to settings with error
    $_SESSION['error_message'] = 'Failed to connect Gmail: ' . $e->getMessage();
    header('Location: ../../email-settings.php?error=oauth');
}
exit;
