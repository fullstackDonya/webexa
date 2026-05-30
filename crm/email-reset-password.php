<?php
/**
 * Page pour réinitialiser le mot de passe d'une configuration email
 * Utile quand le mot de passe est corrompu ou invalide
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

// Récupérer les configurations de cet utilisateur
$stmt = $pdo->prepare("
    SELECT id, email, imap_server, imap_port, smtp_host, smtp_port, provider 
    FROM email_configurations 
    WHERE customer_id = ?
    ORDER BY email
");
$stmt->execute([$customer_id]);
$configs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traiter le formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $config_id = intval($_POST['config_id'] ?? 0);
    $password = $_POST['password'] ?? '';
    
    if ($config_id && $password) {
        try {
            $crypto = new EmailCrypto();
            $encryptedPassword = $crypto->encrypt($password);
            
            $stmt = $pdo->prepare("
                UPDATE email_configurations 
                SET password = ?
                WHERE id = ? AND customer_id = ?
            ");
            $stmt->execute([$encryptedPassword, $config_id, $customer_id]);
            
            $message = "✓ Mot de passe mis à jour avec succès !";
            
        } catch (Exception $e) {
            $error = "Erreur lors de la mise à jour: " . $e->getMessage();
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
    <title>Réinitialiser Mot de Passe Email - CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-lg-6 mx-auto">
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-key"></i> Réinitialiser Mot de Passe Email</h1>
                    <a href="email-diagnostic.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
                
                <div class="alert alert-info">
                    <h5><i class="fas fa-info-circle"></i> À propos</h5>
                    <p class="mb-0">
                        Cette page vous permet de réenregistrer votre mot de passe email de façon sécurisée.
                        Utile si le mot de passe stocké est corrompu ou invalide.
                    </p>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <hr>
                        <p class="mb-0">
                            <a href="email-diagnostic.php" class="btn btn-sm btn-primary">
                                <i class="fas fa-check"></i> Tester la connexion
                            </a>
                        </p>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (empty($configs)): ?>
                    
                    <div class="alert alert-warning">
                        <h5><i class="fas fa-exclamation-triangle"></i> Aucune configuration trouvée</h5>
                        <p class="mb-0">
                            Vous devez d'abord créer une configuration email dans 
                            <a href="email-settings.php">les paramètres</a>.
                        </p>
                    </div>
                    
                <?php else: ?>
                    
                    <?php foreach ($configs as $config): ?>
                        
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($config['email']); ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                
                                <div class="mb-3">
                                    <small class="text-muted">
                                        <strong>Serveur IMAP:</strong> <?php echo htmlspecialchars($config['imap_server']); ?>:<?php echo $config['imap_port']; ?><br>
                                        <strong>Serveur SMTP:</strong> <?php echo htmlspecialchars($config['smtp_host']); ?>:<?php echo $config['smtp_port']; ?><br>
                                        <strong>Fournisseur:</strong> <?php echo htmlspecialchars($config['provider'] ?: 'custom'); ?>
                                    </small>
                                </div>
                                
                                <hr>
                                
                                <form method="POST">
                                    <input type="hidden" name="config_id" value="<?php echo $config['id']; ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-lock"></i> Mot de passe email *
                                        </label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" name="password" 
                                                   id="password_<?php echo $config['id']; ?>"
                                                   placeholder="Entrez le mot de passe de votre compte email" 
                                                   required>
                                            <button class="btn btn-outline-secondary" type="button" 
                                                    onclick="togglePassword(<?php echo $config['id']; ?>)">
                                                <i class="fas fa-eye" id="eye_<?php echo $config['id']; ?>"></i>
                                            </button>
                                        </div>
                                        <small class="text-muted">
                                            Mot de passe de votre compte <strong><?php echo htmlspecialchars($config['email']); ?></strong>
                                        </small>
                                    </div>
                                    
                                    <div class="alert alert-warning">
                                        <small>
                                            <i class="fas fa-shield-alt"></i> 
                                            Le mot de passe sera chiffré avec AES-256-CBC avant d'être stocké en base de données.
                                        </small>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-save"></i> Enregistrer le mot de passe
                                    </button>
                                </form>
                                
                            </div>
                        </div>
                        
                    <?php endforeach; ?>
                    
                    <div class="mt-4 text-center">
                        <h5>Informations importantes</h5>
                        <div class="alert alert-light text-start">
                            <ul class="mb-0">
                                <li><strong>Hostinger:</strong> Utilisez le mot de passe de votre compte email Hostinger</li>
                                <li><strong>Gmail:</strong> Vous devez utiliser un <a href="https://myaccount.google.com/apppasswords" target="_blank">mot de passe d'application</a></li>
                                <li><strong>Outlook:</strong> Vous devez utiliser un <a href="https://account.live.com/proofs/AppPassword" target="_blank">mot de passe d'application</a></li>
                                <li><strong>Vérification en 2 étapes:</strong> Si activée, utilisez un mot de passe d'application</li>
                            </ul>
                        </div>
                    </div>
                    
                <?php endif; ?>
                
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
        const input = document.getElementById('password_' + configId);
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
