<!-- ERP Topbar -->
<style>
    .erp-topbar {
        background: linear-gradient(220deg,
            rgba(16, 185, 129, 0.95) 0%,
            rgba(5, 150, 105, 0.92) 100%
        );
        color: white;
        padding: 0.6rem 2rem;
        height: 56px;
        box-shadow: 0 2px 12px rgba(16, 185, 129, 0.12), 0 1px 4px rgba(0,0,0,0.06);
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

    .erp-topbar-left {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .erp-topbar-logo {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 700;
        font-size: 1.1rem;
        text-decoration: none;
        color: white;
        transition: opacity 0.3s ease;
    }

    .erp-topbar-logo:hover {
        opacity: 0.85;
    }

    .erp-topbar-logo i {
        font-size: 1.3rem;
    }

    .erp-topbar-right {
        display: flex;
        align-items: center;
        gap: 1.2rem;
    }

    .erp-topbar-icon-btn {
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

    .erp-topbar-icon-btn:hover {
        background: rgba(255,255,255,0.3);
        border-color: rgba(255,255,255,0.4);
        transform: scale(1.08) translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .erp-topbar-icon-btn:active {
        transform: scale(1.02) translateY(0);
    }

    /* Tooltips */
    .erp-tooltip {
        position: relative;
    }
    
    .erp-tooltip::before {
        content: attr(data-tooltip);
        position: absolute;
        top: calc(100% + 12px);
        left: 50%;
        transform: translateX(-50%) translateY(5px);
        background: #1f2937;
        color: white;
        padding: 0.5rem 0.75rem;
        border-radius: 8px;
        font-size: 0.8rem;
        white-space: nowrap;
        box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        opacity: 0;
        pointer-events: none;
        transition: all 0.3s ease;
        z-index: 3000;
    }
    
    .erp-tooltip:hover::before {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }
    
    /* Responsive */
    @media (max-width: 992px) {
        .erp-topbar {
            padding: 0.5rem 1rem;
            height: 52px;
        }
        
        .erp-topbar-right {
            gap: 1rem;
        }
    }
    
    @media (max-width: 768px) {
        .erp-topbar {
            padding: 0.5rem 1rem;
            height: 50px;
        }
        
        .erp-topbar-icon-btn {
            width: 34px;
            height: 34px;
            font-size: 0.95rem;
        }
        
        .erp-topbar-right {
            display: none !important;
        }
        
        .erp-menu-toggle {
            display: flex !important;
        }
        
        .erp-topbar-logo span {
            display: none;
        }
    }
    
    /* Classes utilitaires responsive */
    .d-none { display: none !important; }
    .d-md-inline { display: none; }
    
    @media (min-width: 768px) {
        .d-md-none { display: none !important; }
        .d-md-inline { display: inline !important; }
    }
    
    /* Bouton menu burger */
    .erp-menu-toggle {
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
    
    .erp-menu-toggle:hover {
        background: rgba(255,255,255,0.3);
    }
    
    @media (max-width: 992px) {
        .erp-menu-toggle {
            display: flex !important;
        }
    }

    .erp-today-reminders-popup {
        position: fixed;
        right: 1.5rem;
        bottom: 1.5rem;
        width: 360px;
        background: #fff;
        border: 1px solid rgba(0,0,0,.08);
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0,0,0,.18);
        display: none;
        overflow: hidden;
        z-index: 2000;
    }
    .erp-today-reminders-popup.show { display: block; animation: erpReminderIn .35s ease-out; }
    @keyframes erpReminderIn { from { opacity: 0; transform: translateY(24px); } to { opacity: 1; transform: translateY(0); } }
    .erp-reminder-header {
        padding: .9rem 1.1rem;
        color: #fff;
        background: linear-gradient(135deg, #059669, #0f766e);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .erp-reminder-header h4 { margin: 0; font-size: .95rem; }
    .erp-reminder-close {
        border: 0;
        border-radius: 50%;
        width: 26px;
        height: 26px;
        color: #fff;
        background: rgba(255,255,255,.2);
        cursor: pointer;
    }
    .erp-reminder-body { max-height: 380px; overflow-y: auto; padding: .6rem; }
    .erp-reminder-section { padding: .5rem .4rem .2rem; color: #6b7280; font-size: .7rem; font-weight: 700; text-transform: uppercase; }
    .erp-reminder-item { display: flex; align-items: center; margin-bottom: .2rem; }
    .erp-reminder-link { flex: 1; min-width: 0; padding: .55rem .4rem; color: #111827; text-decoration: none; border-radius: 8px; }
    .erp-reminder-link:hover { background: #f3f4f6; }
    .erp-reminder-title { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .84rem; font-weight: 600; }
    .erp-reminder-sub { color: #6b7280; font-size: .72rem; margin-top: .15rem; }
    .erp-reminder-delete { border: 0; background: transparent; color: #d1d5db; cursor: pointer; padding: .6rem; }
    .erp-reminder-delete:hover { color: #dc2626; }
    .erp-reminder-footer { display: flex; gap: .5rem; padding: .6rem 1rem; border-top: 1px solid #f3f4f6; background: #f9fafb; }
    .erp-reminder-footer a { flex: 1; text-align: center; color: #059669; font-size: .8rem; font-weight: 600; text-decoration: none; }
    @media (max-width: 480px) { .erp-today-reminders-popup { right: 1rem; width: calc(100vw - 2rem); } }
</style>

<nav class="erp-topbar">
    <div class="erp-topbar-left">
        <button class="erp-menu-toggle" id="erpSidebarToggle" aria-label="Toggle menu">
            <i class="fas fa-bars"></i>
        </button>
        <a href="index.php" class="erp-topbar-logo">
            <i class="fas fa-chart-network"></i>
            <span class="d-none d-md-inline">Webitech ERP</span>
        </a>
    </div>
    
    <div class="erp-topbar-right">
        <?php if (isset($erpCan) && $erpCan('billing')): ?>
        <!-- Bouton Scanner -->
        <button class="erp-topbar-icon-btn erp-tooltip" 
                data-tooltip="📄 Scanner documents" 
                onclick="window.location.href='document-scanner.php'">
            <i class="fas fa-file-invoice"></i>
        </button>
        <?php endif; ?>
        
        <?php if (isset($erpCan) && $erpCan('invoices')): ?>
        <!-- Bouton Facturation -->
        <button class="erp-topbar-icon-btn erp-tooltip" 
                data-tooltip="📋 Factures et devis" 
                onclick="window.location.href='invoices.php'">
            <i class="fas fa-file-invoice-dollar"></i>
        </button>
        <?php endif; ?>
        
        <?php if (isset($erpCan) && $erpCan('analytics')): ?>
        <!-- Bouton Rapports -->
        <button class="erp-topbar-icon-btn erp-tooltip" 
                data-tooltip="📊 Rapports et analyses" 
                onclick="window.location.href='reports.php'">
            <i class="fas fa-chart-pie"></i>
        </button>
        <?php endif; ?>
        
        <?php if (isset($erpCan) && $erpCan('billing')): ?>
        <!-- Bouton Comptabilité -->
        <button class="erp-topbar-icon-btn erp-tooltip" 
                data-tooltip="🏦 Comptabilité et banque" 
                onclick="window.location.href='accounting.php'">
            <i class="fas fa-university"></i>
        </button>
        <?php endif; ?>

        <!-- Bouton CRM -->
        <button class="erp-topbar-icon-btn erp-tooltip" 
                data-tooltip="📈 Accès CRM" 
                onclick="window.location.href='../crm/index.php'">
            <i class="fas fa-chart-line"></i>
        </button>

        <!-- Bouton Documentation -->
        <button class="erp-topbar-icon-btn erp-tooltip" 
                data-tooltip="📚 Documentation" 
                onclick="window.location.href='documentation-utilisateurs.php'">
            <i class="fas fa-book-reader"></i>
        </button>

        <!-- Bouton Compte utilisateur -->
        <button class="erp-topbar-icon-btn erp-tooltip" 
                data-tooltip="👤 Mon compte" 
                onclick="window.location.href='../account.php'">
            <i class="fas fa-user"></i>
        </button>
    </div>
</nav>

<div class="erp-today-reminders-popup" id="erpTodayRemindersPopup">
    <div class="erp-reminder-header">
        <h4><i class="fas fa-calendar-day"></i> Rappels d'aujourd'hui</h4>
        <button class="erp-reminder-close" onclick="dismissErpTodayReminders()" title="Fermer jusqu'à demain"><i class="fas fa-times"></i></button>
    </div>
    <div class="erp-reminder-body" id="erpTodayRemindersBody"></div>
    <div class="erp-reminder-footer">
        <a href="../crm/tasks.php"><i class="fas fa-tasks"></i> Tâches</a>
        <a href="../crm/calls.php"><i class="fas fa-phone"></i> Appels</a>
    </div>
</div>

<!-- Ajouter un padding-top au body pour compenser la topbar fixe -->
<style>
    body {
        padding-top: 56px;
    }
    
    @media (max-width: 992px) {
        body {
            padding-top: 52px;
        }
    }
    
    @media (max-width: 768px) {
        body {
            padding-top: 50px;
        }
    }
</style>

<script>
(() => {
    const apiUrl = '../crm/api/today-reminders.php';
    const escapeReminder = value => String(value || '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));

    async function loadErpTodayReminders() {
        const today = new Date().toISOString().slice(0, 10);
        const dismissedKey = 'erp_reminders_dismissed_date';
        if (localStorage.getItem(dismissedKey) === today) return;
        try {
            const response = await fetch(apiUrl);
            const data = await response.json();
            const missions = data.missions || [];
            const shifts = data.shifts || [];
            if (!missions.length && !shifts.length) return;
            const body = document.getElementById('erpTodayRemindersBody');
            let html = '';
            if (missions.length) {
                html += `<div class="erp-reminder-section"><i class="fas fa-route"></i> Missions (${missions.length})</div>` + missions.map(mission => {
                const time = mission.datetime ? new Date(mission.datetime).toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'}) : '';
                const route = [mission.departure, mission.arrival].filter(Boolean).join(' → ');
                return `<div class="erp-reminder-item" data-id="${mission.id}">
                    <a class="erp-reminder-link" href="missions.php">
                        <div class="erp-reminder-title">Dossier #${escapeReminder(mission.folder_id)} · ${escapeReminder(mission.company_name)}</div>
                        <div class="erp-reminder-sub">⏰ ${time}${route ? ' · ' + escapeReminder(route) : ''}</div>
                    </a>
                    <button class="erp-reminder-delete" onclick="completeErpMission(${mission.id}, this)" title="Marquer cette mission comme terminée"><i class="fas fa-check"></i></button>
                </div>`;
                }).join('');
            }
            if (shifts.length) {
                html += `<div class="erp-reminder-section"><i class="fas fa-calendar-alt"></i> Shifts (${shifts.length})</div>` + shifts.map(shift => {
                    const start = shift.start_datetime ? new Date(shift.start_datetime).toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'}) : '';
                    const end = shift.end_datetime ? new Date(shift.end_datetime).toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'}) : '';
                    const person = shift.employee_name || 'Société seule';
                    const company = shift.company_name || '';
                    return `<div class="erp-reminder-item" data-id="${shift.id}">
                        <a class="erp-reminder-link" href="shifts.php">
                            <div class="erp-reminder-title">${escapeReminder(person)}${company ? ' · ' + escapeReminder(company) : ''}</div>
                            <div class="erp-reminder-sub">⏰ ${start}${end ? ' - ' + end : ''}${shift.role ? ' · ' + escapeReminder(shift.role) : ''}</div>
                        </a>
                    </div>`;
                }).join('');
            }
            body.innerHTML = html;
            document.getElementById('erpTodayRemindersPopup').classList.add('show');
        } catch (error) {}
    }

    async function completeErpMission(id, button) {
        if (!confirm('Marquer cette mission comme terminée ?')) return;
        try {
            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'complete_mission', id: id})
            });
            const data = await response.json();
            if (!data.success) throw new Error(data.message || 'Suppression impossible');
            button.closest('.erp-reminder-item').remove();
            if (!document.querySelector('#erpTodayRemindersBody .erp-reminder-item')) {
                document.getElementById('erpTodayRemindersPopup').classList.remove('show');
            }
        } catch (error) {
            alert(error.message);
        }
    }

    function dismissErpTodayReminders() {
        localStorage.setItem('erp_reminders_dismissed_date', new Date().toISOString().slice(0, 10));
        document.getElementById('erpTodayRemindersPopup').classList.remove('show');
    }

    window.dismissErpTodayReminders = dismissErpTodayReminders;
    setTimeout(loadErpTodayReminders, 1800);
})();
</script>
