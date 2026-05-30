<?php
require_once 'includes/verify_subscriptions.php';

$page_title = "Analyse des Ventes - CRM Intelligent";
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
        <?php include 'includes/topbar.php'; ?>
        
        <div class="main-content">

            
            <div class="container-fluid">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-chart-area text-primary"></i> Analyse des Ventes
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

                <!-- Métriques de ventes -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Ventes ce mois
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="monthly-sales">€245,780</div>
                                        <div class="text-xs text-success">
                                            <i class="fas fa-arrow-up"></i> +15.2% vs mois dernier
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
                                            Nombre de ventes
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="sales-count">187</div>
                                        <div class="text-xs text-success">
                                            <i class="fas fa-arrow-up"></i> +8% vs mois dernier
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
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
                                            Ticket moyen
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="avg-ticket">€1,314</div>
                                        <div class="text-xs text-success">
                                            <i class="fas fa-arrow-up"></i> +6.8% vs mois dernier
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calculator fa-2x text-gray-300"></i>
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
                                            Objectif atteint
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="goal-achieved">78%</div>
                                        <div class="progress progress-sm mt-2">
                                            <div class="progress-bar bg-warning" style="width: 78%"></div>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-target fa-2x text-gray-300"></i>
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
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">Évolution des Ventes</h6>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary active" data-period="7d">7j</button>
                                    <button class="btn btn-outline-primary" data-period="30d">30j</button>
                                    <button class="btn btn-outline-primary" data-period="90d">90j</button>
                                    <button class="btn btn-outline-primary" data-period="1y">1an</button>
                                </div>
                            </div>
                            <div class="card-body">
                                <canvas id="sales-evolution-chart" height="300"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Répartition par Produit</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="product-sales-chart" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Performance par commercial -->
                <div class="row mb-4">
                    <div class="col-xl-6">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Performance Équipe</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="team-performance-chart" height="250"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-6">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Tendances Saisonnières</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="seasonal-trends-chart" height="250"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tableau détaillé -->
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Analyse Détaillée par Période</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="salesAnalysisTable">
                                <thead>
                                    <tr>
                                        <th>Période</th>
                                        <th>Ventes (€)</th>
                                        <th>Nombre</th>
                                        <th>Ticket Moyen</th>
                                        <th>Croissance</th>
                                        <th>Top Produit</th>
                                        <th>Meilleur Commercial</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><strong>Cette semaine</strong></td>
                                        <td>€67,450</td>
                                        <td>52</td>
                                        <td>€1,297</td>
                                        <td><span class="text-success">+12%</span></td>
                                        <td>CRM Pro</td>
                                        <td>Marie Martin</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Semaine dernière</strong></td>
                                        <td>€58,920</td>
                                        <td>47</td>
                                        <td>€1,254</td>
                                        <td><span class="text-success">+8%</span></td>
                                        <td>Analytics Plus</td>
                                        <td>Jean Dupont</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Ce mois</strong></td>
                                        <td>€245,780</td>
                                        <td>187</td>
                                        <td>€1,314</td>
                                        <td><span class="text-success">+15%</span></td>
                                        <td>CRM Pro</td>
                                        <td>Marie Martin</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Mois dernier</strong></td>
                                        <td>€213,456</td>
                                        <td>173</td>
                                        <td>€1,234</td>
                                        <td><span class="text-success">+5%</span></td>
                                        <td>Dashboard BI</td>
                                        <td>Pierre Durand</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Ce trimestre</strong></td>
                                        <td>€678,900</td>
                                        <td>524</td>
                                        <td>€1,296</td>
                                        <td><span class="text-success">+18%</span></td>
                                        <td>CRM Pro</td>
                                        <td>Marie Martin</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/analytics-sales.js"></script>
</body>
</html>
