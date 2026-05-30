<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

try {
    $period = isset($_GET['period']) ? $_GET['period'] : 'month';
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

    // Répartition des opportunités par source
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(source, 'Non spécifié') as source_name,
            COUNT(*) as count,
            COALESCE(SUM(amount), 0) as total_amount,
            COUNT(CASE WHEN stage = 'closed_won' THEN 1 END) as won_count,
            AVG(amount) as avg_amount
        FROM opportunities o
        WHERE o.created_at BETWEEN :start_date AND :end_date
        GROUP BY COALESCE(source, 'Non spécifié')
        ORDER BY COUNT(*) DESC
    ");

    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    $source_data = $stmt->fetchAll();

    $labels = [];
    $values = [];
    $amounts = [];
    $conversion_rates = [];

    foreach ($source_data as $row) {
        $labels[] = $row['source_name'];
        $values[] = intval($row['count']);
        $amounts[] = floatval($row['total_amount']);
        
        $conversion_rate = $row['count'] > 0 ? ($row['won_count'] / $row['count']) * 100 : 0;
        $conversion_rates[] = round($conversion_rate, 1);
    }

    // Données de performance par source
    $performance_data = [];
    foreach ($source_data as $row) {
        $performance_data[] = [
            'source' => $row['source_name'],
            'total_opportunities' => intval($row['count']),
            'won_opportunities' => intval($row['won_count']),
            'total_amount' => floatval($row['total_amount']),
            'avg_amount' => round(floatval($row['avg_amount']), 2),
            'conversion_rate' => $row['count'] > 0 ? round(($row['won_count'] / $row['count']) * 100, 1) : 0
        ];
    }

    // Évolution des sources dans le temps
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COALESCE(source, 'Non spécifié') as source_name,
            COUNT(*) as count
        FROM opportunities
        WHERE created_at BETWEEN DATE_SUB(:end_date, INTERVAL 6 MONTH) AND :end_date
        GROUP BY DATE_FORMAT(created_at, '%Y-%m'), COALESCE(source, 'Non spécifié')
        ORDER BY month DESC, count DESC
    ");

    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    $evolution_data = $stmt->fetchAll();

    // Organiser les données d'évolution
    $evolution_by_month = [];
    foreach ($evolution_data as $row) {
        $month = $row['month'];
        if (!isset($evolution_by_month[$month])) {
            $evolution_by_month[$month] = [];
        }
        $evolution_by_month[$month][$row['source_name']] = intval($row['count']);
    }

    // Top 3 des sources les plus performantes
    $top_sources = array_slice($performance_data, 0, 3);

    // ROI par source (si des coûts sont disponibles)
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(o.source, 'Non spécifié') as source_name,
            COALESCE(SUM(CASE WHEN o.stage = 'closed_won' THEN o.amount END), 0) as revenue,
            COUNT(*) as total_opportunities
        FROM opportunities o
        WHERE o.created_at BETWEEN :start_date AND :end_date
        GROUP BY COALESCE(o.source, 'Non spécifié')
    ");

    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    $roi_data = $stmt->fetchAll();

    $response = [
        'success' => true,
        'labels' => $labels,
        'values' => $values,
        'amounts' => $amounts,
        'conversion_rates' => $conversion_rates,
        'performance_data' => $performance_data,
        'top_sources' => $top_sources,
        'evolution' => $evolution_by_month,
        'stats' => [
            'total_sources' => count($labels),
            'total_opportunities' => array_sum($values),
            'total_revenue' => array_sum($amounts),
            'best_source' => !empty($performance_data) ? $performance_data[0]['source'] : 'Aucune',
            'best_conversion_rate' => !empty($conversion_rates) ? max($conversion_rates) : 0
        ],
        'period' => [
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
        'error' => 'Erreur lors du chargement des données sources: ' . $e->getMessage(),
        'labels' => [],
        'values' => []
    ]);
}
?>
