<?php
header('Content-Type: application/json; charset=utf-8');
session_start();


require_once __DIR__ . '/../config/database.php';




$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'unauthenticated']);
    exit;
}

// Récupération du customer_id
$customer_id = $_SESSION['customer_id'] ?? null;
if (!$customer_id) {
    if (!empty($_SESSION['pending_customer_id'])) {
        $customer_id = intval($_SESSION['pending_customer_id']);
        error_log('api/dashboard-data: using pending_customer_id from session: ' . $customer_id);
    }
}
try {
    // Vérifier si les tables existent
    $stmt = $pdo->query("SHOW TABLES LIKE 'opportunities'");
    if ($stmt->rowCount() == 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Tables CRM non trouvées. Veuillez installer la base de données.',
            'action' => 'install_database',
            'kpis' => [
                'total_revenue' => 0,
                'active_clients' => 0,
                'conversion_rate' => 0,
                'opportunities' => 0
            ]
        ]);
        exit;
    }

    // Récupération du filtre customer_id si présent
    $customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : null;
    $customer_filter = $customer_id ? "AND c.customer_id = :customer_id" : "";
    $params = [];
    if ($customer_id) {
        $params[':customer_id'] = $customer_id;
    }

    // Calcul des revenus totaux (opportunités fermées gagnées, sociétés externes)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(o.amount), 0) as total_revenue
        FROM opportunities o
        INNER JOIN companies c ON o.company_id = c.id
        WHERE o.stage = 'closed_won'
        AND YEAR(o.created_at) = YEAR(CURRENT_DATE)
        AND c.interne_customer = 0
        $customer_filter
    ");
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $total_revenue = $stmt->fetchColumn();

    // Nombre de clients actifs (sociétés externes)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as active_clients
        FROM companies c
        WHERE c.status = 'client'
        AND c.is_active = 1
        AND c.interne_customer = 0
        $customer_filter
    ");
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();

    $active_clients = $stmt->fetchColumn();

    // Calcul du taux de conversion (sociétés externes)
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(CASE WHEN o.stage = 'closed_won' THEN 1 END) as won_deals,
            COUNT(*) as total_deals
        FROM opportunities o
        INNER JOIN companies c ON o.company_id = c.id
        WHERE o.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
        AND c.interne_customer = 0
        $customer_filter
    ");
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $conversion_data = $stmt->fetch();

    $conversion_rate = 0;
    if ($conversion_data['total_deals'] > 0) {
        $conversion_rate = round(($conversion_data['won_deals'] / $conversion_data['total_deals']) * 100, 1);
    }

    // Nombre d'opportunités actives (sociétés externes)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as opportunities
        FROM opportunities o
        INNER JOIN companies c ON o.company_id = c.id
        WHERE o.stage NOT IN ('closed_won', 'closed_lost')
        AND c.interne_customer = 0
        $customer_filter
    ");
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $opportunities = $stmt->fetchColumn();

    // Données de performance supplémentaires (sociétés externes)
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT o.assigned_to) as active_sales_reps,
            AVG(o.amount) as avg_deal_size,
            COUNT(CASE WHEN o.created_at >= CURDATE() THEN 1 END) as today_activities
        FROM opportunities o
        INNER JOIN companies c ON o.company_id = c.id
        WHERE o.stage NOT IN ('closed_lost')
        AND c.interne_customer = 0
        $customer_filter
    ");
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $performance_data = $stmt->fetch();

    // Objectifs mensuels (inchangé)
    $stmt = $pdo->prepare("
        SELECT 
            SUM(target_value) as monthly_target,
            SUM(achieved_value) as monthly_achieved
        FROM targets 
        WHERE start_date <= CURRENT_DATE 
        AND end_date >= CURRENT_DATE 
        AND period = 'monthly'
    ");
    $stmt->execute();
    $target_data = $stmt->fetch();



    
    // Toutes les factures (invoices)
    $sql_invoices = "
        SELECT i.*, f.name AS folder_name, c.name AS company_name
        FROM invoices i
        INNER JOIN folders f ON i.folder_id = f.id
        INNER JOIN companies c ON f.company_id = c.id
        WHERE c.interne_customer = 0
        $customer_filter
        ORDER BY i.issued_at DESC
    ";
    $stmt = $pdo->prepare($sql_invoices);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Toutes les missions (missions) avec état
    $sql_missions = "
        SELECT m.*, f.name AS folder_name, c.name AS company_name
        FROM missions m
        INNER JOIN folders f ON m.folder_id = f.id
        INNER JOIN companies c ON f.company_id = c.id
        WHERE c.interne_customer = 0
        $customer_filter
        ORDER BY m.created_at DESC
    ";
    $stmt = $pdo->prepare($sql_missions);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $missions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Statistiques sur l'état des missions
    $sql_mission_status = "
        SELECT s.name, COUNT(*) as count
        FROM missions m
        INNER JOIN folders f ON m.folder_id = f.id
        INNER JOIN companies c ON f.company_id = c.id
        INNER JOIN statuses s ON m.status_id = s.id
        WHERE c.interne_customer = 0
        $customer_filter
        GROUP BY s.name     
    ";
       $stmt = $pdo->prepare($sql_mission_status);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $missions_status = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);


    $response = [
        'success' => true,
        'kpis' => [
            'total_revenue' => floatval($total_revenue),
            'active_clients' => intval($active_clients),
            'conversion_rate' => floatval($conversion_rate),
            'opportunities' => intval($opportunities),
            'active_sales_reps' => intval($performance_data['active_sales_reps'] ?? 0),
            'avg_deal_size' => floatval($performance_data['avg_deal_size'] ?? 0),
            'today_activities' => intval($performance_data['today_activities'] ?? 0),
            'monthly_target' => floatval($target_data['monthly_target'] ?? 0),
            'monthly_achieved' => floatval($target_data['monthly_achieved'] ?? 0)
            
        ],
        'invoices' => $invoices,
        'missions' => $missions,
        'missions_status' => $missions_status,
        'generated_at' => date('Y-m-d H:i:s')
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors du calcul des KPIs: ' . $e->getMessage(),
        'kpis' => [
            'total_revenue' => 0,
            'active_clients' => 0,
            'conversion_rate' => 0,
            'opportunities' => 0
        ]
    ]);
}
?>
