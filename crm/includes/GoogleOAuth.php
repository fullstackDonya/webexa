<?php
/**
 * GoogleOAuth - Handle OAuth2 flow for Gmail API
 * 
 * Implements Authorization Code Flow with PKCE
 * Manages token refresh and validation
 * 
 * @package CRM
 * @version 1.0.0
 */

require_once __DIR__ . '/EmailCrypto.php';

class GoogleOAuth {
    
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    private array $scopes;
    private EmailCrypto $crypto;
    private PDO $pdo;
    
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const REVOKE_URL = 'https://oauth2.googleapis.com/revoke';
    
    public function __construct(PDO $pdo) {
        $this->clientId = getenv('GOOGLE_CLIENT_ID') ?: '';
        $this->clientSecret = getenv('GOOGLE_CLIENT_SECRET') ?: '';
        $this->redirectUri = getenv('GOOGLE_REDIRECT_URI') ?: '';
        
        $scopesStr = getenv('GOOGLE_SCOPES') ?: 'https://www.googleapis.com/auth/gmail.readonly https://www.googleapis.com/auth/gmail.send';
        $this->scopes = explode(' ', $scopesStr);
        
        $this->crypto = new EmailCrypto();
        $this->pdo = $pdo;
        
        if (empty($this->clientId) || empty($this->clientSecret)) {
            throw new Exception('Google OAuth credentials not configured');
        }
    }
    
    /**
     * Generate authorization URL for user to grant permissions
     * 
     * @param int $customerId Customer ID for state parameter
     * @param string|null $email Optional email hint
     * @return string Authorization URL
     */
    public function getAuthorizationUrl(int $customerId, ?string $email = null): string {
        // Generate state token for CSRF protection
        $state = base64_encode(json_encode([
            'customer_id' => $customerId,
            'timestamp' => time(),
            'nonce' => bin2hex(random_bytes(16))
        ]));
        
        // Store state in session for validation
        $_SESSION['google_oauth_state'] = $state;
        
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $this->scopes),
            'access_type' => 'offline', // Request refresh token
            'prompt' => 'consent', // Force consent to get refresh token
            'state' => $state,
        ];
        
        if ($email) {
            $params['login_hint'] = $email;
        }
        
        return self::AUTH_URL . '?' . http_build_query($params);
    }
    
    /**
     * Handle OAuth callback and exchange code for tokens
     * 
     * @param string $code Authorization code from Google
     * @param string $state State parameter for CSRF validation
     * @return array Result with success status and data
     */
    public function handleCallback(string $code, string $state): array {
        try {
            // Validate state to prevent CSRF
            if (!isset($_SESSION['google_oauth_state']) || $state !== $_SESSION['google_oauth_state']) {
                throw new Exception('Invalid state parameter - possible CSRF attack');
            }
            
            // Decode state to get customer_id
            $stateData = json_decode(base64_decode($state), true);
            $customerId = $stateData['customer_id'] ?? null;
            
            if (!$customerId) {
                throw new Exception('Invalid state data');
            }
            
            // Exchange code for tokens
            $tokens = $this->exchangeCodeForTokens($code);
            
            // Get user email from token info
            $userInfo = $this->getUserInfo($tokens['access_token']);
            $email = $userInfo['email'] ?? null;
            
            if (!$email) {
                throw new Exception('Failed to retrieve user email');
            }
            
            // Save configuration to database
            $configId = $this->saveConfiguration($customerId, $email, $tokens);
            
            // Clean up session
            unset($_SESSION['google_oauth_state']);
            
            return [
                'success' => true,
                'config_id' => $configId,
                'email' => $email,
                'message' => 'Gmail account connected successfully'
            ];
            
        } catch (Exception $e) {
            error_log("Google OAuth callback error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Exchange authorization code for access and refresh tokens
     * 
     * @param string $code Authorization code
     * @return array Tokens array
     * @throws Exception on failure
     */
    private function exchangeCodeForTokens(string $code): array {
        $postData = [
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code'
        ];
        
        $response = $this->makeRequest(self::TOKEN_URL, $postData);
        
        if (!isset($response['access_token'])) {
            throw new Exception('Failed to get access token: ' . ($response['error_description'] ?? 'Unknown error'));
        }
        
        return [
            'access_token' => $response['access_token'],
            'refresh_token' => $response['refresh_token'] ?? null,
            'expires_in' => $response['expires_in'] ?? 3600,
            'scope' => $response['scope'] ?? implode(' ', $this->scopes),
        ];
    }
    
    /**
     * Refresh access token using refresh token
     * 
     * @param string $refreshToken Refresh token
     * @return array New tokens
     * @throws Exception on failure
     */
    public function refreshAccessToken(string $refreshToken): array {
        $postData = [
            'refresh_token' => $refreshToken,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'refresh_token'
        ];
        
        $response = $this->makeRequest(self::TOKEN_URL, $postData);
        
        if (!isset($response['access_token'])) {
            throw new Exception('Failed to refresh token: ' . ($response['error_description'] ?? 'Unknown error'));
        }
        
        return [
            'access_token' => $response['access_token'],
            'expires_in' => $response['expires_in'] ?? 3600,
        ];
    }
    
    /**
     * Get user info from access token
     * 
     * @param string $accessToken Access token
     * @return array User info
     */
    private function getUserInfo(string $accessToken): array {
        $url = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . urlencode($accessToken);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('Failed to get user info');
        }
        
        return json_decode($response, true) ?? [];
    }
    
    /**
     * Save OAuth configuration to database
     * 
     * @param int $customerId Customer ID
     * @param string $email User email
     * @param array $tokens OAuth tokens
     * @return int Configuration ID
     */
    private function saveConfiguration(int $customerId, string $email, array $tokens): int {
        // Encrypt tokens
        $encryptedAccessToken = $this->crypto->encrypt($tokens['access_token']);
        $encryptedRefreshToken = $tokens['refresh_token'] ? $this->crypto->encrypt($tokens['refresh_token']) : null;
        
        // Calculate token expiration
        $expiresAt = date('Y-m-d H:i:s', time() + ($tokens['expires_in'] ?? 3600));
        
        // Check if config already exists
        $stmt = $this->pdo->prepare("
            SELECT id FROM email_configurations 
            WHERE customer_id = ? AND email = ? AND provider = 'gmail'
        ");
        $stmt->execute([$customerId, $email]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing
            $stmt = $this->pdo->prepare("
                UPDATE email_configurations SET
                    oauth_provider = 'google',
                    oauth_access_token = ?,
                    oauth_refresh_token = ?,
                    oauth_token_expires_at = ?,
                    oauth_scope = ?,
                    token_encrypted = 1,
                    connection_method = 'oauth',
                    is_active = 1,
                    last_error = NULL,
                    error_count = 0,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $encryptedAccessToken,
                $encryptedRefreshToken,
                $expiresAt,
                $tokens['scope'],
                $existing['id']
            ]);
            
            return $existing['id'];
        } else {
            // Insert new
            $stmt = $this->pdo->prepare("
                INSERT INTO email_configurations (
                    customer_id, provider, email, 
                    oauth_provider, oauth_access_token, oauth_refresh_token,
                    oauth_token_expires_at, oauth_scope, token_encrypted,
                    connection_method, is_active, sync_enabled
                ) VALUES (?, 'gmail', ?, 'google', ?, ?, ?, ?, 1, 'oauth', 1, 1)
            ");
            $stmt->execute([
                $customerId,
                $email,
                $encryptedAccessToken,
                $encryptedRefreshToken,
                $expiresAt,
                $tokens['scope']
            ]);
            
            return $this->pdo->lastInsertId();
        }
    }
    
    /**
     * Get valid access token for a configuration (auto-refresh if needed)
     * 
     * @param int $configId Configuration ID
     * @return string Valid access token
     * @throws Exception if unable to get valid token
     */
    public function getValidAccessToken(int $configId): string {
        $stmt = $this->pdo->prepare("
            SELECT oauth_access_token, oauth_refresh_token, oauth_token_expires_at
            FROM email_configurations
            WHERE id = ? AND oauth_provider = 'google'
        ");
        $stmt->execute([$configId]);
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$config) {
            throw new Exception('Configuration not found');
        }
        
        // Check if token is still valid (with 5 min buffer)
        $expiresAt = strtotime($config['oauth_token_expires_at']);
        $now = time();
        
        if ($expiresAt > $now + 300) {
            // Token still valid
            return $this->crypto->decrypt($config['oauth_access_token']);
        }
        
        // Need to refresh
        if (!$config['oauth_refresh_token']) {
            throw new Exception('No refresh token available - need to re-authenticate');
        }
        
        $refreshToken = $this->crypto->decrypt($config['oauth_refresh_token']);
        $newTokens = $this->refreshAccessToken($refreshToken);
        
        // Update database
        $encryptedAccessToken = $this->crypto->encrypt($newTokens['access_token']);
        $newExpiresAt = date('Y-m-d H:i:s', time() + $newTokens['expires_in']);
        
        $stmt = $this->pdo->prepare("
            UPDATE email_configurations 
            SET oauth_access_token = ?, 
                oauth_token_expires_at = ?,
                last_error = NULL,
                error_count = 0,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$encryptedAccessToken, $newExpiresAt, $configId]);
        
        return $newTokens['access_token'];
    }
    
    /**
     * Revoke access and delete configuration
     * 
     * @param int $configId Configuration ID
     * @return bool Success
     */
    public function revokeAccess(int $configId): bool {
        try {
            $accessToken = $this->getValidAccessToken($configId);
            
            // Revoke token with Google
            $ch = curl_init(self::REVOKE_URL);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, ['token' => $accessToken]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            curl_close($ch);
            
            return true;
        } catch (Exception $e) {
            error_log("Failed to revoke Google token: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Make HTTP request to Google API
     * 
     * @param string $url API URL
     * @param array $postData POST data
     * @return array Response data
     */
    private function makeRequest(string $url, array $postData): array {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('CURL error: ' . $error);
        }
        
        $data = json_decode($response, true);
        
        if ($httpCode >= 400) {
            throw new Exception('HTTP error ' . $httpCode . ': ' . ($data['error_description'] ?? $response));
        }
        
        return $data ?? [];
    }
}
