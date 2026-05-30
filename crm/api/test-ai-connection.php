<?php
/**
 * Test de connexion à l'API IA
 * Accédez à ce fichier directement pour vérifier la connexion
 */

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/AIAgentsClient.php';

$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'tests' => []
];

// Test 1: Initialisation du client
try {
    $client = new AIAgentsClient();
    $results['tests']['client_init'] = [
        'status' => 'success',
        'message' => 'Client initialisé avec succès'
    ];
} catch (Exception $e) {
    $results['tests']['client_init'] = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
    echo json_encode($results, JSON_PRETTY_PRINT);
    exit;
}

// Test 2: Health check
try {
    $health = $client->healthCheck();
    $results['tests']['health_check'] = [
        'status' => 'success',
        'message' => 'API FastAPI est accessible',
        'data' => $health
    ];
} catch (Exception $e) {
    $results['tests']['health_check'] = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
}

// Test 3: Vérifier CURL
$results['tests']['curl_available'] = [
    'status' => function_exists('curl_init') ? 'success' : 'error',
    'message' => function_exists('curl_init') ? 'CURL disponible' : 'CURL non installé'
];

// Test 4: Vérifier la connexion au port 8000
try {
    $connection = @fsockopen('localhost', 8000, $errno, $errstr, 1);
    if ($connection) {
        fclose($connection);
        $results['tests']['port_8000'] = [
            'status' => 'success',
            'message' => 'Port 8000 accessible'
        ];
    } else {
        $results['tests']['port_8000'] = [
            'status' => 'error',
            'message' => "Port 8000 non accessible: $errstr (code: $errno)"
        ];
    }
} catch (Exception $e) {
    $results['tests']['port_8000'] = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
}

// Résumé
$successCount = 0;
$totalTests = count($results['tests']);
foreach ($results['tests'] as $test) {
    if ($test['status'] === 'success') {
        $successCount++;
    }
}

$results['summary'] = [
    'total_tests' => $totalTests,
    'passed' => $successCount,
    'failed' => $totalTests - $successCount,
    'overall_status' => $successCount === $totalTests ? 'success' : 'partial'
];

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
