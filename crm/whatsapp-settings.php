<?php
include 'includes/verify_subscriptions.php';
require_once __DIR__ . '/includes/env.php';

$page_title = "Configuration WhatsApp Business";
$customer_id = $_SESSION['customer_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;
$tables_exist = false;
$error_message = null;

if (!$customer_id) {
    header('Location: index.php?error=customer');
    exit;
}

// Vérifier si les tables WhatsApp existent
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'whatsapp_configurations'");
    $tables_exist = $stmt->rowCount() > 0;
    
    if (!$tables_exist) {
        $error_message = "⚠️ Les tables WhatsApp ne sont pas créées. Exécutez: <code>database/migrations/002_add_whatsapp_support.sql</code>";
    }
} catch (Exception $e) {
    error_log("WhatsApp table check error: " . $e->getMessage());
    $tables_exist = false;
}

// Récupérer les configurations WhatsApp
$whatsapp_configs = [];
try {
    if ($tables_exist) {
        $stmt = $pdo->prepare("
            SELECT * FROM whatsapp_configurations 
            WHERE customer_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$customer_id]);
        $whatsapp_configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    error_log("WhatsApp configs fetch error: " . $e->getMessage());
}

// Statistiques
$stats = [
    'total_messages' => 0,
    'sent_today' => 0,
    'templates_approved' => 0,
    'active_conversations' => 0
];

try {
    if ($tables_exist && !empty($whatsapp_configs)) {
        $config_ids = array_column($whatsapp_configs, 'id');
        $placeholders = implode(',', array_fill(0, count($config_ids), '?'));
        
        // Messages totaux
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM whatsapp_messages WHERE config_id IN ($placeholders)");
        $stmt->execute($config_ids);
        $stats['total_messages'] = $stmt->fetch()['total'] ?? 0;
        
        // Messages envoyés aujourd'hui
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM whatsapp_messages WHERE config_id IN ($placeholders) AND DATE(created_at) = CURDATE() AND direction = 'outbound'");
        $stmt->execute($config_ids);
        $stats['sent_today'] = $stmt->fetch()['total'] ?? 0;
        
        // Templates approuvés
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM whatsapp_templates WHERE config_id IN ($placeholders) AND status = 'APPROVED'");
        $stmt->execute($config_ids);
        $stats['templates_approved'] = $stmt->fetch()['total'] ?? 0;
        
        // Conversations actives (dernier message < 24h)
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM whatsapp_conversations WHERE config_id IN ($placeholders) AND last_message_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) AND is_archived = 0");
        $stmt->execute($config_ids);
        $stats['active_conversations'] = $stmt->fetch()['total'] ?? 0;
    }
} catch (Exception $e) {
    error_log("WhatsApp stats error: " . $e->getMessage());
}

$page_title = "WhatsApp Business - CRM";
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
        .whatsapp-card {
            border-left: 4px solid #25D366;
        }
        .quality-badge-GREEN { background: #28a745; }
        .quality-badge-YELLOW { background: #ffc107; color: #000; }
        .quality-badge-RED { background: #dc3545; }
        .tier-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        .config-card {
            transition: all 0.3s;
            border-radius: 10px;
            overflow: hidden;
        }
        .config-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
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
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        <?php include 'includes/topbar.php'; ?>
        
        <div class="main-content">
            <div class="container-fluid">

                <!-- Page Header -->
                <div class="page-header d-flex justify-content-between align-items-center mb-4">
                    <h1>
                        <i class="fab fa-whatsapp" style="color: #25D366;"></i> WhatsApp Business
                    </h1>
                    <div class="btn-group">
                        <button class="btn whatsapp-btn" data-bs-toggle="modal" data-bs-target="#addWhatsAppModal">
                            <i class="fab fa-whatsapp"></i> Connecter WhatsApp
                        </button>
                        <a href="whatsapp-campaigns.php" class="btn btn-primary">
                            <i class="fas fa-bullhorn"></i> Campagnes WhatsApp
                        </a>
                        <a href="whatsapp-templates.php" class="btn btn-info">
                            <i class="fas fa-file-alt"></i> Gérer Templates
                        </a>
                    </div>
                </div>

                <!-- Alertes -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <strong>✓ Succès!</strong> <?php echo htmlspecialchars($_SESSION['success_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-warning alert-dismissible fade show">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Info WhatsApp -->
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>À propos de WhatsApp Business Cloud API</strong>
                    <ul class="mb-0 mt-2">
                        <li>Nécessite un compte <strong>Meta Business</strong> vérifié</li>
                        <li>Chaque numéro WhatsApp doit être approuvé par Meta</li>
                        <li>Les messages sortants nécessitent des <strong>templates approuvés</strong></li>
                        <li>Tarification: ~0.005$ - 0.10$ par message selon le pays</li>
                        <li>Limite initiale: 50 conversations/24h (augmente automatiquement)</li>
                    </ul>
                </div>

                <!-- Statistiques -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Numéros Connectés
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($whatsapp_configs); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fab fa-whatsapp fa-2x text-gray-300"></i>
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
                                            Messages Totaux
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stats['total_messages']); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-comments fa-2x text-gray-300"></i>
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
                                            Envoyés Aujourd'hui
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['sent_today']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-paper-plane fa-2x text-gray-300"></i>
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
                                            Templates Approuvés
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['templates_approved']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Numéros WhatsApp Connectés -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fab fa-whatsapp"></i> Numéros WhatsApp Business
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($whatsapp_configs)): ?>
                            <div class="text-center py-5">
                                <i class="fab fa-whatsapp fa-5x text-muted mb-3"></i>
                                <h5>Aucun numéro WhatsApp connecté</h5>
                                <p class="text-muted">Connectez votre WhatsApp Business pour envoyer des messages à vos clients</p>
                                <button class="btn whatsapp-btn" data-bs-toggle="modal" data-bs-target="#addWhatsAppModal">
                                    <i class="fab fa-whatsapp"></i> Connecter WhatsApp
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($whatsapp_configs as $config): 
                                    $quality_class = "quality-badge-{$config['quality_rating']}";
                                    $tier_label = str_replace('TIER_', '', $config['messaging_limit']);
                                    $is_active = $config['is_active'] ? 'Actif' : 'Inactif';
                                    $status_color = $config['is_active'] ? 'success' : 'secondary';
                                ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card config-card whatsapp-card h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <div>
                                                        <h5 class="card-title mb-1">
                                                            <i class="fab fa-whatsapp" style="color: #25D366;"></i>
                                                            <?php echo htmlspecialchars($config['display_phone_number'] ?? 'Numéro non défini'); ?>
                                                        </h5>
                                                        <small class="text-muted">ID: <?php echo htmlspecialchars(substr($config['phone_number_id'], 0, 20)); ?>...</small>
                                                    </div>
                                                    <span class="badge bg-<?php echo $status_color; ?>"><?php echo $is_active; ?></span>
                                                </div>

                                                <div class="row g-2 mb-3">
                                                    <div class="col-6">
                                                        <small class="text-muted">Qualité</small><br>
                                                        <span class="badge <?php echo $quality_class; ?>">
                                                            <?php echo $config['quality_rating']; ?>
                                                        </span>
                                                    </div>
                                                    <div class="col-6">
                                                        <small class="text-muted">Limite/24h</small><br>
                                                        <span class="badge bg-info tier-badge"><?php echo $tier_label; ?> conversations</span>
                                                    </div>
                                                    <div class="col-6">
                                                        <small class="text-muted">Vérifié</small><br>
                                                        <?php if ($config['verified']): ?>
                                                            <i class="fas fa-check-circle text-success"></i> Oui
                                                        <?php else: ?>
                                                            <i class="fas fa-times-circle text-danger"></i> Non
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="col-6">
                                                        <small class="text-muted">Ajouté le</small><br>
                                                        <?php echo date('d/m/Y', strtotime($config['created_at'])); ?>
                                                    </div>
                                                </div>

                                                <?php if ($config['last_error']): ?>
                                                <div class="alert alert-danger alert-sm mb-3">
                                                    <small><strong>Erreur:</strong> <?php echo htmlspecialchars(substr($config['last_error'], 0, 100)); ?></small>
                                                </div>
                                                <?php endif; ?>

                                                <div class="btn-group w-100" role="group">
                                                    <a href="whatsapp-conversations.php?config_id=<?php echo $config['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-comments"></i> Conversations
                                                    </a>
                                                    <a href="whatsapp-templates.php?config_id=<?php echo $config['id']; ?>" class="btn btn-sm btn-outline-info">
                                                        <i class="fas fa-file-alt"></i> Templates
                                                    </a>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteWhatsApp(<?php echo $config['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Guide de Configuration -->
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-graduation-cap"></i> Comment connecter WhatsApp Business ?
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="whatsappGuide">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#step1">
                                        Étape 1: Créer un compte Meta Business
                                    </button>
                                </h2>
                                <div id="step1" class="accordion-collapse collapse show" data-bs-parent="#whatsappGuide">
                                    <div class="accordion-body">
                                        <ol>
                                            <li>Allez sur <a href="https://business.facebook.com" target="_blank">business.facebook.com</a></li>
                                            <li>Créez un <strong>Meta Business Manager</strong></li>
                                            <li>Vérifiez votre entreprise (requis pour WhatsApp)</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#step2">
                                        Étape 2: Créer une App Meta
                                    </button>
                                </h2>
                                <div id="step2" class="accordion-collapse collapse" data-bs-parent="#whatsappGuide">
                                    <div class="accordion-body">
                                        <ol>
                                            <li>Sur <a href="https://developers.facebook.com/apps" target="_blank">developers.facebook.com/apps</a></li>
                                            <li>Cliquez <strong>Créer une app</strong></li>
                                            <li>Sélectionnez <strong>Type: Business</strong></li>
                                            <li>Ajoutez le produit <strong>WhatsApp</strong></li>
                                        </ol>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#step3">
                                        Étape 3: Obtenir le Phone Number ID
                                    </button>
                                </h2>
                                <div id="step3" class="accordion-collapse collapse" data-bs-parent="#whatsappGuide">
                                    <div class="accordion-body">
                                        <ol>
                                            <li>Dans votre App Meta → <strong>WhatsApp → Démarrage</strong></li>
                                            <li>Ajoutez un numéro de téléphone WhatsApp Business</li>
                                            <li>Copiez le <strong>Phone Number ID</strong> (commence par 1xxxxx)</li>
                                            <li>Copiez le <strong>Business Account ID</strong></li>
                                        </ol>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#step4">
                                        Étape 4: Générer le Token d'Accès
                                    </button>
                                </h2>
                                <div id="step4" class="accordion-collapse collapse" data-bs-parent="#whatsappGuide">
                                    <div class="accordion-body">
                                        <ol>
                                            <li>Dans l'App Meta → <strong>Paramètres → Utilisateurs système</strong></li>
                                            <li>Créez un utilisateur système</li>
                                            <li>Générez un <strong>token permanent</strong> avec les permissions WhatsApp</li>
                                            <li>⚠️ Sauvegardez ce token en lieu sûr !</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ajouter WhatsApp -->
    <div class="modal fade" id="addWhatsAppModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fab fa-whatsapp"></i> Connecter WhatsApp Business
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addWhatsAppForm">
                        <div class="mb-3">
                            <label class="form-label">Phone Number ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="phone_number_id" name="phone_number_id" 
                                   placeholder="123456789012345" required>
                            <small class="text-muted">Trouvé dans Meta App → WhatsApp → Démarrage</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Business Account ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="business_account_id" name="business_account_id" 
                                   placeholder="987654321098765" required>
                            <small class="text-muted">Trouvé dans Meta Business Manager</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Numéro de Téléphone <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="display_phone_number" name="display_phone_number" 
                                   placeholder="+33612345678" required>
                            <small class="text-muted">Format international avec +</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Access Token Permanent <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="access_token" name="access_token" rows="3" 
                                      placeholder="EAAxxxxxxxxxxxxx" required></textarea>
                            <small class="text-muted">Token généré depuis Utilisateurs système (ne jamais partager !)</small>
                        </div>

                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Important:</strong> Le token sera chiffré avec AES-256 avant stockage.
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn whatsapp-btn" onclick="saveWhatsApp()">
                        <i class="fab fa-whatsapp"></i> Connecter
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function saveWhatsApp() {
        const form = document.getElementById('addWhatsAppForm');
        const formData = new FormData(form);
        formData.append('action', 'add');

        fetch('api/whatsapp-integration.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ WhatsApp connecté avec succès !');
                location.reload();
            } else {
                alert('❌ Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ Erreur lors de la connexion WhatsApp');
        });
    }

    function deleteWhatsApp(configId) {
        if (!confirm('⚠️ Supprimer ce numéro WhatsApp ? Toutes les conversations et templates seront perdus.')) return;

        fetch('api/whatsapp-integration.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                action: 'delete', 
                config_id: configId 
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Numéro supprimé');
                location.reload();
            } else {
                alert('❌ Erreur: ' + data.message);
            }
        });
    }
    </script>
</body>
</html>
