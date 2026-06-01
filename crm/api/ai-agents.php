<?php
/**
 * API endpoint pour gérer les agents IA depuis le CRM
 */

// Activer l'affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 0); // Ne pas afficher les erreurs HTML
header('Content-Type: application/json; charset=utf-8');

// Gestionnaire d'erreurs global
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// Gestionnaire d'exceptions non capturées
set_exception_handler(function($exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine()
    ]);
    exit;
});

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/AIAgentsClient.php';

session_start();

if (!isset($_SESSION['customer_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

$customerId = (int)$_SESSION['customer_id'];
$action = $_REQUEST['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Initialiser le client API avec l'URL de base depuis .env
$aiClientConfig = [
    'baseUrl' => $_ENV['AI_API_BASE_URL'] ?? 'https://webexa.online',
    'apiKey' => $_ENV['AI_API_KEY'] ?? 'bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps'
];
$aiClient = new AIAgentsClient($aiClientConfig['baseUrl'], $aiClientConfig['apiKey']);

try {
    switch ($action) {
        // ========== Vérification santé ==========
        case 'health':
            $result = $aiClient->healthCheck();
            echo json_encode(['success' => true, 'data' => $result]);
            break;

        // ========== Analyse d'email ==========
        case 'analyze-email':
            if ($method !== 'POST') {
                throw new Exception('POST required');
            }

            $emailId = (int)($_POST['email_id'] ?? 0);
            
            // Récupérer l'email depuis la DB
            $stmt = $pdo->prepare("
                SELECT id, subject, body_text, from_email 
                FROM emails 
                WHERE id = ? AND customer_id = ?
            ");
            $stmt->execute([$emailId, $customerId]);
            $email = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$email) {
                throw new Exception('Email not found');
            }

            $result = $aiClient->analyzeEmail(
                $emailId,
                $email['subject'],
                $email['body_text'] ?? '',
                $email['from_email'],
                $customerId
            );

            echo json_encode(['success' => true, 'data' => $result]);
            break;

        // ========== Traiter emails en attente ==========
        case 'process-emails':
            $limit = (int)($_POST['limit'] ?? 10);
            $result = $aiClient->processPendingEmails($customerId, $limit);
            echo json_encode(['success' => true, 'data' => $result]);
            break;

        // ========== Scorer un lead ==========
        case 'score-lead':
            if ($method !== 'POST') {
                throw new Exception('POST required');
            }

            $leadId = (int)($_POST['lead_id'] ?? 0);
            if ($leadId <= 0) {
                throw new Exception('Invalid lead_id');
            }

            $result = $aiClient->scoreLead($leadId, $customerId);
            echo json_encode(['success' => true, 'data' => $result]);
            break;

        // ========== Scorer tous les leads ==========
        case 'score-all-leads':
            // Récupérer tous les leads actifs
            $stmt = $pdo->prepare("
                SELECT id FROM leads 
                WHERE customer_id = ? 
                AND status NOT IN ('lost', 'converted')
                ORDER BY created_at DESC
            ");
            $stmt->execute([$customerId]);
            $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $results = [];
            $errors = 0;
            
            foreach ($leads as $lead) {
                try {
                    $result = $aiClient->scoreLead($lead['id'], $customerId);
                    $results[] = $result;
                } catch (Exception $e) {
                    $errors++;
                }
            }
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'total' => count($leads),
                    'scored' => count($results),
                    'errors' => $errors
                ]
            ]);
            break;

        // ========== Leads chauds ==========
        case 'hot-leads':
            $limit = (int)($_GET['limit'] ?? 10);
            $result = $aiClient->getHotLeads($customerId, $limit);
            echo json_encode(['success' => true, 'data' => $result]);
            break;

        // ========== Leads froids ==========
        case 'cold-leads':
            $days = (int)($_GET['days'] ?? 30);
            $result = $aiClient->getColdLeads($customerId, $days);
            echo json_encode(['success' => true, 'data' => $result]);
            break;

        // ========== Actions en attente ==========
        case 'pending-actions':
            $result = $aiClient->getPendingActions($customerId);
            echo json_encode(['success' => true, 'data' => $result]);
            break;

        // ========== Approuver une action ==========
        case 'approve-action':
            if ($method !== 'POST') {
                throw new Exception('POST required');
            }

            $actionId = (int)($_POST['action_id'] ?? 0);
            if ($actionId <= 0) {
                throw new Exception('Invalid action_id');
            }

            $result = $aiClient->approveAction($actionId);
            echo json_encode(['success' => true, 'data' => $result]);
            break;

        // ========== Rejeter une action ==========
        case 'reject-action':
            if ($method !== 'POST') {
                throw new Exception('POST required');
            }

            $actionId = (int)($_POST['action_id'] ?? 0);
            if ($actionId <= 0) {
                throw new Exception('Invalid action_id');
            }

            $result = $aiClient->rejectAction($actionId);
            echo json_encode(['success' => true, 'data' => $result]);
            break;

        // ========== Logs ==========
        case 'logs':
            $agentName = $_GET['agent_name'] ?? null;
            $limit = (int)($_GET['limit'] ?? 50);
            $result = $aiClient->getLogs($agentName, $limit);
            echo json_encode(['success' => true, 'data' => $result]);
            break;

        // ========== Statistiques générales ==========
        case 'stats':
            // Récupérer les stats depuis la vue agent_stats
            $stmt = $pdo->prepare("
                SELECT * FROM agent_stats 
                WHERE customer_id = ? 
                ORDER BY date DESC 
                LIMIT 30
            ");
            $stmt->execute([$customerId]);
            $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Compter les actions pendantes
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count FROM agent_actions 
                WHERE customer_id = ? AND status = 'pending'
            ");
            $stmt->execute([$customerId]);
            $pendingCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            echo json_encode([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'pending_actions' => $pendingCount
                ]
            ]);
            break;

        default:
            throw new Exception('Action inconnue');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
