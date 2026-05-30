<?php
/**
 * MicrosoftOAuth - Handle OAuth2 flow for Microsoft Graph (Outlook)
 * 
 * Implements Authorization Code Flow
 * Manages token refresh and validation
 * 
 * @package CRM
 * @version 1.0.0
 */

require_once __DIR__ . '/EmailCrypto.php';

class MicrosoftOAuth {
    
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    private string $tenant;
    private array $scopes;
    private EmailCrypto $crypto;
    private PDO $pdo;
    
    private const AUTH_BASE = 'https://login.microsoftonline.com';
    private const GRAPH_BASE = 'https://graph.microsoft.com/v1.0';
    
    public function __construct(PDO $pdo) {
        $this->clientId = getenv('MICROSOFT_CLIENT_ID') ?: '';
        $this->clientSecret = getenv('MICROSOFT_CLIENT_SECRET') ?: '';
        $this->redirectUri = getenv('MICROSOFT_REDIRECT_URI') ?: '';
        $this->tenant = getenv('MICROSOFT_TENANT') ?: 'common';
        
        $scopesStr = getenv('MICROSOFT_SCOPES') ?: 'offline_access Mail.Read Mail.Send';
        $this->scopes = explode(' ', $scopesStr);
        
        $this->crypto = new EmailCrypto();
        $this->pdo = $pdo;
        
        if (empty($this->clientId) || empty($this->clientSecret)) {
            throw new Exception('Microsoft OAuth credentials not configured');
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
        $authUrl = self::AUTH_BASE . '/' . $this->tenant . '/oauth2/v2.0/authorize';
        
        // Generate state token for CSRF protection
        $state = base64_encode(json_encode([
            'customer_id' => $customerId,
            'timestamp' => time(),
            'nonce' => bin2hex(random_bytes(16))
        ]));
        
        // Store state in session for validation
        $_SESSION['microsoft_oauth_state'] = $state;
        
        $params = [
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUri,
            'scope' => implode(' ', $this->scopes),
            'response_mode' => 'query',
            'state' => $state,
        ];
        
        if ($email) {
            $params['login_hint'] = $email;
        }
        
        return $authUrl . '?' . http_build_query($params);
    }
    
    /**
     * Handle OAuth callback and exchange code for tokens
     * 
     * @param string $code Authorization code from Microsoft
     * @param string $state State parameter for CSRF validation
     * @return array Result with success status and data
     */
    public function handleCallback(string $code, string $state): array {
        try {
            // Validate state to prevent CSRF
            if (!isset($_SESSION['microsoft_oauth_state']) || $state !== $_SESSION['microsoft_oauth_state']) {
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
            
            // Get user email from Microsoft Graph
            $userInfo = $this->getUserInfo($tokens['access_token']);
            $email = $userInfo['mail'] ?? $userInfo['userPrincipalName'] ?? null;
            
            if (!$email) {
                throw new Exception('Failed to retrieve user email');
            }
            
            // Save configuration to database
            $configId = $this->saveConfiguration($customerId, $email, $tokens);
            
            // Clean up session
            unset($_SESSION['microsoft_oauth_state']);
            
            return [
                'success' => true,
                'config_id' => $configId,
                'email' => $email,
                'message' => 'Outlook account connected successfully'
            ];
            
        } catch (Exception $e) {
            error_log("Microsoft OAuth callback error: " . $e->getMessage());
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
        $tokenUrl = self::AUTH_BASE . '/' . $this->tenant . '/oauth2/v2.0/token';
        
        $postData = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code',
            'scope' => implode(' ', $this->scopes)
        ];
        
        $response = $this->makeRequest($tokenUrl, $postData);
        
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
        $tokenUrl = self::AUTH_BASE . '/' . $this->tenant . '/oauth2/v2.0/token';
        
        $postData = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
            'scope' => implode(' ', $this->scopes)
        ];
        
        $response = $this->makeRequest($tokenUrl, $postData);
        
        if (!isset($response['access_token'])) {
            throw new Exception('Failed to refresh token: ' . ($response['error_description'] ?? 'Unknown error'));
        }
        
        return [
            'access_token' => $response['access_token'],
            'refresh_token' => $response['refresh_token'] ?? $refreshToken, // Microsoft may return new refresh token
            'expires_in' => $response['expires_in'] ?? 3600,
        ];
    }
    
    /**
     * Get user info from Microsoft Graph
     * 
     * @param string $accessToken Access token
     * @return array User info
     */
    private function getUserInfo(string $accessToken): array {
        $url = self::GRAPH_BASE . '/me';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('Failed to get user info from Microsoft Graph');
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
            WHERE customer_id = ? AND email = ? AND provider = 'outlook'
        ");
        $stmt->execute([$customerId, $email]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing
            $stmt = $this->pdo->prepare("
                UPDATE email_configurations SET
                    oauth_provider = 'microsoft',
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
                ) VALUES (?, 'outlook', ?, 'microsoft', ?, ?, ?, ?, 1, 'oauth', 1, 1)
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
            WHERE id = ? AND oauth_provider = 'microsoft'
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
        
        // Update database (including new refresh token if provided)
        $encryptedAccessToken = $this->crypto->encrypt($newTokens['access_token']);
        $encryptedRefreshToken = $this->crypto->encrypt($newTokens['refresh_token']);
        $newExpiresAt = date('Y-m-d H:i:s', time() + $newTokens['expires_in']);
        
        $stmt = $this->pdo->prepare("
            UPDATE email_configurations 
            SET oauth_access_token = ?, 
                oauth_refresh_token = ?,
                oauth_token_expires_at = ?,
                last_error = NULL,
                error_count = 0,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$encryptedAccessToken, $encryptedRefreshToken, $newExpiresAt, $configId]);
        
        return $newTokens['access_token'];
    }
    
    /**
     * Revoke access and delete configuration
     * 
     * @param int $configId Configuration ID
     * @return bool Success
     */
    public function revokeAccess(int $configId): bool {
        // Microsoft doesn't have a simple revoke endpoint
        // User must revoke via account.microsoft.com
        // We just mark as inactive
        try {
            $stmt = $this->pdo->prepare("
                UPDATE email_configurations 
                SET is_active = 0, sync_enabled = 0
                WHERE id = ?
            ");
            $stmt->execute([$configId]);
            return true;
        } catch (Exception $e) {
            error_log("Failed to revoke Microsoft access: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Make HTTP request to Microsoft API
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
