<?php
include 'includes/opportunities.php';

// Récupération des stages dynamiques depuis le système pipeline
require_once __DIR__ . '/includes/pipeline_utils.php';
pipeline_ensure_schema($pdo);
$boardId = pipeline_get_board_id($pdo, 'opportunity');
$stagesStmt = $pdo->prepare("SELECT id, name, slug, color_code, order_position, probability_default
    FROM pipeline_stages
    WHERE board_id = :board AND is_active = 1
    ORDER BY order_position, id");
$stagesStmt->execute([':board' => $boardId]);
$pipelineStages = $stagesStmt->fetchAll(PDO::FETCH_ASSOC);
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
    <style>
        .pipeline-stage {
            min-height: 600px;
            background-color: #f8f9fc;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .opportunity-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            cursor: move;
            transition: all 0.3s;
        }
        .opportunity-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        .stage-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .amount-badge {
            font-size: 0.9em;
            font-weight: bold;
        }
        .opportunities-container {
            min-height: 500px;
        }
        .drop-target {
            background-color: rgba(13, 110, 253, 0.1);
            border: 2px dashed #0d6efd;
        }
        .opportunity-card {
            cursor: grab;
        }
        .opportunity-card:active {
            cursor: grabbing;
        }
        .opportunity-card.dragging {
            opacity: 0.5;
        }
        /* Responsive pour petits écrans */
        @media (max-width: 992px) {
            .row > div[class*="col"] {
                flex: 0 0 100%;
                max-width: 100%;
                margin-bottom: 20px;
            }
            .pipeline-stage {
                min-height: 300px;
            }
        }
        /* Ajustement pour les stages nombreux */
        @media (min-width: 993px) {
            .row > .col {
                flex: 1;
                min-width: 150px;
                max-width: 250px;
            }
        }
        .pipeline-column-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .amount-badge {
            font-size: 0.9em;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        <?php include 'includes/topbar.php'; ?>
        <div class="main-content">

            <div class="container-fluid">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-handshake text-primary"></i> Pipeline des Opportunités
                    </h1>
                    <div class="btn-group">
                        <a href="opportunities-export.php" class="btn btn-outline-primary">
                            <i class="fas fa-file-export"></i> Exporter
                        </a>
                        <a href="opportunities-add.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nouvelle Opportunité
                        </a>
                    </div>
                </div>

                <!-- KPIs Pipeline -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Valeur Pipeline
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="pipeline-value">
                                            €<?php echo number_format($pipeline_value, 2, ',', ' '); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-euro-sign fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Opportunités Actives
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="active-opportunities">
                                            <?php echo $active_opportunities; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-handshake fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Taux de Fermeture
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="close-rate">
                                            <?php echo $close_rate; ?>%
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Cycle Moyen (jours)
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="avg-cycle">
                                            <?php echo $avg_cycle; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pipeline Kanban -->
                <div class="row">
                    <?php 
                    $stageCount = count($pipelineStages);
                    // Calcul de la largeur de colonne en fonction du nombre de stages
                    $colClass = 'col-md-2'; // Par défaut 6 colonnes (12/2 = 6)
                    if ($stageCount <= 4) {
                        $colClass = 'col-md-3'; // 4 colonnes
                    } elseif ($stageCount == 5) {
                        $colClass = 'col'; // 5 colonnes égales
                    } elseif ($stageCount >= 7) {
                        $colClass = 'col'; // Colonnes flexibles
                    }
                    
                    foreach ($pipelineStages as $stage): 
                        $stageSlug = $stage['slug'];
                        $stageName = htmlspecialchars($stage['name']);
                        $stageColor = htmlspecialchars($stage['color_code'] ?? '#6c757d');
                        
                        // Styles spéciaux pour fermé gagné et fermé perdu
                        $bgStyle = '';
                        $headerStyle = "background: linear-gradient(135deg, {$stageColor} 0%, {$stageColor}99 100%);";
                        
                        if ($stageSlug === 'closed_won') {
                            $bgStyle = 'background-color: #d4edda;';
                        } elseif ($stageSlug === 'closed_lost') {
                            $bgStyle = 'background-color: #f8d7da;';
                        }
                        
                        // Afficher la valeur pré-calculée pour les stages fermés
                        $displayValue = '€0';
                        if ($stageSlug === 'closed_won') {
                            $displayValue = '€' . number_format($won_value, 2, ',', ' ');
                        } elseif ($stageSlug === 'closed_lost') {
                            $displayValue = '€' . number_format($lost_value, 2, ',', ' ');
                        }
                    ?>
                    <div class="<?php echo $colClass; ?>">
                        <div class="pipeline-stage" id="stage-<?php echo $stageSlug; ?>" style="<?php echo $bgStyle; ?>">
                            <div class="stage-header text-center" style="<?php echo $headerStyle; ?>">
                                <h6 class="mb-1"><?php echo $stageName; ?></h6>
                                <small id="<?php echo $stageSlug; ?>-count">0 opportunités</small>
                                <div class="mt-1">
                                    <small id="<?php echo $stageSlug; ?>-value"><?php echo $displayValue; ?></small>
                                </div>
                            </div>
                            <div class="opportunities-container" data-stage="<?php echo $stageSlug; ?>">
                                <!-- Opportunités chargées dynamiquement -->
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Tu peux ajouter ici ton JS pour charger dynamiquement les cartes d'opportunités -->

    <script>

        document.addEventListener('DOMContentLoaded', function() {
            const API_LIST = 'api/opportunities-list.php';
            const API_UPDATE = 'api/opportunity-update-stage.php';
            const POLL_INTERVAL = 10000; // ms
        
            function safeFetch(url, opts = {}) {
                opts.credentials = 'same-origin';
                if (!opts.headers) opts.headers = {};
                return fetch(url, opts).then(r => r.json());
            }
        
            function createCard(op) {
                const div = document.createElement('div');
                div.className = 'opportunity-card';
                div.draggable = true;
                div.dataset.id = op.id;
                div.innerHTML = `
                    <div class="d-flex justify-content-between">
                        <div><strong>${escapeHtml(op.title)}</strong><br><small class="text-muted">${escapeHtml(op.company_name || '')}</small></div>
                        <div class="text-end"><span class="amount-badge">€${Number(op.amount||0).toFixed(2)}</span></div>
                    </div>
                `;
                
                div.addEventListener('dragstart', e => {
                    e.dataTransfer.setData('text/plain', op.id.toString());
                    e.dataTransfer.effectAllowed = 'move';
                    setTimeout(() => div.classList.add('dragging'), 0);
                });
                
                div.addEventListener('dragend', e => {
                    div.classList.remove('dragging');
                });
                
                // Optional: double-click to view
                div.addEventListener('dblclick', e => {
                    window.location.href = `opportunities-edit.php?id=${op.id}`;
                });
                
                return div;
            }
        
            function escapeHtml(str) {
                if (!str) return '';
                return String(str).replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[s]));
            }
        
            function clearContainers() {
                document.querySelectorAll('.opportunities-container').forEach(c => c.innerHTML = '');
            }
        
            function render(data) {
                if (!data || !data.stages) return;
                clearContainers();
                // Attach drop handlers BEFORE adding cards
                attachDropHandlers();
                
                for (const stage in data.stages) {
                    const list = data.stages[stage];
                    const container = document.querySelector(`.opportunities-container[data-stage="${stage}"]`);
                    if (!container) continue;
                    list.forEach(op => container.appendChild(createCard(op)));
                    // update counts/values if present
                    const meta = data.meta && data.meta[stage] ? data.meta[stage] : {count: list.length, sum: list.reduce((s,i)=>s+Number(i.amount||0),0)};
                    const countEl = document.getElementById(`${stage}-count`);
                    const valueEl = document.getElementById(`${stage}-value`);
                    if (countEl) countEl.textContent = `${meta.count} opportunité${meta.count>1?'s':''}`;
                    if (valueEl) valueEl.textContent = `€${Number(meta.sum||0).toFixed(2)}`;
                }
            }
        
            let handlersAttached = false;
            function attachDropHandlers() {
                if (handlersAttached) return;
                handlersAttached = true;
                
                document.querySelectorAll('.opportunities-container').forEach(container => {
                    container.addEventListener('dragover', e => {
                        e.preventDefault();
                        e.dataTransfer.dropEffect = 'move';
                        container.classList.add('drop-target');
                    });
                    container.addEventListener('dragleave', e => {
                        // Remove class when leaving the container
                        if (!container.contains(e.relatedTarget)) {
                            container.classList.remove('drop-target');
                        }
                    });
                    container.addEventListener('drop', e => {
                        e.preventDefault();
                        container.classList.remove('drop-target');
                        const id = e.dataTransfer.getData('text/plain');
                        const stage = container.dataset.stage;
                        if (!id || !stage) {
                            console.warn('Missing id or stage:', {id, stage});
                            return;
                        }
                        
                        console.log('Dropping opportunity', id, 'into stage', stage);
                        
                        // Déplacement visuel immédiat (optimiste)
                        const card = document.querySelector(`.opportunity-card[data-id="${id}"]`);
                        if (card && card.parentElement !== container) {
                            container.appendChild(card);
                        }
                        
                        // Call API to update stage in database
                        safeFetch(API_UPDATE, {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({id: parseInt(id), stage: stage})
                        }).then(res => {
                            if (!res || !res.success) {
                                console.error('Failed to update stage:', res);
                                const errorMsg = res && res.message ? res.message : 'Erreur lors du déplacement de l\'opportunité';
                                alert(errorMsg);
                                // On error, reload to restore correct state
                                load();
                            } else {
                                console.log('Stage updated successfully in database');
                                // Optionally refresh to update counts/values
                                setTimeout(load, 500);
                            }
                        }).catch(err => {
                            console.error('API error:', err);
                            alert('Erreur réseau lors du déplacement: ' + err.message);
                            // On error, reload to restore correct state
                            load();
                        });
                    });
                });
            }
        
            let pollingTimer = null;
            function load() {
                safeFetch(API_LIST).then(res => {
                    if (res && res.success) render(res);
                }).catch(err => console.error('opps load error', err));
            }
        
            load();
            if (pollingTimer) clearInterval(pollingTimer);
            pollingTimer = setInterval(load, POLL_INTERVAL);
        
        });
    </script>
</body>
</html>