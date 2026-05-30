<?php
include 'includes/missions.php';

// Calculer les KPIs
$total_missions = count($missions);
$missions_in_progress = count(array_filter($missions, function($m) { 
    return in_array($m['status_name'], ['En cours', 'À faire']); 
}));
$missions_completed = count(array_filter($missions, function($m) { 
    return $m['status_name'] === 'Terminée'; 
}));
$completion_rate = $total_missions > 0 ? round(($missions_completed / $total_missions) * 100) : 0;
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
    <link href="assets/css/missions.css" rel="stylesheet">
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
                        <i class="fas fa-tasks"></i> Gestion des Missions
                    </h1>
                    <div class="btn-group">
                        <a href="missions.php" class="btn btn-outline-primary">
                            <i class="fas fa-redo"></i> Réinitialiser
                        </a>
                        <a href="mission_add.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nouvelle Mission
                        </a>
                    </div>
                </div>

                <!-- KPIs - Analytics -->
                <div class="kpi-container">
                    <div class="kpi-card info">
                        <div class="kpi-icon">
                            <i class="fas fa-list-check"></i>
                        </div>
                        <div class="kpi-value" id="total-missions"><?php echo $total_missions; ?></div>
                        <p class="kpi-label">Total Missions</p>
                    </div>

                    <div class="kpi-card warning">
                        <div class="kpi-icon">
                            <i class="fas fa-spinner"></i>
                        </div>
                        <div class="kpi-value" id="in-progress"><?php echo $missions_in_progress; ?></div>
                        <p class="kpi-label">En Cours</p>
                    </div>

                    <div class="kpi-card success">
                        <div class="kpi-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="kpi-value" id="completed-missions"><?php echo $missions_completed; ?></div>
                        <p class="kpi-label">Terminées</p>
                    </div>

                    <div class="kpi-card primary">
                        <div class="kpi-icon">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <div class="kpi-value" id="completion-rate"><?php echo $completion_rate; ?>%</div>
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
                            <label for="search-mission">Rechercher</label>
                            <input type="text" id="search-mission" class="form-control" placeholder="Chauffeur, véhicule, départ...">
                        </div>

                        <div class="filter-group">
                            <label for="filter-status">Statut</label>
                            <select id="filter-status" class="form-select">
                                <option value="">Tous les statuts</option>
                                <option value="À faire">À faire</option>
                                <option value="En cours">En cours</option>
                                <option value="Terminée">Terminée</option>
                                <option value="Annulée">Annulée</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="filter-folder">Dossier</label>
                            <select id="filter-folder" class="form-select">
                                <option value="">Tous les dossiers</option>
                                <?php
                                $folder_options = array_unique(array_map(function($m) { return $m['folder_name']; }, $missions));
                                foreach ($folder_options as $folder):
                                ?>
                                    <option value="<?php echo htmlspecialchars($folder); ?>"><?php echo htmlspecialchars($folder); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="filter-driver">Chauffeur</label>
                            <select id="filter-driver" class="form-select">
                                <option value="">Tous les chauffeurs</option>
                                <?php
                                $driver_options = array_unique(array_filter(array_map(function($m) { return $m['driver']; }, $missions)));
                                foreach ($driver_options as $driver):
                                ?>
                                    <option value="<?php echo htmlspecialchars($driver); ?>"><?php echo htmlspecialchars($driver); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="filter-actions">
                        <button class="btn btn-primary" id="apply-filters">
                            <i class="fas fa-search"></i> Appliquer
                        </button>
                        <button class="btn btn-outline-primary" id="reset-filters">
                            <i class="fas fa-undo"></i> Réinitialiser
                        </button>
                    </div>
                </div>

                <!-- Tableau des Missions -->
                <div class="missions-table-container">
                    <div class="missions-table-header">
                        <h5>Liste des Missions</h5>
                        <span id="missions-count" class="badge bg-primary"><?php echo $total_missions; ?> missions</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="missionsTable">
                            <thead>
                                <tr>
                                    <th># Mission</th>
                                    <th>Société</th>
                                    <th># Dossier</th>
                                    <th>Départ</th>
                                    <th>Arrivée</th>
                                    <th>Date/Heure</th>
                                    <th>Chauffeur</th>
                                    <th>Véhicule</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="missions-list">
                                    <?php foreach ($missions as $mission): ?>
                                    <tr data-mission-id="<?php echo intval($mission['mission_id']); ?>">
                                        <td class="readonly">M-<?php echo htmlspecialchars($mission['mission_id']); ?></td>
                                        <td class="readonly"><?php echo htmlspecialchars($mission['company_name']); ?></td>
                                        <td class="readonly">D-<?php echo htmlspecialchars($mission['folder_id']); ?> (<?php echo htmlspecialchars($mission['folder_name']); ?>)</td>
                                        <td class="editable" data-field="departure"><?php echo htmlspecialchars($mission['departure'] ?? ''); ?></td>
                                        <td class="editable" data-field="arrival"><?php echo htmlspecialchars($mission['arrival'] ?? ''); ?></td>
                                        <td class="readonly">
                                            <?php
                                            $datetime = $mission['datetime'] ?? '';
                                            echo $datetime ? htmlspecialchars(date('d/m/Y H:i', strtotime($datetime))) : '';
                                            ?>
                                        </td>
                                        <td class="editable" data-field="driver"><?php echo htmlspecialchars($mission['driver'] ?? ''); ?></td>
                                        <td class="editable" data-field="vehicle"><?php echo htmlspecialchars($mission['vehicle'] ?? ''); ?></td>
                                        <td class="readonly">
                                            <?php
                                            $status = $mission['status_name'] ?? 'Non défini';
                                            $badge_class = 'badge-secondary';
                                            if ($status === 'Terminée') {
                                                $badge_class = 'badge-success';
                                            } elseif ($status === 'En cours') {
                                                $badge_class = 'badge-warning';
                                            } elseif ($status === 'À faire') {
                                                $badge_class = 'badge-info';
                                            } elseif ($status === 'Annulée') {
                                                $badge_class = 'badge-danger';
                                            }
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>">
                                                <?php echo htmlspecialchars($status); ?>
                                            </span>
                                        </td>
                                        <td class="readonly">
                                            <div class="actions-group">
                                                <a href="mission_view.php?id=<?php echo $mission['mission_id']; ?>" class="btn btn-sm btn-info" title="Voir"><i class="fas fa-eye"></i></a>
                                                <a href="mission_edit.php?id=<?php echo $mission['mission_id']; ?>" class="btn btn-sm btn-warning" title="Éditer"><i class="fas fa-edit"></i></a>
                                                <a href="mission_delete.php?id=<?php echo $mission['mission_id']; ?>" class="btn btn-sm btn-danger" title="Supprimer" onclick="return confirm('Supprimer cette mission ?');"><i class="fas fa-trash"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($missions)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center text-muted py-5">
                                            <i class="fas fa-inbox fa-3x mb-3" style="opacity: 0.3; display: block;"></i>
                                            <p>Aucune mission trouvée.</p>
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
    // Filtrer les missions
    function filterMissions() {
        const searchText = document.getElementById('search-mission').value.toLowerCase();
        const filterStatus = document.getElementById('filter-status').value;
        const filterFolder = document.getElementById('filter-folder').value;
        const filterDriver = document.getElementById('filter-driver').value;
        
        const rows = document.querySelectorAll('#missions-list tr');
        let visibleCount = 0;
        
        rows.forEach(row => {
            if (row.querySelector('td') === null) return; // Passer les lignes sans données
            
            const driver = row.cells[6]?.textContent.toLowerCase() || '';
            const vehicle = row.cells[7]?.textContent.toLowerCase() || '';
            const departure = row.cells[3]?.textContent.toLowerCase() || '';
            const status = row.cells[8]?.textContent.trim() || '';
            const folder = row.cells[2]?.textContent || '';
            
            let matchSearch = !searchText || driver.includes(searchText) || vehicle.includes(searchText) || departure.includes(searchText);
            let matchStatus = !filterStatus || status.includes(filterStatus);
            let matchFolder = !filterFolder || folder.includes(filterFolder);
            let matchDriver = !filterDriver || driver.includes(filterDriver.toLowerCase());
            
            if (matchSearch && matchStatus && matchFolder && matchDriver) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Mettre à jour le compteur
        const header = document.querySelector('.missions-table-header span');
        if (header) {
            header.textContent = visibleCount + ' mission' + (visibleCount !== 1 ? 's' : '');
        }
    }

    // Réinitialiser les filtres
    function resetFilters() {
        document.getElementById('search-mission').value = '';
        document.getElementById('filter-status').value = '';
        document.getElementById('filter-folder').value = '';
        document.getElementById('filter-driver').value = '';
        filterMissions();
    }

    // Ajouter les écouteurs d'événements
    document.getElementById('search-mission').addEventListener('keyup', filterMissions);
    document.getElementById('filter-status').addEventListener('change', filterMissions);
    document.getElementById('filter-folder').addEventListener('change', filterMissions);
    document.getElementById('filter-driver').addEventListener('change', filterMissions);
    document.getElementById('apply-filters').addEventListener('click', filterMissions);
    document.getElementById('reset-filters').addEventListener('click', resetFilters);

    // =============================================
    // ÉDITION INLINE - Missions
    // =============================================
    
    const editingIndicator = document.getElementById('editingIndicator');
    const saveIndicator = document.getElementById('saveIndicator');
    let editingCell = null;

    // Double-clic pour éditer
    document.querySelectorAll('tbody td.editable').forEach(cell => {
        cell.addEventListener('dblclick', function() {
            if (editingCell) return; // Une cellule est déjà en édition
            
            editingCell = this;
            const currentValue = this.textContent.trim();
            const field = this.dataset.field;
            
            // Marquer comme en édition
            this.classList.add('editing');
            
            // Afficher l'indicateur
            editingIndicator.classList.add('show');
            
            // Créer l'input
            const input = document.createElement('input');
            input.type = 'text';
            input.value = currentValue;
            input.className = 'form-control';
            
            this.innerHTML = '';
            this.appendChild(input);
            input.focus();
            input.select();
            
            // Sauvegarder sur blur ou Enter
            const saveEdit = async () => {
                const newValue = input.value.trim();
                const row = this.closest('tr');
                const missionId = row.dataset.missionId;
                
                // Retirer l'indicateur d'édition
                editingIndicator.classList.remove('show');
                this.classList.remove('editing');
                
                if (newValue !== currentValue) {
                    // Sauvegarder via AJAX
                    try {
                        const formData = new FormData();
                        formData.append('mission_id', missionId);
                        formData.append('field', field);
                        formData.append('value', newValue);
                        
                        const response = await fetch('api/update-mission-inline.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            this.textContent = newValue;
                            
                            // Afficher l'indicateur de sauvegarde
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