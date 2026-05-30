// Power BI Integration JavaScript

class PowerBIManager {
    constructor() {
        this.powerbi = window['powerbi-client'];
        this.accessToken = null;
        this.embedUrl = null;
        this.reportId = null;
        this.workspaceId = null;
    }

    // Initialisation de Power BI
    async init() {
        try {
            // Récupération du token d'accès depuis le serveur PHP
            const tokenResponse = await fetch('api/powerbi-token.php');
            
            if (!tokenResponse.ok) {
                throw new Error(`Erreur HTTP ${tokenResponse.status}`);
            }
            
            const tokenData = await tokenResponse.json();
            
            if (tokenData.success) {
                this.accessToken = tokenData.accessToken;
                this.embedUrl = tokenData.embedUrl;
                this.reportId = tokenData.reportId;
                this.workspaceId = tokenData.workspaceId;
                
                console.log('Power BI initialisé avec succès');
                return true;
            } else {
                // Si c'est un problème de configuration, activer le mode démo
                if (tokenData.demo_mode) {
                    console.log('Mode démo Power BI activé:', tokenData.message);
                    this.showDemoMode();
                    return false;
                }
                throw new Error(tokenData.error || 'Impossible d\'obtenir le token d\'accès Azure AD');
            }
        } catch (error) {
            console.error('Erreur init Power BI:', error);
            // Activer automatiquement le mode démo en cas d'erreur
            this.showDemoMode();
            return false;
        }
    }

    // Charger un rapport Power BI
    async loadReport(reportId = null, containerId = 'powerbi-container') {
        try {
            if (!this.accessToken) {
                const initSuccess = await this.init();
                if (!initSuccess) {
                    throw new Error('Impossible d\'initialiser Power BI');
                }
            }

            const reportIdToUse = reportId || this.reportId;
            const container = document.getElementById(containerId);

            if (!container) {
                throw new Error(`Container ${containerId} non trouvé`);
            }

            // Configuration du rapport
            const config = {
                type: 'report',
                id: reportIdToUse,
                embedUrl: this.embedUrl,
                accessToken: this.accessToken,
                tokenType: this.powerbi.models.TokenType.Aad,
                settings: {
                    filterPaneEnabled: true,
                    navContentPaneEnabled: true,
                    background: this.powerbi.models.BackgroundType.Transparent,
                    layoutType: this.powerbi.models.LayoutType.Custom,
                    customLayout: {
                        displayOption: this.powerbi.models.DisplayOption.FitToPage
                    }
                }
            };

            // Intégration du rapport
            const report = this.powerbi.embed(container, config);

            // Événements
            report.on('loaded', () => {
                console.log('Rapport Power BI chargé');
                this.hideLoader(containerId);
            });

            report.on('rendered', () => {
                console.log('Rapport Power BI rendu');
            });

            report.on('error', (event) => {
                console.error('Erreur Power BI:', event.detail);
                this.showError(containerId, 'Erreur lors du chargement du rapport');
            });

            return report;

        } catch (error) {
            console.error('Erreur chargement rapport:', error);
            this.showError(containerId, error.message);
            return null;
        }
    }

    // Charger un dashboard Power BI
    async loadDashboard(dashboardId, containerId = 'powerbi-container') {
        try {
            if (!this.accessToken) {
                await this.init();
            }

            const container = document.getElementById(containerId);
            
            const config = {
                type: 'dashboard',
                id: dashboardId,
                embedUrl: `https://app.powerbi.com/dashboardEmbed?dashboardId=${dashboardId}&groupId=${this.workspaceId}`,
                accessToken: this.accessToken,
                tokenType: this.powerbi.models.TokenType.Aad
            };

            const dashboard = this.powerbi.embed(container, config);

            dashboard.on('loaded', () => {
                console.log('Dashboard Power BI chargé');
                this.hideLoader(containerId);
            });

            return dashboard;

        } catch (error) {
            console.error('Erreur chargement dashboard:', error);
            this.showError(containerId, error.message);
            return null;
        }
    }

    // Actualiser le token d'accès
    async refreshToken() {
        try {
            const response = await fetch('api/powerbi-refresh-token.php', {
                method: 'POST'
            });
            const data = await response.json();
            
            if (data.success) {
                this.accessToken = data.accessToken;
                return true;
            }
            return false;
        } catch (error) {
            console.error('Erreur refresh token:', error);
            return false;
        }
    }

    // Appliquer des filtres au rapport
    async applyFilters(report, filters) {
        try {
            await report.setFilters(filters);
            console.log('Filtres appliqués:', filters);
        } catch (error) {
            console.error('Erreur application filtres:', error);
        }
    }

    // Exporter le rapport en PDF
    async exportToPDF(report) {
        try {
            const exportData = await report.exportData(this.powerbi.models.ExportDataType.Summarized);
            console.log('Export PDF généré');
            return exportData;
        } catch (error) {
            console.error('Erreur export PDF:', error);
            return null;
        }
    }

    // Afficher le loader
    showLoader(containerId) {
        const container = document.getElementById(containerId);
        if (container) {
            container.innerHTML = `
                <div class="text-center p-5">
                    <i class="fas fa-spinner fa-spin fa-3x text-muted mb-3"></i>
                    <p>Chargement du rapport Power BI...</p>
                </div>
            `;
        }
    }

    // Masquer le loader
    hideLoader(containerId) {
        // Le loader sera automatiquement remplacé par le rapport
    }

    // Afficher une erreur
    showError(containerId, message) {
        const container = document.getElementById(containerId);
        if (container) {
            container.innerHTML = `
                <div class="text-center p-5">
                    <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                    <p class="text-danger">${message}</p>
                    <button class="btn btn-primary" onclick="powerBIManager.loadReport()">
                        Réessayer
                    </button>
                </div>
            `;
        }
    }

    // Créer des filtres personnalisés
    createFilter(table, column, operator, values) {
        return {
            $schema: "http://powerbi.com/product/schema#basic",
            target: {
                table: table,
                column: column
            },
            operator: operator,
            values: values
        };
    }

    // Filtres prédéfinis pour le CRM
    getCustomerFilter(customerId) {
        return this.createFilter("Customers", "CustomerID", "In", [customerId]);
    }

    getDateRangeFilter(startDate, endDate) {
        return {
            $schema: "http://powerbi.com/product/schema#advanced",
            target: {
                table: "Sales",
                column: "Date"
            },
            logicalOperator: "And",
            conditions: [
                {
                    operator: "GreaterThanOrEqual",
                    value: startDate
                },
                {
                    operator: "LessThanOrEqual", 
                    value: endDate
                }
            ]
        };
    }

    getSalesPersonFilter(salesPersonId) {
        return this.createFilter("Sales", "SalesPersonID", "In", [salesPersonId]);
    }

    // Mode démo Power BI
    showDemoMode(containerId = 'powerbi-container') {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        container.innerHTML = `
            <div class="demo-powerbi-content">
                <div class="text-center p-4 border-bottom bg-light">
                    <h5><i class="fas fa-chart-bar text-primary"></i> Mode Démo - Rapports CRM</h5>
                    <p class="text-muted mb-0">Simulation d'un dashboard Power BI intégré</p>
                    <small class="text-warning">
                        <i class="fas fa-info-circle"></i> 
                        Pour utiliser de vrais rapports Power BI, configurez vos identifiants Azure dans config/database.php
                    </small>
                </div>
                <div class="row p-4">
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <canvas id="demo-chart-1" width="300" height="200"></canvas>
                                <h6 class="mt-2">Ventes par Mois</h6>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <canvas id="demo-chart-2" width="300" height="200"></canvas>
                                <h6 class="mt-2">Performance Équipe</h6>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h6><i class="fas fa-table text-info"></i> Tableau de Bord Synthétique</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Commercial</th>
                                                <th>Objectif</th>
                                                <th>Réalisé</th>
                                                <th>%</th>
                                                <th>Trend</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Jean Dupont</td>
                                                <td>50 000€</td>
                                                <td>45 000€</td>
                                                <td class="text-warning">90%</td>
                                                <td><i class="fas fa-arrow-up text-success"></i></td>
                                            </tr>
                                            <tr>
                                                <td>Marie Martin</td>
                                                <td>60 000€</td>
                                                <td>65 000€</td>
                                                <td class="text-success">108%</td>
                                                <td><i class="fas fa-arrow-up text-success"></i></td>
                                            </tr>
                                            <tr>
                                                <td>Pierre Durand</td>
                                                <td>40 000€</td>
                                                <td>32 000€</td>
                                                <td class="text-danger">80%</td>
                                                <td><i class="fas fa-arrow-down text-danger"></i></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-center p-3 bg-light border-top">
                    <button class="btn btn-primary btn-sm me-2" onclick="powerBIManager.refreshDemoCharts()">
                        <i class="fas fa-sync"></i> Actualiser
                    </button>
                    <button class="btn btn-secondary btn-sm" onclick="powerBIManager.toggleFullscreen('${containerId}')">
                        <i class="fas fa-expand"></i> Plein écran
                    </button>
                </div>
            </div>
        `;
        
        // Créer les graphiques démo après un petit délai
        setTimeout(() => {
            this.createDemoCharts();
        }, 100);
    }

    // Créer des graphiques de démonstration
    createDemoCharts() {
        // Graphique 1 - Ventes par mois
        const ctx1 = document.getElementById('demo-chart-1');
        if (ctx1) {
            new Chart(ctx1, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun'],
                    datasets: [{
                        label: 'Ventes (k€)',
                        data: [65, 78, 90, 45, 67, 89],
                        backgroundColor: '#5a67d8',
                        borderColor: '#4c63d2',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
        
        // Graphique 2 - Performance équipe
        const ctx2 = document.getElementById('demo-chart-2');
        if (ctx2) {
            new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: ['Jean D.', 'Marie M.', 'Pierre D.'],
                    datasets: [{
                        data: [45, 65, 32],
                        backgroundColor: ['#5a67d8', '#48bb78', '#ed8936'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    }

    // Actualiser les graphiques démo
    refreshDemoCharts() {
        this.createDemoCharts();
        this.showNotification('Graphiques actualisés', 'success');
    }

    // Notification simple
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 1060; min-width: 250px;';
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 3000);
    }

    // Basculer en plein écran
    toggleFullscreen(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        if (!document.fullscreenElement) {
            container.requestFullscreen().catch(err => {
                console.log('Plein écran non supporté:', err);
            });
        } else {
            document.exitFullscreen();
        }
    }

    // ...existing code...
}

// Instance globale
const powerBIManager = new PowerBIManager();

// Fonctions globales pour l'interface
function loadPowerBIReport(reportId = null, containerId = 'powerbi-container') {
    powerBIManager.showLoader(containerId);
    powerBIManager.loadReport(reportId, containerId);
}

function refreshPowerBI() {
    powerBIManager.refreshToken().then(success => {
        if (success) {
            powerBIManager.loadReport();
        }
    });
}

function fullscreenPowerBI() {
    const container = document.getElementById('powerbi-container');
    if (container.requestFullscreen) {
        container.requestFullscreen();
    } else if (container.webkitRequestFullscreen) {
        container.webkitRequestFullscreen();
    } else if (container.mozRequestFullScreen) {
        container.mozRequestFullScreen();
    }
}

// Filtres CRM intégrés
function filterByCustomer(customerId) {
    const report = powerBIManager.currentReport;
    if (report) {
        const filter = powerBIManager.getCustomerFilter(customerId);
        powerBIManager.applyFilters(report, [filter]);
    }
}

function filterByDateRange(startDate, endDate) {
    const report = powerBIManager.currentReport;
    if (report) {
        const filter = powerBIManager.getDateRangeFilter(startDate, endDate);
        powerBIManager.applyFilters(report, [filter]);
    }
}

function filterBySalesPerson(salesPersonId) {
    const report = powerBIManager.currentReport;
    if (report) {
        const filter = powerBIManager.getSalesPersonFilter(salesPersonId);
        powerBIManager.applyFilters(report, [filter]);
    }
}

// Initialisation automatique
document.addEventListener('DOMContentLoaded', function() {
    // Chargement automatique si un container Power BI existe
    const powerBIContainer = document.getElementById('powerbi-container');
    if (powerBIContainer) {
        // Attendre un peu pour que la page soit complètement chargée
        setTimeout(() => {
            // Essayer d'initialiser Power BI, sinon passer en mode démo
            powerBIManager.init().then(success => {
                if (!success) {
                    // Mode démo déjà activé dans la fonction init()
                    console.log('Mode démo Power BI activé automatiquement');
                }
            });
        }, 1000);
    }
});

// Export des fonctions pour utilisation externe
window.PowerBIManager = PowerBIManager;
window.powerBIManager = powerBIManager;
