<?php
/**
 * API pour les opérations sur les emails
 * Synchronisation, lecture, marquage, suppression
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
header('Content-Type: application/json');

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
    
    // Récupérer l'action depuis GET, POST ou le JSON body
    $action = $_GET['action'] ?? $_POST['action'] ?? null;
    
    // Si pas d'action dans GET/POST, chercher dans le JSON body
    if (!$action && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $jsonData = json_decode(file_get_contents('php://input'), true);
        $action = $jsonData['action'] ?? null;
    }
    
    // === SYNCHRONISER LES EMAILS ===
    if ($action === 'sync' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($jsonData)) {
            $jsonData = json_decode(file_get_contents('php://input'), true);
        }
        $configId = $jsonData['config_id'] ?? null;
        
        if (!$configId) {
            echo json_encode(['success' => false, 'message' => 'config_id requis']);
            exit;
        }
        
        // Vérifier que la config appartient au client
        $stmt = $pdo->prepare("SELECT id FROM email_configurations WHERE id = ? AND customer_id = ?");
        $stmt->execute([$configId, $customer_id]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Accès refusé']);
            exit;
        }
        
        try {
            $syncManager = new EmailSyncManager($pdo, $configId);
            $stats = $syncManager->syncAllEmails(200);
            
            echo json_encode([
                'success' => true,
                'message' => 'Synchronisation terminée',
                'stats' => $stats
            ]);
        } catch (Exception $e) {
            error_log("Sync error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Erreur de synchronisation: ' . $e->getMessage()
            ]);
        }
        exit;
    }
    
    // === SYNCHRONISER TOUS LES COMPTES ===
    if ($action === 'sync_all' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $stmt = $pdo->prepare("
            SELECT id FROM email_configurations 
            WHERE customer_id = ? AND is_active = 1
        ");
        $stmt->execute([$customer_id]);
        $configs = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $totalStats = [
            'success' => true,
            'stats' => [
                'emails_synced' => 0,
                'emails_new' => 0,
                'emails_updated' => 0,
                'configs_processed' => 0,
                'errors' => []
            ]
        ];
        
        foreach ($configs as $configId) {
            try {
                $syncManager = new EmailSyncManager($pdo, $configId);
                $stats = $syncManager->syncAllEmails(200);
                
                $totalStats['stats']['emails_synced'] += $stats['emails_synced'] ?? 0;
                $totalStats['stats']['emails_new'] += $stats['emails_new'] ?? 0;
                $totalStats['stats']['emails_updated'] += $stats['emails_updated'] ?? 0;
                $totalStats['stats']['configs_processed']++;
                
                if (isset($stats['errors'])) {
                    $totalStats['stats']['errors'] = array_merge(
                        $totalStats['stats']['errors'],
                        $stats['errors']
                    );
                }
            } catch (Exception $e) {
                $totalStats['stats']['errors'][] = [
                    'config_id' => $configId,
                    'error' => $e->getMessage()
                ];
                error_log("Sync error for config $configId: " . $e->getMessage());
            }
        }
        
        echo json_encode($totalStats);
        exit;
    }
    
    // === RÉCUPÉRER UN EMAIL ===
    if ($action === 'get' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $emailId = $_GET['id'] ?? null;
        
        if (!$emailId) {
            echo json_encode(['success' => false, 'message' => 'ID requis']);
            exit;
        }
        
        $stmt = $pdo->prepare("
            SELECT * FROM emails 
            WHERE id = ? AND customer_id = ?
        ");
        $stmt->execute([$emailId, $customer_id]);
        $email = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$email) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Email non trouvé']);
            exit;
        }
        
        // Nettoyer et décoder le contenu du body
        if (!empty($email['body'])) {
            $body = $email['body'];
            
            // Détecter et décoder si c'est du base64
            if (preg_match('/^[A-Za-z0-9+\/=\s]+$/', $body) && strlen($body) > 100) {
                $decoded = base64_decode($body, true);
                if ($decoded !== false && mb_detect_encoding($decoded, ['UTF-8', 'ISO-8859-1', 'ASCII'], true)) {
                    $body = $decoded;
                }
            }
            
            // Décoder quoted-printable si détecté
            if (strpos($body, '=?') !== false || preg_match('/=[0-9A-F]{2}/', $body)) {
                $body = quoted_printable_decode($body);
                $body = iconv_mime_decode($body, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
            }
            
            // Convertir en UTF-8 si nécessaire
            $encoding = mb_detect_encoding($body, ['UTF-8', 'ISO-8859-1', 'ISO-8859-15', 'Windows-1252'], true);
            if ($encoding && $encoding !== 'UTF-8') {
                $body = mb_convert_encoding($body, 'UTF-8', $encoding);
            }
            
            // Si le contenu est du texte brut, le convertir en HTML
            if (strpos($body, '<html') === false && strpos($body, '<div') === false) {
                $body = nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'));
            }
            
            $email['body'] = $body;
        }
        
        echo json_encode([
            'success' => true,
            'email' => $email
        ]);
        exit;
    }
    
    // === MARQUER COMME LU ===
    if ($action === 'mark_read' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($jsonData)) {
            $jsonData = json_decode(file_get_contents('php://input'), true);
        }
        $emailId = $jsonData['id'] ?? null;
        
        if (!$emailId) {
            echo json_encode(['success' => false, 'message' => 'ID requis']);
            exit;
        }
        
        $stmt = $pdo->prepare("
            UPDATE emails SET is_read = 1, updated_at = NOW()
            WHERE id = ? AND customer_id = ?
        ");
        $stmt->execute([$emailId, $customer_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Marqué comme lu'
        ]);
        exit;
    }
    
    // === MARQUER COMME NON LU ===
    if ($action === 'mark_unread' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($jsonData)) {
            $jsonData = json_decode(file_get_contents('php://input'), true);
        }
        $emailId = $jsonData['id'] ?? null;
        
        if (!$emailId) {
            echo json_encode(['success' => false, 'message' => 'ID requis']);
            exit;
        }
        
        $stmt = $pdo->prepare("
            UPDATE emails SET is_read = 0, updated_at = NOW()
            WHERE id = ? AND customer_id = ?
        ");
        $stmt->execute([$emailId, $customer_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Marqué comme non lu'
        ]);
        exit;
    }
    
    // === SUPPRIMER UN EMAIL ===
    if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        // Récupérer les données JSON (déjà décodées si besoin)
        if (!isset($jsonData)) {
            $jsonData = json_decode(file_get_contents('php://input'), true);
        }
        $emailId = $jsonData['id'] ?? null;
        
        if (!$emailId) {
            echo json_encode(['success' => false, 'message' => 'ID requis']);
            exit;
        }
        
        $stmt = $pdo->prepare("
            DELETE FROM emails 
            WHERE id = ? AND customer_id = ?
        ");
        $stmt->execute([$emailId, $customer_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Email supprimé'
        ]);
        exit;
    }
    
    // === SUPPRIMER PLUSIEURS EMAILS ===
    if ($action === 'delete_multiple' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($jsonData)) {
            $jsonData = json_decode(file_get_contents('php://input'), true);
        }
        $emailIds = $jsonData['ids'] ?? [];
        
        if (empty($emailIds) || !is_array($emailIds)) {
            echo json_encode(['success' => false, 'message' => 'IDs requis']);
            exit;
        }
        
        $placeholders = implode(',', array_fill(0, count($emailIds), '?'));
        $stmt = $pdo->prepare("
            DELETE FROM emails 
            WHERE id IN ($placeholders) AND customer_id = ?
        ");
        $stmt->execute([...$emailIds, $customer_id]);
        
        echo json_encode([
            'success' => true,
            'message' => count($emailIds) . ' emails supprimés',
            'deleted' => $stmt->rowCount()
        ]);
        exit;
    }
    
    // === MARQUER COMME SPAM ===
    if ($action === 'mark_spam' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($jsonData)) {
            $jsonData = json_decode(file_get_contents('php://input'), true);
        }
        $emailId = $jsonData['id'] ?? null;
        
        if (!$emailId) {
            echo json_encode(['success' => false, 'message' => 'ID requis']);
            exit;
        }
        
        $stmt = $pdo->prepare("
            UPDATE emails SET is_spam = 1, updated_at = NOW()
            WHERE id = ? AND customer_id = ?
        ");
        $stmt->execute([$emailId, $customer_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Marqué comme spam'
        ]);
        exit;
    }
    
    // === EXTRAIRE UN LEAD DEPUIS UN EMAIL ===
    if ($action === 'extract_lead' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($jsonData)) {
            $jsonData = json_decode(file_get_contents('php://input'), true);
        }
        $emailId = $jsonData['id'] ?? null;
        
        if (!$emailId) {
            echo json_encode(['success' => false, 'message' => 'ID requis']);
            exit;
        }
        
        // Récupérer l'email
        $stmt = $pdo->prepare("
            SELECT * FROM emails 
            WHERE id = ? AND customer_id = ?
        ");
        $stmt->execute([$emailId, $customer_id]);
        $email = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$email) {
            echo json_encode(['success' => false, 'message' => 'Email non trouvé']);
            exit;
        }
        
        // Créer ou mettre à jour le lead
        try {
            $stmt = $pdo->prepare("
                INSERT INTO leads (
                    customer_id, email, name, stage, source, created_at
                ) VALUES (?, ?, ?, 'new', 'email', NOW())
                ON DUPLICATE KEY UPDATE
                    name = VALUES(name),
                    updated_at = NOW()
            ");
            
            $name = $email['from_name'] ?: explode('@', $email['from_address'])[0];
            $stmt->execute([
                $customer_id,
                $email['from_address'],
                $name
            ]);
            
            $leadId = $pdo->lastInsertId() ?: $pdo->query("SELECT id FROM leads WHERE email = '{$email['from_address']}' AND customer_id = $customer_id")->fetchColumn();
            
            // Lier l'email au lead
            $stmt = $pdo->prepare("
                UPDATE emails SET extracted_lead_id = ?
                WHERE id = ?
            ");
            $stmt->execute([$leadId, $emailId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Lead créé/mis à jour',
                'lead_id' => $leadId
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la création du lead: ' . $e->getMessage()
            ]);
        }
        exit;
    }
    
    // === STATISTIQUES ===
    if ($action === 'stats' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread,
                SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as read,
                SUM(CASE WHEN has_attachments = 1 THEN 1 ELSE 0 END) as with_attachments,
                SUM(CASE WHEN is_spam = 1 THEN 1 ELSE 0 END) as spam,
                SUM(CASE WHEN extracted_lead_id IS NOT NULL THEN 1 ELSE 0 END) as with_lead
            FROM emails
            WHERE customer_id = ?
        ");
        $stmt->execute([$customer_id]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
        exit;
    }
    
    // Action non reconnue
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Action non reconnue: ' . $action
    ]);
    
} catch (Exception $e) {
    error_log("Email Operations API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}
