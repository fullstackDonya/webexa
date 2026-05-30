<?php
/**
 * Page de réparation rapide de la configuration email
 * Permet de corriger une configuration incomplète
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/env.php';
loadEnv(__DIR__ . '/.env');
require_once __DIR__ . '/includes/EmailCrypto.php';

session_start();

$customer_id = $_SESSION['customer_id'] ?? null;

if (!$customer_id) {
    die("Erreur: Non authentifié");
}

$message = null;
$error = null;

// Récupérer les configurations incomplètes
$stmt = $pdo->prepare("
    SELECT * FROM email_configurations 
    WHERE customer_id = ? 
    AND (imap_server IS NULL OR imap_server = '' OR smtp_host IS NULL OR smtp_host = '')
");
$stmt->execute([$customer_id]);
$incompleteConfigs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traiter le formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $config_id = intval($_POST['config_id'] ?? 0);
    $imap_server = trim($_POST['imap_server'] ?? '');
    $imap_port = intval($_POST['imap_port'] ?? 993);
    $smtp_host = trim($_POST['smtp_host'] ?? '');
    $smtp_port = intval($_POST['smtp_port'] ?? 587);
    $provider = $_POST['provider'] ?? 'custom';
    
    if ($config_id && $imap_server && $smtp_host) {
        try {
            $stmt = $pdo->prepare("
                UPDATE email_configurations 
                SET imap_server = ?, imap_port = ?, smtp_host = ?, smtp_port = ?, provider = ?
                WHERE id = ? AND customer_id = ?
            ");
            $stmt->execute([$imap_server, $imap_port, $smtp_host, $smtp_port, $provider, $config_id, $customer_id]);
            
            $message = "✓ Configuration mise à jour avec succès !";
            
            // Recharger les configurations
            $stmt = $pdo->prepare("
                SELECT * FROM email_configurations 
                WHERE customer_id = ? 
                AND (imap_server IS NULL OR imap_server = '' OR smtp_host IS NULL OR smtp_host = '')
            ");
            $stmt->execute([$customer_id]);
            $incompleteConfigs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $error = "Erreur lors de la mise à jour: " . $e->getMessage();
        }
    } else {
        $error = "Veuillez remplir tous les champs obligatoires";
    }
}

// Templates de configuration
$presets = [
    'gmail' => [
        'name' => 'Gmail',
        'imap_server' => 'imap.gmail.com',
        'imap_port' => 993,
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => 587
    ],
    'outlook' => [
        'name' => 'Outlook / Office365',
        'imap_server' => 'outlook.office365.com',
        'imap_port' => 993,
        'smtp_host' => 'smtp.office365.com',
        'smtp_port' => 587
    ],
    'ovh' => [
        'name' => 'OVH',
        'imap_server' => 'ssl0.ovh.net',
        'imap_port' => 993,
        'smtp_host' => 'ssl0.ovh.net',
        'smtp_port' => 587
    ],
    'webitech' => [
        'name' => 'Webitech',
        'imap_server' => 'mail.webitech.fr',
        'imap_port' => 993,
        'smtp_host' => 'mail.webitech.fr',
        'smtp_port' => 587
    ],
    'hostinger' => [
        'name' => 'Hostinger',
        'imap_server' => 'imap.hostinger.com',
        'imap_port' => 993,
        'smtp_host' => 'smtp.hostinger.com',
        'smtp_port' => 465
    ]
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réparer Configuration Email - CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .preset-card {
            cursor: pointer;
            transition: all 0.2s;
            border: 2px solid #dee2e6;
        }
        .preset-card:hover {
            border-color: #0d6efd;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .preset-card.selected {
            border-color: #0d6efd;
            background: #e7f3ff;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-wrench"></i> Réparer Configuration Email</h1>
                    <a href="email-diagnostic.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Retour au diagnostic
                    </a>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo htmlspecialchars($message); ?>
                        <hr>
                        <p class="mb-0">
                            <strong>Prochaine étape:</strong> 
                            <a href="email-reset-password.php" class="btn btn-warning btn-sm">
                                <i class="fas fa-key"></i> Configurer le mot de passe
                            </a>
                            <span class="text-muted">ou</span>
                            <a href="email-diagnostic.php" class="btn btn-info btn-sm">
                                <i class="fas fa-stethoscope"></i> Tester la connexion
                            </a>
                        </p>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (empty($incompleteConfigs)): ?>
                    
                    <div class="alert alert-success">
                        <h5><i class="fas fa-check-circle"></i> Toutes les configurations sont complètes !</h5>
                        <p class="mb-0">Vous pouvez maintenant <a href="email-inbox.php">synchroniser vos emails</a>.</p>
                    </div>
                    
                <?php else: ?>
                    
                    <div class="alert alert-warning">
                        <h5><i class="fas fa-exclamation-triangle"></i> Configuration(s) incomplète(s)</h5>
                        <p class="mb-0">Les serveurs IMAP/SMTP ne sont pas configurés. Complétez-les ci-dessous.</p>
                    </div>
                    
                    <?php foreach ($incompleteConfigs as $config): ?>
                        
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($config['email']); ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                
                                <h6 class="mb-3">Choisissez un modèle prédéfini :</h6>
                                
                                <div class="row mb-4">
                                    <?php foreach ($presets as $key => $preset): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="preset-card card h-100" 
                                                 onclick="applyPreset('<?php echo $key; ?>', <?php echo $config['id']; ?>)">
                                                <div class="card-body">
                                                    <h6><?php echo $preset['name']; ?></h6>
                                                    <small class="text-muted">
                                                        IMAP: <?php echo $preset['imap_server']; ?>:<?php echo $preset['imap_port']; ?><br>
                                                        SMTP: <?php echo $preset['smtp_host']; ?>:<?php echo $preset['smtp_port']; ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <hr>
                                
                                <h6 class="mb-3">Ou configurez manuellement :</h6>
                                
                                <form method="POST" id="form-<?php echo $config['id']; ?>">
                                    <input type="hidden" name="config_id" value="<?php echo $config['id']; ?>">
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Serveur IMAP *</label>
                                            <input type="text" class="form-control" name="imap_server" 
                                                   id="imap_server_<?php echo $config['id']; ?>"
                                                   placeholder="imap.gmail.com" required
                                                   value="<?php echo htmlspecialchars($config['imap_server'] ?? ''); ?>">
                                            <small class="text-muted">Ex: imap.gmail.com, outlook.office365.com</small>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Port IMAP *</label>
                                            <input type="number" class="form-control" name="imap_port" 
                                                   id="imap_port_<?php echo $config['id']; ?>"
                                                   value="<?php echo $config['imap_port'] ?: 993; ?>" required>
                                            <small class="text-muted">Généralement 993 (SSL)</small>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Serveur SMTP *</label>
                                            <input type="text" class="form-control" name="smtp_host" 
                                                   id="smtp_host_<?php echo $config['id']; ?>"
                                                   placeholder="smtp.gmail.com" required
                                                   value="<?php echo htmlspecialchars($config['smtp_host'] ?? ''); ?>">
                                            <small class="text-muted">Ex: smtp.gmail.com, smtp.office365.com</small>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Port SMTP *</label>
                                            <input type="number" class="form-control" name="smtp_port" 
                                                   id="smtp_port_<?php echo $config['id']; ?>"
                                                   value="<?php echo $config['smtp_port'] ?: 587; ?>" required>
                                            <small class="text-muted">Généralement 587 (TLS)</small>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Type de fournisseur</label>
                                        <select class="form-control" name="provider" id="provider_<?php echo $config['id']; ?>">
                                            <option value="custom" <?php echo ($config['provider'] ?? 'custom') === 'custom' ? 'selected' : ''; ?>>Custom</option>
                                            <option value="gmail" <?php echo ($config['provider'] ?? '') === 'gmail' ? 'selected' : ''; ?>>Gmail</option>
                                            <option value="outlook" <?php echo ($config['provider'] ?? '') === 'outlook' ? 'selected' : ''; ?>>Outlook</option>
                                        </select>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Enregistrer la configuration
                                    </button>
                                </form>
                                
                            </div>
                        </div>
                        
                    <?php endforeach; ?>
                    
                <?php endif; ?>
                
                <div class="mt-4">
                    <a href="email-settings.php" class="btn btn-outline-secondary">
                        <i class="fas fa-cog"></i> Paramètres Email Complets
                    </a>
                    <a href="email-inbox.php" class="btn btn-success">
                        <i class="fas fa-inbox"></i> Accéder à la Boîte de Réception
                    </a>
                </div>
                
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    const presets = <?php echo json_encode($presets); ?>;
    
    function applyPreset(presetKey, configId) {
        const preset = presets[presetKey];
        if (!preset) return;
        
        // Remplir les champs
        document.getElementById('imap_server_' + configId).value = preset.imap_server;
        document.getElementById('imap_port_' + configId).value = preset.imap_port;
        document.getElementById('smtp_host_' + configId).value = preset.smtp_host;
        document.getElementById('smtp_port_' + configId).value = preset.smtp_port;
        document.getElementById('provider_' + configId).value = presetKey;
        
        // Highlight visuel
        document.querySelectorAll('.preset-card').forEach(card => card.classList.remove('selected'));
        event.currentTarget.classList.add('selected');
        
        // Scroll vers le formulaire
        document.getElementById('form-' + configId).scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    </script>
</body>
</html>
