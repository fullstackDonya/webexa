<?php
include 'includes/verify_subscriptions.php';
require_once __DIR__ . '/includes/env.php';
loadEnv(__DIR__ . '/.env');

$page_title = "Paramètres Email";
$customer_id = $_SESSION['customer_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;
$tables_exist = false;
$error_message = null;

if (!$customer_id) {
    header('Location: index.php?error=customer');
    exit;
}

// Vérifier si les tables existent
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'email_configurations'");
    $tables_exist = $stmt->rowCount() > 0;
    
    if (!$tables_exist) {
        $error_message = "⚠️ Les tables email ne sont pas créées. Veuillez exécuter le script SQL: <code>database/email_tables.sql</code>";
    }
} catch (Exception $e) {
    error_log("Table check error: " . $e->getMessage());
    $tables_exist = false;
}

// Récupérer les configurations email existantes
$email_configs = [];
try {
    if ($tables_exist) {
        $stmt = $pdo->prepare("
            SELECT * FROM email_configurations 
            WHERE customer_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$customer_id]);
        $email_configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    error_log("Email configs fetch error: " . $e->getMessage());
}

// Récupérer le statut de synchronisation
$sync_stats = ['synced_emails' => 0];
try {
    if ($tables_exist) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as synced_emails FROM emails 
            WHERE config_id IN (SELECT id FROM email_configurations WHERE customer_id = ?)
        ");
        $stmt->execute([$customer_id]);
        $sync_stats = $stmt->fetch();
    }
} catch (Exception $e) {
    error_log("Sync stats error: " . $e->getMessage());
}

$page_title = "Configuration Email - CRM";
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
    <link href="assets/css/email-settings.css" rel="stylesheet">
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        <?php include 'includes/topbar.php'; ?>
        
        <div class="main-content">
            <div class="container-fluid">

                <!-- Page Header -->
                <div class="page-header">
                    <h1>
                        <i class="fas fa-envelope"></i> Configuration Email
                    </h1>
                    <div class="btn-group">
                        <a href="email-inbox.php" class="btn btn-primary">
                            <i class="fas fa-inbox"></i> Boîte de Réception
                        </a>
                        <button class="btn btn-success" onclick="connectGmail()">
                            <i class="fab fa-google"></i> Connecter Gmail
                        </button>
                        <button class="btn btn-info" onclick="connectOutlook()">
                            <i class="fab fa-microsoft"></i> Connecter Outlook
                        </button>
                        <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#addEmailModal">
                            <i class="fas fa-server"></i> IMAP/SMTP Manuel
                        </button>
                    </div>
                </div>

                <!-- Alertes -->
                <div id="alerts-container"></div>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>✓ Succès!</strong><br>
                        <?php echo htmlspecialchars($_SESSION['success_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>✗ Erreur!</strong><br>
                        <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <strong>⚠️ Configuration requise</strong><br>
                        Les tables email ne sont pas encore créées dans la base de données.<br>
                        <strong>Étapes pour corriger:</strong>
                        <ol style="margin-top: 10px; margin-bottom: 0;">
                            <li>Ouvre un terminal</li>
                            <li>Exécute: <code style="background: #f0f0f0; padding: 5px 10px; border-radius: 3px;">cd /Applications/MAMP/htdocs/PP/webitech/WEB/crm && mysql -u root webitech < database/email_tables.sql</code></li>
                            <li>Rafraîchis cette page</li>
                        </ol>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Info Message -->
                <div class="info-box">
                    <div class="info-content">
                        <i class="fas fa-info-circle"></i>
                        <div>
                            <strong>Connexion Email Optionnelle</strong>
                            <p>La gestion d'emails est optionnelle. Le système CRM fonctionne complètement sans cette fonction.</p>
                        </div>
                    </div>
                </div>

                <!-- Statistiques -->
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo count($email_configs); ?></div>
                        <div class="stat-label">Comptes Configurés</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $sync_stats['synced_emails'] ?? 0; ?></div>
                        <div class="stat-label">Emails Synchronisés</div>
                    </div>
                </div>

                <!-- Configuration List -->
                <div class="config-section">
                    <div class="config-header">
                        <h5><i class="fas fa-cogs"></i> Comptes Email Connectés</h5>
                    </div>

                    <?php if (empty($email_configs)): ?>
                        <div class="no-data">
                            <i class="fas fa-inbox"></i>
                            <p>Aucun compte email configuré</p>
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addEmailModal">
                                <i class="fas fa-plus"></i> Ajouter un Email
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="configs-grid">
                            <?php foreach ($email_configs as $config): 
                                $provider_icon = match($config['provider']) {
                                    'gmail' => 'fab fa-google',
                                    'outlook' => 'fab fa-microsoft',
                                    'hostinger' => 'fas fa-server',
                                    default => 'fas fa-envelope'
                                };
                                
                                $provider_label = ucfirst($config['provider']);
                                $is_active = $config['is_active'] ? 'active' : 'inactive';
                                $status_color = $config['is_active'] ? 'success' : 'secondary';
                                
                                // Token status - ne vérifier que si OAuth2 est utilisé
                                $authMethod = $config['auth_method'] ?? $config['connection_method'] ?? 'password';
                                $tokenStatus = 'N/A';
                                $tokenColor = 'secondary';
                                
                                if ($authMethod === 'oauth' || ($config['oauth_provider'] && $config['oauth_provider'] !== 'none' && !in_array($authMethod, ['password', 'app_password']))) {
                                    $tokenStatus = 'OK';
                                    $tokenColor = 'success';
                                    $expiresAt = strtotime($config['oauth_token_expires_at'] ?? 'now');
                                    if ($expiresAt < time()) {
                                        $tokenStatus = 'Expiré';
                                        $tokenColor = 'danger';
                                    } elseif ($expiresAt < time() + 3600) {
                                        $tokenStatus = 'Expire bientôt';
                                        $tokenColor = 'warning';
                                    }
                                }
                                
                                $connectionMethod = match($authMethod) {
                                    'oauth' => '🔐 OAuth2',
                                    'app_password' => '🔑 Mot de passe app',
                                    'password' => '🔓 Mot de passe',
                                    default => '🔓 Mot de passe'
                                };
                            ?>
                                <div class="config-card <?php echo $is_active; ?>">
                                    <div class="config-header-card">
                                        <div class="config-icon">
                                            <i class="<?php echo $provider_icon; ?>"></i>
                                        </div>
                                        <div class="config-title">
                                            <h6><?php echo htmlspecialchars($config['email']); ?></h6>
                                            <small><?php echo $provider_label; ?></small>
                                        </div>
                                        <span class="badge bg-<?php echo $status_color; ?>">
                                            <?php echo $config['is_active'] ? 'Actif' : 'Inactif'; ?>
                                        </span>
                                    </div>

                                    <div class="config-body">
                                        <div class="config-info">
                                            <div class="info-item">
                                                <span class="label">Méthode:</span>
                                                <span class="value"><?php echo $connectionMethod; ?></span>
                                            </div>
                                            <div class="info-item">
                                                <span class="label">Token:</span>
                                                <span class="value">
                                                    <span class="badge bg-<?php echo $tokenColor; ?>"><?php echo $tokenStatus; ?></span>
                                                </span>
                                            </div>
                                            <div class="info-item">
                                                <span class="label">Dernière Sync:</span>
                                                <span class="value">
                                                    <?php echo $config['last_sync'] ? date('d/m/Y H:i', strtotime($config['last_sync'])) : 'Jamais'; ?>
                                                </span>
                                            </div>
                                            <?php if ($config['last_error']): ?>
                                            <div class="info-item">
                                                <span class="label">Erreur:</span>
                                                <span class="value text-danger">
                                                    <?php echo htmlspecialchars(substr($config['last_error'], 0, 100)); ?>
                                                    <?php if ($config['provider'] === 'gmail' && (strpos($config['last_error'], 'Application-specific') !== false || strpos($config['last_error'], 'authentication') !== false)): ?>
                                                        <br><a href="email-gmail-fix.php" class="badge bg-warning text-dark">
                                                            <i class="fas fa-tools"></i> Résoudre maintenant
                                                        </a>
                                                    <?php elseif ($config['provider'] === 'outlook' && (strpos($config['last_error'], 'authentication') !== false || strpos($config['last_error'], 'password') !== false)): ?>
                                                        <br><a href="email-outlook-fix.php" class="badge bg-warning text-dark">
                                                            <i class="fas fa-tools"></i> Résoudre maintenant
                                                        </a>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="config-actions">
                                        <button class="btn btn-sm btn-outline-primary" onclick="syncEmails(<?php echo $config['id']; ?>)">
                                            <i class="fas fa-sync"></i> Synchroniser
                                        </button>
                                        <?php if ($tokenStatus === 'Expiré' && $config['provider'] === 'gmail'): ?>
                                        <a href="email-gmail-fix.php" class="btn btn-sm btn-warning">
                                            <i class="fas fa-wrench"></i> Résoudre
                                        </a>
                                        <?php elseif ($tokenStatus === 'Expiré' && $config['provider'] === 'outlook'): ?>
                                        <a href="email-outlook-fix.php" class="btn btn-sm btn-warning">
                                            <i class="fas fa-wrench"></i> Résoudre
                                        </a>
                                        <?php elseif ($tokenStatus === 'Expiré' && $authMethod === 'oauth'): ?>
                                        <button class="btn btn-sm btn-outline-warning" onclick="reconnectEmail(<?php echo $config['id']; ?>, '<?php echo $config['oauth_provider']; ?>')">
                                            <i class="fas fa-redo"></i> Reconnecter
                                        </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteEmail(<?php echo $config['id']; ?>)">
                                            <i class="fas fa-trash"></i> Supprimer
                                        </button>
                                    </div>
                                    
                                    <!-- Outils de diagnostic et réparation -->
                                    <div class="config-tools mt-2 pt-2" style="border-top: 1px solid #e9ecef;">
                                        <small class="text-muted">
                                            <i class="fas fa-tools"></i> Outils : 
                                            <a href="email-diagnostic.php" class="text-decoration-none">
                                                <i class="fas fa-stethoscope"></i> Diagnostic
                                            </a>
                                            <span class="mx-1">•</span>
                                            <?php if ($config['provider'] === 'gmail'): ?>
                                            <a href="email-gmail-fix.php" class="text-decoration-none">
                                                <i class="fab fa-google"></i> Configuration Gmail
                                            </a>
                                            <?php elseif ($config['provider'] === 'outlook'): ?>
                                            <a href="email-outlook-fix.php" class="text-decoration-none">
                                                <i class="fab fa-microsoft"></i> Configuration Outlook
                                            </a>
                                            <?php else: ?>
                                            <a href="email-fix-config.php" class="text-decoration-none">
                                                <i class="fas fa-wrench"></i> Réparer Config
                                            </a>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- FAQ Section -->
                <div class="faq-section">
                    <h5><i class="fas fa-question-circle"></i> FAQ - Configuration Email</h5>
                    
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    Comment configurer Gmail?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <ol>
                                        <li>Allez sur <strong>myaccount.google.com</strong></li>
                                        <li>Cliquez sur <strong>Sécurité</strong></li>
                                        <li>Activez l'authentification à deux facteurs</li>
                                        <li>Générez un <strong>mot de passe d'application</strong></li>
                                        <li>Utilisez ce mot de passe pour la connexion</li>
                                    </ol>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    Comment configurer Outlook?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <ol>
                                        <li>Allez sur <strong>account.microsoft.com</strong></li>
                                        <li>Allez à <strong>Sécurité</strong></li>
                                        <li>Créez un <strong>mot de passe d'application</strong></li>
                                        <li>Serveur IMAP: <strong>outlook.office365.com</strong></li>
                                        <li>Serveur SMTP: <strong>smtp.office365.com</strong> (Port 587)</li>
                                    </ol>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    Comment configurer Hostinger?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <ol>
                                        <li>Allez sur votre <strong>Panneau Hostinger</strong></li>
                                        <li>Trouvez les <strong>paramètres Email</strong></li>
                                        <li>Serveur IMAP: <strong>imap.hostinger.com</strong> (Port 993)</li>
                                        <li>Serveur SMTP: <strong>smtp.hostinger.com</strong> (Port 465/587)</li>
                                        <li>Utilisez votre email complet et mot de passe</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ajouter Email -->
    <div class="modal fade" id="addEmailModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter un Compte Email</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addEmailForm">
                        <div class="mb-3">
                            <label class="form-label">Fournisseur Email</label>
                            <select class="form-select" id="provider" name="provider" required>
                                <option value="">-- Sélectionner --</option>
                                <option value="gmail">Gmail</option>
                                <option value="outlook">Outlook/Office 365</option>
                                <option value="hostinger">Hostinger</option>
                                <option value="custom">Personnalisé (IMAP/SMTP)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Adresse Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Mot de Passe</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <small class="text-muted">Ne sera pas stocké en clair, chiffré sécurisé</small>
                        </div>

                        <div id="customSettings" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Serveur IMAP</label>
                                <input type="text" class="form-control" id="imap_server" name="imap_server" placeholder="imap.example.com">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Port IMAP</label>
                                <input type="number" class="form-control" id="imap_port" name="imap_port" placeholder="993">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Serveur SMTP</label>
                                <input type="text" class="form-control" id="smtp_host" name="smtp_host" placeholder="smtp.example.com">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Port SMTP</label>
                                <input type="number" class="form-control" id="smtp_port" name="smtp_port" placeholder="587">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Utilisateur SMTP</label>
                                <input type="text" class="form-control" id="smtp_user" name="smtp_user" placeholder="user@example.com">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email d'envoi (FROM)</label>
                                <input type="email" class="form-control" id="smtp_from" name="smtp_from" placeholder="from@example.com">
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="sync_leads" name="sync_leads">
                            <label class="form-check-label" for="sync_leads">
                                Synchroniser les leads depuis les emails
                            </label>
                        </div>

                        <div class="alert alert-info" id="smtpHelpBlock" style="display:none;">
                            <strong>Aide configuration SMTP&nbsp;:</strong><br>
                            <ul style="margin-bottom:0;">
                                <li>Pour Gmail&nbsp;: <br>
                                    <b>Serveur SMTP</b> : smtp.gmail.com<br>
                                    <b>Port</b> : 587 (TLS)<br>
                                    <b>Utilisateur</b> : votre adresse Gmail complète<br>
                                    <b>Mot de passe</b> : <u>mot de passe d'application</u> (obligatoire si la double authentification est activée)<br>
                                    <a href="https://myaccount.google.com/apppasswords" target="_blank">Générer un mot de passe d'application</a>
                                </li>
                                <li>Pour Outlook/Office 365&nbsp;: <br>
                                    <b>Serveur SMTP</b> : smtp.office365.com<br>
                                    <b>Port</b> : 587 (TLS)<br>
                                    <b>Utilisateur</b> : votre adresse email complète<br>
                                    <b>Mot de passe</b> : <u>mot de passe d'application</u> si la double authentification est activée<br>
                                    <a href="https://account.live.com/proofs/AppPassword" target="_blank">Générer un mot de passe d'application</a>
                                </li>
                                <li>Pour Hostinger&nbsp;: <br>
                                    <b>Serveur SMTP</b> : smtp.hostinger.com<br>
                                    <b>Port</b> : 465 (SSL) ou 587 (TLS)<br>
                                    <b>Utilisateur</b> : votre adresse email complète<br>
                                    <b>Mot de passe</b> : votre mot de passe email</li>
                            </ul>
                            <span class="text-muted">Le mot de passe est chiffré et jamais affiché en clair.</span>
                        </div>
                        // Afficher l'aide SMTP uniquement si custom
                        document.getElementById('provider')?.addEventListener('change', function() {
                            const smtpHelp = document.getElementById('smtpHelpBlock');
                            if (this.value === 'custom') {
                                smtpHelp.style.display = 'block';
                            } else {
                                smtpHelp.style.display = 'none';
                            }
                        });
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" onclick="saveEmail()">Connecter</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Connecter Gmail via OAuth
    function connectGmail() {
        fetch('oauth/google/connect.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.auth_url;
                } else {
                    alert('Erreur: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erreur lors de la connexion Gmail');
            });
    }

    // Connecter Outlook via OAuth
    function connectOutlook() {
        fetch('oauth/microsoft/connect.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.auth_url;
                } else {
                    alert('Erreur: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erreur lors de la connexion Outlook');
            });
    }

    // Reconnecter un compte OAuth expiré
    function reconnectEmail(configId, provider) {
        if (!confirm('Reconnecter ce compte?')) return;
        
        const endpoint = provider === 'google' ? 'oauth/google/connect.php' : 'oauth/microsoft/connect.php';
        
        fetch(endpoint)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.auth_url;
                } else {
                    alert('Erreur: ' + data.error);
                }
            });
    }

    // Afficher/masquer les paramètres personnalisés

    document.getElementById('provider')?.addEventListener('change', function() {
        const customSettings = document.getElementById('customSettings');
        if (this.value === 'custom') {
            customSettings.style.display = 'block';
            // Préremplir smtp_from avec l'email saisi si vide
            const emailInput = document.getElementById('email');
            const smtpFromInput = document.getElementById('smtp_from');
            if (emailInput && smtpFromInput && !smtpFromInput.value) {
                smtpFromInput.value = emailInput.value;
            }
        } else {
            customSettings.style.display = 'none';
        }
    });

    // Préremplir smtp_from à la saisie de l'email si vide
    document.getElementById('email')?.addEventListener('input', function() {
        const smtpFromInput = document.getElementById('smtp_from');
        if (smtpFromInput && !smtpFromInput.value) {
            smtpFromInput.value = this.value;
        }
    });

    function saveEmail() {
        const form = document.getElementById('addEmailForm');
        const formData = new FormData(form);

        fetch('api/email-integration.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Email configuré avec succès!');
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de la configuration');
        });
    }

    function syncEmails(configId) {
        if (!confirm('Synchroniser les emails? Cela peut prendre quelques minutes.')) return;

        fetch('api/email-integration.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'sync', config_id: configId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Synchronisation lancée! ' + data.message);
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        });
    }

    function deleteEmail(configId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer ce compte?')) return;

        fetch('api/email-integration.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', config_id: configId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Compte supprimé');
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        });
    }

    // Fonction d'édition retirée - utiliser reconnectEmail pour OAuth ou reconfigurer manuellement
    </script>
</body>
</html>