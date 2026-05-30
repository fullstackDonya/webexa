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
        <!-- Bouton Scanner -->
        <button class="erp-topbar-icon-btn erp-tooltip" 
                data-tooltip="📄 Scanner documents" 
                onclick="window.location.href='document-scanner.php'">
            <i class="fas fa-file-invoice"></i>
        </button>
        
        <!-- Bouton Facturation -->
        <button class="erp-topbar-icon-btn erp-tooltip" 
                data-tooltip="📋 Factures et devis" 
                onclick="window.location.href='invoices.php'">
            <i class="fas fa-file-invoice-dollar"></i>
        </button>
        
        <!-- Bouton Rapports -->
        <button class="erp-topbar-icon-btn erp-tooltip" 
                data-tooltip="📊 Rapports et analyses" 
                onclick="window.location.href='reports.php'">
            <i class="fas fa-chart-pie"></i>
        </button>
        
        <!-- Bouton Comptabilité -->
        <button class="erp-topbar-icon-btn erp-tooltip" 
                data-tooltip="🏦 Comptabilité et banque" 
                onclick="window.location.href='accounting.php'">
            <i class="fas fa-university"></i>
        </button>

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
