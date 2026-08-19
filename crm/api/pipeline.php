<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/permission-bootstrap.php';
require_once __DIR__ . '/../includes/pipeline_utils.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification de sécurité : l'utilisateur doit être connecté
$customerId = $_SESSION['customer_id'] ?? null;
$userId = $_SESSION['user_id'] ?? ($_SESSION['user']['id'] ?? null);

if (!$customerId && !$userId) {
    http_response_code(401);
    $response['error'] = 'Non authentifié';
    echo json_encode($response);
    exit;
}

$response = ['success' => false];

try {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $action = $_GET['action'] ?? ($method === 'POST' ? 'move' : 'board');

    // Pour POST, on doit d'abord lire le payload pour récupérer l'entity
    if ($method === 'POST') {
        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            $payload = $_POST;
        }
        // Récupérer entity depuis le payload en priorité
        $entityParam = $payload['entity'] ?? $_GET['entity'] ?? $_POST['entity'] ?? null;
    } else {
        $entityParam = $_GET['entity'] ?? $_POST['entity'] ?? null;
    }
    
    $entityType = pipeline_normalize_entity_type($entityParam ?? 'opportunity');
    
    error_log("PIPELINE API - Method: $method, Action: $action");
    error_log("PIPELINE API - entityParam from request: " . var_export($entityParam, true));
    error_log("PIPELINE API - normalized entityType: " . var_export($entityType, true));
    
    if (!$entityType) {
        throw new InvalidArgumentException('Type d\'entité invalide.');
    }

    if ($method === 'GET' && $action === 'board') {
        $data = pipeline_get_board($pdo, $entityType, $customerId ? (int)$customerId : null, $userId ? (int)$userId : null);
        $response['success'] = true;
        $response['data'] = $data;
        echo json_encode($response);
        exit;
    }

    if ($method === 'POST' && $action === 'move') {
        // Le payload a déjà été lu en haut, on le réutilise
        if (!isset($payload) || !is_array($payload)) {
            $payload = json_decode(file_get_contents('php://input'), true);
            if (!is_array($payload)) {
                $payload = $_POST;
            }
        }
        
        error_log("PIPELINE API MOVE - payload reçu: " . json_encode($payload));
        error_log("PIPELINE API MOVE - entityType utilisé: " . $entityType);

        $stageId = isset($payload['stage_id']) ? (int)$payload['stage_id'] : 0;
        $entityId = isset($payload['item_id']) ? (int)$payload['item_id'] : (isset($payload['entity_id']) ? (int)$payload['entity_id'] : 0);
        $position = isset($payload['position']) ? (int)$payload['position'] : null;

        if ($stageId <= 0 || $entityId <= 0) {
            throw new InvalidArgumentException('Paramètres manquants pour le déplacement.');
        }

        $result = pipeline_move_item(
            $pdo,
            $entityType,
            $entityId,
            $stageId,
            $position,
            $userId ? (int)$userId : null,
            $customerId ? (int)$customerId : null
        );
        $response['success'] = true;
        $response['data'] = $result;
        echo json_encode($response);
        exit;
    }

    throw new InvalidArgumentException('Action non supportée.');
} catch (Throwable $e) {
    http_response_code($e instanceof InvalidArgumentException ? 400 : 500);
    $response['error'] = $e->getMessage();
    echo json_encode($response);
    exit;
}