<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/permission-bootstrap.php';

$response = [
    'success' => false,
];

try {
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        echo json_encode(['success' => false, 'error' => 'unauthenticated']);
        exit;
    }

    // customer filter (from session or query param)
    $sessionCustomerId = $_SESSION['customer_id'] ?? null;
    $customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : $sessionCustomerId;

    // Build WHERE conditions
    $where = ["c.status IN ('lead','qualified')"]; // consider leads as contacts with these statuses
    $params = [];

    // If customer filter available, limit to companies of that customer or contacts directly linked
    if ($customer_id) {
        $where[] = '(co.customer_id = :customer_id OR co.customer_id IS NULL)';
        $params[':customer_id'] = $customer_id;
    }

    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    // Distribution and category counts
    $sqlDist = "
        SELECT
            SUM(CASE WHEN c.ai_score BETWEEN 0 AND 20 THEN 1 ELSE 0 END) AS c0_20,
            SUM(CASE WHEN c.ai_score BETWEEN 21 AND 40 THEN 1 ELSE 0 END) AS c21_40,
            SUM(CASE WHEN c.ai_score BETWEEN 41 AND 60 THEN 1 ELSE 0 END) AS c41_60,
            SUM(CASE WHEN c.ai_score BETWEEN 61 AND 80 THEN 1 ELSE 0 END) AS c61_80,
            SUM(CASE WHEN c.ai_score BETWEEN 81 AND 100 THEN 1 ELSE 0 END) AS c81_100,

            SUM(CASE WHEN c.ai_score >= 81 THEN 1 ELSE 0 END) AS hot,
            SUM(CASE WHEN c.ai_score BETWEEN 60 AND 80 THEN 1 ELSE 0 END) AS warm,
            SUM(CASE WHEN c.ai_score BETWEEN 40 AND 59 THEN 1 ELSE 0 END) AS cold,
            SUM(CASE WHEN c.ai_score < 40 OR c.ai_score IS NULL THEN 1 ELSE 0 END) AS unqualified,
            COUNT(*) AS total
        FROM leads c
        LEFT JOIN companies co ON c.company_id = co.id
        $whereSql
    ";

    $stmt = $pdo->prepare($sqlDist);
    foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
    $stmt->execute();
    $dist = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    // Compute a naive model accuracy: among HOT leads, share that are qualified
    $sqlAccuracy = "
        SELECT
            SUM(CASE WHEN c.ai_score >= 81 AND c.status = 'qualified' THEN 1 ELSE 0 END) AS qualified_hot,
            SUM(CASE WHEN c.ai_score >= 81 THEN 1 ELSE 0 END) AS hot
        FROM leads c
        LEFT JOIN companies co ON c.company_id = co.id
        $whereSql
    ";
    $sa = $pdo->prepare($sqlAccuracy);
    foreach ($params as $k => $v) { $sa->bindValue($k, $v); }
    $sa->execute();
    $accRow = $sa->fetch(PDO::FETCH_ASSOC) ?: ['qualified_hot' => 0, 'hot' => 0];
    $model_accuracy = 0;
    if ((int)($accRow['hot'] ?? 0) > 0) {
        $model_accuracy = round(100 * ((int)($accRow['qualified_hot'] ?? 0)) / (int)$accRow['hot']);
    }

    // last update = max(last_activity) if available, else max(created_at)
    $sqlLast = "
        SELECT 
            COALESCE(
                DATE_FORMAT(MAX(c.last_activity), '%Y-%m-%d %H:%i:%s'),
                DATE_FORMAT(MAX(c.updated_at), '%Y-%m-%d %H:%i:%s'),
                DATE_FORMAT(MAX(c.created_at), '%Y-%m-%d %H:%i:%s')
            ) AS last_update
        FROM leads c
        LEFT JOIN companies co ON c.company_id = co.id
        $whereSql
    ";
    $sl = $pdo->prepare($sqlLast);
    foreach ($params as $k => $v) { $sl->bindValue($k, $v); }
    $sl->execute();
    $last_update = ($sl->fetch(PDO::FETCH_ASSOC)['last_update'] ?? null);

    // Leads list (limited)
    $sqlLeads = "
        SELECT 
            c.id,
            c.first_name,
            c.last_name,
            c.email,
            c.status,
            c.source,
            c.created_at,
            c.last_activity,
            c.ai_score,
            co.name AS company_name
        FROM leads c
        LEFT JOIN companies co ON c.company_id = co.id
        $whereSql
        ORDER BY c.ai_score DESC, c.created_at DESC
        LIMIT 200
    ";
    $ls = $pdo->prepare($sqlLeads);
    foreach ($params as $k => $v) { $ls->bindValue($k, $v); }
    $ls->execute();
    $leads = $ls->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Normalize data and add computed category
    foreach ($leads as &$lead) {
        $score = isset($lead['ai_score']) ? (int)$lead['ai_score'] : null;
        $lead['ai_category'] = null;
        if ($score !== null) {
            if ($score >= 81) $lead['ai_category'] = 'Chaud';
            elseif ($score >= 60) $lead['ai_category'] = 'Tiède';
            elseif ($score >= 40) $lead['ai_category'] = 'Froid';
            else $lead['ai_category'] = 'Non-qualifié';
        }
    }

    $response['success'] = true;
    $response['metrics'] = [
        'hot' => (int)($dist['hot'] ?? 0),
        'warm' => (int)($dist['warm'] ?? 0),
        'cold' => (int)($dist['cold'] ?? 0),
        'unqualified' => (int)($dist['unqualified'] ?? 0),
        'total' => (int)($dist['total'] ?? 0),
        'distribution' => [
            (int)($dist['c0_20'] ?? 0),
            (int)($dist['c21_40'] ?? 0),
            (int)($dist['c41_60'] ?? 0),
            (int)($dist['c61_80'] ?? 0),
            (int)($dist['c81_100'] ?? 0),
        ],
        'model_accuracy' => $model_accuracy,
        'last_update' => $last_update,
    ];
    $response['leads'] = $leads;

    echo json_encode($response);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'server_error',
        'message' => $e->getMessage(),
    ]);
}