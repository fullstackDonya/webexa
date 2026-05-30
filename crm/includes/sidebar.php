
<!-- CRM Sidebar include (no <html>/<head>/<body>) -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php">
        <div class="sidebar-brand-icon rotate-n-15">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="sidebar-brand-text mx-3">CRM Intelligent</div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Search Box -->
    <div class="sidebar-search-wrapper px-3 py-3">
        <div class="input-group">
            <input type="text" class="form-control form-control-sm bg-light border-0" id="sidebarSearchInput" placeholder="Rechercher..." aria-label="Rechercher" style="border-radius: 20px; padding: 0.5rem 1rem; font-size: 0.85rem;">
        </div>
    </div>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Section Actions Rapides (Mobile uniquement) -->
    <div class="mobile-quick-actions d-md-none">
        <div class="sidebar-heading">Actions Rapides</div>
        
        <li class="nav-item">
            <a class="nav-link" href="search.php">
                <i class="fas fa-fw fa-search"></i>
                <span>Recherche Globale</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link" href="automate.php">
                <i class="fas fa-fw fa-bolt"></i>
                <span>Quick Actions</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link" href="notifications.php">
                <i class="fas fa-fw fa-bell"></i>
                <span>Notifications</span>
                <span class="badge bg-danger ms-2" id="sidebarMobileNotificationBadge" style="display: none;">0</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link" href="integrations.php">
                <i class="fas fa-fw fa-plug"></i>
                <span>Intégrations</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link" href="analytics.php">
                <i class="fas fa-fw fa-chart-line"></i>
                <span>Analytics</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link" href="whatsapp-settings.php">
                <i class="fab fa-fw fa-whatsapp"></i>
                <span>WhatsApp</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link" href="emails.php">
                <i class="fas fa-fw fa-envelope"></i>
                <span>Emails</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link" href="ai-dashboard.php">
                <i class="fas fa-fw fa-robot"></i>
                <span>Agents IA</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link" href="../erp/">
                <i class="fas fa-fw fa-boxes"></i>
                <span>ERP</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link" href="../account.php">
                <i class="fas fa-fw fa-user"></i>
                <span>Mon Compte</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link" href="settings.php">
                <i class="fas fa-fw fa-cog"></i>
                <span>Paramètres</span>
            </a>
        </li>
        
        <hr class="sidebar-divider">
    </div>

    <!-- Nav Item - Dashboard -->
    <li class="nav-item active">
        <a class="nav-link" href="index.php">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <!-- Nav Item - Pipeline -->
     <li class="nav-item">
         <a class="nav-link" href="pipeline-board.php">
             <i class="fas fa-fw fa-project-diagram"></i>
             <span>Pipeline (Kanban)</span>
         </a>
     </li>

     <!-- Nav Item - Tasks -->
    <li class="nav-item">
        <a class="nav-link" href="tasks.php">
            <i class="fas fa-fw fa-tasks"></i>
            <span>Tâches</span>
        </a>
    </li>

    <!-- Nav Item - Calls -->
     <li class="nav-item">
         <a class="nav-link" href="calls.php">
             <i class="fas fa-fw fa-phone"></i>
             <span>Appels</span>
         </a>   
    </li>   

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">CRM</div>


  
    <!-- Nav Item - Leads -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLeads">
            <i class="fas fa-fw fa-bullseye"></i>
            <span>Leads</span>
        </a>
        <div id="collapseLeads" class="collapse">
            <div class="bg-white py-2 collapse-inner rounded">
              
                <a class="collapse-item" href="leads.php">Tous les leads</a>
                <a class="collapse-item" href="leads-add.php">Ajouter lead</a>
                <a class="collapse-item" href="leads-import.php">Importer leads</a>
                <a class="collapse-item" href="leads-scoring.php">Scoring IA</a>
            </div>
        </div>
    </li>

    <!-- Nav Item - Folders-->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseFolders">
            <i class="fas fa-fw fa-folder-open"></i>
            <span>Dossiers</span>
        </a>
        <div id="collapseFolders" class="collapse">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="folders.php">Tous les dossiers</a>
                <a class="collapse-item" href="folder_add.php">Ajouter dossier</a>
                <a class="collapse-item" href="folders-import.php">Importer dossiers</a>
            </div>
        </div>
    </li>

    <!-- Nav Item - Missions-->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseMissions">
            <i class="fas fa-fw fa-tasks"></i>
            <span>Missions</span>
        </a>
        <div id="collapseMissions" class="collapse">
            <div class="bg-white py-2 collapse-inner rounded">

                <a class="collapse-item" href="missions.php">Toutes les missions</a>
                <a class="collapse-item" href="mission_add.php">Ajouter mission</a>
            </div>
        </div>
    </li>

    <!-- Nav Item - Opportunities -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseOpportunities">
            <i class="fas fa-fw fa-handshake"></i>
            <span>Opportunités</span>
        </a>
        <div id="collapseOpportunities" class="collapse">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="opportunities.php">Pipeline</a>
                <a class="collapse-item" href="opportunities-forecast.php">Prévisions IA</a>
                <a class="collapse-item" href="opportunities-analytics.php">Analytics</a>
                <a class="collapse-item" href="opportunities-import.php">Importer opportunités</a>
            </div>
        </div>
    </li>

    <!-- Nav Item - Customers -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseCustomers">
            <i class="fas fa-fw fa-user-tie"></i>
            <span>Clients</span>
        </a>
        <div id="collapseCustomers" class="collapse">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="customers.php">Tous les clients</a>
                <a class="collapse-item" href="customers-add.php">Ajouter client</a>
                <a class="collapse-item" href="customers-import.php">Importer clients</a>
            </div>
        </div>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">Analytics & BI</div>

    <!-- Nav Item - Power BI -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapsePowerBI">
            <i class="fas fa-fw fa-chart-bar"></i>
            <span>Power BI</span>
        </a>
        <div id="collapsePowerBI" class="collapse">
            <div class="bg-white py-2 collapse-inner rounded">
     
                <a class="collapse-item" href="powerbi-sales.php">Ventes</a>
                <a class="collapse-item" href="powerbi-customers.php">Clients</a>
                <a class="collapse-item" href="powerbi-performance.php">Performance</a>
                <a class="collapse-item" href="powerbi-predictions.php">Prédictions IA</a>
            </div>
        </div>
    </li>

    <!-- Nav Item - Analytics -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseAnalytics">
            <i class="fas fa-fw fa-analytics"></i>
            <span>Analytics</span>
        </a>
        <div id="collapseAnalytics" class="collapse">
            <div class="bg-white py-2 collapse-inner rounded">

                <a class="collapse-item" href="analytics-sales.php">Analyse des ventes</a>
                <a class="collapse-item" href="analytics-customer.php">Comportement client</a>
                <a class="collapse-item" href="analytics-funnel.php">Entonnoir conversion</a>
            </div>
        </div>
    </li>

    <!-- Nav Item - AI Insights -->
    <li class="nav-item">
        <a class="nav-link" href="ai-insights.php">
            <i class="fas fa-fw fa-brain"></i>
            <span>Insights IA</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">
    <div class="sidebar-heading">IA Webitech</div>
        <!-- Nav Item - AI Dashboard -->
    <li class="nav-item">
        <a class="nav-link" href="ai-dashboard.php">
            <i class="fas fa-fw fa-robot"></i>
            <span>Agent IA</span>
        </a>
    </li>
    
    <!-- Nav Item - Notifications -->
    <!-- <li class="nav-item">
        <a class="nav-link" href="notifications.php">
            <i class="fas fa-fw fa-bell"></i>
            <span>Notifications</span>
            <span class="badge bg-danger ms-2" id="sidebarNotificationBadge" style="display: none;">0</span>
        </a>
    </li> -->
    
  

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">Marketing</div>

    <!-- Nav Item - Campaigns -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseCampaigns">
            <i class="fas fa-fw fa-bullhorn"></i>
            <span>Campagnes</span>
        </a>
        <div id="collapseCampaigns" class="collapse">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="campaigns.php">Toutes les campagnes</a>
                <a class="collapse-item" href="campaigns-email.php">Email Marketing</a>
                <a class="collapse-item" href="campaigns-automation.php">Automatisation IA</a>
                <a class="collapse-item" href="campaigns-import.php">Importer campagnes</a>
                <div class="collapse-divider"></div>
               
            </div>
        </div>
    </li>

    <!-- Nav Item - Email -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseEmail">
            <i class="fas fa-fw fa-envelope"></i>
            <span>Email</span>
        </a>
        <div id="collapseEmail" class="collapse">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="email-inbox.php">
                    <i class="fas fa-inbox"></i> Boîte de réception
                </a>
                <a class="collapse-item" href="email-compose.php">
                    <i class="fas fa-pen"></i> Nouveau message
                </a>
                <div class="collapse-divider"></div>
                <a class="collapse-item" href="email-settings.php">
                    <i class="fas fa-cog"></i> Paramètres & Comptes
                </a>
            </div>
        </div>
    </li>

    <!-- Nav Item - WhatsApp -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseWhatsApp">
            <i class="fab fa-fw fa-whatsapp" style="color: #25D366;"></i>
            <span>WhatsApp Business</span>
        </a>
        <div id="collapseWhatsApp" class="collapse">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="whatsapp-settings.php">
                    <i class="fas fa-cog"></i> Configuration
                </a>
                <a class="collapse-item" href="whatsapp-conversations.php">
                    <i class="fas fa-comments"></i> Conversations
                </a>
                <a class="collapse-item" href="whatsapp-templates.php">
                    <i class="fas fa-file-alt"></i> Templates
                </a>
                <a class="collapse-item" href="whatsapp-campaigns.php">
                    <i class="fas fa-bullhorn"></i> Campagnes WhatsApp
                </a>
            </div>
        </div>
    </li>

       <!-- Divider -->
    <hr class="sidebar-divider">
    <div class="sidebar-heading">Utilitaires</div>

      <!-- Nav Item - Integrations -->
    <li class="nav-item">
        <a class="nav-link" href="integrations.php">
            <i class="fas fa-fw fa-plug"></i>
            <span>Intégrations</span>
        </a>
    </li>

      <!-- Nav Item - Settings -->
    <li class="nav-item">
        <a class="nav-link" href="settings.php">
            <i class="fas fa-fw fa-cog"></i>
            <span>Paramètres</span>
        </a>
    </li>

    

    

    <!-- Heading -->
    <div class="sidebar-heading">Documentation & Aide</div>

    <!-- Nav Item - Documentation -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseDocs">
            <i class="fas fa-fw fa-book"></i>
            <span>Documentation</span>
        </a>
        <div id="collapseDocs" class="collapse">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="documentation-commerciaux.php">
                    <i class="fas fa-graduation-cap"></i> Guide Commerciaux
                </a>
                <a class="collapse-item" href="documentation-clients.php">
                    <i class="fas fa-users"></i> Guide Clients
                </a>
            </div>
        </div>
    </li>

    <!-- Nav Item - Chat Assistant -->
    <li class="nav-item">
        <a class="nav-link" href="chat-assistant.php">
            <i class="fas fa-fw fa-robot text-success"></i>
            <span>Chat Assistant IA</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider d-none d-md-block">

    <!-- Sidebar Toggler (Sidebar) -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>
  
 </ul>
 <div class="sidebar-backdrop"></div>


<script>
    document.addEventListener('DOMContentLoaded', function () {
        const body = document.body;
        const sidebar = document.querySelector('.sidebar');
        const desktopToggle = document.getElementById('sidebarToggle');
        const topToggle = document.getElementById('sidebarToggleTop');
        const desktopQuery = window.matchMedia('(min-width: 992px)');
        const mobileQuery = window.matchMedia('(max-width: 991.98px)');
        const PIN_KEY = 'crmSidebarPinned';

        let collapseTimeout = null;
        let isPinned = false;

        function ensureBackdrop() {
            let el = document.querySelector('.sidebar-backdrop');
            if (!el) {
                el = document.createElement('div');
                el.className = 'sidebar-backdrop';
                document.body.appendChild(el);
            }
            return el;
        }

        const backdrop = ensureBackdrop();

        function storePinned(pinned) {
            try {
                localStorage.setItem(PIN_KEY, pinned ? 'true' : 'false');
            } catch (err) {
                /* noop for private mode */
            }
        }

        function getStoredPinned() {
            try {
                return localStorage.getItem(PIN_KEY) === 'true';
            } catch (err) {
                return false;
            }
        }

        function clearCollapseSchedule() {
            if (collapseTimeout) {
                clearTimeout(collapseTimeout);
                collapseTimeout = null;
            }
        }

        function updateToggleState() {
            if (desktopToggle) {
                desktopToggle.setAttribute('aria-pressed', isPinned ? 'true' : 'false');
                desktopToggle.classList.toggle('is-active', isPinned);
            }
        }

        function setCollapsed(collapsed) {
            if (!sidebar) return;
            body.classList.toggle('sidebar-collapsed', collapsed);
            sidebar.classList.toggle('is-collapsed', collapsed);
            if (collapsed) {
                sidebar.classList.remove('show');
                if (backdrop) {
                    backdrop.classList.remove('active');
                }
            }
        }

        function scheduleCollapse(delay = 140) {
            if (isPinned || mobileQuery.matches) {
                return;
            }
            clearCollapseSchedule();
            collapseTimeout = setTimeout(function () {
                setCollapsed(true);
            }, delay);
        }

        function setPinned(pinned, persist = true) {
            if (!sidebar) return;
            isPinned = pinned;
            updateToggleState();
            if (persist) {
                storePinned(pinned);
            }
            if (mobileQuery.matches) {
                body.classList.remove('sidebar-pinned');
                return;
            }
            body.classList.toggle('sidebar-pinned', pinned);
            if (pinned) {
                clearCollapseSchedule();
                setCollapsed(false);
            } else {
                setCollapsed(true);
            }
        }

        function openMobileSidebar() {
            if (!sidebar) return;
            clearCollapseSchedule();
            setCollapsed(false);
            sidebar.classList.add('show');
            if (backdrop) {
                backdrop.classList.add('active');
            }
            // Changer l'icône du bouton topbar en croix
            const topbarToggle = document.getElementById('sidebarToggleTop');
            if (topbarToggle) {
                const icon = topbarToggle.querySelector('i');
                if (icon) icon.className = 'fas fa-times';
            }
        }

        function closeMobileSidebar() {
            if (!sidebar) return;
            clearCollapseSchedule();
            sidebar.classList.remove('show');
            if (backdrop) {
                backdrop.classList.remove('active');
            }
            if (mobileQuery.matches) {
                setCollapsed(true);
            }
            // Réinitialiser l'icône du bouton topbar
            const topbarToggle = document.getElementById('sidebarToggleTop');
            if (topbarToggle) {
                const icon = topbarToggle.querySelector('i');
                if (icon) icon.className = 'fas fa-bars';
            }
        }

        function handleDesktopChange(event) {
            clearCollapseSchedule();
            if (sidebar) {
                sidebar.classList.remove('show');
            }
            if (backdrop) {
                backdrop.classList.remove('active');
            }
            if (event.matches) {
                if (isPinned) {
                    setPinned(true, false);
                } else {
                    body.classList.remove('sidebar-pinned');
                    setCollapsed(true);
                }
            } else {
                body.classList.remove('sidebar-pinned');
                if (!mobileQuery.matches) {
                    setCollapsed(true);
                } else {
                    setCollapsed(true);
                }
            }
        }

        if (sidebar) {
            sidebar.addEventListener('mouseenter', function () {
                if (mobileQuery.matches || isPinned) {
                    return;
                }
                clearCollapseSchedule();
                setCollapsed(false);
            });

            sidebar.addEventListener('mouseleave', function () {
                scheduleCollapse(120);
            });

            sidebar.addEventListener('focusin', function () {
                if (mobileQuery.matches || isPinned) {
                    return;
                }
                clearCollapseSchedule();
                setCollapsed(false);
            });

            sidebar.addEventListener('focusout', function () {
                if (mobileQuery.matches || isPinned) {
                    return;
                }
                scheduleCollapse(160);
            });
        }

        isPinned = getStoredPinned();
        updateToggleState();
        setCollapsed(true);
        if (isPinned && !mobileQuery.matches) {
            setPinned(true, false);
        }

        if (topToggle) {
            topToggle.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                // Toggle la sidebar (ouvrir/fermer)
                if (sidebar && sidebar.classList.contains('show')) {
                    closeMobileSidebar();
                } else {
                    openMobileSidebar();
                }
            });
        }

        if (desktopToggle) {
            desktopToggle.addEventListener('click', function (event) {
                event.preventDefault();
                if (mobileQuery.matches) {
                    openMobileSidebar();
                    return;
                }
                setPinned(!isPinned);
            });
        }

        if (backdrop) {
            backdrop.addEventListener('click', function () {
                closeMobileSidebar();
            });
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeMobileSidebar();
            }
        });

        document.querySelectorAll('.sidebar .nav-link').forEach(function (link) {
            link.addEventListener('click', function () {
                if (mobileQuery.matches) {
                    closeMobileSidebar();
                }
            });
        });

        if (typeof desktopQuery.addEventListener === 'function') {
            desktopQuery.addEventListener('change', function (event) {
                handleDesktopChange(event);
            });
        } else if (typeof desktopQuery.addListener === 'function') {
            desktopQuery.addListener(function (event) {
                handleDesktopChange(event);
            });
        }

        if (sidebar) {
            sidebar.addEventListener('wheel', function () {
                if (mobileQuery.matches || isPinned) {
                    return;
                }
                clearCollapseSchedule();
                setCollapsed(false);
            });
        }

        handleDesktopChange(desktopQuery);
    });

    // Sidebar Search Functionality
    const searchInput = document.getElementById('sidebarSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const navItems = document.querySelectorAll('.sidebar .nav-item');
            const headings = document.querySelectorAll('.sidebar .sidebar-heading');
            const dividers = document.querySelectorAll('.sidebar .sidebar-divider');
            
            // Si la recherche est vide, tout afficher
            if (searchTerm === '') {
                navItems.forEach(item => item.style.display = '');
                headings.forEach(heading => heading.style.display = '');
                dividers.forEach(divider => divider.style.display = '');
                // Fermer tous les collapses
                document.querySelectorAll('.sidebar .collapse').forEach(collapse => {
                    collapse.classList.remove('show');
                });
                return;
            }
            
            // Masquer tous les headings et dividers pendant la recherche
            headings.forEach(heading => heading.style.display = 'none');
            dividers.forEach(divider => divider.style.display = 'none');
            
            navItems.forEach(item => {
                const itemText = item.textContent.toLowerCase();
                const hasMatch = itemText.includes(searchTerm);
                
                if (hasMatch) {
                    item.style.display = '';
                    // Ouvrir les collapses qui contiennent des résultats
                    const collapse = item.querySelector('.collapse');
                    if (collapse) {
                        collapse.classList.add('show');
                    }
                } else {
                    item.style.display = 'none';
                }
            });
        });
        
        // Focus sur la recherche avec Ctrl+K ou Cmd+K
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                searchInput.focus();
            }
        });
        
        // Mettre à jour le badge de notifications dans le sidebar
        async function updateSidebarNotificationBadge() {
            try {
                const response = await fetch('api/notifications.php?action=list&unread_only=true&limit=1');
                const data = await response.json();
                
                if (data.success) {
                    const badge = document.getElementById('sidebarNotificationBadge');
                    const mobileBadge = document.getElementById('sidebarMobileNotificationBadge');
                    
                    if (data.unread_count > 0) {
                        const displayCount = data.unread_count > 99 ? '99+' : data.unread_count;
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
            } catch (error) {
                console.error('Erreur mise à jour badge notifications:', error);
            }
        }
        
        // Mettre à jour au chargement et toutes les 30 secondes
        updateSidebarNotificationBadge();
        setInterval(updateSidebarNotificationBadge, 30000);
    }
</script>

<!-- Chat notifications polling -->
<script src="assets/js/chat-notifications.js"></script>

<!-- end CRM Sidebar include -->