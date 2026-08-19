<?php
// Activer l'affichage des erreurs pour debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require '../crm/config/database.php';
require '../config/mailer.php';

// Détecter si c'est une requête AJAX
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($isAjax) {
    header('Content-Type: application/json');
}

// Log des requêtes pour debug
file_put_contents('register_debug.log', date('Y-m-d H:i:s') . " - Début de la requête (AJAX: " . ($isAjax ? 'OUI' : 'NON') . ")\n", FILE_APPEND);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Récupération et validation des données
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $phone = trim($_POST['phone'] ?? '');
        $newsletter = isset($_POST['newsletter']) ? 1 : 0;
        $terms = isset($_POST['terms']) ? 1 : 0;

        file_put_contents('register_debug.log', "Données reçues: username=$username, email=$email, phone=$phone\n", FILE_APPEND);

        // Validation des données
        $errors = [];

        if (empty($username) || strlen($username) < 3) {
            $errors[] = "Le nom d'utilisateur doit contenir au moins 3 caractères";
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Adresse email invalide";
        }

        if (empty($password) || strlen($password) < 8) {
            $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
        }

        if ($password !== $confirm_password) {
            $errors[] = "Les mots de passe ne correspondent pas";
        }

        // Le téléphone est facultatif, pas de validation requise

        if (!$terms) {
            $errors[] = "Vous devez accepter les conditions d'utilisation";
        }

        if (!empty($errors)) {
            file_put_contents('register_debug.log', "Erreurs de validation: " . implode(', ', $errors) . "\n", FILE_APPEND);
            
            if ($isAjax) {
                echo json_encode([
                    'success' => false,
                    'message' => implode('<br>', $errors)
                ]);
            } else {
                echo "<h3>Erreurs de validation</h3><ul>";
                foreach ($errors as $error) {
                    echo "<li>" . htmlspecialchars($error) . "</li>";
                }
                echo "</ul><a href='../register'>Retour</a>";
            }
            exit;
        }

        file_put_contents('register_debug.log', "Validation OK, vérification des doublons\n", FILE_APPEND);

        // Vérifier si l'email existe déjà
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            file_put_contents('register_debug.log', "Email déjà existant\n", FILE_APPEND);
            
            $message = 'Un compte avec cet email existe déjà';
            if ($isAjax) {
                echo json_encode([
                    'success' => false,
                    'message' => $message
                ]);
            } else {
                echo "<h3>Erreur</h3><p>" . htmlspecialchars($message) . "</p><a href='../register'>Retour</a>";
            }
            exit;
        }

        // Vérifier si le numéro de téléphone existe déjà (uniquement s'il est fourni)
        if (!empty($phone)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = :phone");
            $stmt->execute([':phone' => $phone]);
            if ($stmt->fetch()) {
                file_put_contents('register_debug.log', "Téléphone déjà existant\n", FILE_APPEND);
                
                $message = 'Ce numéro de téléphone est déjà utilisé';
                if ($isAjax) {
                    echo json_encode([
                        'success' => false,
                        'message' => $message
                    ]);
                } else {
                    echo "<h3>Erreur</h3><p>" . htmlspecialchars($message) . "</p><a href='../register'>Retour</a>";
                }
                exit;
            }
        }

        file_put_contents('register_debug.log', "Pas de doublons, début de l'insertion\n", FILE_APPEND);

        // Commencer une transaction
        $pdo->beginTransaction();

        // Insérer le nouvel utilisateur avec mot de passe sécurisé
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, phone, created_at) VALUES (:username, :email, :password, :phone, NOW())");
        $result = $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':password' => $hashed_password,
            ':phone' => $phone
        ]);

        if (!$result) {
            throw new Exception("Erreur lors de l'insertion de l'utilisateur");
        }

        $user_id = $pdo->lastInsertId();
        file_put_contents('register_debug.log', "Utilisateur créé avec ID: $user_id\n", FILE_APPEND);

        // Créer les préférences par défaut
        $stmt = $pdo->prepare("INSERT INTO user_preferences (user_id, email_notifications, security_alerts, newsletter, theme) VALUES (:user_id, 1, 1, :newsletter, 'light')");
        $prefResult = $stmt->execute([
            ':user_id' => $user_id,
            ':newsletter' => $newsletter
        ]);

        if (!$prefResult) {
            throw new Exception("Erreur lors de la création des préférences");
        }

        file_put_contents('register_debug.log', "Préférences créées, validation de la transaction\n", FILE_APPEND);

        // Valider la transaction
        $pdo->commit();


        // Générer un token de vérification
        $verify_token = bin2hex(random_bytes(32));
        $stmt = $pdo->prepare("UPDATE users SET email_verify_token = :token WHERE id = :id");
        $stmt->execute([':token' => $verify_token, ':id' => $user_id]);

        // Envoi de l'email de vérification
        $verify_link = "https://" . $_SERVER['HTTP_HOST'] . "/forms/verify_email.php?token=$verify_token";
          
        $subject = "Vérification de votre adresse email - Webexa By WebItech";
        $messageBody = "
            <h2>Bienvenue sur Webexa By WebItech !</h2>
            <p>Bonjour <strong>" . htmlspecialchars($username) . "</strong>,</p>
            <p>Merci pour votre inscription sur Webexa By WebItech.</p>
            <p>Pour activer votre compte, veuillez cliquer sur le lien ci-dessous pour vérifier votre adresse email :</p>
            <p><a href='$verify_link' style='display:inline-block;padding:12px 24px;background:#007bff;color:white;text-decoration:none;border-radius:5px;'>Vérifier mon email</a></p>
            <p>Ou copiez ce lien dans votre navigateur :<br>
            <code>$verify_link</code></p>
            <p>Si vous n'êtes pas à l'origine de cette inscription, ignorez simplement ce message.</p>
            <p>Cordialement,<br>L'équipe Webexa</p>
        ";

        // Utiliser la fonction sendEmail améliorée
        $emailSent = sendEmail($email, $subject, $messageBody);
        
        if (!$emailSent) {
            error_log('[REGISTER] Email de vérification non envoyé pour user_id: ' . $user_id);
        }

        file_put_contents('register_debug.log', "Inscription réussie pour user_id: $user_id\n", FILE_APPEND);

        $successMessage = 'Inscription réussie ! Vous pouvez maintenant vous connecter après avoir validé votre adresse email.';

        if ($isAjax) {
            echo json_encode([
                'success' => true,
                'message' => $successMessage,
                'redirect' => 'login'
            ]);
        } else {
            echo "<h3>Succès !</h3><p>" . htmlspecialchars($successMessage) . "</p>";
            echo "<p><a href='../login'>Se connecter maintenant</a></p>";
        }

    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        file_put_contents('register_debug.log', "ERREUR: " . $e->getMessage() . "\n", FILE_APPEND);
        
        $errorMessage = 'Erreur lors de l\'inscription: ' . $e->getMessage();
        
        if ($isAjax) {
            echo json_encode([
                'success' => false,
                'message' => $errorMessage
            ]);
        } else {
            echo "<h3>Erreur</h3><p>" . htmlspecialchars($errorMessage) . "</p><a href='../register'>Retour</a>";
        }
    }
} else {
    file_put_contents('register_debug.log', "Méthode non autorisée: " . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);
    
    $message = 'Méthode non autorisée';
    
    if ($isAjax) {
        echo json_encode([
            'success' => false,
            'message' => $message
        ]);
    } else {
        echo "<h3>Erreur</h3><p>" . htmlspecialchars($message) . "</p><a href='../register'>Retour</a>";
    }
}
?>