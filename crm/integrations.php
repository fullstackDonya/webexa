<?php
/**
 * Page de gestion des intégrations
 * Permet d'activer et configurer les intégrations avec des services tiers
 */

require_once __DIR__ . '/config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['customer_id'])) {
    header('Location: index.php');
    exit;
}

$customerId = (int)$_SESSION['customer_id'];
$pageTitle = "Intégrations - CRM Intelligent";

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        .wrapper {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            padding: 0;
        }

        .integrations-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 0;
            margin-bottom: 2rem;
        }

        .integration-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }

        .integration-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .integration-card:hover {
            border-color: #667eea;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.15);
            transform: translateY(-2px);
        }

        .integration-card:hover::before {
            opacity: 1;
        }

        .integration-card.active {
            border-color: #10b981;
            background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
        }

        .integration-card.active::before {
            background: linear-gradient(90deg, #10b981, #059669);
            opacity: 1;
        }

        .integration-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
        }

        .integration-icon.primary { background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: #2563eb; }
        .integration-icon.success { background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); color: #059669; }
        .integration-icon.danger { background: linear-gradient(135deg, #fecaca 0%, #fca5a5 100%); color: #dc2626; }
        .integration-icon.warning { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #d97706; }
        .integration-icon.info { background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: #0284c7; }
        .integration-icon.dark { background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%); color: #1f2937; }
        .integration-icon.secondary { background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); color: #6366f1; }

        .integration-name {
            font-weight: 700;
            font-size: 1.1rem;
            color: #111827;
            margin-bottom: 0.5rem;
        }

        .integration-description {
            color: #6b7280;
            font-size: 0.875rem;
            margin-bottom: 1rem;
            flex: 1;
        }

        .integration-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.75rem;
        }

        .status-badge.active {
            background: #d1fae5;
            color: #065f46;
        }

        .status-badge.inactive {
            background: #e5e7eb;
            color: #6b7280;
        }

        .status-badge.syncing {
            background: #fef3c7;
            color: #92400e;
        }

        .status-badge.error {
            background: #fee2e2;
            color: #991b1b;
        }

        .integration-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: auto;
        }

        .btn-integrate {
            flex: 1;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-activate {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-activate:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-deactivate {
            background: #f3f4f6;
            color: #6b7280;
        }

        .btn-deactivate:hover {
            background: #e5e7eb;
        }

        .btn-configure {
            background: white;
            border: 2px solid #667eea;
            color: #667eea;
        }

        .btn-configure:hover {
            background: #667eea;
            color: white;
        }

        .btn-sync {
            background: #10b981;
            color: white;
        }

        .btn-sync:hover {
            background: #059669;
        }

        .filter-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .filter-tab {
            padding: 0.5rem 1.25rem;
            border-radius: 25px;
            background: white;
            border: 2px solid #e5e7eb;
            color: #6b7280;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-tab:hover {
            border-color: #667eea;
            color: #667eea;
        }

        .filter-tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
        }

        .sync-info {
            font-size: 0.75rem;
            color: #9ca3af;
            margin-top: 0.5rem;
        }

        .modal-content {
            border-radius: 16px;
            border: none;
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 16px 16px 0 0;
        }

        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            border: 2px solid #e5e7eb;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #111827;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/topbar.php'; ?>
            
            <div class="container-fluid px-4">
                <div class="integrations-header">
                    <h1 style="margin: 0 0 0.5rem 0; font-size: 2rem;">
                        <i class="fas fa-plug"></i> Intégrations
                    </h1>
                    <p style="margin: 0; opacity: 0.9;">
                        Connectez votre CRM à vos outils favoris pour automatiser vos workflows
                    </p>
                </div>

                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value" id="totalIntegrations">0</div>
                        <div class="stat-label">Intégrations disponibles</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="activeIntegrations">0</div>
                        <div class="stat-label">Intégrations actives</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="lastSyncTime">-</div>
                        <div class="stat-label">Dernière synchronisation</div>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="filter-tabs">
                    <button class="filter-tab active" onclick="filterIntegrations('all')">
                        <i class="fas fa-globe"></i> Toutes
                    </button>
                    <button class="filter-tab" onclick="filterIntegrations('active')">
                        <i class="fas fa-check-circle"></i> Actives
                    </button>
                    <button class="filter-tab" onclick="filterIntegrations('inactive')">
                        <i class="fas fa-times-circle"></i> Inactives
                    </button>
                    <button class="filter-tab" onclick="filterIntegrations('cloud')">
                        <i class="fas fa-cloud"></i> Cloud
                    </button>
                    <button class="filter-tab" onclick="filterIntegrations('productivity')">
                        <i class="fas fa-briefcase"></i> Productivité
                    </button>
                    <button class="filter-tab" onclick="filterIntegrations('marketing')">
                        <i class="fas fa-bullhorn"></i> Marketing
                    </button>
                </div>

                <!-- Grille des intégrations -->
                <div class="row g-3" id="integrationsGrid">
                    <!-- Chargement -->
                    <div class="col-12 text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <p class="mt-3 text-muted">Chargement des intégrations...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de configuration -->
    <div class="modal fade" id="configModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-cog"></i> <span id="modalIntegrationName">Configuration</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="configForm">
                        <input type="hidden" id="configIntegrationId">
                        
                        <div class="mb-3">
                            <label for="apiKey" class="form-label">
                                <i class="fas fa-key"></i> Clé API
                            </label>
                            <input type="text" class="form-control" id="apiKey" placeholder="Entrez votre clé API">
                            <small class="text-muted">Obtenue depuis votre compte du service</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="apiSecret" class="form-label">
                                <i class="fas fa-lock"></i> Secret API (optionnel)
                            </label>
                            <input type="password" class="form-control" id="apiSecret" placeholder="Secret API">
                        </div>
                        
                        <div class="mb-3">
                            <label for="webhookUrl" class="form-label">
                                <i class="fas fa-link"></i> Webhook URL (optionnel)
                            </label>
                            <input type="url" class="form-control" id="webhookUrl" placeholder="https://...">
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Astuce :</strong> Consultez la documentation de l'intégration pour obtenir vos identifiants.
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" onclick="saveConfiguration()">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let allIntegrations = [];
        let currentFilter = 'all';
        let configModal;

        document.addEventListener('DOMContentLoaded', function() {
            configModal = new bootstrap.Modal(document.getElementById('configModal'));
            loadIntegrations();
        });

        // Charger les intégrations
        async function loadIntegrations() {
            try {
                const response = await fetch('api/integrations.php?action=list');
                const data = await response.json();
                
                if (data.success) {
                    allIntegrations = data.integrations;
                    updateStats(data);
                    displayIntegrations(allIntegrations);
                }
            } catch (error) {
                console.error('Erreur:', error);
                showError('Impossible de charger les intégrations');
            }
        }

        // Afficher les intégrations
        function displayIntegrations(integrations) {
            const grid = document.getElementById('integrationsGrid');
            
            if (integrations.length === 0) {
                grid.innerHTML = `
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-plug" style="font-size: 4rem; color: #d1d5db;"></i>
                        <p class="mt-3 text-muted">Aucune intégration trouvée</p>
                    </div>
                `;
                return;
            }
            
            grid.innerHTML = integrations.map(integration => `
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="integration-card ${integration.is_active ? 'active' : ''}">
                        <div class="integration-icon ${integration.color}">
                            <i class="${integration.icon}"></i>
                        </div>
                        
                        <div class="integration-name">${integration.name}</div>
                        <div class="integration-description">${integration.description || ''}</div>
                        
                        <div class="integration-status">
                            <span class="status-badge ${integration.is_active ? 'active' : 'inactive'}">
                                ${integration.is_active ? '<i class="fas fa-check"></i> Active' : '<i class="fas fa-times"></i> Inactive'}
                            </span>
                            ${integration.sync_status !== 'never' ? `
                                <span class="status-badge ${integration.sync_status}">
                                    ${integration.sync_status === 'success' ? '<i class="fas fa-sync"></i>' : 
                                      integration.sync_status === 'error' ? '<i class="fas fa-exclamation-triangle"></i>' : 
                                      '<i class="fas fa-spinner fa-spin"></i>'}
                                </span>
                            ` : ''}
                        </div>
                        
                        ${integration.last_sync ? `
                            <div class="sync-info">
                                <i class="far fa-clock"></i> Dernière sync: ${formatDate(integration.last_sync)}
                            </div>
                        ` : ''}
                        
                        <div class="integration-actions">
                            ${!integration.is_active ? `
                                <button class="btn-integrate btn-activate" onclick="toggleIntegration(${integration.id}, 1)">
                                    <i class="fas fa-power-off"></i> Activer
                                </button>
                            ` : `
                                <button class="btn-integrate btn-configure" onclick="configureIntegration(${integration.id}, '${integration.name}')">
                                    <i class="fas fa-cog"></i> Configurer
                                </button>
                                <button class="btn-integrate btn-sync" onclick="syncIntegration(${integration.id})" title="Synchroniser">
                                    <i class="fas fa-sync"></i>
                                </button>
                                <button class="btn-integrate btn-deactivate" onclick="toggleIntegration(${integration.id}, 0)" title="Désactiver">
                                    <i class="fas fa-power-off"></i>
                                </button>
                            `}
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // Mettre à jour les stats
        function updateStats(data) {
            document.getElementById('totalIntegrations').textContent = data.total || 0;
            document.getElementById('activeIntegrations').textContent = data.active_count || 0;
            
            // Trouver la dernière sync
            const lastSync = allIntegrations
                .filter(i => i.last_sync)
                .sort((a, b) => new Date(b.last_sync) - new Date(a.last_sync))[0];
            
            document.getElementById('lastSyncTime').textContent = lastSync 
                ? formatDate(lastSync.last_sync) 
                : 'Jamais';
        }

        // Filtrer les intégrations
        function filterIntegrations(filter) {
            currentFilter = filter;
            
            // Mettre à jour les tabs
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.closest('.filter-tab').classList.add('active');
            
            let filtered = allIntegrations;
            
            if (filter === 'active') {
                filtered = allIntegrations.filter(i => i.is_active);
            } else if (filter === 'inactive') {
                filtered = allIntegrations.filter(i => !i.is_active);
            } else if (filter === 'cloud') {
                filtered = allIntegrations.filter(i => 
                    ['google_drive', 'dropbox', 'notion'].includes(i.integration_type)
                );
            } else if (filter === 'productivity') {
                filtered = allIntegrations.filter(i => 
                    ['slack', 'microsoft_teams', 'zoom', 'jira', 'trello', 'github', 'gitlab'].includes(i.integration_type)
                );
            } else if (filter === 'marketing') {
                filtered = allIntegrations.filter(i => 
                    ['meta_ads', 'google_ads', 'mailchimp', 'hubspot'].includes(i.integration_type)
                );
            }
            
            displayIntegrations(filtered);
        }

        // Toggle activation
        async function toggleIntegration(id, isActive) {
            try {
                const formData = new FormData();
                formData.append('id', id);
                formData.append('is_active', isActive);
                
                const response = await fetch('api/integrations.php?action=toggle', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showSuccess(data.message);
                    loadIntegrations();
                } else {
                    showError(data.error);
                }
            } catch (error) {
                console.error('Erreur:', error);
                showError('Une erreur est survenue');
            }
        }

        // Configurer une intégration
        function configureIntegration(id, name) {
            document.getElementById('configIntegrationId').value = id;
            document.getElementById('modalIntegrationName').textContent = name;
            
            // Charger la config existante
            const integration = allIntegrations.find(i => i.id === id);
            if (integration) {
                document.getElementById('apiKey').value = integration.api_key || '';
                document.getElementById('apiSecret').value = integration.api_secret || '';
                document.getElementById('webhookUrl').value = integration.webhook_url || '';
            }
            
            configModal.show();
        }

        // Sauvegarder la configuration
        async function saveConfiguration() {
            const id = document.getElementById('configIntegrationId').value;
            const apiKey = document.getElementById('apiKey').value;
            const apiSecret = document.getElementById('apiSecret').value;
            const webhookUrl = document.getElementById('webhookUrl').value;
            
            try {
                const response = await fetch('api/integrations.php?action=configure', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: parseInt(id),
                        api_key: apiKey,
                        api_secret: apiSecret,
                        webhook_url: webhookUrl
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showSuccess('Configuration enregistrée');
                    configModal.hide();
                    loadIntegrations();
                } else {
                    showError(data.error);
                }
            } catch (error) {
                console.error('Erreur:', error);
                showError('Impossible d\'enregistrer la configuration');
            }
        }

        // Synchroniser une intégration
        async function syncIntegration(id) {
            const btn = event.target.closest('button');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;
            
            try {
                const formData = new FormData();
                formData.append('id', id);
                
                const response = await fetch('api/integrations.php?action=sync', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showSuccess('Synchronisation réussie');
                } else {
                    showError(data.error || 'Erreur de synchronisation');
                }
                
                loadIntegrations();
            } catch (error) {
                console.error('Erreur:', error);
                showError('Impossible de synchroniser');
            } finally {
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            }
        }

        // Utilitaires
        function formatDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diff = Math.floor((now - date) / 1000);
            
            if (diff < 60) return 'À l\'instant';
            if (diff < 3600) return Math.floor(diff / 60) + ' min';
            if (diff < 86400) return Math.floor(diff / 3600) + ' h';
            if (diff < 604800) return Math.floor(diff / 86400) + ' j';
            
            return date.toLocaleDateString('fr-FR', { 
                day: '2-digit', 
                month: 'short',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function showSuccess(message) {
            alert('✅ ' + message);
        }

        function showError(message) {
            alert('❌ ' + message);
        }
    </script>
</body>
</html>
