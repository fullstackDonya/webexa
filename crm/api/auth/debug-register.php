<?php
/**
 * Debug Register API - Test the endpoint directly
 */

// Enable all errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log everything to a file
$debug_log = '/tmp/webexa_register_debug.log';
file_put_contents($debug_log, "=== DEBUG START " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);

try {
    file_put_contents($debug_log, "1. Session start...\n", FILE_APPEND);
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    file_put_contents($debug_log, "✓ Session started\n", FILE_APPEND);

    file_put_contents($debug_log, "2. Loading database config...\n", FILE_APPEND);
    $db_config_path = __DIR__ . '/../../config/database.php';
    file_put_contents($debug_log, "Database config path: $db_config_path\n", FILE_APPEND);
    
    if (!file_exists($db_config_path)) {
        file_put_contents($debug_log, "ERROR: database.php not found!\n", FILE_APPEND);
        die("Config file not found");
    }
    
    require $db_config_path;
    file_put_contents($debug_log, "✓ Database config loaded\n", FILE_APPEND);

    file_put_contents($debug_log, "3. Checking PDO connection...\n", FILE_APPEND);
    if (!isset($pdo)) {
        file_put_contents($debug_log, "ERROR: PDO not initialized!\n", FILE_APPEND);
        die("PDO not initialized");
    }
    file_put_contents($debug_log, "✓ PDO is set\n", FILE_APPEND);

    file_put_contents($debug_log, "4. Request method: " . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        file_put_contents($debug_log, "ERROR: Not a POST request\n", FILE_APPEND);
        echo json_encode(['error' => 'POST required']);
        exit;
    }

    file_put_contents($debug_log, "5. POST data received: " . json_encode($_POST) . "\n", FILE_APPEND);

    // Test database connection
    file_put_contents($debug_log, "6. Testing database query...\n", FILE_APPEND);
    $test = $pdo->query("SELECT COUNT(*) FROM users LIMIT 1");
    $count = $test->fetchColumn();
    file_put_contents($debug_log, "✓ DB Query successful. User count: $count\n", FILE_APPEND);

    file_put_contents($debug_log, "SUCCESS - All checks passed!\n", FILE_APPEND);
    
    echo json_encode([
        'debug' => true,
        'message' => 'All systems operational',
        'log_file' => $debug_log
    ]);

} catch (Exception $e) {
    file_put_contents($debug_log, "EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
    file_put_contents($debug_log, "Stack: " . $e->getTraceAsString() . "\n", FILE_APPEND);
    
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'log_file' => $debug_log
    ]);
}

file_put_contents($debug_log, "=== DEBUG END ===\n\n", FILE_APPEND);
