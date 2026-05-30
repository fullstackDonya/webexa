<?php
/**
 * Environment Loader
 * 
 * Loads environment variables from .env file
 * Call this at the beginning of your scripts
 * 
 * Usage:
 *   require_once 'includes/env.php';
 *   loadEnv(__DIR__ . '/../.env');
 */

function loadEnv(string $filePath): void {
    if (!file_exists($filePath)) {
        error_log("Warning: .env file not found at {$filePath}");
        return;
    }
    
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Skip invalid lines
        if (strpos($line, '=') === false) {
            continue;
        }
        
        // Parse KEY=VALUE
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        
        // Remove quotes if present
        if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
            (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
            $value = substr($value, 1, -1);
        }
        
        // Set in environment (don't override existing)
        if (!getenv($key)) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// Auto-load if .env exists in parent directory
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    loadEnv($envPath);
}
