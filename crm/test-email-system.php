#!/usr/bin/env php
<?php
/**
 * Script de test pour le système email
 * Vérifie que toutes les dépendances sont installées et fonctionnelles
 */

echo "================================================\n";
echo "  Test du Système Email - CRM\n";
echo "================================================\n\n";

$errors = [];
$warnings = [];
$success = [];

// 1. Vérifier PHP version
echo "🔍 Vérification de PHP...\n";
if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
    $success[] = "✓ PHP " . PHP_VERSION;
} else {
    $errors[] = "✗ PHP version trop ancienne: " . PHP_VERSION . " (requis: 8.0+)";
}

// 2. Vérifier les extensions
echo "\n🔍 Vérification des extensions PHP...\n";
$requiredExtensions = ['openssl', 'pdo', 'imap', 'mbstring', 'curl'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        $success[] = "✓ Extension $ext";
    } else {
        $errors[] = "✗ Extension $ext manquante";
    }
}

// 3. Vérifier les fichiers
echo "\n🔍 Vérification des fichiers...\n";
$requiredFiles = [
    'includes/EmailCrypto.php',
    'includes/EmailSyncManager.php',
    'api/email-operations.php',
    'api/email-sync.php',
    'email-inbox.php',
    'email-compose.php',
    'email-settings.php',
    'cron-email-sync.php',
    'database/email_tables.sql'
];

foreach ($requiredFiles as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        $success[] = "✓ Fichier $file";
    } else {
        $errors[] = "✗ Fichier manquant: $file";
    }
}

// 4. Vérifier .env
echo "\n🔍 Vérification de .env...\n";
if (file_exists(__DIR__ . '/.env')) {
    $success[] = "✓ Fichier .env existe";
    
    $envContent = file_get_contents(__DIR__ . '/.env');
    if (strpos($envContent, 'MAIL_CRYPTO_KEY') !== false) {
        $success[] = "✓ MAIL_CRYPTO_KEY présente dans .env";
    } else {
        $errors[] = "✗ MAIL_CRYPTO_KEY manquante dans .env";
    }
} else {
    $errors[] = "✗ Fichier .env manquant";
}

// 5. Vérifier Composer et dépendances
echo "\n🔍 Vérification de Composer...\n";
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    $success[] = "✓ Vendor autoload présent";
    require_once __DIR__ . '/vendor/autoload.php';
    
    // Vérifier PHPMailer
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        $success[] = "✓ PHPMailer installé";
    } else {
        $errors[] = "✗ PHPMailer non trouvé";
    }
} else {
    $warnings[] = "⚠ Composer vendor manquant - Exécutez: composer install";
}

// 6. Vérifier la base de données
echo "\n🔍 Vérification de la base de données...\n";
try {
    require_once __DIR__ . '/config/database.php';
    $success[] = "✓ Connexion à la base de données";
    
    // Vérifier les tables
    $requiredTables = [
        'email_configurations',
        'emails',
        'emails_sent',
        'email_attachments',
        'email_sync_logs'
    ];
    
    foreach ($requiredTables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $success[] = "✓ Table $table";
        } else {
            $errors[] = "✗ Table manquante: $table (Exécutez: database/email_tables.sql)";
        }
    }
} catch (Exception $e) {
    $errors[] = "✗ Erreur base de données: " . $e->getMessage();
}

// 7. Vérifier les permissions
echo "\n🔍 Vérification des permissions...\n";
if (!is_dir(__DIR__ . '/logs')) {
    $warnings[] = "⚠ Répertoire logs manquant - Créez-le: mkdir logs";
} else {
    if (is_writable(__DIR__ . '/logs')) {
        $success[] = "✓ Répertoire logs accessible en écriture";
    } else {
        $warnings[] = "⚠ Répertoire logs non accessible en écriture";
    }
}

// 8. Test des classes principales
echo "\n🔍 Test des classes...\n";
try {
    require_once __DIR__ . '/includes/EmailCrypto.php';
    
    // Test si MAIL_CRYPTO_KEY est définie
    if (getenv('MAIL_CRYPTO_KEY') || (isset($_ENV['MAIL_CRYPTO_KEY']))) {
        $crypto = new EmailCrypto();
        $success[] = "✓ EmailCrypto initialisée";
        
        // Test de chiffrement/déchiffrement
        $testData = "test123";
        try {
            $encrypted = $crypto->encrypt($testData, 'test');
            $decrypted = $crypto->decrypt($encrypted, 'test');
            
            if ($decrypted === $testData) {
                $success[] = "✓ Chiffrement/déchiffrement fonctionnel";
            } else {
                $errors[] = "✗ Chiffrement/déchiffrement défaillant";
            }
        } catch (Exception $e) {
            $errors[] = "✗ Erreur de chiffrement: " . $e->getMessage();
        }
    } else {
        $warnings[] = "⚠ MAIL_CRYPTO_KEY non définie - Définissez-la dans .env";
    }
} catch (Exception $e) {
    $errors[] = "✗ Erreur lors du test EmailCrypto: " . $e->getMessage();
}

// Afficher les résultats
echo "\n\n================================================\n";
echo "  Résumé des Tests\n";
echo "================================================\n\n";

if (!empty($success)) {
    echo "✅ SUCCÈS (" . count($success) . "):\n";
    foreach ($success as $msg) {
        echo "   $msg\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "⚠️  AVERTISSEMENTS (" . count($warnings) . "):\n";
    foreach ($warnings as $msg) {
        echo "   $msg\n";
    }
    echo "\n";
}

if (!empty($errors)) {
    echo "❌ ERREURS (" . count($errors) . "):\n";
    foreach ($errors as $msg) {
        echo "   $msg\n";
    }
    echo "\n";
}

// Verdict final
echo "================================================\n";
if (empty($errors)) {
    if (empty($warnings)) {
        echo "🎉 PARFAIT! Le système email est prêt à l'emploi!\n";
        echo "\nProchaines étapes:\n";
        echo "1. Configurer un compte email: http://localhost:8888/crm/email-settings.php\n";
        echo "2. Consulter la boîte: http://localhost:8888/crm/email-inbox.php\n";
        echo "3. Configurer le cron pour sync auto (voir EMAIL_GUIDE_RAPIDE.md)\n";
        exit(0);
    } else {
        echo "✅ BON! Quelques avertissements à corriger.\n";
        echo "\nLe système devrait fonctionner, mais corrigez les avertissements.\n";
        exit(0);
    }
} else {
    echo "❌ ERREURS CRITIQUES! Corrigez les erreurs avant d'utiliser le système.\n";
    echo "\nConsultez EMAIL_GUIDE_RAPIDE.md pour l'aide.\n";
    exit(1);
}
