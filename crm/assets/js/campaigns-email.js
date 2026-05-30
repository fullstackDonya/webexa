// Campaigns Email - Script pour la gestion des campagnes email
document.addEventListener('DOMContentLoaded', function() {
    console.log('Campaigns Email page loaded');

    // Initialisation de la page
    initEmailCampaigns();
    loadCampaignsData();
    setupCampaignFilters();
    setupCampaignHandlers();
});

function initEmailCampaigns() {
    // Initialisation des graphiques de performance
    initCampaignCharts();
    
    // Configuration des modals
    setupModalHandlers();
    
    // Initialisation du drag & drop pour l'import
    setupDragDropHandlers();
}

function initCampaignCharts() {
    // Graphique d'évolution des taux d'ouverture
    if (document.getElementById('openRateChart')) {
        createOpenRateChart();
    }
    
    // Graphique de répartition des types de campagnes
    if (document.getElementById('campaignTypeChart')) {
        createCampaignTypeChart();
    }
    
    // Graphique de performance comparative
    if (document.getElementById('performanceChart')) {
        createPerformanceChart();
    }
}

function createOpenRateChart() {
    const ctx = document.getElementById('openRateChart').getContext('2d');
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun'],
            datasets: [
                {
                    label: 'Taux d\'ouverture',
                    data: [22.3, 24.1, 23.8, 25.2, 24.7, 26.3],
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    tension: 0.4
                },
                {
                    label: 'Moyenne industrie',
                    data: [21.5, 21.8, 22.1, 22.3, 22.5, 22.8],
                    borderColor: '#6c757d',
                    backgroundColor: 'rgba(108, 117, 125, 0.1)',
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
                    text: 'Évolution des taux d\'ouverture'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 30,
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

function createCampaignTypeChart() {
    const ctx = document.getElementById('campaignTypeChart').getContext('2d');
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Newsletter', 'Promotionnelle', 'Automatisée', 'Transactionnelle', 'Bienvenue'],
            datasets: [{
                data: [35, 25, 20, 12, 8],
                backgroundColor: [
                    '#007bff',
                    '#ffc107',
                    '#28a745',
                    '#17a2b8',
                    '#6f42c1'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Répartition par type de campagne'
                },
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

function createPerformanceChart() {
    const ctx = document.getElementById('performanceChart').getContext('2d');
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Newsletter Jan', 'Promo Hiver', 'Bienvenue', 'Rappel Panier', 'Newsletter Fév'],
            datasets: [
                {
                    label: 'Taux d\'ouverture (%)',
                    data: [24.3, 31.7, 45.2, 18.9, 26.1],
                    backgroundColor: 'rgba(0, 123, 255, 0.8)'
                },
                {
                    label: 'Taux de clic (%)',
                    data: [5.2, 8.9, 12.3, 3.4, 6.1],
                    backgroundColor: 'rgba(40, 167, 69, 0.8)'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Performance des dernières campagnes'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
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

function loadCampaignsData() {
    // Chargement des données des campagnes
    fetch('api/campaigns-data.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCampaignMetrics(data.metrics);
                updateCampaignsTable(data.campaigns);
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des campagnes:', error);
            loadDemoCampaignsData();
        });
}

function updateCampaignMetrics(metrics) {
    // Mise à jour des métriques principales
    document.getElementById('activeCampaigns').textContent = metrics.active || 24;
    document.getElementById('deliveryRate').textContent = (metrics.deliveryRate || 89.2) + '%';
    document.getElementById('openRate').textContent = (metrics.openRate || 23.4) + '%';
    document.getElementById('clickRate').textContent = (metrics.clickRate || 4.7) + '%';
}

function updateCampaignsTable(campaigns) {
    const tableBody = document.getElementById('campaignsTableBody');
    if (!tableBody) return;

    if (!campaigns || campaigns.length === 0) {
        loadDemoCampaignsTable();
        return;
    }

    tableBody.innerHTML = campaigns.map(campaign => `
        <tr>
            <td>
                <div class="d-flex align-items-center">
                    <div class="campaign-icon me-2">
                        <i class="fas ${getCampaignIcon(campaign.type)} text-${getCampaignColor(campaign.type)}"></i>
                    </div>
                    <div>
                        <div class="fw-bold">${campaign.name}</div>
                        <small class="text-muted">${campaign.description}</small>
                    </div>
                </div>
            </td>
            <td><span class="badge bg-${getCampaignTypeColor(campaign.type)}">${campaign.type}</span></td>
            <td><span class="badge bg-${getStatusColor(campaign.status)}">${campaign.status}</span></td>
            <td>${campaign.recipients.toLocaleString()}</td>
            <td>
                <div class="d-flex align-items-center">
                    <span class="me-2">${campaign.openRate}%</span>
                    <div class="progress" style="width: 50px; height: 8px;">
                        <div class="progress-bar bg-warning" style="width: ${campaign.openRate}%"></div>
                    </div>
                </div>
            </td>
            <td>
                <div class="d-flex align-items-center">
                    <span class="me-2">${campaign.clickRate}%</span>
                    <div class="progress" style="width: 50px; height: 8px;">
                        <div class="progress-bar bg-info" style="width: ${campaign.clickRate}%"></div>
                    </div>
                </div>
            </td>
            <td>${campaign.sentDate}</td>
            <td>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        Actions
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" onclick="viewCampaign('${campaign.id}')">
                            <i class="fas fa-eye me-2"></i>Voir
                        </a></li>
                        <li><a class="dropdown-item" href="#" onclick="editCampaign('${campaign.id}')">
                            <i class="fas fa-edit me-2"></i>Modifier
                        </a></li>
                        <li><a class="dropdown-item" href="#" onclick="duplicateCampaign('${campaign.id}')">
                            <i class="fas fa-copy me-2"></i>Dupliquer
                        </a></li>
                        <li><a class="dropdown-item" href="#" onclick="viewStats('${campaign.id}')">
                            <i class="fas fa-chart-bar me-2"></i>Statistiques
                        </a></li>
                        ${campaign.status === 'Brouillon' ? 
                            '<li><hr class="dropdown-divider"></li><li><a class="dropdown-item text-danger" href="#" onclick="deleteCampaign(\'' + campaign.id + '\')"><i class="fas fa-trash me-2"></i>Supprimer</a></li>' : 
                            ''
                        }
                    </ul>
                </div>
            </td>
        </tr>
    `).join('');
}

function getCampaignIcon(type) {
    const icons = {
        'Newsletter': 'fa-envelope',
        'Promotionnelle': 'fa-percentage',
        'Automatisée': 'fa-robot',
        'Transactionnelle': 'fa-receipt',
        'Bienvenue': 'fa-heart'
    };
    return icons[type] || 'fa-envelope';
}

function getCampaignColor(type) {
    const colors = {
        'Newsletter': 'primary',
        'Promotionnelle': 'warning',
        'Automatisée': 'success',
        'Transactionnelle': 'info',
        'Bienvenue': 'danger'
    };
    return colors[type] || 'primary';
}

function getCampaignTypeColor(type) {
    const colors = {
        'Newsletter': 'info',
        'Promotionnelle': 'warning',
        'Automatisée': 'secondary',
        'Transactionnelle': 'primary',
        'Bienvenue': 'success'
    };
    return colors[type] || 'secondary';
}

function getStatusColor(status) {
    const colors = {
        'Envoyée': 'success',
        'Programmée': 'primary',
        'Active': 'success',
        'Suspendue': 'warning',
        'Brouillon': 'secondary',
        'Erreur': 'danger'
    };
    return colors[status] || 'secondary';
}

function loadDemoCampaignsData() {
    console.log('Chargement des données de démonstration des campagnes');
    updateCampaignMetrics({
        active: 24,
        deliveryRate: 89.2,
        openRate: 23.4,
        clickRate: 4.7
    });
    loadDemoCampaignsTable();
}

function loadDemoCampaignsTable() {
    // Chargement des données de démonstration dans le tableau
    const demoCampaigns = [
        {
            id: 1,
            name: 'Newsletter Janvier 2024',
            description: 'Actualités et promotions',
            type: 'Newsletter',
            status: 'Envoyée',
            recipients: 2847,
            openRate: 24.3,
            clickRate: 5.2,
            sentDate: '15/01/2024'
        },
        {
            id: 2,
            name: 'Promotion Hiver',
            description: 'Réduction 30% sur tout',
            type: 'Promotionnelle',
            status: 'Programmée',
            recipients: 5234,
            openRate: 0,
            clickRate: 0,
            sentDate: '20/01/2024'
        }
    ];
    
    updateCampaignsTable(demoCampaigns);
}

function setupCampaignFilters() {
    // Configuration des filtres
    const filters = ['statusFilter', 'typeFilter', 'dateFilter', 'searchCampaign'];
    
    filters.forEach(filterId => {
        const element = document.getElementById(filterId);
        if (element) {
            element.addEventListener('change', function() {
                applyCampaignFilters();
            });
        }
    });
    
    // Recherche en temps réel
    const searchInput = document.getElementById('searchCampaign');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function() {
            applyCampaignFilters();
        }, 300));
    }
}

function applyCampaignFilters() {
    const status = document.getElementById('statusFilter')?.value;
    const type = document.getElementById('typeFilter')?.value;
    const date = document.getElementById('dateFilter')?.value;
    const search = document.getElementById('searchCampaign')?.value;
    
    console.log('Application des filtres:', { status, type, date, search });
    loadCampaignsData();
}

function setupCampaignHandlers() {
    // Configuration des gestionnaires d'événements
    
    // Bouton d'export
    const exportButton = document.getElementById('exportCampaigns');
    if (exportButton) {
        exportButton.addEventListener('click', function() {
            exportCampaignsData();
        });
    }
    
    // Boutons d'action en lot
    const bulkActions = document.getElementById('bulkActions');
    if (bulkActions) {
        bulkActions.addEventListener('change', function() {
            if (this.value) {
                executeBulkAction(this.value);
                this.value = '';
            }
        });
    }
}

function setupModalHandlers() {
    // Configuration du modal de nouvelle campagne
    const modal = document.getElementById('newCampaignModal');
    if (modal) {
        modal.addEventListener('show.bs.modal', function() {
            resetCampaignForm();
        });
    }
    
    // Gestion de l'affichage du champ de programmation
    const schedulingType = document.getElementById('schedulingType');
    if (schedulingType) {
        schedulingType.addEventListener('change', function() {
            const scheduleDateDiv = document.getElementById('scheduleDateDiv');
            if (scheduleDateDiv) {
                scheduleDateDiv.style.display = this.value === 'scheduled' ? 'block' : 'none';
            }
        });
    }
    
    // Validation et soumission du formulaire
    const form = document.getElementById('newCampaignForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            submitCampaignForm();
        });
    }
}

function setupDragDropHandlers() {
    // Configuration du drag & drop pour l'import de listes
    const dropZone = document.getElementById('audienceDropZone');
    if (dropZone) {
        dropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('drag-over');
        });
        
        dropZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');
        });
        
        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');
            handleFileUpload(e.dataTransfer.files);
        });
    }
}

function resetCampaignForm() {
    const form = document.getElementById('newCampaignForm');
    if (form) {
        form.reset();
        document.getElementById('scheduleDateDiv').style.display = 'none';
    }
}

function submitCampaignForm() {
    const formData = new FormData(document.getElementById('newCampaignForm'));
    
    // Validation des champs
    if (!validateCampaignForm(formData)) {
        return;
    }
    
    // Envoi des données
    fetch('api/create-campaign.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Campagne créée avec succès', 'success');
            bootstrap.Modal.getInstance(document.getElementById('newCampaignModal')).hide();
            loadCampaignsData();
        } else {
            showNotification('Erreur lors de la création: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('Erreur de connexion', 'error');
    });
}

function validateCampaignForm(formData) {
    // Validation des champs obligatoires
    const required = ['campaignName', 'campaignType', 'campaignSubject', 'audienceSelect'];
    
    for (let field of required) {
        if (!formData.get(field)) {
            showNotification(`Le champ ${field} est obligatoire`, 'warning');
            return false;
        }
    }
    
    return true;
}

// Actions sur les campagnes
function viewCampaign(campaignId) {
    console.log('Affichage de la campagne:', campaignId);
    // Redirection ou modal avec les détails
}

function editCampaign(campaignId) {
    console.log('Modification de la campagne:', campaignId);
    // Redirection vers l'éditeur
}

function duplicateCampaign(campaignId) {
    console.log('Duplication de la campagne:', campaignId);
    // Logique de duplication
}

function viewStats(campaignId) {
    console.log('Statistiques de la campagne:', campaignId);
    // Affichage des statistiques détaillées
}

function deleteCampaign(campaignId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette campagne ?')) {
        console.log('Suppression de la campagne:', campaignId);
        // Logique de suppression
    }
}

function exportCampaignsData() {
    console.log('Export des données de campagnes...');
    // Logique d'export
}

function executeBulkAction(action) {
    console.log('Exécution de l\'action en lot:', action);
    // Logique d'actions en lot
}

function handleFileUpload(files) {
    console.log('Upload de fichiers:', files);
    // Traitement des fichiers uploadés
}

function showNotification(message, type) {
    // Affichage de notifications utilisateur
    console.log(`${type.toUpperCase()}: ${message}`);
    // Implémentation avec toast ou alert
}

// Fonction utilitaire debounce
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
