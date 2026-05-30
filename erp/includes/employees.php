 <?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../crm/config/database.php';
include_once __DIR__ . '/../../crm/includes/auth.php';

// customer_id et user_id lus depuis la session (peuvent être null)
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$customer_id = $_SESSION['customer_id'] ?? null;

$action = $_GET['action'] ?? 'list';
$q = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;

// customer_id et user_id lus depuis la session (peuvent être null)
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$customer_id = $_SESSION['customer_id'] ?? null;

function fetchEmployees(PDO $pdo, string $q = '', int $limit = 50, int $offset = 0): array {
    global $customer_id;
    if (empty($customer_id)) {
        return [];
    }
    $params = [];
    $where = ["customer_id = :cid"];
    $params[':cid'] = $customer_id;

    if ($q !== '') {
        $where[] = "(CONCAT(last_name,' ',first_name) LIKE :q OR department LIKE :q)";
        $params[':q'] = "%$q%";
    }

    $sql = "SELECT * FROM erp_employees WHERE " . implode(' AND ', $where) . " ORDER BY last_name, first_name LIMIT :lim OFFSET :off";
    $stmt = $pdo->prepare($sql);

    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v, $k === ':cid' ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function fetchEmployee(PDO $pdo, int $id): ?array {
    global $customer_id;
    if (empty($customer_id)) {
        return null;
    }
    $stmt = $pdo->prepare("SELECT * FROM erp_employees WHERE id = ? AND customer_id = ?");
    $stmt->execute([$id, $customer_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function countEmployees(PDO $pdo, string $q = ''): int {
    global $customer_id;
    if (empty($customer_id)) {
        return 0;
    }
    if ($q !== '') {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM erp_employees WHERE (CONCAT(last_name,' ',first_name) LIKE :q OR department LIKE :q) AND customer_id = :cid");
        $stmt->execute([':q' => "%$q%", ':cid' => $customer_id]);
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM erp_employees WHERE customer_id = ?");
        $stmt->execute([$customer_id]);
    }
    return (int)$stmt->fetchColumn();
}


function saveEmployee(PDO $pdo, array $data, ?int $id = null): void {
    global $customer_id;
    if (empty($customer_id)) {
        throw new InvalidArgumentException('customer_id required');
    }

    if ($id) {
        $sql = "UPDATE erp_employees SET first_name=?, last_name=?, email=?, hire_date=?, base_salary=?, job_title=?, department=?, contract_type=?, status=?, customer_id=? WHERE id=? AND customer_id=?";
        $params = [
            $data['first_name'], $data['last_name'], $data['email'], $data['hire_date'] ?: null, $data['base_salary'],
            $data['job_title'], $data['department'], $data['contract_type'], $data['status'],
            $customer_id,
            $id,
            $customer_id,
        ];
    } else {
        $sql = "INSERT INTO erp_employees (first_name,last_name,email,hire_date,base_salary,job_title,department,contract_type,status,customer_id,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,NOW())";
        $params = [
            $data['first_name'], $data['last_name'], $data['email'], $data['hire_date'] ?: null, $data['base_salary'],
            $data['job_title'], $data['department'], $data['contract_type'], $data['status'],
            $customer_id
        ];
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
}

function deleteEmployee(PDO $pdo, int $id): void {
    global $customer_id;
    if (empty($customer_id)) {
        throw new InvalidArgumentException('customer_id required');
    }
    $stmt = $pdo->prepare("DELETE FROM erp_employees WHERE id=? AND customer_id=?");
    $stmt->execute([$id, $customer_id]);
}

/* POST actions: create/update/delete */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['_method'] ?? '') === 'DELETE') {
        deleteEmployee($pdo, (int)$_POST['id']);
        header('Location: employees.php');
        exit;
    }

    $payload = [
        'first_name' => trim((string)($_POST['first_name'] ?? '')),
        'last_name' => trim((string)($_POST['last_name'] ?? '')),
        'email' => trim((string)($_POST['email'] ?? '')),
        'hire_date' => $_POST['hire_date'] ?? null,
        'base_salary' => isset($_POST['base_salary']) ? (float)$_POST['base_salary'] : 0.0,
        'job_title' => trim((string)($_POST['job_title'] ?? '')),
        'department' => trim((string)($_POST['department'] ?? '')),
        'contract_type' => $_POST['contract_type'] ?? 'CDI',
        'status' => $_POST['status'] ?? 'active',
    ];
    $id = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
    saveEmployee($pdo, $payload, $id);
    header('Location: employees.php');
    exit;
}

/* Export CSV */
if ($action === 'export') {
    $all = fetchEmployees($pdo, $q, 10000, 0);
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="employees.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['id','first_name','last_name','email','hire_date','base_salary','job_title','department','contract_type','status']);
    foreach ($all as $r) {
        fputcsv($out, [
            $r['id'],$r['first_name'],$r['last_name'],$r['email'],$r['hire_date'],$r['base_salary'],$r['job_title'],$r['department'],$r['contract_type'],$r['status']
        ]);
    }
    fclose($out);
    exit;
}

/* Listing / edit */
$employees = [];
$current = null;
if ($action === 'edit') {
    $current = fetchEmployee($pdo, (int)($_GET['id'] ?? 0));
} elseif ($action === 'add') {
    $current = null;
} else {
    $total = countEmployees($pdo, $q);
    $offset = ($page - 1) * $perPage;
    $employees = fetchEmployees($pdo, $q, $perPage, $offset);
    $totalPages = (int)ceil($total / $perPage);
}