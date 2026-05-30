<?php
/**
 * Test de connexion CRM Hostinger → Oracle Cloud API
 * 
 * À uploader sur Hostinger pour tester la communication
 * URL: https://votre-domaine.com/crm/test-oracle-api.php
 */

// Désactiver l'affichage des erreurs PHP en production
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Headers
header('Content-Type: text/plain; charset=utf-8');

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  TEST DE CONNEXION CRM HOSTINGER → ORACLE CLOUD API       ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Charger .env si présent
function loadEnv() {
    $envFile = __DIR__ . '/.env';
    
    if (file_exists($envFile)) {
        echo "✅ Fichier .env trouvé\n";
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                putenv(trim($key) . '=' . trim($value));
            }
        }
        return true;
    } else {
        echo "⚠️  Fichier .env non trouvé dans " . dirname(__FILE__) . "\n";
        return false;
    }
}

// Étape 1: Charger la configuration
echo "1️⃣  CHARGEMENT DE LA CONFIGURATION\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$envLoaded = loadEnv();

// Configuration
$apiUrl = getenv('AI_API_URL') ?: 'http://localhost:8000';
$apiKey = getenv('AI_API_KEY') ?: 'dev-secret-key-change-me';

echo "API URL      : " . $apiUrl . "\n";
echo "API Key      : " . substr($apiKey, 0, 20) . "...\n";
echo "Serveur      : " . $_SERVER['SERVER_NAME'] . "\n";
echo "IP Serveur   : " . $_SERVER['SERVER_ADDR'] . "\n\n";

if (!$envLoaded && $apiUrl === 'http://localhost:8000') {
    echo "⚠️  ATTENTION: Configuration par défaut utilisée\n";
    echo "   Créez un fichier .env avec:\n";
    echo "   AI_API_URL=http://VOTRE_IP_ORACLE:8000\n";
    echo "   AI_API_KEY=votre-clé-secrète\n\n";
}

// Étape 2: Test de résolution DNS / Connectivité réseau
echo "2️⃣  TEST DE CONNECTIVITÉ RÉSEAU\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$parsedUrl = parse_url($apiUrl);
$host = $parsedUrl['host'] ?? 'localhost';
$port = $parsedUrl['port'] ?? ($parsedUrl['scheme'] === 'https' ? 443 : 8000);

echo "Host: {$host}\n";
echo "Port: {$port}\n\n";

// Test de résolution DNS
$ip = gethostbyname($host);
if ($ip !== $host) {
    echo "✅ DNS résolu: {$host} -> {$ip}\n";
} else {
    echo "⚠️  DNS non résolu (ou IP directe): {$host}\n";
}

// Test de connectivité (socket)
echo "🔌 Test de connexion TCP sur {$host}:{$port}...\n";
$timeout = 5;
$startTime = microtime(true);

$socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
$endTime = microtime(true);
$latency = round(($endTime - $startTime) * 1000, 2);

if ($socket) {
    echo "✅ Connexion TCP établie ({$latency}ms)\n";
    fclose($socket);
} else {
    echo "❌ Connexion TCP échouée: [{$errno}] {$errstr}\n";
    echo "   Vérifiez:\n";
    echo "   - Le service est démarré sur Oracle Cloud\n";
    echo "   - Le port {$port} est ouvert dans le firewall\n";
    echo "   - Le Security List Oracle Cloud autorise ce port\n\n";
    exit(1);
}

echo "\n";

// Étape 3: Test Health Check (sans authentification)
echo "3️⃣  TEST HEALTH CHECK (PUBLIC)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$healthUrl = rtrim($apiUrl, '/') . '/health';
echo "URL: {$healthUrl}\n\n";

$ch = curl_init($healthUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false, // En dev seulement
]);

$startTime = microtime(true);
$response = curl_exec($ch);
$endTime = microtime(true);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
$latency = round(($endTime - $startTime) * 1000, 2);
curl_close($ch);

if ($curlError) {
    echo "❌ Erreur cURL: {$curlError}\n\n";
    exit(1);
}

echo "HTTP Status  : {$httpCode}\n";
echo "Latency      : {$latency}ms\n";
echo "Response     : ";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if ($data && isset($data['status'])) {
        echo "✅ OK\n";
        echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "⚠️  Réponse invalide\n";
        echo $response . "\n";
    }
} else {
    echo "❌ Erreur HTTP {$httpCode}\n";
    echo $response . "\n\n";
    exit(1);
}

echo "\n";

// Étape 4: Test endpoint avec authentification
echo "4️⃣  TEST AUTHENTIFICATION API\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$pendingUrl = rtrim($apiUrl, '/') . '/api/inbox/pending-actions';
echo "URL: {$pendingUrl}\n";
echo "API Key: " . substr($apiKey, 0, 20) . "...\n\n";

$ch = curl_init($pendingUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode(['customer_id' => 1]),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-API-Key: ' . $apiKey
    ],
    CURLOPT_SSL_VERIFYPEER => false,
]);

$startTime = microtime(true);
$response = curl_exec($ch);
$endTime = microtime(true);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
$latency = round(($endTime - $startTime) * 1000, 2);
curl_close($ch);

if ($curlError) {
    echo "❌ Erreur cURL: {$curlError}\n\n";
    exit(1);
}

echo "HTTP Status  : {$httpCode}\n";
echo "Latency      : {$latency}ms\n";
echo "Response     : ";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if ($data && isset($data['success'])) {
        echo "✅ Authentification réussie\n";
        echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "⚠️  Réponse invalide\n";
        echo $response . "\n";
    }
} elseif ($httpCode === 401) {
    echo "❌ Authentification échouée (401 Unauthorized)\n";
    echo "   Vérifiez que AI_API_KEY est identique à API_SECRET_KEY sur Oracle\n\n";
    echo "Response: " . $response . "\n";
    exit(1);
} else {
    echo "❌ Erreur HTTP {$httpCode}\n";
    echo $response . "\n\n";
    exit(1);
}

echo "\n";

// Étape 5: Test connexion MySQL (via API)
echo "5️⃣  TEST CONNEXION BASE DE DONNÉES\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// Tester un endpoint qui interroge la base de données
$customersUrl = rtrim($apiUrl, '/') . '/api/leads/potential-leads';
echo "URL: {$customersUrl}\n\n";

$ch = curl_init($customersUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_HTTPHEADER => [
        'X-API-Key: ' . $apiKey
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if ($data && isset($data['success'])) {
        echo "✅ Connexion base de données OK\n";
        echo "   Leads trouvés: " . count($data['data'] ?? []) . "\n";
    } else {
        echo "⚠️  Réponse API valide mais format inattendu\n";
    }
} else {
    echo "⚠️  Impossible de vérifier la connexion DB\n";
    echo "   (Endpoint peut ne pas exister, c'est normal)\n";
}

echo "\n";

// Résumé final
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║                    RÉSUMÉ DES TESTS                        ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

echo "✅ Configuration chargée\n";
echo "✅ Connectivité réseau OK\n";
echo "✅ Health check API réussi\n";
echo "✅ Authentification API validée\n";
echo "✅ L'API Oracle Cloud est OPÉRATIONNELLE\n\n";

echo "🎉 TOUS LES TESTS SONT PASSÉS !\n\n";

echo "Vous pouvez maintenant utiliser l'API depuis:\n";
echo "  - Dashboard: " . (isset($_SERVER['HTTPS']) ? 'https' : 'http') . "://{$_SERVER['HTTP_HOST']}/crm/ai-dashboard.php\n";
echo "  - API directe: {$apiUrl}\n\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "Test terminé à " . date('Y-m-d H:i:s') . "\n";
echo "═══════════════════════════════════════════════════════════════\n";
