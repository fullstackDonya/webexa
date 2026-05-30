<?php
require_once 'includes/verify_subscriptions.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$page_title = "Analytics Opportunités - CRM Intelligent";
$user = getCurrentUser();
$user_id = $user['id'] ?? null;
$customer_id = isset($_SESSION['customer_id']) ? (int)$_SESSION['customer_id'] : null;

// Si pas d'utilisateur, rediriger
if (!$user || !$user_id) {
    header('Location: ../login.php');
    exit;
}

// Récupérer toutes les opportunités (actives et fermées) avec leurs stages
if ($customer_id) {
    $stmt = $pdo->prepare("
        SELECT o.*, 
               c.name as company_name,
               u.username as assigned_name,
               ps.probability_default,
               ps.name as stage_name,
               ps.order_position
        FROM opportunities o
        LEFT JOIN companies c ON c.id = o.company_id
        LEFT JOIN users u ON u.id = o.assigned_to
        LEFT JOIN pipeline_stages ps ON ps.slug = o.stage
        WHERE (o.customer_id = ? OR c.customer_id = ?)
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$customer_id, $customer_id]);
} else {
    $stmt = $pdo->prepare("
        SELECT o.*, 
               c.name as company_name,
               u.name as assigned_name,
               ps.probability_default,
               ps.name as stage_name,
               ps.order_position
        FROM opportunities o
        LEFT JOIN companies c ON c.id = o.company_id
        LEFT JOIN users u ON u.id = o.assigned_to
        LEFT JOIN pipeline_stages ps ON ps.slug = o.stage
        WHERE o.assigned_to = ?
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$user_id]);
}
$all_opportunities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculer les KPIs
$active_opportunities = array_filter($all_opportunities, function($o) {
    return !in_array($o['stage'], ['closed_won', 'closed_lost']);
});

$won_opportunities = array_filter($all_opportunities, function($o) {
    return $o['stage'] === 'closed_won';
});

$lost_opportunities = array_filter($all_opportunities, function($o) {
    return $o['stage'] === 'closed_lost';
});

// Valeur pipeline (opportunités actives)
$pipeline_value = array_sum(array_column($active_opportunities, 'amount'));

// Taux de conversion
$total_closed = count($won_opportunities) + count($lost_opportunities);
$conversion_rate = $total_closed > 0 ? round((count($won_opportunities) / $total_closed) * 100, 1) : 0;

// Cycle moyen de vente (en jours) pour les opportunités gagnées
$avg_cycle = 0;
$cycle_count = 0;
foreach ($won_opportunities as $opp) {
    if ($opp['actual_close_date'] && $opp['created_at']) {
        $start = strtotime($opp['created_at']);
        $end = strtotime($opp['actual_close_date']);
        if ($start && $end && $end > $start) {
            $avg_cycle += round(($end - $start) / 86400);
            $cycle_count++;
        }
    }
}
$avg_cycle = $cycle_count > 0 ? round($avg_cycle / $cycle_count) : 0;

// Valeur moyenne des opportunités actives
$avg_value = count($active_opportunities) > 0 ? round($pipeline_value / count($active_opportunities)) : 0;

// Statistiques par stage
$stats_by_stage = [];
foreach ($all_opportunities as $opp) {
    $stage = $opp['stage_name'] ?? 'Non défini';
    if (!isset($stats_by_stage[$stage])) {
        $stats_by_stage[$stage] = [
            'name' => $stage,
            'count' => 0,
            'value' => 0,
            'slug' => $opp['stage'],
            'order' => $opp['order_position'] ?? 999,
            'probability' => $opp['probability_default'] ?? 0
        ];
    }
    $stats_by_stage[$stage]['count']++;
    $stats_by_stage[$stage]['value'] += floatval($opp['amount']);
}

// Trier par ordre
uasort($stats_by_stage, function($a, $b) {
    return $a['order'] <=> $b['order'];
});

// Statistiques par commercial
$stats_by_user = [];
foreach ($all_opportunities as $opp) {
    $user_name = $opp['assigned_name'] ?? 'Non assigné';
    if (!isset($stats_by_user[$user_name])) {
        $stats_by_user[$user_name] = [
            'count' => 0,
            'value' => 0,
            'won' => 0,
            'lost' => 0
        ];
    }
    $stats_by_user[$user_name]['count']++;
    $stats_by_user[$user_name]['value'] += floatval($opp['amount']);
    if ($opp['stage'] === 'closed_won') {
        $stats_by_user[$user_name]['won']++;
    } elseif ($opp['stage'] === 'closed_lost') {
        $stats_by_user[$user_name]['lost']++;
    }
}

// Évolution mensuelle du pipeline (6 derniers mois)
$monthly_evolution = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthly_evolution[$month] = 0;
}

foreach ($all_opportunities as $opp) {
    if ($opp['created_at']) {
        $month = date('Y-m', strtotime($opp['created_at']));
        if (isset($monthly_evolution[$month])) {
            $monthly_evolution[$month] += floatval($opp['amount']);
        }
    }
}

// Récupérer tous les utilisateurs pour le filtre
$all_users = [];
if ($customer_id) {
    $stmt = $pdo->prepare("
        SELECT id, CONCAT(first_name, ' ', last_name) as full_name 
        FROM users 
        WHERE customer_id = ? AND role IN ('user', 'admin')
        ORDER BY first_name, last_name
    ");
    $stmt->execute([$customer_id]);
    $all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
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
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">

            
            <div class="container-fluid">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-chart-line text-primary"></i> Analytics des Opportunités
                    </h1>
                    <div>
                        <button class="btn btn-info me-2" onclick="refreshAnalytics()">
                            <i class="fas fa-sync"></i> Actualiser
                        </button>
                        <button class="btn btn-success" onclick="exportAnalytics()">
                            <i class="fas fa-download"></i> Exporter
                        </button>
                    </div>
                </div>

                <!-- Filtres de période -->
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">Période d'analyse</label>
                                <select class="form-control" id="period-select">
                                    <option value="month">Ce mois</option>
                                    <option value="quarter" selected>Ce trimestre</option>
                                    <option value="year">Cette année</option>
                                    <option value="custom">Personnalisé</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Commercial</label>
                                <select class="form-control" id="salesperson-filter">
                                    <option value="">Tous les commerciaux</option>
                                    <?php foreach ($all_users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['full_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Secteur</label>
                                <select class="form-control" id="sector-filter">
                                    <option value="">Tous les secteurs</option>
                                    <option value="tech">Technologie</option>
                                    <option value="finance">Finance</option>
                                    <option value="retail">Commerce</option>
                                    <option value="industry">Industrie</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Actions</label>
                                <div>
                                    <button class="btn btn-outline-secondary" onclick="resetFilters()">
                                        <i class="fas fa-undo"></i> Reset
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Métriques principales -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Valeur Pipeline
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="pipeline-value"><?php echo number_format($pipeline_value, 0, ',', ' '); ?> €</div>
                                        <div class="text-xs text-success">
                                            <i class="fas fa-arrow-up"></i> +12% vs période précédente
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-euro-sign fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Taux de Conversion
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="conversion-rate"><?php echo $conversion_rate; ?>%</div>
                                        <div class="text-xs text-success">
                                            <i class="fas fa-arrow-up"></i> +3.2% vs période précédente
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-percentage fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Cycle Moyen
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="avg-cycle"><?php echo $avg_cycle; ?> jours</div>
                                        <div class="text-xs text-danger">
                                            <i class="fas fa-arrow-up"></i> +5 jours vs période précédente
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Valeur Moyenne
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="avg-value"><?php echo number_format($avg_value, 0, ',', ' '); ?> €</div>
                                        <div class="text-xs text-success">
                                            <i class="fas fa-arrow-up"></i> +8% vs période précédente
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calculator fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Graphiques d'analyse -->
                <div class="row mb-4">
                    <div class="col-xl-8">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Évolution du Pipeline</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="pipeline-evolution-chart" height="320"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Répartition par Étape</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="stage-distribution-chart" height="320"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Analyse par commercial -->
                <div class="row mb-4">
                    <div class="col-xl-6">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Performance par Commercial</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="salesperson-performance-chart" height="300"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-6">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Sources d'Opportunités</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="source-analysis-chart" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Analyse détaillée -->
                <div class="row">
                    <div class="col-xl-8">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Analyse de la Vélocité</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Étape</th>
                                                <th>Nombre d'Opportunités</th>
                                                <th>Valeur Totale</th>
                                                <th>Probabilité</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($stats_by_stage)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted">Aucune opportunité trouvée</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($stats_by_stage as $stage_name => $stats): ?>
                                                    <tr>
                                                        <td>
                                                            <span class="badge bg-<?php 
                                                                echo match($stats['slug']) {
                                                                    'prospection' => 'primary',
                                                                    'qualification' => 'info',
                                                                    'proposition' => 'warning',
                                                                    'negociation' => 'secondary',
                                                                    'closed_won' => 'success',
                                                                    'closed_lost' => 'danger',
                                                                    default => 'secondary'
                                                                };
                                                            ?>"><?php echo htmlspecialchars($stats['name']); ?></span>
                                                        </td>
                                                        <td><?php echo $stats['count']; ?></td>
                                                        <td><?php echo number_format($stats['value'], 0, ',', ' '); ?> €</td>
                                                        <td><?php echo round($stats['probability']); ?>%</td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Insights IA</h6>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-success mb-3">
                                    <h6 class="alert-heading"><i class="fas fa-lightbulb"></i> Recommandation</h6>
                                    <p class="mb-0">L'étape "Proposition" montre une baisse de conversion. Considérez une formation sur la présentation.</p>
                                </div>

                                <div class="alert alert-info mb-3">
                                    <h6 class="alert-heading"><i class="fas fa-chart-line"></i> Tendance</h6>
                                    <p class="mb-0">Les opportunités de plus de €50k ont 23% plus de chances de se conclure.</p>
                                </div>

                                <div class="alert alert-warning mb-0">
                                    <h6 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Attention</h6>
                                    <p class="mb-0">Le cycle de vente s'allonge. Focus sur la qualification précoce recommandé.</p>
                                </div>
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
        // Données PHP converties en JavaScript
        const analyticsData = {
            monthlyEvolution: <?php echo json_encode(array_values($monthly_evolution)); ?>,
            monthlyLabels: <?php echo json_encode(array_map(function($m) { 
                return date('M Y', strtotime($m . '-01')); 
            }, array_keys($monthly_evolution))); ?>,
            
            stageDistribution: {
                labels: <?php echo json_encode(array_map(function($s) { return $s['name']; }, $stats_by_stage)); ?>,
                data: <?php echo json_encode(array_map(function($s) { return $s['count']; }, $stats_by_stage)); ?>,
                values: <?php echo json_encode(array_map(function($s) { return $s['value']; }, $stats_by_stage)); ?>
            },
            
            userPerformance: {
                labels: <?php echo json_encode(array_keys($stats_by_user)); ?>,
                counts: <?php echo json_encode(array_map(function($u) { return $u['count']; }, $stats_by_user)); ?>,
                values: <?php echo json_encode(array_map(function($u) { return $u['value']; }, $stats_by_user)); ?>,
                won: <?php echo json_encode(array_map(function($u) { return $u['won']; }, $stats_by_user)); ?>
            }
        };
    </script>
    
    <script src="assets/js/opportunities-analytics.js"></script>
</body>
</html>
