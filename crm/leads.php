<?php
include("includes/verify_subscriptions.php");
include("includes/leads.php");

$page_title = "Leads - CRM Intelligent";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
     <link href="assets/css/leads.css" rel="stylesheet">
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        <?php include 'includes/topbar.php'; ?>
        
        <div class="main-content">
            
            <div class="container-fluid">
                <!-- En-tête -->
                <div class="leads-page-header">
                    <h1>
                        <i class="fas fa-bullseye"></i> Gestion des Leads
                    </h1>
                    <div class="leads-btn-group">
                        <a href="leads-export.php" class="btn btn-outline-primary leads-btn leads-btn-outline-primary">
                            <i class="fas fa-file-export"></i> Exporter
                        </a>
                        <a href="leads-add.php" class="btn btn-primary leads-btn leads-btn-primary">
                            <i class="fas fa-plus"></i> Nouveau Lead
                        </a>
                    </div>
                </div>

                <!-- KPIs Leads -->
                <div class="leads-kpi-container">
                    <div class="leads-kpi-card primary">
                        <div class="leads-kpi-icon">
                            <i class="fas fa-plus-circle"></i>
                        </div>
                        <div class="leads-kpi-value" id="new-leads"><?php echo isset($leads_kpis['new_leads']) ? intval($leads_kpis['new_leads']) : 0; ?></div>
                        <p class="leads-kpi-label">Nouveaux Leads</p>
                    </div>

                    <div class="leads-kpi-card success">
                        <div class="leads-kpi-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="leads-kpi-value" id="qualified-leads"><?php echo isset($leads_kpis['qualified']) ? intval($leads_kpis['qualified']) : 0; ?></div>
                        <p class="leads-kpi-label">Leads Qualifiés</p>
                    </div>

                    <div class="leads-kpi-card info">
                        <div class="leads-kpi-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="leads-kpi-value" id="conversion-rate"><?php echo isset($leads_kpis['conversion_rate']) ? htmlspecialchars($leads_kpis['conversion_rate']) : '0%'; ?></div>
                        <p class="leads-kpi-label">Taux de Conversion</p>
                    </div>

                    <div class="leads-kpi-card warning">
                        <div class="leads-kpi-icon">
                            <i class="fas fa-brain"></i>
                        </div>
                        <div class="leads-kpi-value" id="avg-score"><?php echo isset($leads_kpis['avg_score']) ? htmlspecialchars($leads_kpis['avg_score']) : '--'; ?></div>
                        <p class="leads-kpi-label">Score Moyen IA</p>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="leads-filters-section">
                    <h6 class="leads-filters-title">
                        <i class="fas fa-filter"></i> Filtres et Recherche
                    </h6>
                    
                    <div class="leads-filter-row">
                        <div class="leads-filter-group">
                            <label for="search-leads">Rechercher</label>
                            <input type="text" class="form-control" id="search-leads" placeholder="Nom, email, entreprise...">
                        </div>

                        <div class="leads-filter-group">
                            <label for="filter-status">Statut</label>
                            <select class="form-control" id="filter-status">
                                <option value="">Tous les statuts</option>
                                <option value="lead">Nouveau</option>
                                <option value="contacted">Contacté</option>
                                <option value="qualified">Qualifié</option>
                                <option value="proposal">Proposition</option>
                                <option value="converted">Converti</option>
                                <option value="unqualified">Perdu</option>
                            </select>
                        </div>

                        <div class="leads-filter-group">
                            <label for="filter-source">Source</label>
                            <select class="form-control" id="filter-source">
                                <option value="">Toutes les sources</option>
                                <option value="website">Site web</option>
                                <option value="social_media">Réseaux sociaux</option>
                                <option value="referral">Recommandation</option>
                            </select>
                        </div>

                        <div class="leads-filter-group">
                            <label for="filter-score">Score IA</label>
                            <select class="form-control" id="filter-score">
                                <option value="">Tous les scores</option>
                                <option value="hot">Chaud (80-100)</option>
                                <option value="warm">Tiède (60-79)</option>
                                <option value="cold">Froid (0-59)</option>
                            </select>
                        </div>
                    </div>

                    <div class="leads-filter-actions">
                        <button class="btn btn-outline-secondary leads-btn leads-btn-outline-secondary" onclick="resetFilters()">
                            <i class="fas fa-undo"></i> Réinitialiser
                        </button>
                        <button class="btn btn-info leads-btn leads-btn-info" onclick="refreshScoring()">
                            <i class="fas fa-brain"></i> Scorer avec IA
                        </button>
                    </div>
                </div>

                <!-- Liste des leads -->
                <div class="leads-table-container">
                    <div class="leads-table-header">
                        <h5>
                            <i class="fas fa-list"></i> Liste des Leads
                        </h5>
                        <span id="leads-count" class="leads-badge leads-badge-primary"><?php echo count($leads_list); ?> leads</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="leadsTable">
                            <thead>
                                <tr>
                                    <th>Score IA</th>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Entreprise</th>
                                    <th>Tags</th>
                                    <th class="d-none">Source</th>
                                    <th>Statut</th>
                                    <th>Créé le</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="leads-list">
                                <?php if (!empty($leads_list)): ?>
                                    <?php foreach ($leads_list as $lead): ?>
                                        <tr data-lead-id="<?php echo intval($lead['id']); ?>">
                                            <td class="readonly">
                                                <?php 
                                                $score = isset($lead['ai_score']) && $lead['ai_score'] !== null ? intval($lead['ai_score']) : null;
                                                if ($score !== null):
                                                    $scoreClass = $score >= 80 ? 'leads-score-hot' : ($score >= 60 ? 'leads-score-warm' : 'leads-score-cold');
                                                ?>
                                                    <span class="leads-score-badge <?php echo $scoreClass; ?>"><?php echo $score; ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">--</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="editable" data-field="name"><?php echo htmlspecialchars(trim(($lead['first_name'] ?? '').' '.($lead['last_name'] ?? ''))); ?></td>
                                            <td class="editable" data-field="email"><?php echo htmlspecialchars($lead['email'] ?? ''); ?></td>
                                            <td class="editable" data-field="company"><?php echo htmlspecialchars($lead['company_name'] ?? ''); ?></td>
                                            <td class="editable" data-field="tags"><?php echo htmlspecialchars($lead['tags'] ?? ''); ?></td>
                                            <td class="editable d-none" data-field="source"><?php echo htmlspecialchars($lead['source'] ?? ''); ?></td>
                                            <td class="readonly">
                                                <?php
                                                $status = $lead['stage'] ?? $lead['status'] ?? 'lead';
                                                $badgeClass = in_array($status, ['qualified', 'converted']) ? 'leads-badge-success' : 'leads-badge-secondary';
                                                ?>
                                                <span class="leads-badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($status); ?></span>
                                            </td>
                                            <td class="readonly"><?php echo htmlspecialchars(isset($lead['created_at']) ? date('d/m/Y', strtotime($lead['created_at'])) : ''); ?></td>
                                            <td class="readonly">
                                                <?php if (!empty($lead['id'])): ?>
                                                    <?php
                                                        $is_company     = !empty($lead['company_id']);
                                                        $is_opportunity = in_array($lead['id'], $lead_opportunity_ids ?? []);
                                                        $lead_full_name = urlencode(trim(($lead['first_name'] ?? '').' '.($lead['last_name'] ?? '')));
                                                        $lead_phone_enc = urlencode($lead['phone'] ?? '');
                                                    ?>
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="fas fa-ellipsis-h"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                            <li>
                                                                <a class="dropdown-item" href="leads-view.php?id=<?php echo intval($lead['id']); ?>" title="Voir le lead">
                                                                    <i class="fas fa-eye text-info me-2"></i> Voir
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item" href="leads-edit.php?id=<?php echo intval($lead['id']); ?>" title="Éditer le lead">
                                                                    <i class="fas fa-edit text-warning me-2"></i> Éditer
                                                                </a>
                                                            </li>
                                                            <li><hr class="dropdown-divider my-1"></li>
                                                            <li>
                                                                <button class="dropdown-item btn-add-opportunity <?php echo $is_opportunity ? 'disabled' : ''; ?>"
                                                                        data-lead-id="<?php echo intval($lead['id']); ?>"
                                                                        <?php if ($is_opportunity): ?>disabled<?php endif; ?>
                                                                        title="<?php echo $is_opportunity ? 'Déjà une opportunité' : 'Ajouter comme opportunité'; ?>">
                                                                    <i class="fas fa-handshake text-primary me-2"></i>
                                                                    Opportunité<?php if ($is_opportunity): ?> <i class="fas fa-check text-success ms-1 small"></i><?php endif; ?>
                                                                </button>
                                                            </li>
                                                            <li>
                                                                <button class="dropdown-item btn-add-company <?php echo $is_company ? 'disabled' : ''; ?>"
                                                                        data-lead-id="<?php echo intval($lead['id']); ?>"
                                                                        <?php if ($is_company): ?>disabled<?php endif; ?>
                                                                        title="<?php echo $is_company ? 'Déjà un client' : 'Ajouter comme client'; ?>">
                                                                    <i class="fas fa-building text-success me-2"></i>
                                                                    Client<?php if ($is_company): ?> <i class="fas fa-check text-success ms-1 small"></i><?php endif; ?>
                                                                </button>
                                                            </li>
                                                            <li><hr class="dropdown-divider my-1"></li>
                                                            <li>
                                                                <a class="dropdown-item"
                                                                   href="tasks.php?from_lead=<?php echo intval($lead['id']); ?>&amp;lead_name=<?php echo $lead_full_name; ?>"
                                                                   title="Créer une tâche pour ce lead">
                                                                    <i class="fas fa-tasks text-secondary me-2"></i> Tâche
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item"
                                                                   href="calls.php?from_lead=<?php echo intval($lead['id']); ?>&amp;lead_name=<?php echo $lead_full_name; ?>&amp;phone=<?php echo $lead_phone_enc; ?>"
                                                                   title="Planifier un appel pour ce lead">
                                                                    <i class="fas fa-phone text-success me-2"></i> Appel
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">--</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-5">
                                            <i class="fas fa-inbox fa-3x mb-3" style="opacity: 0.3; display: block;"></i>
                                            <p>Aucun lead trouvé.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Indicateurs -->
    <div class="leads-editing-indicator" id="editingIndicator">
        <i class="fas fa-edit"></i> Éditez la cellule...
    </div>
    <div class="leads-save-indicator" id="saveIndicator">
        <i class="fas fa-check"></i> Sauvegardé !
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // ===== ÉDITION INLINE DES CELLULES =====
    let editingCell = null;
    let originalValue = '';

    // Rendre les cellules éditables
    document.querySelectorAll('tbody td.editable').forEach(cell => {
        cell.addEventListener('dblclick', function() {
            if (editingCell && editingCell !== this) {
                cancelEdit();
            }
            startEdit(this);
        });
    });

    function startEdit(cell) {
        editingCell = cell;
        originalValue = cell.textContent.trim();
        
        const field = cell.dataset.field;
        const row = cell.closest('tr');
        const leadId = row.dataset.leadId;
        
        // Afficher indicateur d'édition
        document.getElementById('editingIndicator').classList.add('show');
        
        // Marquer comme en édition
        cell.classList.add('editing');
        
        // Créer l'input
        const input = document.createElement('input');
        input.type = 'text';
        input.value = originalValue;
        input.className = 'form-control';
        
        cell.textContent = '';
        cell.appendChild(input);
        input.focus();
        input.select();
        
        // Événements
        input.addEventListener('blur', () => saveEdit(cell, leadId, field, input.value));
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                saveEdit(cell, leadId, field, input.value);
            } else if (e.key === 'Escape') {
                cancelEdit();
            }
        });
    }

    async function saveEdit(cell, leadId, field, newValue) {
        if (!cell.classList.contains('editing')) return;
        
        // Si aucun changement
        if (newValue === originalValue) {
            cancelEdit();
            return;
        }
        
        try {
            // Sauvegarder via AJAX
            const formData = new FormData();
            formData.append('lead_id', leadId);
            formData.append('field', field);
            formData.append('value', newValue);
            
            const response = await fetch('api/update-lead-inline.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Succès - mettre à jour la cellule
                cell.textContent = newValue;
                cell.classList.remove('editing');
                
                // Afficher indicateur de succès
                document.getElementById('editingIndicator').classList.remove('show');
                const saveIndicator = document.getElementById('saveIndicator');
                saveIndicator.classList.add('show');
                setTimeout(() => {
                    saveIndicator.classList.remove('show');
                }, 2000);
                
                editingCell = null;
            } else {
                throw new Error(result.message || 'Erreur de sauvegarde');
            }
        } catch (error) {
            alert('❌ Erreur: ' + error.message);
            cancelEdit();
        }
    }

    function cancelEdit() {
        if (!editingCell) return;
        
        editingCell.textContent = originalValue;
        editingCell.classList.remove('editing');
        document.getElementById('editingIndicator').classList.remove('show');
        editingCell = null;
    }

    // Fermer l'édition en cliquant ailleurs
    document.addEventListener('click', (e) => {
        if (editingCell && !editingCell.contains(e.target)) {
            if (editingCell.querySelector('input')) {
                const input = editingCell.querySelector('input');
                const row = editingCell.closest('tr');
                const leadId = row.dataset.leadId;
                const field = editingCell.dataset.field;
                saveEdit(editingCell, leadId, field, input.value);
            }
        }
    });

    // ===== FILTRAGE DES LEADS =====
    function filterLeads() {
        const searchText = document.getElementById('search-leads').value.toLowerCase();
        const filterStatus = document.getElementById('filter-status').value;
        const filterSource = document.getElementById('filter-source').value;
        const filterScore = document.getElementById('filter-score').value;
        
        const rows = document.querySelectorAll('#leads-list tr[data-lead-id]');
        let visibleCount = 0;
        
        rows.forEach(row => {
            const nom = row.cells[1].textContent.toLowerCase();
            const email = row.cells[2].textContent.toLowerCase();
            const company = row.cells[3].textContent.toLowerCase();
            const source = row.cells[4].textContent.trim();
            const statusBadge = row.cells[5].textContent.trim();
            
            // Score
            const scoreCell = row.cells[0];
            const scoreBadge = scoreCell.querySelector('.score-badge');
            const score = scoreBadge ? parseInt(scoreBadge.textContent) : 0;
            
            // Vérifier la recherche
            let matchSearch = !searchText || nom.includes(searchText) || email.includes(searchText) || company.includes(searchText);
            
            // Vérifier le statut
            let matchStatus = !filterStatus || statusBadge.toLowerCase().includes(filterStatus.toLowerCase());
            
            // Vérifier la source
            let matchSource = !filterSource || source === filterSource;
            
            // Vérifier le score
            let matchScore = true;
            if (filterScore === 'hot') {
                matchScore = score >= 80;
            } else if (filterScore === 'warm') {
                matchScore = score >= 60 && score < 80;
            } else if (filterScore === 'cold') {
                matchScore = score > 0 && score < 60;
            }
            
            if (matchSearch && matchStatus && matchSource && matchScore) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Mettre à jour le compteur
        document.getElementById('leads-count').textContent = visibleCount + ' leads';
    }
    
    // Réinitialiser les filtres
    function resetFilters() {
        document.getElementById('search-leads').value = '';
        document.getElementById('filter-status').value = '';
        document.getElementById('filter-source').value = '';
        document.getElementById('filter-score').value = '';
        filterLeads();
    }
    
    // Recalculer les scores IA (redirige vers la page de scoring)
    function refreshScoring() {
        window.location.href = 'ai-recalculate.php';
    }
    
    // Ajouter les écouteurs d'événements
    document.getElementById('search-leads').addEventListener('keyup', filterLeads);
    document.getElementById('filter-status').addEventListener('change', filterLeads);
    document.getElementById('filter-source').addEventListener('change', filterLeads);
    document.getElementById('filter-score').addEventListener('change', filterLeads);

    // Ajouter un lead comme Opportunité
    document.querySelectorAll('.btn-add-opportunity').forEach(btn => {
        btn.addEventListener('click', async function() {
            if (this.disabled) return;
            const leadId = this.getAttribute('data-lead-id');
            if (!leadId) return;
            if (!confirm('Créer une opportunité à partir de ce lead ?')) return;

            const formData = new FormData();
            formData.append('lead_id', leadId);

            try {
                const response = await fetch('api/lead-to-opportunity.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success || result.already_exists) {
                    alert(result.success ? '✅ ' + result.message : 'ℹ️ ' + result.message);
                    this.disabled = true;
                    this.classList.add('disabled');
                    this.title = 'Déjà une opportunité';
                    this.innerHTML = '<i class="fas fa-handshake text-primary me-2"></i> Opportunité <i class="fas fa-check text-success ms-1 small"></i>';
                } else {
                    alert('❌ ' + (result.message || 'Erreur'));
                }
            } catch (error) {
                alert('❌ Erreur réseau: ' + error.message);
            }
        });
    });

    // Ajouter un lead dans Companies
    document.querySelectorAll('.btn-add-company').forEach(btn => {
        btn.addEventListener('click', async function() {
            if (this.disabled) return;
            const leadId = this.getAttribute('data-lead-id');
            if (!leadId) return;
            if (!confirm('Créer une company à partir de ce lead ?')) return;

            const formData = new FormData();
            formData.append('lead_id', leadId);

            try {
                const response = await fetch('api/lead-to-company.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success || result.already_exists) {
                    alert(result.success ? '✅ ' + result.message : 'ℹ️ ' + result.message);
                    this.disabled = true;
                    this.classList.add('disabled');
                    this.title = 'Déjà un client';
                    this.innerHTML = '<i class="fas fa-building text-success me-2"></i> Client <i class="fas fa-check text-success ms-1 small"></i>';
                } else {
                    alert('❌ ' + (result.message || 'Erreur'));
                }
            } catch (error) {
                alert('❌ Erreur réseau: ' + error.message);
            }
        });
    });
    </script>
</body>
</html>