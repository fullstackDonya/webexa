<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once __DIR__ . '/permission-bootstrap.php';

function getPeriodRange($type){

    switch($type){

        case 'current_week':

            return [
                date('Y-m-d', strtotime('monday this week')),
                date('Y-m-d')
            ];


        case 'previous_week':

            return [
                date('Y-m-d', strtotime('monday last week')),
                date('Y-m-d', strtotime('sunday last week'))
            ];


        case 'current_month':

            return [
                date('Y-m-01'),
                date('Y-m-t')
            ];


        case 'previous_month':

            return [
                date('Y-m-01', strtotime('first day of last month')),
                date('Y-m-t', strtotime('last day of last month'))
            ];


        case 'current_quarter':

            $month = date('n');

            $quarter = ceil($month / 3);

            $startMonth = (($quarter - 1) * 3) + 1;


            $start = date(
                'Y-'.$startMonth.'-01'
            );


            $end = date(
                'Y-m-t',
                strtotime($start.' +2 months')
            );


            return [
                $start,
                $end
            ];


        default:

            return [
                date('Y-m-01'),
                date('Y-m-t')
            ];
    }

}




try {
    $period = isset($_GET['period']) ? $_GET['period'] : 'month';
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;
    $source = isset($_GET['source']) ? $_GET['source'] : 'opportunities';
    
    $customer_id = $_SESSION['customer_id'];

    switch($source){

        case 'erp_sales':

            $table = 'erp_sales';
            $dateField = 'created_at';
            $amountField = 'total_price';
            $where = "customer_id = :customer_id";

            break;


      
        case 'missions':

            $table = 'missions';
            $dateField = 'created_at';
            $amountField = 'prix';

            $where = "folder_id IN (
                SELECT f.id
                FROM folders f
                INNER JOIN companies c 
                    ON c.id = f.company_id
                WHERE c.customer_id = :customer_id
            )";

        break;

        default:

            $table = 'opportunities';
            $dateField = 'actual_close_date';
            $amountField = 'amount';
            $where = "stage='closed_won' AND customer_id = :customer_id";

            break;
    }
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
    $conditions = [];

    if (!empty($where)) {
        $conditions[] = $where;
    }

    $conditions[] = "$dateField BETWEEN :start_date AND :end_date";

    $whereSql = implode(' AND ', $conditions);


 
    // Données de ventes par jour/semaine/mois selon la période
    if ($period === 'week' || (strtotime($end_date) - strtotime($start_date)) <= 30 * 24 * 3600) {
        // Données quotidiennes
        $stmt = $pdo->prepare("
            SELECT 
                DATE($dateField) as date_label,
                COALESCE(SUM($amountField), 0) as sales_amount,
                COUNT(id) as deals_count
            FROM $table
            WHERE $whereSql
            GROUP BY DATE($dateField)
            ORDER BY DATE($dateField)
        ");
    } else {
        // Données mensuelles
        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT($dateField, '%Y-%m') as date_label,
                COALESCE(SUM($amountField), 0) as sales_amount,
                COUNT(id) as deals_count
            FROM $table
            WHERE $whereSql
            GROUP BY DATE_FORMAT($dateField, '%Y-%m')
            ORDER BY DATE_FORMAT($dateField, '%Y-%m')
        ");
    }

    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->bindParam(':customer_id', $customer_id);
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


    $conditions = [];

    if (!empty($where)) {
        $conditions[] = $where;
    }

    $conditions[] = "$dateField BETWEEN :previous_start AND :previous_end";

    $whereSqlPrevious = implode(' AND ', $conditions);
            


    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM($amountField), 0) as previous_sales
        FROM $table
        WHERE $whereSqlPrevious
    ");
    $stmt->bindParam(':previous_start', $previous_start);
    $stmt->bindParam(':previous_end', $previous_end);
    $stmt->bindParam(':customer_id', $customer_id);
    $stmt->execute();
    $previous_sales = $stmt->fetchColumn();

    $growth_rate = $previous_sales > 0 ? (($total_sales - $previous_sales) / $previous_sales) * 100 : 0;
    $table_rows = [];

    $periods = [

        "Cette semaine" => "current_week",

        "Semaine dernière" => "previous_week",

        "Ce mois" => "current_month",

        "Mois dernier" => "previous_month",

        "Ce trimestre" => "current_quarter"

    ];


    foreach($periods as $label=>$type){
        $growth = '';

        [$pStart,$pEnd] = getPeriodRange($type);


        $stmt = $pdo->prepare("
            SELECT
                COALESCE(SUM($amountField),0) AS total,
                COUNT(id) AS total_count
            FROM $table
            WHERE $dateField BETWEEN :start AND :end
            ".(!empty($where) ? "AND ".$where : "")."
        ");


       $stmt->execute([
            ':start'=>$pStart,
            ':end'=>$pEnd,
            ':customer_id'=>$customer_id
        ]);


        $row = $stmt->fetch(PDO::FETCH_ASSOC);


        $total = floatval($row['total']);
        $count = intval($row['total_count']);

        // calcul croissance selon période
        if($type === "current_week"){

            $previousType = "previous_week";

        }
        elseif($type === "current_month"){

            $previousType = "previous_month";

        }
        else{

            $previousType = null;

        }


        if($previousType){

            [$prevStart,$prevEnd] = getPeriodRange($previousType);


            $stmtPrev = $pdo->prepare("
                SELECT COALESCE(SUM($amountField),0)
                FROM $table
                WHERE $dateField BETWEEN :start AND :end
                ".(!empty($where) ? "AND ".$where : "")."
            ");


            $stmtPrev->execute([
                ':start'=>$prevStart,
                ':end'=>$prevEnd,
                ':customer_id'=>$customer_id
            ]);


            $previousTotal = $stmtPrev->fetchColumn();


            if($previousTotal > 0){

                $growth = round(
                    (($total-$previousTotal)/$previousTotal)*100,
                    1
                ).'%';

            }
        }


        $table_rows[]=[

            "label"=>$label,

            "sales"=>$total,

            "count"=>$count,

            "avg"=>$count > 0 ? round($total/$count,2) : 0,

            "growth"=>isset($growth) ? $growth : '',

            "top_product"=>"-",

            "top_salesperson"=>"-"

        ];

    }

    switch($source){

        case 'missions':

        $kpiLabels=[
            'total'=>'Montant missions',
            'count'=>'Nombre missions',
            'avg'=>'Prix moyen'
        ];

        break;


        case 'erp_sales':

        $kpiLabels=[
            'total'=>'CA réalisé',
            'count'=>'Nombre ventes',
            'avg'=>'Ticket moyen'
        ];

        break;


        default:

        $kpiLabels=[
            'total'=>'CA opportunités',
            'count'=>'Opportunités gagnées',
            'avg'=>'Valeur moyenne'
        ];

    }

    $goal = 10000; // objectif mensuel en €

    $goal_achieved = $goal > 0 
    ? round(($total_sales / $goal) * 100, 1)
    : 0;

    $response = [
        'kpi_labels'=>$kpiLabels,
        'success' => true,
        'labels' => $labels,
        'sales' => $sales,
        'deals' => $deals,
        'table_rows'=>$table_rows,
        'source'=>$source,
        'stats' => [
            'total_sales' => $total_sales,
            'total_deals' => $total_deals,
            'avg_deal_size' => round($avg_deal_size, 2),
            'growth_rate' => round($growth_rate, 1),
            'goal_achieved' => $goal_achieved,
            'goal' => $goal,
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
