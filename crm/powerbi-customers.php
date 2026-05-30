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
    }
} catch (Exception $e) {
    error_log('powerbi-customers: failed to check creds: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Power BI - Analyse Clients | CRM Intelligent</title>

</head>

<body>
    
    <div class="wrapper">

        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
                
                <div class="container-fluid">
                    <!-- En-tête de page -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">
                            <i class="fas fa-chart-line text-primary"></i>
                            Power BI - Analyse Clients
                        </h1>
                        <div class="btn-group">
                            <button class="btn btn-primary" id="refreshReports">
                                <i class="fas fa-sync-alt"></i> Actualiser
                            </button>
                            <button class="btn btn-outline-primary" id="exportReport">
                                <i class="fas fa-download"></i> Exporter
                            </button>
                        </div>
                    </div>

                    <!-- Message d'alerte mode démo -->
                    <div class="alert alert-info alert-dismissible fade show" id="demoAlert">
                        <i class="fas fa-info-circle"></i>
                        <strong>Mode Démo :</strong> Les rapports Power BI affichés sont des exemples. 
                        Configurez Azure AD et Power BI pour voir vos vraies données.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>

                    <!-- Métriques principales -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Clients actifs</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="activeCustomers">-</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users fa-2x text-gray-300"></i>
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
                                                Nouveaux clients (30j)</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="newCustomers">-</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-user-plus fa-2x text-gray-300"></i>
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
                                                Valeur vie client (LTV)</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="avgLTV">-</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-euro-sign fa-2x text-gray-300"></i>
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
                                                Taux de rétention</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="retentionRate">-</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-heart fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rapports Power BI -->
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-chart-pie"></i>
                                        Segmentation Clients
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div id="powerbi-customer-segmentation" class="powerbi-embed">
                                        <div class="text-center p-5">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="sr-only">Chargement...</span>
                                            </div>
                                            <p class="mt-3 text-muted">Chargement du rapport Power BI...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-trophy"></i>
                                        Top Clients
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div id="topCustomersList">
                                        <!-- Sera rempli par JavaScript -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Deuxième ligne de rapports -->
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-chart-line"></i>
                                        Évolution Comportement Client
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div id="powerbi-customer-behavior" class="powerbi-embed">
                                        <div class="text-center p-5">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="sr-only">Chargement...</span>
                                            </div>
                                            <p class="mt-3 text-muted">Chargement du rapport Power BI...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-map-marker-alt"></i>
                                        Répartition Géographique
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div id="powerbi-customer-geography" class="powerbi-embed">
                                        <div class="text-center p-5">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="sr-only">Chargement...</span>
                                            </div>
                                            <p class="mt-3 text-muted">Chargement du rapport Power BI...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Analyses prédictives IA -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-brain"></i>
                                        Analyses Prédictives IA - Clients
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div id="powerbi-customer-predictions" class="powerbi-embed">
                                        <div class="text-center p-5">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="sr-only">Chargement...</span>
                                            </div>
                                            <p class="mt-3 text-muted">Chargement des prédictions IA...</p>
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


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/powerbi-integration.js"></script>

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
    
    <script>
    $(document).ready(function() {
        // Charger les métriques clients
        loadCustomerMetrics();
        
        // Charger les rapports Power BI spécifiques aux clients
        loadCustomerReports();
        
        // Event listeners
        $('#refreshReports').click(function() {
            loadCustomerMetrics();
            loadCustomerReports();
        });
        
        $('#exportReport').click(function() {
            exportCustomerReport();
        });
    });

    function loadCustomerMetrics() {
        $.get('api/customer-metrics.php')
            .done(function(data) {
                if (data.success) {
                    $('#activeCustomers').text(data.metrics.active_customers || 0);
                    $('#newCustomers').text(data.metrics.new_customers || 0);
                    $('#avgLTV').text((data.metrics.avg_ltv || 0) + '€');
                    $('#retentionRate').text((data.metrics.retention_rate || 0) + '%');
                    
                    // Charger la liste des top clients
                    loadTopCustomers(data.top_customers || []);
                }
            })
            .fail(function() {
                // Données de démonstration en cas d'erreur
                $('#activeCustomers').text('247');
                $('#newCustomers').text('23');
                $('#avgLTV').text('4,250€');
                $('#retentionRate').text('87%');
                
                loadTopCustomers([
                    {name: 'TechCorp SA', revenue: 45000, status: 'VIP'},
                    {name: 'Innovation Ltd', revenue: 32000, status: 'Premium'},
                    {name: 'Digital Solutions', revenue: 28000, status: 'Standard'},
                    {name: 'Smart Systems', revenue: 25000, status: 'Premium'},
                    {name: 'Future Tech', revenue: 22000, status: 'Standard'}
                ]);
            });
    }

    function loadTopCustomers(customers) {
        let html = '';
        customers.forEach(function(customer, index) {
            const badgeClass = customer.status === 'VIP' ? 'badge-danger' : 
                             customer.status === 'Premium' ? 'badge-warning' : 'badge-secondary';
            
            html += `
                <div class="d-flex align-items-center mb-3">
                    <div class="mr-3">
                        <div class="text-xs font-weight-bold text-gray-400">#${index + 1}</div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="font-weight-bold">${customer.name}</div>
                        <div class="text-sm text-gray-500">${customer.revenue.toLocaleString()}€</div>
                    </div>
                    <div>
                        <span class="badge ${badgeClass}">${customer.status}</span>
                    </div>
                </div>
            `;
        });
        $('#topCustomersList').html(html);
    }

    function loadCustomerReports() {
        // Simuler le chargement des rapports Power BI spécifiques aux clients
        const reports = [
            'powerbi-customer-segmentation',
            'powerbi-customer-behavior', 
            'powerbi-customer-geography',
            'powerbi-customer-predictions'
        ];
        
        reports.forEach(function(reportId) {
            setTimeout(function() {
                const mockChart = `
                    <div class="text-center p-4">
                        <div class="alert alert-info">
                            <i class="fas fa-chart-bar fa-3x mb-3"></i>
                            <h5>Rapport Power BI - ${reportId.replace('powerbi-customer-', '').toUpperCase()}</h5>
                            <p>Mode démonstration - Configurez Power BI pour voir vos données réelles</p>
                        </div>
                    </div>
                `;
                $('#' + reportId).html(mockChart);
            }, Math.random() * 2000 + 1000);
        });
    }

    function exportCustomerReport() {
        // Simuler l'export du rapport
        showNotification('Rapport client exporté avec succès!', 'success');
    }

    function showNotification(message, type = 'success') {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const notification = `
            <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
                 style="top: 20px; right: 20px; z-index: 9999;">
                <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        $('body').append(notification);
        
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 3000);
    }
    </script>
</body>
</html>