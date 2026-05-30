<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../crm/config/database.php';
include_once __DIR__ . '/../../crm/includes/auth.php';


$action = $_GET['action'] ?? 'list';

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$customer_id = $_SESSION['customer_id'] ?? null;

function fetchCompanies(PDO $pdo): array {
    global $customer_id;
    if (empty($customer_id)) {
        return [];
    }
    $stmt = $pdo->prepare("SELECT * FROM companies WHERE customer_id = ? AND interne_customer = false ORDER BY name");
    $stmt->execute([$customer_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchCompany(PDO $pdo, int $id): ?array {
    global $customer_id;
    if (empty($customer_id)) {
        return null;
    }
    $stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ? AND customer_id = ? AND interne_customer = false");
    $stmt->execute([$id, $customer_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function saveCompany(PDO $pdo, array $data, ?int $id = null): void {
    global $customer_id;
    if (empty($customer_id)) {
        throw new InvalidArgumentException('customer_id required');
    }

    if ($id) {
        $sql = "UPDATE companies SET name=?, siret=?, naf=?, address_line1=?, address_line2=?, phone=?, email=?, notes=?, customer_id=? WHERE id=? AND customer_id=? AND interne_customer = false";
        $params = [
            $data['name'], $data['siret'], $data['naf'], $data['address_line1'], $data['address_line2'], $data['phone'], $data['email'], $data['notes'],
            $customer_id,
            $id,
            $customer_id,
        ];
    } else {
        $sql = "INSERT INTO companies (name, siret, naf, address_line1, address_line2, phone, email, notes, customer_id, interne_customer, created_at) VALUES (?,?,?,?,?,?,?,?,?, false, NOW())";
        $params = [
            $data['name'], $data['siret'], $data['naf'], $data['address_line1'], $data['address_line2'], $data['phone'], $data['email'], $data['notes'],
            $customer_id
        ];
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
}

function deleteCompany(PDO $pdo, int $id): void {
    global $customer_id;
    if (empty($customer_id)) {
        throw new InvalidArgumentException('customer_id required');
    }
    $stmt = $pdo->prepare("DELETE FROM companies WHERE id = ? AND customer_id = ? AND interne_customer = false");
    $stmt->execute([$id, $customer_id]);
}

/* POST handling: create / update / delete */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['_method'] ?? '') === 'DELETE') {
        deleteCompany($pdo, (int)($_POST['id'] ?? 0));
        header('Location: companies.php');
        exit;
    }

    $payload = [
        'name' => trim((string)($_POST['name'] ?? '')),
        'siret' => trim((string)($_POST['siret'] ?? '')),
        'naf' => trim((string)($_POST['naf'] ?? '')),
        'address_line1' => trim((string)($_POST['address_line1'] ?? '')),
        'address_line2' => trim((string)($_POST['address_line2'] ?? '')),
        'phone' => trim((string)($_POST['phone'] ?? '')),
        'email' => trim((string)($_POST['email'] ?? '')),
        'notes' => trim((string)($_POST['notes'] ?? '')),
    ];

    $id = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
    saveCompany($pdo, $payload, $id);
    header('Location: companies.php');
    exit;
}

/* CSV export */
if ($action === 'export') {
    $rows = fetchCompanies($pdo);
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="companies.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['id','name','siret','naf','address_line1','address_line2','phone','email','notes','created_at']);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['id'],$r['name'],$r['siret'],$r['naf'],$r['address_line1'],$r['address_line2'],$r['phone'],$r['email'],$r['notes'],$r['created_at'] ?? ''
        ]);
    }
    fclose($out);
    exit;
}

/* Page data */
$companies = [];
$current = null;
if ($action === 'edit') {
    $current = fetchCompany($pdo, (int)($_GET['id'] ?? 0));
} else {
    $companies = fetchCompanies($pdo);
}