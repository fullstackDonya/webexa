// Power BI Performance - Script pour l'intégration des rapports de performance Power BI
document.addEventListener('DOMContentLoaded', function() {
    console.log('Power BI Performance page loaded');

    // Initialisation des éléments Power BI
    initPowerBIPerformance();
    loadPerformanceReports();
    setupPerformanceFilters();
});

function initPowerBIPerformance() {
    // Vérification de la disponibilité de Power BI
    checkPowerBIAvailability();
    
    // Initialisation des conteneurs de rapports
    initReportContainers();
    
    // Configuration des boutons d'interaction
    setupReportControls();
}

function checkPowerBIAvailability() {
    // Vérification si Power BI est configuré
    fetch('api/powerbi-token.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.token) {
                loadPowerBIReports(data.token);
            } else {
                console.log('Power BI non configuré, passage en mode démo');
                loadDemoPerformanceCharts();
            }
        })
        .catch(error => {
            console.error('Erreur Power BI:', error);
            loadDemoPerformanceCharts();
        });
}

function loadPowerBIReports(token) {
    // Configuration Power BI
    const models = window['powerbi-client'].models;
    
    // Configuration du rapport de performance globale
    const performanceConfig = {
        type: 'report',
        tokenType: models.TokenType.Embed,
        accessToken: token,
        embedUrl: 'https://app.powerbi.com/reportEmbed?reportId=performance-report-id',
        id: 'performance-report-id',
        permissions: models.Permissions.Read,
        settings: {
            filterPaneEnabled: false,
            navContentPaneEnabled: true,
            background: models.BackgroundType.Transparent
        }
    };

    // Intégration du rapport principal
    const performanceContainer = document.getElementById('powerbi-performance-report');
    if (performanceContainer) {
        powerbi.embed(performanceContainer, performanceConfig);
    }

    // Configuration du rapport KPIs
    const kpiConfig = {
        type: 'report',
        tokenType: models.TokenType.Embed,
        accessToken: token,
        embedUrl: 'https://app.powerbi.com/reportEmbed?reportId=kpi-report-id',
        id: 'kpi-report-id',
        permissions: models.Permissions.Read,
        settings: {
            filterPaneEnabled: false,
            navContentPaneEnabled: false,
            background: models.BackgroundType.Transparent
        }
    };

    const kpiContainer = document.getElementById('powerbi-kpi-dashboard');
    if (kpiContainer) {
        powerbi.embed(kpiContainer, kpiConfig);
    }
}

function loadDemoPerformanceCharts() {
    console.log('Chargement des graphiques de démonstration Power BI Performance');
    
    // Création des graphiques de démonstration
    createPerformanceOverviewChart();
    createKPIChart();
    createTeamPerformanceChart();
    createGoalsProgressChart();
    
    // Affichage du message de mode démo
    showDemoMessage();
}

function createPerformanceOverviewChart() {
    const ctx = document.getElementById('performanceOverviewChart');
    if (!ctx) return;
    
    new Chart(ctx.getContext('2d'), {
        type: 'line',
        data: {
            labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'],
            datasets: [
                {
                    label: 'Chiffre d\'affaires',
                    data: [65000, 72000, 68000, 75000, 82000, 88000, 92000, 89000, 95000, 98000, 103000, 110000],
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    tension: 0.4
                },
                {
                    label: 'Objectif',
                    data: [70000, 70000, 75000, 75000, 80000, 80000, 85000, 85000, 90000, 90000, 95000, 95000],
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    borderDash: [5, 5],
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Performance vs Objectifs - Vue d\'ensemble'
                },
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    ticks: {
                        callback: function(value) {
                            return '€' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

function createKPIChart() {
    const ctx = document.getElementById('kpiChart');
    if (!ctx) return;
    
    new Chart(ctx.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Atteint', 'En cours', 'À risque'],
            datasets: [{
                data: [65, 25, 10],
                backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Répartition des KPIs'
                },
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

function createTeamPerformanceChart() {
    const ctx = document.getElementById('teamPerformanceChart');
    if (!ctx) return;
    
    new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: ['Équipe A', 'Équipe B', 'Équipe C', 'Équipe D', 'Équipe E'],
            datasets: [
                {
                    label: 'Performance actuelle (%)',
                    data: [95, 87, 102, 78, 91],
                    backgroundColor: function(context) {
                        const value = context.parsed.y;
                        return value >= 100 ? '#28a745' : 
                               value >= 80 ? '#ffc107' : '#dc3545';
                    }
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Performance par équipe'
                },
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 120,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });
}

function createGoalsProgressChart() {
    const ctx = document.getElementById('goalsProgressChart');
    if (!ctx) return;
    
    new Chart(ctx.getContext('2d'), {
        type: 'horizontalBar',
        data: {
            labels: ['Ventes Q4', 'Nouveaux clients', 'Rétention', 'Satisfaction', 'Productivité'],
            datasets: [{
                label: 'Progression (%)',
                data: [85, 92, 78, 88, 95],
                backgroundColor: [
                    '#007bff',
                    '#28a745',
                    '#ffc107',
                    '#17a2b8',
                    '#6f42c1'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                title: {
                    display: true,
                    text: 'Progression des objectifs'
                },
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });
}

function showDemoMessage() {
    const demoAlert = document.getElementById('demo-alert');
    if (demoAlert) {
        demoAlert.style.display = 'block';
    }
}

function initReportContainers() {
    // Initialisation des conteneurs pour les rapports Power BI
    const containers = [
        'powerbi-performance-report',
        'powerbi-kpi-dashboard',
        'powerbi-team-metrics',
        'powerbi-goals-tracking'
    ];
    
    containers.forEach(containerId => {
        const container = document.getElementById(containerId);
        if (container && !container.hasChildNodes()) {
            container.innerHTML = `
                <div class="text-center p-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mt-2 text-muted">Chargement du rapport Power BI...</p>
                </div>
            `;
        }
    });
}

function setupReportControls() {
    // Configuration des boutons de contrôle des rapports
    const refreshButton = document.getElementById('refreshReports');
    if (refreshButton) {
        refreshButton.addEventListener('click', function() {
            refreshAllReports();
        });
    }
    
    const fullscreenButtons = document.querySelectorAll('.fullscreen-report');
    fullscreenButtons.forEach(button => {
        button.addEventListener('click', function() {
            const reportId = this.dataset.reportId;
            openReportFullscreen(reportId);
        });
    });
    
    const exportButtons = document.querySelectorAll('.export-report');
    exportButtons.forEach(button => {
        button.addEventListener('click', function() {
            const reportId = this.dataset.reportId;
            exportReport(reportId);
        });
    });
}

function loadPerformanceReports() {
    // Chargement des données de performance
    fetch('api/performance-data.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updatePerformanceMetrics(data.metrics);
                updatePerformanceTable(data.details);
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des données:', error);
            loadDemoPerformanceData();
        });
}

function updatePerformanceMetrics(metrics) {
    // Mise à jour des métriques de performance
    document.getElementById('overallPerformance').textContent = (metrics.overall || 87) + '%';
    document.getElementById('monthlyGrowth').textContent = '+' + (metrics.growth || 12.5) + '%';
    document.getElementById('goalsAchieved').textContent = (metrics.goalsAchieved || 8) + '/10';
    document.getElementById('teamScore').textContent = (metrics.teamScore || 4.3) + '/5';
}

function loadDemoPerformanceData() {
    console.log('Chargement des données de performance de démonstration');
    updatePerformanceMetrics({
        overall: 87,
        growth: 12.5,
        goalsAchieved: 8,
        teamScore: 4.3
    });
}

function setupPerformanceFilters() {
    // Configuration des filtres de période et d'équipe
    const periodFilter = document.getElementById('periodFilter');
    if (periodFilter) {
        periodFilter.addEventListener('change', function() {
            applyPerformanceFilters();
        });
    }
    
    const teamFilter = document.getElementById('teamFilter');
    if (teamFilter) {
        teamFilter.addEventListener('change', function() {
            applyPerformanceFilters();
        });
    }
}

function applyPerformanceFilters() {
    const period = document.getElementById('periodFilter')?.value;
    const team = document.getElementById('teamFilter')?.value;
    
    console.log('Application des filtres:', { period, team });
    loadPerformanceReports();
}

function refreshAllReports() {
    console.log('Actualisation de tous les rapports Power BI...');
    // Logique de rafraîchissement des rapports
    loadPerformanceReports();
}

function openReportFullscreen(reportId) {
    console.log('Ouverture du rapport en plein écran:', reportId);
    // Logique d'ouverture en plein écran
}

function exportReport(reportId) {
    console.log('Export du rapport:', reportId);
    // Logique d'export du rapport
}

// Gestion des événements Power BI
function handlePowerBIEvents() {
    // Écoute des événements des rapports Power BI
    const reports = powerbi.embeds;
    
    reports.forEach(report => {
        report.on('loaded', function() {
            console.log('Rapport Power BI chargé');
        });
        
        report.on('error', function(event) {
            console.error('Erreur Power BI:', event.detail);
            loadDemoPerformanceCharts();
        });
    });
}
