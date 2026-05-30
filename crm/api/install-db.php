<?php
header('Content-Type: application/json');

$host = 'localhost';
$dbname = 'webitech';
$username = 'root';
$password = 'root';

try {
    // Connexion à MySQL
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Créer la base de données si elle n'existe pas
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    $pdo->exec("USE `$dbname`");
    
    // Lire le fichier SQL
    $sql_file = '../database/crm_schema.sql';
    if (!file_exists($sql_file)) {
        throw new Exception("Fichier SQL introuvable: $sql_file");
    }
    
    $sql = file_get_contents($sql_file);
    
    // Exécuter le script SQL
    $pdo->exec($sql);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Base de données CRM installée avec succès',
        'database' => $dbname
    ]);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Erreur lors de l\'installation: ' . $e->getMessage()
    ]);
}
?>
