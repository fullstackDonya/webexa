<?php
require_once 'includes/verify_subscriptions.php';


$page_title = "Insights IA - CRM Intelligent";
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
                        <i class="fas fa-brain text-primary"></i> Insights Intelligence Artificielle
                    </h1>
                    <div>
                        <button class="btn btn-info me-2" onclick="refreshInsights()">
                            <i class="fas fa-sync"></i> Actualiser
                        </button>
                        <button class="btn btn-primary" onclick="generateNewInsights()">
                            <i class="fas fa-magic"></i> Générer Insights
                        </button>
                    </div>
                </div>

                <!-- Alertes IA -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-left-warning shadow">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            <i class="fas fa-exclamation-triangle"></i> Alertes IA du jour
                                        </div>
                                        <div id="ai-alerts">
                                            <!-- Alertes chargées via JavaScript -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KPIs IA -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Leads à Fort Potentiel
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="high-potential-leads">0</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-bullseye fa-2x text-gray-300"></i>
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
                                            Opportunités à Risque
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="at-risk-opportunities">0</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-exclamation-circle fa-2x text-gray-300"></i>
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
                                            Prédiction Revenus
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="revenue-prediction">€0</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-chart-line fa-2x text-gray-300"></i>
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
                                            Score Confiance IA
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="ai-confidence">0%</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-brain fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Insights principaux -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-lightbulb"></i> Recommandations IA
                                </h6>
                            </div>
                            <div class="card-body">
                                <div id="ai-recommendations">
                                    <!-- Recommandations chargées via JavaScript -->
                                </div>
                            </div>
                        </div>

                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-chart-area"></i> Analyses Prédictives
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <canvas id="prediction-chart" width="400" height="200"></canvas>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Prévisions des 3 prochains mois :</h6>
                                        <div id="monthly-predictions">
                                            <!-- Prédictions mensuelles -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-robot"></i> Actions Suggérées
                                </h6>
                            </div>
                            <div class="card-body">
                                <div id="suggested-actions">
                                    <!-- Actions suggérées -->
                                </div>
                            </div>
                        </div>

                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-star"></i> Leads à Scorer
                                </h6>
                            </div>
                            <div class="card-body">
                                <div id="leads-to-score">
                                    <!-- Leads nécessitant un scoring -->
                                </div>
                            </div>
                        </div>

                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-cog"></i> Configuration IA
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Seuil de confidence</label>
                                    <input type="range" class="form-range" id="confidence-threshold" min="0" max="100" value="75">
                                    <div class="form-text">Actuellement : <span id="threshold-value">75%</span></div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="auto-scoring" checked>
                                        <label class="form-check-label" for="auto-scoring">
                                            Scoring automatique des nouveaux leads
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="predictive-alerts" checked>
                                        <label class="form-check-label" for="predictive-alerts">
                                            Alertes prédictives
                                        </label>
                                    </div>
                                </div>

                                <button class="btn btn-primary btn-sm" onclick="saveAISettings()">
                                    <i class="fas fa-save"></i> Enregistrer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/ai-insights.js"></script>
</body>
</html>
