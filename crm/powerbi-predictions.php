<?php

require_once 'includes/verify_subscriptions.php';



$page_title = "Prédictions IA Power BI";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo $page_title; ?> - CRM Intelligent</title>
    
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
</head>

<body id="page-top">
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="container-fluid">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">
                            <i class="fas fa-brain text-primary"></i>
                            Prédictions IA Power BI
                        </h1>
                        <div class="btn-group">
                            <button class="btn btn-primary btn-sm" onclick="runPredictions()">
                                <i class="fas fa-play"></i> Lancer prédictions
                            </button>
                            <button class="btn btn-outline-primary btn-sm" onclick="schedulePredictions()">
                                <i class="fas fa-calendar"></i> Programmer
                            </button>
                        </div>
                    </div>

                    <!-- Alertes -->
                    <div id="alerts-container"></div>

                    <!-- Statut des modèles IA -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Précision modèle
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="model-accuracy">
                                                <div class="spinner-border spinner-border-sm" role="status"></div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-bullseye fa-2x text-gray-300"></i>
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
                                                Prédictions générées
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="predictions-count">
                                                <div class="spinner-border spinner-border-sm" role="status"></div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
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
                                                Dernière mise à jour
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="last-update">
                                                <div class="spinner-border spinner-border-sm" role="status"></div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Confiance moyenne
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="confidence-score">
                                                <div class="spinner-border spinner-border-sm" role="status"></div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-percentage fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Prédictions de ventes -->
                    <div class="row">
                        <div class="col-xl-8 col-lg-7">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Prédictions de ventes</h6>
                                    <div class="dropdown no-arrow">
                                        <select class="form-control form-control-sm" id="prediction-horizon">
                                            <option value="30d">30 prochains jours</option>
                                            <option value="90d" selected>90 prochains jours</option>
                                            <option value="180d">6 prochains mois</option>
                                            <option value="365d">12 prochains mois</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="salesPredictionChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-4 col-lg-5">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Recommandations IA</h6>
                                </div>
                                <div class="card-body">
                                    <div id="ai-recommendations">
                                        <div class="text-center">
                                            <div class="spinner-border" role="status">
                                                <span class="sr-only">Chargement...</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Prédictions détaillées -->
                    <div class="row">
                        <div class="col-xl-6 col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Prédiction churn clients</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="churnPredictionChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-6 col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Scoring leads</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="leadScoringChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modèles de Machine Learning -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Modèles de Machine Learning</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="ml-models-table" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Modèle</th>
                                                    <th>Type</th>
                                                    <th>Précision</th>
                                                    <th>Dernière formation</th>
                                                    <th>Statut</th>
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

                    <!-- Power BI Dashboard Prédictif -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Dashboard Prédictif Power BI</h6>
                                </div>
                                <div class="card-body">
                                    <div id="powerbi-predictive-container" class="text-center" style="min-height: 600px;">
                                        <div class="d-flex justify-content-center align-items-center h-100">
                                            <div class="spinner-border" role="status">
                                                <span class="sr-only">Chargement du dashboard...</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            

        </div>
    </div>

    <!-- Modal Configuration Modèle -->
    <div class="modal fade" id="modelConfigModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Configuration du modèle</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="model-config-form">
                        <div class="form-group">
                            <label>Nom du modèle</label>
                            <input type="text" class="form-control" id="model-name" required>
                        </div>
                        <div class="form-group">
                            <label>Type de prédiction</label>
                            <select class="form-control" id="prediction-type">
                                <option value="sales">Prédiction de ventes</option>
                                <option value="churn">Prédiction de churn</option>
                                <option value="lead_scoring">Scoring de leads</option>
                                <option value="revenue">Prédiction de revenus</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Période d'entraînement (jours)</label>
                            <input type="number" class="form-control" id="training-period" value="365">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" onclick="trainModel()">Entraîner</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/vendor/jquery/jquery.min.js"></script>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="assets/js/sb-admin-2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/powerbi-predictions.js"></script>
</body>
</html>
