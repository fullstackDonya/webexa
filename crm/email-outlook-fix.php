<?php
/**
 * Guide de résolution pour les problèmes Outlook
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

// Récupérer les configurations Outlook
$stmt = $pdo->prepare("
    SELECT * FROM email_configurations 
    WHERE customer_id = ? AND provider = 'outlook'
");
$stmt->execute([$customer_id]);
$outlookConfigs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traiter le formulaire de mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $config_id = intval($_POST['config_id'] ?? 0);
    $app_password = $_POST['app_password'] ?? '';
    
    if ($config_id && $app_password) {
        try {
            $crypto = new EmailCrypto();
            $encryptedPassword = $crypto->encrypt($app_password);
            
            // Mettre à jour avec mot de passe d'application (désactiver OAuth2)
            $stmt = $pdo->prepare("
                UPDATE email_configurations 
                SET password = ?,
                    auth_method = 'password',
                    connection_method = 'password',
                    oauth_provider = NULL,
                    oauth_access_token = NULL,
                    oauth_refresh_token = NULL,
                    oauth_token_expires = NULL,
                    oauth_token_expires_at = NULL,
                    imap_host = 'outlook.office365.com',
                    imap_port = 993,
                    smtp_host = 'smtp.office365.com',
                    smtp_port = 587
                WHERE id = ? AND customer_id = ?
            ");
            $stmt->execute([$encryptedPassword, $config_id, $customer_id]);
            
            $message = "✓ Mot de passe d'application configuré avec succès !";
            
            // Recharger les configs
            $stmt = $pdo->prepare("
                SELECT * FROM email_configurations 
                WHERE customer_id = ? AND provider = 'outlook'
            ");
            $stmt->execute([$customer_id]);
            $outlookConfigs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $error = "Erreur: " . $e->getMessage();
        }
    } else {
        $error = "Veuillez remplir tous les champs";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résoudre Problème Outlook - CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .step-card {
            border-left: 4px solid #0078d4;
            margin-bottom: 20px;
        }
        .step-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            background: #0078d4;
            color: white;
            border-radius: 50%;
            font-weight: bold;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fab fa-microsoft"></i> Résoudre Problème Outlook</h1>
                    <a href="email-settings.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
                
                <div class="alert alert-info">
                    <h5><i class="fas fa-info-circle"></i> Configuration Outlook / Microsoft 365</h5>
                    <p class="mb-0">
                        Pour connecter votre compte Outlook, vous devez utiliser un <strong>mot de passe d'application</strong> 
                        si l'authentification à deux facteurs est activée.
                    </p>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo htmlspecialchars($message); ?>
                        <hr>
                        <p class="mb-0">
                            <a href="email-diagnostic.php" class="btn btn-sm btn-primary">
                                <i class="fas fa-check"></i> Tester la connexion
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
                
                <div class="card step-card">
                    <div class="card-body">
                        <h5><span class="step-number">1</span> Créer un Mot de Passe d'Application Microsoft</h5>
                        <p>Pour les comptes Microsoft avec authentification à deux facteurs :</p>
                        
                        <ol>
                            <li>Allez sur <a href="https://account.microsoft.com/security" target="_blank" class="btn btn-sm btn-primary">
                                <i class="fas fa-external-link-alt"></i> Sécurité Microsoft
                            </a></li>
                            <li>Cliquez sur <strong>"Options de sécurité avancées"</strong></li>
                            <li>Sous <strong>"Mots de passe d'application"</strong>, cliquez sur <strong>"Créer un mot de passe d'application"</strong></li>
                            <li>Donnez un nom (ex: "CRM Webitech")</li>
                            <li><strong>Copiez le mot de passe généré</strong> (il ne sera affiché qu'une fois)</li>
                        </ol>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> <strong>Note:</strong> 
                            Si vous n'avez pas activé l'authentification à deux facteurs, vous pouvez utiliser votre mot de passe 
                            Outlook habituel, mais nous recommandons fortement d'activer la 2FA et d'utiliser un mot de passe d'application.
                        </div>
                    </div>
                </div>
                
                <div class="card step-card">
                    <div class="card-body">
                        <h5><span class="step-number">2</span> Configurer le Mot de Passe d'Application</h5>
                        
                        <?php if (empty($outlookConfigs)): ?>
                            <div class="alert alert-warning">
                                Aucun compte Outlook trouvé. <a href="email-settings.php">Ajoutez-en un</a>.
                            </div>
                        <?php else: ?>
                            <?php foreach ($outlookConfigs as $config): ?>
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <i class="fab fa-microsoft"></i> <?php echo htmlspecialchars($config['email']); ?>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST">
                                            <input type="hidden" name="config_id" value="<?php echo $config['id']; ?>">
                                            
                                            <div class="mb-3">
                                                <label class="form-label">
                                                    <i class="fas fa-key"></i> Mot de passe d'application ou mot de passe Outlook *
                                                </label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control" 
                                                           name="app_password" 
                                                           id="app_password_<?php echo $config['id']; ?>"
                                                           placeholder="Votre mot de passe d'application" 
                                                           required>
                                                    <button class="btn btn-outline-secondary" type="button" 
                                                            onclick="togglePassword(<?php echo $config['id']; ?>)">
                                                        <i class="fas fa-eye" id="eye_<?php echo $config['id']; ?>"></i>
                                                    </button>
                                                </div>
                                                <small class="text-muted">
                                                    Entrez le mot de passe d'application généré ou votre mot de passe Outlook habituel
                                                </small>
                                            </div>
                                            
                                            <div class="alert alert-info">
                                                <strong>Paramètres IMAP/SMTP automatiques :</strong>
                                                <ul class="mb-0 mt-2">
                                                    <li>IMAP: outlook.office365.com:993 (SSL)</li>
                                                    <li>SMTP: smtp.office365.com:587 (STARTTLS)</li>
                                                </ul>
                                            </div>
                                            
                                            <div class="alert alert-warning">
                                                <i class="fas fa-shield-alt"></i> 
                                                Le mot de passe sera chiffré avec AES-256-CBC avant d'être stocké.
                                            </div>
                                            
                                            <button type="submit" name="update_password" class="btn btn-success">
                                                <i class="fas fa-save"></i> Enregistrer le Mot de Passe
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card step-card">
                    <div class="card-body">
                        <h5><span class="step-number">3</span> Tester la Connexion</h5>
                        <p>Une fois le mot de passe configuré :</p>
                        <ol>
                            <li>Allez sur <a href="email-diagnostic.php">la page de diagnostic</a></li>
                            <li>Cliquez sur <strong>"Tester la connexion IMAP"</strong></li>
                            <li>Vérifiez que la connexion fonctionne</li>
                            <li>Retournez sur <a href="email-inbox.php">la boîte de réception</a> et cliquez sur <strong>"Synchroniser"</strong></li>
                        </ol>
                    </div>
                </div>
                
                <div class="card border-info">
                    <div class="card-header bg-info text-white">
                        <i class="fas fa-question-circle"></i> Aide Supplémentaire
                    </div>
                    <div class="card-body">
                        <h6>Problèmes courants avec Outlook</h6>
                        
                        <div class="mb-3">
                            <strong>Erreur "Authentification échouée"</strong>
                            <p>Vérifiez que :</p>
                            <ul>
                                <li>L'authentification à deux facteurs est activée</li>
                                <li>Vous utilisez un mot de passe d'application (pas le mot de passe principal)</li>
                                <li>Le mot de passe d'application n'a pas été révoqué</li>
                            </ul>
                        </div>
                        
                        <div class="mb-3">
                            <strong>Erreur "IMAP n'est pas activé"</strong>
                            <p>Activez IMAP depuis les paramètres Outlook :</p>
                            <ul>
                                <li>Allez sur <a href="https://outlook.office365.com" target="_blank">Outlook Web</a></li>
                                <li>Paramètres → Afficher tous les paramètres Outlook</li>
                                <li>Courrier → Synchronisation de messagerie</li>
                                <li>Activez <strong>"Autoriser les appareils et les applications à utiliser POP"</strong> et <strong>IMAP</strong></li>
                            </ul>
                        </div>
                        
                        <h6>Liens utiles</h6>
                        <ul>
                            <li><a href="https://support.microsoft.com/fr-fr/account-billing/g%C3%A9rer-les-mots-de-passe-d-application-d06b7d09-cb4b-47d8-97a0-1fc5eeb2b628" target="_blank">
                                Guide officiel Microsoft sur les mots de passe d'application
                            </a></li>
                            <li><a href="https://support.microsoft.com/fr-fr/office/param%C3%A8tres-pop-imap-et-smtp-pour-outlook-com-d088b986-291d-42b8-9564-9c414e2aa040" target="_blank">
                                Paramètres POP, IMAP et SMTP pour Outlook.com
                            </a></li>
                        </ul>
                    </div>
                </div>
                
                <div class="mt-4 d-flex gap-2">
                    <a href="email-settings.php" class="btn btn-outline-secondary">
                        <i class="fas fa-cog"></i> Paramètres Email
                    </a>
                    <a href="email-diagnostic.php" class="btn btn-info">
                        <i class="fas fa-stethoscope"></i> Diagnostic
                    </a>
                    <a href="email-inbox.php" class="btn btn-success">
                        <i class="fas fa-inbox"></i> Boîte de Réception
                    </a>
                </div>
                
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function togglePassword(configId) {
        const input = document.getElementById('app_password_' + configId);
        const eye = document.getElementById('eye_' + configId);
        
        if (input.type === 'password') {
            input.type = 'text';
            eye.classList.remove('fa-eye');
            eye.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            eye.classList.remove('fa-eye-slash');
            eye.classList.add('fa-eye');
        }
    }
    </script>
</body>
</html>
