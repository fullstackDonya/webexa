<?php
include 'includes/index.php';
// voir toutes les erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Intelligent - Dashboard Analytics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0"></script>
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .metric-card {
            background: var(--primary-gradient);
            border-radius: 15px;
            padding: 25px;
            color: white;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.1);
            transition: left 0.5s;
        }
        
        .metric-card:hover::before {
            left: 100%;
        }
        
        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
        }
        
        .metric-card.success { background: var(--success-gradient); }
        .metric-card.warning { background: var(--warning-gradient); }
        .metric-card.info { background: var(--info-gradient); }
        
        .metric-card .metric-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 10px 0;
        }
        
        .metric-card .metric-label {
            font-size: 0.9rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .metric-card .metric-trend {
            font-size: 0.85rem;
            margin-top: 10px;
        }
        
        .metric-card .metric-icon {
            font-size: 3rem;
            opacity: 0.2;
            position: absolute;
            right: 20px;
            top: 20px;
        }
        
        .chart-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            transition: box-shadow 0.3s;
        }
        
        .chart-card:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        
        .chart-card h5 {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .activity-item {
            border-left: 3px solid #667eea;
            padding: 15px;
            margin-bottom: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .activity-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }
        
        .filter-pills {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-pill {
            padding: 8px 20px;
            border-radius: 25px;
            border: 2px solid #dee2e6;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .filter-pill.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .filter-pill:hover {
            border-color: #667eea;
            transform: translateY(-2px);
        }
        
        .alert-card {
            border-left: 4px solid #ffc107;
            background: #fff3cd;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .alert-card:hover {
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .deal-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid #28a745;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }
        
        .deal-card:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .mini-stat {
            text-align: center;
            padding: 15px;
        }
        
        .mini-stat .value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #667eea;
        }
        
        .mini-stat .label {
            font-size: 0.8rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .welcome-banner {
            background: var(--primary-gradient);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .welcome-banner h2 {
            margin-bottom: 10px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }
        
        .task-item, .call-item {
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
            transition: all 0.2s;
            cursor: pointer;
        }
        
        .task-item:hover, .call-item:hover {
            background: #f8f9fc;
            transform: translateX(5px);
        }
        
        .task-item:last-child, .call-item:last-child {
            border-bottom: none;
        }
        
        .task-title, .call-contact {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .task-meta, .call-meta {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .priority-badge {
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .priority-urgent { background: #dc3545; color: white; }
        .priority-high { background: #fd7e14; color: white; }
        .priority-medium { background: #ffc107; color: #000; }
        .priority-low { background: #6c757d; color: white; }
        
        .status-badge {
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .status-pending { background: #ffc107; color: #000; }
        .status-completed { background: #198754; color: white; }
        .status-in_progress { background: #0dcaf0; color: white; }
        
        .overdue-badge {
            background: #dc3545;
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: 600;
            animation: pulse 2s infinite;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        <?php include 'includes/topbar.php'; ?>
        
        <div class="main-content">
            <div class="container-fluid">
                <!-- Welcome Banner -->
                <div class="welcome-banner fade-in">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2><i class="fas fa-chart-line"></i> Tableau de Bord Analytics</h2>
                            <p class="mb-0">Aperçu en temps réel de vos performances commerciales et indicateurs clés</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <p class="mb-1"><i class="fas fa-calendar"></i> <?php 
                                setlocale(LC_TIME, 'fr_FR.UTF-8', 'fr_FR', 'fr');
                                echo date('d F Y'); 
                            ?></p>
                            <p class="mb-0"><i class="fas fa-clock"></i> Mis à jour il y a quelques instants</p>
                        </div>
                    </div>
                </div>

                <!-- Filtres Temporels -->
                <div class="filter-pills fade-in">
                    <div class="filter-pill active" onclick="setTimePeriod('today')" id="filter-today">
                        <i class="fas fa-calendar-day"></i> Aujourd'hui
                    </div>
                    <div class="filter-pill" onclick="setTimePeriod('week')" id="filter-week">
                        <i class="fas fa-calendar-week"></i> Cette Semaine
                    </div>
                    <div class="filter-pill" onclick="setTimePeriod('month')" id="filter-month">
                        <i class="fas fa-calendar-alt"></i> Ce Mois
                    </div>
                    <div class="filter-pill" onclick="setTimePeriod('quarter')" id="filter-quarter">
                        <i class="fas fa-calendar"></i> Ce Trimestre
                    </div>
                    <div class="filter-pill" onclick="setTimePeriod('year')" id="filter-year">
                        <i class="fas fa-calendar-check"></i> Cette Année
                    </div>
                </div>

                <!-- KPIs Principaux -->
                <div class="row fade-in">
                    <div class="col-md-3">
                        <div class="metric-card">
                            <i class="fas fa-euro-sign metric-icon"></i>
                            <div class="metric-label">Chiffre d'Affaires</div>
                            <div class="metric-value" id="kpi-revenue">€<?php echo number_format($total_revenue, 0, ',', ' '); ?></div>
                            <div class="metric-trend" id="trend-revenue">
                                <i class="fas fa-arrow-up"></i> +12.5% vs période précédente
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="metric-card success">
                            <i class="fas fa-users metric-icon"></i>
                            <div class="metric-label">Clients Actifs</div>
                            <div class="metric-value" id="kpi-clients"><?php echo $active_clients; ?></div>
                            <div class="metric-trend" id="trend-clients">
                                <i class="fas fa-arrow-up"></i> +8 nouveaux clients
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="metric-card warning">
                            <i class="fas fa-bullseye metric-icon"></i>
                            <div class="metric-label">Opportunités Actives</div>
                            <div class="metric-value" id="kpi-opportunities"><?php echo $opportunities; ?></div>
                            <div class="metric-trend" id="trend-opportunities">
                                <i class="fas fa-chart-line"></i> Valeur totale: €<?php echo number_format($total_revenue * 1.5, 0, ',', ' '); ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="metric-card info">
                            <i class="fas fa-percentage metric-icon"></i>
                            <div class="metric-label">Taux de Conversion</div>
                            <div class="metric-value" id="kpi-conversion"><?php echo number_format($conversion_rate, 1); ?>%</div>
                            <div class="metric-trend" id="trend-conversion">
                                <i class="fas fa-arrow-up"></i> Objectif: 30%
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mini Stats -->
                <div class="row fade-in">
                    <div class="col-md-12">
                        <div class="chart-card">
                            <div class="row text-center">
                                <div class="col-md-2">
                                    <div class="mini-stat">
                                        <div class="value" id="stat-leads">0</div>
                                        <div class="label">Leads Actifs</div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="mini-stat">
                                        <div class="value" id="stat-emails">0</div>
                                        <div class="label">Emails Envoyés</div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="mini-stat">
                                        <div class="value" id="stat-whatsapp">0</div>
                                        <div class="label">Messages WhatsApp</div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="mini-stat">
                                        <div class="value" id="stat-meetings">0</div>
                                        <div class="label">RDV Cette Semaine</div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="mini-stat">
                                        <div class="value" id="stat-tasks">0</div>
                                        <div class="label">Tâches En Cours</div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="mini-stat">
                                        <div class="value" id="stat-pipeline">€0</div>
                                        <div class="label">Pipeline Valeur</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Graphiques Principaux -->
                <div class="row fade-in">
                    <!-- Revenue Evolution -->
                    <div class="col-lg-8">
                        <div class="chart-card">
                            <h5>
                                <i class="fas fa-chart-area text-primary"></i>
                                Évolution du Chiffre d'Affaires (12 derniers mois)
                            </h5>
                            <div style="position: relative; height: 300px; max-height: 300px;">
                                <canvas id="revenueChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Conversion Funnel -->
                    <div class="col-lg-4">
                        <div class="chart-card">
                            <h5>
                                <i class="fas fa-funnel-dollar text-warning"></i>
                                Entonnoir de Conversion
                            </h5>
                            <div style="position: relative; height: 300px; max-height: 300px;">
                                <canvas id="funnelChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Second Row Charts -->
                <div class="row fade-in">
                    <!-- Pipeline Health -->
                    <div class="col-lg-6">
                        <div class="chart-card">
                            <h5>
                                <i class="fas fa-stream text-info"></i>
                                Santé du Pipeline par Étape
                            </h5>
                            <div style="position: relative; height: 280px; max-height: 280px;">
                                <canvas id="pipelineChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Lead Sources -->
                    <div class="col-lg-6">
                        <div class="chart-card">
                            <h5>
                                <i class="fas fa-chart-pie text-success"></i>
                                Leads par Source d'Acquisition
                            </h5>
                            <div style="position: relative; height: 280px; max-height: 280px;">
                                <canvas id="sourcesChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alerts & Top Deals -->
                <div class="row fade-in">
                    <!-- Alertes Intelligentes -->
                    <div class="col-lg-4">
                        <div class="chart-card">
                            <h5>
                                <i class="fas fa-exclamation-triangle text-warning"></i>
                                Alertes & Actions Requises
                            </h5>
                            <div id="alerts-container">
                                <div class="alert-card">
                                    <strong><i class="fas fa-clock"></i> 5 opportunités</strong> sans activité depuis 7 jours
                                    <br>
                                    <a href="opportunities.php?filter=inactive" class="btn btn-sm btn-warning mt-2">
                                        Voir <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                                <div class="alert-card" style="border-left-color: #dc3545; background: #f8d7da;">
                                    <strong><i class="fas fa-fire"></i> 12 leads chauds</strong> (score > 70) à contacter
                                    <br>
                                    <a href="leads.php?filter=hot" class="btn btn-sm btn-danger mt-2">
                                        Contacter <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                                <div class="alert-card" style="border-left-color: #0dcaf0; background: #cff4fc;">
                                    <strong><i class="fas fa-envelope"></i> 3 emails</strong> non lus de prospects
                                    <br>
                                    <a href="email-settings.php" class="btn btn-sm btn-info mt-2">
                                        Lire <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Deals -->
                    <div class="col-lg-4">
                        <div class="chart-card">
                            <h5>
                                <i class="fas fa-trophy text-warning"></i>
                                Top 5 Opportunités
                            </h5>
                            <div id="top-deals-container">
                                <p class="text-muted text-center">Chargement...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Activités Récentes -->
                    <div class="col-lg-4">
                        <div class="chart-card">
                            <h5>
                                <i class="fas fa-history text-info"></i>
                                Activités Récentes
                            </h5>
                            <div id="recent-activities" style="max-height: 400px; overflow-y: auto">
                                <p class="text-muted text-center">Chargement...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tâches & Appels -->
                <div class="row fade-in">
                    <!-- Tâches à Faire -->
                    <div class="col-lg-6">
                        <div class="chart-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">
                                    <i class="fas fa-clipboard-list text-primary"></i>
                                    Tâches à Faire
                                </h5>
                                <a href="tasks.php" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> Voir tout
                                </a>
                            </div>
                            <div id="tasks-list" style="max-height: 400px; overflow-y: auto;">
                                <p class="text-muted text-center">Chargement...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Rappels d'Appels -->
                    <div class="col-lg-6">
                        <div class="chart-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">
                                    <i class="fas fa-phone-alt text-success"></i>
                                    Rappels d'Appels
                                </h5>
                                <a href="calls.php" class="btn btn-sm btn-success">
                                    <i class="fas fa-eye"></i> Voir tout
                                </a>
                            </div>
                            <div id="calls-list" style="max-height: 400px; overflow-y: auto;">
                                <p class="text-muted text-center">Chargement...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Invoices & Missions -->
                <div class="row fade-in">
                    <div class="col-lg-6">
                        <div class="chart-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">
                                    <i class="fas fa-file-invoice text-primary"></i>
                                    Factures Récentes
                                </h5>
                                <button class="btn btn-sm btn-primary" onclick="createInvoice()">
                                    <i class="fas fa-plus"></i> Nouvelle
                                </button>
                            </div>
                            <div id="invoices-list">
                                <p class="text-muted text-center">Chargement...</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="chart-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">
                                    <i class="fas fa-tasks text-success"></i>
                                    Missions Récentes
                                </h5>
                                <button class="btn btn-sm btn-success" onclick="createMission()">
                                    <i class="fas fa-plus"></i> Nouvelle
                                </button>
                            </div>
                            <div id="missions-list">
                                <p class="text-muted text-center">Chargement...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    let currentPeriod = 'month';

    // Set Time Period Filter
    function setTimePeriod(period) {
        currentPeriod = period;
        
        // Update UI
        document.querySelectorAll('.filter-pill').forEach(pill => pill.classList.remove('active'));
        document.getElementById('filter-' + period).classList.add('active');
        
        // Reload data
        loadDashboardData();
    }

    // Load Dashboard Data
    async function loadDashboardData() {
        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000); // 10s timeout
            
            const response = await fetch(`api/dashboard-data.php?period=${currentPeriod}`, {
                signal: controller.signal
            });
            clearTimeout(timeoutId);
            
            const data = await response.json();
            
            if (data.success) {
                updateKPIs(data.kpis);
                updateMiniStats(data.stats);
                updateCharts(data.charts);
                updateTopDeals(data.topDeals);
                updateActivities(data.activities);
            } else {
                console.warn('API returned error:', data.error);
            }
        } catch (error) {
            if (error.name === 'AbortError') {
                console.error('Dashboard API timeout after 10s');
            } else {
                console.error('Error loading dashboard data:', error);
            }
        }
    }

    // Update KPIs
    function updateKPIs(kpis) {
        if (!kpis) return;
        
        if (kpis.revenue !== undefined) {
            document.getElementById('kpi-revenue').textContent = '€' + formatNumber(kpis.revenue);
        }
        if (kpis.clients !== undefined) {
            document.getElementById('kpi-clients').textContent = kpis.clients;
        }
        if (kpis.opportunities !== undefined) {
            document.getElementById('kpi-opportunities').textContent = kpis.opportunities;
        }
        if (kpis.conversion !== undefined) {
            document.getElementById('kpi-conversion').textContent = kpis.conversion.toFixed(1) + '%';
        }
        
        // Update trends
        updateTrend('trend-revenue', kpis.revenueTrend);
        updateTrend('trend-clients', kpis.clientsTrend);
        updateTrend('trend-opportunities', kpis.opportunitiesTrend);
        updateTrend('trend-conversion', kpis.conversionTrend);
    }

    // Update Mini Stats
    function updateMiniStats(stats) {
        if (!stats) return;
        
        document.getElementById('stat-leads').textContent = stats.leads || 0;
        document.getElementById('stat-emails').textContent = stats.emails || 0;
        document.getElementById('stat-whatsapp').textContent = stats.whatsapp || 0;
        document.getElementById('stat-meetings').textContent = stats.meetings || 0;
        document.getElementById('stat-tasks').textContent = stats.tasks || 0;
        document.getElementById('stat-pipeline').textContent = '€' + formatNumber(stats.pipeline || 0);
    }

    // Update Trend Indicator
    function updateTrend(elementId, trendData) {
        const element = document.getElementById(elementId);
        if (!element || !trendData) return;
        
        const icon = trendData.direction === 'up' ? 'arrow-up' : 'arrow-down';
        const color = trendData.direction === 'up' ? '#ffffff' : '#ffdddd';
        element.innerHTML = `<i class="fas fa-${icon}"></i> ${trendData.text}`;
    }

    // Format Number
    function formatNumber(num) {
        return new Intl.NumberFormat('fr-FR').format(Math.round(num));
    }

    // Revenue Chart
    let revenueChart;
    function initRevenueChart(data) {
        const ctx = document.getElementById('revenueChart').getContext('2d');
        
        if (revenueChart) revenueChart.destroy();
        
        revenueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data?.labels || ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'],
                datasets: [{
                    label: 'Chiffre d\'Affaires',
                    data: data?.values || [45000, 52000, 48000, 61000, 55000, 67000, 72000, 68000, 75000, 82000, 79000, 88000],
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 3
                }, {
                    label: 'Objectif',
                    data: data?.target || [50000, 50000, 55000, 55000, 60000, 60000, 65000, 65000, 70000, 70000, 75000, 75000],
                    borderColor: '#f5576c',
                    borderDash: [5, 5],
                    fill: false,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true, position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': €' + formatNumber(context.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '€' + formatNumber(value);
                            }
                        }
                    }
                }
            }
        });
    }

    // Funnel Chart
    let funnelChart;
    function initFunnelChart(data) {
        const ctx = document.getElementById('funnelChart').getContext('2d');
        
        if (funnelChart) funnelChart.destroy();
        
        funnelChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data?.labels || ['Visiteurs', 'Leads', 'Qualifiés', 'Opportunités', 'Clients'],
                datasets: [{
                    label: 'Entonnoir',
                    data: data?.values || [1000, 450, 180, 85, 32],
                    backgroundColor: [
                        'rgba(102, 126, 234, 0.9)',
                        'rgba(102, 126, 234, 0.75)',
                        'rgba(102, 126, 234, 0.6)',
                        'rgba(102, 126, 234, 0.45)',
                        'rgba(102, 126, 234, 0.3)'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Pipeline Chart
    let pipelineChart;
    function initPipelineChart(data) {
        const ctx = document.getElementById('pipelineChart').getContext('2d');
        
        if (pipelineChart) pipelineChart.destroy();
        
        pipelineChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data?.labels || ['Prospection', 'Qualification', 'Proposition', 'Négociation', 'Closing'],
                datasets: [{
                    data: data?.values || [25, 18, 32, 15, 10],
                    backgroundColor: [
                        '#667eea',
                        '#764ba2',
                        '#f093fb',
                        '#4facfe',
                        '#00f2fe'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right' }
                }
            }
        });
    }

    // Sources Chart
    let sourcesChart;
    function initSourcesChart(data) {
        const ctx = document.getElementById('sourcesChart').getContext('2d');
        
        if (sourcesChart) sourcesChart.destroy();
        
        sourcesChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: data?.labels || ['Site Web', 'Référencement', 'Cold Calling', 'Réseaux Sociaux', 'Événements'],
                datasets: [{
                    data: data?.values || [35, 25, 20, 15, 5],
                    backgroundColor: [
                        '#28a745',
                        '#17a2b8',
                        '#ffc107',
                        '#dc3545',
                        '#6c757d'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    // Update Charts
    function updateCharts(chartsData) {
        if (!chartsData) chartsData = {};
        
        initRevenueChart(chartsData.revenue || {});
        initFunnelChart(chartsData.funnel || {});
        initPipelineChart(chartsData.pipeline || {});
        initSourcesChart(chartsData.sources || {});
    }

    // Update Top Deals
    function updateTopDeals(deals) {
        const container = document.getElementById('top-deals-container');
        if (!container) return;
        
        if (!deals || deals.length === 0) {
            container.innerHTML = '<p class="text-muted text-center">Aucune opportunité</p>';
            return;
        }
        
        container.innerHTML = deals.slice(0, 5).map(deal => `
            <div class="deal-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <strong>${escapeHtml(deal.name || 'Sans nom')}</strong>
                        <br><small class="text-muted">${escapeHtml(deal.company || '—')}</small>
                    </div>
                    <div class="text-end">
                        <strong class="text-success">€${formatNumber(deal.amount || 0)}</strong>
                        <br><small class="text-muted">${deal.probability || 0}%</small>
                    </div>
                </div>
            </div>
        `).join('');
    }

    // Update Activities
    function updateActivities(activities) {
        const container = document.getElementById('recent-activities');
        if (!container) return;
        
        if (!activities || activities.length === 0) {
            container.innerHTML = '<p class="text-muted text-center">Aucune activité récente</p>';
            return;
        }
        
        container.innerHTML = activities.slice(0, 10).map(activity => `
            <div class="activity-item">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <i class="fas fa-${activity.icon || 'circle'} me-2"></i>
                        <strong>${escapeHtml(activity.title || '')}</strong>
                        <br><small class="text-muted">${escapeHtml(activity.description || '')}</small>
                    </div>
                    <small class="text-muted">${activity.time || ''}</small>
                </div>
            </div>
        `).join('');
    }

    // Escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Load Invoices
    async function loadInvoices() {
        try {
            const response = await fetch('api/invoices-list.php');
            const data = await response.json();
            
            const container = document.getElementById('invoices-list');
            if (!container) return;
            
            if (!data.success || !data.invoices || data.invoices.length === 0) {
                container.innerHTML = '<p class="text-muted text-center">Aucune facture récente</p>';
                return;
            }
            
            container.innerHTML = data.invoices.slice(0, 5).map(inv => `
                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                    <div>
                        <strong>${escapeHtml(inv.invoice_number || '—')}</strong>
                        <br><small class="text-muted">${escapeHtml(inv.company_name || '')}</small>
                    </div>
                    <div class="text-end">
                        <strong class="text-primary">€${Number(inv.amount || 0).toFixed(2)}</strong>
                        <br><span class="badge bg-${inv.status === 'paid' ? 'success' : 'warning'}">${escapeHtml(inv.status || '')}</span>
                    </div>
                </div>
            `).join('');
        } catch (error) {
            console.error('Error loading invoices:', error);
            const container = document.getElementById('invoices-list');
            if (container) container.innerHTML = '<p class="text-danger text-center">Erreur de chargement</p>';
        }
    }

    // Load Missions
    async function loadMissions() {
        try {
            const response = await fetch('api/missions-list.php');
            const data = await response.json();
            
            const container = document.getElementById('missions-list');
            if (!container) return;
            
            if (!data.success || !data.missions || data.missions.length === 0) {
                container.innerHTML = '<p class="text-muted text-center">Aucune mission récente</p>';
                return;
            }
            
            container.innerHTML = data.missions.slice(0, 5).map(ms => `
                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                    <div>
                        <strong>${escapeHtml(ms.title || '—')}</strong>
                        <br><small class="text-muted">${escapeHtml(ms.assignee || '—')}</small>
                    </div>
                    <div class="text-end">
                        <small class="text-muted">${escapeHtml(ms.due_date || '')}</small>
                        <br><span class="badge bg-${ms.status === 'done' ? 'success' : ms.status === 'pending' ? 'warning' : 'secondary'}">${escapeHtml(ms.status || '')}</span>
                    </div>
                </div>
            `).join('');
        } catch (error) {
            console.error('Error loading missions:', error);
            const container = document.getElementById('missions-list');
            if (container) container.innerHTML = '<p class="text-danger text-center">Erreur de chargement</p>';
        }
    }

    // Load Tasks
    async function loadTasks() {
        try {
            const response = await fetch('api/tasks.php?action=list&filter=active&limit=5');
            const data = await response.json();
            
            const container = document.getElementById('tasks-list');
            if (!container) return;
            
            if (!data.success || !data.tasks || data.tasks.length === 0) {
                container.innerHTML = '<p class="text-muted text-center">Aucune tâche à faire</p>';
                return;
            }
            
            const now = new Date();
            container.innerHTML = data.tasks.map(task => {
                const dueDate = task.due_date ? new Date(task.due_date) : null;
                const isOverdue = dueDate && dueDate < now && task.status !== 'completed';
                const isDueToday = dueDate && dueDate.toDateString() === now.toDateString();
                
                let priorityClass = 'priority-medium';
                if (task.priority === 'urgent') priorityClass = 'priority-urgent';
                else if (task.priority === 'high') priorityClass = 'priority-high';
                else if (task.priority === 'low') priorityClass = 'priority-low';
                
                return `
                    <div class="task-item" onclick="window.location.href='tasks.php?id=${task.id}'">
                        <div class="task-title">
                            ${escapeHtml(task.title || '—')}
                            ${isOverdue ? '<span class="overdue-badge ms-2">RETARD</span>' : ''}
                            ${isDueToday && !isOverdue ? '<span class="badge bg-warning text-dark ms-2">AUJOURD\'HUI</span>' : ''}
                        </div>
                        <div class="task-meta">
                            <span class="priority-badge ${priorityClass}">${task.priority || 'medium'}</span>
                            <span class="ms-2">
                                <i class="fas fa-calendar"></i>
                                ${dueDate ? dueDate.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) : 'Pas d\'échéance'}
                            </span>
                        </div>
                    </div>
                `;
            }).join('');
        } catch (error) {
            console.error('Error loading tasks:', error);
            const container = document.getElementById('tasks-list');
            if (container) container.innerHTML = '<p class="text-danger text-center">Erreur de chargement</p>';
        }
    }

    // Load Calls
    async function loadCalls() {
        try {
            const response = await fetch('api/calls.php?action=list&filter=pending&limit=5');
            const data = await response.json();
            
            const container = document.getElementById('calls-list');
            if (!container) return;
            
            if (!data.success || !data.calls || data.calls.length === 0) {
                container.innerHTML = '<p class="text-muted text-center">Aucun rappel en attente</p>';
                return;
            }
            
            const now = new Date();
            container.innerHTML = data.calls.map(call => {
                const scheduledTime = new Date(call.scheduled_time);
                const isOverdue = scheduledTime < now;
                const isToday = scheduledTime.toDateString() === now.toDateString();
                
                let statusClass = 'status-pending';
                if (call.status === 'completed') statusClass = 'status-completed';
                
                return `
                    <div class="call-item" onclick="window.location.href='calls.php?id=${call.id}'">
                        <div class="call-contact">
                            <i class="fas fa-phone text-success me-2"></i>
                            ${escapeHtml(call.contact_name || '—')}
                            ${isOverdue ? '<span class="overdue-badge ms-2">RETARD</span>' : ''}
                            ${isToday && !isOverdue ? '<span class="badge bg-info ms-2">AUJOURD\'HUI</span>' : ''}
                        </div>
                        <div class="call-meta">
                            <span class="status-badge ${statusClass}">${call.call_type || 'follow_up'}</span>
                            <span class="ms-2">
                                <i class="fas fa-calendar"></i>
                                ${scheduledTime.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' })}
                            </span>
                            <span class="ms-2">
                                <i class="fas fa-mobile-alt"></i>
                                ${escapeHtml(call.phone || '')}
                            </span>
                        </div>
                    </div>
                `;
            }).join('');
        } catch (error) {
            console.error('Error loading calls:', error);
            const container = document.getElementById('calls-list');
            if (container) container.innerHTML = '<p class="text-danger text-center">Erreur de chargement</p>';
        }
    }

    // Quick Actions
    window.createInvoice = function() {
        window.location.href = 'generate_invoice.php';
    };

    window.createMission = function() {
        window.location.href = 'mission_add.php';
    };

    // Initialize Dashboard
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize charts with default data first (fast)
        updateCharts({});
        
        // Load data progressively to avoid blocking
        setTimeout(() => loadInvoices(), 100);
        setTimeout(() => loadMissions(), 200);
        setTimeout(() => loadTasks(), 250);
        setTimeout(() => loadCalls(), 300);
        setTimeout(() => loadDashboardData(), 350);
        
        // Refresh every 5 minutes
        setInterval(() => {
            loadDashboardData();
            loadInvoices();
            loadMissions();
            loadTasks();
            loadCalls();
        }, 300000);
    });
    </script>
</body>
</html>
                   