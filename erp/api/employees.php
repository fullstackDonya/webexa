<?php
/**
 * API Employés
 * Gestion des employés via AJAX
 */

require_once __DIR__ . '/../../crm/config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

$customer_id = $_SESSION['customer_id'] ?? null;

if (!$customer_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    switch ($action) {
        case 'fetch':
        case 'list':
            // Récupérer tous les employés du client
            $stmt = $pdo->prepare("
                SELECT id, first_name, last_name, email, phone, 
                       job_title, department, status
                FROM erp_employees 
                WHERE customer_id = ? 
                ORDER BY last_name, first_name
            ");
            $stmt->execute([$customer_id]);
            $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($employees);
            break;

        case 'create':
            // Créer un nouvel employé
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Méthode non autorisée');
            }

            $first_name = trim($_POST['first_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $job_title = trim($_POST['job_title'] ?? 'Employé');
            $department = trim($_POST['department'] ?? 'Général');
            $status = 'active';

            if (empty($first_name) || empty($last_name)) {
                throw new Exception('Le prénom et le nom sont requis');
            }

            $stmt = $pdo->prepare("
                INSERT INTO erp_employees (
                    first_name, last_name, email, phone, 
                    job_title, department, status, 
                    customer_id, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $first_name,
                $last_name,
                $email,
                $phone,
                $job_title,
                $department,
                $status,
                $customer_id
            ]);

            $newId = (int)$pdo->lastInsertId();

            echo json_encode([
                'success' => true,
                'id' => $newId,
                'message' => 'Employé créé avec succès'
            ]);
            break;

        case 'get':
            // Récupérer un employé par ID
            $id = (int)($_GET['id'] ?? 0);
            
            $stmt = $pdo->prepare("
                SELECT * FROM erp_employees 
                WHERE id = ? AND customer_id = ?
            ");
            $stmt->execute([$id, $customer_id]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$employee) {
                http_response_code(404);
                echo json_encode(['error' => 'Employé non trouvé']);
                exit;
            }

            echo json_encode($employee);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Action non reconnue']);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
