<?php
require_once 'includes/verify_subscriptions.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$page_title = "Prévisions de Vente - CRM Intelligent";
$user = getCurrentUser();
$user_id = $user['id'] ?? null;
$customer_id = isset($_SESSION['customer_id']) ? (int)$_SESSION['customer_id'] : null;

// Si pas d'utilisateur, rediriger
if (!$user || !$user_id) {
    header('Location: ../login.php');
    exit;
}

// Récupérer toutes les opportunités actives avec probabilité
if ($customer_id) {
    $stmt = $pdo->prepare("
        SELECT o.*, 
               c.name as company_name,
               u.username as assigned_name,
               ps.probability_default,
               ps.name as stage_name
        FROM opportunities o
        LEFT JOIN companies c ON c.id = o.company_id
        LEFT JOIN users u ON u.id = o.assigned_to
        LEFT JOIN pipeline_stages ps ON ps.slug = o.stage
        WHERE (o.customer_id = ? OR c.customer_id = ?) 
        AND o.stage NOT IN ('closed_won', 'closed_lost')
        ORDER BY o.expected_close_date ASC
    ");
    $stmt->execute([$customer_id, $customer_id]);
} else {
    $stmt = $pdo->prepare("
        SELECT o.*, 
               c.name as company_name,
               u.name as assigned_name,
               ps.probability_default,
               ps.name as stage_name
        FROM opportunities o
        LEFT JOIN companies c ON c.id = o.company_id
        LEFT JOIN users u ON u.id = o.assigned_to
        LEFT JOIN pipeline_stages ps ON ps.slug = o.stage
        WHERE o.assigned_to = ? 
        AND o.stage NOT IN ('closed_won', 'closed_lost')
        ORDER BY o.expected_close_date ASC
    ");
    $stmt->execute([$user_id]);
}
$opportunities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculer les KPIs de prévision
$total_pipeline_value = 0;
$weighted_pipeline_value = 0;
$opportunities_this_month = 0;
$opportunities_next_month = 0;
$opportunities_this_quarter = 0;
$at_risk_count = 0;

$current_month = date('Y-m');
$next_month = date('Y-m', strtotime('+1 month'));
$current_quarter_start = date('Y-m-d', strtotime('first day of this quarter'));
$current_quarter_end = date('Y-m-d', strtotime('last day of this quarter'));

$forecast_by_month = [];
$forecast_by_stage = [];

foreach ($opportunities as $opp) {
    $amount = floatval($opp['amount']);
    $probability = floatval($opp['probability_default'] ?? 50) / 100;
    
    $total_pipeline_value += $amount;
    $weighted_pipeline_value += $amount * $probability;
    
    // Par mois
    if ($opp['expected_close_date']) {
        $close_month = date('Y-m', strtotime($opp['expected_close_date']));
        
        if (!isset($forecast_by_month[$close_month])) {
            $forecast_by_month[$close_month] = [
                'total' => 0,
                'weighted' => 0,
                'count' => 0
            ];
        }
        
        $forecast_by_month[$close_month]['total'] += $amount;
        $forecast_by_month[$close_month]['weighted'] += $amount * $probability;
        $forecast_by_month[$close_month]['count']++;
        
        if ($close_month === $current_month) {
            $opportunities_this_month++;
        } elseif ($close_month === $next_month) {
            $opportunities_next_month++;
        }
        
        if ($opp['expected_close_date'] >= $current_quarter_start && $opp['expected_close_date'] <= $current_quarter_end) {
            $opportunities_this_quarter++;
        }
        
        // Opportunités à risque (date dépassée)
        if (strtotime($opp['expected_close_date']) < time()) {
            $at_risk_count++;
        }
    }
    
    // Par stage
    $stage = $opp['stage_name'] ?? 'Non défini';
    if (!isset($forecast_by_stage[$stage])) {
        $forecast_by_stage[$stage] = [
            'total' => 0,
            'weighted' => 0,
            'count' => 0,
            'probability' => $probability * 100
        ];
    }
    
    $forecast_by_stage[$stage]['total'] += $amount;
    $forecast_by_stage[$stage]['weighted'] += $amount * $probability;
    $forecast_by_stage[$stage]['count']++;
}

// Trier les prévisions par mois
ksort($forecast_by_month);

// Top 10 opportunités par montant pondéré
$top_opportunities = $opportunities;
usort($top_opportunities, function($a, $b) {
    $weight_a = floatval($a['amount']) * (floatval($a['probability_default'] ?? 50) / 100);
    $weight_b = floatval($b['amount']) * (floatval($b['probability_default'] ?? 50) / 100);
    return $weight_b <=> $weight_a;
});
$top_opportunities = array_slice($top_opportunities, 0, 10);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .forecast-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .kpi-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        .kpi-card.success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        .kpi-card.warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .kpi-card.info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .kpi-value {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 10px 0;
        }
        .kpi-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
        }
        .opportunity-row {
            padding: 15px;
            border-bottom: 1px solid #eee;
            transition: background 0.3s;
        }
        .opportunity-row:hover {
            background: #f8f9fa;
        }
        .probability-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.85rem;
        }
        .prob-high {
            background: #d4edda;
            color: #155724;
        }
        .prob-medium {
            background: #fff3cd;
            color: #856404;
        }
        .prob-low {
            background: #f8d7da;
            color: #721c24;
        }
        .at-risk {
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        <div class="main-content">
            <div class="container-fluid">
                
                <!-- En-tête -->
                <div class="page-header">
                    <h1>
                        <i class="fas fa-chart-line"></i> Prévisions de Vente
                    </h1>
                    <div class="btn-group">
                        <a href="opportunities.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left"></i> Retour Opportunités
                        </a>
                        <a href="opportunities-export.php" class="btn btn-primary">
                            <i class="fas fa-file-export"></i> Exporter
                        </a>
                    </div>
                </div>

                <!-- KPIs Principaux -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <i class="fas fa-chart-bar fa-2x"></i>
                            <div class="kpi-value"><?php echo number_format($total_pipeline_value, 0, ',', ' '); ?> €</div>
                            <div class="kpi-label">Valeur Pipeline Total</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card success">
                            <i class="fas fa-percentage fa-2x"></i>
                            <div class="kpi-value"><?php echo number_format($weighted_pipeline_value, 0, ',', ' '); ?> €</div>
                            <div class="kpi-label">CA Prévisionnel Pondéré</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card info">
                            <i class="fas fa-calendar-alt fa-2x"></i>
                            <div class="kpi-value"><?php echo $opportunities_this_month; ?></div>
                            <div class="kpi-label">Closing Prévu ce Mois</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card warning">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                            <div class="kpi-value"><?php echo $at_risk_count; ?></div>
                            <div class="kpi-label">Opportunités à Risque</div>
                        </div>
                    </div>
                </div>

                <!-- Graphiques -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="forecast-card">
                            <h4><i class="fas fa-calendar"></i> Prévisions par Mois</h4>
                            <div class="chart-container">
                                <canvas id="forecastByMonthChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="forecast-card">
                            <h4><i class="fas fa-stream"></i> Répartition par Stage</h4>
                            <div class="chart-container">
                                <canvas id="forecastByStageChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tableau Prévisions Mensuelles -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="forecast-card">
                            <h4><i class="fas fa-table"></i> Détail Prévisions Mensuelles</h4>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Mois</th>
                                            <th>Nb Opportunités</th>
                                            <th>Valeur Total</th>
                                            <th>CA Pondéré</th>
                                            <th>Probabilité Moyenne</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($forecast_by_month as $month => $data): 
                                            $avg_prob = $data['total'] > 0 ? ($data['weighted'] / $data['total']) * 100 : 0;
                                            $month_formatted = date('F Y', strtotime($month . '-01'));
                                        ?>
                                        <tr>
                                            <td><strong><?php echo $month_formatted; ?></strong></td>
                                            <td><?php echo $data['count']; ?></td>
                                            <td><?php echo number_format($data['total'], 0, ',', ' '); ?> €</td>
                                            <td><strong><?php echo number_format($data['weighted'], 0, ',', ' '); ?> €</strong></td>
                            <td><?php echo round($avg_prob); ?>%</td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($forecast_by_month)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">Aucune opportunité avec date de closing prévue</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top 10 Opportunités -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="forecast-card">
                            <h4><i class="fas fa-trophy"></i> Top 10 Opportunités (par valeur pondérée)</h4>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Opportunité</th>
                                            <th>Entreprise</th>
                                            <th>Montant</th>
                                            <th>Probabilité</th>
                                            <th>Valeur Pondérée</th>
                                            <th>Date Closing</th>
                                            <th>Stage</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($top_opportunities as $opp): 
                                            $probability = floatval($opp['probability_default'] ?? 50);
                                            $weighted = floatval($opp['amount']) * ($probability / 100);
                                            $prob_class = $probability >= 75 ? 'prob-high' : ($probability >= 50 ? 'prob-medium' : 'prob-low');
                                            $is_at_risk = $opp['expected_close_date'] && strtotime($opp['expected_close_date']) < time();
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($opp['title']); ?></strong>
                                                <?php if ($is_at_risk): ?>
                                                <br><small class="at-risk"><i class="fas fa-exclamation-circle"></i> Date dépassée</small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($opp['company_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo number_format($opp['amount'], 0, ',', ' '); ?> €</td>
                                            <td><span class="probability-badge <?php echo $prob_class; ?>"><?php echo round($probability); ?>%</span></td>
                                            <td><strong><?php echo number_format($weighted, 0, ',', ' '); ?> €</strong></td>
                                            <td><?php echo $opp['expected_close_date'] ? date('d/m/Y', strtotime($opp['expected_close_date'])) : 'N/A'; ?></td>
                                            <td><?php echo htmlspecialchars($opp['stage_name'] ?? $opp['stage']); ?></td>
                                            <td>
                                                <a href="opportunities.php?view=<?php echo $opp['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($top_opportunities)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">Aucune opportunité active</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    // Graphique par mois
    const monthlyData = <?php echo json_encode($forecast_by_month); ?>;
    const monthLabels = Object.keys(monthlyData).map(m => {
        const d = new Date(m + '-01');
        return d.toLocaleDateString('fr-FR', { month: 'short', year: 'numeric' });
    });
    const monthTotals = Object.values(monthlyData).map(d => d.total);
    const monthWeighted = Object.values(monthlyData).map(d => d.weighted);

    const ctxMonth = document.getElementById('forecastByMonthChart').getContext('2d');
    new Chart(ctxMonth, {
        type: 'bar',
        data: {
            labels: monthLabels,
            datasets: [
                {
                    label: 'Valeur Pipeline',
                    data: monthTotals,
                    backgroundColor: 'rgba(102, 126, 234, 0.5)',
                    borderColor: 'rgba(102, 126, 234, 1)',
                    borderWidth: 2
                },
                {
                    label: 'CA Prévisionnel Pondéré',
                    data: monthWeighted,
                    backgroundColor: 'rgba(17, 153, 142, 0.5)',
                    borderColor: 'rgba(17, 153, 142, 1)',
                    borderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString() + ' €';
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y.toLocaleString() + ' €';
                        }
                    }
                }
            }
        }
    });

    // Graphique par stage
    const stageData = <?php echo json_encode($forecast_by_stage); ?>;
    const stageLabels = Object.keys(stageData);
    const stageWeighted = Object.values(stageData).map(d => d.weighted);
    const stageColors = [
        'rgba(102, 126, 234, 0.8)',
        'rgba(118, 75, 162, 0.8)',
        'rgba(79, 172, 254, 0.8)',
        'rgba(0, 242, 254, 0.8)',
        'rgba(17, 153, 142, 0.8)',
        'rgba(56, 239, 125, 0.8)'
    ];

    const ctxStage = document.getElementById('forecastByStageChart').getContext('2d');
    new Chart(ctxStage, {
        type: 'doughnut',
        data: {
            labels: stageLabels,
            datasets: [{
                data: stageWeighted,
                backgroundColor: stageColors,
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return label + ': ' + value.toLocaleString() + ' € (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
    </script>
</body>
</html>