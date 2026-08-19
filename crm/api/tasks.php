<?php
/**
 * API pour la gestion des tâches
 */
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/permission-bootstrap.php';

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
    // === RÉCUPÉRER UNE TÂCHE ===
    if ($action === 'get' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $taskId = $_GET['id'] ?? null;
        
        if (!$taskId) {
            throw new Exception('ID requis');
        }
        
        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND customer_id = ?");
        $stmt->execute([$taskId, $customer_id]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$task) {
            http_response_code(404);
            throw new Exception('Tâche non trouvée');
        }
        
        echo json_encode([
            'success' => true,
            'task' => $task
        ]);
        exit;
    }
    
    // === CRÉER UNE TÂCHE ===
    if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($jsonData)) {
            $jsonData = json_decode(file_get_contents('php://input'), true);
        }
        
        $title = $jsonData['title'] ?? null;
        if (!$title) {
            throw new Exception('Le titre est requis');
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO tasks (
                customer_id, title, description, due_date,
                priority, status, type, related_type, related_id, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $customer_id,
            $title,
            $jsonData['description'] ?? null,
            $jsonData['due_date'] ?? null,
            $jsonData['priority'] ?? 'medium',
            $jsonData['status'] ?? 'todo',
            $jsonData['type'] ?? 'general',
            !empty($jsonData['related_type']) ? $jsonData['related_type'] : null,
            !empty($jsonData['related_id'])   ? intval($jsonData['related_id']) : null,
        ]);
        
        $taskId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Tâche créée',
            'id' => $taskId
        ]);
        exit;
    }
    
    // === METTRE À JOUR UNE TÂCHE ===
    if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($jsonData)) {
            $jsonData = json_decode(file_get_contents('php://input'), true);
        }
        
        $taskId = $jsonData['id'] ?? null;
        if (!$taskId) {
            throw new Exception('ID requis');
        }
        
        // Vérifier que la tâche appartient au client
        $check = $pdo->prepare("SELECT id FROM tasks WHERE id = ? AND customer_id = ?");
        $check->execute([$taskId, $customer_id]);
        if (!$check->fetch()) {
            http_response_code(403);
            throw new Exception('Accès refusé');
        }
        
        $stmt = $pdo->prepare("
            UPDATE tasks SET
                title        = ?,
                description  = ?,
                due_date     = ?,
                priority     = ?,
                status       = ?,
                related_type = ?,
                related_id   = ?,
                updated_at   = NOW()
            WHERE id = ? AND customer_id = ?
        ");

        $stmt->execute([
            $jsonData['title'] ?? '',
            $jsonData['description'] ?? null,
            $jsonData['due_date'] ?? null,
            $jsonData['priority'] ?? 'medium',
            $jsonData['status'] ?? 'todo',
            !empty($jsonData['related_type']) ? $jsonData['related_type'] : null,
            !empty($jsonData['related_id'])   ? intval($jsonData['related_id']) : null,
            $taskId,
            $customer_id,
        ]);
        
        // Si marquée comme terminée, enregistrer la date
        if (isset($jsonData['status']) && $jsonData['status'] === 'completed') {
            $pdo->prepare("UPDATE tasks SET completed_at = NOW() WHERE id = ?")->execute([$taskId]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Tâche mise à jour'
        ]);
        exit;
    }
    
    // === METTRE À JOUR UN CHAMP ===
    if ($action === 'update_field' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($jsonData)) {
            $jsonData = json_decode(file_get_contents('php://input'), true);
        }
        
        $taskId = $jsonData['id'] ?? null;
        $field = $jsonData['field'] ?? null;
        $value = $jsonData['value'] ?? null;
        
        if (!$taskId || !$field) {
            throw new Exception('ID et champ requis');
        }
        
        // Vérifier que la tâche appartient au client
        $check = $pdo->prepare("SELECT id FROM tasks WHERE id = ? AND customer_id = ?");
        $check->execute([$taskId, $customer_id]);
        if (!$check->fetch()) {
            http_response_code(403);
            throw new Exception('Accès refusé');
        }
        
        // Champs autorisés pour mise à jour inline
        $allowedFields = ['title', 'description', 'priority', 'status', 'due_date'];
        if (!in_array($field, $allowedFields)) {
            throw new Exception('Champ non autorisé');
        }
        
        $stmt = $pdo->prepare("UPDATE tasks SET $field = ?, updated_at = NOW() WHERE id = ? AND customer_id = ?");
        $stmt->execute([$value, $taskId, $customer_id]);
        
        // Si marquée comme terminée, enregistrer la date
        if ($field === 'status' && $value === 'completed') {
            $pdo->prepare("UPDATE tasks SET completed_at = NOW() WHERE id = ?")->execute([$taskId]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Champ mis à jour'
        ]);
        exit;
    }
    
    // === SUPPRIMER UNE TÂCHE ===
    if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($jsonData)) {
            $jsonData = json_decode(file_get_contents('php://input'), true);
        }
        
        $taskId = $jsonData['id'] ?? null;
        if (!$taskId) {
            throw new Exception('ID requis');
        }
        
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND customer_id = ?");
        $stmt->execute([$taskId, $customer_id]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Tâche non trouvée');
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Tâche supprimée'
        ]);
        exit;
    }
    
    // === LISTER LES TÂCHES ===
    if ($action === 'list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $filter = $_GET['filter'] ?? 'active';
        $limit = intval($_GET['limit'] ?? 100);
        
        $whereConditions = ["customer_id = ?"];
        $params = [$customer_id];
        
        if ($filter === 'active') {
            $whereConditions[] = "status NOT IN ('completed', 'cancelled')";
        } elseif ($filter === 'completed') {
            $whereConditions[] = "status = 'completed'";
        } elseif ($filter === 'overdue') {
            $whereConditions[] = "due_date < NOW() AND status NOT IN ('completed', 'cancelled')";
        }
        
        $sql = "SELECT * FROM tasks WHERE " . implode(' AND ', $whereConditions) . 
               " ORDER BY due_date ASC LIMIT $limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'tasks' => $tasks,
            'count' => count($tasks)
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
