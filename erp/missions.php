<?php
include __DIR__ . '/includes/missions.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>window.initERPSync = true;</script>
    <script src="assets/js/sync.js" defer></script>
    <style>
        .kpi-grid{
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
            gap:20px;
            margin:24px 0;
        }
        .kpi-card{
            background:rgba(255,255,255,0.85);
            backdrop-filter:blur(20px);
            border:1px solid rgba(255,255,255,0.3);
            padding:24px;
            border-radius:16px;
            box-shadow:0 10px 40px rgba(16,24,40,0.08);
            transition:all 0.3s cubic-bezier(0.4,0,0.2,1);
            position:relative;
            overflow:hidden;
        }
        .kpi-card::before{
            content:'';
            position:absolute;
            top:0;left:0;right:0;
            height:4px;
            background:linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
        }
        .kpi-card:hover{
            transform:translateY(-4px);
            box-shadow:0 20px 60px rgba(16,24,40,0.12);
        }
        .kpi-card.success::before{background:linear-gradient(135deg, #10b981 0%, #059669 100%)}
        .kpi-card.warning::before{background:linear-gradient(135deg, #f59e0b 0%, #d97706 100%)}
        .kpi-card.info::before{background:linear-gradient(135deg, #06b6d4 0%, #0891b2 100%)}
        .kpi-icon{
            width:48px;
            height:48px;
            border-radius:12px;
            background:linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:24px;
            color:#fff;
            margin-bottom:16px;
        }
        .kpi-card.success .kpi-icon{background:linear-gradient(135deg, #10b981 0%, #059669 100%)}
        .kpi-card.warning .kpi-icon{background:linear-gradient(135deg, #f59e0b 0%, #d97706 100%)}
        .kpi-card.info .kpi-icon{background:linear-gradient(135deg, #06b6d4 0%, #0891b2 100%)}
        .kpi-value{font-size:36px;font-weight:800;color:#0f172a;margin:8px 0}
        .kpi-label{font-size:13px;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;font-weight:600}
        
        .filters-section{
            background:rgba(255,255,255,0.85);
            backdrop-filter:blur(20px);
            padding:24px;
            border-radius:16px;
            margin:24px 0;
            box-shadow:0 10px 40px rgba(16,24,40,0.08);
        }
        .filters-title{
            font-size:16px;
            font-weight:700;
            color:#0f172a;
            margin-bottom:16px;
            display:flex;
            align-items:center;
            gap:8px;
        }
        .filter-row{
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
            gap:16px;
            margin-bottom:16px;
        }
        .filter-group label{
            display:block;
            font-size:13px;
            font-weight:600;
            color:#64748b;
            margin-bottom:8px;
        }
        .filter-group input,
        .filter-group select{
            width:100%;
            padding:12px;
            border:2px solid #e2e8f0;
            border-radius:12px;
            font-size:14px;
            transition:all 0.3s;
        }
        .filter-group input:focus,
        .filter-group select:focus{
            outline:none;
            border-color:#3b82f6;
            box-shadow:0 0 0 3px rgba(59,130,246,0.1);
        }
        
        .missions-table-container{
            background:rgba(255,255,255,0.85);
            backdrop-filter:blur(20px);
            border-radius:16px;
            padding:24px;
            box-shadow:0 10px 40px rgba(16,24,40,0.08);
            margin:24px 0;
        }
        .missions-table-header{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:20px;
        }
        .missions-table-header h5{
            font-size:18px;
            font-weight:700;
            color:#0f172a;
            margin:0;
        }
        .badge{
            display:inline-block;
            padding:6px 12px;
            border-radius:8px;
            font-size:12px;
            font-weight:600;
        }
        .badge-primary{background:#dbeafe;color:#1e40af}
        .badge-success{background:#d1fae5;color:#065f46}
        .badge-warning{background:#fef3c7;color:#92400e}
        .badge-info{background:#cffafe;color:#155e75}
        .badge-danger{background:#fee2e2;color:#991b1b}
        
        .tabs{
            display:flex;
            gap:8px;
            margin-bottom:24px;
            border-bottom:2px solid #e2e8f0;
        }
        .tab{
            padding:12px 24px;
            border:none;
            background:transparent;
            font-size:14px;
            font-weight:600;
            color:#64748b;
            cursor:pointer;
            position:relative;
            transition:all 0.3s;
        }
        .tab.active{
            color:#3b82f6;
        }
        .tab.active::after{
            content:'';
            position:absolute;
            bottom:-2px;
            left:0;
            right:0;
            height:2px;
            background:linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
        }
        .tab:hover{color:#3b82f6}
        
        .tab-content{display:none}
        .tab-content.active{display:block}
        
        .action-btns{display:flex;gap:8px}
        .btn-sm{
            padding:8px 12px;
            font-size:13px;
            border-radius:8px;
        }
        .btn-info{background:#06b6d4;color:#fff}
        .btn-warning{background:#f59e0b;color:#fff}
        .btn-danger{background:#ef4444;color:#fff}
        
        @media (max-width:768px){
            .kpi-grid{grid-template-columns:1fr}
            .filter-row{grid-template-columns:1fr}
        }
    </style>
</head>
<body>
    <?php include 'erp_nav.php'; ?>
    
    <main class="main-content fade-in">
        <h1><i class="fas fa-tasks"></i> Missions & Projets</h1>
        
        <!-- KPIs -->
        <div class="kpi-grid">
            <div class="kpi-card info">
                <div class="kpi-icon"><i class="fas fa-list-check"></i></div>
                <div class="kpi-value"><?php echo $total_missions; ?></div>
                <p class="kpi-label">Total Missions</p>
            </div>
            
            <div class="kpi-card warning">
                <div class="kpi-icon"><i class="fas fa-spinner"></i></div>
                <div class="kpi-value"><?php echo $missions_in_progress; ?></div>
                <p class="kpi-label">En Cours / À Faire</p>
            </div>
            
            <div class="kpi-card success">
                <div class="kpi-icon"><i class="fas fa-check-circle"></i></div>
                <div class="kpi-value"><?php echo $missions_completed; ?></div>
                <p class="kpi-label">Terminées</p>
            </div>
            
            <div class="kpi-card">
                <div class="kpi-icon"><i class="fas fa-chart-pie"></i></div>
                <div class="kpi-value"><?php echo $completion_rate; ?>%</div>
                <p class="kpi-label">Taux de Complétion</p>
            </div>
        </div>
        
        <!-- Onglets -->
        <div class="tabs">
            <button class="tab active" data-tab="missions-tab">
                <i class="fas fa-tasks"></i> Missions CRM
            </button>
            <button class="tab" data-tab="shifts-tab">
                <i class="fas fa-calendar-alt"></i> Planning ERP
            </button>
        </div>
        
        <!-- Contenu Missions -->
        <div id="missions-tab" class="tab-content active">
            <!-- Filtres -->
            <div class="filters-section">
                <h6 class="filters-title">
                    <i class="fas fa-filter"></i> Filtres et Recherche
                </h6>
                
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="search-mission">Rechercher</label>
                        <input type="text" id="search-mission" placeholder="Départ, arrivée, chauffeur...">
                    </div>
                    
                    <div class="filter-group">
                        <label for="filter-status">Statut</label>
                        <select id="filter-status">
                            <option value="">Tous les statuts</option>
                            <option value="À faire">À faire</option>
                            <option value="En cours">En cours</option>
                            <option value="Terminée">Terminée</option>
                            <option value="Annulée">Annulée</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="filter-company">Société</label>
                        <select id="filter-company">
                            <option value="">Toutes les sociétés</option>
                            <?php foreach ($companies as $company): ?>
                                <option value="<?php echo htmlspecialchars($company['name']); ?>">
                                    <?php echo htmlspecialchars($company['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="filter-date">Date</label>
                        <input type="date" id="filter-date">
                    </div>
                </div>
                
                <div style="display:flex;gap:12px">
                    <button class="btn btn-primary" onclick="applyFilters()">
                        <i class="fas fa-search"></i> Appliquer
                    </button>
                    <button class="btn btn-ghost" onclick="resetFilters()">
                        <i class="fas fa-undo"></i> Réinitialiser
                    </button>
                    <button class="btn btn-success" style="margin-left:auto" onclick="window.location.href='../crm/mission_add.php'">
                        <i class="fas fa-plus"></i> Nouvelle Mission
                    </button>
                </div>
            </div>
            
            <!-- Tableau Missions -->
            <div class="missions-table-container">
                <div class="missions-table-header">
                    <h5>Liste des Missions</h5>
                    <span class="badge badge-primary"><?php echo $total_missions; ?> missions</span>
                </div>
                
                <div class="table-responsive">
                    <table class="table-compact" id="missionsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Société</th>
                                <th>Dossier</th>
                                <th>Départ</th>
                                <th>Arrivée</th>
                                <th>Date/Heure</th>
                                <th>Chauffeur</th>
                                <th>Véhicule</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($missions)): ?>
                                <tr>
                                    <td colspan="10" style="text-align:center;padding:40px;color:#64748b">
                                        <i class="fas fa-inbox" style="font-size:48px;margin-bottom:16px;opacity:0.3"></i>
                                        <p>Aucune mission trouvée</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($missions as $mission): ?>
                                <tr>
                                    <td><strong>M-<?php echo htmlspecialchars($mission['mission_id']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($mission['company_name']); ?></td>
                                    <td><?php echo htmlspecialchars($mission['folder_name']); ?></td>
                                    <td><?php echo htmlspecialchars($mission['departure'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($mission['arrival'] ?? '-'); ?></td>
                                    <td>
                                        <?php 
                                        echo $mission['datetime'] 
                                            ? date('d/m/Y H:i', strtotime($mission['datetime'])) 
                                            : '-'; 
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($mission['driver'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($mission['vehicle'] ?? '-'); ?></td>
                                    <td>
                                        <?php
                                        $status = $mission['status_name'] ?? 'Non défini';
                                        $badge_class = 'badge-secondary';
                                        if ($status === 'Terminée') $badge_class = 'badge-success';
                                        elseif ($status === 'En cours') $badge_class = 'badge-warning';
                                        elseif ($status === 'À faire') $badge_class = 'badge-info';
                                        elseif ($status === 'Annulée') $badge_class = 'badge-danger';
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <?php echo htmlspecialchars($status); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <a href="../crm/mission_view.php?id=<?php echo $mission['mission_id']; ?>" 
                                               class="btn btn-sm btn-info" title="Voir">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="../crm/mission_edit.php?id=<?php echo $mission['mission_id']; ?>" 
                                               class="btn btn-sm btn-warning" title="Éditer">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Contenu Planning -->
        <div id="shifts-tab" class="tab-content">
            <div class="missions-table-container">
                <div class="missions-table-header">
                    <h5>Planning des Employés (30 derniers jours)</h5>
                    <span class="badge badge-primary"><?php echo count($shifts); ?> créneaux</span>
                </div>
                
                <div class="table-responsive">
                    <table class="table-compact">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Employé</th>
                                <th>Société</th>
                                <th>Début</th>
                                <th>Fin</th>
                                <th>Durée</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($shifts)): ?>
                                <tr>
                                    <td colspan="8" style="text-align:center;padding:40px;color:#64748b">
                                        <i class="fas fa-calendar" style="font-size:48px;margin-bottom:16px;opacity:0.3"></i>
                                        <p>Aucun créneau trouvé</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($shifts as $shift): ?>
                                <?php
                                $start = new DateTime($shift['start_datetime']);
                                $end = new DateTime($shift['end_datetime']);
                                $duration = $start->diff($end);
                                $hours = $duration->h + ($duration->days * 24);
                                ?>
                                <tr>
                                    <td><strong>#<?php echo $shift['id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($shift['employee_name'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($shift['company_name'] ?? '-'); ?></td>
                                    <td><?php echo $start->format('d/m/Y H:i'); ?></td>
                                    <td><?php echo $end->format('d/m/Y H:i'); ?></td>
                                    <td><?php echo $hours; ?>h <?php echo $duration->i; ?>min</td>
                                    <td><?php echo htmlspecialchars($shift['notes'] ?? '-'); ?></td>
                                    <td>
                                        <a href="shifts.php?edit=<?php echo $shift['id']; ?>" 
                                           class="btn btn-sm btn-warning" title="Éditer">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div style="margin-top:20px;text-align:center">
                    <a href="shifts.php" class="btn btn-primary">
                        <i class="fas fa-calendar-alt"></i> Voir le planning complet
                    </a>
                </div>
            </div>
        </div>
    </main>
    
    <script>
    // Gestion des onglets
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', function() {
            // Retirer active de tous
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            // Activer le bon
            this.classList.add('active');
            document.getElementById(this.dataset.tab).classList.add('active');
        });
    });
    
    // Filtres
    function applyFilters() {
        const search = document.getElementById('search-mission').value.toLowerCase();
        const status = document.getElementById('filter-status').value;
        const company = document.getElementById('filter-company').value;
        const date = document.getElementById('filter-date').value;
        
        const rows = document.querySelectorAll('#missionsTable tbody tr');
        
        rows.forEach(row => {
            if (row.cells.length === 1) return; // Skip empty row
            
            const text = row.textContent.toLowerCase();
            const rowStatus = row.cells[8].textContent.trim();
            const rowCompany = row.cells[1].textContent.trim();
            const rowDate = row.cells[5].textContent;
            
            let show = true;
            
            if (search && !text.includes(search)) show = false;
            if (status && rowStatus !== status) show = false;
            if (company && rowCompany !== company) show = false;
            if (date && !rowDate.includes(date.split('-').reverse().join('/'))) show = false;
            
            row.style.display = show ? '' : 'none';
        });
    }
    
    function resetFilters() {
        document.getElementById('search-mission').value = '';
        document.getElementById('filter-status').value = '';
        document.getElementById('filter-company').value = '';
        document.getElementById('filter-date').value = '';
        applyFilters();
    }
    </script>
</body>
</html>
