<?php
/**
 * Script de diagnostic pour vérifier la configuration en production
 * Accessible via: https://webitech.fr/crm/check-env.php
 */

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Diagnostic Configuration Email</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .ok { color: #4ec9b0; }
        .error { color: #f48771; }
        .warning { color: #dcdcaa; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #444; border-radius: 5px; }
        h2 { color: #569cd6; }
        code { background: #2d2d2d; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
<h1>🔍 Diagnostic Configuration Email</h1>";

echo "<div class='section'>";
echo "<h2>📁 Fichier .env</h2>";
$envPath = __DIR__ . '/.env';
$envExists = file_exists($envPath);

if ($envExists) {
    echo "<p class='ok'>✅ Fichier .env trouvé: <code>$envPath</code></p>";
    echo "<p class='warning'>⚠️  Permissions: " . substr(sprintf('%o', fileperms($envPath)), -4) . "</p>";
} else {
    echo "<p class='error'>❌ Fichier .env MANQUANT: <code>$envPath</code></p>";
    echo "<p class='error'>🔧 ACTION REQUISE: Créez ce fichier sur le serveur de production</p>";
}
echo "</div>";

// Load env if exists
if ($envExists) {
    require_once __DIR__ . '/includes/env.php';
}

echo "<div class='section'>";
echo "<h2>🔐 Variables d'environnement</h2>";

$requiredVars = [
    'MAIL_CRYPTO_KEY' => 'Clé de chiffrement (32 bytes base64)',
    'GOOGLE_CLIENT_ID' => 'Google OAuth Client ID',
    'GOOGLE_CLIENT_SECRET' => 'Google OAuth Secret',
    'GOOGLE_REDIRECT_URI' => 'Google Redirect URI',
];

foreach ($requiredVars as $var => $description) {
    $value = getenv($var);
    if ($value) {
        if ($var === 'MAIL_CRYPTO_KEY') {
            // Vérifier la validité de la clé
            $decoded = base64_decode($value, true);
            if ($decoded !== false && strlen($decoded) === 32) {
                echo "<p class='ok'>✅ $var: <code>" . substr($value, 0, 20) . "...</code> (valide, 32 bytes)</p>";
            } else {
                echo "<p class='error'>❌ $var: INVALIDE (doit être 32 bytes en base64)</p>";
            }
        } else {
            $preview = substr($value, 0, 30);
            echo "<p class='ok'>✅ $var: <code>$preview...</code></p>";
        }
    } else {
        echo "<p class='error'>❌ $var: NON DÉFINIE - $description</p>";
    }
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>🗄️ Base de données</h2>";
try {
    require_once __DIR__ . '/config/database.php';
    echo "<p class='ok'>✅ Connexion database.php chargée</p>";
    
    // Tester la connexion
    $stmt = $pdo->query("SELECT 1");
    echo "<p class='ok'>✅ Connexion PDO fonctionnelle</p>";
    
    // Vérifier les tables email
    $tables = ['email_configurations', 'emails', 'email_attachments'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p class='ok'>✅ Table <code>$table</code> existe</p>";
        } else {
            echo "<p class='error'>❌ Table <code>$table</code> manquante</p>";
        }
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur DB: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>📦 Classes PHP</h2>";
$classes = [
    __DIR__ . '/includes/EmailCrypto.php' => 'EmailCrypto',
    __DIR__ . '/includes/EmailSyncManager.php' => 'EmailSyncManager',
    __DIR__ . '/includes/env.php' => 'env loader'
];

foreach ($classes as $file => $name) {
    if (file_exists($file)) {
        echo "<p class='ok'>✅ $name: <code>" . basename($file) . "</code></p>";
    } else {
        echo "<p class='error'>❌ $name: MANQUANT</p>";
    }
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>🔧 Extensions PHP</h2>";
$extensions = ['openssl', 'imap', 'pdo', 'pdo_mysql', 'curl'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p class='ok'>✅ $ext</p>";
    } else {
        echo "<p class='error'>❌ $ext: NON INSTALLÉE</p>";
    }
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>📝 Logs d'erreurs</h2>";
echo "<p>Vérifiez les logs PHP du serveur pour plus de détails:</p>";
echo "<ul>";
echo "<li><code>/var/log/php_errors.log</code></li>";
echo "<li><code>/var/log/apache2/error.log</code></li>";
echo "<li>Ou via le panneau d'hébergement</li>";
echo "</ul>";
echo "</div>";

// Test EmailCrypto si disponible
if ($envExists && file_exists(__DIR__ . '/includes/EmailCrypto.php')) {
    echo "<div class='section'>";
    echo "<h2>🔐 Test EmailCrypto</h2>";
    try {
        require_once __DIR__ . '/includes/EmailCrypto.php';
        $crypto = new EmailCrypto();
        
        $testValue = "test-password-123";
        $encrypted = $crypto->encrypt($testValue);
        $decrypted = $crypto->decrypt($encrypted);
        
        if ($decrypted === $testValue) {
            echo "<p class='ok'>✅ Chiffrement/déchiffrement fonctionne correctement</p>";
        } else {
            echo "<p class='error'>❌ Échec du test de chiffrement</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Erreur EmailCrypto: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    echo "</div>";
}

echo "</body></html>";
