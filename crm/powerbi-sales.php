<?php

require_once 'includes/verify_subscriptions.php';

// Déterminer si les identifiants Power BI sont manquants pour ce customer
$needsPowerBICreds = false;
try {
    $customer_id = $_SESSION['customer_id'] ?? null;
    if ($customer_id) {
        $stmt = $pdo->prepare("SELECT powerbi_client_id, powerbi_client_secret, powerbi_tenant_id FROM customers WHERE id = ?");
        $stmt->execute([$customer_id]);
        $creds = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$creds || empty($creds['powerbi_client_id']) || empty($creds['powerbi_client_secret']) || empty($creds['powerbi_tenant_id'])) {
            $needsPowerBICreds = true;
        }
    } else {
        // Pas de customer lié → on reste en mode démo mais on ne force pas ce modal ici
        $needsPowerBICreds = false;
    }
} catch (Exception $e) {
    // En cas d'erreur, ne pas bloquer l'affichage de la page
    error_log('powerbi-sales: failed to check creds: ' . $e->getMessage());
}

$page_title = "Rapports Power BI - Ventes - CRM Intelligent";
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
    <script src="https://unpkg.com/powerbi-client@2.22.0/dist/powerbi.min.js"></script>
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        <?php include 'includes/topbar.php'; ?>
        
        <div class="main-content">
            
            <div class="container-fluid">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-chart-bar text-primary"></i> Rapports Power BI - Ventes
                    </h1>
                    <div>
                        <button class="btn btn-info me-2" onclick="refreshReport()">
                            <i class="fas fa-sync"></i> Actualiser
                        </button>
                        <button class="btn btn-secondary" onclick="exportReport()">
                            <i class="fas fa-download"></i> Exporter
                        </button>
                    </div>
                </div>

                <!-- Filtres rapides -->
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">Période</label>
                                <select class="form-control" id="period-filter" onchange="applyPeriodFilter()">
                                    <option value="month">Ce mois</option>
                                    <option value="quarter">Ce trimestre</option>
                                    <option value="year">Cette année</option>
                                    <option value="last_month">Mois dernier</option>
                                    <option value="last_quarter">Trimestre dernier</option>
                                    <option value="custom">Personnalisé</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Commercial</label>
                                <select class="form-control" id="salesperson-filter" onchange="applySalespersonFilter()">
                                    <option value="">Tous les commerciaux</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Région</label>
                                <select class="form-control" id="region-filter" onchange="applyRegionFilter()">
                                    <option value="">Toutes les régions</option>
                                    <option value="north">Nord</option>
                                    <option value="south">Sud</option>
                                    <option value="east">Est</option>
                                    <option value="west">Ouest</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Actions</label>
                                <div>
                                    <button class="btn btn-outline-secondary btn-sm" onclick="resetFilters()">
                                        <i class="fas fa-undo"></i> Reset
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rapport Power BI Principal -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-chart-area"></i> Analyse des Ventes
                        </h6>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-cog"></i> Options
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="toggleFullscreen('sales-report')">
                                    <i class="fas fa-expand"></i> Plein écran
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="printReport()">
                                    <i class="fas fa-print"></i> Imprimer
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" onclick="configureReport()">
                                    <i class="fas fa-wrench"></i> Configurer
                                </a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div id="sales-report" style="height: 600px; border: 1px solid #e3e6f0;">
                            <!-- Rapport Power BI ou mode démo -->
                        </div>
                    </div>
                </div>

                <!-- Métriques rapides -->
                <div class="row mb-4">
                    <div class="col-lg-6">
                        <div class="card shadow">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-chart-line"></i> Tendances des Ventes
                                </h6>
                            </div>
                            <div class="card-body">
                                <div id="sales-trends-chart" style="height: 300px;">
                                    <!-- Graphique des tendances -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card shadow">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-chart-pie"></i> Répartition par Produit
                                </h6>
                            </div>
                            <div class="card-body">
                                <div id="product-distribution-chart" style="height: 300px;">
                                    <!-- Graphique en secteurs -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KPIs détaillés -->
                <div class="row">
                    <div class="col-lg-4">
                        <div class="card shadow">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-trophy"></i> Top Performers
                                </h6>
                            </div>
                            <div class="card-body">
                                <div id="top-performers-list">
                                    <!-- Liste des meilleurs commerciaux -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card shadow">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-target"></i> Objectifs vs Réalisé
                                </h6>
                            </div>
                            <div class="card-body">
                                <div id="goals-vs-actual">
                                    <!-- Comparaison objectifs/réalisé -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card shadow">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-calendar"></i> Prévisions
                                </h6>
                            </div>
                            <div class="card-body">
                                <div id="sales-forecast">
                                    <!-- Prévisions de ventes -->
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
    <script src="assets/js/powerbi-integration.js"></script>
    <script src="assets/js/powerbi-sales.js"></script>

    <!-- Modal identifiants Power BI (champs obligatoires uniquement) -->
    <div class="modal fade" id="powerbiCredentialsModal" tabindex="-1" aria-labelledby="powerbiCredentialsLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="powerbiCredentialsLabel">Configurer Power BI</h5>
          </div>
          <div class="modal-body">
            <p class="text-muted mb-3">Renseignez les identifiants Azure AD requis pour afficher vos rapports Power BI.</p>
            <form id="powerbi-credentials-form">
              <div class="mb-3">
                <label class="form-label">Power BI - Client ID</label>
                <input type="text" name="powerbi_client_id" class="form-control" required placeholder="ID client Azure (obligatoire)">
              </div>
              <div class="mb-3">
                <label class="form-label">Power BI - Client Secret</label>
                <input type="password" name="powerbi_client_secret" class="form-control" required placeholder="Secret client (obligatoire)">
              </div>
              <div class="mb-3">
                <label class="form-label">Power BI - Tenant ID</label>
                <input type="text" name="powerbi_tenant_id" class="form-control" required placeholder="Tenant ID (obligatoire)">
              </div>
              <div id="powerbi-credentials-error" class="alert alert-danger d-none" role="alert"></div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Plus tard</button>
            <button type="button" id="savePowerBICredsBtn" class="btn btn-primary">Enregistrer</button>
          </div>
        </div>
      </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
      var shouldOpen = <?php echo $needsPowerBICreds ? 'true' : 'false'; ?>;
      if (shouldOpen) {
        var modalEl = document.getElementById('powerbiCredentialsModal');
        var modal = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false });
        modal.show();
      }

      var saveBtn = document.getElementById('savePowerBICredsBtn');
      var formEl = document.getElementById('powerbi-credentials-form');
      var errorBox = document.getElementById('powerbi-credentials-error');

      function showError(message) {
        errorBox.textContent = message || 'Erreur lors de l\'enregistrement.';
        errorBox.classList.remove('d-none');
      }

      function hideError() {
        errorBox.classList.add('d-none');
        errorBox.textContent = '';
      }

      saveBtn && saveBtn.addEventListener('click', async function() {
        hideError();
        if (!formEl.checkValidity()) {
          formEl.reportValidity();
          return;
        }
        const formData = new FormData(formEl);
        const payload = {
          powerbi_client_id: formData.get('powerbi_client_id'),
          powerbi_client_secret: formData.get('powerbi_client_secret'),
          powerbi_tenant_id: formData.get('powerbi_tenant_id')
        };
        try {
          const resp = await fetch('api/powerbi-credentials.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
          });
          const data = await resp.json();
          if (data && data.success) {
            // Recharge la page pour initialiser Power BI avec les nouveaux identifiants
            window.location.reload();
          } else {
            showError((data && data.error) ? data.error : 'Échec de l\'enregistrement');
          }
        } catch (e) {
          showError('Erreur réseau.');
        }
      });
    });
    </script>
</body>
</html>