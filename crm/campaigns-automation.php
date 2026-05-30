<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/includes/verify_subscriptions.php';


$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$customer_id = $_SESSION['customer_id'] ?? null;

// Récupérer les companies liées au même customer_id
$companies = [];
if($customer_id){
    try{
        $cstmt = $pdo->prepare('SELECT id, name FROM companies WHERE customer_id = ? ORDER BY name ASC');
        $cstmt->execute([$customer_id]);
        $companies = $cstmt->fetchAll();
    }catch(Exception $e){
        $companies = [];
    }
}
// Récupérer les automatisations pour ce customer_id (multi-tenant)
$automations = [];
$active_count = 0;
try{
    if(!empty($customer_id)){
        $astmt = $pdo->prepare('SELECT * FROM automations WHERE customer_id = ? ORDER BY created_at DESC');
        $astmt->execute([$customer_id]);
    }else{
        $astmt = $pdo->query('SELECT * FROM automations ORDER BY created_at DESC');
    }
    $automations = $astmt->fetchAll();
    foreach($automations as $a){ if(isset($a['status']) && $a['status'] === 'active') $active_count++; }
}catch(Exception $e){
    $automations = [];
}
// require_once 'includes/header.php';
?>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">

      <style>
    .automation-flow-preview {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        height: 400px;
        overflow-y: auto;
    }

    .flow-step {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
    }

    .step-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        border: 2px solid #dee2e6;
    }

    .flow-connector {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin: 10px 0;
        margin-left: 20px;
    }

    .connector-line {
        width: 2px;
        height: 20px;
        background: #dee2e6;
    }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        <?php include 'includes/topbar.php'; ?>
        
        <div class="main-content">

            
            <div class="container-fluid">
          
        
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Automatisation Marketing</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newAutomationModal">
                                <i class="fas fa-robot"></i> Nouvel Automatisme
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-download"></i> Templates
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Métriques des automatisations -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="card bg-primary text-white mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="fs-4 fw-bold"><?php echo intval($active_count); ?></div>
                                        <div>Automatisations actives</div>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-cogs fs-2"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card bg-success text-white mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="fs-4 fw-bold">1,847</div>
                                        <div>Contacts traités</div>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-users fs-2"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card bg-warning text-white mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="fs-4 fw-bold">67.3%</div>
                                        <div>Taux de conversion</div>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-chart-line fs-2"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card bg-info text-white mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="fs-4 fw-bold">€12,450</div>
                                        <div>Revenus générés</div>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-euro-sign fs-2"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Templates préconçus -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Templates d'Automatisation</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="card h-100 border-primary">
                                    <div class="card-body text-center">
                                        <i class="fas fa-hand-holding-heart fs-1 text-primary mb-3"></i>
                                        <h6 class="card-title">Séquence de Bienvenue</h6>
                                        <p class="card-text small">Accueillez vos nouveaux clients avec une série d'emails personnalisés</p>
                                        <a class="btn btn-primary btn-sm" href="email-editor.php?tpl=welcome" target="_blank">Utiliser ce template</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card h-100 border-warning">
                                    <div class="card-body text-center">
                                        <i class="fas fa-shopping-cart fs-1 text-warning mb-3"></i>
                                        <h6 class="card-title">Panier Abandonné</h6>
                                        <p class="card-text small">Récupérez les ventes perdues avec des rappels automatiques</p>
                                        <a class="btn btn-warning btn-sm" href="email-editor.php?tpl=abandoned_cart" target="_blank">Utiliser ce template</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card h-100 border-success">
                                    <div class="card-body text-center">
                                        <i class="fas fa-birthday-cake fs-1 text-success mb-3"></i>
                                        <h6 class="card-title">Anniversaire Client</h6>
                                        <p class="card-text small">Fidélisez vos clients avec des offres d'anniversaire</p>
                                        <a class="btn btn-success btn-sm" href="email-editor.php?tpl=birthday" target="_blank">Utiliser ce template</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Liste des automatisations -->
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Automatisations Actives</h5>
                            <div class="btn-group" role="group">
                                <input type="radio" class="btn-check" name="viewMode" id="listView" autocomplete="off" checked>
                                <label class="btn btn-outline-secondary btn-sm" for="listView">
                                    <i class="fas fa-list"></i>
                                </label>
                                <input type="radio" class="btn-check" name="viewMode" id="flowView" autocomplete="off">
                                <label class="btn btn-outline-secondary btn-sm" for="flowView">
                                    <i class="fas fa-project-diagram"></i>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="listViewContent">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nom</th>
                                            <th>Type</th>
                                            <th>Statut</th>
                                            <th>Déclencheur</th>
                                            <th>Contacts actifs</th>
                                            <th>Conversion</th>
                                            <th>Dernière activité</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if(!empty($automations)): ?>
                                        <?php foreach($automations as $automation): ?>
                                            <?php
                                                $aid = intval($automation['id']);
                                                $atype = htmlspecialchars($automation['type'] ?? '');
                                                $aname = htmlspecialchars($automation['name'] ?? '');
                                                $astatus = htmlspecialchars($automation['status'] ?? 'paused');
                                                $atrigger = htmlspecialchars($automation['trigger_type'] ?? '');
                                                $last = htmlspecialchars($automation['created_at'] ?? '');
                                                // estimate contacts count (COUNT query)
                                                try{
                                                    if(!empty($automation['customer_id'])){
                                                        $cstmt = $pdo->prepare('SELECT COUNT(*) as c FROM contacts WHERE customer_id = ?');
                                                        $cstmt->execute([$automation['customer_id']]);
                                                    }else{
                                                        $cstmt = $pdo->query('SELECT COUNT(*) as c FROM contacts');
                                                    }
                                                    $cres = $cstmt->fetch();
                                                    $contacts_count = intval($cres['c'] ?? 0);
                                                }catch(Exception $e){ $contacts_count = 0; }
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="automation-icon me-2">
                                                            <i class="fas fa-robot text-primary"></i>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold"><?php echo $aname; ?></div>
                                                            <small class="text-muted"><?php echo (isset($automation['id']) ? 'ID ' . $aid : '—'); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><span class="badge bg-secondary"><?php echo $atype; ?></span></td>
                                                <td><span class="badge <?php echo ($astatus==='active')? 'bg-success':'bg-secondary'; ?>"><?php echo $astatus; ?></span></td>
                                                <td><?php echo $atrigger; ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="me-2"><?php echo $contacts_count; ?></span>
                                                        <div class="progress" style="width: 50px; height: 8px;">
                                                            <div class="progress-bar" style="width: <?php echo ($contacts_count>0)? min(100,($contacts_count/1000)*100) : 0; ?>%"></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="text-success fw-bold"><?php echo isset($automation['open_rate'])? (float)$automation['open_rate'].'%':'—'; ?></span>
                                                </td>
                                                <td><?php echo $last ? date('d/m H:i', strtotime($last)) : '—'; ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a class="btn btn-sm btn-outline-primary" title="Voir le flux" href="automations-view.php?id=<?php echo $aid; ?>">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a class="btn btn-sm btn-outline-secondary" title="Modifier" href="automations-edit.php?id=<?php echo $aid; ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php if($astatus === 'active'): ?>
                                                            <a class="btn btn-sm btn-outline-danger" title="Suspendre" href="automations-pause.php?id=<?php echo $aid; ?>">
                                                                <i class="fas fa-pause"></i>
                                                            </a>
                                                        <?php else: ?>
                                                            <a class="btn btn-sm btn-outline-success" title="Reprendre" href="automations-toggle.php?id=<?php echo $aid; ?>">
                                                                <i class="fas fa-play"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <a class="btn btn-sm btn-outline-danger" title="Supprimer" href="automations-delete.php?id=<?php echo $aid; ?>" onclick="return confirm('Supprimer cette automatisation ?');">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="8" class="text-center text-muted">Aucune automatisation trouvée.</td></tr>
                                    <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Vue flux (masquée par défaut) -->
                        <div id="flowViewContent" style="display: none;">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                La vue flux visuelle sera disponible dans une prochaine version. 
                                Utilisez la vue liste pour gérer vos automatisations.
                            </div>
                        </div>
                    </div>
                </div>
        
          <!-- Modal Nouvelle Automatisation -->
            <div class="modal fade" id="newAutomationModal" tabindex="-1">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Créer une Automatisation</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <!-- Configuration générale -->
                                <div class="col-md-6">
                                    <h6 class="fw-bold mb-3">Configuration Générale</h6>
                                    <form id="newAutomationForm">
                                        <div class="mb-3">
                                            <label for="automationName" class="form-label">Nom de l'automatisation</label>
                                            <input type="text" class="form-control" id="automationName" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="automationType" class="form-label">Type</label>
                                            <select class="form-select" id="automationType" required>
                                                <option value="">Sélectionner...</option>
                                                <option value="welcome">Séquence de bienvenue</option>
                                                <option value="abandoned_cart">Panier abandonné</option>
                                                <option value="reactivation">Réactivation client</option>
                                                <option value="birthday">Anniversaire</option>
                                                <option value="nurturing">Nurturing leads</option>
                                                <option value="upsell">Upsell/Cross-sell</option>
                                                <option value="custom">Personnalisée</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="triggerType" class="form-label">Déclencheur</label>
                                            <select class="form-select" id="triggerType" required>
                                                <option value="">Sélectionner...</option>
                                                <option value="signup">Inscription</option>
                                                <option value="purchase">Achat</option>
                                                <option value="cart_abandon">Panier abandonné</option>
                                                <option value="date">Date anniversaire</option>
                                                <option value="inactivity">Inactivité</option>
                                                <option value="tag_added">Tag ajouté</option>
                                                <option value="custom_field">Champ personnalisé</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="audienceFilter" class="form-label">Audience cible</label>
                                            <select class="form-select" id="audienceFilter">
                                                <option value="all">Tous les contacts</option>
                                                <option value="subscribers">Abonnés newsletter</option>
                                                <option value="customers">Clients</option>
                                                <option value="prospects">Prospects</option>
                                                <option value="segment">Segment spécifique</option>
                                                <?php if(!empty($companies)): ?>
                                                    <optgroup label="Companies">
                                                        <?php foreach($companies as $comp): ?>
                                                            <option value="company_<?php echo intval($comp['id']); ?>"><?php echo htmlspecialchars($comp['name']); ?></option>
                                                        <?php endforeach; ?>
                                                    </optgroup>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                    </form>
                                </div>

                                <!-- Prévisualisation du flux -->
                                <div class="col-md-6">
                                    <h6 class="fw-bold mb-3">Flux d'Automatisation</h6>
                                    <div class="automation-flow-preview">
                                        <div class="flow-step">
                                            <div class="step-icon">
                                                <i class="fas fa-play text-success"></i>
                                            </div>
                                            <div class="step-content">
                                                <strong>Déclencheur</strong>
                                                <small class="text-muted d-block">Définit quand l'automatisation démarre</small>
                                            </div>
                                        </div>
                                        <div class="flow-connector">
                                            <div class="connector-line"></div>
                                            <i class="fas fa-arrow-down text-muted"></i>
                                        </div>
                                        <div class="flow-step">
                                            <div class="step-icon">
                                                <i class="fas fa-envelope text-primary"></i>
                                            </div>
                                            <div class="step-content">
                                                <strong>Email 1</strong>
                                                <small class="text-muted d-block">Immédiatement</small>
                                            </div>
                                        </div>
                                        <div class="flow-connector">
                                            <div class="connector-line"></div>
                                            <i class="fas fa-arrow-down text-muted"></i>
                                        </div>
                                        <div class="flow-step">
                                            <div class="step-icon">
                                                <i class="fas fa-clock text-warning"></i>
                                            </div>
                                            <div class="step-content">
                                                <strong>Attente</strong>
                                                <small class="text-muted d-block">2 jours</small>
                                            </div>
                                        </div>
                                        <div class="flow-connector">
                                            <div class="connector-line"></div>
                                            <i class="fas fa-arrow-down text-muted"></i>
                                        </div>
                                        <div class="flow-step">
                                            <div class="step-icon">
                                                <i class="fas fa-envelope text-primary"></i>
                                            </div>
                                            <div class="step-content">
                                                <strong>Email 2</strong>
                                                <small class="text-muted d-block">Rappel</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#templateSuggestionsModal">
                                <i class="fas fa-wand-magic-sparkles"></i> Templates
                            </button>
                            <button type="button" id="createAutomationBtn" class="btn btn-primary">Créer l'automatisation</button>
                        </div>
                    </div>
                </div>
            </div>
            </div>
            

          
        </div>
        </div>

        <!-- Bootstrap JS bundle (includes Popper) required for modals and data-bs attributes -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

        <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gestion des vues liste/flux
        const listView = document.getElementById('listView');
        const flowView = document.getElementById('flowView');
        const listViewContent = document.getElementById('listViewContent');
        const flowViewContent = document.getElementById('flowViewContent');

        if(listView && typeof listView.addEventListener === 'function'){
            listView.addEventListener('change', function() {
                if (this.checked) {
                    listViewContent.style.display = 'block';
                    flowViewContent.style.display = 'none';
                }
            });
        }

        if(flowView && typeof flowView.addEventListener === 'function'){
            flowView.addEventListener('change', function() {
                if (this.checked) {
                    listViewContent.style.display = 'none';
                    flowViewContent.style.display = 'block';
                }
            });
        }

        // Mise à jour de la prévisualisation du flux
        const automationType = document.getElementById('automationType');
        const triggerType = document.getElementById('triggerType');

        function updateFlowPreview() {
            // Logique pour mettre à jour la prévisualisation du flux
            console.log('Mise à jour de la prévisualisation...');
        }

        // Guarded listeners (elements might be absent in some contexts)
        if(automationType && typeof automationType.addEventListener === 'function'){
            automationType.addEventListener('change', updateFlowPreview);
        }
        if(triggerType && typeof triggerType.addEventListener === 'function'){
            triggerType.addEventListener('change', updateFlowPreview);
        }

        // Submit automation via fetch
        const createBtn = document.getElementById('createAutomationBtn');
        const newForm = document.getElementById('newAutomationForm');
        createBtn && createBtn.addEventListener('click', async function(e){
            e.preventDefault();
            const name = document.getElementById('automationName').value.trim();
            const type = document.getElementById('automationType').value;
            const trigger = document.getElementById('triggerType').value;
            const audience = document.getElementById('audienceFilter').value;
            if(!name || !type || !trigger){
                alert('Veuillez renseigner le nom, le type et le déclencheur');
                return;
            }

            const payload = { name: name, type: type, trigger_type: trigger, audience_filter: audience };

            try{
                const res = await fetch('create_automation.php', {
                    method: 'POST',
                    headers: {'Content-Type':'application/json'},
                    body: JSON.stringify(payload)
                });
                const json = await res.json();
                if(json && json.success){
                    // close modal and reload to show the new automation
                    const modalEl = document.getElementById('newAutomationModal');
                    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                    modal.hide();
                    alert(json.message || 'Automatisation créée');
                    window.location.reload();
                }else{
                    alert(json.message || 'Erreur lors de la création');
                }
            }catch(err){
                console.error(err);
                alert('Erreur réseau ou serveur');
            }
        });

        // Realtime polling to refresh automations list
        const POLL_INTERVAL = 8000; // ms
        const tableBody = document.querySelector('table.table tbody');
        const activeCountEl = document.querySelector('.card.bg-primary .fs-4');

        function escapeHtml(s){
            if(s === null || s === undefined) return '';
            return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        function renderRows(data){
            if(!Array.isArray(data) || data.length === 0){
                tableBody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">Aucune automatisation trouvée.</td></tr>';
                activeCountEl.textContent = '0';
                return;
            }
            let rows = '';
            let activeCount = 0;
            data.forEach(a => {
                const aid = parseInt(a.id || 0,10);
                const aname = escapeHtml(a.name || '');
                const atype = escapeHtml(a.type || '');
                const astatus = escapeHtml(a.status || 'paused');
                const atrigger = escapeHtml(a.trigger_type || '');
                const contacts = parseInt(a.contacts_count || 0,10);
                if(astatus === 'active') activeCount++;
                const last = a.created_at ? new Date(a.created_at) : null;
                const lastStr = last ? (('0'+last.getDate()).slice(-2)) + '/' + (('0'+(last.getMonth()+1)).slice(-2)) + ' ' + (('0'+last.getHours()).slice(-2)) + ':' + (('0'+last.getMinutes()).slice(-2)) : '—';

                rows += `<tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="automation-icon me-2"><i class="fas fa-robot text-primary"></i></div>
                            <div><div class="fw-bold">${aname}</div><small class="text-muted">ID ${aid}</small></div>
                        </div>
                    </td>
                    <td><span class="badge bg-secondary">${atype}</span></td>
                    <td><span class="badge ${astatus==='active' ? 'bg-success' : 'bg-secondary'}">${astatus}</span></td>
                    <td>${atrigger}</td>
                    <td><div class="d-flex align-items-center"><span class="me-2">${contacts}</span><div class="progress" style="width:50px;height:8px;"><div class="progress-bar" style="width:${contacts>0?Math.min(100,(contacts/1000)*100):0}%"></div></div></div></td>
                    <td><span class="text-success fw-bold">${a.open_rate? a.open_rate + '%' : '—'}</span></td>
                    <td>${lastStr}</td>
                    <td>
                        <div class="btn-group" role="group">
                            <a class="btn btn-sm btn-outline-primary" title="Voir le flux" href="automations-view.php?id=${aid}"><i class="fas fa-eye"></i></a>
                            <a class="btn btn-sm btn-outline-secondary" title="Modifier" href="automations-edit.php?id=${aid}"><i class="fas fa-edit"></i></a>
                            ${astatus==='active' ? `<a class="btn btn-sm btn-outline-danger" title="Suspendre" href="automations-pause.php?id=${aid}"><i class="fas fa-pause"></i></a>` : `<a class="btn btn-sm btn-outline-success" title="Reprendre" href="automations-toggle.php?id=${aid}"><i class="fas fa-play"></i></a>`}
                            <a class="btn btn-sm btn-outline-danger" title="Supprimer" href="automations-delete.php?id=${aid}" onclick="return confirm('Supprimer cette automatisation ?');"><i class="fas fa-trash"></i></a>
                        </div>
                    </td>
                </tr>`;
            });
            tableBody.innerHTML = rows;
            activeCountEl.textContent = String(activeCount);
        }

        async function poll(){
            try{
                const r = await fetch('automations-list.php');
                const j = await r.json();
                if(j && j.success){ renderRows(j.data); }
            }catch(e){ console.error('poll error', e); }
        }

        // start polling
        setInterval(poll, POLL_INTERVAL);
        // initial load
        poll();
    });
    </script>

    <!-- Modal pour les suggestions de templates -->
    <?php include 'includes/template_suggestions_modal.php'; ?>

</body>
</html></body>
</html>