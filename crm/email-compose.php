<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once 'includes/verify_subscriptions.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/env.php';
loadEnv(__DIR__ . '/.env');
require_once __DIR__ . '/includes/EmailCrypto.php';

$page_title = "Composer un Email - CRM";
$customer_id = $_SESSION['customer_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

if (!$customer_id) {
    header('Location: index.php?error=customer');
    exit;
}

// Vérifier si les tables email existent
$error_message = null;
$errorMessage = null;
$successMessage = null;

try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'email_configurations'");
    if ($stmt->rowCount() === 0) {
        $error_message = "Les tables email ne sont pas créées. Exécutez: mysql -u root webitech < database/email_tables.sql";
    }
} catch (Exception $e) {
    $error_message = "Erreur de base de données: " . $e->getMessage();
}

$emailConfigs = [];
$contacts = [];
$originalEmail = null;
$hasCustomerIdColumn = false;

// ÉTAPE 1 : Vérifier la structure de la table emails AVANT toute autre opération
if (!$error_message) {
    try {
        $checkColumns = $pdo->query("
            SELECT 
                (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_SCHEMA = DATABASE() 
                 AND TABLE_NAME = 'emails' 
                 AND COLUMN_NAME = 'customer_id') as has_customer_id,
                (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_SCHEMA = DATABASE() 
                 AND TABLE_NAME = 'emails' 
                 AND COLUMN_NAME = 'config_id') as has_config_id
        ");
        $columnsCheck = $checkColumns->fetch(PDO::FETCH_ASSOC);
        $hasCustomerIdColumn = $columnsCheck['has_customer_id'] > 0;
        
        // Si on accède à un email existant (reply/forward), on a BESOIN de customer_id
        $replyToId = $_GET['reply'] ?? null;
        $forwardId = $_GET['forward'] ?? null;
        
        if (($replyToId || $forwardId) && !$hasCustomerIdColumn) {
            $error_message = "⚠️ La table emails n'a pas été migrée. Les colonnes nécessaires sont manquantes. 
                             <br><a href='migrate-email-customer-id.php' class='btn btn-warning btn-sm mt-2'>
                             <i class='fas fa-database'></i> Exécuter la migration maintenant</a>";
        }
    } catch (Exception $e) {
        $error_message = "Erreur lors de la vérification de la structure: " . $e->getMessage();
    }
}

// ÉTAPE 2 : Récupérer les données si pas d'erreur
if (!$error_message) {
    try {
        // Récupérer les configurations email actives
        $configStmt = $pdo->prepare("
            SELECT id, email, provider, smtp_server, smtp_port 
            FROM email_configurations 
            WHERE customer_id = ? AND is_active = 1
            ORDER BY email
        ");
        $configStmt->execute([$customer_id]);
        $emailConfigs = $configStmt->fetchAll(PDO::FETCH_ASSOC);

        // Récupérer les contacts pour l'auto-complétion
        // Vérifier si la table contacts a la colonne customer_id
        $checkContactsColumn = $pdo->query("
            SELECT COUNT(*) as has_customer_id 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'contacts' 
            AND COLUMN_NAME = 'customer_id'
        ");
        $contactsHasCustomerId = $checkContactsColumn->fetch(PDO::FETCH_ASSOC)['has_customer_id'] > 0;
        
        if ($contactsHasCustomerId) {
            // La colonne existe, filtrer par customer_id
            $contactsStmt = $pdo->prepare("
                SELECT DISTINCT email, CONCAT(first_name, ' ', last_name) as name
                FROM contacts
                WHERE customer_id = ? AND email IS NOT NULL AND email != ''
                ORDER BY first_name, last_name
            ");
            $contactsStmt->execute([$customer_id]);
        } else {
            // La colonne n'existe pas, récupérer tous les contacts avec email
            $contactsStmt = $pdo->query("
                SELECT DISTINCT email, CONCAT(first_name, ' ', last_name) as name
                FROM contacts
                WHERE email IS NOT NULL AND email != ''
                ORDER BY first_name, last_name
            ");
        }
        $contacts = $contactsStmt->fetchAll(PDO::FETCH_ASSOC);

        // ÉTAPE 3 : Charger l'email original si c'est une réponse/transfert
        $replyToId = $_GET['reply'] ?? null;
        $forwardId = $_GET['forward'] ?? null;
        
        if ($replyToId || $forwardId) {
            $emailId = $replyToId ?? $forwardId;
            
            // On sait maintenant que $hasCustomerIdColumn est bien défini
            if ($hasCustomerIdColumn) {
                $stmt = $pdo->prepare("SELECT * FROM emails WHERE id = ? AND customer_id = ?");
                $stmt->execute([$emailId, $customer_id]);
            } else {
                // Fallback si la colonne n'existe pas (ne devrait pas arriver ici à cause du check ci-dessus)
                $stmt = $pdo->prepare("SELECT * FROM emails WHERE id = ?");
                $stmt->execute([$emailId]);
            }
            $originalEmail = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
    } catch (Exception $e) {
        $error_message = "Erreur lors de la récupération des données: " . $e->getMessage();
        error_log("Email compose error: " . $e->getMessage());
    }
}

// Traitement de l'envoi
if (!$error_message && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $from_config_id = $_POST['from_config'] ?? null;
    $to = $_POST['to'] ?? '';
    $cc = $_POST['cc'] ?? '';
    $bcc = $_POST['bcc'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $body = $_POST['body'] ?? '';
    
    if (!$from_config_id || !$to || !$subject || !$body) {
        $errorMessage = "Tous les champs obligatoires doivent être remplis";
    } else {
        try {
            // Récupérer la configuration
            $configStmt = $pdo->prepare("
                SELECT * FROM email_configurations 
                WHERE id = ? AND customer_id = ?
            ");
            $configStmt->execute([$from_config_id, $customer_id]);
            $config = $configStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$config) {
                throw new Exception("Configuration email non trouvée");
            }
            
            // Déchiffrer le mot de passe
            $crypto = new EmailCrypto();
            $password = $crypto->decrypt($config['password']);
            
            // Configurer PHPMailer ou mail()
            require_once __DIR__ . '/../vendor/autoload.php';
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // Configuration SMTP
            $mail->isSMTP();
            $mail->Host = $config['smtp_server'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['email'];
            $mail->Password = $password;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $config['smtp_port'];
            $mail->CharSet = 'UTF-8';
            
            // Expéditeur
            $mail->setFrom($config['email'], $_SESSION['user_name'] ?? $config['email']);
            
            // Destinataires
            $toAddresses = array_map('trim', explode(',', $to));
            foreach ($toAddresses as $address) {
                if (filter_var($address, FILTER_VALIDATE_EMAIL)) {
                    $mail->addAddress($address);
                }
            }
            
            // CC
            if ($cc) {
                $ccAddresses = array_map('trim', explode(',', $cc));
                foreach ($ccAddresses as $address) {
                    if (filter_var($address, FILTER_VALIDATE_EMAIL)) {
                        $mail->addCC($address);
                    }
                }
            }
            
            // BCC
            if ($bcc) {
                $bccAddresses = array_map('trim', explode(',', $bcc));
                foreach ($bccAddresses as $address) {
                    if (filter_var($address, FILTER_VALIDATE_EMAIL)) {
                        $mail->addBCC($address);
                    }
                }
            }
            
            // Contenu
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body);
            
            // Envoyer
            $mail->send();
            
            // Enregistrer dans la base (emails envoyés)
            $stmt = $pdo->prepare("
                INSERT INTO emails_sent (
                    customer_id, config_id, user_id,
                    to_address, cc_address, bcc_address,
                    subject, body, sent_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $customer_id,
                $from_config_id,
                $user_id,
                $to,
                $cc,
                $bcc,
                $subject,
                $body
            ]);
            
            $_SESSION['success_message'] = "Email envoyé avec succès!";
            header('Location: email-inbox.php');
            exit;
            
        } catch (Exception $e) {
            $errorMessage = "Erreur lors de l'envoi: " . $e->getMessage();
            error_log("Email send error: " . $e->getMessage());
        }
    }
    
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .compose-container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            max-width: 1000px;
            margin: 0 auto;
        }
        .form-label {
            font-weight: 600;
            margin-bottom: 8px;
        }
        .recipient-input {
            position: relative;
        }
        .suggestions-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }
        .suggestion-item {
            padding: 10px 15px;
            cursor: pointer;
        }
        .suggestion-item:hover {
            background: #f8f9fa;
        }
        .original-email {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-left: 3px solid #0d6efd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        <?php include 'includes/topbar.php'; ?>
        
        <div class="main-content">
            <div class="container-fluid">
                
                <div class="compose-container">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1><i class="fas fa-pen"></i> Composer un Email</h1>
                        <a href="email-inbox.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Retour
                        </a>
                    </div>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <h5><i class="fas fa-exclamation-triangle"></i> Erreur de Configuration</h5>
                            <?php if (strpos($error_message, '<a href') !== false): ?>
                                <p><?php echo $error_message; ?></p>
                            <?php else: ?>
                                <p><?php echo htmlspecialchars($error_message); ?></p>
                            <?php endif; ?>
                            <?php if (strpos($error_message, 'tables email') !== false): ?>
                                <hr>
                                <p class="mb-0">
                                    <strong>Solution:</strong> Exécutez cette commande:<br>
                                    <code>mysql -u root webitech &lt; database/email_tables.sql</code>
                                </p>
                            <?php endif; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($errorMessage)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errorMessage); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (empty($emailConfigs)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            Aucun compte email configuré. 
                            <a href="email-settings.php">Configurer un compte</a>
                        </div>
                    <?php else: ?>
                        
                        <form method="POST" id="composeForm">
                            <!-- De -->
                            <div class="mb-3">
                                <label class="form-label">De</label>
                                <select name="from_config" class="form-select" required>
                                    <option value="">Sélectionner un compte...</option>
                                    <?php foreach ($emailConfigs as $config): ?>
                                        <option value="<?php echo $config['id']; ?>">
                                            <?php echo htmlspecialchars($config['email']); ?> 
                                            (<?php echo ucfirst($config['provider']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- À -->
                            <div class="mb-3 recipient-input">
                                <label class="form-label">À *</label>
                                <input type="text" name="to" id="toInput" class="form-control" 
                                    placeholder="destinataire@exemple.com (séparer par des virgules)" 
                                    value="<?php echo $originalEmail && $replyToId ? htmlspecialchars($originalEmail['from_address']) : ''; ?>"
                                    required>
                                <div class="suggestions-dropdown" id="toSuggestions"></div>
                            </div>
                            
                            <!-- CC -->
                            <div class="mb-3">
                                <label class="form-label">CC</label>
                                <input type="text" name="cc" class="form-control" 
                                    placeholder="cc@exemple.com (séparer par des virgules)">
                            </div>
                            
                            <!-- BCC -->
                            <div class="mb-3">
                                <label class="form-label">BCC</label>
                                <input type="text" name="bcc" class="form-control" 
                                    placeholder="bcc@exemple.com (séparer par des virgules)">
                            </div>
                            
                            <!-- Sujet -->
                            <div class="mb-3">
                                <label class="form-label">Sujet *</label>
                                <input type="text" name="subject" class="form-control" 
                                    value="<?php 
                                        if ($replyToId && $originalEmail) {
                                            echo 'Re: ' . htmlspecialchars($originalEmail['subject']);
                                        } elseif ($forwardId && $originalEmail) {
                                            echo 'Fwd: ' . htmlspecialchars($originalEmail['subject']);
                                        }
                                    ?>"
                                    required>
                            </div>
                            
                            <!-- Corps -->
                            <div class="mb-3">
                                <label class="form-label">Message *</label>
                                <textarea name="body" id="emailBody" class="form-control" rows="15" required></textarea>
                            </div>
                            
                            <?php if ($originalEmail): ?>
                                <div class="original-email">
                                    <strong><?php echo $replyToId ? 'Message original' : 'Message à transférer'; ?></strong>
                                    <hr>
                                    <div><strong>De:</strong> <?php echo htmlspecialchars($originalEmail['from_name']); ?> &lt;<?php echo htmlspecialchars($originalEmail['from_address']); ?>&gt;</div>
                                    <div><strong>Date:</strong> <?php echo date('d/m/Y H:i', strtotime($originalEmail['email_date'])); ?></div>
                                    <div><strong>Sujet:</strong> <?php echo htmlspecialchars($originalEmail['subject']); ?></div>
                                    <hr>
                                    <div><?php echo nl2br(htmlspecialchars($originalEmail['body'])); ?></div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mt-4 d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Envoyer
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="saveDraft()">
                                    <i class="fas fa-save"></i> Enregistrer comme brouillon
                                </button>
                                <a href="email-inbox.php" class="btn btn-outline-danger">
                                    <i class="fas fa-times"></i> Annuler
                                </a>
                            </div>
                        </form>
                        
                    <?php endif; ?>
                </div>
                
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/lang/summernote-fr-FR.min.js"></script>
    
    <script>
    // Contacts pour auto-complétion
    const contacts = <?php echo json_encode($contacts); ?>;
    
    // Initialiser l'éditeur riche
    $('#emailBody').summernote({
        height: 400,
        lang: 'fr-FR',
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'underline', 'clear']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link']],
            ['view', ['codeview', 'help']]
        ]
    });
    
    <?php if ($originalEmail): ?>
    // Ajouter le message original à l'éditeur
    $('#emailBody').summernote('code', '<?php echo $replyToId ? "<br><br><blockquote>" : "<br><br><div>"; ?>' + 
        <?php echo json_encode(nl2br($originalEmail['body'])); ?> + 
        '<?php echo $replyToId ? "</blockquote>" : "</div>"; ?>');
    <?php endif; ?>
    
    // Auto-complétion pour le champ "À"
    $('#toInput').on('input', function() {
        const value = $(this).val().toLowerCase();
        const lastPart = value.split(',').pop().trim();
        
        if (lastPart.length < 2) {
            $('#toSuggestions').hide();
            return;
        }
        
        const matches = contacts.filter(c => 
            c.email.toLowerCase().includes(lastPart) ||
            c.name.toLowerCase().includes(lastPart)
        );
        
        if (matches.length > 0) {
            const html = matches.map(c => 
                `<div class="suggestion-item" data-email="${c.email}">
                    ${c.name} &lt;${c.email}&gt;
                </div>`
            ).join('');
            
            $('#toSuggestions').html(html).show();
        } else {
            $('#toSuggestions').hide();
        }
    });
    
    // Sélection d'une suggestion
    $(document).on('click', '.suggestion-item', function() {
        const email = $(this).data('email');
        const currentValue = $('#toInput').val();
        const parts = currentValue.split(',');
        parts[parts.length - 1] = ' ' + email;
        $('#toInput').val(parts.join(',') + ', ');
        $('#toSuggestions').hide();
    });
    
    // Fermer les suggestions en cliquant ailleurs
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.recipient-input').length) {
            $('#toSuggestions').hide();
        }
    });
    
    function saveDraft() {
        alert('Fonction de brouillon à implémenter');
    }
    </script>
</body>
</html>
