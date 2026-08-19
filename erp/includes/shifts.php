<?php

require_once __DIR__ . '/../../crm/config/database.php';
include_once __DIR__ . '/../../crm/includes/auth.php';
requireFeature('planning');

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$customer_id = $_SESSION['customer_id'] ?? null;

/* --- API & DB helpers --- */
function fetchShiftsInRange(PDO $pdo, string $start, string $end, ?int $companyId = null, ?int $employeeId = null): array {
    global $customer_id;
    // si l'utilisateur n'a pas de customer_id en session, ne rien renvoyer
    if (empty($customer_id)) {
        return [];
    }

    // start, end, customer_id obligatoires dans les paramètres
    $params = [$start, $end, $customer_id];
    $sql = "SELECT s.*, e.first_name, e.last_name, c.name AS company_name
            FROM erp_shifts s
            LEFT JOIN erp_employees e ON e.id = s.employee_id
            LEFT JOIN companies c ON c.id = s.company_id
            WHERE s.start_datetime >= ? AND s.end_datetime <= ? AND s.customer_id = ?";

    if ($companyId !== null) {
        $sql .= " AND s.company_id = ?";
        $params[] = $companyId;
    }
    if ($employeeId !== null) {
        $sql .= " AND s.employee_id = ?";
        $params[] = $employeeId;
    }
    $sql .= " ORDER BY s.start_datetime";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


function createShift(PDO $pdo, array $data): int {
    global $customer_id;
    // allow employee_id nullable (company-only shift)
    $employee = isset($data['employee_id']) && $data['employee_id'] > 0 ? $data['employee_id'] : null;
    $company = isset($data['company_id']) && $data['company_id'] > 0 ? $data['company_id'] : null;
    if (!$employee && !$company) {
        throw new InvalidArgumentException('employee_id or company_id required');
    }
    $sql = "INSERT INTO erp_shifts (employee_id, start_datetime, end_datetime, role, notes, company_id, customer_id, created_at)
            VALUES (?,?,?,?,?,?,?,NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $employee,
        $data['start_datetime'],
        $data['end_datetime'],
        $data['role'] ?? null,
        $data['notes'] ?? null,
        $company,
        $customer_id,
    ]);
    return (int)$pdo->lastInsertId();
}

function updateShift(PDO $pdo, int $id, array $data): bool {
    global $customer_id;
    $employee = isset($data['employee_id']) && $data['employee_id'] > 0 ? $data['employee_id'] : null;
    $company = isset($data['company_id']) && $data['company_id'] > 0 ? $data['company_id'] : null;
    if (!$employee && !$company) {
        throw new InvalidArgumentException('employee_id or company_id required');
    }
    if ($customer_id) {
        $stmt = $pdo->prepare("UPDATE erp_shifts SET employee_id=?, start_datetime=?, end_datetime=?, role=?, notes=?, company_id=? WHERE id=? AND customer_id=?");
        return $stmt->execute([$employee, $data['start_datetime'], $data['end_datetime'], $data['role'] ?? null, $data['notes'] ?? null, $company, $id, $customer_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE erp_shifts SET employee_id=?, start_datetime=?, end_datetime=?, role=?, notes=?, company_id=? WHERE id=?");
        return $stmt->execute([$employee, $data['start_datetime'], $data['end_datetime'], $data['role'] ?? null, $data['notes'] ?? null, $company, $id]);
    }
}

function deleteShift(PDO $pdo, int $id): bool {
    global $customer_id;
    if ($customer_id) {
        $stmt = $pdo->prepare("DELETE FROM erp_shifts WHERE id=? AND customer_id=?");
        return $stmt->execute([$id, $customer_id]);
    } else {
        $stmt = $pdo->prepare("DELETE FROM erp_shifts WHERE id=?");
        return $stmt->execute([$id]);
    }
}

/* --- API routing --- */
$action = $_REQUEST['action'] ?? 'view';
if ($action === 'fetch' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $start = $_GET['start'] ?? null;
    $end = $_GET['end'] ?? null;
    $companyId = isset($_GET['company_id']) && $_GET['company_id'] !== '' ? (int)$_GET['company_id'] : null;
    $employeeId = isset($_GET['employee_id']) && $_GET['employee_id'] !== '' ? (int)$_GET['employee_id'] : null;
    if (!$start || !$end) { http_response_code(400); echo json_encode(['error' => 'start and end required']); exit; }
    
    // Récupérer les shifts
    $shifts = fetchShiftsInRange($pdo, $start, $end, $companyId, $employeeId);
    
    // Récupérer les missions dans la même plage de dates
    $missionsInRange = [];
    if ($customer_id) {
        $params = [$customer_id, $start.' 00:00:00', $end.' 23:59:59'];
        $sql = "SELECT 
            m.id AS mission_id,
            m.departure,
            m.arrival,
            m.datetime,
            m.driver,
            m.vehicle,
            m.status_id,
            s.name AS status_name,
            f.id AS folder_id,
            f.name AS folder_name,
            c.id AS company_id,
            c.name AS company_name,
            m.notes
        FROM missions m
        INNER JOIN folders f ON m.folder_id = f.id
        INNER JOIN companies c ON f.company_id = c.id
        LEFT JOIN statuses s ON m.status_id = s.id
        WHERE c.customer_id = ? AND c.interne_customer = false
        AND m.datetime >= ? AND m.datetime <= ?";
        
        if ($companyId !== null) {
            $sql .= " AND c.id = ?";
            $params[] = $companyId;
        }
        
        $sql .= " ORDER BY m.datetime";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $missionsInRange = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['shifts' => $shifts, 'missions' => $missionsInRange]);
    exit;
}
if ($action === 'stats' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $start = $_GET['start'] ?? null;
    $end = $_GET['end'] ?? null;
    if (!$start || !$end || !$customer_id) {
        http_response_code(400);
        echo json_encode(['error' => 'start, end and customer_id required']);
        exit;
    }

    $monthStart = date('Y-m-01');
    $monthEnd = date('Y-m-t');
    $params = [$customer_id, $start . ' 00:00:00', $end . ' 23:59:59'];
    $monthParams = [$customer_id, $monthStart . ' 00:00:00', $monthEnd . ' 23:59:59'];

    $shiftSql = "SELECT DATE(start_datetime) AS day, COUNT(*) AS total
                 FROM erp_shifts
                 WHERE customer_id = ? AND start_datetime BETWEEN ? AND ?
                 GROUP BY DATE(start_datetime)";
    $stmt = $pdo->prepare($shiftSql);
    $stmt->execute($params);
    $shiftDays = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $missionSql = "SELECT DATE(m.datetime) AS day, COUNT(*) AS total
                   FROM missions m
                   INNER JOIN folders f ON f.id = m.folder_id
                   INNER JOIN companies c ON c.id = f.company_id
                   WHERE c.customer_id = ? AND c.interne_customer = false
                     AND m.datetime BETWEEN ? AND ?
                   GROUP BY DATE(m.datetime)";
    $stmt = $pdo->prepare($missionSql);
    $stmt->execute($params);
    $missionDays = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM erp_shifts WHERE customer_id = ? AND start_datetime BETWEEN ? AND ?");
    $stmt->execute($monthParams);
    $monthShifts = (int)$stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM missions m
        INNER JOIN folders f ON f.id = m.folder_id
        INNER JOIN companies c ON c.id = f.company_id
        WHERE c.customer_id = ? AND c.interne_customer = false AND m.datetime BETWEEN ? AND ?");
    $stmt->execute($monthParams);
    $monthMissions = (int)$stmt->fetchColumn();

    $weekDays = [];
    foreach (array_unique(array_merge(array_keys($shiftDays), array_keys($missionDays))) as $day) {
        $weekDays[$day] = [
            'shifts' => (int)($shiftDays[$day] ?? 0),
            'missions' => (int)($missionDays[$day] ?? 0),
        ];
    }

    echo json_encode([
        'week' => [
            'shifts' => array_sum(array_map('intval', $shiftDays)),
            'missions' => array_sum(array_map('intval', $missionDays)),
            'days' => $weekDays
        ],
        'month' => ['shifts' => $monthShifts, 'missions' => $monthMissions]
    ]);
    exit;
}
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'employee_id' => isset($_POST['employee_id']) ? (int)$_POST['employee_id'] : 0,
        'start_datetime' => $_POST['start_datetime'] ?? '',
        'end_datetime' => $_POST['end_datetime'] ?? '',
        'role' => trim((string)($_POST['role'] ?? '')),
        'notes' => trim((string)($_POST['notes'] ?? '')),
        'company_id' => isset($_POST['company_id']) && $_POST['company_id'] !== '' ? (int)$_POST['company_id'] : null,
    ];
    // validation: allow employee nullable if company provided
    $employee_ok = $data['employee_id'] > 0;
    $company_ok = !empty($data['company_id']);
    if ((!$employee_ok && !$company_ok) || !$data['start_datetime'] || !$data['end_datetime']) {
        http_response_code(400); echo json_encode(['error'=>'Invalid data: employee or company required, start & end required']); exit;
    }
    try {
        $id = createShift($pdo, $data);
    } catch (Exception $e) {
        http_response_code(400); echo json_encode(['error' => $e->getMessage()]); exit;
    }
    header('Content-Type: application/json; charset=utf-8'); echo json_encode(['id' => $id]); exit;
}
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $data = [
        'employee_id' => isset($_POST['employee_id']) ? (int)$_POST['employee_id'] : 0,
        'start_datetime' => $_POST['start_datetime'] ?? '',
        'end_datetime' => $_POST['end_datetime'] ?? '',
        'role' => trim((string)($_POST['role'] ?? '')),
        'notes' => trim((string)($_POST['notes'] ?? '')),
        'company_id' => isset($_POST['company_id']) && $_POST['company_id'] !== '' ? (int)$_POST['company_id'] : null,
    ];
    $employee_ok = $data['employee_id'] > 0;
    $company_ok = !empty($data['company_id']);
    if ($id <= 0 || (!$employee_ok && !$company_ok)) { http_response_code(400); echo json_encode(['error'=>'Invalid']); exit; }
    try {
        $ok = updateShift($pdo, $id, $data);
    } catch (Exception $e) {
        http_response_code(400); echo json_encode(['error' => $e->getMessage()]); exit;
    }
    header('Content-Type: application/json; charset=utf-8'); echo json_encode(['ok' => (bool)$ok]); exit;
}
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { http_response_code(400); echo json_encode(['error'=>'Invalid id']); exit; }
    $ok = deleteShift($pdo, $id);
    header('Content-Type: application/json; charset=utf-8'); echo json_encode(['ok' => (bool)$ok]); exit;
}

/* --- Prepare data for view --- */
// Récupérer les employés filtrés par customer_id
$employeesStmt = $pdo->prepare("SELECT id, first_name, last_name FROM erp_employees WHERE customer_id = ? AND status = 'active' ORDER BY last_name");
$employeesStmt->execute([$customer_id]);
$employees = $employeesStmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les companies filtrées par customer_id et interne_customer = false (comme dans le CRM)
$companiesStmt = $pdo->prepare("SELECT id, name FROM companies WHERE customer_id = ? AND interne_customer = false ORDER BY name");
$companiesStmt->execute([$customer_id]);
$companies = $companiesStmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les missions depuis le CRM pour affichage dans le planning
$missionsStmt = $pdo->prepare("
    SELECT 
        m.id AS mission_id,
        m.departure,
        m.arrival,
        m.datetime,
        m.driver,
        m.vehicle,
        m.status_id,
        s.name AS status_name,
        f.id AS folder_id,
        f.name AS folder_name,
        c.id AS company_id,
        c.name AS company_name,
        m.notes
    FROM missions m
    INNER JOIN folders f ON m.folder_id = f.id
    INNER JOIN companies c ON f.company_id = c.id
    LEFT JOIN statuses s ON m.status_id = s.id
    WHERE c.customer_id = ? AND c.interne_customer = false
    ORDER BY m.datetime DESC
");
$missionsStmt->execute([$customer_id]);
$missions = $missionsStmt->fetchAll(PDO::FETCH_ASSOC);