<?php
/**
 * OAuth Callback Endpoint - Microsoft Outlook
 * 
 * Handles OAuth2 callback from Microsoft
 * Exchanges authorization code for access/refresh tokens
 */

session_start();
require_once __DIR__ . '/../../includes/env.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/MicrosoftOAuth.php';

try {
    // Get authorization code and state from query params
    $code = $_GET['code'] ?? null;
    $state = $_GET['state'] ?? null;
    $error = $_GET['error'] ?? null;
    $errorDescription = $_GET['error_description'] ?? null;
    
    // Check for OAuth errors
    if ($error) {
        throw new Exception('OAuth error: ' . $error . ' - ' . $errorDescription);
    }
    
    if (!$code || !$state) {
        throw new Exception('Missing required parameters');
    }
    
    // Initialize OAuth handler
    $msOAuth = new MicrosoftOAuth($pdo);
    
    // Handle callback and save configuration
    $result = $msOAuth->handleCallback($code, $state);
    
    if ($result['success']) {
        // Redirect to settings page with success message
        $_SESSION['success_message'] = $result['message'];
        header('Location: /crm/email-settings.php?connected=microsoft&email=' . urlencode($result['email']));
    } else {
        throw new Exception($result['error']);
    }
    
} catch (Exception $e) {
    error_log("Microsoft OAuth callback error: " . $e->getMessage());
    
    // Redirect to settings with error
    $_SESSION['error_message'] = 'Failed to connect Outlook: ' . $e->getMessage();
    header('Location: /crm/email-settings.php?error=oauth');
}
exit;
