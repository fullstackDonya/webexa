#!/usr/bin/env php
<?php
/**
 * Email Sync Worker
 * 
 * Background process that consumes jobs from Redis queue
 * and synchronizes emails from configured accounts
 * 
 * Usage: php email-sync-worker.php
 * 
 * @package CRM
 * @version 1.0.0
 */

// Load environment
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($key, $value) = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($value));
    }
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/EmailSyncQueue.php';
require_once __DIR__ . '/../includes/EmailCrypto.php';
require_once __DIR__ . '/../includes/GoogleOAuth.php';
require_once __DIR__ . '/../includes/MicrosoftOAuth.php';

// Configure error logging
ini_set('display_errors', '0');
ini_set('log_errors', '1');
$logFile = getenv('LOG_FILE') ?: __DIR__ . '/../logs/email.log';
ini_set('error_log', $logFile);

echo "[" . date('Y-m-d H:i:s') . "] Email Sync Worker started\n";

// Initialize queue
try {
    $queue = new EmailSyncQueue();
    $crypto = new EmailCrypto();
    $googleOAuth = new GoogleOAuth($pdo);
    $msOAuth = new MicrosoftOAuth($pdo);
} catch (Exception $e) {
    error_log("Worker initialization failed: " . $e->getMessage());
    die("Failed to initialize worker: " . $e->getMessage() . "\n");
}

// Worker loop
$running = true;
$sleepSeconds = 5; // Poll every 5 seconds

// Handle shutdown signals
pcntl_signal(SIGTERM, function() use (&$running) {
    echo "[" . date('Y-m-d H:i:s') . "] Received SIGTERM, shutting down...\n";
    $running = false;
});

pcntl_signal(SIGINT, function() use (&$running) {
    echo "[" . date('Y-m-d H:i:s') . "] Received SIGINT, shutting down...\n";
    $running = false;
});

while ($running) {
    pcntl_signal_dispatch();
    
    try {
        // Get next job
        $job = $queue->dequeue();
        
        if (!$job) {
            // No jobs, sleep
            sleep($sleepSeconds);
            continue;
        }
        
        $configId = $job['config_id'];
        echo "[" . date('Y-m-d H:i:s') . "] Processing config_id: {$configId}\n";
        
        // Fetch configuration
        $stmt = $pdo->prepare("
            SELECT * FROM email_configurations 
            WHERE id = ? AND is_active = 1 AND sync_enabled = 1
        ");
        $stmt->execute([$configId]);
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$config) {
            echo "[" . date('Y-m-d H:i:s') . "] Config {$configId} not found or disabled\n";
            $queue->complete($configId);
            continue;
        }
        
        // Sync based on provider
        try {
            switch ($config['oauth_provider']) {
                case 'google':
                    syncGmail($config, $googleOAuth, $pdo);
                    break;
                
                case 'microsoft':
                    syncOutlook($config, $msOAuth, $pdo);
                    break;
                
                case 'none':
                    syncIMAP($config, $crypto, $pdo);
                    break;
                
                default:
                    throw new Exception("Unknown provider: " . $config['oauth_provider']);
            }
            
            // Mark as complete
            $queue->complete($configId);
            
            // Update last_sync timestamp
            $stmt = $pdo->prepare("
                UPDATE email_configurations 
                SET last_sync = NOW(), 
                    last_error = NULL,
                    error_count = 0
                WHERE id = ?
            ");
            $stmt->execute([$configId]);
            
            echo "[" . date('Y-m-d H:i:s') . "] Sync completed for config_id: {$configId}\n";
            
        } catch (Exception $e) {
            error_log("Sync failed for config {$configId}: " . $e->getMessage());
            echo "[" . date('Y-m-d H:i:s') . "] Sync failed: " . $e->getMessage() . "\n";
            
            // Update error in database
            $stmt = $pdo->prepare("
                UPDATE email_configurations 
                SET last_error = ?,
                    last_error_at = NOW(),
                    error_count = error_count + 1
                WHERE id = ?
            ");
            $stmt->execute([substr($e->getMessage(), 0, 500), $configId]);
            
            // Mark job as failed (will retry with backoff)
            $queue->fail($configId, $e->getMessage());
        }
        
    } catch (Exception $e) {
        error_log("Worker error: " . $e->getMessage());
        echo "[" . date('Y-m-d H:i:s') . "] Worker error: " . $e->getMessage() . "\n";
        sleep($sleepSeconds);
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Email Sync Worker stopped\n";

/**
 * Sync emails from Gmail using Gmail API
 */
function syncGmail(array $config, GoogleOAuth $googleOAuth, PDO $pdo): void {
    $configId = $config['id'];
    $customerId = $config['customer_id'];
    
    // Get valid access token (auto-refreshes if needed)
    $accessToken = $googleOAuth->getValidAccessToken($configId);
    
    // Fetch emails using Gmail API
    $limit = (int)(getenv('EMAIL_SYNC_LIMIT') ?: 50);
    $url = "https://gmail.googleapis.com/gmail/v1/users/me/messages?maxResults={$limit}&q=is:unread";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception("Gmail API error: HTTP {$httpCode}");
    }
    
    $data = json_decode($response, true);
    $messages = $data['messages'] ?? [];
    
    echo "  Found " . count($messages) . " unread messages\n";
    
    // Fetch and store each message
    foreach ($messages as $message) {
        $messageId = $message['id'];
        
        // Get full message
        $url = "https://gmail.googleapis.com/gmail/v1/users/me/messages/{$messageId}";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken
        ]);
        
        $messageResponse = curl_exec($ch);
        curl_close($ch);
        
        $messageData = json_decode($messageResponse, true);
        
        // Extract headers
        $headers = $messageData['payload']['headers'] ?? [];
        $from = extractHeader($headers, 'From');
        $to = extractHeader($headers, 'To');
        $subject = extractHeader($headers, 'Subject');
        $date = extractHeader($headers, 'Date');
        
        // Extract body (simplified - handle multipart in production)
        $body = $messageData['snippet'] ?? '';
        
        // Store in database
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO emails (
                customer_id, config_id, email_id, from_address, to_address,
                subject, body, email_date, is_read, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())
        ");
        
        $stmt->execute([
            $customerId,
            $configId,
            $messageId,
            $from,
            $to,
            $subject,
            $body,
            $date ? date('Y-m-d H:i:s', strtotime($date)) : null
        ]);
    }
}

/**
 * Sync emails from Outlook using Microsoft Graph API
 */
function syncOutlook(array $config, MicrosoftOAuth $msOAuth, PDO $pdo): void {
    $configId = $config['id'];
    $customerId = $config['customer_id'];
    
    // Get valid access token
    $accessToken = $msOAuth->getValidAccessToken($configId);
    
    // Fetch emails using Microsoft Graph
    $limit = (int)(getenv('EMAIL_SYNC_LIMIT') ?: 50);
    $url = "https://graph.microsoft.com/v1.0/me/messages?\$filter=isRead eq false&\$top={$limit}";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception("Microsoft Graph error: HTTP {$httpCode}");
    }
    
    $data = json_decode($response, true);
    $messages = $data['value'] ?? [];
    
    echo "  Found " . count($messages) . " unread messages\n";
    
    foreach ($messages as $message) {
        $messageId = $message['id'];
        $from = $message['from']['emailAddress']['address'] ?? '';
        $to = $message['toRecipients'][0]['emailAddress']['address'] ?? '';
        $subject = $message['subject'] ?? '';
        $body = $message['bodyPreview'] ?? '';
        $date = $message['receivedDateTime'] ?? null;
        
        // Store in database
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO emails (
                customer_id, config_id, email_id, from_address, to_address,
                subject, body, email_date, is_read, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())
        ");
        
        $stmt->execute([
            $customerId,
            $configId,
            $messageId,
            $from,
            $to,
            $subject,
            $body,
            $date ? date('Y-m-d H:i:s', strtotime($date)) : null
        ]);
    }
}

/**
 * Sync emails from IMAP (Hostinger, custom providers)
 */
function syncIMAP(array $config, EmailCrypto $crypto, PDO $pdo): void {
    $configId = $config['id'];
    $customerId = $config['customer_id'];
    
    // Decrypt password
    $password = $crypto->decrypt($config['password']);
    
    // Connect to IMAP
    $mailbox = '{' . $config['imap_server'] . ':' . $config['imap_port'] . '/imap/ssl}INBOX';
    $connection = imap_open($mailbox, $config['email'], $password);
    
    if (!$connection) {
        throw new Exception('IMAP connection failed: ' . imap_last_error());
    }
    
    // Fetch unread emails
    $limit = (int)(getenv('EMAIL_SYNC_LIMIT') ?: 50);
    $emails = imap_search($connection, 'UNSEEN', SE_UID);
    
    if (!$emails) {
        echo "  No unread messages\n";
        imap_close($connection);
        return;
    }
    
    $emails = array_slice($emails, 0, $limit);
    echo "  Found " . count($emails) . " unread messages\n";
    
    foreach ($emails as $uid) {
        $header = imap_headerinfo($connection, imap_msgno($connection, $uid));
        $body = imap_fetchbody($connection, $uid, 1, FT_UID);
        
        $from = $header->from[0]->mailbox . '@' . $header->from[0]->host;
        $to = $header->to[0]->mailbox . '@' . $header->to[0]->host;
        $subject = $header->subject ?? '';
        $date = date('Y-m-d H:i:s', $header->udate);
        
        // Store in database
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO emails (
                customer_id, config_id, email_id, from_address, to_address,
                subject, body, email_date, is_read, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())
        ");
        
        $stmt->execute([
            $customerId,
            $configId,
            (string)$uid,
            $from,
            $to,
            $subject,
            $body,
            $date
        ]);
    }
    
    imap_close($connection);
}

/**
 * Extract header value from Gmail API headers array
 */
function extractHeader(array $headers, string $name): ?string {
    foreach ($headers as $header) {
        if (strcasecmp($header['name'], $name) === 0) {
            return $header['value'];
        }
    }
    return null;
}
