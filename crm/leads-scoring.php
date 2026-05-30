<?php
require_once 'includes/verify_subscriptions.php';


?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Intelligent - Dashboard</title>
     <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"></noscript>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Power BI JavaScript SDK -->
    <script src="https://unpkg.com/powerbi-client@2.22.0/dist/powerbi.min.js"></script>
</head>
<body>
    

    <!-- Content Wrapper -->
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        <?php include 'includes/topbar.php'; ?>
        
        <div class="main-content">

            
            <div class="container-fluid">
                <!-- Page Heading -->
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Scoring IA des Leads</h1>
                    <button class="btn btn-primary" id="refreshScoring">
                        <i class="fas fa-sync-alt"></i> Recalculer le scoring
                    </button>
                </div>

                <!-- Scoring Settings Card -->
                <div class="row mb-4">
                    <div class="col-xl-12">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Configuration du Scoring IA</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Score démographique (30%)</label>
                                            <input type="range" class="form-control-range" min="10" max="50" value="30" id="demoWeight">
                                            <small class="text-muted">Basé sur l'âge, localisation, secteur</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Score comportemental (40%)</label>
                                            <input type="range" class="form-control-range" min="20" max="60" value="40" id="behaviorWeight">
                                            <small class="text-muted">Pages vues, emails ouverts, téléchargements</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Score d'engagement (20%)</label>
                                            <input type="range" class="form-control-range" min="10" max="40" value="20" id="engagementWeight">
                                            <small class="text-muted">Interactions, réponses, participation</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Score temporel (10%)</label>
                                            <input type="range" class="form-control-range" min="5" max="20" value="10" id="timeWeight">
                                            <small class="text-muted">Récence des interactions</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Scoring Overview Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Leads Chauds (80-100)</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="hotLeads">23</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-fire fa-2x text-gray-300"></i>
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
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Leads Tièdes (60-79)</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="warmLeads">45</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-thermometer-half fa-2x text-gray-300"></i>
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
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Leads Froids (40-59)</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="coldLeads">67</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-snowflake fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-secondary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Leads Non-qualifiés (&lt;40)</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="unqualifiedLeads">89</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- AI Scoring Chart -->
                <div class="row mb-4">
                    <div class="col-xl-8">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Distribution du Scoring IA</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="scoringChart" width="100%" height="40"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Précision du Modèle IA</h6>
                            </div>
                            <div class="card-body">
                                <div class="text-center">
                                    <div class="progress mb-3">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: 87%" id="modelAccuracy">87%</div>
                                    </div>
                                    <h4 class="small font-weight-bold">Précision actuelle: <span class="float-right">87%</span></h4>
                                    
                                    <div class="mt-4">
                                        <h6 class="font-weight-bold">Dernière mise à jour:</h6>
                                        <p class="text-muted" id="lastUpdate">Il y a 2 heures</p>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <small class="text-muted">
                                            Basé sur 1,247 conversions analysées
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Leads Scoring Table -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Leads avec Scoring IA</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="leadsTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Lead</th>
                                        <th>Score IA</th>
                                        <th>Catégorie</th>
                                        <th>Dernière activité</th>
                                        <th>Source</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="leadsTableBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
            <!-- /.container-fluid -->
        </div>
        <!-- End of Main Content -->
    </div>
    <!-- End of Content Wrapper -->

    <script src="assets/vendor/chart.js/Chart.min.js"></script>
    <script>
    // Graphique de distribution du scoring
    const ctx = document.getElementById('scoringChart').getContext('2d');
    const scoringChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['0-20', '21-40', '41-60', '61-80', '81-100'],
            datasets: [{
                label: 'Nombre de leads',
                data: [12, 89, 67, 45, 23],
                backgroundColor: [
                    '#e74a3b',
                    '#f39c12',
                    '#3498db',
                    '#f39c12',
                    '#27ae60'
                ]
            }]
        },
        options: {
            maintainAspectRatio: false,
            layout: {
                padding: {
                    left: 10,
                    right: 25,
                    top: 25,
                    bottom: 0
                }
            },
            scales: {
                xAxes: [{
                    gridLines: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        maxTicksLimit: 6
                    }
                }],
                yAxes: [{
                    ticks: {
                        maxTicksLimit: 5,
                        padding: 10
                    },
                    gridLines: {
                        color: "rgb(234, 236, 244)",
                        zeroLineColor: "rgb(234, 236, 244)",
                        drawBorder: false,
                        borderDash: [2],
                        zeroLineBorderDash: [2]
                    }
                }]
            },
            legend: {
                display: false
            },
            tooltips: {
                backgroundColor: "rgb(255,255,255)",
                bodyFontColor: "#858796",
                titleMarginBottom: 10,
                titleFontColor: '#6e707e',
                titleFontSize: 14,
                borderColor: '#dddfeb',
                borderWidth: 1,
                xPadding: 15,
                yPadding: 15,
                displayColors: false,
                intersect: false,
                mode: 'index',
                caretPadding: 10
            }
        }
    });

    function categoryForScore(score) {
        if (score === null || score === undefined) return 'Non-qualifié';
        const s = parseInt(score, 10);
        if (s >= 81) return 'Chaud';
        if (s >= 60) return 'Tiède';
        if (s >= 40) return 'Froid';
        return 'Non-qualifié';
    }

    function badgeClassForScore(score) {
        if (score === null || score === undefined) return 'secondary';
        const s = parseInt(score, 10);
        if (s >= 81) return 'success';
        if (s >= 60) return 'warning';
        if (s >= 40) return 'info';
        return 'secondary';
    }

    function progressClassForScore(score) {
        if (score === null || score === undefined) return 'bg-secondary';
        const s = parseInt(score, 10);
        if (s >= 81) return 'bg-success';
        if (s >= 60) return 'bg-warning';
        if (s >= 40) return 'bg-info';
        return 'bg-secondary';
    }

    function initialsFor(name, email) {
        const parts = (name || '').trim().split(/\s+/).filter(Boolean);
        if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
        if (parts.length === 1) return (parts[0][0] || '?').toUpperCase();
        const mail = (email || '').split('@')[0];
        return (mail[0] || '?').toUpperCase();
    }

    async function fetchScoringData() {
        try {
            const res = await fetch('api/leads-scoring.php');
            const data = await res.json();
            if (!data.success) throw new Error(data.error || 'Erreur API');

            const m = data.metrics || {};
            // KPI cards
            document.getElementById('hotLeads').textContent = m.hot ?? 0;
            document.getElementById('warmLeads').textContent = m.warm ?? 0;
            document.getElementById('coldLeads').textContent = m.cold ?? 0;
            document.getElementById('unqualifiedLeads').textContent = m.unqualified ?? 0;

            // Chart update
            if (Array.isArray(m.distribution)) {
                scoringChart.data.datasets[0].data = m.distribution;
                scoringChart.update();
            }

            // Model accuracy + last update
            const acc = parseInt(m.model_accuracy ?? 0, 10);
            const accBar = document.getElementById('modelAccuracy');
            if (accBar) {
                accBar.style.width = acc + '%';
                accBar.textContent = acc + '%';
            }
            const accText = document.querySelector('h4.small.font-weight-bold span.float-right');
            if (accText) accText.textContent = (acc || 0) + '%';

            const last = document.getElementById('lastUpdate');
            if (last && m.last_update) {
                const d = new Date(m.last_update.replace(' ', 'T'));
                last.textContent = d.toLocaleString();
            }

            // Leads table
            const tbody = document.getElementById('leadsTableBody');
            if (tbody) {
                tbody.innerHTML = '';
                (data.leads || []).forEach(lead => {
                    const score = lead.ai_score !== null ? parseInt(lead.ai_score, 10) : null;
                    const badge = badgeClassForScore(score);
                    const pclass = progressClassForScore(score);
                    const category = categoryForScore(score);
                    const name = ((lead.first_name || '') + ' ' + (lead.last_name || '')).trim();
                    const email = lead.email || '';
                    const initials = initialsFor(name, email);
                    const lastActivity = lead.last_activity ? new Date(lead.last_activity.replace(' ', 'T')).toLocaleString() : '';
                    const percent = score !== null ? Math.max(0, Math.min(100, score)) : 0;

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm rounded-circle bg-primary text-white me-2">${initials}</div>
                                <div>
                                    <strong>${name || '(Sans nom)'}</strong><br>
                                    <small class="text-muted">${email}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <span class="badge badge-${badge} me-2">${score !== null ? score : '--'}</span>
                                <div class="progress flex-grow-1" style="height: 8px;">
                                    <div class="progress-bar ${pclass}" style="width: ${percent}%"></div>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge badge-${badge}">${category}</span></td>
                        <td>${lastActivity || ''}</td>
                        <td>${(lead.source || '').toString()}</td>
                        <td>
                            <button class="btn btn-sm btn-primary">Contacter</button>
                            ${category === 'Chaud' ? '<button class="btn btn-sm btn-success ms-1">Convertir</button>' : ''}
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        } catch (e) {
            console.error('Erreur de chargement du scoring:', e);
        }
    }

    // Initial load
    fetchScoringData();

    // Actualisation du scoring
    document.getElementById('refreshScoring').addEventListener('click', function() {
        const btn = this;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Calcul en cours...';
        btn.disabled = true;
        
        // Rafraîchir les données via l'API puis notifier
        fetchScoringData().then(() => {
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-dismissible fade show';
            alert.innerHTML = `
                <strong>Succès!</strong> Le scoring IA a été recalculé/rafraîchi.
                <button type="button" class="close" data-dismiss="alert">
                    <span aria-hidden="true">&times;</span>
                </button>
            `;
            document.querySelector('.container-fluid').insertBefore(alert, document.querySelector('.container-fluid').firstChild);
            setTimeout(() => { alert.remove(); }, 5000);
        }).finally(() => {
            btn.innerHTML = '<i class="fas fa-sync-alt"></i> Recalculer le scoring';
            btn.disabled = false;
        });
    });

    // Mise à jour des poids en temps réel
    document.querySelectorAll('.form-control-range').forEach(range => {
        range.addEventListener('input', function() {
            const total = Array.from(document.querySelectorAll('.form-control-range'))
                .reduce((sum, r) => sum + parseInt(r.value), 0);
            
            if (total !== 100) {
                this.style.borderColor = '#e74a3b';
            } else {
                this.style.borderColor = '#27ae60';
            }
        });
    });
    </script>

</body>
</html>