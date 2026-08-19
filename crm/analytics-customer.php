<?php

include_once 'includes/verify_subscriptions.php';
// Vérifier l'authentification
// checkAuth();

$page_title = "Comportement Client";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo $page_title; ?> - CRM Intelligent</title>
    
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"></noscript>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>

<body id="page-top">
    
    <div class="wrapper">

        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        <?php include 'includes/topbar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
          
            
            <!-- Dashboard Content -->
            <div class="container-fluid">
                <div class="container-fluid">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">
                            <i class="fas fa-user-analytics text-primary"></i>
                            Analyse du Comportement Client
                        </h1>
                        <div class="btn-group">
                            <button class="btn btn-primary btn-sm" onclick="refreshAnalytics()">
                                <i class="fas fa-sync-alt"></i> Actualiser
                            </button>
                            <button class="btn btn-outline-primary btn-sm" onclick="exportReport()">
                                <i class="fas fa-download"></i> Exporter
                            </button>
                        </div>
                    </div>

                    <!-- Alertes -->
                    <div id="alerts-container"></div>

                    <!-- Métriques comportementales -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                CLV moyen
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="avg-clv">
                                                <div class="spinner-border spinner-border-sm" role="status"></div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-euro-sign fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Taux de rétention
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="retention-rate">
                                                <div class="spinner-border spinner-border-sm" role="status"></div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-heart fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Fréquence d'achat
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="purchase-frequency">
                                                <div class="spinner-border spinner-border-sm" role="status"></div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Score engagement
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="engagement-score">
                                                <div class="spinner-border spinner-border-sm" role="status"></div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-star fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Analyses comportementales -->
                    <div class="row">
                        <div class="col-xl-8 col-lg-7">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Évolution du comportement</h6>
                                    <div class="dropdown no-arrow">
                                        <select class="form-control form-control-sm" id="behavior-period">
                                            <option value="30d">30 derniers jours</option>
                                            <option value="90d" selected>90 derniers jours</option>
                                            <option value="365d">12 derniers mois</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="behaviorTrendChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-4 col-lg-5">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Segments clients</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="segmentChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Analyse RFM -->
                    <div class="row">
                        <div class="col-xl-6 col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Analyse RFM (Récence, Fréquence, Montant)</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="rfmChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-6 col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Risque de churn</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="churnRiskChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Parcours client -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        Parcours Client Type
                                    </h6>
                                </div>

                                <div class="card-body">
                                    <div id="customer-journey" class="text-center">

                                        <div class="row">

                                            <!-- Découverte -->
                                            <div class="col-md-2">
                                                <div class="journey-step">
                                                    <div class="step-icon bg-primary">
                                                        <i class="fas fa-eye text-white"></i>
                                                    </div>

                                                    <h6>Découverte</h6>
                                                    <small class="text-muted">
                                                        Premier contact
                                                    </small>

                                                    <div class="journey-count fw-bold text-primary mt-2">
                                                        0
                                                    </div>
                                                </div>
                                            </div>


                                            <!-- Considération -->
                                            <div class="col-md-2">
                                                <div class="journey-step">
                                                    <div class="step-icon bg-info">
                                                        <i class="fas fa-search text-white"></i>
                                                    </div>

                                                    <h6>Considération</h6>
                                                    <small class="text-muted">
                                                        Évaluation
                                                    </small>

                                                    <div class="journey-count fw-bold text-info mt-2">
                                                        0
                                                    </div>
                                                </div>
                                            </div>


                                            <!-- Achat -->
                                            <div class="col-md-2">
                                                <div class="journey-step">
                                                    <div class="step-icon bg-warning">
                                                        <i class="fas fa-shopping-cart text-white"></i>
                                                    </div>

                                                    <h6>Achat</h6>
                                                    <small class="text-muted">
                                                        Conversion
                                                    </small>

                                                    <div class="journey-count fw-bold text-warning mt-2">
                                                        0
                                                    </div>
                                                </div>
                                            </div>


                                            <!-- Satisfaction -->
                                            <div class="col-md-2">
                                                <div class="journey-step">
                                                    <div class="step-icon bg-success">
                                                        <i class="fas fa-thumbs-up text-white"></i>
                                                    </div>

                                                    <h6>Satisfaction</h6>
                                                    <small class="text-muted">
                                                        Expérience
                                                    </small>

                                                    <div class="journey-count fw-bold text-success mt-2">
                                                        0
                                                    </div>
                                                </div>
                                            </div>


                                            <!-- Fidélisation -->
                                            <div class="col-md-2">
                                                <div class="journey-step">
                                                    <div class="step-icon bg-primary">
                                                        <i class="fas fa-heart text-white"></i>
                                                    </div>

                                                    <h6>Fidélisation</h6>
                                                    <small class="text-muted">
                                                        Rétention
                                                    </small>

                                                    <div class="journey-count fw-bold text-primary mt-2">
                                                        0
                                                    </div>
                                                </div>
                                            </div>


                                            <!-- Ambassadeur -->
                                            <div class="col-md-2">
                                                <div class="journey-step">
                                                    <div class="step-icon bg-secondary">
                                                        <i class="fas fa-share text-white"></i>
                                                    </div>

                                                    <h6>Ambassadeur</h6>
                                                    <small class="text-muted">
                                                        Recommandation
                                                    </small>

                                                    <div class="journey-count fw-bold text-secondary mt-2">
                                                        0
                                                    </div>
                                                </div>
                                            </div>


                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top clients -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Analyse Détaillée des Clients</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="customer-analysis-table" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Client</th>
                                                    <th>Segment</th>
                                                    <th>CLV</th>
                                                    <th>Score RFM</th>
                                                    <th>Risque Churn</th>
                                                    <th>Dernière activité</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Données chargées via JavaScript -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>

    <!-- Modal Détail Client -->
    <div class="modal fade" id="customerDetailModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Analyse Comportementale Détaillée</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="customer-detail-content">
                        <!-- Contenu chargé dynamiquement -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                    <button type="button" class="btn btn-primary" onclick="generatePersonalizedRecommendations()">
                        Générer recommandations
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
 
     <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/analytics-customer.js"></script>
</body>
</html>

