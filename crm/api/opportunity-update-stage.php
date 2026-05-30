<?php

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/pipeline_utils.php';

$user_id = $_SESSION['user_id'] ?? null;
$customer_id = $_SESSION['customer_id'] ?? null;

if (!$user_id && !$customer_id) {
    echo json_encode(['success' => false, 'error' => 'unauthenticated']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = isset($input['id']) ? intval($input['id']) : 0;
$newStageSlug = $input['stage'] ?? null;

if (!$id || !$newStageSlug) {
    echo json_encode(['success' => false, 'error' => 'invalid_params', 'message' => 'ID ou stage manquant']);
    exit;
}

try {
    // Vérifier que l'opportunité existe et que l'utilisateur y a accès
    if ($customer_id) {
        // Pour un customer, vérifier via customer_id direct ou via company
        $stmt = $pdo->prepare("SELECT o.id, o.stage FROM opportunities o 
            LEFT JOIN companies co ON co.id = o.company_id 
            WHERE o.id = ? AND (o.customer_id = ? OR co.customer_id = ?)");
        $stmt->execute([$id, $customer_id, $customer_id]);
    } else {
        // Pour un utilisateur, vérifier via assigned_to
        $stmt = $pdo->prepare("SELECT id, stage FROM opportunities WHERE id = ? AND assigned_to = ?");
        $stmt->execute([$id, $user_id]);
    }
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo json_encode(['success' => false, 'error' => 'not_found', 'message' => 'Opportunité non trouvée ou accès refusé']);
        exit;
    }

    // Récupérer le stage_id depuis le système pipeline
    pipeline_ensure_schema($pdo);
    $boardId = pipeline_get_board_id($pdo, 'opportunity');
    $stageData = pipeline_get_stage_by_slug($pdo, 'opportunity', $newStageSlug);
    
    if (!$stageData) {
        echo json_encode(['success' => false, 'error' => 'invalid_stage', 'message' => 'Stage invalide: ' . $newStageSlug]);
        exit;
    }
    
    $stageId = (int)$stageData['id'];
    $probability = $stageData['probability_default'] ?? null;

    // Update stage dans la table opportunities (et actual_close_date si closed_won)
    if ($newStageSlug === 'closed_won') {
        $stmt = $pdo->prepare("UPDATE opportunities SET stage = ?, probability = ?, actual_close_date = NOW(), updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newStageSlug, $probability, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE opportunities SET stage = ?, probability = IF(? IS NULL, probability, ?), updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newStageSlug, $probability, $probability, $id]);
    }

    // Synchroniser avec pipeline_entity_stages
    $stmt = $pdo->prepare("INSERT INTO pipeline_entity_stages (entity_type, entity_id, stage_id, position, updated_at)
        VALUES ('opportunity', ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE stage_id = VALUES(stage_id), position = VALUES(position), updated_at = NOW()");
    $stmt->execute([$id, $stageId, time()]);

    echo json_encode(['success' => true, 'message' => 'Stage mis à jour avec succès']);
} catch (Exception $e) {
    error_log('opportunity-update-stage error: '.$e->getMessage());
    echo json_encode(['success' => false, 'error' => 'server_error', 'message' => $e->getMessage()]);
}

