<?php
include 'includes/verify_subscriptions.php';
require_once __DIR__ . '/includes/env.php';

$page_title = "Campagnes WhatsApp";
$customer_id = $_SESSION['customer_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

if (!$customer_id) {
    header('Location: index.php?error=customer');
    exit;
}

// Récupérer les configurations WhatsApp
$whatsapp_configs = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM whatsapp_configurations WHERE customer_id = ? AND is_active = 1");
    $stmt->execute([$customer_id]);
    $whatsapp_configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("WhatsApp configs error: " . $e->getMessage());
}

// Récupérer les templates approuvés
$templates = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM whatsapp_templates WHERE customer_id = ? AND status = 'APPROVED' ORDER BY template_name");
    $stmt->execute([$customer_id]);
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Templates error: " . $e->getMessage());
}

// Récupérer les campagnes WhatsApp
$campaigns = [];
$filter_status = $_GET['status'] ?? '';
$filter_config = $_GET['config_id'] ?? '';

try {
    $conditions = ['customer_id = ?'];
    $params = [$customer_id];
    
    if ($filter_status) {
        $conditions[] = 'status = ?';
        $params[] = $filter_status;
    }
    if ($filter_config) {
        $conditions[] = 'config_id = ?';
        $params[] = $filter_config;
    }
    
    $sql = "SELECT c.*, t.template_name, cfg.display_phone_number 
            FROM whatsapp_campaigns c 
            LEFT JOIN whatsapp_templates t ON c.template_id = t.id
            LEFT JOIN whatsapp_configurations cfg ON c.config_id = cfg.id
            WHERE " . implode(' AND ', $conditions) . " 
            ORDER BY c.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Campaigns fetch error: " . $e->getMessage());
}

// Statistiques
$stats = [
    'total' => count($campaigns),
    'active' => count(array_filter($campaigns, fn($c) => $c['status'] === 'sending')),
    'sent' => array_sum(array_column($campaigns, 'sent_count')),
    'delivered' => array_sum(array_column($campaigns, 'delivered_count')),
];

$page_title = "Campagnes WhatsApp - CRM";
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
        .whatsapp-btn {
            background: #25D366;
            color: white;
            border: none;
        }
        .whatsapp-btn:hover {
            background: #20ba5a;
            color: white;
        }
        .campaign-card {
            border-left: 4px solid #25D366;
            transition: all 0.3s;
        }
        .campaign-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .status-draft { border-left-color: #6c757d; }
        .status-scheduled { border-left-color: #0dcaf0; }
        .status-sending { border-left-color: #ffc107; }
        .status-sent { border-left-color: #28a745; }
        .status-paused { border-left-color: #fd7e14; }
        .progress-bar-whatsapp {
            background: #25D366;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        <?php include 'includes/topbar.php'; ?>
        
        <div class="main-content">
            <div class="container-fluid">

                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>
                        <i class="fab fa-whatsapp" style="color: #25D366;"></i> Campagnes WhatsApp
                    </h1>
                    <div class="btn-group">
                        <button class="btn whatsapp-btn" data-bs-toggle="modal" data-bs-target="#addCampaignModal">
                            <i class="fas fa-plus"></i> Nouvelle Campagne
                        </button>
                        <a href="whatsapp-templates.php" class="btn btn-info">
                            <i class="fas fa-file-alt"></i> Gérer Templates
                        </a>
                        <a href="whatsapp-settings.php" class="btn btn-secondary">
                            <i class="fas fa-cog"></i> Configuration
                        </a>
                    </div>
                </div>

                <!-- Info Campagnes -->
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>À propos des Campagnes WhatsApp</strong>
                    <ul class="mb-0 mt-2">
                        <li>Utilise des <strong>templates approuvés</strong> par Meta uniquement</li>
                        <li>Coût: ~0.005$ - 0.10$ par message selon le pays de destination</li>
                        <li>Limite quotidienne basée sur le <strong>tier</strong> de votre numéro (50 → illimité)</li>
                        <li>Respecte la fenêtre de 24h (hors conversation active)</li>
                        <li>Statistiques en temps réel: envoyé, délivré, lu, répondu</li>
                    </ul>
                </div>

                <!-- Statistiques -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card border-left-primary">
                            <div class="card-body">
                                <h6 class="text-primary">Campagnes Totales</h6>
                                <h3><?php echo $stats['total']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-left-warning">
                            <div class="card-body">
                                <h6 class="text-warning">En cours</h6>
                                <h3><?php echo $stats['active']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-left-info">
                            <div class="card-body">
                                <h6 class="text-info">Messages Envoyés</h6>
                                <h3><?php echo number_format($stats['sent']); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-left-success">
                            <div class="card-body">
                                <h6 class="text-success">Délivrés</h6>
                                <h3><?php echo number_format($stats['delivered']); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <form method="get" class="row g-2">
                            <div class="col-md-4">
                                <select class="form-control" name="config_id">
                                    <option value="">Tous les numéros</option>
                                    <?php foreach ($whatsapp_configs as $config): ?>
                                        <option value="<?php echo $config['id']; ?>" <?php echo $filter_config == $config['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($config['display_phone_number']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select class="form-control" name="status">
                                    <option value="">Tous les statuts</option>
                                    <option value="draft" <?php echo $filter_status === 'draft' ? 'selected' : ''; ?>>Brouillon</option>
                                    <option value="scheduled" <?php echo $filter_status === 'scheduled' ? 'selected' : ''; ?>>Programmée</option>
                                    <option value="sending" <?php echo $filter_status === 'sending' ? 'selected' : ''; ?>>En cours</option>
                                    <option value="sent" <?php echo $filter_status === 'sent' ? 'selected' : ''; ?>>Envoyée</option>
                                    <option value="paused" <?php echo $filter_status === 'paused' ? 'selected' : ''; ?>>En pause</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-info w-100">
                                    <i class="fas fa-filter"></i> Filtrer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Liste des Campagnes -->
                <div class="row">
                    <?php if (empty($campaigns)): ?>
                        <div class="col-12">
                            <div class="card text-center py-5">
                                <div class="card-body">
                                    <i class="fab fa-whatsapp fa-5x text-muted mb-3"></i>
                                    <h5>Aucune campagne WhatsApp</h5>
                                    <p class="text-muted">Créez votre première campagne pour commencer</p>
                                    <button class="btn whatsapp-btn" data-bs-toggle="modal" data-bs-target="#addCampaignModal">
                                        <i class="fas fa-plus"></i> Créer une Campagne
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($campaigns as $camp): 
                            $status_badge = [
                                'draft' => 'secondary',
                                'scheduled' => 'info',
                                'sending' => 'warning',
                                'sent' => 'success',
                                'paused' => 'danger',
                                'cancelled' => 'dark'
                            ][$camp['status']] ?? 'secondary';
                            
                            $progress = $camp['recipients_count'] > 0 
                                ? round(($camp['sent_count'] / $camp['recipients_count']) * 100) 
                                : 0;
                            
                            $delivery_rate = $camp['sent_count'] > 0 
                                ? round(($camp['delivered_count'] / $camp['sent_count']) * 100) 
                                : 0;
                            
                            $read_rate = $camp['delivered_count'] > 0 
                                ? round(($camp['read_count'] / $camp['delivered_count']) * 100) 
                                : 0;
                        ?>
                            <div class="col-md-6 mb-4">
                                <div class="card campaign-card status-<?php echo $camp['status']; ?> h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0">
                                                <i class="fab fa-whatsapp"></i>
                                                <?php echo htmlspecialchars($camp['name']); ?>
                                            </h6>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($camp['display_phone_number']); ?> • 
                                                Template: <?php echo htmlspecialchars($camp['template_name']); ?>
                                            </small>
                                        </div>
                                        <span class="badge bg-<?php echo $status_badge; ?>"><?php echo strtoupper($camp['status']); ?></span>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($camp['description']): ?>
                                            <p class="text-muted small"><?php echo nl2br(htmlspecialchars($camp['description'])); ?></p>
                                        <?php endif; ?>

                                        <!-- Progression -->
                                        <?php if ($camp['status'] === 'sending' || $camp['status'] === 'sent'): ?>
                                            <div class="mb-3">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <small>Progression</small>
                                                    <small><strong><?php echo $camp['sent_count']; ?></strong> / <?php echo $camp['recipients_count']; ?></small>
                                                </div>
                                                <div class="progress" style="height: 8px;">
                                                    <div class="progress-bar progress-bar-whatsapp" style="width: <?php echo $progress; ?>%"></div>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Statistiques -->
                                        <div class="row g-2 text-center mb-3">
                                            <div class="col-3">
                                                <small class="text-muted d-block">Destinataires</small>
                                                <strong><?php echo $camp['recipients_count']; ?></strong>
                                            </div>
                                            <div class="col-3">
                                                <small class="text-muted d-block">Envoyés</small>
                                                <strong class="text-info"><?php echo $camp['sent_count']; ?></strong>
                                            </div>
                                            <div class="col-3">
                                                <small class="text-muted d-block">Délivrés</small>
                                                <strong class="text-success"><?php echo $camp['delivered_count']; ?></strong>
                                            </div>
                                            <div class="col-3">
                                                <small class="text-muted d-block">Lus</small>
                                                <strong class="text-primary"><?php echo $camp['read_count']; ?></strong>
                                            </div>
                                        </div>

                                        <!-- Taux -->
                                        <div class="row g-2 text-center">
                                            <div class="col-4">
                                                <small class="text-muted d-block">Délivrance</small>
                                                <span class="badge bg-success"><?php echo $delivery_rate; ?>%</span>
                                            </div>
                                            <div class="col-4">
                                                <small class="text-muted d-block">Lecture</small>
                                                <span class="badge bg-primary"><?php echo $read_rate; ?>%</span>
                                            </div>
                                            <div class="col-4">
                                                <small class="text-muted d-block">Coût</small>
                                                <span class="badge bg-warning text-dark">$<?php echo number_format($camp['total_cost'], 2); ?></span>
                                            </div>
                                        </div>

                                        <?php if ($camp['scheduled_at'] && $camp['status'] === 'scheduled'): ?>
                                            <div class="alert alert-info mt-3 mb-0">
                                                <i class="fas fa-clock"></i> Programmée pour le 
                                                <strong><?php echo date('d/m/Y H:i', strtotime($camp['scheduled_at'])); ?></strong>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-footer">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                Créée le <?php echo date('d/m/Y', strtotime($camp['created_at'])); ?>
                                            </small>
                                            <div class="btn-group">
                                                <?php if ($camp['status'] === 'draft'): ?>
                                                    <button class="btn btn-sm whatsapp-btn" onclick="sendCampaign(<?php echo $camp['id']; ?>)">
                                                        <i class="fas fa-paper-plane"></i> Envoyer
                                                    </button>
                                                <?php elseif ($camp['status'] === 'sending'): ?>
                                                    <button class="btn btn-sm btn-warning" onclick="pauseCampaign(<?php echo $camp['id']; ?>)">
                                                        <i class="fas fa-pause"></i> Pause
                                                    </button>
                                                <?php elseif ($camp['status'] === 'paused'): ?>
                                                    <button class="btn btn-sm btn-success" onclick="resumeCampaign(<?php echo $camp['id']; ?>)">
                                                        <i class="fas fa-play"></i> Reprendre
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-outline-primary" onclick="viewCampaign(<?php echo $camp['id']; ?>)">
                                                    <i class="fas fa-eye"></i> Détails
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteCampaign(<?php echo $camp['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Créer Campagne -->
    <div class="modal fade" id="addCampaignModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fab fa-whatsapp"></i> Créer une Campagne WhatsApp
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addCampaignForm">
                        <div class="mb-3">
                            <label class="form-label">Nom de la Campagne <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" 
                                   placeholder="Promotion Été 2026" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="2"
                                      placeholder="Campagne promotionnelle pour les soldes d'été"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Numéro WhatsApp <span class="text-danger">*</span></label>
                                <select class="form-control" name="config_id" id="configSelect" required>
                                    <option value="">-- Sélectionner --</option>
                                    <?php foreach ($whatsapp_configs as $config): ?>
                                        <option value="<?php echo $config['id']; ?>">
                                            <?php echo htmlspecialchars($config['display_phone_number']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Template <span class="text-danger">*</span></label>
                                <select class="form-control" name="template_id" required>
                                    <option value="">-- Sélectionner --</option>
                                    <?php foreach ($templates as $tpl): ?>
                                        <option value="<?php echo $tpl['id']; ?>" data-body="<?php echo htmlspecialchars($tpl['body_text']); ?>">
                                            <?php echo htmlspecialchars($tpl['template_name']); ?> (<?php echo $tpl['category']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Destinataires <span class="text-danger">*</span></label>
                            <select class="form-control" name="recipients_source" id="recipientsSource" required>
                                <option value="leads">Tous les leads</option>
                                <option value="customers">Tous les clients</option>
                                <option value="segment">Segment spécifique</option>
                                <option value="manual">Liste manuelle (CSV)</option>
                            </select>
                        </div>

                        <div class="mb-3" id="manualRecipientsDiv" style="display:none;">
                            <label class="form-label">Numéros (CSV)</label>
                            <textarea class="form-control" name="manual_recipients" rows="3"
                                      placeholder="+33612345678,John Doe&#10;+33698765432,Jane Smith"></textarea>
                            <small class="text-muted">Format: numéro,nom (un par ligne)</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Programmer l'envoi (optionnel)</label>
                            <input type="datetime-local" class="form-control" name="scheduled_at">
                            <small class="text-muted">Laisser vide pour envoi immédiat</small>
                        </div>

                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Important:</strong> Vérifiez que vous avez suffisamment de quota (limite tier) avant d'envoyer.
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn whatsapp-btn" onclick="saveCampaign()">
                        <i class="fas fa-save"></i> Créer la Campagne
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.getElementById('recipientsSource')?.addEventListener('change', function() {
        document.getElementById('manualRecipientsDiv').style.display = 
            this.value === 'manual' ? 'block' : 'none';
    });

    function saveCampaign() {
        const form = document.getElementById('addCampaignForm');
        const formData = new FormData(form);
        formData.append('action', 'create');

        fetch('api/whatsapp-campaigns.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Campagne créée !');
                location.reload();
            } else {
                alert('❌ Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ Erreur lors de la création');
        });
    }

    function sendCampaign(id) {
        if (!confirm('📤 Envoyer cette campagne maintenant ?')) return;

        fetch('api/whatsapp-campaigns.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'send', campaign_id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Campagne en cours d\'envoi !');
                location.reload();
            } else {
                alert('❌ Erreur: ' + data.message);
            }
        });
    }

    function pauseCampaign(id) {
        fetch('api/whatsapp-campaigns.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'pause', campaign_id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('⏸️ Campagne mise en pause');
                location.reload();
            }
        });
    }

    function resumeCampaign(id) {
        fetch('api/whatsapp-campaigns.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'resume', campaign_id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('▶️ Campagne reprise');
                location.reload();
            }
        });
    }

    function viewCampaign(id) {
        window.location.href = 'whatsapp-campaign-details.php?id=' + id;
    }

    function deleteCampaign(id) {
        if (!confirm('⚠️ Supprimer cette campagne ?')) return;

        fetch('api/whatsapp-campaigns.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', campaign_id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Campagne supprimée');
                location.reload();
            }
        });
    }
    </script>
</body>
</html>
