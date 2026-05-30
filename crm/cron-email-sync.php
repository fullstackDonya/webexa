#!/usr/bin/env php
<?php
/**
 * Script Cron pour synchroniser automatiquement les emails
 * À exécuter toutes les 5-10 minutes
 * 
 * Ajoutez à crontab:
 * */5 * * * * /usr/bin/php /path/to/crm/cron-email-sync.php >> /path/to/logs/email-sync.log 2>&1
 */

// Définir le timezone
date_default_timezone_set('Europe/Paris');

// Chemins
$basePath = __DIR__;
require_once $basePath . '/config/database.php';
require_once $basePath . '/includes/EmailSyncManager.php';

// Log
function logMessage($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] [$level] $message\n";
}

logMessage("=== Démarrage de la synchronisation automatique des emails ===");

try {
    // Récupérer tous les comptes email actifs de tous les clients
    $stmt = $pdo->query("
        SELECT 
            ec.id,
            ec.customer_id,
            ec.email,
            ec.provider,
            c.name as customer_name,
            ec.last_sync,
            TIMESTAMPDIFF(MINUTE, ec.last_sync, NOW()) as minutes_since_sync
        FROM email_configurations ec
        JOIN customers c ON ec.customer_id = c.id
        WHERE ec.is_active = 1
        ORDER BY ec.last_sync ASC
    ");
    
    $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($configs)) {
        logMessage("Aucun compte email actif trouvé", "WARNING");
        exit(0);
    }
    
    logMessage("Trouvé " . count($configs) . " compte(s) email à synchroniser");
    
    $successCount = 0;
    $errorCount = 0;
    $totalEmailsSynced = 0;
    $totalNewEmails = 0;
    
    foreach ($configs as $config) {
        $minutes = $config['minutes_since_sync'] ?? 999;
        
        // Synchroniser si pas de sync depuis plus de 5 minutes (ou jamais)
        if ($minutes >= 5 || $config['last_sync'] === null) {
            logMessage("Synchronisation de {$config['email']} (client: {$config['customer_name']})");
            
            try {
                $syncManager = new EmailSyncManager($pdo, $config['id']);
                $stats = $syncManager->syncAllEmails(100);
                
                if ($stats['success']) {
                    $successCount++;
                    $totalEmailsSynced += $stats['emails_synced'] ?? 0;
                    $totalNewEmails += $stats['emails_new'] ?? 0;
                    
                    logMessage(
                        "  ✓ Succès: {$stats['emails_synced']} emails synchronisés, " .
                        "{$stats['emails_new']} nouveaux",
                        "SUCCESS"
                    );
                    
                    if (!empty($stats['errors'])) {
                        logMessage("  ⚠ " . count($stats['errors']) . " erreur(s) pendant la sync", "WARNING");
                    }
                } else {
                    $errorCount++;
                    logMessage("  ✗ Échec: " . ($stats['message'] ?? 'Erreur inconnue'), "ERROR");
                }
                
            } catch (Exception $e) {
                $errorCount++;
                logMessage("  ✗ Exception: " . $e->getMessage(), "ERROR");
                
                // Enregistrer l'erreur dans la base
                $errorStmt = $pdo->prepare("
                    UPDATE email_configurations 
                    SET last_error = ? 
                    WHERE id = ?
                ");
                $errorStmt->execute([
                    substr($e->getMessage(), 0, 500),
                    $config['id']
                ]);
            }
            
            // Pause courte entre les syncs pour éviter de surcharger
            sleep(2);
            
        } else {
            logMessage("Skip {$config['email']} (dernière sync il y a {$minutes} min)");
        }
    }
    
    logMessage("=== Synchronisation terminée ===");
    logMessage("Résumé:");
    logMessage("  - Comptes traités: " . ($successCount + $errorCount));
    logMessage("  - Succès: $successCount");
    logMessage("  - Erreurs: $errorCount");
    logMessage("  - Total emails synchronisés: $totalEmailsSynced");
    logMessage("  - Nouveaux emails: $totalNewEmails");
    
    // Code de sortie
    exit($errorCount > 0 ? 1 : 0);
    
} catch (Exception $e) {
    logMessage("ERREUR FATALE: " . $e->getMessage(), "CRITICAL");
    logMessage("Stack trace: " . $e->getTraceAsString(), "DEBUG");
    exit(2);
}
