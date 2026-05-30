<?php
require_once 'includes/verify_subscriptions.php';
require_once __DIR__ . '/config/database.php';

$page_title = "Rappels d'Appels - CRM";
$customer_id = $_SESSION['customer_id'] ?? 22;

// Récupérer les statistiques
$statsQuery = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'missed' THEN 1 ELSE 0 END) as missed,
        SUM(CASE WHEN DATE(scheduled_time) = CURDATE() AND status = 'pending' THEN 1 ELSE 0 END) as today,
        SUM(CASE WHEN scheduled_time < NOW() AND status = 'pending' THEN 1 ELSE 0 END) as overdue
    FROM call_reminders
    WHERE customer_id = ?
");
$statsQuery->execute([$customer_id]);
$stats = $statsQuery->fetch(PDO::FETCH_ASSOC);

// Filtres
$filter = $_GET['filter'] ?? 'pending';
$search = $_GET['search'] ?? '';

// Construire la requête
$whereConditions = ["customer_id = ?"];
$params = [$customer_id];

if ($filter === 'pending') {
    $whereConditions[] = "status = 'pending'";
} elseif ($filter === 'completed') {
    $whereConditions[] = "status = 'completed'";
} elseif ($filter === 'today') {
    $whereConditions[] = "DATE(scheduled_time) = CURDATE() AND status = 'pending'";
} elseif ($filter === 'overdue') {
    $whereConditions[] = "scheduled_time < NOW() AND status = 'pending'";
} elseif ($filter === 'missed') {
    $whereConditions[] = "status = 'missed'";
}

if ($search) {
    $whereConditions[] = "(contact_name LIKE ? OR phone LIKE ? OR notes LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$sql = "SELECT * FROM call_reminders WHERE " . implode(' AND ', $whereConditions) . " ORDER BY scheduled_time ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$calls = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        .calls-page {
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
        .calls-table-wrapper {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .calls-table {
            width: 100%;
            margin: 0;
        }
        .calls-table thead {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }
        .calls-table th {
            padding: 1rem;
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
        }
        .calls-table tbody tr {
            border-bottom: 1px solid #e9ecef;
            transition: background 0.2s;
        }
        .calls-table tbody tr:hover {
            background: #f8f9fc;
        }
        .calls-table tbody tr.completed {
            opacity: 0.6;
        }
        .calls-table tbody tr.overdue {
            background: #fff3cd;
        }
        .calls-table td {
            padding: 1rem;
            vertical-align: middle;
        }
        .calls-table td[contenteditable="true"]:hover {
            background: #fff9e6;
            cursor: text;
        }
        .call-type-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .call-type-follow_up { background: #0dcaf0; color: white; }
        .call-type-demo { background: #6f42c1; color: white; }
        .call-type-support { background: #fd7e14; color: white; }
        .call-type-sales { background: #198754; color: white; }
        .call-type-other { background: #6c757d; color: white; }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-pending { background: #ffc107; color: #000; }
        .status-completed { background: #198754; color: white; }
        .status-cancelled { background: #6c757d; color: white; }
        .status-missed { background: #dc3545; color: white; }
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
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
        .phone-link {
            color: #0dcaf0;
            text-decoration: none;
            font-weight: 600;
        }
        .phone-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>




    <div class="d-flex wrapper">
        <?php include 'includes/sidebar.php'; ?>
        <?php include 'includes/topbar.php'; ?>
        
        <div class="flex-grow-1 main-content">
            <?php include 'includes/navbar.php'; ?>
            
            <div class="container-fluid calls-page">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="fas fa-phone text-success"></i> Rappels d'Appels</h2>
                        <p class="text-muted mb-0">Planifiez et suivez vos appels importants</p>
                    </div>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#callModal" onclick="openCallModal()">
                        <i class="fas fa-plus"></i> Nouveau Rappel
                    </button>
                </div>

                <!-- Statistiques -->
                <div class="stats-card">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="stat-item">
                                <i class="fas fa-phone text-success"></i>
                                <div>
                                    <div class="stat-value"><?php echo $stats['total']; ?></div>
                                    <div class="stat-label">Total Rappels</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-item">
                                <i class="fas fa-clock text-warning"></i>
                                <div>
                                    <div class="stat-value"><?php echo $stats['pending']; ?></div>
                                    <div class="stat-label">En Attente</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-item">
                                <i class="fas fa-calendar-day text-info"></i>
                                <div>
                                    <div class="stat-value"><?php echo $stats['today']; ?></div>
                                    <div class="stat-label">Aujourd'hui</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="filter-tabs">
                        <a href="?filter=pending" class="filter-tab <?php echo $filter === 'pending' ? 'active' : ''; ?>">
                            <i class="fas fa-clock"></i> En attente (<?php echo $stats['pending']; ?>)
                        </a>
                        <a href="?filter=today" class="filter-tab <?php echo $filter === 'today' ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-day"></i> Aujourd'hui (<?php echo $stats['today']; ?>)
                        </a>
                        <a href="?filter=overdue" class="filter-tab <?php echo $filter === 'overdue' ? 'active' : ''; ?>">
                            <i class="fas fa-exclamation-triangle"></i> En retard (<?php echo $stats['overdue']; ?>)
                        </a>
                        <a href="?filter=completed" class="filter-tab <?php echo $filter === 'completed' ? 'active' : ''; ?>">
                            <i class="fas fa-check-circle"></i> Terminés (<?php echo $stats['completed']; ?>)
                        </a>
                        <a href="?filter=missed" class="filter-tab <?php echo $filter === 'missed' ? 'active' : ''; ?>">
                            <i class="fas fa-times-circle"></i> Manqués (<?php echo $stats['missed']; ?>)
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

                <!-- Tableau des appels -->
                <div class="calls-table-wrapper">
                    <table class="calls-table">
                        <thead>
                            <tr>
                                <th>Contact</th>
                                <th style="width: 150px;">Téléphone</th>
                                <th style="width: 180px;">Date & Heure</th>
                                <th style="width: 120px;">Type</th>
                                <th>Notes</th>
                                <th style="width: 120px;">Statut</th>
                                <th style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($calls)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fas fa-phone-slash fa-3x mb-3 d-block"></i>
                                        Aucun rappel trouvé
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($calls as $call): 
                                    $isOverdue = strtotime($call['scheduled_time']) < time() && $call['status'] === 'pending';
                                    $isToday = date('Y-m-d', strtotime($call['scheduled_time'])) === date('Y-m-d');
                                    $isCompleted = $call['status'] === 'completed';
                                ?>
                                    <tr class="<?php echo $isCompleted ? 'completed' : ($isOverdue ? 'overdue' : ''); ?>" 
                                        data-call-id="<?php echo $call['id']; ?>">
                                        <td>
                                            <strong contenteditable="true" 
                                                    data-field="contact_name" 
                                                    data-call-id="<?php echo $call['id']; ?>"
                                                    onblur="saveFieldInline(this)">
                                                <?php echo htmlspecialchars($call['contact_name']); ?>
                                            </strong>
                                            <?php if ($call['contact_type'] !== 'other'): ?>
                                                <span class="badge bg-secondary ms-2"><?php echo ucfirst($call['contact_type']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="tel:<?php echo htmlspecialchars($call['phone']); ?>" 
                                               class="phone-link">
                                                <i class="fas fa-phone"></i>
                                                <?php echo htmlspecialchars($call['phone']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="<?php echo $isOverdue ? 'overdue-indicator' : ''; ?>">
                                                <i class="fas fa-calendar"></i>
                                                <?php echo date('d/m/Y', strtotime($call['scheduled_time'])); ?>
                                                <br>
                                                <small>
                                                    <i class="fas fa-clock"></i>
                                                    <?php echo date('H:i', strtotime($call['scheduled_time'])); ?>
                                                    (<?php echo $call['duration_minutes']; ?> min)
                                                </small>
                                            </div>
                                            <?php if ($isOverdue): ?>
                                                <span class="badge bg-danger mt-1">RETARD</span>
                                            <?php elseif ($isToday): ?>
                                                <span class="badge bg-warning text-dark mt-1">AUJOURD'HUI</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm call-type-badge call-type-<?php echo $call['call_type']; ?>"
                                                    onchange="updateCallField(<?php echo $call['id']; ?>, 'call_type', this.value)">
                                                <option value="follow_up" <?php echo $call['call_type'] === 'follow_up' ? 'selected' : ''; ?>>Suivi</option>
                                                <option value="demo" <?php echo $call['call_type'] === 'demo' ? 'selected' : ''; ?>>Démo</option>
                                                <option value="support" <?php echo $call['call_type'] === 'support' ? 'selected' : ''; ?>>Support</option>
                                                <option value="sales" <?php echo $call['call_type'] === 'sales' ? 'selected' : ''; ?>>Vente</option>
                                                <option value="other" <?php echo $call['call_type'] === 'other' ? 'selected' : ''; ?>>Autre</option>
                                            </select>
                                        </td>
                                        <td>
                                            <div contenteditable="true"
                                                 data-field="notes"
                                                 data-call-id="<?php echo $call['id']; ?>"
                                                 onblur="saveFieldInline(this)"
                                                 class="text-muted small">
                                                <?php echo htmlspecialchars($call['notes'] ?? 'Pas de notes'); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm status-badge status-<?php echo $call['status']; ?>"
                                                    onchange="updateCallField(<?php echo $call['id']; ?>, 'status', this.value)">
                                                <option value="pending" <?php echo $call['status'] === 'pending' ? 'selected' : ''; ?>>En attente</option>
                                                <option value="completed" <?php echo $call['status'] === 'completed' ? 'selected' : ''; ?>>Terminé</option>
                                                <option value="missed" <?php echo $call['status'] === 'missed' ? 'selected' : ''; ?>>Manqué</option>
                                                <option value="cancelled" <?php echo $call['status'] === 'cancelled' ? 'selected' : ''; ?>>Annulé</option>
                                            </select>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-sm btn-outline-primary btn-icon" 
                                                        onclick="editCall(<?php echo $call['id']; ?>)" 
                                                        title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger btn-icon" 
                                                        onclick="deleteCall(<?php echo $call['id']; ?>)" 
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

    <!-- Modal pour créer/éditer un rappel -->
    <div class="modal fade" id="callModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="callModalTitle">Nouveau Rappel d'Appel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="callForm">
                    <div class="modal-body">
                        <input type="hidden" id="call_id" name="id">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact *</label>
                                <input type="text" class="form-control" id="call_contact_name" name="contact_name" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Téléphone *</label>
                                <input type="tel" class="form-control" id="call_phone" name="phone" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date & Heure *</label>
                                <input type="datetime-local" class="form-control" id="call_scheduled_time" name="scheduled_time" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Durée (minutes)</label>
                                <input type="number" class="form-control" id="call_duration" name="duration_minutes" value="30" min="5" step="5">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Type d'appel</label>
                                <select class="form-select" id="call_type" name="call_type">
                                    <option value="follow_up" selected>Suivi</option>
                                    <option value="demo">Démonstration</option>
                                    <option value="support">Support</option>
                                    <option value="sales">Vente</option>
                                    <option value="other">Autre</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Statut</label>
                                <select class="form-select" id="call_status" name="status">
                                    <option value="pending" selected>En attente</option>
                                    <option value="completed">Terminé</option>
                                    <option value="missed">Manqué</option>
                                    <option value="cancelled">Annulé</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" id="call_notes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-success">
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
    // Sauvegarder un champ inline
    function saveFieldInline(element) {
        const callId = element.dataset.callId;
        const field = element.dataset.field;
        const value = element.textContent.trim();
        
        updateCallField(callId, field, value);
    }
    
    // Mettre à jour un champ de rappel
    function updateCallField(callId, field, value) {
        fetch('api/calls.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'update_field',
                id: callId,
                field: field,
                value: value
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Réactualiser si changement de statut
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
    
    // Ouvrir le modal pour un nouveau rappel
    function openCallModal() {
        document.getElementById('callModalTitle').textContent = 'Nouveau Rappel d\'Appel';
        document.getElementById('callForm').reset();
        document.getElementById('call_id').value = '';
        
        // Définir l'heure par défaut à maintenant + 1 heure
        const now = new Date();
        now.setHours(now.getHours() + 1);
        document.getElementById('call_scheduled_time').value = now.toISOString().slice(0, 16);
    }
    
    // Éditer un rappel
    function editCall(callId) {
        fetch(`api/calls.php?action=get&id=${callId}`)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const call = data.call;
                    document.getElementById('callModalTitle').textContent = 'Modifier le Rappel';
                    document.getElementById('call_id').value = call.id;
                    document.getElementById('call_contact_name').value = call.contact_name;
                    document.getElementById('call_phone').value = call.phone;
                    document.getElementById('call_type').value = call.call_type;
                    document.getElementById('call_status').value = call.status;
                    document.getElementById('call_notes').value = call.notes || '';
                    document.getElementById('call_duration').value = call.duration_minutes;
                    
                    if (call.scheduled_time) {
                        const date = new Date(call.scheduled_time);
                        const formattedDate = date.toISOString().slice(0, 16);
                        document.getElementById('call_scheduled_time').value = formattedDate;
                    }
                    
                    new bootstrap.Modal(document.getElementById('callModal')).show();
                }
            });
    }
    
    // Supprimer un rappel
    function deleteCall(callId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer ce rappel ?')) return;
        
        fetch('api/calls.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'delete',
                id: callId
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
    document.getElementById('callForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = {
            action: document.getElementById('call_id').value ? 'update' : 'create'
        };
        
        formData.forEach((value, key) => {
            data[key] = value;
        });
        
        fetch('api/calls.php', {
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
    </script>
</body>
</html>
