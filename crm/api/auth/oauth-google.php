<?php
/**
 * OAuth Google Handler
 * Initiates Google OAuth flow
 */

session_start();
require __DIR__ . '/../../config/database.php';

// Get OAuth configuration
$google_client_id = $_ENV['GOOGLE_CLIENT_ID'] ?? null;
$google_redirect_uri = $_ENV['APP_URL'] . '/crm/api/auth/oauth-google-callback.php';

if (!$google_client_id) {
    die('Google OAuth not configured');
}

// var_dump($google_redirect_uri);
// exit;

// Generate state token for CSRF protection
$state = bin2hex(random_bytes(32));
$_SESSION['oauth_state'] = $state;

// Build Google OAuth URL
$google_auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'client_id' => $google_client_id,
    'redirect_uri' => $google_redirect_uri,
    'response_type' => 'code',
    'scope' => $_ENV['GOOGLE_SCOPES'] ?? 'email profile',
    'state' => $state,
    'access_type' => 'offline'
]);

header('Location: ' . $google_auth_url);
exit;
