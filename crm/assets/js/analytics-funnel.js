// Analytics Funnel - Script pour l'analyse de l'entonnoir de vente
document.addEventListener('DOMContentLoaded', function() {
    console.log('Analytics Funnel page loaded');

    // Initialisation des graphiques et données
    initFunnelAnalytics();
    loadFunnelData();
    setupFunnelFilters();
});

function initFunnelAnalytics() {
    // Graphique principal de l'entonnoir
    if (document.getElementById('funnelChart')) {
        createFunnelChart();
    }

    // Graphique de conversion par étape
    if (document.getElementById('conversionChart')) {
        createConversionChart();
    }

    // Graphique d'évolution temporelle
    if (document.getElementById('funnelTrendChart')) {
        createFunnelTrendChart();
    }
}

function createFunnelChart() {
    const ctx = document.getElementById('funnelChart').getContext('2d');
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Visiteurs', 'Leads', 'Prospects qualifiés', 'Opportunités', 'Clients'],
            datasets: [{
                label: 'Nombre',
                data: [10000, 2500, 750, 200, 80],
                backgroundColor: [
                    '#e3f2fd',
                    '#bbdefb',
                    '#90caf9',
                    '#64b5f6',
                    '#2196f3'
                ],
                borderColor: '#1976d2',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        afterLabel: function(context) {
                            const index = context.dataIndex;
                            const data = context.dataset.data;
                            if (index > 0) {
                                const prev = data[index - 1];
                                const current = data[index];
                                const conversionRate = ((current / prev) * 100).toFixed(1);
                                return `Taux de conversion: ${conversionRate}%`;
                            }
                            return '';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

function createConversionChart() {
    const ctx = document.getElementById('conversionChart').getContext('2d');
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Visiteurs → Leads', 'Leads → Prospects', 'Prospects → Opportunités', 'Opportunités → Clients'],
            datasets: [{
                label: 'Taux de conversion (%)',
                data: [25, 30, 26.7, 40],
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 50,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y + '%';
                        }
                    }
                }
            }
        }
    });
}

function createFunnelTrendChart() {
    const ctx = document.getElementById('funnelTrendChart').getContext('2d');
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun'],
            datasets: [
                {
                    label: 'Visiteurs',
                    data: [8500, 9200, 10000, 9800, 11200, 10500],
                    borderColor: '#e3f2fd',
                    backgroundColor: 'rgba(227, 242, 253, 0.1)'
                },
                {
                    label: 'Leads',
                    data: [2100, 2300, 2500, 2450, 2800, 2625],
                    borderColor: '#90caf9',
                    backgroundColor: 'rgba(144, 202, 249, 0.1)'
                },
                {
                    label: 'Opportunités',
                    data: [160, 175, 200, 195, 220, 210],
                    borderColor: '#64b5f6',
                    backgroundColor: 'rgba(100, 181, 246, 0.1)'
                },
                {
                    label: 'Clients',
                    data: [64, 70, 80, 78, 88, 84],
                    borderColor: '#2196f3',
                    backgroundColor: 'rgba(33, 150, 243, 0.1)'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            interaction: {
                mode: 'index',
                intersect: false
            }
        }
    });
}

function loadFunnelData() {
    // Chargement des données de l'entonnoir via API
    fetch('api/funnel-analytics.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateFunnelMetrics(data.metrics);
                updateFunnelDetails(data.details);
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des données:', error);
            showDemoFunnelData();
        });
}

function updateFunnelMetrics(metrics) {
    // Mise à jour des métriques principales
    document.getElementById('totalVisitors').textContent = (metrics.visitors || 10000).toLocaleString();
    document.getElementById('totalLeads').textContent = (metrics.leads || 2500).toLocaleString();
    document.getElementById('totalOpportunities').textContent = (metrics.opportunities || 200).toLocaleString();
    document.getElementById('totalCustomers').textContent = (metrics.customers || 80).toLocaleString();
    
    // Calcul et affichage des taux de conversion
    const overallConversion = ((metrics.customers / metrics.visitors) * 100).toFixed(2);
    document.getElementById('overallConversion').textContent = overallConversion + '%';
}

function updateFunnelDetails(details) {
    // Mise à jour du tableau de détails
    const tableBody = document.getElementById('funnelDetailsTable');
    if (!tableBody || !details) return;

    tableBody.innerHTML = details.map(stage => `
        <tr>
            <td>
                <div class="d-flex align-items-center">
                    <div class="stage-icon me-2">
                        <i class="fas ${getStageIcon(stage.name)} text-primary"></i>
                    </div>
                    <strong>${stage.name}</strong>
                </div>
            </td>
            <td>${stage.count.toLocaleString()}</td>
            <td>
                <div class="d-flex align-items-center">
                    <span class="me-2">${stage.conversion}%</span>
                    <div class="progress" style="width: 80px; height: 8px;">
                        <div class="progress-bar" style="width: ${stage.conversion}%"></div>
                    </div>
                </div>
            </td>
            <td>
                <span class="badge bg-${stage.trend > 0 ? 'success' : 'danger'}">
                    <i class="fas fa-arrow-${stage.trend > 0 ? 'up' : 'down'}"></i>
                    ${Math.abs(stage.trend)}%
                </span>
            </td>
            <td>${stage.avgTime}</td>
            <td>
                <button class="btn btn-sm btn-outline-primary" onclick="analyzeStage('${stage.id}')">
                    <i class="fas fa-chart-line"></i> Analyser
                </button>
            </td>
        </tr>
    `).join('');
}

function getStageIcon(stageName) {
    const icons = {
        'Visiteurs': 'fa-users',
        'Leads': 'fa-user-plus',
        'Prospects qualifiés': 'fa-user-check',
        'Opportunités': 'fa-handshake',
        'Clients': 'fa-user-tie'
    };
    return icons[stageName] || 'fa-circle';
}

function showDemoFunnelData() {
    console.log('Mode démo - Données d\'entonnoir d\'exemple');
    updateFunnelMetrics({
        visitors: 10000,
        leads: 2500,
        opportunities: 200,
        customers: 80
    });
}

function setupFunnelFilters() {
    // Configuration des filtres de période
    const periodFilter = document.getElementById('periodFilter');
    if (periodFilter) {
        periodFilter.addEventListener('change', function() {
            loadFunnelData();
        });
    }

    // Configuration du filtre de source
    const sourceFilter = document.getElementById('sourceFilter');
    if (sourceFilter) {
        sourceFilter.addEventListener('change', function() {
            loadFunnelData();
        });
    }

    // Configuration des boutons de comparaison
    const compareButtons = document.querySelectorAll('.compare-period');
    compareButtons.forEach(button => {
        button.addEventListener('click', function() {
            const period = this.dataset.period;
            comparePeriods(period);
        });
    });
}

function comparePeriods(period) {
    console.log('Comparaison avec la période:', period);
    // Logique de comparaison des périodes
    loadFunnelData();
}

function analyzeStage(stageId) {
    console.log('Analyse détaillée de l\'étape:', stageId);
    // Affichage d'une modal ou redirection vers l'analyse détaillée
}

function exportFunnelReport() {
    console.log('Export du rapport d\'entonnoir...');
    // Logique d'export du rapport
}

// Fonctions d'optimisation de l'entonnoir
function suggestOptimizations() {
    // Suggestions d'optimisation basées sur les données
    const suggestions = [
        'Améliorer le taux de conversion Visiteurs → Leads en optimisant les formulaires',
        'Mettre en place du lead nurturing pour améliorer Leads → Prospects',
        'Raccourcir le cycle de vente pour augmenter Opportunités → Clients'
    ];
    
    const container = document.getElementById('optimizationSuggestions');
    if (container) {
        container.innerHTML = suggestions.map(suggestion => `
            <div class="alert alert-info">
                <i class="fas fa-lightbulb me-2"></i>
                ${suggestion}
            </div>
        `).join('');
    }
}
