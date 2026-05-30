<?php
include 'includes/verify_subscriptions.php';
require_once __DIR__ . '/includes/env.php';

$page_title = "Templates WhatsApp";
$customer_id = $_SESSION['customer_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

if (!$customer_id) {
    header('Location: index.php?error=customer');
    exit;
}

// Vérifier si les configurations WhatsApp existent
$whatsapp_configs = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM whatsapp_configurations WHERE customer_id = ? AND is_active = 1");
    $stmt->execute([$customer_id]);
    $whatsapp_configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("WhatsApp configs error: " . $e->getMessage());
}

// Récupérer les templates
$templates = [];
$filter_status = $_GET['status'] ?? '';
$filter_category = $_GET['category'] ?? '';
$filter_config = $_GET['config_id'] ?? '';

try {
    $conditions = ['customer_id = ?'];
    $params = [$customer_id];
    
    if ($filter_status) {
        $conditions[] = 'status = ?';
        $params[] = $filter_status;
    }
    if ($filter_category) {
        $conditions[] = 'category = ?';
        $params[] = $filter_category;
    }
    if ($filter_config) {
        $conditions[] = 'config_id = ?';
        $params[] = $filter_config;
    }
    
    $sql = "SELECT t.*, c.display_phone_number 
            FROM whatsapp_templates t 
            LEFT JOIN whatsapp_configurations c ON t.config_id = c.id 
            WHERE " . implode(' AND ', $conditions) . " 
            ORDER BY t.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Templates fetch error: " . $e->getMessage());
}

$page_title = "Templates WhatsApp - CRM";
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
        .template-card {
            border-left: 4px solid #25D366;
            transition: all 0.3s;
        }
        .template-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .status-APPROVED { border-left-color: #28a745; }
        .status-PENDING { border-left-color: #ffc107; }
        .status-REJECTED { border-left-color: #dc3545; }
        .template-preview {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .whatsapp-btn {
            background: #25D366;
            color: white;
            border: none;
        }
        .whatsapp-btn:hover {
            background: #20ba5a;
            color: white;
        }
        .variable-tag {
            background: #e3f2fd;
            color: #1976d2;
            padding: 2px 8px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.85em;
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
                        <i class="fas fa-file-alt" style="color: #25D366;"></i> Templates WhatsApp
                    </h1>
                    <div class="btn-group">
                        <button class="btn whatsapp-btn" data-bs-toggle="modal" data-bs-target="#addTemplateModal">
                            <i class="fas fa-plus"></i> Créer Template
                        </button>
                        <a href="whatsapp-settings.php" class="btn btn-secondary">
                            <i class="fas fa-cog"></i> Configuration
                        </a>
                    </div>
                </div>

                <!-- Info Templates -->
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>À propos des Templates WhatsApp</strong>
                    <ul class="mb-0 mt-2">
                        <li><strong>Obligatoires</strong> pour envoyer des messages sortants (hors réponse 24h)</li>
                        <li>Doivent être <strong>approuvés par Meta</strong> avant utilisation (délai: 24-48h)</li>
                        <li>Variables dynamiques: {{1}}, {{2}}, {{3}}... (max 4096 caractères)</li>
                        <li>3 catégories: MARKETING, UTILITY (transactionnel), AUTHENTICATION</li>
                        <li>Une fois approuvé, ne peut pas être modifié (créer nouvelle version)</li>
                    </ul>
                </div>

                <!-- Statistiques -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card border-left-success">
                            <div class="card-body">
                                <h6 class="text-success">Approuvés</h6>
                                <h3><?php echo count(array_filter($templates, fn($t) => $t['status'] === 'APPROVED')); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-left-warning">
                            <div class="card-body">
                                <h6 class="text-warning">En attente</h6>
                                <h3><?php echo count(array_filter($templates, fn($t) => $t['status'] === 'PENDING')); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-left-danger">
                            <div class="card-body">
                                <h6 class="text-danger">Rejetés</h6>
                                <h3><?php echo count(array_filter($templates, fn($t) => $t['status'] === 'REJECTED')); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-left-primary">
                            <div class="card-body">
                                <h6 class="text-primary">Total</h6>
                                <h3><?php echo count($templates); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <form method="get" class="row g-2">
                            <div class="col-md-3">
                                <select class="form-control" name="config_id">
                                    <option value="">Tous les numéros</option>
                                    <?php foreach ($whatsapp_configs as $config): ?>
                                        <option value="<?php echo $config['id']; ?>" <?php echo $filter_config == $config['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($config['display_phone_number']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-control" name="status">
                                    <option value="">Tous les statuts</option>
                                    <option value="APPROVED" <?php echo $filter_status === 'APPROVED' ? 'selected' : ''; ?>>Approuvé</option>
                                    <option value="PENDING" <?php echo $filter_status === 'PENDING' ? 'selected' : ''; ?>>En attente</option>
                                    <option value="REJECTED" <?php echo $filter_status === 'REJECTED' ? 'selected' : ''; ?>>Rejeté</option>
                                    <option value="DISABLED" <?php echo $filter_status === 'DISABLED' ? 'selected' : ''; ?>>Désactivé</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-control" name="category">
                                    <option value="">Toutes les catégories</option>
                                    <option value="MARKETING" <?php echo $filter_category === 'MARKETING' ? 'selected' : ''; ?>>Marketing</option>
                                    <option value="UTILITY" <?php echo $filter_category === 'UTILITY' ? 'selected' : ''; ?>>Utilitaire</option>
                                    <option value="AUTHENTICATION" <?php echo $filter_category === 'AUTHENTICATION' ? 'selected' : ''; ?>>Authentification</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-info w-100">
                                    <i class="fas fa-filter"></i> Filtrer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Liste des Templates -->
                <div class="row">
                    <?php if (empty($templates)): ?>
                        <div class="col-12">
                            <div class="card text-center py-5">
                                <div class="card-body">
                                    <i class="fas fa-file-alt fa-5x text-muted mb-3"></i>
                                    <h5>Aucun template trouvé</h5>
                                    <p class="text-muted">Créez votre premier template pour commencer</p>
                                    <button class="btn whatsapp-btn" data-bs-toggle="modal" data-bs-target="#addTemplateModal">
                                        <i class="fas fa-plus"></i> Créer Template
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($templates as $tpl): 
                            $status_badge = [
                                'APPROVED' => 'success',
                                'PENDING' => 'warning',
                                'REJECTED' => 'danger',
                                'DISABLED' => 'secondary'
                            ][$tpl['status']] ?? 'secondary';
                            
                            $category_icon = [
                                'MARKETING' => 'bullhorn',
                                'UTILITY' => 'tools',
                                'AUTHENTICATION' => 'lock'
                            ][$tpl['category']] ?? 'file';
                        ?>
                            <div class="col-md-6 mb-4">
                                <div class="card template-card status-<?php echo $tpl['status']; ?> h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0">
                                                <i class="fas fa-<?php echo $category_icon; ?>"></i>
                                                <?php echo htmlspecialchars($tpl['template_name']); ?>
                                            </h6>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($tpl['display_phone_number']); ?> • 
                                                <?php echo strtoupper($tpl['language']); ?>
                                            </small>
                                        </div>
                                        <span class="badge bg-<?php echo $status_badge; ?>"><?php echo $tpl['status']; ?></span>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <span class="badge bg-info"><?php echo $tpl['category']; ?></span>
                                            <?php if ($tpl['header_type'] !== 'NONE'): ?>
                                                <span class="badge bg-secondary"><?php echo $tpl['header_type']; ?></span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Aperçu Template -->
                                        <div class="template-preview">
                                            <?php if ($tpl['header_type'] !== 'NONE' && $tpl['header_content']): ?>
                                                <div class="mb-2">
                                                    <?php if ($tpl['header_type'] === 'TEXT'): ?>
                                                        <strong><?php echo nl2br(htmlspecialchars($tpl['header_content'])); ?></strong>
                                                    <?php else: ?>
                                                        <span class="text-muted">[<?php echo $tpl['header_type']; ?>]</span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="mb-2">
                                                <?php echo nl2br(htmlspecialchars($tpl['body_text'])); ?>
                                            </div>
                                            
                                            <?php if ($tpl['footer_text']): ?>
                                                <div class="text-muted small">
                                                    <?php echo htmlspecialchars($tpl['footer_text']); ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($tpl['buttons']): 
                                                $buttons = json_decode($tpl['buttons'], true);
                                                if ($buttons):
                                            ?>
                                                <div class="mt-2">
                                                    <?php foreach ($buttons as $btn): ?>
                                                        <button class="btn btn-sm btn-outline-secondary w-100 mb-1" disabled>
                                                            <?php echo htmlspecialchars($btn['text']); ?>
                                                        </button>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; endif; ?>
                                        </div>

                                        <?php if ($tpl['status'] === 'REJECTED' && $tpl['rejection_reason']): ?>
                                            <div class="alert alert-danger mt-3 mb-0">
                                                <strong>Rejeté:</strong> <?php echo htmlspecialchars($tpl['rejection_reason']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-footer">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                Créé le <?php echo date('d/m/Y', strtotime($tpl['created_at'])); ?>
                                            </small>
                                            <div class="btn-group">
                                                <?php if ($tpl['status'] === 'APPROVED'): ?>
                                                    <button class="btn btn-sm whatsapp-btn" onclick="useTemplate(<?php echo $tpl['id']; ?>)">
                                                        <i class="fas fa-paper-plane"></i> Utiliser
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteTemplate(<?php echo $tpl['id']; ?>)">
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

    <!-- Modal Créer Template -->
    <div class="modal fade" id="addTemplateModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-file-alt"></i> Créer un Template WhatsApp
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addTemplateForm">
                        <div class="mb-3">
                            <label class="form-label">Numéro WhatsApp <span class="text-danger">*</span></label>
                            <select class="form-control" name="config_id" required>
                                <option value="">-- Sélectionner --</option>
                                <?php foreach ($whatsapp_configs as $config): ?>
                                    <option value="<?php echo $config['id']; ?>">
                                        <?php echo htmlspecialchars($config['display_phone_number']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nom du Template <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="template_name" 
                                       placeholder="bienvenue_client" pattern="[a-z0-9_]+" required>
                                <small class="text-muted">Minuscules, chiffres et _ uniquement</small>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Langue <span class="text-danger">*</span></label>
                                <select class="form-control" name="language" required>
                                    <option value="fr">Français</option>
                                    <option value="en">English</option>
                                    <option value="es">Español</option>
                                    <option value="ar">العربية</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Catégorie <span class="text-danger">*</span></label>
                                <select class="form-control" name="category" required>
                                    <option value="MARKETING">Marketing</option>
                                    <option value="UTILITY">Utilitaire</option>
                                    <option value="AUTHENTICATION">Auth</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Header (optionnel)</label>
                            <select class="form-control" name="header_type" id="headerType">
                                <option value="NONE">Aucun</option>
                                <option value="TEXT">Texte</option>
                                <option value="IMAGE">Image</option>
                                <option value="VIDEO">Vidéo</option>
                                <option value="DOCUMENT">Document</option>
                            </select>
                        </div>

                        <div class="mb-3" id="headerContentDiv" style="display:none;">
                            <label class="form-label">Contenu Header</label>
                            <input type="text" class="form-control" name="header_content" 
                                   placeholder="Texte ou URL média">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Corps du message <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="body_text" rows="5" 
                                      placeholder="Bonjour {{1}}, votre commande {{2}} est prête !" required></textarea>
                            <small class="text-muted">
                                Variables: <span class="variable-tag">{{1}}</span> 
                                <span class="variable-tag">{{2}}</span> 
                                <span class="variable-tag">{{3}}</span>
                            </small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Footer (optionnel, max 60 car.)</label>
                            <input type="text" class="form-control" name="footer_text" maxlength="60"
                                   placeholder="Merci de votre confiance">
                        </div>

                        <div class="alert alert-warning">
                            <i class="fas fa-clock"></i> Le template sera soumis à Meta pour approbation (délai: 24-48h).
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn whatsapp-btn" onclick="saveTemplate()">
                        <i class="fas fa-paper-plane"></i> Soumettre à Meta
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.getElementById('headerType')?.addEventListener('change', function() {
        document.getElementById('headerContentDiv').style.display = 
            this.value !== 'NONE' ? 'block' : 'none';
    });

    function saveTemplate() {
        const form = document.getElementById('addTemplateForm');
        const formData = new FormData(form);
        formData.append('action', 'create');

        fetch('api/whatsapp-templates.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Template soumis à Meta pour approbation !');
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

    function deleteTemplate(id) {
        if (!confirm('⚠️ Supprimer ce template ?')) return;

        fetch('api/whatsapp-templates.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', template_id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Template supprimé');
                location.reload();
            } else {
                alert('❌ Erreur: ' + data.message);
            }
        });
    }

    function useTemplate(id) {
        window.location.href = 'whatsapp-campaigns.php?template_id=' + id;
    }
    </script>
</body>
</html>
