<?php
/**
 * API de gestion des notifications CRM
 * Gère les alertes, notifications et rappels du système
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

// Récupérer le customer_id de la session
session_start();
$customer_id = $_SESSION['customer_id'] ?? 22; // Valeur par défaut pour tests
$user_id = $_SESSION['user_id'] ?? null;

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            // Lister les notifications
            $limit = intval($_GET['limit'] ?? 50);
            $offset = intval($_GET['offset'] ?? 0);
            $unread_only = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
            
            $sql = "SELECT * FROM crm_notifications WHERE customer_id = ?";
            $params = [$customer_id];
            
            if ($unread_only) {
                $sql .= " AND is_read = 0";
            }
            
            if ($user_id) {
                $sql .= " AND (user_id IS NULL OR user_id = ?)";
                $params[] = $user_id;
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Compter les non lues
            $countSql = "SELECT COUNT(*) as count FROM crm_notifications 
                         WHERE customer_id = ? AND is_read = 0";
            $countParams = [$customer_id];
            
            if ($user_id) {
                $countSql .= " AND (user_id IS NULL OR user_id = ?)";
                $countParams[] = $user_id;
            }
            
            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute($countParams);
            $unreadCount = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => intval($unreadCount),
                'total' => count($notifications)
            ]);
            break;
            
        case 'mark-read':
            // Marquer une notification comme lue
            $notif_id = intval($_GET['id'] ?? 0);
            
            if ($notif_id) {
                $stmt = $pdo->prepare("
                    UPDATE crm_notifications 
                    SET is_read = 1, read_at = NOW() 
                    WHERE id = ? AND customer_id = ?
                ");
                $stmt->execute([$notif_id, $customer_id]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Notification marquée comme lue'
                ]);
            } else {
                throw new Exception('ID de notification manquant');
            }
            break;
            
        case 'mark-all-read':
            // Marquer toutes les notifications comme lues
            $sql = "UPDATE crm_notifications SET is_read = 1, read_at = NOW() 
                    WHERE customer_id = ? AND is_read = 0";
            $params = [$customer_id];
            
            if ($user_id) {
                $sql .= " AND (user_id IS NULL OR user_id = ?)";
                $params[] = $user_id;
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            echo json_encode([
                'success' => true,
                'message' => 'Toutes les notifications ont été marquées comme lues',
                'updated' => $stmt->rowCount()
            ]);
            break;
            
        case 'create':
            // Créer une nouvelle notification
            $data = json_decode(file_get_contents('php://input'), true);
            
            $required = ['type', 'title', 'message'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Le champ $field est requis");
                }
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO crm_notifications 
                (customer_id, user_id, type, title, message, icon, color, link, priority)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $customer_id,
                $data['user_id'] ?? null,
                $data['type'],
                $data['title'],
                $data['message'],
                $data['icon'] ?? 'fa-bell',
                $data['color'] ?? 'info',
                $data['link'] ?? null,
                $data['priority'] ?? 'medium'
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Notification créée',
                'id' => $pdo->lastInsertId()
            ]);
            break;
            
        case 'delete':
            // Supprimer une notification
            $notif_id = intval($_GET['id'] ?? 0);
            
            if ($notif_id) {
                $stmt = $pdo->prepare("
                    DELETE FROM crm_notifications 
                    WHERE id = ? AND customer_id = ?
                ");
                $stmt->execute([$notif_id, $customer_id]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Notification supprimée'
                ]);
            } else {
                throw new Exception('ID de notification manquant');
            }
            break;
            
        case 'delete-all-read':
            // Supprimer toutes les notifications lues
            $stmt = $pdo->prepare("
                DELETE FROM crm_notifications 
                WHERE customer_id = ? AND is_read = 1
            ");
            $stmt->execute([$customer_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Notifications lues supprimées',
                'deleted' => $stmt->rowCount()
            ]);
            break;
            
        case 'stats':
            // Statistiques des notifications
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread,
                    SUM(CASE WHEN priority = 'urgent' AND is_read = 0 THEN 1 ELSE 0 END) as urgent,
                    COUNT(DISTINCT type) as types_count
                FROM crm_notifications
                WHERE customer_id = ?
            ");
            $stmt->execute([$customer_id]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Notifications par type
            $typeStmt = $pdo->prepare("
                SELECT type, COUNT(*) as count, 
                       SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_count
                FROM crm_notifications
                WHERE customer_id = ?
                GROUP BY type
                ORDER BY count DESC
            ");
            $typeStmt->execute([$customer_id]);
            $byType = $typeStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'stats' => $stats,
                'by_type' => $byType
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
