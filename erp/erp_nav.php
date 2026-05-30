

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  
  <!-- ERP Topbar -->
  <?php include __DIR__ . '/includes/topbar.php'; ?>
  
  <div class="layout">
    <aside class="sidebar" id="sidebar" aria-hidden="false">
      <div class="brand">
        <div class="logo"><i class="fas fa-chart-network"></i></div>
        <div>
          <div class="title">Webitech ERP</div>
          <div class="small" style="color:rgba(255,255,255,.7)">Gestion d'entreprise moderne</div>
        </div>
      </div>

      <div class="search" style="margin-top:8px">
        <input id="sidebarSearch" placeholder="Rechercher..." type="search" aria-label="Recherche">
      </div>

      <!-- Actions Rapides (Mobile uniquement) -->
      <nav class="nav-section mobile-quick-actions" aria-label="Actions rapides">
        <div style="padding: 0.5rem 1rem; font-weight: 600; color: rgba(255,255,255,0.7); font-size: 0.85rem;">Actions Rapides</div>
        <a class="nav-link" href="document-scanner.php"><span class="icon"><i class="fas fa-file-invoice"></i></span><span class="label">Scanner documents</span></a>
        <a class="nav-link" href="invoices.php"><span class="icon"><i class="fas fa-file-invoice-dollar"></i></span><span class="label">Factures et devis</span></a>
        <a class="nav-link" href="reports.php"><span class="icon"><i class="fas fa-chart-pie"></i></span><span class="label">Rapports et analyses</span></a>
        <a class="nav-link" href="accounting.php"><span class="icon"><i class="fas fa-university"></i></span><span class="label">Comptabilité</span></a>
        <a class="nav-link" href="../crm/index.php"><span class="icon"><i class="fas fa-chart-line"></i></span><span class="label">Accès CRM</span></a>
        <a class="nav-link" href="documentation-utilisateurs.php"><span class="icon"><i class="fas fa-book-reader"></i></span><span class="label">Documentation</span></a>
        <a class="nav-link" href="../account.php"><span class="icon"><i class="fas fa-user"></i></span><span class="label">Mon Compte</span></a>
        <div class="nav-divider" role="separator"></div>
      </nav>

      <nav class="nav-section" aria-label="Navigation principale">
        <a class="nav-link" href="index.php"><span class="icon"><i class="fas fa-chart-line"></i></span><span class="label">Dashboard</span></a>
        <a class="nav-link" href="employees.php"><span class="icon"><i class="fas fa-users"></i></span><span class="label">Personnel</span></a>
        <a class="nav-link" href="companies.php"><span class="icon"><i class="fas fa-building"></i></span><span class="label">Entreprises</span></a>
        <a class="nav-link" href="missions.php"><span class="icon"><i class="fas fa-tasks"></i></span><span class="label">Missions & Projets</span></a>
        <a class="nav-link" href="shifts.php"><span class="icon"><i class="fas fa-calendar-alt"></i></span><span class="label">Planning</span></a>
        <a class="nav-link" href="sales.php"><span class="icon"><i class="fas fa-chart-bar"></i></span><span class="label">Ventes</span></a>
        <a class="nav-link" href="invoices.php"><span class="icon"><i class="fas fa-file-invoice"></i></span><span class="label">Factures/Devis</span></a>
        <a class="nav-link" href="payroll.php"><span class="icon"><i class="fas fa-file-invoice-dollar"></i></span><span class="label">Paies</span></a>
        <a class="nav-link" href="reports.php"><span class="icon"><i class="fas fa-chart-pie"></i></span><span class="label">Rapports</span></a>
      </nav>

      <div class="nav-divider" role="separator"></div>

      <nav class="nav-section" aria-label="Actions">
        <a class="nav-link" href="accounting.php"><span class="icon"><i class="fas fa-university"></i></span><span class="label">Comptabilité & Banque</span></a>
        <a class="nav-link" href="document-scanner.php"><span class="icon"><i class="fas fa-file-invoice"></i></span><span class="label">Scanner documents</span></a>
        <a class="nav-link" href="../crm/index.php" target="_blank"><span class="icon"><i class="fas fa-external-link-alt"></i></span><span class="label">Accès CRM</span></a>
      </nav>

      <div class="nav-divider" role="separator"></div>

      <nav class="nav-section" aria-label="Documentation">
        <a class="nav-link" href="documentation-utilisateurs.php"><span class="icon"><i class="fas fa-book-reader"></i></span><span class="label">Guide Utilisateur</span></a>
        <a class="nav-link" href="documentation-admin.php"><span class="icon"><i class="fas fa-book"></i></span><span class="label">Doc Avancée</span></a>
      </nav>

      <div class="user" role="note">
        <div class="avatar"><?= isset($_SESSION['user_name']) ? strtoupper(substr($_SESSION['user_name'],0,1)) : 'U' ?></div>
        <div>
          <div class="name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Utilisateur') ?></div>
          <div class="small" style="color:rgba(255,255,255,.6)"><?= htmlspecialchars($_SESSION['customer_name'] ?? '') ?></div>
        </div>
      </div>
    </aside>

  
  <button id="toggleSidebar" aria-label="Menu" title="Menu">☰</button>
  <div class="mobile-overlay" id="mobileOverlay" aria-hidden="true"></div>

<script>
  // Sidebar interactivity
  (function(){
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('toggleSidebar');
    const topbarToggle = document.getElementById('erpSidebarToggle');
    const overlay = document.getElementById('mobileOverlay');
    const openAddBtn = document.getElementById('openAdd');
    const mobileQuickActions = document.querySelector('.mobile-quick-actions');

    function isMobile(){ return window.innerWidth <= 900; }

    // Afficher/masquer les actions rapides selon la taille d'écran
    function updateMobileActions() {
      if (mobileQuickActions) {
        mobileQuickActions.style.display = isMobile() ? 'block' : 'none';
      }
    }

    updateMobileActions();
    window.addEventListener('resize', updateMobileActions);

    // Toggle depuis le bouton desktop
    toggle && toggle.addEventListener('click', ()=>{
      if (isMobile()){
        sidebar.classList.toggle('open');
        overlay.classList.toggle('active');
        document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
      } else {
        sidebar.classList.toggle('collapsed');
        document.querySelectorAll('.sidebar .label').forEach(el=> el.style.display = sidebar.classList.contains('collapsed') ? 'none' : '');
      }
    });

    // Toggle depuis la topbar (mobile)
    topbarToggle && topbarToggle.addEventListener('click', (e)=>{
      e.stopPropagation();
      const isOpen = sidebar.classList.contains('open');
      
      if (isOpen) {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        topbarToggle.querySelector('i').className = 'fas fa-bars';
        document.body.style.overflow = '';
      } else {
        sidebar.classList.add('open');
        overlay.classList.add('active');
        topbarToggle.querySelector('i').className = 'fas fa-times';
        document.body.style.overflow = 'hidden';
      }
    });

    overlay.addEventListener('click', ()=>{
      sidebar.classList.remove('open');
      overlay.classList.remove('active');
      document.body.style.overflow = '';
      if (topbarToggle) {
        topbarToggle.querySelector('i').className = 'fas fa-bars';
      }
    });

    // close on ESC
    document.addEventListener('keydown', (e)=>{ 
      if (e.key === 'Escape'){ 
        sidebar.classList.remove('open'); 
        overlay.classList.remove('active');
        document.body.style.overflow = '';
        if (topbarToggle) {
          topbarToggle.querySelector('i').className = 'fas fa-bars';
        }
      } 
    });

    // quick open add modal (if modal exists)
    openAddBtn && openAddBtn.addEventListener('click', ()=> {
      const addBtn = document.getElementById('btnAdd');
      if(addBtn) addBtn.click();
    });

    // sidebar search (simple client filter)
    const sInput = document.getElementById('sidebarSearch');
    sInput && sInput.addEventListener('input', (e)=>{
      const q = e.target.value.toLowerCase();
      document.querySelectorAll('.nav-section .nav-link').forEach(a=>{
        a.style.display = a.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });

    // Fermer la sidebar sur mobile quand on clique sur un lien
    document.querySelectorAll('.sidebar .nav-link').forEach(link => {
      link.addEventListener('click', () => {
        if (isMobile()) {
          sidebar.classList.remove('open');
          overlay.classList.remove('active');
          document.body.style.overflow = '';
          if (topbarToggle) {
            topbarToggle.querySelector('i').className = 'fas fa-bars';
          }
        }
      });
    });

    // responsive collapse on resize
    window.addEventListener('resize', ()=>{
      if (!isMobile()){
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
      }
    });
  })();
</script>

  <?php include __DIR__ . '/includes/customer_modals.php'; ?>

  <script>
    (function(){
      function qs(sel){ return document.querySelector(sel); }
      function show(sel){ var m=qs(sel); if(!m) return; m.classList.add('show'); m.setAttribute('aria-hidden','false'); }
      function hide(sel){ var m=qs(sel); if(!m) return; m.classList.remove('show'); m.setAttribute('aria-hidden','true'); }
      // Expose helpers if pages need to force customer validation
      window.erpShowCustomerModal = function(){ show('#erpCustomerModal'); };
      window.erpShowCustomerValidationModal = function(){ show('#erpCustomerValidationModal'); };
      window.erpHideCustomerModals = function(){ hide('#erpCustomerModal'); hide('#erpCustomerValidationModal'); };
    })();
  </script>

