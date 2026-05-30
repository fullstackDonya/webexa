<?php
/**
 * Script d'exécution de la migration pour le système d'onboarding
 * Usage: php run_onboarding_migration.php
 */

require_once __DIR__ . '/config/database.php';

echo "🚀 Début de la migration du système d'onboarding...\n\n";

try {
    // Lire le fichier SQL
    $sqlFile = __DIR__ . '/migrations/004_onboarding_system.sql';
    
    if (!file_exists($sqlFile)) {
        die("❌ Erreur: Le fichier de migration n'existe pas: $sqlFile\n");
    }
    
    $sql = file_get_contents($sqlFile);
    
    if (!$sql) {
        die("❌ Erreur: Impossible de lire le fichier de migration\n");
    }
    
    echo "📄 Fichier de migration chargé: 004_onboarding_system.sql\n\n";
    
    // Séparer les requêtes SQL (divisées par ;)
    $queries = array_filter(
        array_map('trim', explode(';', $sql)),
        function($query) {
            // Ignorer les commentaires et les lignes vides
            return !empty($query) && !preg_match('/^--/', $query);
        }
    );
    
    $totalQueries = count($queries);
    $successCount = 0;
    $errorCount = 0;
    
    echo "📊 Nombre de requêtes à exécuter: $totalQueries\n\n";
    
    foreach ($queries as $index => $query) {
        try {
            $pdo->exec($query);
            $successCount++;
            
            // Afficher le type de requête exécutée
            if (preg_match('/^(ALTER TABLE|CREATE TABLE|CREATE INDEX|UPDATE|INSERT)/i', trim($query), $matches)) {
                $action = strtoupper($matches[1]);
                echo "✅ [$successCount/$totalQueries] $action exécuté avec succès\n";
            }
        } catch (PDOException $e) {
            $errorCount++;
            $queryPreview = substr(trim($query), 0, 100);
            echo "⚠️  [$errorCount erreurs] Requête ignorée (peut-être déjà appliquée): " . $queryPreview . "...\n";
            // On continue quand même car certaines colonnes peuvent déjà exister
        }
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "✨ Migration terminée!\n";
    echo "   • Requêtes réussies: $successCount\n";
    echo "   • Requêtes ignorées: $errorCount\n";
    echo str_repeat("=", 60) . "\n\n";
    
    // Vérifier que tout est bien en place
    echo "🔍 Vérification de l'installation...\n\n";
    
    $checks = [
        "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'onboarding_completed'" => "Colonne users.onboarding_completed",
        "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_settings'" => "Table user_settings",
        "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'companies' AND COLUMN_NAME = 'industry'" => "Colonne companies.industry",
    ];
    
    $allChecksPass = true;
    
    foreach ($checks as $query => $description) {
        $result = $pdo->query($query)->fetchColumn();
        if ($result > 0) {
            echo "   ✅ $description\n";
        } else {
            echo "   ❌ $description - MANQUANT!\n";
            $allChecksPass = false;
        }
    }
    
    echo "\n";
    
    if ($allChecksPass) {
        echo "🎉 Toutes les vérifications sont passées avec succès!\n";
        echo "🚀 Le système d'onboarding est prêt à être utilisé.\n\n";
        echo "📝 Prochaines étapes:\n";
        echo "   1. Les nouveaux utilisateurs seront redirigés vers setup-wizard.php\n";
        echo "   2. Les utilisateurs peuvent modifier leurs paramètres dans settings.php\n";
        echo "   3. Les données customer sont automatiquement synchronisées avec companies\n\n";
    } else {
        echo "⚠️  Certaines vérifications ont échoué. Veuillez vérifier les erreurs ci-dessus.\n\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ ERREUR CRITIQUE: " . $e->getMessage() . "\n";
    echo "   Fichier: " . $e->getFile() . "\n";
    echo "   Ligne: " . $e->getLine() . "\n\n";
    exit(1);
}
