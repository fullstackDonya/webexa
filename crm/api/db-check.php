<?php
header('Content-Type: application/json');

// Configuration
$host = 'localhost';
$dbname = 'webitech';
$username = 'root';
$password = 'root';

try {
    // Test de connexion à MySQL
    $pdo_test = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo_test->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Créer la base de données si elle n'existe pas
    $pdo_test->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    
    // Se connecter à la base de données
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Vérifier si les tables existent
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'users'");
    $stmt->execute();
    $tables_exist = $stmt->rowCount() > 0;
    
    $response = [
        'status' => 'success',
        'database_exists' => true,
        'tables_exist' => $tables_exist,
        'message' => $tables_exist ? 'Base de données et tables OK' : 'Base de données OK, mais tables manquantes'
    ];
    
    if (!$tables_exist) {
        $response['action_needed'] = 'Importer le schéma SQL depuis database/crm_schema.sql';
    }
    
} catch(PDOException $e) {
    $response = [
        'status' => 'error',
        'message' => 'Erreur de connexion: ' . $e->getMessage()
    ];
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>
