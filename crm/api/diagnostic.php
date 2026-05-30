<?php
header('Content-Type: application/json');

$diagnostics = [];

// 1. Vérifier la configuration de base
$diagnostics['config'] = [
    'php_version' => PHP_VERSION,
    'extensions' => [
        'pdo' => extension_loaded('pdo'),
        'pdo_mysql' => extension_loaded('pdo_mysql'),
        'json' => extension_loaded('json'),
        'curl' => extension_loaded('curl')
    ]
];

// 2. Vérifier la base de données
try {
    require_once '../config/database.php';
    
    if (isset($pdo)) {
        $diagnostics['database']['connection'] = 'OK';
        
        // Vérifier les tables principales
        $tables_to_check = ['users', 'companies', 'contacts', 'opportunities', 'activities', 'activity_logs'];
        $existing_tables = [];
        
        foreach ($tables_to_check as $table) {
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            $existing_tables[$table] = $stmt->rowCount() > 0;
        }
        
        $diagnostics['database']['tables'] = $existing_tables;
        
        // Vérifier les données de base
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users");
        $stmt->execute();
        $diagnostics['database']['user_count'] = $stmt->fetchColumn();
        
    } else {
        $diagnostics['database']['connection'] = 'FAILED - PDO not initialized';
    }
    
} catch (Exception $e) {
    $diagnostics['database']['connection'] = 'FAILED - ' . $e->getMessage();
}

// 3. Vérifier les fichiers API
$api_files = ['kpis.php', 'recent-activities.php', 'sales-data.php', 'source-data.php', 'dashboard-data.php'];
$diagnostics['api_files'] = [];

foreach ($api_files as $file) {
    $file_path = $file;
    $diagnostics['api_files'][$file] = [
        'exists' => file_exists($file_path),
        'readable' => file_exists($file_path) && is_readable($file_path),
        'size' => file_exists($file_path) ? filesize($file_path) : 0
    ];
}

// 4. Vérifier les fichiers includes
$include_files = ['../includes/auth.php', '../includes/sidebar.php', '../includes/header.php'];
$diagnostics['include_files'] = [];

foreach ($include_files as $file) {
    $filename = basename($file);
    $diagnostics['include_files'][$filename] = [
        'exists' => file_exists($file),
        'readable' => file_exists($file) && is_readable($file),
        'size' => file_exists($file) ? filesize($file) : 0
    ];
}

// 5. Vérifier les dossiers
$directories = ['../assets/css', '../assets/js', '../config', '../includes', '.'];
$diagnostics['directories'] = [];

foreach ($directories as $dir) {
    $dirname = basename($dir);
    $diagnostics['directories'][$dirname] = [
        'exists' => is_dir($dir),
        'writable' => is_dir($dir) && is_writable($dir)
    ];
}

// 6. Test des URLs API
$base_url = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
$diagnostics['api_urls'] = [];

foreach ($api_files as $file) {
    $url = $base_url . '/' . $file;
    $diagnostics['api_urls'][$file] = $url;
}

// 7. Calcul du score global
$score = 0;
$total_checks = 0;

// Score pour la base de données
if (isset($diagnostics['database']['connection']) && $diagnostics['database']['connection'] === 'OK') {
    $score += 20;
}
$total_checks += 20;

// Score pour les tables
if (isset($diagnostics['database']['tables'])) {
    foreach ($diagnostics['database']['tables'] as $exists) {
        if ($exists) $score += 5;
        $total_checks += 5;
    }
}

// Score pour les fichiers API
foreach ($diagnostics['api_files'] as $file_info) {
    if ($file_info['exists'] && $file_info['readable'] && $file_info['size'] > 0) {
        $score += 10;
    }
    $total_checks += 10;
}

$diagnostics['overall'] = [
    'score' => $score,
    'total' => $total_checks,
    'percentage' => $total_checks > 0 ? round(($score / $total_checks) * 100, 1) : 0,
    'status' => $score >= ($total_checks * 0.8) ? 'EXCELLENT' : 
               ($score >= ($total_checks * 0.6) ? 'GOOD' : 
               ($score >= ($total_checks * 0.4) ? 'WARNING' : 'CRITICAL'))
];

echo json_encode($diagnostics, JSON_PRETTY_PRINT);
?>
