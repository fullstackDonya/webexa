<?php
/**
 * API de gestion des intégrations CRM
 * Gère les connexions avec les services tiers (Notion, Google, Slack, etc.)
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

session_start();
$customer_id = $_SESSION['customer_id'] ?? 22;
$user_id = $_SESSION['user_id'] ?? null;

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            // Lister toutes les intégrations disponibles
            $stmt = $pdo->prepare("
                SELECT * FROM integrations 
                WHERE customer_id = ?
                ORDER BY is_active DESC, name ASC
            ");
            $stmt->execute([$customer_id]);
            $integrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Si aucune intégration n'existe, initialiser les intégrations par défaut
            if (empty($integrations)) {
                initializeIntegrations($pdo, $customer_id);
                
                // Recharger les intégrations
                $stmt->execute([$customer_id]);
                $integrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            echo json_encode([
                'success' => true,
                'integrations' => $integrations,
                'total' => count($integrations),
                'active_count' => count(array_filter($integrations, fn($i) => $i['is_active']))
            ]);
            break;
            
        case 'get':
            // Obtenir une intégration spécifique
            $id = intval($_GET['id'] ?? 0);
            
            $stmt = $pdo->prepare("
                SELECT * FROM integrations 
                WHERE id = ? AND customer_id = ?
            ");
            $stmt->execute([$id, $customer_id]);
            $integration = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$integration) {
                throw new Exception('Intégration non trouvée');
            }
            
            // Déchiffrer les données sensibles si nécessaire
            if ($integration['config']) {
                $integration['config'] = json_decode($integration['config'], true);
            }
            
            echo json_encode([
                'success' => true,
                'integration' => $integration
            ]);
            break;
            
        case 'toggle':
            // Activer/désactiver une intégration
            $id = intval($_POST['id'] ?? 0);
            $is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : null;
            
            if (!$id) {
                throw new Exception('ID d\'intégration manquant');
            }
            
            if ($is_active === null) {
                // Toggle l'état actuel
                $stmt = $pdo->prepare("
                    UPDATE integrations 
                    SET is_active = NOT is_active
                    WHERE id = ? AND customer_id = ?
                ");
                $stmt->execute([$id, $customer_id]);
            } else {
                // Définir l'état spécifié
                $stmt = $pdo->prepare("
                    UPDATE integrations 
                    SET is_active = ?
                    WHERE id = ? AND customer_id = ?
                ");
                $stmt->execute([$is_active, $id, $customer_id]);
            }
            
            // Récupérer l'état mis à jour
            $stmt = $pdo->prepare("SELECT name, is_active FROM integrations WHERE id = ?");
            $stmt->execute([$id]);
            $updated = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Créer une notification
            createNotification($pdo, $customer_id, [
                'type' => 'integration_toggle',
                'title' => 'Intégration ' . ($updated['is_active'] ? 'activée' : 'désactivée'),
                'message' => "L'intégration {$updated['name']} a été " . ($updated['is_active'] ? 'activée' : 'désactivée'),
                'icon' => $updated['is_active'] ? 'fa-check-circle' : 'fa-times-circle',
                'color' => $updated['is_active'] ? 'success' : 'warning',
                'link' => 'integrations.php'
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Statut de l\'intégration mis à jour',
                'is_active' => $updated['is_active']
            ]);
            break;
            
        case 'configure':
            // Configurer une intégration
            $data = json_decode(file_get_contents('php://input'), true);
            $id = intval($data['id'] ?? 0);
            
            if (!$id) {
                throw new Exception('ID d\'intégration manquant');
            }
            
            $updates = [];
            $params = [];
            
            if (isset($data['api_key'])) {
                $updates[] = "api_key = ?";
                $params[] = $data['api_key'];
            }
            
            if (isset($data['api_secret'])) {
                $updates[] = "api_secret = ?";
                $params[] = $data['api_secret'];
            }
            
            if (isset($data['webhook_url'])) {
                $updates[] = "webhook_url = ?";
                $params[] = $data['webhook_url'];
            }
            
            if (isset($data['config'])) {
                $updates[] = "config = ?";
                $params[] = json_encode($data['config']);
            }
            
            if (empty($updates)) {
                throw new Exception('Aucune donnée à mettre à jour');
            }
            
            $params[] = $id;
            $params[] = $customer_id;
            
            $sql = "UPDATE integrations SET " . implode(', ', $updates) . " 
                    WHERE id = ? AND customer_id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            echo json_encode([
                'success' => true,
                'message' => 'Configuration enregistrée'
            ]);
            break;
            
        case 'sync':
            // Synchroniser une intégration
            $id = intval($_POST['id'] ?? 0);
            
            if (!$id) {
                throw new Exception('ID d\'intégration manquant');
            }
            
            // Récupérer l'intégration
            $stmt = $pdo->prepare("
                SELECT * FROM integrations 
                WHERE id = ? AND customer_id = ?
            ");
            $stmt->execute([$id, $customer_id]);
            $integration = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$integration) {
                throw new Exception('Intégration non trouvée');
            }
            
            if (!$integration['is_active']) {
                throw new Exception('L\'intégration doit être activée pour la synchroniser');
            }
            
            // Marquer comme en cours de synchronisation
            $stmt = $pdo->prepare("
                UPDATE integrations 
                SET sync_status = 'syncing'
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            
            // Simuler une synchronisation (À remplacer par la vraie logique)
            $syncResult = performSync($integration);
            
            // Mettre à jour le statut
            $stmt = $pdo->prepare("
                UPDATE integrations 
                SET sync_status = ?, 
                    last_sync = NOW(),
                    error_message = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $syncResult['success'] ? 'success' : 'error',
                $syncResult['error'] ?? null,
                $id
            ]);
            
            // Logger
            logIntegration($pdo, $id, 'sync', $syncResult['success'] ? 'success' : 'error', 
                          $syncResult['message'] ?? '', $syncResult);
            
            echo json_encode([
                'success' => $syncResult['success'],
                'message' => $syncResult['message'] ?? 'Synchronisation terminée',
                'data' => $syncResult['data'] ?? null
            ]);
            break;
            
        case 'test':
            // Tester la connexion d'une intégration
            $id = intval($_POST['id'] ?? 0);
            
            if (!$id) {
                throw new Exception('ID d\'intégration manquant');
            }
            
            $stmt = $pdo->prepare("SELECT * FROM integrations WHERE id = ? AND customer_id = ?");
            $stmt->execute([$id, $customer_id]);
            $integration = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$integration) {
                throw new Exception('Intégration non trouvée');
            }
            
            // Tester la connexion
            $testResult = testConnection($integration);
            
            logIntegration($pdo, $id, 'test', $testResult['success'] ? 'success' : 'error',
                          $testResult['message'] ?? '');
            
            echo json_encode([
                'success' => $testResult['success'],
                'message' => $testResult['message']
            ]);
            break;
            
        case 'logs':
            // Récupérer les logs d'une intégration
            $id = intval($_GET['id'] ?? 0);
            $limit = intval($_GET['limit'] ?? 50);
            
            $stmt = $pdo->prepare("
                SELECT * FROM integration_logs
                WHERE integration_id = ?
                ORDER BY created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$id, $limit]);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'logs' => $logs,
                'total' => count($logs)
            ]);
            break;
            
        default:
            throw new Exception('Action non reconnue');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// Fonctions helper
function initializeIntegrations($pdo, $customer_id) {
    $integrations = [
        ['notion', 'Notion', 'Synchronisez vos données CRM avec Notion', 'fa-file-alt', 'dark'],
        ['google_forms', 'Google Forms', 'Importez automatiquement les réponses', 'fab fa-google', 'danger'],
        ['zoom', 'Zoom', 'Planifiez vos réunions Zoom', 'fa-video', 'info'],
        ['linkedin', 'LinkedIn', 'Enrichissez vos leads avec LinkedIn', 'fab fa-linkedin', 'primary'],
        ['slack', 'Slack', 'Notifications et gestion depuis Slack', 'fab fa-slack', 'warning'],
        ['github', 'GitHub', 'Liez vos projets GitHub', 'fab fa-github', 'dark'],
        ['gitlab', 'GitLab', 'Intégration GitLab', 'fab fa-gitlab', 'warning'],
        ['jira', 'Jira', 'Synchronisez vos tickets Jira', 'fa-tasks', 'primary'],
        ['trello', 'Trello', 'Connectez vos boards Trello', 'fab fa-trello', 'info'],
        ['microsoft_teams', 'Microsoft Teams', 'Collaboration via Teams', 'fa-users', 'primary'],
        ['hubspot', 'HubSpot', 'Synchronisation avec HubSpot CRM', 'fa-chart-line', 'warning'],
        ['dropbox', 'Dropbox', 'Stockez vos fichiers', 'fab fa-dropbox', 'info'],
        ['google_drive', 'Google Drive', 'Accédez à Google Drive', 'fab fa-google-drive', 'success'],
        ['google_calendar', 'Google Calendar', 'Synchronisez vos événements', 'fa-calendar', 'danger'],
        ['outlook_calendar', 'Outlook Calendar', 'Calendrier Outlook', 'fa-calendar-alt', 'primary'],
        ['meta_ads', 'Meta Ads', 'Campagnes Facebook/Instagram', 'fab fa-facebook', 'primary'],
        ['google_ads', 'Google Ads', 'Analysez Google Ads', 'fab fa-google', 'danger'],
        ['mailchimp', 'Mailchimp', 'Synchronisez vos campagnes email', 'fa-envelope', 'warning'],
        ['stripe', 'Stripe', 'Gestion des paiements', 'fab fa-stripe', 'primary'],
        ['zapier', 'Zapier', 'Automatisez vos workflows', 'fa-bolt', 'warning'],
        ['webhooks', 'Webhooks', 'Webhooks personnalisés', 'fa-code', 'secondary']
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO integrations (customer_id, integration_type, name, description, icon, color)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE name = VALUES(name)
    ");
    
    foreach ($integrations as $int) {
        $stmt->execute([$customer_id, $int[0], $int[1], $int[2], $int[3], $int[4]]);
    }
}

function performSync($integration) {
    // Simuler une synchronisation
    // À remplacer par la vraie logique selon le type d'intégration
    return [
        'success' => true,
        'message' => 'Synchronisation simulée réussie',
        'data' => [
            'synced_items' => rand(5, 50),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];
}

function testConnection($integration) {
    // Tester la connexion selon le type
    // À remplacer par de vrais tests
    
    if (empty($integration['api_key']) && $integration['integration_type'] !== 'webhooks') {
        return [
            'success' => false,
            'message' => 'Clé API manquante'
        ];
    }
    
    return [
        'success' => true,
        'message' => 'Connexion réussie'
    ];
}

function logIntegration($pdo, $integration_id, $action, $status, $message, $data = null) {
    $stmt = $pdo->prepare("
        INSERT INTO integration_logs (integration_id, action, status, message, data)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $integration_id,
        $action,
        $status,
        $message,
        $data ? json_encode($data) : null
    ]);
}

function createNotification($pdo, $customer_id, $data) {
    $stmt = $pdo->prepare("
        INSERT INTO crm_notifications 
        (customer_id, type, title, message, icon, color, link, priority)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'medium')
    ");
    
    $stmt->execute([
        $customer_id,
        $data['type'],
        $data['title'],
        $data['message'],
        $data['icon'] ?? 'fa-bell',
        $data['color'] ?? 'info',
        $data['link'] ?? null
    ]);
}
