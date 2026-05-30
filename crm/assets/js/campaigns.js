// Campaigns JavaScript pour CRM Intelligent

document.addEventListener('DOMContentLoaded', function() {
    console.log('Initialisation du module Campaigns');
    initializeCampaigns();
    loadCampaignPerformanceChart();
    loadTopCampaigns();
});

// Initialisation du module campaigns
function initializeCampaigns() {
    console.log('Campaigns module initialized');
    
    // Animation des cartes
    animateCampaignCards();
}

// Animation des cartes de campagnes
function animateCampaignCards() {
    const cards = document.querySelectorAll('#campaigns-grid .card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 50 * index);
    });
}

// Fonction pour ouvrir le modal d'envoi de campagne
function openSendModal(campaignId) {
    console.log('Ouverture du modal d\'envoi pour la campagne:', campaignId);
    
    // Vérifier si le modal existe
    const modalElement = document.getElementById('campaignSendModal');
    if (!modalElement) {
        console.error('Modal d\'envoi non trouvé');
        alert('❌ Le modal d\'envoi n\'est pas disponible');
        return;
    }
    
    // Stocker l'ID de la campagne dans le modal
    modalElement.dataset.campaignId = campaignId;
    
    // Ouvrir le modal avec Bootstrap
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
    
    // Charger les informations de la campagne
    loadCampaignInfo(campaignId);
}

// Charger les informations de la campagne pour le modal
function loadCampaignInfo(campaignId) {
    fetch(`api/campaigns.php?action=get&id=${campaignId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur de chargement');
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success' && data.campaign) {
                // Remplir le modal avec les données de la campagne
                const campaign = data.campaign;
                const modalBody = document.querySelector('#campaignSendModal .modal-body');
                if (modalBody) {
                    const infoHtml = `
                        <div class="campaign-info mb-3">
                            <h5>${campaign.name || 'Campagne'}</h5>
                            <p class="text-muted">${campaign.subject || ''}</p>
                            <p><strong>Type:</strong> ${campaign.type || '-'}</p>
                            <p><strong>Canal:</strong> ${campaign.channel || '-'}</p>
                        </div>
                    `;
                    // Insérer au début du modal body
                    modalBody.insertAdjacentHTML('afterbegin', infoHtml);
                }
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement de la campagne:', error);
        });
}

// Charger le graphique de performance des campagnes
function loadCampaignPerformanceChart() {
    const chartElement = document.getElementById('campaign-performance-chart');
    if (!chartElement) {
        console.log('Élément graphique non trouvé');
        return;
    }
    
    // Récupérer les données de performance
    fetch('api/campaigns.php?action=performance')
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur de chargement');
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success' && data.performance) {
                renderPerformanceChart(chartElement, data.performance);
            } else {
                // Afficher un graphique avec des données par défaut
                renderPerformanceChart(chartElement, getDefaultPerformanceData());
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des données de performance:', error);
            // Afficher un graphique avec des données par défaut
            renderPerformanceChart(chartElement, getDefaultPerformanceData());
        });
}

// Rendre le graphique de performance
function renderPerformanceChart(chartElement, performanceData) {
    const ctx = chartElement.getContext('2d');
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: performanceData.labels || ['Sem 1', 'Sem 2', 'Sem 3', 'Sem 4'],
            datasets: [{
                label: 'Taux d\'ouverture (%)',
                data: performanceData.openRate || [0, 0, 0, 0],
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.4
            }, {
                label: 'Taux de clic (%)',
                data: performanceData.clickRate || [0, 0, 0, 0],
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                tension: 0.4
            }, {
                label: 'Taux de conversion (%)',
                data: performanceData.conversionRate || [0, 0, 0, 0],
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
}

// Données de performance par défaut
function getDefaultPerformanceData() {
    return {
        labels: ['Sem 1', 'Sem 2', 'Sem 3', 'Sem 4'],
        openRate: [0, 0, 0, 0],
        clickRate: [0, 0, 0, 0],
        conversionRate: [0, 0, 0, 0]
    };
}

// Charger la liste des top campagnes
function loadTopCampaigns() {
    const topCampaignsElement = document.getElementById('top-campaigns-list');
    if (!topCampaignsElement) {
        console.log('Élément top campagnes non trouvé');
        return;
    }
    
    // Afficher un loader
    topCampaignsElement.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Chargement...</div>';
    
    // Récupérer les top campagnes
    fetch('api/campaigns.php?action=top')
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur de chargement');
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success' && data.campaigns) {
                renderTopCampaigns(topCampaignsElement, data.campaigns);
            } else {
                topCampaignsElement.innerHTML = '<p class="text-muted">Aucune donnée disponible</p>';
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des top campagnes:', error);
            topCampaignsElement.innerHTML = '<p class="text-muted">Erreur de chargement</p>';
        });
}

// Rendre la liste des top campagnes
function renderTopCampaigns(element, campaigns) {
    if (!campaigns || campaigns.length === 0) {
        element.innerHTML = '<p class="text-muted">Aucune campagne disponible</p>';
        return;
    }
    
    let html = '<div class="list-group list-group-flush">';
    campaigns.forEach((campaign, index) => {
        const openRate = campaign.open_rate || 0;
        const progressColor = openRate >= 30 ? 'bg-success' : openRate >= 15 ? 'bg-warning' : 'bg-danger';
        
        html += `
            <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-bold">${index + 1}. ${escapeHtml(campaign.name || 'Campagne')}</span>
                    <span class="badge bg-primary">${openRate}%</span>
                </div>
                <div class="progress" style="height: 5px;">
                    <div class="progress-bar ${progressColor}" role="progressbar" style="width: ${openRate}%" 
                         aria-valuenow="${openRate}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted">${campaign.type || 'Email'} • ${campaign.leads || 0} leads</small>
            </div>
        `;
    });
    html += '</div>';
    
    element.innerHTML = html;
}

// Fonction utilitaire pour échapper le HTML
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// Fonction pour rafraîchir les KPIs
function refreshCampaignKPIs() {
    fetch('api/campaigns.php?action=kpis')
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur de chargement');
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success' && data.kpis) {
                updateKPIsDisplay(data.kpis);
            }
        })
        .catch(error => {
            console.error('Erreur lors du rafraîchissement des KPIs:', error);
        });
}

// Mettre à jour l'affichage des KPIs
function updateKPIsDisplay(kpis) {
    const elements = {
        'active-campaigns': kpis.activeCount || 0,
        'avg-open-rate': (kpis.avgOpenRate || 0) + '%',
        'leads-generated': kpis.leadsGenerated || 0,
        'avg-roi': (kpis.avgRoi || 0) + '%'
    };
    
    Object.entries(elements).forEach(([id, value]) => {
        const element = document.getElementById(id);
        if (element) {
            animateValue(element, value);
        }
    });
}

// Animer la valeur d'un élément
function animateValue(element, newValue) {
    element.style.transition = 'all 0.3s ease';
    element.style.transform = 'scale(1.1)';
    element.textContent = newValue;
    
    setTimeout(() => {
        element.style.transform = 'scale(1)';
    }, 300);
}

// Exporter les fonctions pour l'accès global
window.openSendModal = openSendModal;
window.refreshCampaignKPIs = refreshCampaignKPIs;
