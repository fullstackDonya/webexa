<?php
/**
 * Guide de résolution pour les problèmes Gmail
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

// Récupérer les configurations Gmail
$stmt = $pdo->prepare("
    SELECT * FROM email_configurations 
    WHERE customer_id = ? AND provider = 'gmail'
");
$stmt->execute([$customer_id]);
$gmailConfigs = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                    oauth_token_expires_at = NULL
                WHERE id = ? AND customer_id = ?
            ");
            $stmt->execute([$encryptedPassword, $config_id, $customer_id]);
            
            $message = "✓ Mot de passe d'application configuré avec succès !";
            
            // Recharger les configs
            $stmt = $pdo->prepare("
                SELECT * FROM email_configurations 
                WHERE customer_id = ? AND provider = 'gmail'
            ");
            $stmt->execute([$customer_id]);
            $gmailConfigs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
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
    <title>Résoudre Problème Gmail - CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .step-card {
            border-left: 4px solid #4285f4;
            margin-bottom: 20px;
        }
        .step-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            background: #4285f4;
            color: white;
            border-radius: 50%;
            font-weight: bold;
            margin-right: 10px;
        }
        .code-box {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 4px;
            border: 1px solid #ddd;
            font-family: monospace;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fab fa-google"></i> Résoudre Problème Gmail</h1>
                    <a href="email-settings.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
                
                <div class="alert alert-danger">
                    <h5><i class="fas fa-exclamation-circle"></i> Erreur détectée</h5>
                    <p class="mb-0">
                        <strong>Token OAuth2 expiré</strong> ou <strong>Mot de passe d'application requis</strong>
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
                        <h5><span class="step-number">1</span> Créer un Mot de Passe d'Application Gmail</h5>
                        <p>Gmail nécessite un <strong>mot de passe d'application</strong> pour les applications tierces.</p>
                        
                        <ol>
                            <li>Ouvrez <a href="https://myaccount.google.com/apppasswords" target="_blank" class="btn btn-sm btn-primary">
                                <i class="fas fa-external-link-alt"></i> Google App Passwords</a>
                            </li>
                            <li>Connectez-vous avec votre compte Gmail : <strong><?php echo htmlspecialchars($gmailConfigs[0]['email'] ?? 'votre-email@gmail.com'); ?></strong></li>
                            <li>Sélectionnez <strong>"Mail"</strong> comme application</li>
                            <li>Sélectionnez votre appareil (ex: <strong>"Windows Computer"</strong> ou <strong>"Mac"</strong>)</li>
                            <li>Cliquez sur <strong>"Générer"</strong></li>
                            <li><strong>Copiez le mot de passe de 16 caractères</strong> qui s'affiche (ex: <code>abcd efgh ijkl mnop</code>)</li>
                        </ol>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> <strong>Note:</strong> 
                            Si vous ne voyez pas l'option "Mots de passe des applications", vous devez activer 
                            <strong>la validation en 2 étapes</strong> sur votre compte Google d'abord.
                        </div>
                    </div>
                </div>
                
                <div class="card step-card">
                    <div class="card-body">
                        <h5><span class="step-number">2</span> Configurer le Mot de Passe d'Application</h5>
                        
                        <?php if (empty($gmailConfigs)): ?>
                            <div class="alert alert-warning">
                                Aucun compte Gmail trouvé. <a href="email-settings.php">Ajoutez-en un</a>.
                            </div>
                        <?php else: ?>
                            <?php foreach ($gmailConfigs as $config): ?>
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <i class="fab fa-google"></i> <?php echo htmlspecialchars($config['email']); ?>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST">
                                            <input type="hidden" name="config_id" value="<?php echo $config['id']; ?>">
                                            
                                            <div class="mb-3">
                                                <label class="form-label">
                                                    <i class="fas fa-key"></i> Mot de passe d'application Gmail *
                                                </label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control" 
                                                           name="app_password" 
                                                           id="app_password_<?php echo $config['id']; ?>"
                                                           placeholder="abcd efgh ijkl mnop" 
                                                           required>
                                                    <button class="btn btn-outline-secondary" type="button" 
                                                            onclick="togglePassword(<?php echo $config['id']; ?>)">
                                                        <i class="fas fa-eye" id="eye_<?php echo $config['id']; ?>"></i>
                                                    </button>
                                                </div>
                                                <small class="text-muted">
                                                    Collez le mot de passe de 16 caractères généré par Google 
                                                    (avec ou sans espaces)
                                                </small>
                                            </div>
                                            
                                            <div class="alert alert-warning">
                                                <i class="fas fa-shield-alt"></i> 
                                                Le mot de passe sera chiffré avec AES-256-CBC avant d'être stocké.
                                            </div>
                                            
                                            <button type="submit" name="update_password" class="btn btn-success">
                                                <i class="fas fa-save"></i> Enregistrer et Passer en Mode Password
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
                        <h6>Pourquoi Gmail nécessite-t-il un mot de passe d'application ?</h6>
                        <p>
                            Pour des raisons de <strong>sécurité</strong>, Gmail ne permet plus aux applications tierces 
                            d'utiliser votre mot de passe principal. Vous devez créer un <strong>mot de passe d'application</strong> 
                            spécifique qui peut être révoqué à tout moment sans affecter votre compte principal.
                        </p>
                        
                        <h6>Et OAuth2 ?</h6>
                        <p>
                            OAuth2 est une méthode plus sécurisée mais nécessite un renouvellement périodique du token. 
                            Le mot de passe d'application est plus simple pour une utilisation continue.
                        </p>
                        
                        <h6>Liens utiles</h6>
                        <ul>
                            <li><a href="https://support.google.com/accounts/answer/185833" target="_blank">
                                Guide officiel Google sur les mots de passe d'application
                            </a></li>
                            <li><a href="https://support.google.com/accounts/answer/185839" target="_blank">
                                Activer la validation en 2 étapes
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
