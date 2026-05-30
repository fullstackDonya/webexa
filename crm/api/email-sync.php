<?php
/**
 * API pour synchroniser les emails
 * Endpoint dédié à la synchronisation
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
header('Content-Type: application/json');

// Charger les variables d'environnement
require_once __DIR__ . '/../includes/env.php';
loadEnv(__DIR__ . '/../.env');

try {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/EmailSyncManager.php';
    
    // Vérifier l'authentification
    if (!isset($_SESSION['customer_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Non authentifié']);
        exit;
    }
    
    $customer_id = $_SESSION['customer_id'];
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? 'sync_all';
    
    if ($action === 'sync_all') {
        // Récupérer tous les comptes actifs
        $stmt = $pdo->prepare("
            SELECT id, email FROM email_configurations 
            WHERE customer_id = ? AND is_active = 1
        ");
        $stmt->execute([$customer_id]);
        $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($configs)) {
            echo json_encode([
                'success' => false,
                'message' => 'Aucun compte email configuré'
            ]);
            exit;
        }
        
        $results = [];
        $totalNew = 0;
        $totalSynced = 0;
        
        foreach ($configs as $config) {
            try {
                error_log("Starting sync for {$config['email']} (config_id: {$config['id']})");
                $syncManager = new EmailSyncManager($pdo, $config['id']);
                $stats = $syncManager->syncAllEmails(100);
                
                // Enregistrer les résultats détaillés
                error_log("Sync result for {$config['email']}: " . json_encode($stats));
                
                $results[] = [
                    'config_id' => $config['id'],
                    'email' => $config['email'],
                    'success' => $stats['success'] ?? false,
                    'stats' => $stats,
                    'message' => $stats['message'] ?? 'OK'
                ];
                
                if (isset($stats['emails_new'])) {
                    $totalNew += $stats['emails_new'];
                }
                if (isset($stats['emails_synced'])) {
                    $totalSynced += $stats['emails_synced'];
                }
            } catch (Exception $e) {
                $errorMsg = $e->getMessage();
                error_log("Sync error for {$config['email']}: " . $errorMsg);
                error_log("Stack trace: " . $e->getTraceAsString());
                
                $results[] = [
                    'config_id' => $config['id'],
                    'email' => $config['email'],
                    'success' => false,
                    'error' => $errorMsg,
                    'message' => 'Erreur: ' . $errorMsg
                ];
            }
        }
        
        // Construire un message détaillé
        $detailedMessage = "Synchronisation terminée: $totalNew nouveaux emails, $totalSynced total";
        $hasErrors = false;
        
        foreach ($results as $result) {
            if (!$result['success']) {
                $hasErrors = true;
                break;
            }
        }
        
        if ($hasErrors) {
            $detailedMessage .= " (avec des erreurs - voir détails)";
        }
        
        echo json_encode([
            'success' => !$hasErrors,
            'message' => $detailedMessage,
            'stats' => [
                'configs_processed' => count($configs),
                'emails_new' => $totalNew,
                'emails_synced' => $totalSynced
            ],
            'results' => $results,
            'debug_info' => [
                'has_errors' => $hasErrors,
                'configs_count' => count($configs)
            ]
        ]);
        exit;
    }
    
    if ($action === 'sync_one') {
        $configId = $data['config_id'] ?? null;
        
        if (!$configId) {
            echo json_encode(['success' => false, 'message' => 'config_id requis']);
            exit;
        }
        
        // Vérifier que la config appartient au client
        $stmt = $pdo->prepare("
            SELECT email FROM email_configurations 
            WHERE id = ? AND customer_id = ?
        ");
        $stmt->execute([$configId, $customer_id]);
        $config = $stmt->fetch();
        
        if (!$config) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Accès refusé']);
            exit;
        }
        
        try {
            $syncManager = new EmailSyncManager($pdo, $configId);
            $stats = $syncManager->syncAllEmails(100);
            
            echo json_encode([
                'success' => true,
                'message' => "Synchronisation terminée pour {$config['email']}",
                'stats' => $stats
            ]);
        } catch (Exception $e) {
            error_log("Sync error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ]);
        }
        exit;
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Action non reconnue'
    ]);
    
} catch (Exception $e) {
    error_log("Email Sync API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}
