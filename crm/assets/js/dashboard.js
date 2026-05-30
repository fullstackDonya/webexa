// Dashboard JavaScript pour CRM Intelligent

// Fonctions de diagnostic et installation
function checkDatabase() {
    const resultDiv = document.getElementById('diagnostic-result');
    resultDiv.style.display = 'block';
    resultDiv.className = 'alert alert-info';
    resultDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Vérification en cours...';
    
    fetch('api/db-check.php')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                resultDiv.className = 'alert alert-success';
                resultDiv.innerHTML = `<i class="fas fa-check"></i> ${data.message}`;
                
                if (!data.tables_exist) {
                    resultDiv.className = 'alert alert-warning';
                    resultDiv.innerHTML += `<br><strong>Action requise:</strong> ${data.action_needed}`;
                }
            } else {
                resultDiv.className = 'alert alert-danger';
                resultDiv.innerHTML = `<i class="fas fa-times"></i> ${data.message}`;
            }
        })
        .catch(error => {
            resultDiv.className = 'alert alert-danger';
            resultDiv.innerHTML = `<i class="fas fa-times"></i> Erreur: ${error.message}`;
        });
}

function installDatabase() {
    if (!confirm('Êtes-vous sûr de vouloir installer/réinstaller la base de données CRM ?')) {
        return;
    }
    
    const resultDiv = document.getElementById('diagnostic-result');
    resultDiv.style.display = 'block';
    resultDiv.className = 'alert alert-info';
    resultDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Installation en cours...';
    
    fetch('api/install-db.php')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                resultDiv.className = 'alert alert-success';
                resultDiv.innerHTML = `<i class="fas fa-check"></i> ${data.message}`;
                // Recharger les KPIs après installation
                setTimeout(() => {
                    loadKPIs();
                    loadRecentActivities();
                }, 2000);
            } else {
                resultDiv.className = 'alert alert-danger';
                resultDiv.innerHTML = `<i class="fas fa-times"></i> ${data.message}`;
            }
        })
        .catch(error => {
            resultDiv.className = 'alert alert-danger';
            resultDiv.innerHTML = `<i class="fas fa-times"></i> Erreur: ${error.message}`;
        });
}

document.addEventListener('DOMContentLoaded', function() {
    initializeDashboard();
    loadKPIs();
    loadRecentActivities();
    // Charger les données du dashboard pour alimenter Win/Loss, CA par Source, Âge et Top Clients
    updateDashboardData();
    
    // Actualisation automatique toutes les 5 minutes
    setInterval(function() {
        loadKPIs();
        loadRecentActivities();
    }, 300000);
});

// Initialisation du dashboard
function initializeDashboard() {
    console.log('Initialisation du dashboard CRM');
    
    // Animation des cartes KPI
    animateKPICards();
    
    // Gestion du responsive
    handleResponsiveLayout();
    
    // Événements pour les filtres
    setupFilters();
}

// Animation des cartes KPI
function animateKPICards() {
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
}

// Chargement des KPIs
async function loadKPIs() {
    try {
        const response = await fetch('api/kpis.php');
        
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success !== false && data.kpis) {
            updateKPICard('total-revenue', data.kpis.total_revenue, '€');
            updateKPICard('active-clients', data.kpis.active_clients);
            updateKPICard('conversion-rate', data.kpis.conversion_rate, '%');
            updateKPICard('opportunities', data.kpis.opportunities);
            
            // Animation des compteurs
            animateCounters();
        } else {
            console.error('Erreur dans les données KPI:', data.error || 'Données non valides');
            
            // Si les tables n'existent pas, proposer l'installation
            if (data.action === 'install_database') {
                showKPIInstallPrompt(data.error);
            } else {
                showKPIError(data.error || 'Erreur de chargement des KPIs');
            }
        }
    } catch (error) {
        console.error('Erreur lors du chargement des KPIs:', error);
        showKPIError('Impossible de charger les KPIs: ' + error.message);
    }
}

function showKPIError(message) {
    // Afficher des valeurs par défaut et le message d'erreur
    updateKPICard('total-revenue', 0, '€');
    updateKPICard('active-clients', 0);
    updateKPICard('conversion-rate', 0, '%');
    updateKPICard('opportunities', 0);
    
    // Afficher une notification d'erreur
    const errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-warning alert-dismissible fade show';
    errorDiv.innerHTML = `
        <i class="fas fa-exclamation-triangle"></i> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insérer l'erreur au début du contenu
    const container = document.querySelector('.container-fluid');
    if (container) {
        container.insertBefore(errorDiv, container.firstChild);
    }
}

function showKPIInstallPrompt(message) {
    // Afficher des valeurs par défaut
    updateKPICard('total-revenue', 0, '€');
    updateKPICard('active-clients', 0);
    updateKPICard('conversion-rate', 0, '%');
    updateKPICard('opportunities', 0);
    
    // Afficher une notification avec bouton d'installation
    const promptDiv = document.createElement('div');
    promptDiv.className = 'alert alert-warning alert-dismissible fade show';
    promptDiv.innerHTML = `
        <i class="fas fa-database"></i> <strong>Base de données non configurée</strong><br>
        ${message}<br>
        <div class="mt-2">
            <button type="button" class="btn btn-primary btn-sm me-2" onclick="installDatabase(); this.parentElement.parentElement.remove();">
                <i class="fas fa-download"></i> Installer CRM
            </button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="runFullDiagnostic(); this.parentElement.parentElement.remove();">
                <i class="fas fa-stethoscope"></i> Diagnostic
            </button>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insérer au début du contenu
    const container = document.querySelector('.container-fluid');
    if (container) {
        container.insertBefore(promptDiv, container.firstChild);
    }
}

// Mise à jour d'une carte KPI
function updateKPICard(elementId, value, suffix = '') {
    const element = document.getElementById(elementId);
    if (element) {
        const numericValue = typeof value === 'number' ? value : parseInt(value) || 0;
        element.setAttribute('data-target', numericValue);
        element.textContent = formatNumber(numericValue) + suffix;
    }
}

// Animation des compteurs
function animateCounters() {
    const counters = document.querySelectorAll('[data-target]');
    
    counters.forEach(counter => {
        const target = parseInt(counter.getAttribute('data-target'));
        const increment = target / 100;
        let current = 0;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            
            const suffix = counter.textContent.includes('€') ? '€' : 
                          counter.textContent.includes('%') ? '%' : '';
            counter.textContent = Math.floor(current).toLocaleString() + suffix;
        }, 20);
    });
}

// Formatage des nombres
function formatNumber(num) {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    } else if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return num.toString();
}

// Initialisation des graphiques
function initializeCharts() {
    createSalesChart();
    createSourceChart();
}

// Références aux graphiques pour éviter les doublons
let winLossChartRef = null;
let revenueBySourceChartRef = null;
let opAgeChartRef = null;

function escapeHtml(s){
    if (!s && s !== 0) return '';
    const map = {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'};
    return String(s).replace(/[&<>"']/g, c => map[c]);
}

function renderWinLoss(winLoss) {
    const canvas = document.getElementById('winLossChart');
    if (!canvas || !winLoss) return;
    const ctx = canvas.getContext('2d');
    const labels = ['Gagnés', 'Perdus'];
    const values = [Number(winLoss.won?.count || 0), Number(winLoss.lost?.count || 0)];
    const colors = ['#48bb78', '#e53e3e'];
    if (winLossChartRef) { winLossChartRef.destroy(); }
    winLossChartRef = new Chart(ctx, {
        type: 'doughnut',
        data: { labels, datasets: [{ data: values, backgroundColor: colors, borderWidth: 0 }] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%',
            plugins: { legend: { position: 'bottom', labels: { usePointStyle: true } } }
        }
    });
}

function renderRevenueBySource(items) {
    const canvas = document.getElementById('revenueBySourceChart');
    if (!canvas || !Array.isArray(items)) return;
    const ctx = canvas.getContext('2d');
    const labels = items.map(x => x.source);
    const values = items.map(x => Number(x.revenue || 0));
    if (revenueBySourceChartRef) { revenueBySourceChartRef.destroy(); }
    revenueBySourceChartRef = new Chart(ctx, {
        type: 'bar',
        data: { labels, datasets: [{ label: 'CA (€)', data: values, backgroundColor: '#5a67d8' }] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } },
            plugins: { legend: { display: false } }
        }
    });
}

function renderOpportunityAge(age) {
    const canvas = document.getElementById('opAgeChart');
    if (!canvas || !age) return;
    const ctx = canvas.getContext('2d');
    const labels = ['0-30j', '31-60j', '61-90j', '90+j'];
    const values = [Number(age['0_30']||0), Number(age['31_60']||0), Number(age['61_90']||0), Number(age['90_plus']||0)];
    if (opAgeChartRef) { opAgeChartRef.destroy(); }
    opAgeChartRef = new Chart(ctx, {
        type: 'bar',
        data: { labels, datasets: [{ label: 'Opportunités', data: values, backgroundColor: '#ed8936' }] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } },
            plugins: { legend: { display: false } }
        }
    });
}

function renderTopClients(topClients) {
    const tbody = document.getElementById('topCompaniesBody');
    if (!tbody || !Array.isArray(topClients)) return;
    const rows = topClients.map(c => {
        const revenue = (Number(c.revenue || 0)).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        return `
            <tr>
                <td>${escapeHtml(c.company_name || '')}</td>
                <td>${Number(c.won_deals || 0)}</td>
                <td>€${revenue}</td>
            </tr>
        `;
    }).join('');
    tbody.innerHTML = rows || '<tr><td colspan="3" class="text-muted">Aucune donnée</td></tr>';
}

// Graphique des ventes
async function createSalesChart() {
    try {
        const response = await fetch('api/sales-data.php');
        const data = await response.json();
        
        if (data.success) {
            const ctx = document.getElementById('salesChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Ventes (€)',
                        data: data.sales,
                        borderColor: '#5a67d8',
                        backgroundColor: 'rgba(90, 103, 216, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0,0,0,0.1)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }
    } catch (error) {
        console.error('Erreur création graphique ventes:', error);
    }
}

// Graphique de répartition par source
async function createSourceChart() {
    try {
        const response = await fetch('api/source-data.php');
        const data = await response.json();
        
        if (data.success) {
            const ctx = document.getElementById('sourceChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: data.labels,
                    datasets: [{
                        data: data.values,
                        backgroundColor: [
                            '#5a67d8',
                            '#48bb78',
                            '#ed8936',
                            '#38b2ac',
                            '#e53e3e'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '60%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 20
                            }
                        }
                    }
                }
            });
        }
    } catch (error) {
        console.error('Erreur création graphique sources:', error);
    }
}

// Chargement des activités récentes
async function loadRecentActivities() {
    try {
        const response = await fetch('api/recent-activities.php');
        
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success !== false && data.activities) {
            const container = document.getElementById('recent-activities');
            container.innerHTML = '';
            
            if (data.activities.length > 0) {
                data.activities.forEach(activity => {
                    const activityElement = createActivityElement(activity);
                    container.appendChild(activityElement);
                });
            } else {
                container.innerHTML = '<div class="text-center text-muted p-3">Aucune activité récente</div>';
            }
        } else {
            console.error('Erreur dans les données activités:', data.error || 'Données non valides');
            showActivitiesError(data.error || 'Erreur de chargement des activités');
        }
    } catch (error) {
        console.error('Erreur lors du chargement des activités:', error);
        showActivitiesError('Impossible de charger les activités: ' + error.message);
    }
}

function showActivitiesError(message) {
    const container = document.getElementById('recent-activities');
    if (container) {
        container.innerHTML = `
            <div class="alert alert-warning text-center">
                <i class="fas fa-exclamation-triangle"></i> ${message}
            </div>
        `;
    }
}

// Créer un élément d'activité
function createActivityElement(activity) {
    const div = document.createElement('div');
    div.className = 'activity-item d-flex align-items-center mb-3 p-3 bg-light rounded';
    
    const icon = getActivityIcon(activity.action);
    const timeAgo = getTimeAgo(activity.created_at);
    
    div.innerHTML = `
        <div class="activity-icon me-3">
            <i class="${icon} text-primary"></i>
        </div>
        <div class="activity-content flex-grow-1">
            <div class="activity-description">${activity.description}</div>
            <div class="activity-meta">
                <span class="text-muted">${activity.user_name}</span>
                <span class="text-muted ms-2">${timeAgo}</span>
            </div>
        </div>
    `;
    
    return div;
}

// Icônes pour les activités
function getActivityIcon(action) {
    const icons = {
        'login': 'fas fa-sign-in-alt',
        'logout': 'fas fa-sign-out-alt',
        'create_contact': 'fas fa-user-plus',
        'update_contact': 'fas fa-user-edit',
        'create_lead': 'fas fa-bullseye',
        'create_opportunity': 'fas fa-handshake',
        'send_email': 'fas fa-envelope',
        'call': 'fas fa-phone',
        'meeting': 'fas fa-calendar',
        'powerbi_access': 'fas fa-chart-bar'
    };
    
    return icons[action] || 'fas fa-info-circle';
}

// Calcul du temps écoulé
function getTimeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) {
        return 'Il y a quelques secondes';
    } else if (diffInSeconds < 3600) {
        const minutes = Math.floor(diffInSeconds / 60);
        return `Il y a ${minutes} minute${minutes > 1 ? 's' : ''}`;
    } else if (diffInSeconds < 86400) {
        const hours = Math.floor(diffInSeconds / 3600);
        return `Il y a ${hours} heure${hours > 1 ? 's' : ''}`;
    } else {
        const days = Math.floor(diffInSeconds / 86400);
        return `Il y a ${days} jour${days > 1 ? 's' : ''}`;
    }
}

// Gestion du responsive
function handleResponsiveLayout() {
    const checkLayout = () => {
        const width = window.innerWidth;
        const sidebar = document.getElementById('accordionSidebar');
        
        if (width < 768 && sidebar) {
            sidebar.classList.add('collapsed');
        }
    };
    
    window.addEventListener('resize', checkLayout);
    checkLayout();
}

// Configuration des filtres
function setupFilters() {
    // Filtre de période
    const periodFilter = document.getElementById('period-filter');
    if (periodFilter) {
        periodFilter.addEventListener('change', function() {
            const period = this.value;
            filterByPeriod(period);
        });
    }
    
    // Filtre de source
    const sourceFilter = document.getElementById('source-filter');
    if (sourceFilter) {
        sourceFilter.addEventListener('change', function() {
            const source = this.value;
            filterBySource(source);
        });
    }
}

// Filtrage par période
function filterByPeriod(period) {
    const endDate = new Date();
    const startDate = new Date();
    
    switch(period) {
        case 'today':
            startDate.setHours(0, 0, 0, 0);
            break;
        case 'week':
            startDate.setDate(endDate.getDate() - 7);
            break;
        case 'month':
            startDate.setMonth(endDate.getMonth() - 1);
            break;
        case 'quarter':
            startDate.setMonth(endDate.getMonth() - 3);
            break;
        case 'year':
            startDate.setFullYear(endDate.getFullYear() - 1);
            break;
    }
    
    // Appliquer le filtre aux graphiques et Power BI
    updateDashboardData(startDate, endDate);
    
    // Filtre Power BI si disponible
    if (window.powerBIManager && window.powerBIManager.currentReport) {
        filterByDateRange(startDate.toISOString().split('T')[0], endDate.toISOString().split('T')[0]);
    }
}

// Filtrage par source
function filterBySource(source) {
    // Logique de filtrage par source
    console.log('Filtrage par source:', source);
    
    // Mise à jour des données
    updateDashboardData(null, null, source);
}

// Appelle cette fonction après avoir reçu la réponse de l'API dashboard-data.php
function displayInvoicesMissions(data) {
    // Affichage des factures sous forme de liste
    const invoicesList = document.getElementById('invoices-list');
    if (invoicesList) {
        invoicesList.innerHTML = '';
        if (data.invoices && Array.isArray(data.invoices) && data.invoices.length > 0) {
            data.invoices.forEach(inv => {
                invoicesList.innerHTML += `
                    <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-1">
                        <div>
                            <strong>${inv.invoice_number || inv.id}</strong> - ${inv.company_name} <span class="text-muted">(${inv.folder_name})</span>
                            <br>
                            <span class="badge bg-secondary">${inv.status}</span>
                        </div>
                        <div class="text-end">
                            <span class="fw-bold">${inv.amount} €</span><br>
                            <small class="text-muted">${inv.issued_at ? new Date(inv.issued_at).toLocaleDateString('fr-FR') : ''}</small>
                        </div>
                    </div>
                `;
            });
        } else {
            invoicesList.innerHTML = `<div class="text-center text-muted">Aucune facture</div>`;
        }
    }

    // Affichage des missions sous forme de liste
    const missionsList = document.getElementById('missions-list');
    if (missionsList) {
        missionsList.innerHTML = '';
        if (data.missions && Array.isArray(data.missions) && data.missions.length > 0) {
            data.missions.forEach(mission => {
                missionsList.innerHTML += `
                    <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-1">
                        <div>
                            <strong>${mission.name}</strong> - ${mission.company_name} <span class="text-muted">(${mission.folder_name})</span>
                            <br>
                            <span class="badge bg-info">${mission.status}</span>
                        </div>
                        <div class="text-end">
                            <small class="text-muted">${mission.start_date || ''} → ${mission.end_date || ''}</small>
                        </div>
                    </div>
                `;
            });
        } else {
            missionsList.innerHTML = `<div class="text-center text-muted">Aucune mission</div>`;
        }
    }
}

// Exemple d'appel après récupération des données dashboard
async function updateDashboardData(startDate = null, endDate = null, source = null) {
    try {
        const params = new URLSearchParams();
        if (startDate) params.append('start_date', startDate.toISOString().split('T')[0]);
        if (endDate) params.append('end_date', endDate.toISOString().split('T')[0]);
        if (source) params.append('source', source);

        const response = await fetch(`api/dashboard-data.php?${params.toString()}`);
        const data = await response.json();

        if (data.success) {
            // Mise à jour des KPIs
            updateKPICard('total-revenue', data.kpis.total_revenue, '€');
            updateKPICard('active-clients', data.kpis.active_clients);
            updateKPICard('conversion-rate', data.kpis.conversion_rate, '%');
            updateKPICard('opportunities', data.kpis.opportunities);

            // Affichage des factures, missions et états
            displayInvoicesMissions(data);

            // Recréer les graphiques avec les nouvelles données
            initializeCharts();

            // Nouveaux graphiques et tableau
            renderWinLoss(data.win_loss);
            renderRevenueBySource(data.revenue_by_source);
            renderOpportunityAge(data.opportunity_age);
            renderTopClients(data.top_clients);
        }
    } catch (error) {
        console.error('Erreur mise à jour données:', error);
    }
}

// Fonctions utilitaires pour l'export
function exportDashboard(format = 'pdf') {
    const printWindow = window.open('', '_blank');
    const dashboardContent = document.querySelector('.container-fluid').innerHTML;
    
    printWindow.document.write(`
        <html>
        <head>
            <title>Dashboard CRM - Export</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { padding: 20px; }
                .card { margin-bottom: 20px; }
                .no-print { display: none !important; }
            </style>
        </head>
        <body>
            <h1>Dashboard CRM Intelligent</h1>
            <p>Généré le ${new Date().toLocaleDateString('fr-FR')}</p>
            ${dashboardContent}
        </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.print();
}

// Actualisation manuelle du dashboard
function refreshDashboard() {
    console.log('Actualisation du dashboard...');
    
    loadKPIs();
    initializeCharts();
    loadRecentActivities();
    
    // Actualiser Power BI
    if (window.powerBIManager) {
        refreshPowerBI();
    }
    
    // Notification de succès
    showNotification('Dashboard actualisé avec succès', 'success');
}

// Système de notifications
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 1050; min-width: 300px;';
    
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Suppression automatique après 5 secondes
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 5000);
}

// Diagnostic complet
function runFullDiagnostic() {
    const resultDiv = document.getElementById('diagnostic-result');
    resultDiv.style.display = 'block';
    resultDiv.className = 'alert alert-info';
    resultDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Diagnostic complet en cours...';
    
    fetch('api/diagnostic.php')
        .then(response => response.json())
        .then(data => {
            let html = '<h6><i class="fas fa-chart-pie"></i> Diagnostic Complet</h6>';
            
            // Score global
            const overall = data.overall;
            const badgeClass = overall.status === 'EXCELLENT' ? 'success' : 
                              overall.status === 'GOOD' ? 'primary' :
                              overall.status === 'WARNING' ? 'warning' : 'danger';
            
            html += `<div class="mb-3">
                <span class="badge bg-${badgeClass}">${overall.status}</span>
                <span class="ms-2">${overall.percentage}% (${overall.score}/${overall.total})</span>
            </div>`;
            
            // Base de données
            html += '<div class="mb-2"><strong>Base de données:</strong> ';
            if (data.database && data.database.connection === 'OK') {
                html += '<span class="text-success">✓ Connectée</span>';
                if (data.database.tables) {
                    const tableCount = Object.values(data.database.tables).filter(Boolean).length;
                    const totalTables = Object.keys(data.database.tables).length;
                    html += ` (${tableCount}/${totalTables} tables)`;
                }
                if (data.database.user_count !== undefined) {
                    html += ` - ${data.database.user_count} utilisateur(s)`;
                }
            } else {
                html += '<span class="text-danger">✗ Erreur de connexion</span>';
            }
            html += '</div>';
            
            // Fichiers API
            html += '<div class="mb-2"><strong>API:</strong> ';
            if (data.api_files) {
                const workingApis = Object.values(data.api_files).filter(f => f.exists && f.readable && f.size > 0).length;
                const totalApis = Object.keys(data.api_files).length;
                html += `${workingApis}/${totalApis} fichiers OK`;
            }
            html += '</div>';
            
            // Fichiers includes
            html += '<div class="mb-2"><strong>Includes:</strong> ';
            if (data.include_files) {
                const workingIncludes = Object.values(data.include_files).filter(f => f.exists && f.readable).length;
                const totalIncludes = Object.keys(data.include_files).length;
                html += `${workingIncludes}/${totalIncludes} fichiers OK`;
            }
            html += '</div>';
            
            // Actions recommandées
            if (overall.percentage < 80) {
                html += '<hr><small><strong>Actions recommandées:</strong><br>';
                if (data.database && data.database.connection !== 'OK') {
                    html += '• Installer la base de données<br>';
                }
                if (data.api_files) {
                    Object.entries(data.api_files).forEach(([file, info]) => {
                        if (!info.exists || !info.readable || info.size === 0) {
                            html += `• Vérifier le fichier ${file}<br>`;
                        }
                    });
                }
                html += '</small>';
            }
            
            resultDiv.className = `alert alert-${badgeClass}`;
            resultDiv.innerHTML = html;
        })
        .catch(error => {
            resultDiv.className = 'alert alert-danger';
            resultDiv.innerHTML = `<i class="fas fa-times"></i> Erreur lors du diagnostic: ${error.message}`;
        });
}

function quickSetup() {
    const resultDiv = document.getElementById('diagnostic-result');
    resultDiv.style.display = 'block';
    resultDiv.className = 'alert alert-info';
    resultDiv.innerHTML = '<i class="fas fa-magic fa-spin"></i> Configuration automatique en cours...';
    
    fetch('api/quick-setup.php')
        .then(response => response.json())
        .then(data => {
            let html = '<h6><i class="fas fa-magic"></i> Configuration Rapide Terminée</h6>';
            
            if (data.success) {
                resultDiv.className = 'alert alert-success';
                
                // Actions effectuées
                if (data.actions_performed && data.actions_performed.length > 0) {
                    html += '<div class="mb-2"><strong>Actions effectuées:</strong><ul class="mb-0">';
                    data.actions_performed.forEach(action => {
                        html += `<li>${action}</li>`;
                    });
                    html += '</ul></div>';
                }
                
                // Recommandations
                if (data.recommendations && data.recommendations.length > 0) {
                    html += '<div class="mb-2"><strong>État:</strong><br>';
                    data.recommendations.forEach(rec => {
                        html += `${rec}<br>`;
                    });
                    html += '</div>';
                }
                
                html += '<div class="mt-2">';
                html += '<button class="btn btn-primary btn-sm me-2" onclick="loadKPIs(); loadRecentActivities();">Actualiser Dashboard</button>';
                html += '<button class="btn btn-secondary btn-sm" onclick="runFullDiagnostic();">Diagnostic Complet</button>';
                html += '</div>';
                
            } else {
                resultDiv.className = 'alert alert-danger';
                html += '<div class="text-danger">Erreur lors de la configuration automatique</div>';
                if (data.status && data.status.error) {
                    html += `<small>${data.status.error}</small>`;
                }
            }
            
            resultDiv.innerHTML = html;
            
            // Auto-actualiser après une configuration réussie
            if (data.success) {
                setTimeout(() => {
                    loadKPIs();
                    loadRecentActivities();
                    showNotification('Configuration terminée avec succès!', 'success');
                }, 2000);
            }
            
        })
        .catch(error => {
            resultDiv.className = 'alert alert-danger';
            resultDiv.innerHTML = `<i class="fas fa-times"></i> Erreur: ${error.message}`;
        });
}




(async function(){
    const API_INVOICES = 'api/invoices-list.php';
    const API_MISSIONS = 'api/missions-list.php';
    const POLL_MS = 30000; // polling toutes les 30s

    function q(id){ return document.getElementById(id); }
    function emptyHtml(msg){ return `<div class="text-muted py-3">${msg}</div>`; }

    async function fetchJson(url, opts = {}) {
        opts.credentials = 'same-origin';
        try {
            const res = await fetch(url, opts);
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return await res.json();
        } catch (e) {
            console.error('fetch error', url, e);
            return { success: false, error: e.message || 'network' };
        }
    }

    function renderInvoicesList(data) {
        const container = q('invoices-list');
        if(!container) return;
        if(!data || !data.success || !Array.isArray(data.invoices) || data.invoices.length === 0){
            container.innerHTML = emptyHtml('Aucune facture récente.');
            return;
        }
        const html = data.invoices.map(inv => `
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <strong>${escapeHtml(inv.invoice_number || '—')}</strong><br>
                    <small class="text-green">${escapeHtml(inv.company_name || '')}</small>
                </div>
                <div class="text-end">
                    <div><strong>€${Number(inv.amount||0).toFixed(2)}</strong></div>
                    <small class="text-green">${escapeHtml(inv.status || '')}</small>
                </div>
            </div>
            <hr class="my-2">
        `).join('');
        container.innerHTML = html;
    }

    function renderMissionsList(data) {
        const container = q('missions-list');
        if(!container) return;
        if(!data || !data.success || !Array.isArray(data.missions) || data.missions.length === 0){
            container.innerHTML = emptyHtml('Aucune mission récente.');
            return;

        }
        console.log("missions", data.missions);
        
        const html = data.missions.map(ms => `
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <strong>${escapeHtml(ms.title || '—')}</strong><br>
                    <small class="text-muted">${escapeHtml(ms.assignee || '—')}</small>
                </div>
                <div class="text-end">
                    <div><small class="text-muted">${escapeHtml(ms.due_date || '')}</small></div>
                    <div><span class="badge bg-${ms.status === 'done' ? 'success' : (ms.status === 'pending' ? 'warning' : 'secondary')}">${escapeHtml(ms.status || '')}</span></div>
                </div>
            </div>
            <hr class="my-2">
        `).join('');
        container.innerHTML = html;
    }

    function escapeHtml(s){
        if (!s) return '';
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    window.refreshInvoices = async function(){
        const res = await fetchJson(API_INVOICES);
        console.log("📦 Invoices data:", res); // <--- ajout
        renderInvoicesList(res);
    };



    window.createInvoice = function(){
        // prefer modal if exists, otherwise redirect to add page
        if (typeof openInvoiceModal === 'function') return openInvoiceModal();
        window.location.href = 'invoices-add.php';
    };

    window.refreshMissions = async function(){
        const res = await fetchJson(API_MISSIONS);
        console.log("🧩 Missions data:", res); // <--- ajout
        renderMissionsList(res);
    };
    window.createMission = function(){
        if (typeof openMissionModal === 'function') return openMissionModal();
        window.location.href = 'mission_add.php';
    };

    // initial load
    await Promise.all([ window.refreshInvoices(), window.refreshMissions() ]);

    // polling
    setInterval(() => {
        window.refreshInvoices();
        window.refreshMissions();
    }, POLL_MS);

    // attach auto-refresh on visibility change (optional)
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            window.refreshInvoices();
            window.refreshMissions();
        }
    });
})();

