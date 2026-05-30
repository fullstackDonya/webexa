<?php
/**
 * EmailCrypto - Secure encryption/decryption for email credentials and OAuth tokens
 * 
 * Uses AES-256-CBC with HMAC-SHA256 for authenticated encryption
 * Requires MAIL_CRYPTO_KEY in environment (256-bit base64 encoded)
 * 
 * @package CRM
 * @version 1.0.0
 */

class EmailCrypto {
    
    private const CIPHER_METHOD = 'aes-256-cbc';
    private const HMAC_ALGO = 'sha256';
    
    private string $encryptionKey;
    
    /**
     * Initialize crypto with master key from environment
     * Falls back to a machine-specific key if MAIL_CRYPTO_KEY is not set
     * 
     * @throws Exception on critical failure
     */
    public function __construct() {
        $key = getenv('MAIL_CRYPTO_KEY');
        
        if (!$key) {
            // Fallback: Generate a consistent key based on server info
            // WARNING: Not ideal for production, but prevents crashes
            error_log('WARNING: MAIL_CRYPTO_KEY not set, using fallback key. Set MAIL_CRYPTO_KEY in .env for security!');
            
            $serverInfo = php_uname() . __FILE__ . (defined('DB_HOST') ? DB_HOST : 'localhost');
            $fallbackKey = hash('sha256', $serverInfo, true);
            $this->encryptionKey = $fallbackKey;
            return;
        }
        
        // Decode base64 key
        $decodedKey = base64_decode($key, true);
        
        if ($decodedKey === false || strlen($decodedKey) !== 32) {
            error_log('WARNING: Invalid MAIL_CRYPTO_KEY format, using fallback');
            $serverInfo = php_uname() . __FILE__;
            $this->encryptionKey = hash('sha256', $serverInfo, true);
            return;
        }
        
        $this->encryptionKey = $decodedKey;
    }
    
    /**
     * Encrypt plaintext value
     * 
     * @param string $plaintext Value to encrypt
     * @return string Base64 encoded encrypted value with IV and HMAC
     * @throws Exception on encryption failure
     */
    public function encrypt(string $plaintext): string {
        if (empty($plaintext)) {
            throw new Exception('Cannot encrypt empty value');
        }
        
        // Generate random IV
        $ivLength = openssl_cipher_iv_length(self::CIPHER_METHOD);
        $iv = openssl_random_pseudo_bytes($ivLength);
        
        if ($iv === false) {
            throw new Exception('Failed to generate IV');
        }
        
        // Encrypt
        $ciphertext = openssl_encrypt(
            $plaintext, 
            self::CIPHER_METHOD, 
            $this->encryptionKey, 
            OPENSSL_RAW_DATA, 
            $iv
        );
        
        if ($ciphertext === false) {
            throw new Exception('Encryption failed: ' . openssl_error_string());
        }
        
        // Generate HMAC for authentication
        $hmac = hash_hmac(
            self::HMAC_ALGO, 
            $iv . $ciphertext, 
            $this->encryptionKey, 
            true
        );
        
        // Combine: IV + HMAC + Ciphertext
        $encrypted = $iv . $hmac . $ciphertext;
        
        // Return base64 encoded
        return base64_encode($encrypted);
    }
    
    /**
     * Decrypt encrypted value
     * 
     * @param string $encryptedValue Base64 encoded encrypted value
     * @return string Decrypted plaintext
     * @throws Exception on decryption failure or HMAC mismatch
     */
    public function decrypt(string $encryptedValue): string {
        if (empty($encryptedValue)) {
            throw new Exception('Cannot decrypt empty value');
        }
        
        // Decode base64
        $data = base64_decode($encryptedValue, true);
        
        if ($data === false) {
            throw new Exception('Invalid base64 encoding');
        }
        
        $ivLength = openssl_cipher_iv_length(self::CIPHER_METHOD);
        $hmacLength = 32; // SHA256 produces 32 bytes
        
        // Validate length
        if (strlen($data) < $ivLength + $hmacLength) {
            throw new Exception('Encrypted data too short');
        }
        
        // Extract components
        $iv = substr($data, 0, $ivLength);
        $hmac = substr($data, $ivLength, $hmacLength);
        $ciphertext = substr($data, $ivLength + $hmacLength);
        
        // Verify HMAC
        $calculatedHmac = hash_hmac(
            self::HMAC_ALGO, 
            $iv . $ciphertext, 
            $this->encryptionKey, 
            true
        );
        
        if (!hash_equals($hmac, $calculatedHmac)) {
            throw new Exception('HMAC verification failed - data may be tampered');
        }
        
        // Decrypt
        $plaintext = openssl_decrypt(
            $ciphertext, 
            self::CIPHER_METHOD, 
            $this->encryptionKey, 
            OPENSSL_RAW_DATA, 
            $iv
        );
        
        if ($plaintext === false) {
            throw new Exception('Decryption failed: ' . openssl_error_string());
        }
        
        return $plaintext;
    }
    
    /**
     * Generate a new random encryption key (for setup)
     * 
     * @return string Base64 encoded 256-bit key
     */
    public static function generateKey(): string {
        $key = openssl_random_pseudo_bytes(32); // 256 bits
        return base64_encode($key);
    }
    
    /**
     * Safely encrypt OAuth tokens for storage
     * 
     * @param array $tokens Array with 'access_token' and 'refresh_token'
     * @return array Encrypted tokens
     */
    public function encryptOAuthTokens(array $tokens): array {
        $encrypted = [];
        
        if (!empty($tokens['access_token'])) {
            $encrypted['access_token'] = $this->encrypt($tokens['access_token']);
        }
        
        if (!empty($tokens['refresh_token'])) {
            $encrypted['refresh_token'] = $this->encrypt($tokens['refresh_token']);
        }
        
        return $encrypted;
    }
    
    /**
     * Safely decrypt OAuth tokens from storage
     * 
     * @param array $encryptedTokens Array with encrypted tokens
     * @return array Decrypted tokens
     */
    public function decryptOAuthTokens(array $encryptedTokens): array {
        $decrypted = [];
        
        if (!empty($encryptedTokens['access_token'])) {
            try {
                $decrypted['access_token'] = $this->decrypt($encryptedTokens['access_token']);
            } catch (Exception $e) {
                error_log("Failed to decrypt access_token: " . $e->getMessage());
                $decrypted['access_token'] = null;
            }
        }
        
        if (!empty($encryptedTokens['refresh_token'])) {
            try {
                $decrypted['refresh_token'] = $this->decrypt($encryptedTokens['refresh_token']);
            } catch (Exception $e) {
                error_log("Failed to decrypt refresh_token: " . $e->getMessage());
                $decrypted['refresh_token'] = null;
            }
        }
        
        return $decrypted;
    }
}
