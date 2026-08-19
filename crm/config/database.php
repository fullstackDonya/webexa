<?php
// Chargement des variables d'environnement
require_once __DIR__ . '/../../vendor/autoload.php';

// Chargement du .env depuis la racine du projet
// $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
// $dotenv->load();

// Configuration de la base de données depuis .env
if (!defined('DB_HOST')) define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
if (!defined('DB_PORT')) define('DB_PORT', $_ENV['DB_PORT'] ?? '8889');
if (!defined('DB_NAME')) define('DB_NAME', $_ENV['DB_NAME'] ?? 'webexa');
if (!defined('DB_USER')) define('DB_USER', $_ENV['DB_USER'] ?? 'root');
if (!defined('DB_PASS')) define('DB_PASS', $_ENV['DB_PASS'] ?? 'root');


// Configuration Power BI
if (!defined('POWERBI_CLIENT_ID'))     define('POWERBI_CLIENT_ID', $_ENV['POWERBI_CLIENT_ID'] ?? '');
if (!defined('POWERBI_CLIENT_SECRET')) define('POWERBI_CLIENT_SECRET', $_ENV['POWERBI_CLIENT_SECRET'] ?? '');
if (!defined('POWERBI_TENANT_ID'))     define('POWERBI_TENANT_ID', $_ENV['POWERBI_TENANT_ID'] ?? '');
if (!defined('POWERBI_WORKSPACE_ID'))  define('POWERBI_WORKSPACE_ID', $_ENV['POWERBI_WORKSPACE_ID'] ?? '');
if (!defined('POWERBI_REPORT_ID'))     define('POWERBI_REPORT_ID', $_ENV['POWERBI_REPORT_ID'] ?? '');

// URLs Power BI
if (!defined('POWERBI_API_URL'))  define('POWERBI_API_URL', 'https://api.powerbi.com/v1.0/myorg/');
if (!defined('POWERBI_AUTH_URL')) define('POWERBI_AUTH_URL', POWERBI_TENANT_ID ? 'https://login.microsoftonline.com/' . POWERBI_TENANT_ID . '/oauth2/v2.0/token' : '');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}
?>
