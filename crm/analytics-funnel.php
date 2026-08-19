<?php
include 'includes/verify_subscriptions.php';
include 'includes/missions.php';
include 'includes/customers.php';
include 'includes/leads.php';

$page_title = "Entonnoir de Conversion";
$customer_id = $_SESSION['customer_id'] ?? null;

if (!$customer_id) {
    header('Location: index.php?error=customer');
    exit;
}

// Calculer les statistiques du funnel à partir des données existantes
$funnel_data = [];

try {
    // 1. Contacts/Visiteurs (Leads) - depuis la table leads
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM leads 
        WHERE (customer_id = ? OR assigned_to = ?)
    ");
    $stmt->execute([$customer_id, $_SESSION['user_id'] ?? null]);
    $contacts_count = $stmt->fetch()['count'] ?? 0;
    
    // 2. Leads qualifiés
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM leads 
        WHERE (customer_id = ? OR assigned_to = ?) AND stage = 'qualified'
    ");
    $stmt->execute([$customer_id, $_SESSION['user_id'] ?? null]);
    $qualified_count = $stmt->fetch()['count'] ?? 0;
    
    // 3. Opportunités (Missions) - depuis la table missions
    $opportunities_count = count($missions);
    
    // 4. Clients (Missions complétées/Facturées/Payées)
    $clients_count = count(array_filter($missions, function($m) { 
        return in_array($m['status_name'], ['Terminée', 'Facturée', 'Payée']); 
    }));
    
    // 5. Prospects (depuis companies)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM companies 
        WHERE customer_id = ? AND status = 'prospect' AND interne_customer = 0
    ");
    $stmt->execute([$customer_id]);
    $prospects_count = $stmt->fetch()['count'] ?? 0;
    
    // Calcul des taux de conversion
    $conversion_rate_global = $contacts_count > 0 ? round(($clients_count / $contacts_count) * 100, 2) : 0;
    $conversion_rate_qualified = $contacts_count > 0 ? round(($qualified_count / $contacts_count) * 100, 2) : 0;
    $conversion_rate_opportunities = $qualified_count > 0 ? round(($opportunities_count / $qualified_count) * 100, 2) : 0;
    $conversion_rate_clients = $opportunities_count > 0 ? round(($clients_count / $opportunities_count) * 100, 2) : 0;
    
    $funnel_data = [
        'contacts' => $contacts_count,
        'qualified' => $qualified_count,
        'opportunities' => $opportunities_count,
        'clients' => $clients_count,
        'prospects' => $prospects_count,
        'conversion_global' => $conversion_rate_global,
        'conversion_qualified' => $conversion_rate_qualified,
        'conversion_opportunities' => $conversion_rate_opportunities,
        'conversion_clients' => $conversion_rate_clients,
        'lost_opportunities' => $opportunities_count - $clients_count
    ];
    
} catch (Exception $e) {
    error_log("Erreur lors du calcul du funnel: " . $e->getMessage());
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
    <link href="assets/css/analytics-funnel.css" rel="stylesheet">
</head>

<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        <?php include 'includes/topbar.php'; ?>
        
        <div class="main-content">
            <div class="container-fluid">

                <!-- Page Header -->
                <div class="page-header">
                    <h1>
                        <i class="fas fa-filter"></i> Entonnoir de Conversion
                    </h1>
                    <div class="btn-group">
                        <button class="btn btn-primary btn-sm" onclick="location.reload()">
                            <i class="fas fa-sync-alt"></i> Actualiser
                        </button>
                        <button class="btn btn-outline-primary btn-sm" onclick="exportFunnelReport()">
                            <i class="fas fa-download"></i> Exporter
                        </button>
                    </div>
                </div>

            <!-- KPI Metrics -->
            <div class="kpi-container">
                <div class="kpi-card primary">
                    <div class="kpi-icon"><i class="fas fa-percentage"></i></div>
                    <div class="kpi-value"><?php echo $funnel_data['conversion_global']; ?>%</div>
                    <div class="kpi-label">Taux Global</div>
                </div>
                
                <div class="kpi-card success">
                    <div class="kpi-icon"><i class="fas fa-users"></i></div>
                    <div class="kpi-value"><?php echo $funnel_data['contacts']; ?></div>
                    <div class="kpi-label">Visiteurs</div>
                </div>
                
                <div class="kpi-card info">
                    <div class="kpi-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="kpi-value"><?php echo $funnel_data['qualified']; ?></div>
                    <div class="kpi-label">Qualifiés</div>
                </div>
                
                <div class="kpi-card warning">
                    <div class="kpi-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="kpi-value"><?php echo $funnel_data['lost_opportunities']; ?></div>
                    <div class="kpi-label">Perdues</div>
                </div>
            </div>

            <!-- Funnel Visualization -->
            <div class="funnel-container-main">
                <div class="funnel-box">
                    <div class="funnel-header">
                        <h5><i class="fas fa-funnel"></i> Visualisation de l'Entonnoir</h5>
                    </div>
                    <div class="funnel-stages">
                        <!-- Stage 1: Visitors -->
                        <div class="funnel-stage stage-1" style="width: 100%;">
                            <div class="stage-label">
                                <div class="stage-title">Visiteurs (Leads)</div>
                                <div class="stage-count"><?php echo $funnel_data['contacts']; ?></div>
                                <div class="stage-percent">100%</div>
                            </div>
                        </div>
                        
                        <!-- Stage 2: Qualified -->
                        <?php if ($funnel_data['contacts'] > 0) { 
                            $percent2 = ($funnel_data['qualified'] / $funnel_data['contacts']) * 100;
                            $width2 = max(20, $percent2);
                        ?>
                        <div class="funnel-stage stage-2" style="width: <?php echo $width2; ?>%;">
                            <div class="stage-label">
                                <div class="stage-title">Qualifiés</div>
                                <div class="stage-count"><?php echo $funnel_data['qualified']; ?></div>
                                <div class="stage-percent"><?php echo round($percent2, 1); ?>%</div>
                            </div>
                        </div>
                        <?php } ?>
                        
                        <!-- Stage 3: Opportunities -->
                        <?php if ($funnel_data['qualified'] > 0) { 
                            $percent3 = ($funnel_data['opportunities'] / $funnel_data['qualified']) * 100;
                            $width3 = max(20, $percent3);
                        ?>
                        <div class="funnel-stage stage-3" style="width: <?php echo $width3; ?>%;">
                            <div class="stage-label">
                                <div class="stage-title">Opportunités</div>
                                <div class="stage-count"><?php echo $funnel_data['opportunities']; ?></div>
                                <div class="stage-percent"><?php echo round($percent3, 1); ?>%</div>
                            </div>
                        </div>
                        <?php } ?>
                        
                        <!-- Stage 4: Clients -->
                        <?php if ($funnel_data['opportunities'] > 0) { 
                            $percent4 = ($funnel_data['clients'] / $funnel_data['opportunities']) * 100;
                            $width4 = max(20, $percent4);
                        ?>
                        <div class="funnel-stage stage-4" style="width: <?php echo $width4; ?>%;">
                            <div class="stage-label">
                                <div class="stage-title">facturations clients</div>
                                <div class="stage-count"><?php echo $funnel_data['clients']; ?></div>
                                <div class="stage-percent"><?php echo round($percent4, 1); ?>%</div>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <!-- Conversion Breakdown -->
            <div class="conversion-breakdown">
                <div class="breakdown-box">
                    <div class="breakdown-header">
                        <h5><i class="fas fa-chart-bar"></i> Analyse des Conversions</h5>
                    </div>
                    <div class="breakdown-grid">
                        <div class="breakdown-item">
                            <div class="breakdown-title">Leads → Qualifiés</div>
                            <div class="breakdown-rate"><?php echo $funnel_data['conversion_qualified']; ?>%</div>
                            <div class="breakdown-count"><?php echo $funnel_data['qualified']; ?> / <?php echo $funnel_data['contacts']; ?></div>
                        </div>
                        
                        <div class="breakdown-item">
                            <div class="breakdown-title">Qualifiés → Opportunités</div>
                            <div class="breakdown-rate"><?php echo $funnel_data['conversion_opportunities']; ?>%</div>
                            <div class="breakdown-count"><?php echo $funnel_data['opportunities']; ?> / <?php echo $funnel_data['qualified']; ?></div>
                        </div>
                        
                        <div class="breakdown-item">
                            <div class="breakdown-title">Opportunités → Clients</div>
                            <div class="breakdown-rate"><?php echo $funnel_data['conversion_clients']; ?>%</div>
                            <div class="breakdown-count"><?php echo $funnel_data['clients']; ?> / <?php echo $funnel_data['opportunities']; ?></div>
                        </div>
                        
                        <div class="breakdown-item">
                            <div class="breakdown-title">Global (Leads → Clients)</div>
                            <div class="breakdown-rate"><?php echo $funnel_data['conversion_global']; ?>%</div>
                            <div class="breakdown-count"><?php echo $funnel_data['clients']; ?> / <?php echo $funnel_data['contacts']; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Friction Points -->
            <div class="friction-section">
                <div class="friction-box">
                    <div class="friction-header">
                        <h5><i class="fas fa-exclamation-circle"></i> Points de Friction Identifiés</h5>
                    </div>
                    <div class="friction-list">
                        <?php
                        $friction_points = [];
                        
                        if ($funnel_data['qualified'] > 0 && $funnel_data['conversion_qualified'] < 50) {
                            $friction_points[] = [
                                'stage' => 'Qualification des leads',
                                'impact' => 'Taux de conversion faible',
                                'severity' => 'high',
                                'suggestion' => 'Revoir les critères de qualification'
                            ];
                        }
                        
                        if ($funnel_data['opportunities'] > 0 && $funnel_data['conversion_opportunities'] < 70) {
                            $friction_points[] = [
                                'stage' => 'Conversion en opportunités',
                                'impact' => 'Beaucoup de leads perdus',
                                'severity' => 'medium',
                                'suggestion' => 'Améliorer le suivi des leads'
                            ];
                        }
                        
                        if ($funnel_data['clients'] > 0 && $funnel_data['conversion_clients'] < 60) {
                            $friction_points[] = [
                                'stage' => 'Fermeture des ventes',
                                'impact' => 'Taux de conclusion faible',
                                'severity' => 'high',
                                'suggestion' => 'Optimiser la proposition commerciale'
                            ];
                        }
                        
                        if ($funnel_data['lost_opportunities'] > ($funnel_data['opportunities'] * 0.5)) {
                            $friction_points[] = [
                                'stage' => 'Abandons globaux',
                                'impact' => 'Plus de 50% d\'abandons',
                                'severity' => 'critical',
                                'suggestion' => 'Analyse approfondie recommandée'
                            ];
                        }
                        
                        if (empty($friction_points)) {
                            echo '<div class="no-friction"><i class="fas fa-check-circle"></i> Aucun point de friction majeur détecté</div>';
                        } else {
                            foreach ($friction_points as $point) {
                                $severity_class = 'severity-' . $point['severity'];
                                echo '
                                <div class="friction-item ' . $severity_class . '">
                                    <div class="friction-left">
                                        <div class="friction-stage">' . $point['stage'] . '</div>
                                        <div class="friction-impact">' . $point['impact'] . '</div>
                                    </div>
                                    <div class="friction-right">
                                        <div class="friction-badge">' . strtoupper($point['severity']) . '</div>
                                        <div class="friction-suggestion">' . $point['suggestion'] . '</div>
                                    </div>
                                </div>
                                ';
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Summary Table -->
            <div class="summary-section">
                <div class="summary-box">
                    <div class="summary-header">
                        <h5><i class="fas fa-table"></i> Résumé par Étape</h5>
                    </div>
                    <table class="summary-table">
                        <thead>
                            <tr>
                                <th>Étape</th>
                                <th>Nombre</th>
                                <th>% du Total</th>
                                <th>Taux de Conversion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="badge badge-primary">Visiteurs</span></td>
                                <td><?php echo $funnel_data['contacts']; ?></td>
                                <td>100%</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-info">Qualifiés</span></td>
                                <td><?php echo $funnel_data['qualified']; ?></td>
                                <td><?php echo $funnel_data['contacts'] > 0 ? round(($funnel_data['qualified'] / $funnel_data['contacts']) * 100, 1) : 0; ?>%</td>
                                <td><?php echo $funnel_data['conversion_qualified']; ?>%</td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-warning">Opportunités</span></td>
                                <td><?php echo $funnel_data['opportunities']; ?></td>
                                <td><?php echo $funnel_data['contacts'] > 0 ? round(($funnel_data['opportunities'] / $funnel_data['contacts']) * 100, 1) : 0; ?>%</td>
                                <td><?php echo $funnel_data['conversion_opportunities']; ?>%</td>
                            </tr>
                            <tr class="total-row">
                                <td><span class="badge badge-success">Clients</span></td>
                                <td><?php echo $funnel_data['clients']; ?></td>
                                <td><?php echo $funnel_data['contacts'] > 0 ? round(($funnel_data['clients'] / $funnel_data['contacts']) * 100, 1) : 0; ?>%</td>
                                <td><?php echo $funnel_data['conversion_clients']; ?>%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <!-- Scripts -->
    <script src="assets/vendor/jquery/jquery.min.js"></script>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="assets/js/sb-admin-2.min.js"></script>
    <script src="assets/js/analytics-funnel.js"></script>
    
    <script>
    function exportFunnelReport() {
        const data = {
            funnel: <?php echo json_encode($funnel_data); ?>,
            generatedAt: new Date().toLocaleString('fr-FR')
        };
        
        let content = "RAPPORT - ENTONNOIR DE CONVERSION\n";
        content += "Généré le: " + data.generatedAt + "\n\n";
        content += "STATISTIQUES GLOBALES\n";
        content += "=" .repeat(50) + "\n";
        content += "Taux de conversion global: " + data.funnel.conversion_global + "%\n";
        content += "Leads: " + data.funnel.contacts + "\n";
        content += "Qualifiés: " + data.funnel.qualified + "\n";
        content += "Opportunités: " + data.funnel.opportunities + "\n";
        content += "Clients: " + data.funnel.clients + "\n";
        content += "Opportunités perdues: " + data.funnel.lost_opportunities + "\n\n";
        content += "DÉTAIL DES CONVERSIONS\n";
        content += "=" .repeat(50) + "\n";
        content += "Leads → Qualifiés: " + data.funnel.conversion_qualified + "%\n";
        content += "Qualifiés → Opportunités: " + data.funnel.conversion_opportunities + "%\n";
        content += "Opportunités → Clients: " + data.funnel.conversion_clients + "%\n";
        
        const blob = new Blob([content], { type: 'text/plain' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'rapport-funnel-' + new Date().getTime() + '.txt';
        link.click();
    }
    </script>
            </div>
        </div>
    </div>
</body>
</html>
