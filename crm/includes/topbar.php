<!-- CRM Topbar with Notifications -->
<style>
    .crm-topbar {

      background: linear-gradient(220deg,
        rgba(37, 99, 235, 0.95) 0%,
        rgba(15, 23, 42, 0.92) 100%
    );
    color: white;
        padding: 0.6rem 2rem;
        height: 56px;
        box-shadow: 0 2px 12px rgba(102, 126, 234, 0.12), 0 1px 4px rgba(0,0,0,0.06);
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        width: 100%;
        z-index: 1040;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .topbar-left {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .topbar-logo {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 700;
        font-size: 1.1rem;
        text-decoration: none;
        color: white;
        transition: opacity 0.3s ease;
    }

    .topbar-logo:hover {
        opacity: 0.85;
    }

    .topbar-logo i {
        font-size: 1.3rem;
    }

    .topbar-search {
        position: relative;
    }

    .topbar-search input {
        background: rgba(255,255,255,0.25);
        border: 1px solid rgba(255,255,255,0.35);
        color: white;
        padding: 0.45rem 0.9rem 0.45rem 2.4rem;
        border-radius: 24px;
        width: 280px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        font-size: 0.9rem;
        height: 38px;
    }

    .topbar-search input::placeholder {
        color: rgba(255,255,255,0.75);
    }

    .topbar-search input:focus {
        background: rgba(255,255,255,0.35);
        outline: none;
        box-shadow: 0 0 0 3px rgba(255,255,255,0.15), 0 4px 12px rgba(0,0,0,0.1);
        width: 400px;
        border-color: rgba(255,255,255,0.45);
    }

    .topbar-search i {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: rgba(255,255,255,0.8);
    }

    .topbar-right {
        display: flex;
        align-items: center;
        gap: 1.2rem;
    }

    .topbar-icon-btn {
        position: relative;
        background: rgba(255,255,255,0.18);
        border: 1px solid rgba(255,255,255,0.25);
        color: white;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        backdrop-filter: blur(4px);
    }

    .topbar-icon-btn:hover {
        background: rgba(255,255,255,0.3);
        border-color: rgba(255,255,255,0.4);
        transform: scale(1.08) translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .topbar-icon-btn:active {
        transform: scale(1.02) translateY(0);
    }

    .topbar-badge {
        position: absolute;
        top: -6px;
        right: -6px;
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        font-size: 0.7rem;
        font-weight: 700;
        padding: 0.2rem 0.45rem;
        border-radius: 12px;
        min-width: 22px;
        text-align: center;
        animation: pulse 2s infinite;
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4), 0 0 0 2px white;
        border: 1px solid rgba(255,255,255,0.3);
    }

    @keyframes pulse {
        0%, 100% { 
            transform: scale(1);
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4), 0 0 0 2px white;
        }
        50% { 
            transform: scale(1.15);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.6), 0 0 0 3px white;
        }
    }

    .notifications-dropdown {
        position: absolute;
        top: calc(100% + 0.5rem);
        right: 0;
        background: white;
        border-radius: 12px;
        box-shadow: 0 16px 48px rgba(0,0,0,0.18), 0 6px 20px rgba(0,0,0,0.1);
        width: 420px;
        max-height: 500px;
        overflow: hidden;
        display: none;
        z-index: 9999;
        border: 1px solid rgba(0,0,0,0.08);
    }

    .notifications-dropdown.show {
        display: block;
        animation: slideDown 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-15px) scale(0.96);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .notifications-header {
        padding: 1.1rem 1.35rem;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: linear-gradient(135deg, #f9fafb 0%, #ffffff 100%);
        border-radius: 16px 16px 0 0;
    }

    .notifications-header h3 {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 700;
        color: #111827;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .mark-all-read {
        background: rgba(102, 126, 234, 0.1);
        border: none;
        color: #667eea;
        font-size: 0.875rem;
        cursor: pointer;
        font-weight: 600;
        padding: 0.4rem 0.75rem;
        border-radius: 8px;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .mark-all-read:hover {
        background: rgba(102, 126, 234, 0.18);
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(102, 126, 234, 0.2);
    }

    .notifications-list {
        max-height: 400px;
        overflow-y: auto;
    }

    .notification-item {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #f3f4f6;
        cursor: pointer;
        transition: background 0.2s;
        display: flex;
        gap: 1rem;
        position: relative;
    }

    .notification-item:hover {
        background: #f9fafb;
    }

    .notification-item.unread {
        background: #eff6ff;
    }

    .notification-item.unread::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: #3b82f6;
    }

    .notification-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1.1rem;
    }

    .notification-icon.success {
        background: #d1fae5;
        color: #059669;
    }

    .notification-icon.info {
        background: #dbeafe;
        color: #2563eb;
    }

    .notification-icon.warning {
        background: #fef3c7;
        color: #d97706;
    }

    .notification-icon.danger {
        background: #fee2e2;
        color: #dc2626;
    }

    .notification-content {
        flex: 1;
    }

    .notification-title {
        font-weight: 600;
        color: #111827;
        margin-bottom: 0.25rem;
        font-size: 0.9rem;
    }

    .notification-message {
        color: #6b7280;
        font-size: 0.825rem;
        margin-bottom: 0.25rem;
        line-height: 1.4;
    }

    .notification-time {
        color: #9ca3af;
        font-size: 0.75rem;
    }

    .notifications-footer {
        padding: 0.75rem 1.25rem;
        border-top: 1px solid #e5e7eb;
        text-align: center;
        background: #f9fafb;
    }

    .view-all-link {
        color: #667eea;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.875rem;
    }

    .view-all-link:hover {
        color: #764ba2;
    }

    .empty-notifications {
        padding: 3rem 2rem;
        text-align: center;
        color: #9ca3af;
    }

    .empty-notifications i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.3;
    }
    
    /* Tooltips personnalisés - affichage en DESSOUS */
    .custom-tooltip {
        position: relative;
    }
    
    .custom-tooltip::before,
    .custom-tooltip::after {
        position: absolute;
        opacity: 0;
        pointer-events: none;
        transition: all 0.3s ease;
        z-index: 3000;
    }
    
    .custom-tooltip::before {
        content: attr(data-tooltip);
        top: calc(100% + 12px); /* Affichage EN DESSOUS */
        left: 50%;
        transform: translateX(-50%) translateY(5px);
        background: #ebeef1;
        color: black;
        padding: 0.75rem 1rem;
        border-radius: 8px;
        font-size: 0.875rem;
        white-space: nowrap;
        box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        font-weight: 500;
        max-width: 250px;
        white-space: normal;
        text-align: center;
    }
    
    .custom-tooltip::after {
        content: '';
        top: calc(100% + 6px); /* Flèche en haut du tooltip */
        left: 50%;
        transform: translateX(-50%);
        border: 6px solid transparent;
        border-bottom-color: #1f2937; /* Flèche pointant vers le haut */
    }
    
    .custom-tooltip:hover::before,
    .custom-tooltip:hover::after {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }
    
    /* Responsive design pour la topbar */
    @media (max-width: 992px) {
        .crm-topbar {
            padding: 0.5rem 1.5rem;
            height: 52px;
        }
        
        .topbar-search input {
            width: 180px;
            font-size: 0.85rem;
        }
        
        .topbar-search input:focus {
            width: 220px;
        }
        
        .topbar-right {
            gap: 1rem;
        }
    }
    
    @media (max-width: 768px) {
        .crm-topbar {
            padding: 0.5rem 1rem;
            height: 50px;
        }
        
        .topbar-logo span {
            display: none;
        }
        
        .topbar-search input {
            width: 120px;
            font-size: 0.8rem;
            height: 34px;
        }
        
        .topbar-search input:focus {
            width: 160px;
        }
        
        .topbar-icon-btn {
            width: 34px;
            height: 34px;
            font-size: 0.95rem;
        }
        
        .topbar-right {
            gap: 0.6rem;
        }
        
        .topbar-left {
            gap: 0.6rem;
        }
    }

    @media (max-width: 768px) {
        .topbar-search {
            display: none; /* Cacher la recherche sur très petit écran */
        }
        .notifications-dropdown {
            width: 95vw;
            right: -100px;
        }
        .menu-toggle {
            display: flex !important;
        }
        /* Masquer tous les boutons de la topbar sauf le menu-toggle sur mobile */
        .topbar-right {
            display: none !important;
        }
    }
    
    /* Bouton menu burger */
    .menu-toggle {
        display: none;
        background: rgba(255,255,255,0.18);
        border: 1px solid rgba(255,255,255,0.25);
        color: white;
        width: 38px;
        height: 38px;
        border-radius: 8px;
        cursor: pointer;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        transition: all 0.3s ease;
    }
    
    .menu-toggle:hover {
        background: rgba(255,255,255,0.3);
    }
    
    @media (max-width: 992px) {
        .menu-toggle {
            display: flex !important;
        }
        .topbar-search input {
            width: 150px;
        }
    }
</style>

<nav class="crm-topbar">
    <div class="topbar-left">
        <button class="menu-toggle" id="sidebarToggleTop" aria-label="Toggle menu">
            <i class="fas fa-bars"></i>
        </button>
        <a href="index.php" class="topbar-logo">
            <i class="fas fa-chart-line"></i>
            <span class="d-none d-md-inline">CRM Intelligent</span>
        </a>
        <div class="topbar-search custom-tooltip" data-tooltip="🔍 Recherche globale - Trouvez rapidement leads, contacts, campagnes...">
            <i class="fas fa-search"></i>
            <input type="text" id="globalSearch" placeholder="Rechercher dans le CRM..." autocomplete="off">
        </div>
    </div>
    
    <div class="topbar-right">
        <!-- Bouton Quick Actions -->
        <button class="topbar-icon-btn custom-tooltip" 
                data-tooltip="⚡ Actions rapides - Automatisez vos tâches et workflows" 
                onclick="window.location.href='automate.php'">
            <i class="fas fa-bolt"></i>
        </button>
        
        <!-- Bouton Notifications -->
        <div style="position: relative;">
            <button class="topbar-icon-btn custom-tooltip" 
                    id="notificationsBtn" 
                    data-tooltip="🔔 Notifications - Consultez vos alertes et rappels">
                <i class="fas fa-bell"></i>
                <span class="topbar-badge" id="notificationsBadge" style="display: none;">0</span>
            </button>
            
            <div class="notifications-dropdown" id="notificationsDropdown">
                <div class="notifications-header">
                    <h3><i class="fas fa-bell"></i> Notifications</h3>
                    <button class="mark-all-read" onclick="markAllAsRead()">
                        <i class="fas fa-check-double"></i> Tout marquer comme lu
                    </button>
                </div>
                
                <div class="notifications-list" id="notificationsList">
                    <div class="empty-notifications">
                        <i class="fas fa-bell-slash"></i>
                        <p>Aucune notification</p>
                    </div>
                </div>
                
                <div class="notifications-footer">
                    <a href="notifications.php" class="view-all-link">
                        <i class="fas fa-list"></i> Voir toutes les notifications
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Bouton Intégrations -->
        <button class="topbar-icon-btn custom-tooltip" 
                data-tooltip="🔌 Intégrations - Connectez vos outils favoris (Slack, Google, etc.)" 
                onclick="window.location.href='integrations.php'">
            <i class="fas fa-plug"></i>
        </button>
        
        <!-- Bouton Analytics -->
        <button class="topbar-icon-btn custom-tooltip" 
                data-tooltip="📊 Analytics - Visualisez vos performances et KPIs en temps réel" 
                onclick="window.location.href='analytics.php'">
            <i class="fas fa-chart-line"></i>   
        </button>

        <!-- Bouton whatsapp -->
        <button class="topbar-icon-btn custom-tooltip" 
                data-tooltip="💬 WhatsApp - Gérez vos conversations et campagnes WhatsApp" 
                onclick="window.location.href='whatsapp-settings.php'">
            <i class="fab fa-whatsapp"></i> 
        </button>
        <!-- Bouton Email -->
        <button class="topbar-icon-btn custom-tooltip" 
                data-tooltip="✉️ Emails - Gérez vos campagnes et communications directement depuis le CRM" 
                onclick="window.location.href='emails.php'">
            <i class="fas fa-envelope"></i>         
        </button>

        <!-- Bouton IA -->
        <button class="topbar-icon-btn custom-tooltip" 
                data-tooltip="🤖 Agents IA - Découvrez les insights et actions proposées par l'IA" 
                onclick="window.location.href='ai-dashboard.php'">
            <i class="fas fa-robot"></i>
        </button>

        <!-- Bouton ERP -->
        <button class="topbar-icon-btn custom-tooltip" 
                data-tooltip="📦 ERP - Accédez à votre système ERP intégré" 
                onclick="window.location.href='../erp/'">
            <i class="fas fa-boxes"></i>
        </button>

        <!-- Bouton compte utilisateur -->
        <button class="topbar-icon-btn custom-tooltip" 
                data-tooltip="👤 Mon compte - Gérer votre profil et préférences" 
                onclick="window.location.href='../account.php'">
            <i class="fas fa-user"></i>
        </button>

        <!-- Bouton Settings -->
        <button class="topbar-icon-btn custom-tooltip" 
                data-tooltip="⚙️ Paramètres - Configurez votre CRM et vos préférences" 
                onclick="window.location.href='settings.php'">
            <i class="fas fa-cog"></i>
        </button>
    </div>
</nav>

<script>
    // Gestion des notifications
    let notificationsOpen = false;
    
    document.getElementById('notificationsBtn').addEventListener('click', function(e) {
        e.stopPropagation();
        notificationsOpen = !notificationsOpen;
        
        if (notificationsOpen) {
            document.getElementById('notificationsDropdown').classList.add('show');
            loadNotifications();
        } else {
            document.getElementById('notificationsDropdown').classList.remove('show');
        }
    });
    
    // Fermer le dropdown si on clique ailleurs
    document.addEventListener('click', function(e) {
        if (notificationsOpen && !e.target.closest('.notifications-dropdown')) {
            document.getElementById('notificationsDropdown').classList.remove('show');
            notificationsOpen = false;
        }
    });
    
    // Charger les notifications
    async function loadNotifications() {
        try {
            const response = await fetch('api/notifications.php?action=list&limit=10');
            const data = await response.json();
            
            if (data.success) {
                displayNotifications(data.notifications);
                updateBadge(data.unread_count);
            }
        } catch (error) {
            console.error('Erreur chargement notifications:', error);
        }
    }
    
    // Afficher les notifications
    function displayNotifications(notifications) {
        const container = document.getElementById('notificationsList');
        
        if (notifications.length === 0) {
            container.innerHTML = `
                <div class="empty-notifications">
                    <i class="fas fa-bell-slash"></i>
                    <p>Aucune notification</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = notifications.map(notif => `
            <div class="notification-item ${notif.is_read ? '' : 'unread'}" 
                 onclick="handleNotificationClick(${notif.id}, '${notif.link || ''}')">
                <div class="notification-icon ${notif.color}">
                    <i class="${notif.icon}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">${notif.title}</div>
                    <div class="notification-message">${notif.message}</div>
                    <div class="notification-time">
                        <i class="far fa-clock"></i> ${formatTime(notif.created_at)}
                    </div>
                </div>
            </div>
        `).join('');
    }
    
    // Gérer le clic sur une notification
    async function handleNotificationClick(notifId, link) {
        // Marquer comme lu
        await fetch('api/notifications.php?action=mark-read&id=' + notifId, {
            method: 'POST'
        });
        
        // Recharger le badge
        loadNotifications();
        
        // Rediriger si lien
        if (link) {
            window.location.href = link;
        }
    }
    
    // Marquer toutes comme lues
    async function markAllAsRead() {
        try {
            const response = await fetch('api/notifications.php?action=mark-all-read', {
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
    
    // Mettre à jour le badge
    function updateBadge(count) {
        const badge = document.getElementById('notificationsBadge');
        const mobileBadge = document.getElementById('sidebarMobileNotificationBadge');
        
        if (count > 0) {
            const displayCount = count > 99 ? '99+' : count;
            if (badge) {
                badge.textContent = displayCount;
                badge.style.display = 'inline';
            }
            if (mobileBadge) {
                mobileBadge.textContent = displayCount;
                mobileBadge.style.display = 'inline';
            }
        } else {
            if (badge) badge.style.display = 'none';
            if (mobileBadge) mobileBadge.style.display = 'none';
        }
    }
    
    // Animation topbar au scroll - ombre plus prononcée
    let scrollTimeout;
    
    window.addEventListener('scroll', function() {
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(() => {
            const topbar = document.querySelector('.crm-topbar');
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            if (scrollTop > 20) {
                topbar.style.boxShadow = '0 4px 20px rgba(102, 126, 234, 0.18), 0 2px 8px rgba(0,0,0,0.1)';
            } else {
                topbar.style.boxShadow = '0 2px 12px rgba(102, 126, 234, 0.12), 0 1px 4px rgba(0,0,0,0.06)';
            }
        }, 10);
    }, { passive: true });
    
    // Formater le temps
    function formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = Math.floor((now - date) / 1000); // en secondes
        
        if (diff < 60) return 'À l\'instant';
        if (diff < 3600) return Math.floor(diff / 60) + ' min';
        if (diff < 86400) return Math.floor(diff / 3600) + ' h';
        if (diff < 604800) return Math.floor(diff / 86400) + ' j';
        
        return date.toLocaleDateString('fr-FR', { 
            day: '2-digit', 
            month: 'short' 
        });
    }
    
    // Recherche globale
    let searchTimeout;
    document.getElementById('globalSearch').addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        const query = e.target.value.trim();
        
        if (query.length < 2) return;
        
        searchTimeout = setTimeout(() => {
            performGlobalSearch(query);
        }, 500);
    });
    
    function performGlobalSearch(query) {
        // Rediriger vers une page de recherche globale
        window.location.href = `search.php?q=${encodeURIComponent(query)}`;
    }
    
    // Charger les notifications au démarrage (toutes les 30 secondes)
    loadNotifications();
    setInterval(loadNotifications, 30000);
</script>
