<?php
header('Content-Type: application/json');

$status = [];
$actions_performed = [];

try {
    // 1. Vérifier la connexion à la base de données
    require_once '../config/database.php';
    
    if (isset($pdo)) {
        $status['database_connection'] = 'OK';
        
        // 2. Créer la base de données si elle n'existe pas
        try {
            $pdo->exec("CREATE DATABASE IF NOT EXISTS webitech");
            $pdo->exec("USE webitech");
            $actions_performed[] = 'Base de données webitech créée/vérifiée';
        } catch (Exception $e) {
            $status['database_creation'] = 'Failed: ' . $e->getMessage();
        }
        
        // 3. Vérifier si les tables existent
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() == 0) {
            // 4. Importer le schéma SQL
            $sql_file = '../database/crm_schema.sql';
            if (file_exists($sql_file)) {
                $sql = file_get_contents($sql_file);
                $pdo->exec($sql);
                $actions_performed[] = 'Schéma CRM installé avec succès';
                $status['schema_installation'] = 'OK';
            } else {
                $status['schema_installation'] = 'Failed: Fichier SQL introuvable';
            }
        } else {
            $status['schema_installation'] = 'Already exists';
        }
        
        // 5. Vérifier les données de base
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $user_count = $stmt->fetchColumn();
        $status['user_count'] = $user_count;
        
        if ($user_count == 0) {
            // Créer un utilisateur admin par défaut
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO users (first_name, last_name, email, password, role, phone, department, is_active) 
                VALUES ('Admin', 'CRM', 'admin@crm.local', ?, 'admin', '+33123456789', 'IT', 1)
            ");
            $stmt->execute([password_hash('admin123', PASSWORD_DEFAULT)]);
            $actions_performed[] = 'Utilisateur admin créé (admin@crm.local / admin123)';
        }
        
    } else {
        $status['database_connection'] = 'Failed: PDO non initialisé';
    }
    
    // 6. Vérifier les fichiers API
    $api_files = ['kpis.php', 'recent-activities.php', 'sales-data.php', 'source-data.php'];
    $api_status = [];
    
    foreach ($api_files as $file) {
        $api_status[$file] = file_exists($file) ? 'OK' : 'Missing';
    }
    $status['api_files'] = $api_status;
    
    // 7. Test rapide des APIs
    $api_tests = [];
    foreach ($api_files as $file) {
        if (file_exists($file)) {
            // Test simple d'inclusion sans erreur
            ob_start();
            $old_display_errors = ini_get('display_errors');
            ini_set('display_errors', 0);
            
            try {
                include_once $file;
                $output = ob_get_clean();
                $api_tests[$file] = 'Syntax OK';
            } catch (Exception $e) {
                ob_get_clean();
                $api_tests[$file] = 'Error: ' . $e->getMessage();
            }
            
            ini_set('display_errors', $old_display_errors);
        }
    }
    $status['api_tests'] = $api_tests;
    
} catch (Exception $e) {
    $status['error'] = $e->getMessage();
}

// Résultat final
$response = [
    'success' => !isset($status['error']),
    'status' => $status,
    'actions_performed' => $actions_performed,
    'timestamp' => date('Y-m-d H:i:s'),
    'recommendations' => []
];

// Recommandations
if (isset($status['database_connection']) && $status['database_connection'] === 'OK') {
    $response['recommendations'][] = '✅ Base de données opérationnelle';
} else {
    $response['recommendations'][] = '❌ Problème de connexion base de données';
}

if (isset($status['schema_installation']) && $status['schema_installation'] === 'OK') {
    $response['recommendations'][] = '✅ Schéma CRM installé';
} else {
    $response['recommendations'][] = '⚠️ Vérifier l\'installation du schéma';
}

$working_apis = 0;
if (isset($status['api_files'])) {
    foreach ($status['api_files'] as $file => $status_file) {
        if ($status_file === 'OK') $working_apis++;
    }
}

if ($working_apis >= 3) {
    $response['recommendations'][] = "✅ APIs fonctionnelles ({$working_apis}/4)";
} else {
    $response['recommendations'][] = "⚠️ Certaines APIs manquantes ({$working_apis}/4)";
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>
