<?php
/**
 * Page de gestion des notifications
 * Affiche toutes les notifications avec filtres et actions
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
$pageTitle = "Notifications - CRM Intelligent";

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
        }

        .wrapper {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            padding: 0;
        }

        .notifications-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            margin-bottom: 2rem;
            color: black;
        }

        .notification-card {
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 0.75rem;
            border-left: 4px solid #e5e7eb;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .notification-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateX(4px);
        }

        .notification-card.unread {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border-left-color: #3b82f6;
        }

        .notification-card.priority-urgent {
            border-left-color: #dc2626;
        }

        .notification-card.priority-high {
            border-left-color: #f59e0b;
        }

        .notification-header {
            display: flex;
            align-items: start;
            gap: 1rem;
            margin-bottom: 0.75rem;
        }

        .notification-icon-wrapper {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 1.25rem;
        }

        .notification-icon-wrapper.success {
            background: #d1fae5;
            color: #059669;
        }

        .notification-icon-wrapper.info {
            background: #dbeafe;
            color: #2563eb;
        }

        .notification-icon-wrapper.warning {
            background: #fef3c7;
            color: #d97706;
        }

        .notification-icon-wrapper.danger {
            background: #fee2e2;
            color: #dc2626;
        }

        .notification-info {
            flex: 1;
        }

        .notification-title {
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.25rem;
        }

        .notification-message {
            color: #6b7280;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .notification-meta {
            display: flex;
            gap: 1rem;
            align-items: center;
            margin-top: 0.5rem;
            font-size: 0.8rem;
            color: #9ca3af;
        }

        .notification-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .priority-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .priority-badge.urgent {
            background: #fee2e2;
            color: #991b1b;
        }

        .priority-badge.high {
            background: #fef3c7;
            color: #92400e;
        }

        .priority-badge.medium {
            background: #e0e7ff;
            color: #3730a3;
        }

        .priority-badge.low {
            background: #f3f4f6;
            color: #6b7280;
        }

        .filter-bar {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-btn {
            padding: 0.5rem 1rem;
            border-radius: 25px;
            border: 2px solid #e5e7eb;
            background: white;
            color: #6b7280;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn:hover {
            border-color: #667eea;
            color: #667eea;
        }

        .filter-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
        }

        .btn-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-icon:hover {
            transform: scale(1.1);
        }

        .btn-mark-read {
            background: #e0e7ff;
            color: #4f46e5;
        }

        .btn-delete {
            background: #fee2e2;
            color: #dc2626;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #9ca3af;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/topbar.php'; ?>
            
            <div class="container-fluid px-4">
                <div class="notifications-header">
                    <h1 style="margin: 0 0 0.5rem 0; font-size: 2rem;">
                        <i class="fas fa-bell"></i> Notifications
                    </h1>
                    <p style="margin: 0; opacity: 0.9;">
                        Restez informé de toutes les activités importantes de votre CRM
                    </p>
                </div>

                <!-- Barre de filtres -->
                <div class="filter-bar">
                    <button class="filter-btn active" onclick="filterNotifications('all', this)">
                        <i class="fas fa-globe"></i> Toutes
                    </button>
                    <button class="filter-btn" onclick="filterNotifications('unread', this)">
                        <i class="fas fa-envelope"></i> Non lues
                    </button>
                    <button class="filter-btn" onclick="filterNotifications('urgent', this)">
                        <i class="fas fa-exclamation-circle"></i> Urgentes
                    </button>
                    <button class="filter-btn" onclick="filterNotifications('leads', this)">
                        <i class="fas fa-user-plus"></i> Leads
                    </button>
                    <button class="filter-btn" onclick="filterNotifications('campaigns', this)">
                        <i class="fas fa-bullhorn"></i> Campagnes
                    </button>
                    <button class="filter-btn" onclick="filterNotifications('integrations', this)">
                        <i class="fas fa-plug"></i> Intégrations
                    </button>
                    
                    <div style="flex: 1;"></div>
                    
                    <button class="btn btn-sm btn-outline-primary" onclick="markAllAsRead()">
                        <i class="fas fa-check-double"></i> Tout marquer comme lu
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteAllRead()">
                        <i class="fas fa-trash"></i> Supprimer les lues
                    </button>
                </div>

                <!-- Liste des notifications -->
                <div id="notificationsList">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-3 text-muted">Chargement des notifications...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        console.log('1. Script chargé');
        
        let allNotifications = [];
        let currentFilter = 'all';
        
        console.log('2. Variables initialisées');

        document.addEventListener('DOMContentLoaded', function() {
            console.log('3. DOM chargé');
            loadNotifications();
        });

        // Charger toutes les notifications
        async function loadNotifications() {
            console.log('4. loadNotifications() appelée');
            const container = document.getElementById('notificationsList');
            
            if (!container) {
                console.error('Container introuvable!');
                return;
            }
            
            try {
                console.log('5. Fetch API...');
                const response = await fetch('api/notifications.php?action=list&limit=200');
                console.log('6. Réponse:', response.status);
                
                const data = await response.json();
                console.log('7. Data:', data);
                
                if (data.success && data.notifications) {
                    console.log('8. Notifications:', data.notifications.length);
                    allNotifications = data.notifications;
                    
                    if (data.notifications.length === 0) {
                        container.innerHTML = '<div class="empty-state"><i class="fas fa-bell-slash"></i><h3>Aucune notification</h3></div>';
                        return;
                    }
                    
                    displayNotifications(allNotifications);
                } else {
                    container.innerHTML = '<div class="alert alert-danger">Erreur: ' + (data.error || 'Inconnue') + '</div>';
                }
            } catch (error) {
                console.error('9. ERREUR:', error);
                container.innerHTML = '<div class="alert alert-danger">Erreur: ' + error.message + '</div>';
            }
        }

        // Afficher les notifications
        function displayNotifications(notifications) {
            console.log('>>> displayNotifications() APPELÉE avec', notifications.length, 'notifications'); // Debug
            
            const container = document.getElementById('notificationsList');
            console.log('>>> Container trouvé:', container); // Debug
            
            if (!container) {
                console.error('Container notificationsList introuvable !');
                return;
            }
            
            console.log('Affichage de', notifications.length, 'notifications'); // Debug
            
            if (notifications.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-bell-slash"></i>
                        <h3>Aucune notification</h3>
                        <p>Vous êtes à jour !</p>
                    </div>
                `;
                return;
            }
            
            const html = notifications.map(notif => {
                // Debug chaque notification
                console.log('Notification:', notif.id, notif.title, 'is_read:', notif.is_read, 'type:', typeof notif.is_read);
                
                const notifLink = (notif.link || '').replace(/'/g, "&#39;");
                
                return `
                <div class="notification-card ${!notif.is_read ? 'unread' : ''} priority-${notif.priority}" 
                     data-id="${notif.id}"
                     data-link="${notifLink}"
                     onclick="handleNotificationClick(${notif.id}, this.dataset.link)"
                     style="cursor: pointer;">
                    <div class="notification-header">
                        <div class="notification-icon-wrapper ${notif.color}">
                            <i class="${notif.icon}"></i>
                        </div>
                        <div class="notification-info">
                            <div class="notification-title">${notif.title}</div>
                            <div class="notification-message">${notif.message}</div>
                            <div class="notification-meta">
                                <span>
                                    <i class="far fa-clock"></i> ${formatTime(notif.created_at)}
                                </span>
                                ${notif.priority !== 'medium' ? `
                                    <span class="priority-badge ${notif.priority}">
                                        ${notif.priority === 'urgent' ? 'Urgent' : 
                                          notif.priority === 'high' ? 'Haute priorité' :
                                          notif.priority === 'low' ? 'Basse priorité' : 'Moyenne'}
                                    </span>
                                ` : ''}
                            </div>
                        </div>
                        <div class="notification-actions" onclick="event.stopPropagation()">
                            ${!notif.is_read ? `
                                <button class="btn-icon btn-mark-read" 
                                        onclick="markAsRead(${notif.id})" 
                                        title="Marquer comme lu">
                                    <i class="fas fa-check"></i>
                                </button>
                            ` : ''}
                            <button class="btn-icon btn-delete" 
                                    onclick="deleteNotification(${notif.id})" 
                                    title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `}).join('');
            
            console.log('HTML généré, longueur:', html.length); // Debug
            console.log('Avant innerHTML, container.children.length:', container.children.length); // Debug
            container.innerHTML = html;
            console.log('Après innerHTML, container.children.length:', container.children.length); // Debug
            console.log('HTML inséré dans le DOM'); // Debug
            
            // Vérifier que le HTML est vraiment là
            setTimeout(() => {
                console.log('Vérification après 100ms - Enfants dans container:', container.children.length);
                console.log('Premier enfant:', container.children[0]);
            }, 100);
        }

        // Filtrer les notifications
        function filterNotifications(filter, button) {
            currentFilter = filter;
            
            console.log('Filtre appliqué:', filter); // Debug
            
            // Mettre à jour les boutons
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            if (button) {
                button.classList.add('active');
            }
            
            let filtered = allNotifications;
            
            if (filter === 'unread') {
                filtered = allNotifications.filter(n => !n.is_read);
            } else if (filter === 'urgent') {
                filtered = allNotifications.filter(n => n.priority === 'urgent' || n.priority === 'high');
            } else if (filter === 'leads') {
                filtered = allNotifications.filter(n => n.type.includes('lead'));
            } else if (filter === 'campaigns') {
                filtered = allNotifications.filter(n => n.type.includes('campaign'));
            } else if (filter === 'integrations') {
                filtered = allNotifications.filter(n => n.type.includes('integration'));
            }
            
            displayNotifications(filtered);
        }

        // Gérer le clic sur une notification
        async function handleNotificationClick(notifId, link) {
            await markAsRead(notifId);
            
            if (link) {
                window.location.href = link;
            }
        }

        // Marquer comme lu
        async function markAsRead(notifId) {
            try {
                const response = await fetch(`api/notifications.php?action=mark-read&id=${notifId}`, {
                    method: 'POST'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    loadNotifications();
                }
            } catch (error) {
                console.error('Erreur:', error);
            }
        }

        // Marquer toutes comme lues
        async function markAllAsRead() {
            if (!confirm('Marquer toutes les notifications comme lues ?')) return;
            
            try {
                const response = await fetch('api/notifications.php?action=mark-all-read', {
                    method: 'POST'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('✅ Toutes les notifications ont été marquées comme lues');
                    loadNotifications();
                }
            } catch (error) {
                console.error('Erreur:', error);
            }
        }

        // Supprimer une notification
        async function deleteNotification(notifId) {
            if (!confirm('Supprimer cette notification ?')) return;
            
            try {
                const response = await fetch(`api/notifications.php?action=delete&id=${notifId}`, {
                    method: 'POST'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    loadNotifications();
                }
            } catch (error) {
                console.error('Erreur:', error);
            }
        }

        // Supprimer toutes les notifications lues
        async function deleteAllRead() {
            if (!confirm('Supprimer toutes les notifications lues ?')) return;
            
            try {
                const response = await fetch('api/notifications.php?action=delete-all-read', {
                    method: 'POST'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(`✅ ${data.deleted} notification(s) supprimée(s)`);
                    loadNotifications();
                }
            } catch (error) {
                console.error('Erreur:', error);
            }
        }

        // Formater le temps
        function formatTime(timestamp) {
            const date = new Date(timestamp);
            const now = new Date();
            const diff = Math.floor((now - date) / 1000);
            
            if (diff < 60) return 'À l\'instant';
            if (diff < 3600) return Math.floor(diff / 60) + ' min';
            if (diff < 86400) return Math.floor(diff / 3600) + ' h';
            if (diff < 604800) return Math.floor(diff / 86400) + ' j';
            
            return date.toLocaleDateString('fr-FR', { 
                day: '2-digit', 
                month: 'short',
                year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined,
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    </script>
</body>
</html>
