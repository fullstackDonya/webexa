<?php
/**
 * Diagnostic de la configuration email
 * Ce script vérifie que tout est en place pour la synchronisation des emails
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/env.php';
loadEnv(__DIR__ . '/.env');

session_start();

$customer_id = $_SESSION['customer_id'] ?? null;

if (!$customer_id) {
    die("Erreur: customer_id non défini dans la session");
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Diagnostic Email - CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .check-item {
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            border-left: 4px solid #ccc;
        }
        .check-item.success {
            background: #d4edda;
            border-left-color: #28a745;
        }
        .check-item.warning {
            background: #fff3cd;
            border-left-color: #ffc107;
        }
        .check-item.error {
            background: #f8d7da;
            border-left-color: #dc3545;
        }
        .config-detail {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
            font-family: monospace;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1><i class="fas fa-stethoscope"></i> Diagnostic Email</h1>
        <p class="text-muted">Vérification de la configuration email pour le customer_id: <?php echo $customer_id; ?></p>
        
        <hr>
        
        <?php
        
        // 1. Vérifier l'extension IMAP
        echo '<div class="check-item ' . (extension_loaded('imap') ? 'success' : 'error') . '">';
        echo '<h5>' . (extension_loaded('imap') ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>');
        echo ' Extension PHP IMAP</h5>';
        if (extension_loaded('imap')) {
            echo '<p class="mb-0">✓ L\'extension IMAP est installée et active</p>';
        } else {
            echo '<p class="mb-0">❌ L\'extension IMAP n\'est pas installée. Pour l\'activer dans MAMP:</p>';
            echo '<ol><li>Ouvrez le fichier php.ini (MAMP > Préférences > PHP > Fichier de configuration)</li>';
            echo '<li>Recherchez ";extension=imap"</li>';
            echo '<li>Retirez le ";" au début de la ligne</li>';
            echo '<li>Redémarrez MAMP</li></ol>';
        }
        echo '</div>';
        
        // 2. Vérifier les configurations email
        $stmt = $pdo->prepare("SELECT * FROM email_configurations WHERE customer_id = ?");
        $stmt->execute([$customer_id]);
        $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo '<div class="check-item ' . (!empty($configs) ? 'success' : 'warning') . '">';
        echo '<h5>' . (!empty($configs) ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-exclamation-triangle text-warning"></i>');
        echo ' Configurations Email</h5>';
        
        if (empty($configs)) {
            echo '<p class="mb-0">⚠️ Aucune configuration email trouvée. <a href="email-settings.php">Configurez un compte email</a></p>';
        } else {
            echo '<p>Trouvé ' . count($configs) . ' configuration(s):</p>';
            
            $hasIncomplete = false;
            
            foreach ($configs as $config) {
                $isValid = true;
                $issues = [];
                
                // Vérifier les champs obligatoires
                if (empty($config['email'])) {
                    $issues[] = "Email manquant";
                    $isValid = false;
                }
                if (empty($config['imap_server'])) {
                    $issues[] = "Serveur IMAP manquant";
                    $isValid = false;
                    $hasIncomplete = true;
                }
                if (empty($config['imap_port'])) {
                    $issues[] = "Port IMAP manquant";
                    $isValid = false;
                }
                if (empty($config['password'])) {
                    $issues[] = "Mot de passe manquant";
                    $isValid = false;
                }
                if (empty($config['smtp_host'])) {
                    $issues[] = "Serveur SMTP manquant (envoi désactivé)";
                    $hasIncomplete = true;
                }
                
                $statusClass = $isValid ? 'success' : 'error';
                $statusIcon = $isValid ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>';
                
                echo '<div class="config-detail">';
                echo '<strong>' . $statusIcon . ' ' . htmlspecialchars($config['email']) . '</strong><br>';
                echo 'Actif: ' . ($config['is_active'] ? '✓ Oui' : '❌ Non') . '<br>';
                echo 'Provider: ' . htmlspecialchars($config['provider'] ?? 'non défini') . '<br>';
                echo 'IMAP Server: ' . htmlspecialchars($config['imap_server'] ?? 'NON CONFIGURÉ') . ':' . ($config['imap_port'] ?? 'N/A') . '<br>';
                echo 'SMTP Server: ' . htmlspecialchars($config['smtp_host'] ?? 'NON CONFIGURÉ') . ':' . ($config['smtp_port'] ?? 'N/A') . '<br>';
                
                if (!empty($issues)) {
                    echo '<span class="text-danger">⚠️ Problèmes: ' . implode(', ', $issues) . '</span>';
                }
                echo '</div>';
            }
            
            if ($hasIncomplete) {
                echo '<div class="alert alert-warning mt-3">';
                echo '<i class="fas fa-wrench"></i> <strong>Configuration incomplète détectée</strong><br>';
                echo '<a href="email-fix-config.php" class="btn btn-warning btn-sm mt-2">';
                echo '<i class="fas fa-wrench"></i> Réparer la configuration maintenant</a>';
                echo '</div>';
            }
        }
        echo '</div>';
        
        // 3. Vérifier la table emails
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM emails WHERE customer_id = $customer_id");
            $emailCount = $stmt->fetch()['count'];
            
            echo '<div class="check-item success">';
            echo '<h5><i class="fas fa-check-circle text-success"></i> Base de données Emails</h5>';
            echo '<p class="mb-0">✓ Table emails accessible. Nombre d\'emails: ' . $emailCount . '</p>';
            echo '</div>';
        } catch (Exception $e) {
            echo '<div class="check-item error">';
            echo '<h5><i class="fas fa-times-circle text-danger"></i> Base de données Emails</h5>';
            echo '<p class="mb-0">❌ Erreur: ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p>Exécutez: <code>mysql -u root webitech < database/email_tables.sql</code></p>';
            echo '</div>';
        }
        
        // 4. Vérifier EmailSyncManager
        $syncManagerExists = file_exists(__DIR__ . '/includes/EmailSyncManager.php');
        echo '<div class="check-item ' . ($syncManagerExists ? 'success' : 'error') . '">';
        echo '<h5>' . ($syncManagerExists ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>');
        echo ' Classe EmailSyncManager</h5>';
        if ($syncManagerExists) {
            echo '<p class="mb-0">✓ EmailSyncManager trouvé</p>';
        } else {
            echo '<p class="mb-0">❌ EmailSyncManager.php manquant dans includes/</p>';
        }
        echo '</div>';
        
        // 5. Vérifier EmailCrypto
        $cryptoExists = file_exists(__DIR__ . '/includes/EmailCrypto.php');
        echo '<div class="check-item ' . ($cryptoExists ? 'success' : 'error') . '">';
        echo '<h5>' . ($cryptoExists ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>');
        echo ' Classe EmailCrypto</h5>';
        if ($cryptoExists) {
            echo '<p class="mb-0">✓ EmailCrypto trouvé</p>';
        } else {
            echo '<p class="mb-0">❌ EmailCrypto.php manquant dans includes/</p>';
        }
        echo '</div>';
        
        // 6. Test de connexion IMAP
        if (!empty($configs) && extension_loaded('imap')) {
            echo '<div class="check-item warning">';
            echo '<h5><i class="fas fa-info-circle text-info"></i> Test de Connexion IMAP</h5>';
            echo '<p>Pour tester la connexion IMAP, cliquez sur le bouton ci-dessous :</p>';
            echo '<button class="btn btn-primary" onclick="testImap()">Tester la connexion IMAP</button>';
            echo '<div id="imap-test-result" class="mt-3"></div>';
            echo '</div>';
        }
        
        ?>
        
        <div class="mt-4">
            <a href="email-inbox.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
            <a href="email-settings.php" class="btn btn-primary"><i class="fas fa-cog"></i> Paramètres Email</a>
            <a href="email-reset-password.php" class="btn btn-warning"><i class="fas fa-key"></i> Réinitialiser Mot de Passe</a>
            <a href="email-gmail-fix.php" class="btn btn-danger"><i class="fab fa-google"></i> Résoudre Gmail</a>
            <a href="email-outlook-fix.php" class="btn btn-info"><i class="fab fa-microsoft"></i> Résoudre Outlook</a>
        </div>
    </div>
    
    <script>
    function testImap() {
        const resultDiv = document.getElementById('imap-test-result');
        resultDiv.innerHTML = '<div class="spinner-border spinner-border-sm"></div> Test en cours...';
        
        fetch('api/email-sync.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'sync_all'})
        })
        .then(r => r.json())
        .then(data => {
            console.log('Test result:', data);
            
            let html = '<div class="alert alert-' + (data.success ? 'success' : 'danger') + '">';
            html += '<h6>' + (data.success ? '✓ Succès' : '❌ Échec') + '</h6>';
            html += '<p>' + data.message + '</p>';
            
            let hasPasswordError = false;
            let hasGmailError = false;
            let hasOutlookError = false;
            
            if (data.results) {
                html += '<hr><strong>Détails par compte:</strong><ul>';
                data.results.forEach(result => {
                    const icon = result.success ? '✓' : '❌';
                    html += '<li>' + icon + ' ' + result.email + ': ';
                    const errorMsg = result.message || (result.success ? 'OK' : result.error);
                    html += errorMsg;
                    
                    // Détecter les erreurs Gmail
                    if (!result.success && result.email && result.email.includes('gmail.com') &&
                        (errorMsg.includes('Application-specific') || 
                         errorMsg.includes('authentication') ||
                         errorMsg.includes('AUTHENTICATE'))) {
                        hasGmailError = true;
                    }
                    
                    // Détecter les erreurs Outlook
                    if (!result.success && result.email && 
                        (result.email.includes('outlook.com') || 
                         result.email.includes('hotmail.com') || 
                         result.email.includes('live.com') ||
                         result.email.includes('office365.com')) &&
                        (errorMsg.includes('authentication') || 
                         errorMsg.includes('password') ||
                         errorMsg.includes('AUTHENTICATE'))) {
                        hasOutlookError = true;
                    }
                    
                    // Détecter les erreurs de mot de passe
                    if (!result.success && (errorMsg.includes('déchiffrer') || 
                                           errorMsg.includes('Encrypted data') ||
                                           errorMsg.includes('password'))) {
                        hasPasswordError = true;
                    }
                    
                    if (result.stats) {
                        html += ' (' + (result.stats.emails_synced || 0) + ' emails synchronisés)';
                    }
                    html += '</li>';
                });
                html += '</ul>';
            }
            
            // Ajouter le bouton de réinitialisation si nécessaire
            if (hasPasswordError) {
                html += '<hr><div class="alert alert-warning mb-0">';
                html += '<i class="fas fa-key"></i> <strong>Problème de mot de passe détecté</strong><br>';
                html += '<a href="email-reset-password.php" class="btn btn-warning btn-sm mt-2">';
                html += '<i class="fas fa-key"></i> Réinitialiser le mot de passe maintenant</a>';
                html += '</div>';
            }
            
            // Ajouter le bouton Gmail si nécessaire
            if (hasGmailError) {
                html += '<hr><div class="alert alert-danger mb-0">';
                html += '<i class="fab fa-google"></i> <strong>Erreur Gmail détectée</strong><br>';
                html += '<p class="mb-2">Gmail nécessite un mot de passe d\'application pour les connexions tierces.</p>';
                html += '<a href="email-gmail-fix.php" class="btn btn-danger btn-sm">';
                html += '<i class="fas fa-wrench"></i> Résoudre maintenant</a>';
                html += '</div>';
            }
            
            // Ajouter le bouton Outlook si nécessaire
            if (hasOutlookError) {
                html += '<hr><div class="alert alert-info mb-0">';
                html += '<i class="fab fa-microsoft"></i> <strong>Erreur Outlook détectée</strong><br>';
                html += '<p class="mb-2">Outlook nécessite un mot de passe d\'application si l\'authentification à 2 facteurs est activée.</p>';
                html += '<a href="email-outlook-fix.php" class="btn btn-info btn-sm">';
                html += '<i class="fas fa-wrench"></i> Résoudre maintenant</a>';
                html += '</div>';
            }
            
            html += '</div>';
            resultDiv.innerHTML = html;
        })
        .catch(e => {
            resultDiv.innerHTML = '<div class="alert alert-danger">❌ Erreur: ' + e.message + '</div>';
        });
    }
    </script>
</body>
</html>
