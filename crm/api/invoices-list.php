<?php
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

// Récupération du customer_id
$customer_id = $_SESSION['customer_id'] ?? null;
if (empty($customer_id)) {
    try {
        $stmt = $pdo->prepare("SELECT customer_id FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $customer_id = $stmt->fetchColumn();
    } catch (Throwable $e) {
        error_log('[customer_id lookup error] ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'server_error']);
        exit;
    }
}

try {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    if ($limit <= 0) $limit = 10;
    if ($limit > 100) $limit = 100;

    if ($customer_id) {
        // ✅ Factures liées aux folders dont la company appartient au même customer_id
        $sql = "
            SELECT
                i.id AS invoice_id,
                i.invoice_number,
                i.amount,
                i.status,
                DATE_FORMAT(i.issued_at, '%Y-%m-%d') AS issued_at,
                cust.name AS client_name,
                f.id AS folder_id,
                comp.id AS company_id,
                comp.name AS company_name
            FROM invoices i
            INNER JOIN folders f ON i.folder_id = f.id
            INNER JOIN companies comp ON f.company_id = comp.id
            INNER JOIN customers cust ON comp.customer_id = cust.id
            WHERE comp.customer_id = ?
            ORDER BY i.issued_at DESC
            LIMIT $limit
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$customer_id]);
    } else {
        // 🟡 Si aucun customer_id → factures créées ou liées à l'utilisateur
        $sql = "
            SELECT
                i.id AS invoice_id,
                i.invoice_number,
                i.amount,
                i.status,
                DATE_FORMAT(i.issued_at, '%Y-%m-%d') AS issued_at,
                c.name AS client_name,
                f.id AS folder_id,
                comp.name AS company_name
            FROM invoices i
            LEFT JOIN folders f ON i.folder_id = f.id
            LEFT JOIN customers c ON i.customer_id = c.id
            LEFT JOIN companies comp ON f.company_id = comp.id
            WHERE i.user_id = ?
            ORDER BY i.issued_at DESC
            LIMIT $limit
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
    }

    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'invoices' => $invoices]);
} catch (Throwable $e) {
    file_put_contents(__DIR__ . '/missions_error.log', "[SQL error] " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'error' => 'server_error', 'details' => $e->getMessage()]);
}
