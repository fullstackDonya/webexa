<?php
/**
 * API pour la gestion des rappels d'appels
 */
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../config/database.php';

// Vérifier l'authentification
if (!isset($_SESSION['customer_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

$customer_id = $_SESSION['customer_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? null;

// Si pas d'action dans GET/POST, chercher dans le JSON body
if (!$action && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $jsonData = json_decode(file_get_contents('php://input'), true);
    $action = $jsonData['action'] ?? null;
}

try {
    // === RÉCUPÉRER UN RAPPEL ===
    if ($action === 'get' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $callId = $_GET['id'] ?? null;
        
        if (!$callId) {
            throw new Exception('ID requis');
        }
        
        $stmt = $pdo->prepare("SELECT * FROM call_reminders WHERE id = ? AND customer_id = ?");
        $stmt->execute([$callId, $customer_id]);
        $call = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$call) {
            http_response_code(404);
            throw new Exception('Rappel non trouvé');
        }
        
        echo json_encode([
            'success' => true,
            'call' => $call
        ]);
        exit;
    }
    
    // === CRÉER UN RAPPEL ===
    if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($jsonData)) {
            $jsonData = json_decode(file_get_contents('php://input'), true);
        }
        
        $contact_name = $jsonData['contact_name'] ?? null;
        $phone = $jsonData['phone'] ?? null;
        $scheduled_time = $jsonData['scheduled_time'] ?? null;
        
        if (!$contact_name || !$phone || !$scheduled_time) {
            throw new Exception('Contact, téléphone et date/heure sont requis');
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO call_reminders (
                customer_id, contact_name, phone, scheduled_time,
                duration_minutes, notes, call_type, status, 
                contact_type, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $customer_id,
            $contact_name,
            $phone,
            $scheduled_time,
            $jsonData['duration_minutes'] ?? 30,
            $jsonData['notes'] ?? null,
            $jsonData['call_type'] ?? 'follow_up',
            $jsonData['status'] ?? 'pending',
            $jsonData['contact_type'] ?? 'other'
        ]);
        
        $callId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Rappel créé',
            'id' => $callId
        ]);
        exit;
    }
    
    // === METTRE À JOUR UN RAPPEL ===
    if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($jsonData)) {
            $jsonData = json_decode(file_get_contents('php://input'), true);
        }
        
        $callId = $jsonData['id'] ?? null;
        if (!$callId) {
            throw new Exception('ID requis');
        }
        
        // Vérifier que le rappel appartient au client
        $check = $pdo->prepare("SELECT id FROM call_reminders WHERE id = ? AND customer_id = ?");
        $check->execute([$callId, $customer_id]);
        if (!$check->fetch()) {
            http_response_code(403);
            throw new Exception('Accès refusé');
        }
        
        $stmt = $pdo->prepare("
            UPDATE call_reminders SET 
                contact_name = ?,
                phone = ?,
                scheduled_time = ?,
                duration_minutes = ?,
                notes = ?,
                call_type = ?,
                status = ?,
                updated_at = NOW()
            WHERE id = ? AND customer_id = ?
        ");
        
        $stmt->execute([
            $jsonData['contact_name'] ?? '',
            $jsonData['phone'] ?? '',
            $jsonData['scheduled_time'] ?? null,
            $jsonData['duration_minutes'] ?? 30,
            $jsonData['notes'] ?? null,
            $jsonData['call_type'] ?? 'follow_up',
            $jsonData['status'] ?? 'pending',
            $callId,
            $customer_id
        ]);
        
        // Si marqué comme terminé, enregistrer la date
        if (isset($jsonData['status']) && $jsonData['status'] === 'completed') {
            $pdo->prepare("UPDATE call_reminders SET completed_at = NOW() WHERE id = ?")->execute([$callId]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Rappel mis à jour'
        ]);
        exit;
    }
    
    // === METTRE À JOUR UN CHAMP ===
    if ($action === 'update_field' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($jsonData)) {
            $jsonData = json_decode(file_get_contents('php://input'), true);
        }
        
        $callId = $jsonData['id'] ?? null;
        $field = $jsonData['field'] ?? null;
        $value = $jsonData['value'] ?? null;
        
        if (!$callId || !$field) {
            throw new Exception('ID et champ requis');
        }
        
        // Vérifier que le rappel appartient au client
        $check = $pdo->prepare("SELECT id FROM call_reminders WHERE id = ? AND customer_id = ?");
        $check->execute([$callId, $customer_id]);
        if (!$check->fetch()) {
            http_response_code(403);
            throw new Exception('Accès refusé');
        }
        
        // Champs autorisés pour mise à jour inline
        $allowedFields = ['contact_name', 'phone', 'notes', 'call_type', 'status', 'scheduled_time', 'duration_minutes'];
        if (!in_array($field, $allowedFields)) {
            throw new Exception('Champ non autorisé');
        }
        
        $stmt = $pdo->prepare("UPDATE call_reminders SET $field = ?, updated_at = NOW() WHERE id = ? AND customer_id = ?");
        $stmt->execute([$value, $callId, $customer_id]);
        
        // Si marqué comme terminé, enregistrer la date
        if ($field === 'status' && $value === 'completed') {
            $pdo->prepare("UPDATE call_reminders SET completed_at = NOW() WHERE id = ?")->execute([$callId]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Champ mis à jour'
        ]);
        exit;
    }
    
    // === SUPPRIMER UN RAPPEL ===
    if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($jsonData)) {
            $jsonData = json_decode(file_get_contents('php://input'), true);
        }
        
        $callId = $jsonData['id'] ?? null;
        if (!$callId) {
            throw new Exception('ID requis');
        }
        
        $stmt = $pdo->prepare("DELETE FROM call_reminders WHERE id = ? AND customer_id = ?");
        $stmt->execute([$callId, $customer_id]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Rappel non trouvé');
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Rappel supprimé'
        ]);
        exit;
    }
    
    // === LISTER LES RAPPELS ===
    if ($action === 'list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $filter = $_GET['filter'] ?? 'pending';
        $limit = intval($_GET['limit'] ?? 100);
        
        $whereConditions = ["customer_id = ?"];
        $params = [$customer_id];
        
        if ($filter === 'pending') {
            $whereConditions[] = "status = 'pending'";
        } elseif ($filter === 'today') {
            $whereConditions[] = "DATE(scheduled_time) = CURDATE() AND status = 'pending'";
        } elseif ($filter === 'overdue') {
            $whereConditions[] = "scheduled_time < NOW() AND status = 'pending'";
        }
        
        $sql = "SELECT * FROM call_reminders WHERE " . implode(' AND ', $whereConditions) . 
               " ORDER BY scheduled_time ASC LIMIT $limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $calls = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'calls' => $calls,
            'count' => count($calls)
        ]);
        exit;
    }
    
    // Action non reconnue
    throw new Exception('Action non reconnue');
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
