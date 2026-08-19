<?php
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'tasks' => [], 'calls' => []]);
    exit;
}

$customer_id = (int)$_SESSION['customer_id'];
$tasks = [];
$calls = [];
$missions = [];
$shifts = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    if (($payload['action'] ?? '') === 'complete_mission') {
        $mission_id = (int)($payload['id'] ?? 0);
        if ($mission_id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Mission invalide']);
            exit;
        }

        try {
            $s = $pdo->prepare("UPDATE missions m
                INNER JOIN folders f ON f.id = m.folder_id
                INNER JOIN companies c ON c.id = f.company_id
                LEFT JOIN statuses st ON st.name = 'Terminée'
                SET m.status_id = st.id
                WHERE m.id = ? AND c.customer_id = ?");
            $s->execute([$mission_id, $customer_id]);
            if ($s->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Mission introuvable ou statut Terminée absent']);
                exit;
            }
            echo json_encode(['success' => true, 'message' => 'Mission marquée comme terminée']);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Suppression impossible']);
        }
        exit;
    }
}

try {
    $s = $pdo->prepare("
        SELECT id, title, due_date, priority
        FROM tasks
        WHERE customer_id = ? AND DATE(due_date) = CURDATE()
          AND status NOT IN ('completed','cancelled')
        ORDER BY due_date ASC LIMIT 20
    ");
    $s->execute([$customer_id]);
    $tasks = $s->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

try {
    $s = $pdo->prepare("
        SELECT id, contact_name, phone, scheduled_time, call_type
        FROM call_reminders
        WHERE customer_id = ? AND DATE(scheduled_time) = CURDATE()
          AND status = 'pending'
        ORDER BY scheduled_time ASC LIMIT 20
    ");
    $s->execute([$customer_id]);
    $calls = $s->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

try {
    $s = $pdo->prepare("
        SELECT m.id, m.datetime, m.departure, m.arrival,
               f.id AS folder_id, f.name AS folder_name,
               c.name AS company_name
        FROM missions m
        INNER JOIN folders f ON f.id = m.folder_id
        INNER JOIN companies c ON c.id = f.company_id
        WHERE c.customer_id = ?
          AND DATE(m.datetime) = CURDATE()
        ORDER BY m.datetime ASC
        LIMIT 20
    ");
    $s->execute([$customer_id]);
    $missions = $s->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

try {
    $s = $pdo->prepare("
        SELECT s.id, s.start_datetime, s.end_datetime, s.role, s.notes,
               CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
               c.name AS company_name
        FROM erp_shifts s
        LEFT JOIN erp_employees e ON e.id = s.employee_id
        LEFT JOIN companies c ON c.id = s.company_id
        WHERE s.customer_id = ?
          AND DATE(s.start_datetime) = CURDATE()
        ORDER BY s.start_datetime ASC
        LIMIT 20
    ");
    $s->execute([$customer_id]);
    $shifts = $s->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

echo json_encode([
    'success' => true,
    'tasks' => $tasks,
    'calls' => $calls,
    'missions' => $missions,
    'shifts' => $shifts,
]);
