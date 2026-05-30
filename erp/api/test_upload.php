<?php
/**
 * Script de test pour diagnostiquer les problèmes d'upload
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'test' => 'Document scanner diagnostic',
    'php_version' => phpversion(),
    'checks' => [
        'session_active' => session_status() === PHP_SESSION_ACTIVE,
        'pdo_available' => extension_loaded('pdo'),
        'fileinfo_available' => extension_loaded('fileinfo'),
        'customer_id_in_session' => isset($_SESSION['customer_id']),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
    ],
    'paths' => [
        'upload_dir_exists' => file_exists(__DIR__ . '/../../uploads/scanned_documents/'),
        'upload_dir_writable' => is_writable(__DIR__ . '/../../uploads/scanned_documents/'),
        'log_dir_exists' => file_exists(__DIR__ . '/../../logs/'),
    ],
    'request_info' => [
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'undefined',
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'undefined',
        'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 0,
        'files_count' => count($_FILES),
    ]
]);
