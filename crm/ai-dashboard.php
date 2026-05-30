<?php
/**
 * Tableau de bord des agents IA
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
$pageTitle = "Agents IA - Dashboard";

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
        .ai-dashboard {
            padding: 2rem;
        }

        .ai-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
        }

        .ai-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .ai-stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s;
        }

        .ai-stat-card:hover {
            transform: translateY(-4px);
        }

        .ai-stat-card .icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .ai-stat-card.inbox .icon {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }

        .ai-stat-card.leads .icon {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .ai-stat-card.actions .icon {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        .ai-stat-card.logs .icon {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            color: white;
        }

        .ai-section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .ai-section h3 {
            margin: 0 0 1.5rem 0;
            font-size: 1.25rem;
            color: #111827;
        }

        .action-card {
            background: linear-gradient(to right, #ffffff, #fafbfc);
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 1.75rem;
            margin-bottom: 1.25rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .action-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 5px;
            background: linear-gradient(180deg, #667eea, #764ba2);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .action-card:hover {
            border-color: #667eea;
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.15);
            transform: translateY(-2px);
        }

        .action-card:hover::before {
            opacity: 1;
        }

        .action-card.pending::before {
            background: linear-gradient(180deg, #f59e0b, #d97706);
            opacity: 1;
        }

        .action-card.approved::before {
            background: linear-gradient(180deg, #10b981, #059669);
            opacity: 1;
        }

        .action-card.rejected::before {
            background: linear-gradient(180deg, #ef4444, #dc2626);
            opacity: 1;
        }

        .action-header {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .action-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .action-icon.email_response {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }

        .action-icon.email_processed {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            color: white;
        }

        .action-icon.lead_action {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .action-content {
            flex: 1;
            min-width: 0;
        }

        .action-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }

        .action-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.75rem;
            flex-wrap: wrap;
        }

        .action-description {
            background: #f9fafb;
            border-left: 3px solid #667eea;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            font-size: 0.925rem;
            line-height: 1.6;
            color: #374151;
        }

        .action-buttons {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.25rem;
            padding-top: 1.25rem;
            border-top: 1px solid #e5e7eb;
        }

        .btn-approve {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 0.625rem 1.5rem;
            border-radius: 10px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.25);
        }

        .btn-approve:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(16, 185, 129, 0.35);
        }

        .btn-approve:active {
            transform: translateY(0);
        }

        .btn-reject {
            background: transparent;
            color: #ef4444;
            border: 2px solid #ef4444;
            padding: 0.625rem 1.5rem;
            border-radius: 10px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-reject:hover {
            background: #ef4444;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(239, 68, 68, 0.25);
        }

        .btn-reject:active {
            transform: translateY(0);
        }

        .agent-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.875rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.025em;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        }

        .agent-badge.inbox {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #1e40af;
        }

        .agent-badge.lead {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
        }

        .priority-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.625rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .priority-badge.haute {
            background: #fee2e2;
            color: #991b1b;
        }

        .priority-badge.moyenne {
            background: #fef3c7;
            color: #92400e;
        }

        .priority-badge.basse {
            background: #e0e7ff;
            color: #3730a3;
        }

        /* Réponse suggérée */
        .suggested-response {
            background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%);
            border: 1px solid #e9d5ff;
            border-left: 4px solid #a855f7;
            padding: 1rem;
            border-radius: 10px;
            margin-top: 1rem;
            box-shadow: 0 1px 3px rgba(168, 85, 247, 0.1);
        }

        .suggested-response strong {
            font-weight: 600;
        }

        /* Carte de lead */
        .lead-card {
            border-left-color: #10b981 !important;
        }

        .lead-card::before {
            background: linear-gradient(to bottom, #10b981, #059669) !important;
        }

        .loading {
            text-align: center;
            padding: 2rem;
            color: #6b7280;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #9ca3af;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        <?php include 'includes/topbar.php'; ?>
        
        <div class="main-content">
            
            <div class="container-fluid">
                <div class="ai-header">
                    <h1 style="margin: 0 0 0.5rem 0; font-size: 2rem;">
                        <i class="fas fa-robot"></i> Agents IA
                    </h1>
                    <p style="margin: 0; opacity: 0.9;">
                        Intelligence artificielle au service de votre CRM
                    </p>
                </div>

                <!-- Stats -->
                <div class="ai-stats">
                    <div class="ai-stat-card inbox">
                        <div class="icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="label" style="color: #6b7280; font-size: 0.875rem;">Emails analysés</div>
                        <div class="value" style="font-size: 2rem; font-weight: 700; color: #111827;" id="emailsCount">-</div>
                    </div>

                    <div class="ai-stat-card leads">
                        <div class="icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="label" style="color: #6b7280; font-size: 0.875rem;">Leads scorés</div>
                        <div class="value" style="font-size: 2rem; font-weight: 700; color: #111827;" id="leadsCount">-</div>
                    </div>

                    <div class="ai-stat-card actions">
                        <div class="icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div class="label" style="color: #6b7280; font-size: 0.875rem;">Actions en attente</div>
                        <div class="value" style="font-size: 2rem; font-weight: 700; color: #111827;" id="actionsCount">-</div>
                    </div>

                    <div class="ai-stat-card logs">
                        <div class="icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="label" style="color: #6b7280; font-size: 0.875rem;">Taux de succès</div>
                        <div class="value" style="font-size: 2rem; font-weight: 700; color: #111827;" id="successRate">-</div>
                    </div>
                </div>

                <!-- Actions en attente -->
                <div class="ai-section">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h3 style="margin: 0;">
                            <i class="fas fa-bell"></i> Actions proposées par l'IA
                        </h3>
                        <button onclick="refreshActions()" class="btn btn-ghost" style="font-size: 0.875rem;">
                            <i class="fas fa-sync"></i> Actualiser
                        </button>
                    </div>
                    <div id="actionsContainer">
                        <div class="loading">
                            <i class="fas fa-spinner fa-spin"></i> Chargement...
                        </div>
                    </div>
                </div>

                <!-- Actions rapides -->
                <div class="ai-section">
                    <h3>
                        <i class="fas fa-bolt"></i> Actions rapides
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                        <button onclick="processEmails()" class="btn" style="background: linear-gradient(135deg, #3b82f6, #2563eb); color: white;">
                            <i class="fas fa-envelope-open"></i> Analyser les emails
                        </button>
                        <button onclick="scoreLeads()" class="btn" style="background: linear-gradient(135deg, #10b981, #059669); color: white;">
                            <i class="fas fa-chart-bar"></i> Scorer les leads
                        </button>
                        <button onclick="viewLogs()" class="btn" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white;">
                            <i class="fas fa-file-alt"></i> Voir les logs
                        </button>
                        <button onclick="checkHealth()" class="btn" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white;">
                            <i class="fas fa-heartbeat"></i> État du système
                        </button>
                    </div>
                </div>

                <!-- Leads chauds -->
                <div class="ai-section">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h3 style="margin: 0;">
                            <i class="fas fa-fire" style="color: #ef4444;"></i> Leads Chauds (Score ≥ 70)
                        </h3>
                        <button onclick="loadHotLeads()" class="btn" style="background: white; color: #667eea; border: 2px solid #667eea; padding: 0.5rem 1rem; font-size: 0.875rem;">
                            <i class="fas fa-sync-alt"></i> Rafraîchir
                        </button>
                    </div>
                    <div id="hotLeadsContainer">
                        <div class="loading">
                            <i class="fas fa-spinner fa-spin"></i> Chargement...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Charger les actions au démarrage
        document.addEventListener('DOMContentLoaded', function() {
            loadStats();
            loadPendingActions();
            loadHotLeads();
        });

        // Charger les statistiques
        async function loadStats() {
            try {
                const response = await fetch('api/ai-agents.php?action=stats');
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const contentType = response.headers.get("content-type");
                if (!contentType || !contentType.includes("application/json")) {
                    const text = await response.text();
                    console.error('Réponse non-JSON:', text);
                    throw new Error("La réponse n'est pas du JSON");
                }
                
                const data = await response.json();
                
                if (data.success) {
                    // Calculer les stats
                    const stats = data.data.stats;
                    const totalEmails = stats.filter(s => s.agent_name === 'inbox_agent')
                        .reduce((sum, s) => sum + parseInt(s.total_actions), 0);
                    const totalLeads = stats.filter(s => s.agent_name === 'lead_analyst_agent')
                        .reduce((sum, s) => sum + parseInt(s.total_actions), 0);
                    const avgSuccess = stats.length > 0
                        ? stats.reduce((sum, s) => sum + parseFloat(s.success_rate), 0) / stats.length
                        : 0;

                    document.getElementById('emailsCount').textContent = totalEmails;
                    document.getElementById('leadsCount').textContent = totalLeads;
                    document.getElementById('actionsCount').textContent = data.data.pending_actions;
                    document.getElementById('successRate').textContent = avgSuccess.toFixed(1) + '%';
                } else {
                    console.error('Erreur API:', data.error);
                }
            } catch (error) {
                console.error('Erreur chargement stats:', error);
                // Afficher des valeurs par défaut
                document.getElementById('emailsCount').textContent = '0';
                document.getElementById('leadsCount').textContent = '0';
                document.getElementById('actionsCount').textContent = '0';
                document.getElementById('successRate').textContent = '0%';
            }
        }

        // Charger les actions en attente
        async function loadPendingActions() {
            const container = document.getElementById('actionsContainer');
            
            try {
                const response = await fetch('api/ai-agents.php?action=pending-actions');
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const contentType = response.headers.get("content-type");
                if (!contentType || !contentType.includes("application/json")) {
                    const text = await response.text();
                    console.error('Réponse non-JSON:', text);
                    throw new Error("La réponse du serveur n'est pas du JSON. Vérifiez les logs PHP.");
                }
                
                const data = await response.json();
                
                if (data.success && data.data.actions && data.data.actions.length > 0) {
                    container.innerHTML = '';
                    
                    data.data.actions.forEach(action => {
                        const card = createActionCard(action);
                        container.appendChild(card);
                    });
                } else {
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-check-circle"></i>
                            <h3>Aucune action en attente</h3>
                            <p>Toutes les suggestions IA ont été traitées !</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Erreur chargement actions:', error);
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p style="color: #ef4444;">Erreur de connexion à l'API IA</p>
                        <p style="font-size: 0.875rem;">${error.message}</p>
                        <button onclick="loadPendingActions()" class="btn btn-primary" style="margin-top: 1rem;">
                            <i class="fas fa-sync"></i> Réessayer
                        </button>
                    </div>
                `;
            }
        }

        // Créer une carte d'action
        function createActionCard(action) {
            const div = document.createElement('div');
            div.className = `action-card ${action.status}`;
            
            const actionData = JSON.parse(action.data);
            const agentClass = action.agent_name.replace('_agent', '');
            
            // Déterminer l'icône et la classe selon le type d'action
            let iconClass = 'fa-bolt';
            let typeClass = 'default';
            
            if (action.action_type.includes('email_response')) {
                iconClass = 'fa-reply';
                typeClass = 'email_response';
            } else if (action.action_type.includes('email_processed')) {
                iconClass = 'fa-envelope-open';
                typeClass = 'email_processed';
            } else if (action.action_type.includes('lead')) {
                iconClass = 'fa-user-plus';
                typeClass = 'lead_action';
            }
            
            // Déterminer la priorité
            const priority = actionData.priority || actionData.analysis?.priority || 'moyenne';
            const priorityLabels = { 'high': 'haute', 'medium': 'moyenne', 'low': 'basse', 'haute': 'haute', 'moyenne': 'moyenne', 'basse': 'basse' };
            const priorityText = priorityLabels[priority.toLowerCase()] || 'moyenne';
            
            div.innerHTML = `
                <div class="action-header">
                    <div class="action-icon ${typeClass}">
                        <i class="fas ${iconClass}"></i>
                    </div>
                    <div class="action-content">
                        <div class="action-title">
                            ${action.action_type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}
                        </div>
                        <div class="action-meta">
                            <span class="agent-badge ${agentClass}">
                                <i class="fas fa-robot"></i> ${action.agent_name.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}
                            </span>
                            <span class="priority-badge ${priorityText}">
                                <i class="fas fa-flag"></i> ${priorityText.charAt(0).toUpperCase() + priorityText.slice(1)}
                            </span>
                            <span style="color: #9ca3af; font-size: 0.8125rem;">
                                <i class="far fa-clock"></i> ${new Date(action.created_at).toLocaleString('fr-FR', { 
                                    day: '2-digit', 
                                    month: 'short', 
                                    hour: '2-digit', 
                                    minute: '2-digit' 
                                })}
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="action-description">
                    ${actionData.analysis?.summary || actionData.action || 'Action proposée par l\'intelligence artificielle'}
                </div>
                
                ${actionData.suggested_response ? `
                    <div class="suggested-response">
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <i class="fas fa-magic" style="color: #8b5cf6;"></i>
                            <strong style="color: #6b21a8;">Réponse suggérée :</strong>
                        </div>
                        <p style="margin: 0; line-height: 1.6; color: #4b5563;">${actionData.suggested_response}</p>
                    </div>
                ` : ''}
                
                <div class="action-buttons">
                    <button onclick="approveAction(${action.id})" class="btn-approve">
                        <i class="fas fa-check"></i> Approuver
                    </button>
                    <button onclick="rejectAction(${action.id})" class="btn-reject">
                        <i class="fas fa-times"></i> Rejeter
                    </button>
                </div>
            `;
            
            return div;
        }

        // Approuver une action
        async function approveAction(actionId) {
            if (!confirm('Approuver cette action proposée par l\'IA ?')) return;
            
            try {
                const formData = new FormData();
                formData.append('action', 'approve-action');
                formData.append('action_id', actionId);
                
                const response = await fetch('api/ai-agents.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('✅ Action approuvée !');
                    refreshActions();
                } else {
                    alert('❌ Erreur : ' + data.error);
                }
            } catch (error) {
                alert('❌ Erreur : ' + error.message);
            }
        }

        // Rejeter une action
        async function rejectAction(actionId) {
            if (!confirm('Rejeter cette suggestion de l\'IA ?')) return;
            
            try {
                const formData = new FormData();
                formData.append('action', 'reject-action');
                formData.append('action_id', actionId);
                
                const response = await fetch('api/ai-agents.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('✅ Action rejetée');
                    refreshActions();
                } else {
                    alert('❌ Erreur : ' + data.error);
                }
            } catch (error) {
                alert('❌ Erreur : ' + error.message);
            }
        }

        // Contacter un lead
        function contactLead(leadId, email) {
            // Option 1: Ouvrir le client email par défaut
            window.location.href = `mailto:${email}?subject=Bonjour de Webitech&body=Bonjour,%0D%0A%0D%0ANous avons bien reçu votre demande...`;
            
            // Option 2 (alternative): Rediriger vers une page de contact/compose
            // window.location.href = `campaigns-email.php?to=${email}&lead_id=${leadId}`;
        }

        // Rafraîchir les actions
        function refreshActions() {
            loadStats();
            loadPendingActions();
        }

        // Traiter les emails
        async function processEmails() {
            if (!confirm('Lancer l\'analyse automatique des emails ?')) return;
            
            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Analyse en cours...';
            
            try {
                const formData = new FormData();
                formData.append('action', 'process-emails');
                formData.append('limit', '10');
                
                const response = await fetch('api/ai-agents.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(`✅ ${data.data.processed} emails analysés !`);
                    refreshActions();
                } else {
                    alert('❌ Erreur : ' + data.error);
                }
            } catch (error) {
                alert('❌ Erreur : ' + error.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-envelope-open"></i> Analyser les emails';
            }
        }

        // Scorer les leads
        async function scoreLeads() {
            if (!confirm('Lancer le scoring automatique de tous les leads actifs ?')) return;
            
            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Scoring en cours...';
            
            try {
                const response = await fetch('api/ai-agents.php?action=score-all-leads', {
                    method: 'POST'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(`✅ Scoring terminé !\n\nTotal: ${data.data.total} leads\nScorés: ${data.data.scored}\nErreurs: ${data.data.errors}`);
                    // Recharger les stats et leads
                    loadStats();
                    loadHotLeads();
                } else {
                    alert('❌ Erreur : ' + data.error);
                }
            } catch (error) {
                alert('❌ Erreur : ' + error.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-chart-bar"></i> Scorer les leads';
            }
        }

        // Voir les logs
        function viewLogs() {
            window.location.href = 'ai-logs.php';
        }

        // Charger les leads chauds
        async function loadHotLeads() {
            const container = document.getElementById('hotLeadsContainer');
            if (!container) return;
            
            try {
                const response = await fetch('api/ai-agents.php?action=hot-leads&limit=10');
                const data = await response.json();
                
                if (data.success && data.data.leads && data.data.leads.length > 0) {
                    container.innerHTML = '';
                    
                    data.data.leads.forEach(lead => {
                        const card = createLeadCard(lead);
                        container.appendChild(card);
                    });
                } else {
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-snowflake"></i>
                            <p>Aucun lead chaud pour le moment</p>
                            <small>Lancez le scoring automatique pour évaluer vos leads</small>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Erreur chargement leads chauds:', error);
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>Erreur de chargement</p>
                    </div>
                `;
            }
        }

        // Créer une carte de lead
        function createLeadCard(lead) {
            const div = document.createElement('div');
            div.className = 'action-card lead-card';
            
            const scoreColor = lead.score >= 80 ? '#ef4444' : lead.score >= 60 ? '#f59e0b' : '#10b981';
            const scoreGradient = lead.score >= 80 
                ? 'linear-gradient(135deg, #fee2e2 0%, #fecaca 100%)' 
                : lead.score >= 60 
                ? 'linear-gradient(135deg, #fef3c7 0%, #fde68a 100%)'
                : 'linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%)';
            
            const categoryLabel = (lead.score_category || 'lead').toUpperCase();
            const categoryIcon = lead.score >= 80 ? 'fa-fire' : lead.score >= 60 ? 'fa-star' : 'fa-user-plus';
            
            div.innerHTML = `
                <div class="action-header">
                    <div class="action-icon lead_action">
                        <i class="fas ${categoryIcon}"></i>
                    </div>
                    <div class="action-content">
                        <div class="action-title">
                            ${lead.first_name || ''} ${lead.last_name || 'Lead sans nom'}
                        </div>
                        <div class="action-meta">
                            <span class="agent-badge lead">
                                <i class="fas fa-robot"></i> Lead Analyst
                            </span>
                            <span class="priority-badge ${lead.score >= 80 ? 'haute' : lead.score >= 60 ? 'moyenne' : 'basse'}">
                                <i class="fas fa-flag"></i> ${categoryLabel}
                            </span>
                        </div>
                    </div>
                    <div style="text-align: center; min-width: 80px;">
                        <div style="background: ${scoreGradient}; padding: 0.75rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            <div style="font-size: 1.75rem; font-weight: 800; color: ${scoreColor}; line-height: 1;">
                                ${lead.score}
                            </div>
                            <div style="font-size: 0.7rem; color: #6b7280; font-weight: 600; margin-top: 0.25rem;">
                                / 100
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="action-description" style="margin-top: 1rem;">
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; color: #4b5563;">
                            <i class="fas fa-envelope" style="width: 16px; color: #8b5cf6;"></i>
                            <span style="font-size: 0.875rem;">${lead.email}</span>
                        </div>
                        ${lead.phone ? `
                            <div style="display: flex; align-items: center; gap: 0.5rem; color: #4b5563;">
                                <i class="fas fa-phone" style="width: 16px; color: #10b981;"></i>
                                <span style="font-size: 0.875rem;">${lead.phone}</span>
                            </div>
                        ` : ''}
                    </div>
                </div>
                
                ${lead.score_reasoning ? `
                    <div class="suggested-response" style="margin-top: 1rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <i class="fas fa-lightbulb" style="color: #f59e0b;"></i>
                            <strong style="color: #92400e;">Analyse IA :</strong>
                        </div>
                        <p style="margin: 0; line-height: 1.6; color: #4b5563; font-size: 0.875rem;">${lead.score_reasoning}</p>
                    </div>
                ` : ''}
                
                <div class="action-buttons" style="margin-top: 1rem;">
                    <button onclick="window.location.href='leads-view.php?id=${lead.id}'" class="btn-approve">
                        <i class="fas fa-eye"></i> Voir le lead
                    </button>
                    <button onclick="contactLead(${lead.id}, '${lead.email}')" class="btn-reject" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;">
                        <i class="fas fa-envelope"></i> Contacter
                    </button>
                </div>
            `;
            
            return div;
        }

        // Vérifier l'état du système
        async function checkHealth() {
            try {
                const response = await fetch('api/ai-agents.php?action=health');
                const data = await response.json();
                
                if (data.success) {
                    const health = data.data;
                    alert(`✅ Système opérationnel\n\nDatabase: ${health.database}\nAgents actifs: ${Object.values(health.agents).filter(Boolean).length}/${Object.keys(health.agents).length}`);
                } else {
                    alert('❌ Système hors ligne');
                }
            } catch (error) {
                alert('❌ API IA non accessible\n\n' + error.message);
            }
        }
    </script>
</body>
</html>
