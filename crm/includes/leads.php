<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/verify_subscriptions.php';

$customer_id = $_SESSION['customer_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? ($user['id'] ?? null);

// Ensure minimal leads support using contacts table with status='lead'
$leads_kpis = [
    'new_leads' => 0,
    'qualified' => 0,
    'conversion_rate' => '0%',
    'avg_score' => '--',
];
$leads_list = [];

try {
    // New leads in last 30 days
    if ($customer_id) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE (customer_id = ? OR assigned_to = ?) AND created_at >= (NOW() - INTERVAL 30 DAY)");
        $stmt->execute([$customer_id, $user_id]);
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) FROM leads WHERE created_at >= (NOW() - INTERVAL 30 DAY)");
    }
    $leads_kpis['new_leads'] = (int)$stmt->fetchColumn();
} catch (Throwable $e) {}

try {
    if ($customer_id) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE (customer_id = ? OR assigned_to = ?) AND stage = 'qualified'");
        $stmt->execute([$customer_id, $user_id]);
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) FROM leads WHERE stage = 'qualified'");
    }
    $leads_kpis['qualified'] = (int)$stmt->fetchColumn();
} catch (Throwable $e) {}

try {
    // Conversion rate approximation: qualified / total leads
    if ($customer_id) {
        $tstmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE (customer_id = ? OR assigned_to = ?)");
        $tstmt->execute([$customer_id, $user_id]);
        $total = (int)$tstmt->fetchColumn();
        $q = (int)$leads_kpis['qualified'];
    } else {
        $total = (int)$pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn();
        $q = (int)$leads_kpis['qualified'];
    }
    $rate = $total > 0 ? round(100 * $q / $total, 1) : 0;
    $leads_kpis['conversion_rate'] = $rate . '%';
} catch (Throwable $e) {}

try {
    // Avg AI score if column exists
    $avg = null;
    try {
        if ($customer_id) {
            $s = $pdo->prepare("SELECT AVG(ai_score) FROM leads WHERE (customer_id = ? OR assigned_to = ?) AND ai_score IS NOT NULL");
            $s->execute([$customer_id, $user_id]);
        } else {
            $s = $pdo->query("SELECT AVG(ai_score) FROM leads WHERE ai_score IS NOT NULL");
        }
        $avg = $s->fetchColumn();
    } catch (Throwable $ignore) { $avg = null; }
    if ($avg !== null) { $leads_kpis['avg_score'] = (string)round((float)$avg, 1); }
} catch (Throwable $e) {}

try {
    // List leads joined with company name if available
    if ($customer_id) {
        $sql = "SELECT c.id, c.first_name, c.last_name, c.email, c.phone, c.stage, c.source, c.tags, c.created_at, c.ai_score, c.company_id, co.name AS company_name
                FROM leads c
                LEFT JOIN companies co ON c.company_id = co.id
                WHERE (c.customer_id = ? OR c.assigned_to = ?)
                ORDER BY c.created_at DESC LIMIT 200";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$customer_id, $user_id]);
    } else {
        $sql = "SELECT c.id, c.first_name, c.last_name, c.email, c.phone, c.stage, c.source, c.tags, c.created_at, c.ai_score, c.company_id, co.name AS company_name
                FROM leads c
                LEFT JOIN companies co ON c.company_id = co.id
                ORDER BY c.created_at DESC LIMIT 200";
        $stmt = $pdo->query($sql);
    }
    $leads_list = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $leads_list = [];
}

// IDs des leads déjà convertis en opportunité
$lead_opportunity_ids = [];
try {
    if ($customer_id) {
        $s = $pdo->prepare("SELECT DISTINCT lead_id FROM opportunities WHERE lead_id IS NOT NULL AND customer_id = ?");
        $s->execute([$customer_id]);
    } else {
        $s = $pdo->query("SELECT DISTINCT lead_id FROM opportunities WHERE lead_id IS NOT NULL");
    }
    $lead_opportunity_ids = array_column($s->fetchAll(PDO::FETCH_ASSOC), 'lead_id');
} catch (Throwable $e) {
    $lead_opportunity_ids = [];
}