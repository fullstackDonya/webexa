<?php

header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../config/database.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'unauthenticated']);
    exit;
}

$customer_id = $_SESSION['customer_id'] ?? null;

try {
    if ($customer_id) {
        $sql = "
            SELECT o.id, o.title, o.amount, o.stage, o.company_id, c.name AS company_name, o.created_at
            FROM opportunities o
            LEFT JOIN companies c ON o.company_id = c.id
            WHERE (o.customer_id = ? OR c.customer_id = ?)
            ORDER BY FIELD(o.stage, 'prospecting','qualification','needs_analysis','proposal','negotiation','closed_won','closed_lost'), o.created_at DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$customer_id, $customer_id]);
    } else {
        $sql = "
            SELECT o.id, o.title, o.amount, o.stage, o.company_id, c.name AS company_name, o.created_at
            FROM opportunities o
            LEFT JOIN companies c ON o.company_id = c.id
            WHERE o.assigned_to = ?
            ORDER BY FIELD(o.stage, 'prospecting','qualification','needs_analysis','proposal','negotiation','closed_won','closed_lost'), o.created_at DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
    }
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stages = [
        'prospecting' => [],
        'qualification' => [],
        'needs_analysis' => [],
        'proposal' => [],
        'negotiation' => [],
        'closed_won' => [],
        'closed_lost' => []
    ];

    foreach ($rows as $r) {
        $key = $r['stage'] ?? 'prospecting';
        if (!isset($stages[$key])) $key = 'prospecting';
        $stages[$key][] = $r;
    }

    // counts & sums
    $meta = [];
    foreach ($stages as $k => $list) {
        $count = count($list);
        $sum = 0;
        foreach ($list as $it) $sum += floatval($it['amount']);
        $meta[$k] = ['count' => $count, 'sum' => $sum];
    }

    echo json_encode(['success' => true, 'stages' => $stages, 'meta' => $meta]);
} catch (Exception $e) {
    error_log('opportunities-list error: '.$e->getMessage());
    echo json_encode(['success' => false, 'error' => 'server_error']);
}