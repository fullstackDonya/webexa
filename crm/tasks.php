<?php
require_once 'includes/verify_subscriptions.php';
require_once __DIR__ . '/config/database.php';

$page_title = "Gestion des Tâches - CRM";
$customer_id = $_SESSION['customer_id'] ?? 22;

// Listes pour le select "Associer à" du modal
$modal_leads     = [];
$modal_companies = [];
try {
    $s = $pdo->prepare("SELECT id, CONCAT(first_name,' ',last_name) AS label FROM leads WHERE customer_id = ? ORDER BY first_name LIMIT 300");
    $s->execute([$customer_id]);
    $modal_leads = $s->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}
try {
    $s = $pdo->prepare("SELECT id, name AS label FROM companies WHERE customer_id = ? ORDER BY name LIMIT 300");
    $s->execute([$customer_id]);
    $modal_companies = $s->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}
$from_lead = isset($_GET['from_lead']) ? intval($_GET['from_lead']) : 0;

// Récupérer les statistiques
$statsQuery = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'todo' THEN 1 ELSE 0 END) as todo,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN due_date < NOW() AND status NOT IN ('completed', 'cancelled') THEN 1 ELSE 0 END) as overdue,
        SUM(CASE WHEN DATE(due_date) = CURDATE() AND status NOT IN ('completed', 'cancelled') THEN 1 ELSE 0 END) as due_today,
        SUM(CASE WHEN priority = 'urgent' AND status NOT IN ('completed', 'cancelled') THEN 1 ELSE 0 END) as urgent
    FROM tasks
    WHERE customer_id = ?
");
$statsQuery->execute([$customer_id]);
$stats = $statsQuery->fetch(PDO::FETCH_ASSOC);

// Filtres
$filter = $_GET['filter'] ?? 'active';
$search = $_GET['search'] ?? '';

// Construire la requête
$whereConditions = ["customer_id = ?"];
$params = [$customer_id];

if ($filter === 'active') {
    $whereConditions[] = "status NOT IN ('completed', 'cancelled')";
} elseif ($filter === 'todo') {
    $whereConditions[] = "status = 'todo'";
} elseif ($filter === 'in_progress') {
    $whereConditions[] = "status = 'in_progress'";
} elseif ($filter === 'completed') {
    $whereConditions[] = "status = 'completed'";
} elseif ($filter === 'overdue') {
    $whereConditions[] = "due_date < NOW() AND status NOT IN ('completed', 'cancelled')";
} elseif ($filter === 'today') {
    $whereConditions[] = "DATE(due_date) = CURDATE() AND status NOT IN ('completed', 'cancelled')";
} elseif ($filter === 'urgent') {
    $whereConditions[] = "priority = 'urgent' AND status NOT IN ('completed', 'cancelled')";
}

if ($search) {
    $whereConditions[] = "(title LIKE ? OR description LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$sql = "SELECT * FROM tasks WHERE " . implode(' AND ', $whereConditions) . " ORDER BY 
    CASE WHEN status = 'completed' THEN 1 ELSE 0 END,
    CASE priority 
        WHEN 'urgent' THEN 1 
        WHEN 'high' THEN 2 
        WHEN 'medium' THEN 3 
        ELSE 4 
    END,
    due_date ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        .tasks-page {
            background: #f8f9fc;
            min-height: 100vh;
            padding: 2rem 0;
        }
        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        .stat-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-radius: 8px;
            background: #f8f9fc;
        }
        .stat-item i {
            font-size: 2rem;
            width: 50px;
            text-align: center;
        }
        .stat-item .stat-value {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1;
        }
        .stat-item .stat-label {
            font-size: 0.875rem;
            color: #6c757d;
        }
        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        .filter-tab {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            background: white;
            border: 2px solid #e9ecef;
            color: #6c757d;
            text-decoration: none;
            transition: all 0.2s;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .filter-tab:hover {
            border-color: #4e73df;
            color: #4e73df;
        }
        .filter-tab.active {
            background: #4e73df;
            border-color: #4e73df;
            color: white;
        }
        .tasks-table-wrapper {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .tasks-table {
            width: 100%;
            margin: 0;
        }
        .tasks-table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .tasks-table th {
            padding: 1rem;
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
        }
        .tasks-table tbody tr {
            border-bottom: 1px solid #e9ecef;
            transition: background 0.2s;
        }
        .tasks-table tbody tr:hover {
            background: #f8f9fc;
        }
        .tasks-table tbody tr.completed {
            opacity: 0.6;
        }
        .tasks-table td {
            padding: 1rem;
            vertical-align: middle;
        }
        .tasks-table td[contenteditable="true"]:hover {
            background: #fff9e6;
            cursor: text;
        }
        .priority-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .priority-urgent { background: #dc3545; color: white; }
        .priority-high { background: #fd7e14; color: white; }
        .priority-medium { background: #ffc107; color: #000; }
        .priority-low { background: #6c757d; color: white; }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-todo { background: #e9ecef; color: #495057; }
        .status-in_progress { background: #0dcaf0; color: white; }
        .status-completed { background: #198754; color: white; }
        .status-cancelled { background: #6c757d; color: white; }
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        .btn-icon {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
        }
        .overdue-indicator {
            color: #dc3545;
            font-weight: 600;
        }
        .due-today-indicator {
            color: #fd7e14;
            font-weight: 600;
        }
        .task-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
    </style>
</head>
<body>



    <div class="d-flex wrapper">
        <?php include 'includes/sidebar.php'; ?>
        <?php include 'includes/topbar.php'; ?>
        
        <div class="flex-grow-1 main-content">
            <?php include 'includes/navbar.php'; ?>
            
            <div class="container-fluid tasks-page">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="fas fa-tasks text-primary"></i> Gestion des Tâches</h2>
                        <p class="text-muted mb-0">Organisez et suivez vos tâches quotidiennes</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#taskModal" onclick="openTaskModal()">
                        <i class="fas fa-plus"></i> Nouvelle Tâche
                    </button>
                </div>

                <!-- Statistiques -->
                <div class="stats-card">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="stat-item">
                                <i class="fas fa-list text-primary"></i>
                                <div>
                                    <div class="stat-value"><?php echo $stats['total']; ?></div>
                                    <div class="stat-label">Total Tâches</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-item">
                                <i class="fas fa-circle-notch text-info"></i>
                                <div>
                                    <div class="stat-value"><?php echo $stats['in_progress']; ?></div>
                                    <div class="stat-label">En Cours</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-item">
                                <i class="fas fa-exclamation-triangle text-danger"></i>
                                <div>
                                    <div class="stat-value"><?php echo $stats['overdue']; ?></div>
                                    <div class="stat-label">En Retard</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-item">
                                <i class="fas fa-calendar-day text-warning"></i>
                                <div>
                                    <div class="stat-value"><?php echo $stats['due_today']; ?></div>
                                    <div class="stat-label">Aujourd'hui</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="filter-tabs">
                        <a href="?filter=active" class="filter-tab <?php echo $filter === 'active' ? 'active' : ''; ?>">
                            <i class="fas fa-circle-notch"></i> Actives (<?php echo $stats['todo'] + $stats['in_progress']; ?>)
                        </a>
                        <a href="?filter=todo" class="filter-tab <?php echo $filter === 'todo' ? 'active' : ''; ?>">
                            <i class="fas fa-circle"></i> À faire (<?php echo $stats['todo']; ?>)
                        </a>
                        <a href="?filter=in_progress" class="filter-tab <?php echo $filter === 'in_progress' ? 'active' : ''; ?>">
                            <i class="fas fa-spinner"></i> En cours (<?php echo $stats['in_progress']; ?>)
                        </a>
                        <a href="?filter=overdue" class="filter-tab <?php echo $filter === 'overdue' ? 'active' : ''; ?>">
                            <i class="fas fa-exclamation-triangle"></i> En retard (<?php echo $stats['overdue']; ?>)
                        </a>
                        <a href="?filter=today" class="filter-tab <?php echo $filter === 'today' ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-day"></i> Aujourd'hui (<?php echo $stats['due_today']; ?>)
                        </a>
                        <a href="?filter=completed" class="filter-tab <?php echo $filter === 'completed' ? 'active' : ''; ?>">
                            <i class="fas fa-check-circle"></i> Terminées (<?php echo $stats['completed']; ?>)
                        </a>
                    </div>
                    
                    <form method="GET" class="d-flex" style="max-width: 300px;">
                        <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                        <input type="search" name="search" class="form-control" placeholder="Rechercher..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-outline-primary ms-2">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>

                <!-- Tableau des tâches -->
                <div class="tasks-table-wrapper">
                    <table class="tasks-table">
                        <thead>
                            <tr>
                                <th style="width: 40px;"></th>
                                <th>Tâche</th>
                                <th style="width: 200px;">Échéance</th>
                                <th style="width: 120px;">Priorité</th>
                                <th style="width: 120px;">Statut</th>
                                <th style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($tasks)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                        Aucune tâche trouvée
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($tasks as $task): 
                                    $isOverdue = strtotime($task['due_date']) < time() && !in_array($task['status'], ['completed', 'cancelled']);
                                    $isDueToday = date('Y-m-d', strtotime($task['due_date'])) === date('Y-m-d') && !in_array($task['status'], ['completed', 'cancelled']);
                                    $isCompleted = $task['status'] === 'completed';
                                ?>
                                    <tr class="<?php echo $isCompleted ? 'completed' : ''; ?>" data-task-id="<?php echo $task['id']; ?>">
                                        <td class="text-center">
                                            <input type="checkbox" class="task-checkbox" 
                                                   <?php echo $isCompleted ? 'checked' : ''; ?>
                                                   onchange="toggleTaskComplete(<?php echo $task['id']; ?>, this.checked)">
                                        </td>
                                        <td>
                                            <div>
                                                <strong contenteditable="true" 
                                                        data-field="title" 
                                                        data-task-id="<?php echo $task['id']; ?>"
                                                        onblur="saveFieldInline(this)"
                                                        class="<?php echo $isCompleted ? 'text-decoration-line-through' : ''; ?>">
                                                    <?php echo htmlspecialchars($task['title']); ?>
                                                </strong>
                                                <?php if ($task['description']): ?>
                                                    <div class="text-muted small mt-1" 
                                                         contenteditable="true"
                                                         data-field="description"
                                                         data-task-id="<?php echo $task['id']; ?>"
                                                         onblur="saveFieldInline(this)">
                                                        <?php echo htmlspecialchars($task['description']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($task['due_date']): ?>
                                                <span class="<?php echo $isOverdue ? 'overdue-indicator' : ($isDueToday ? 'due-today-indicator' : ''); ?>">
                                                    <i class="fas fa-calendar"></i>
                                                    <?php echo date('d/m/Y H:i', strtotime($task['due_date'])); ?>
                                                </span>
                                                <?php if ($isOverdue): ?>
                                                    <span class="badge bg-danger ms-2">RETARD</span>
                                                <?php elseif ($isDueToday): ?>
                                                    <span class="badge bg-warning text-dark ms-2">AUJOURD'HUI</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm priority-badge priority-<?php echo $task['priority']; ?>"
                                                    onchange="updateTaskField(<?php echo $task['id']; ?>, 'priority', this.value)">
                                                <option value="low" <?php echo $task['priority'] === 'low' ? 'selected' : ''; ?>>Faible</option>
                                                <option value="medium" <?php echo $task['priority'] === 'medium' ? 'selected' : ''; ?>>Moyenne</option>
                                                <option value="high" <?php echo $task['priority'] === 'high' ? 'selected' : ''; ?>>Haute</option>
                                                <option value="urgent" <?php echo $task['priority'] === 'urgent' ? 'selected' : ''; ?>>Urgente</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm status-badge status-<?php echo $task['status']; ?>"
                                                    onchange="updateTaskField(<?php echo $task['id']; ?>, 'status', this.value)">
                                                <option value="todo" <?php echo $task['status'] === 'todo' ? 'selected' : ''; ?>>À faire</option>
                                                <option value="in_progress" <?php echo $task['status'] === 'in_progress' ? 'selected' : ''; ?>>En cours</option>
                                                <option value="completed" <?php echo $task['status'] === 'completed' ? 'selected' : ''; ?>>Terminée</option>
                                                <option value="cancelled" <?php echo $task['status'] === 'cancelled' ? 'selected' : ''; ?>>Annulée</option>
                                            </select>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-sm btn-outline-primary btn-icon" 
                                                        onclick="editTask(<?php echo $task['id']; ?>)" 
                                                        title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger btn-icon" 
                                                        onclick="deleteTask(<?php echo $task['id']; ?>)" 
                                                        title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
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
    </div>

    <!-- Modal pour créer/éditer une tâche -->
    <div class="modal fade" id="taskModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="taskModalTitle">Nouvelle Tâche</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="taskForm">
                    <div class="modal-body">
                        <input type="hidden" id="task_id" name="id">
                        
                        <div class="mb-3">
                            <label class="form-label">Titre *</label>
                            <input type="text" class="form-control" id="task_title" name="title" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" id="task_description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Échéance</label>
                                <input type="datetime-local" class="form-control" id="task_due_date" name="due_date">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Priorité</label>
                                <select class="form-select" id="task_priority" name="priority">
                                    <option value="low">Faible</option>
                                    <option value="medium" selected>Moyenne</option>
                                    <option value="high">Haute</option>
                                    <option value="urgent">Urgente</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Statut</label>
                            <select class="form-select" id="task_status" name="status">
                                <option value="todo" selected>À faire</option>
                                <option value="in_progress">En cours</option>
                                <option value="completed">Terminée</option>
                                <option value="cancelled">Annulée</option>
                            </select>
                        </div>

                        <!-- Associer à un lead ou un client -->
                        <div class="mb-3">
                            <label class="form-label">Associer à</label>
                            <div class="row g-2">
                                <div class="col-5">
                                    <select class="form-select" id="task_related_type">
                                        <option value="">-- Aucun --</option>
                                        <option value="lead">Lead</option>
                                        <option value="company">Client</option>
                                    </select>
                                </div>
                                <div class="col-7">
                                    <select class="form-select" id="task_related_id" disabled>
                                        <option value="">-- Sélectionner --</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
    // Données pour les selects du modal (injectées depuis PHP)
    const LEADS_DATA     = <?php echo json_encode($modal_leads, JSON_UNESCAPED_UNICODE); ?>;
    const COMPANIES_DATA = <?php echo json_encode($modal_companies, JSON_UNESCAPED_UNICODE); ?>;

    // Peupler le select related_id selon le type choisi
    document.getElementById('task_related_type').addEventListener('change', function () {
        const relatedId = document.getElementById('task_related_id');
        relatedId.innerHTML = '<option value="">-- Sélectionner --</option>';
        const list = this.value === 'lead' ? LEADS_DATA : this.value === 'company' ? COMPANIES_DATA : [];
        list.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = item.label;
            relatedId.appendChild(opt);
        });
        relatedId.disabled = (list.length === 0);
    });

    // Sauvegarder un champ inline
    function saveFieldInline(element) {
        const taskId = element.dataset.taskId;
        const field = element.dataset.field;
        const value = element.textContent.trim();
        
        updateTaskField(taskId, field, value);
    }
    
    // Mettre à jour un champ de tâche
    function updateTaskField(taskId, field, value) {
        fetch('api/tasks.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'update_field',
                id: taskId,
                field: field,
                value: value
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Réactualiser les badges si nécessaire
                if (field === 'status') {
                    location.reload();
                }
            } else {
                alert('Erreur: ' + data.message);
                location.reload();
            }
        })
        .catch(e => {
            console.error('Erreur:', e);
            alert('Erreur de mise à jour');
        });
    }
    
    // Toggle completion status
    function toggleTaskComplete(taskId, isCompleted) {
        const newStatus = isCompleted ? 'completed' : 'todo';
        updateTaskField(taskId, 'status', newStatus);
    }
    
    // Ouvrir le modal pour une nouvelle tâche
    function openTaskModal() {
        document.getElementById('taskModalTitle').textContent = 'Nouvelle Tâche';
        document.getElementById('taskForm').reset();
        document.getElementById('task_id').value = '';
        document.getElementById('task_related_type').value = '';
        document.getElementById('task_related_id').innerHTML = '<option value="">-- Sélectionner --</option>';
        document.getElementById('task_related_id').disabled = true;
    }
    
    // Éditer une tâche
    function editTask(taskId) {
        fetch(`api/tasks.php?action=get&id=${taskId}`)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const task = data.task;
                    document.getElementById('taskModalTitle').textContent = 'Modifier la Tâche';
                    document.getElementById('task_id').value = task.id;
                    document.getElementById('task_title').value = task.title;
                    document.getElementById('task_description').value = task.description || '';
                    document.getElementById('task_priority').value = task.priority;
                    document.getElementById('task_status').value = task.status;

                    // Remplir le champ Associer à
                    const relType = document.getElementById('task_related_type');
                    const relId   = document.getElementById('task_related_id');
                    relType.value = task.related_type || '';
                    relType.dispatchEvent(new Event('change'));
                    if (task.related_id) { relId.value = task.related_id; }
                    
                    if (task.due_date) {
                        const date = new Date(task.due_date);
                        const formattedDate = date.toISOString().slice(0, 16);
                        document.getElementById('task_due_date').value = formattedDate;
                    }
                    
                    new bootstrap.Modal(document.getElementById('taskModal')).show();
                }
            });
    }
    
    // Supprimer une tâche
    function deleteTask(taskId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette tâche ?')) return;
        
        fetch('api/tasks.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'delete',
                id: taskId
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        });
    }
    
    // Soumettre le formulaire
    document.getElementById('taskForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = {
            action: document.getElementById('task_id').value ? 'update' : 'create'
        };
        
        formData.forEach((value, key) => {
            data[key] = value;
        });
        data['related_type'] = document.getElementById('task_related_type').value || null;
        data['related_id']   = document.getElementById('task_related_id').value   || null;
        
        fetch('api/tasks.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(result => {
            if (result.success) {
                location.reload();
            } else {
                alert('Erreur: ' + result.message);
            }
        })
        .catch(e => {
            console.error('Erreur:', e);
            alert('Erreur lors de l\'enregistrement');
        });
    });
    // Auto-ouvrir le modal si redirigé depuis leads.php ou customers.php
    const _urlP        = new URLSearchParams(window.location.search);
    const _fromLead    = _urlP.get('from_lead');
    const _fromCompany = _urlP.get('from_company');
    const _autoFrom    = _fromLead || _fromCompany;
    if (_autoFrom) {
        openTaskModal();
        const rt = document.getElementById('task_related_type');
        rt.value = _fromLead ? 'lead' : 'company';
        rt.dispatchEvent(new Event('change'));
        setTimeout(() => { document.getElementById('task_related_id').value = _autoFrom; }, 60);
        new bootstrap.Modal(document.getElementById('taskModal')).show();
    }
    </script>
</body>
</html>
