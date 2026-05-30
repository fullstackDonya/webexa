// Configurer Chart.js avec les données analytiques

// 1. Graphique d'évolution du pipeline
const pipelineEvolutionCtx = document.getElementById('pipeline-evolution-chart');
if (pipelineEvolutionCtx) {
    new Chart(pipelineEvolutionCtx, {
        type: 'line',
        data: {
            labels: analyticsData.monthlyLabels,
            datasets: [{
                label: 'Valeur du Pipeline',
                data: analyticsData.monthlyEvolution,
                borderColor: 'rgb(78, 115, 223)',
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Valeur: ' + new Intl.NumberFormat('fr-FR', {
                                style: 'currency',
                                currency: 'EUR',
                                maximumFractionDigits: 0
                            }).format(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('fr-FR', {
                                style: 'currency',
                                currency: 'EUR',
                                maximumFractionDigits: 0
                            }).format(value);
                        }
                    }
                }
            }
        }
    });
}

// 2. Graphique de répartition par étape
const stageDistributionCtx = document.getElementById('stage-distribution-chart');
if (stageDistributionCtx) {
    new Chart(stageDistributionCtx, {
        type: 'doughnut',
        data: {
            labels: analyticsData.stageDistribution.labels,
            datasets: [{
                data: analyticsData.stageDistribution.data,
                backgroundColor: [
                    'rgba(78, 115, 223, 0.8)',
                    'rgba(54, 185, 204, 0.8)',
                    'rgba(246, 194, 62, 0.8)',
                    'rgba(133, 135, 150, 0.8)',
                    'rgba(28, 200, 138, 0.8)',
                    'rgba(231, 74, 59, 0.8)'
                ],
                borderColor: [
                    'rgb(78, 115, 223)',
                    'rgb(54, 185, 204)',
                    'rgb(246, 194, 62)',
                    'rgb(133, 135, 150)',
                    'rgb(28, 200, 138)',
                    'rgb(231, 74, 59)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return label + ': ' + value + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
}

// 3. Graphique de performance par commercial
const salespersonPerformanceCtx = document.getElementById('salesperson-performance-chart');
if (salespersonPerformanceCtx) {
    new Chart(salespersonPerformanceCtx, {
        type: 'bar',
        data: {
            labels: analyticsData.userPerformance.labels,
            datasets: [
                {
                    label: 'Opportunités actives',
                    data: analyticsData.userPerformance.counts,
                    backgroundColor: 'rgba(78, 115, 223, 0.8)',
                    borderColor: 'rgb(78, 115, 223)',
                    borderWidth: 1
                },
                {
                    label: 'Opportunités gagnées',
                    data: analyticsData.userPerformance.won,
                    backgroundColor: 'rgba(28, 200, 138, 0.8)',
                    borderColor: 'rgb(28, 200, 138)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

// 4. Graphique d'analyse des sources (données fictives car pas dans la base)
const sourceAnalysisCtx = document.getElementById('source-analysis-chart');
if (sourceAnalysisCtx) {
    new Chart(sourceAnalysisCtx, {
        type: 'pie',
        data: {
            labels: ['Web', 'Référence', 'Direct', 'Publicité', 'Événement'],
            datasets: [{
                data: [30, 25, 20, 15, 10],
                backgroundColor: [
                    'rgba(78, 115, 223, 0.8)',
                    'rgba(54, 185, 204, 0.8)',
                    'rgba(246, 194, 62, 0.8)',
                    'rgba(28, 200, 138, 0.8)',
                    'rgba(231, 74, 59, 0.8)'
                ],
                borderColor: [
                    'rgb(78, 115, 223)',
                    'rgb(54, 185, 204)',
                    'rgb(246, 194, 62)',
                    'rgb(28, 200, 138)',
                    'rgb(231, 74, 59)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            return label + ': ' + value + '%';
                        }
                    }
                }
            }
        }
    });
}

// Fonctions utilitaires
function refreshAnalytics() {
    location.reload();
}

function exportAnalytics() {
    alert('Export des analytics en cours de développement...');
    // TODO: Implémenter l'export PDF/Excel
}

function resetFilters() {
    document.getElementById('period-select').value = 'quarter';
    document.getElementById('salesperson-filter').value = '';
    document.getElementById('sector-filter').value = '';
}

// Gestion des filtres dynamiques
document.getElementById('period-select')?.addEventListener('change', function() {
    console.log('Filtre période changé:', this.value);
    // TODO: Recharger les données avec AJAX
});

document.getElementById('salesperson-filter')?.addEventListener('change', function() {
    console.log('Filtre commercial changé:', this.value);
    // TODO: Recharger les données avec AJAX
});

document.getElementById('sector-filter')?.addEventListener('change', function() {
    console.log('Filtre secteur changé:', this.value);
    // TODO: Recharger les données avec AJAX
});
