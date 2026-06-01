<?php
/**
 * OAuth Microsoft Handler
 * Initiates Microsoft OAuth flow
 */

session_start();
require __DIR__ . '/../../config/database.php';

// Get OAuth configuration
$microsoft_client_id = $_ENV['MICROSOFT_CLIENT_ID'] ?? null;
$microsoft_redirect_uri = $_ENV['APP_URL'] . '/crm/api/auth/oauth-microsoft-callback.php';

if (!$microsoft_client_id) {
    die('Microsoft OAuth not configured');
}

// Generate state token for CSRF protection
$state = bin2hex(random_bytes(32));
$_SESSION['oauth_state'] = $state;

// Build Microsoft OAuth URL
$microsoft_auth_url = 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize?' . http_build_query([
    'client_id' => $microsoft_client_id,
    'redirect_uri' => $microsoft_redirect_uri,
    'response_type' => 'code',
    'scope' => $_ENV['MICROSOFT_SCOPES'] ?? 'openid email profile offline_access Mail.Read Mail.Send',
    'state' => $state,
    'access_type' => 'offline'
]);

header('Location: ' . $microsoft_auth_url);
exit;
