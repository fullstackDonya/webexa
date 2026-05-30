<?php

require_once __DIR__ . '/../../crm/config/database.php';
include_once __DIR__ . '/../../crm/includes/auth.php';

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$customer_id = $_SESSION['customer_id'] ?? null;

$action = $_GET['action'] ?? 'list';
$period = trim((string)($_GET['period'] ?? '')); // format attendu YYYY-MM

function validatePeriod(string $p): bool {
    return $p === '' || preg_match('/^\d{4}-\d{2}$/', $p) === 1;
}

/* Récupère les fiches en filtrant par période et par customer_id si défini */
function fetchPayrolls(PDO $pdo, string $period = ''): array {
    global $customer_id;
    if (empty($customer_id)) {
        return [];
    }
    $params = [$customer_id];
    $sql = "SELECT p.*, e.first_name, e.last_name, e.email FROM erp_payrolls p JOIN erp_employees e ON e.id = p.employee_id WHERE p.customer_id = ?";
    if ($period !== '') {
        $sql .= " AND p.period = ?";
        $params[] = $period;
    }
    $sql .= " ORDER BY e.last_name, e.first_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* Export des employés — apply customer_id filter si présent */
function fetchEmployeesForExport(PDO $pdo): array {
    global $customer_id;
    if (empty($customer_id)) {
        return [];
    }
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, hire_date, base_salary, job_title, department, contract_type, status FROM erp_employees WHERE customer_id = ? ORDER BY last_name, first_name");
    $stmt->execute([$customer_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* Résumé (nombre / coût total) filtré par période et customer_id si défini */
function summaryForPeriod(PDO $pdo, string $period = ''): array {
    global $customer_id;
    if (empty($customer_id)) {
        return ['cnt' => 0, 'total_cost' => 0];
    }
    if ($period === '') {
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt, COALESCE(SUM(net_pay + employer_contrib),0) as total_cost FROM erp_payrolls WHERE customer_id = ?");
        $stmt->execute([$customer_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt, COALESCE(SUM(net_pay + employer_contrib),0) as total_cost FROM erp_payrolls WHERE period = ? AND customer_id = ?");
    $stmt->execute([$period, $customer_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/* Exports */
if ($action === 'export_payrolls') {
    if (!validatePeriod($period)) { http_response_code(400); echo 'Période invalide'; exit; }
    $rows = fetchPayrolls($pdo, $period);
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="payrolls' . ($period ? "_$period" : '') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['payroll_id','employee_id','last_name','first_name','email','period','gross_salary','bonus','overtime','deductions','employee_contrib','employer_contrib','net_pay','created_at','customer_id']);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['id'],$r['employee_id'],$r['last_name'],$r['first_name'],$r['email'],$r['period'],
            $r['gross_salary'],$r['bonus'],$r['overtime'],$r['deductions'],$r['employee_contrib'],$r['employer_contrib'],$r['net_pay'],$r['created_at'],
            $r['customer_id'] ?? ''
        ]);
    }
    fclose($out);
    exit;
}

if ($action === 'export_employees') {
    $rows = fetchEmployeesForExport($pdo);
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="employees' . ($customer_id ? "_c{$customer_id}" : '') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['id','first_name','last_name','email','hire_date','base_salary','job_title','department','contract_type','status']);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['id'],$r['first_name'],$r['last_name'],$r['email'],$r['hire_date'],$r['base_salary'],$r['job_title'],$r['department'],$r['contract_type'],$r['status']
        ]);
    }
    fclose($out);
    exit;
}

/* Page affichage : liste + résumé */
if (!validatePeriod($period)) { $period = ''; }

$payrolls = fetchPayrolls($pdo, $period);
$summary = summaryForPeriod($pdo, $period);