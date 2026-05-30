<?php
require_once 'includes/folders.php';

// Calculer les KPIs
$total_folders = count($folders);
$folders_open = count(array_filter($folders, function($f) { return in_array($f['status_name'], ['Ouverte', 'En cours']); }));
$folders_completed = count(array_filter($folders, function($f) { return $f['status_name'] === 'Terminée'; }));
$completion_rate_folders = $total_folders > 0 ? round(($folders_completed / $total_folders) * 100) : 0;
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
    <link href="assets/css/folders.css" rel="stylesheet">
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        <?php include 'includes/topbar.php'; ?>
        
        <div class="main-content">
            <div class="container-fluid">

                <!-- En-tête -->
                <div class="page-header">
                    <h1>
                        <i class="fas fa-folder-open"></i> Gestion des Dossiers
                    </h1>
                    <div class="btn-group">
                        <a href="folders-export.php" class="btn btn-outline-primary">
                            <i class="fas fa-file-export"></i> Exporter
                        </a>
                        <a href="folder_add.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nouveau Dossier
                        </a>
                    </div>
                </div>

                <!-- KPIs - Analytics -->
                <div class="kpi-container">
                    <div class="kpi-card info">
                        <div class="kpi-icon">
                            <i class="fas fa-folder"></i>
                        </div>
                        <div class="kpi-value" id="total-folders"><?php echo $total_folders; ?></div>
                        <p class="kpi-label">Total Dossiers</p>
                    </div>

                    <div class="kpi-card warning">
                        <div class="kpi-icon">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                        <div class="kpi-value" id="open-folders"><?php echo $folders_open; ?></div>
                        <p class="kpi-label">En Cours</p>
                    </div>

                    <div class="kpi-card success">
                        <div class="kpi-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="kpi-value" id="completed-folders"><?php echo $folders_completed; ?></div>
                        <p class="kpi-label">Terminés</p>
                    </div>

                    <div class="kpi-card primary">
                        <div class="kpi-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="kpi-value" id="completion-rate-folders"><?php echo $completion_rate_folders; ?>%</div>
                        <p class="kpi-label">Taux Complétion</p>
                    </div>
                </div>

                <!-- Filtres et Recherche -->
                <div class="filters-section">
                    <h6 class="filters-title">
                        <i class="fas fa-filter"></i> Filtres et Recherche
                    </h6>
                    
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="search-folder">Rechercher</label>
                            <input type="text" id="search-folder" class="form-control" placeholder="Nom, entreprise...">
                        </div>

                        <div class="filter-group">
                            <label for="filter-status-folder">Statut</label>
                            <select id="filter-status-folder" class="form-select">
                                <option value="">Tous les statuts</option>
                                <option value="Ouverte">Ouverte</option>
                                <option value="En cours">En cours</option>
                                <option value="Terminée">Terminée</option>
                                <option value="Facturée">Facturée</option>
                                <option value="Payée">Payée</option>
                                <option value="Annulée">Annulée</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="filter-company">Entreprise</label>
                            <select id="filter-company" class="form-select">
                                <option value="">Toutes les entreprises</option>
                                <?php
                                $company_options = array_unique(array_map(function($f) { return $f['company_name']; }, $folders));
                                foreach ($company_options as $company):
                                ?>
                                    <option value="<?php echo htmlspecialchars($company); ?>"><?php echo htmlspecialchars($company); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="filter-actions">
                        <button class="btn btn-primary" id="apply-filters-folder">
                            <i class="fas fa-search"></i> Appliquer
                        </button>
                        <button class="btn btn-outline-primary" id="reset-filters-folder">
                            <i class="fas fa-undo"></i> Réinitialiser
                        </button>
                    </div>
                </div>

                <!-- Tableau des Dossiers -->
                <div class="folders-table-container">
                    <div class="folders-table-header">
                        <h5>Liste des Dossiers</h5>
                        <span id="folders-count" class="badge bg-primary"><?php echo $total_folders; ?> dossiers</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="foldersTable">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Entreprise</th>
                                    <th>Date de création</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="folders-list">
                                    <?php if (!empty($folders)): ?>
                                        <?php foreach ($folders as $folder): ?>
                                            <tr data-folder-id="<?php echo intval($folder['id']); ?>">
                                                <td class="editable" data-field="name"><?php echo htmlspecialchars($folder['name']); ?></td>
                                                <td class="readonly"><?php echo htmlspecialchars($folder['company_name']); ?></td>
                                                <td class="readonly"><?php echo htmlspecialchars(date('d/m/Y', strtotime($folder['created_at']))); ?></td>
                                                <td class="readonly">
                                                    <?php
                                                    $status = $folder['status_name'] ?? 'Non défini';
                                                    $badge = 'badge-secondary';
                                                    $icon = 'fa-clock';
                                                    if ($status === 'Terminée') {
                                                        $badge = 'badge-success';
                                                        $icon = 'fa-check';
                                                    } elseif ($status === 'En cours') {
                                                        $badge = 'badge-warning';
                                                        $icon = 'fa-spinner';
                                                    } elseif ($status === 'Ouverte') {
                                                        $badge = 'badge-info';
                                                        $icon = 'fa-play';
                                                    } elseif ($status === 'Facturée') {
                                                        $badge = 'badge-primary';
                                                        $icon = 'fa-file-invoice';
                                                    } elseif ($status === 'Payée') {
                                                        $badge = 'badge-success';
                                                        $icon = 'fa-euro-sign';
                                                    } elseif ($status === 'Annulée') {
                                                        $badge = 'badge-danger';
                                                        $icon = 'fa-times';
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $badge; ?>">
                                                        <i class="fas <?php echo $icon; ?>"></i> <?php echo htmlspecialchars($status); ?>
                                                    </span>
                                                </td>
                                                <td class="readonly">
                                                    <div class="actions-group">
                                                        <a href="folder_view.php?id=<?php echo $folder['id']; ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                                                        <a href="folder_edit.php?id=<?php echo $folder['id']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                                        <a href="folder_delete.php?id=<?php echo $folder['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer ce dossier ?');"><i class="fas fa-trash"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-5">
                                                <i class="fas fa-inbox fa-3x mb-3" style="opacity: 0.3; display: block;"></i>
                                                <p>Aucun dossier trouvé.</p>
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
    </div>

    <!-- Indicateurs d'édition -->
    <div class="editing-indicator" id="editingIndicator">
        <i class="fas fa-edit"></i> Éditez la cellule...
    </div>
    <div class="save-indicator" id="saveIndicator">
        <i class="fas fa-check"></i> Sauvegardé !
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Filtrer les dossiers
    function filterFolders() {
        const searchText = document.getElementById('search-folder').value.toLowerCase();
        const filterStatus = document.getElementById('filter-status-folder').value;
        const filterCompany = document.getElementById('filter-company').value;
        
        const rows = document.querySelectorAll('#folders-list tr');
        let visibleCount = 0;
        
        rows.forEach(row => {
            if (row.querySelector('td') === null) return;
            
            const name = row.cells[0]?.textContent.toLowerCase() || '';
            const company = row.cells[1]?.textContent || '';
            const status = row.cells[3]?.textContent.trim() || '';
            
            let matchSearch = !searchText || name.includes(searchText) || company.toLowerCase().includes(searchText);
            let matchStatus = !filterStatus || status.includes(filterStatus);
            let matchCompany = !filterCompany || company.includes(filterCompany);
            
            if (matchSearch && matchStatus && matchCompany) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Mettre à jour le compteur
        const header = document.querySelector('.folders-table-header span');
        if (header) {
            header.textContent = visibleCount + ' dossier' + (visibleCount !== 1 ? 's' : '');
        }
    }

    // Réinitialiser les filtres
    function resetFilters() {
        document.getElementById('search-folder').value = '';
        document.getElementById('filter-status-folder').value = '';
        document.getElementById('filter-company').value = '';
        filterFolders();
    }

    // Ajouter les écouteurs d'événements
    document.getElementById('search-folder').addEventListener('keyup', filterFolders);
    document.getElementById('filter-status-folder').addEventListener('change', filterFolders);
    document.getElementById('filter-company').addEventListener('change', filterFolders);
    document.getElementById('apply-filters-folder').addEventListener('click', filterFolders);
    document.getElementById('reset-filters-folder').addEventListener('click', resetFilters);

    // =============================================
    // ÉDITION INLINE - Dossiers
    // =============================================
    
    const editingIndicator = document.getElementById('editingIndicator');
    const saveIndicator = document.getElementById('saveIndicator');
    let editingCell = null;

    // Double-clic pour éditer
    document.querySelectorAll('tbody td.editable').forEach(cell => {
        cell.addEventListener('dblclick', function() {
            if (editingCell) return;
            
            editingCell = this;
            const currentValue = this.textContent.trim();
            const field = this.dataset.field;
            
            this.classList.add('editing');
            editingIndicator.classList.add('show');
            
            const input = document.createElement('input');
            input.type = 'text';
            input.value = currentValue;
            input.className = 'form-control';
            
            this.innerHTML = '';
            this.appendChild(input);
            input.focus();
            input.select();
            
            const saveEdit = async () => {
                const newValue = input.value.trim();
                const row = this.closest('tr');
                const folderId = row.dataset.folderId;
                
                editingIndicator.classList.remove('show');
                this.classList.remove('editing');
                
                if (newValue !== currentValue) {
                    try {
                        const formData = new FormData();
                        formData.append('folder_id', folderId);
                        formData.append('field', field);
                        formData.append('value', newValue);
                        
                        const response = await fetch('api/update-folder-inline.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            this.textContent = newValue;
                            saveIndicator.classList.add('show');
                            setTimeout(() => {
                                saveIndicator.classList.remove('show');
                            }, 2000);
                        } else {
                            alert('Erreur: ' + result.message);
                            this.textContent = currentValue;
                        }
                    } catch (error) {
                        console.error('Erreur:', error);
                        alert('Erreur lors de la sauvegarde');
                        this.textContent = currentValue;
                    }
                } else {
                    this.textContent = currentValue;
                }
                
                editingCell = null;
            };
            
            input.addEventListener('blur', saveEdit);
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    saveEdit();
                }
            });
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    editingIndicator.classList.remove('show');
                    this.classList.remove('editing');
                    this.textContent = currentValue;
                    editingCell = null;
                }
            });
        });
    });
    </script>