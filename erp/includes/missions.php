<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../crm/config/database.php';
include_once __DIR__ . '/../../crm/includes/auth.php';



$customer_id = $_SESSION['customer_id'] ?? 0;
$page_title = "Missions & Projets - ERP";

// Récupérer toutes les missions depuis le CRM
$stmt = $pdo->prepare("
    SELECT 
        m.id AS mission_id,
        m.departure,
        m.arrival,
        m.datetime,
        m.driver,
        m.vehicle,
        m.status_id,
        s.name AS status_name,
        m.created_at,
        f.id AS folder_id,
        f.name AS folder_name,
        c.id AS company_id,
        c.name AS company_name,
        m.notes
    FROM missions m
    INNER JOIN folders f ON m.folder_id = f.id
    INNER JOIN companies c ON f.company_id = c.id
    LEFT JOIN statuses s ON m.status_id = s.id
    WHERE c.customer_id = ?
    ORDER BY m.datetime DESC, m.created_at DESC
");
$stmt->execute([$customer_id]);
$missions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les shifts depuis l'ERP pour synchronisation
$stmt_shifts = $pdo->prepare("
    SELECT 
        sh.id,
        sh.employee_id,
        sh.company_id,
        sh.start_datetime,
        sh.end_datetime,
        sh.notes,
        CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
        c.name AS company_name
    FROM erp_shifts sh
    LEFT JOIN erp_employees e ON sh.employee_id = e.id
    LEFT JOIN companies c ON sh.company_id = c.id
    WHERE DATE(sh.start_datetime) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ORDER BY sh.start_datetime DESC
");
$stmt_shifts->execute();
$shifts = $stmt_shifts->fetchAll(PDO::FETCH_ASSOC);

// Calculer les KPIs
$total_missions = count($missions);
$missions_in_progress = count(array_filter($missions, function($m) { 
    return in_array($m['status_name'], ['En cours', 'À faire']); 
}));
$missions_completed = count(array_filter($missions, function($m) { 
    return $m['status_name'] === 'Terminée'; 
}));
$completion_rate = $total_missions > 0 ? round(($missions_completed / $total_missions) * 100) : 0;

// Récupérer les entreprises pour les filtres
$stmt_companies = $pdo->prepare("SELECT id, name FROM companies WHERE customer_id = ? ORDER BY name");
$stmt_companies->execute([$customer_id]);
$companies = $stmt_companies->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les employés pour les filtres
$stmt_employees = $pdo->prepare("SELECT id, first_name, last_name FROM erp_employees WHERE status = 'active' ORDER BY last_name");
$stmt_employees->execute();
$employees = $stmt_employees->fetchAll(PDO::FETCH_ASSOC);
