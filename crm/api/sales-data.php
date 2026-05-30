<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

try {
    $period = isset($_GET['period']) ? $_GET['period'] : 'month';
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;

    // Définir la période par défaut si non spécifiée
    if (!$start_date || !$end_date) {
        switch ($period) {
            case 'week':
                $start_date = date('Y-m-d', strtotime('-7 days'));
                $end_date = date('Y-m-d');
                break;
            case 'month':
                $start_date = date('Y-m-01');
                $end_date = date('Y-m-t');
                break;
            case 'quarter':
                $start_date = date('Y-m-d', strtotime('-3 months'));
                $end_date = date('Y-m-d');
                break;
            case 'year':
                $start_date = date('Y-01-01');
                $end_date = date('Y-12-31');
                break;
            default:
                $start_date = date('Y-m-01');
                $end_date = date('Y-m-t');
        }
    }

    // Données de ventes par jour/semaine/mois selon la période
    if ($period === 'week' || (strtotime($end_date) - strtotime($start_date)) <= 30 * 24 * 3600) {
        // Données quotidiennes
        $stmt = $pdo->prepare("
            SELECT 
                DATE(o.actual_close_date) as date_label,
                COALESCE(SUM(o.amount), 0) as sales_amount,
                COUNT(o.id) as deals_count
            FROM opportunities o
            WHERE o.stage = 'closed_won'
            AND o.actual_close_date BETWEEN :start_date AND :end_date
            GROUP BY DATE(o.actual_close_date)
            ORDER BY DATE(o.actual_close_date)
        ");
    } else {
        // Données mensuelles
        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(o.actual_close_date, '%Y-%m') as date_label,
                COALESCE(SUM(o.amount), 0) as sales_amount,
                COUNT(o.id) as deals_count
            FROM opportunities o
            WHERE o.stage = 'closed_won'
            AND o.actual_close_date BETWEEN :start_date AND :end_date
            GROUP BY DATE_FORMAT(o.actual_close_date, '%Y-%m')
            ORDER BY DATE_FORMAT(o.actual_close_date, '%Y-%m')
        ");
    }

    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    $sales_data = $stmt->fetchAll();

    // Préparer les données pour le graphique
    $labels = [];
    $sales = [];
    $deals = [];

    // Générer toutes les dates de la période pour avoir un graphique complet
    $current_date = new DateTime($start_date);
    $end_date_obj = new DateTime($end_date);
    $sales_by_date = [];
    $deals_by_date = [];

    // Indexer les données par date
    foreach ($sales_data as $row) {
        $sales_by_date[$row['date_label']] = floatval($row['sales_amount']);
        $deals_by_date[$row['date_label']] = intval($row['deals_count']);
    }

    // Générer la série complète
    while ($current_date <= $end_date_obj) {
        if ($period === 'week' || (strtotime($end_date) - strtotime($start_date)) <= 30 * 24 * 3600) {
            $date_key = $current_date->format('Y-m-d');
            $label = $current_date->format('d/m');
            $current_date->modify('+1 day');
        } else {
            $date_key = $current_date->format('Y-m');
            $label = $current_date->format('M Y');
            $current_date->modify('+1 month');
        }

        $labels[] = $label;
        $sales[] = isset($sales_by_date[$date_key]) ? $sales_by_date[$date_key] : 0;
        $deals[] = isset($deals_by_date[$date_key]) ? $deals_by_date[$date_key] : 0;
    }

    // Statistiques supplémentaires
    $total_sales = array_sum($sales);
    $total_deals = array_sum($deals);
    $avg_deal_size = $total_deals > 0 ? $total_sales / $total_deals : 0;

    // Comparaison avec la période précédente
    $previous_start = date('Y-m-d', strtotime($start_date . ' -' . (strtotime($end_date) - strtotime($start_date)) . ' seconds'));
    $previous_end = date('Y-m-d', strtotime($start_date . ' -1 day'));

    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) as previous_sales
        FROM opportunities 
        WHERE stage = 'closed_won'
        AND actual_close_date BETWEEN :previous_start AND :previous_end
    ");
    $stmt->bindParam(':previous_start', $previous_start);
    $stmt->bindParam(':previous_end', $previous_end);
    $stmt->execute();
    $previous_sales = $stmt->fetchColumn();

    $growth_rate = $previous_sales > 0 ? (($total_sales - $previous_sales) / $previous_sales) * 100 : 0;

    $response = [
        'success' => true,
        'labels' => $labels,
        'sales' => $sales,
        'deals' => $deals,
        'stats' => [
            'total_sales' => $total_sales,
            'total_deals' => $total_deals,
            'avg_deal_size' => round($avg_deal_size, 2),
            'growth_rate' => round($growth_rate, 1),
            'period' => $period,
            'start_date' => $start_date,
            'end_date' => $end_date
        ],
        'generated_at' => date('Y-m-d H:i:s')
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors du chargement des données de ventes: ' . $e->getMessage(),
        'labels' => [],
        'sales' => []
    ]);
}
?>
