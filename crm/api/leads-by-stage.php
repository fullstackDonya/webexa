<?php
/**
 * API pour récupérer les leads par stage (pour les campagnes)
 */

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

$customer_id = $_SESSION['customer_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

if (!$customer_id && !$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

try {
    $action = $_GET['action'] ?? 'stages';
    
    // Récupérer les stages de leads depuis le pipeline
    if ($action === 'stages') {
        $sql = "SELECT DISTINCT 
                    ps.id as stage_id,
                    ps.slug,
                    ps.name,
                    ps.color_code,
                    COUNT(DISTINCT pes.entity_id) as lead_count
                FROM pipeline_stages ps
                LEFT JOIN pipeline_entity_stages pes ON pes.stage_id = ps.id AND pes.entity_type = 'lead'
                WHERE ps.entity_type = 'lead' AND ps.is_active = 1
                GROUP BY ps.id, ps.slug, ps.name, ps.color_code, ps.order_position
                ORDER BY ps.order_position ASC";
        
        $stmt = $pdo->query($sql);
        $stages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'stages' => $stages
        ]);
        exit;
    }
    
    // Récupérer les leads d'un stage spécifique
    if ($action === 'leads') {
        $stage_id = isset($_GET['stage_id']) ? (int)$_GET['stage_id'] : 0;
        $stage_slug = $_GET['stage_slug'] ?? '';
        
        if ($stage_id > 0) {
            // Par ID de stage
            $sql = "SELECT DISTINCT 
                        l.id,
                        l.first_name,
                        l.last_name,
                        l.email,
                        l.phone,
                        l.company,
                        l.ai_score,
                        ps.name as stage_name,
                        ps.slug as stage_slug
                    FROM leads l
                    INNER JOIN pipeline_entity_stages pes ON pes.entity_id = l.id AND pes.entity_type = 'lead'
                    INNER JOIN pipeline_stages ps ON ps.id = pes.stage_id
                    WHERE pes.stage_id = ?";
            
            $params = [$stage_id];
            
            if ($customer_id) {
                $sql .= " AND l.customer_id = ?";
                $params[] = $customer_id;
            }
            
            $sql .= " ORDER BY l.ai_score DESC, l.created_at DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
        } elseif ($stage_slug) {
            // Par slug de stage (nouveau, contacté, qualifié, etc.)
            $sql = "SELECT DISTINCT 
                        l.id,
                        l.first_name,
                        l.last_name,
                        l.email,
                        l.phone,
                        l.company,
                        l.ai_score,
                        ps.name as stage_name,
                        ps.slug as stage_slug
                    FROM leads l
                    INNER JOIN pipeline_entity_stages pes ON pes.entity_id = l.id AND pes.entity_type = 'lead'
                    INNER JOIN pipeline_stages ps ON ps.id = pes.stage_id
                    WHERE ps.slug = ? AND ps.entity_type = 'lead'";
            
            $params = [$stage_slug];
            
            if ($customer_id) {
                $sql .= " AND l.customer_id = ?";
                $params[] = $customer_id;
            }
            
            $sql .= " ORDER BY l.ai_score DESC, l.created_at DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
        } else {
            // Tous les leads
            $sql = "SELECT DISTINCT 
                        l.id,
                        l.first_name,
                        l.last_name,
                        l.email,
                        l.phone,
                        l.company,
                        l.ai_score
                    FROM leads l";
            
            if ($customer_id) {
                $sql .= " WHERE l.customer_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$customer_id]);
            } else {
                $stmt = $pdo->query($sql);
            }
        }
        
        $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'count' => count($leads),
            'leads' => $leads
        ]);
        exit;
    }
    
    // Compter les leads par stage
    if ($action === 'count') {
        $stage_id = isset($_GET['stage_id']) ? (int)$_GET['stage_id'] : 0;
        
        if ($stage_id > 0) {
            $sql = "SELECT COUNT(DISTINCT pes.entity_id) as count
                    FROM pipeline_entity_stages pes
                    INNER JOIN leads l ON l.id = pes.entity_id
                    WHERE pes.entity_type = 'lead' AND pes.stage_id = ?";
            
            $params = [$stage_id];
            
            if ($customer_id) {
                $sql .= " AND l.customer_id = ?";
                $params[] = $customer_id;
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        } else {
            $sql = "SELECT COUNT(DISTINCT id) as count FROM leads";
            
            if ($customer_id) {
                $sql .= " WHERE customer_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$customer_id]);
            } else {
                $stmt = $pdo->query($sql);
            }
        }
        
        $count = (int)$stmt->fetchColumn();
        
        echo json_encode([
            'success' => true,
            'count' => $count
        ]);
        exit;
    }
    
    throw new Exception('Action non supportée');
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
