<?php
session_start();
require_once __DIR__ . '/../crm/config/database.php';

$customer_id = $_SESSION['customer_id'] ?? 0;

// API de synchronisation ERP <-> CRM
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$response = ['success' => false, 'message' => ''];

try {
    switch ($action) {
        case 'sync_missions':
            // Synchroniser les missions du CRM vers l'ERP
            $stmt = $pdo->prepare("
                SELECT 
                    m.id, m.departure, m.arrival, m.datetime, m.driver, m.vehicle,
                    m.status_id, m.notes, m.folder_id, m.created_at, m.updated_at
                FROM missions m
                INNER JOIN folders f ON m.folder_id = f.id
                INNER JOIN companies c ON f.company_id = c.id
                WHERE c.customer_id = ?
                ORDER BY m.datetime DESC
            ");
            $stmt->execute([$customer_id]);
            $missions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response['success'] = true;
            $response['data'] = $missions;
            $response['count'] = count($missions);
            break;
            
        case 'sync_companies':
            // Synchroniser les entreprises
            $stmt = $pdo->prepare("
                SELECT id, name, email, phone, address, created_at, updated_at
                FROM companies
                WHERE customer_id = ?
                ORDER BY name
            ");
            $stmt->execute([$customer_id]);
            $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response['success'] = true;
            $response['data'] = $companies;
            $response['count'] = count($companies);
            break;
            
        case 'sync_shifts':
            // Synchroniser les shifts de l'ERP
            $stmt = $pdo->prepare("
                SELECT 
                    sh.id, sh.employee_id, sh.company_id, 
                    sh.start_time, sh.end_time, sh.notes,
                    CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                    c.name as company_name
                FROM erp_shifts sh
                LEFT JOIN erp_employees e ON sh.employee_id = e.id
                LEFT JOIN companies c ON sh.company_id = c.id
                WHERE DATE(sh.start_time) >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                ORDER BY sh.start_time DESC
            ");
            $stmt->execute();
            $shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response['success'] = true;
            $response['data'] = $shifts;
            $response['count'] = count($shifts);
            break;
            
        case 'sync_sales':
            // Synchroniser les ventes entre CRM et ERP
            $stmt = $pdo->prepare("
                SELECT 
                    s.id, s.sale_date, s.customer_name, s.product_name,
                    s.quantity, s.unit_price, s.total_price, s.notes,
                    s.created_at, s.updated_at
                FROM erp_sales s
                WHERE DATE(s.sale_date) >= DATE_SUB(CURDATE(), INTERVAL 180 DAY)
                ORDER BY s.sale_date DESC
            ");
            $stmt->execute();
            $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response['success'] = true;
            $response['data'] = $sales;
            $response['count'] = count($sales);
            break;
            
        case 'get_stats':
            // Obtenir les statistiques globales pour synchronisation
            $stats = [];
            
            // Missions
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total,
                    SUM(CASE WHEN s.name = 'Terminée' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN s.name IN ('En cours', 'À faire') THEN 1 ELSE 0 END) as active
                FROM missions m
                INNER JOIN folders f ON m.folder_id = f.id
                INNER JOIN companies c ON f.company_id = c.id
                LEFT JOIN statuses s ON m.status_id = s.id
                WHERE c.customer_id = ?
            ");
            $stmt->execute([$customer_id]);
            $stats['missions'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Employés
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                    AVG(salary) as avg_salary
                FROM erp_employees
            ");
            $stmt->execute();
            $stats['employees'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Entreprises
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total
                FROM companies
                WHERE customer_id = ?
            ");
            $stmt->execute([$customer_id]);
            $stats['companies'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Shifts
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total,
                    COUNT(DISTINCT employee_id) as unique_employees,
                    COUNT(DISTINCT company_id) as unique_companies
                FROM erp_shifts
                WHERE DATE(start_time) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ");
            $stmt->execute();
            $stats['shifts'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $response['success'] = true;
            $response['data'] = $stats;
            break;
            
        case 'create_shift_from_mission':
            // Créer un shift dans l'ERP depuis une mission CRM
            $mission_id = $_POST['mission_id'] ?? 0;
            $employee_id = $_POST['employee_id'] ?? 0;
            
            if (!$mission_id || !$employee_id) {
                throw new Exception('Mission ID et Employee ID requis');
            }
            
            // Récupérer la mission
            $stmt = $pdo->prepare("
                SELECT m.*, f.company_id
                FROM missions m
                INNER JOIN folders f ON m.folder_id = f.id
                WHERE m.id = ?
            ");
            $stmt->execute([$mission_id]);
            $mission = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$mission) {
                throw new Exception('Mission non trouvée');
            }
            
            // Créer le shift
            $stmt = $pdo->prepare("
                INSERT INTO erp_shifts (employee_id, company_id, start_time, end_time, notes)
                VALUES (?, ?, ?, DATE_ADD(?, INTERVAL 8 HOUR), ?)
            ");
            $notes = "Mission: " . $mission['departure'] . " -> " . $mission['arrival'];
            $stmt->execute([
                $employee_id,
                $mission['company_id'],
                $mission['datetime'],
                $mission['datetime'],
                $notes
            ]);
            
            $response['success'] = true;
            $response['shift_id'] = $pdo->lastInsertId();
            $response['message'] = 'Shift créé avec succès depuis la mission';
            break;
            
        default:
            throw new Exception('Action non reconnue');
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
