<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
session_start();

try {
    require_once __DIR__ . '/../config/database.php';
} catch (Throwable $e) {
    file_put_contents(__DIR__ . '/missions_error.log', "[DB include error] " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'error' => 'server_error']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'unauthenticated']);
    exit;
}

// Déterminer le customer_id
$customer_id = $_SESSION['customer_id'] ?? null;
if (empty($customer_id)) {
    try {
        $stmt = $pdo->prepare("SELECT customer_id FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $customer_id = $stmt->fetchColumn();
    } catch (Throwable $e) {
        file_put_contents(__DIR__ . '/missions_error.log', "[customer_id lookup error] " . $e->getMessage() . "\n", FILE_APPEND);
        echo json_encode(['success' => false, 'error' => 'server_error']);
        exit;
    }
}

try {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    if ($limit <= 0) $limit = 10;
    if ($limit > 100) $limit = 100; // sécurité

    if ($customer_id) {
        // ✅ Missions des folders appartenant à des companies liées à ce customer
        $sql = "
            SELECT
                m.id AS mission_id,
                CONCAT(COALESCE(m.departure,''),' → ',COALESCE(m.arrival,'')) AS title,
                COALESCE(s.name, m.status_id) AS status,
                DATE_FORMAT(m.datetime, '%Y-%m-%d %H:%i') AS datetime,
                m.driver,
                m.vehicle,
                u.id AS assignee_id,
                u.username AS assignee,
                f.id AS folder_id,
                c.id AS company_id,
                c.name AS company_name,
                m.created_at
            FROM missions m
            LEFT JOIN users u ON m.assigned_to = u.id
            LEFT JOIN statuses s ON m.status_id = s.id
            INNER JOIN folders f ON m.folder_id = f.id
            INNER JOIN companies c ON f.company_id = c.id
            WHERE c.customer_id = ?
            ORDER BY m.created_at DESC
            LIMIT $limit
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$customer_id]);
    } else {
        // 🟡 Si aucun customer_id : missions assignées à l'utilisateur
        $sql = "
            SELECT
                m.id AS mission_id,
                CONCAT(COALESCE(m.departure,''),' → ',COALESCE(m.arrival,'')) AS title,
                COALESCE(s.name, m.status_id) AS status,
                DATE_FORMAT(m.datetime, '%Y-%m-%d %H:%i') AS datetime,
                m.driver,
                m.vehicle,
                u.id AS assignee_id,
                u.username AS assignee,
                f.id AS folder_id,
                NULL AS company_id,
                NULL AS company_name,
                m.created_at
            FROM missions m
            LEFT JOIN users u ON m.assignee_id = u.id
            LEFT JOIN statuses s ON m.status_id = s.id
            LEFT JOIN folders f ON m.folder_id = f.id
            WHERE m.assignee_id = ?
            ORDER BY m.created_at DESC
            LIMIT $limit
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
    }

    $missions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'missions' => $missions]);
} catch (Throwable $e) {
    file_put_contents(__DIR__ . '/missions_error.log', "[SQL error] " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'error' => 'server_error', 'details' => $e->getMessage()]);
}
