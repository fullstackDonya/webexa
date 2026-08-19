<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../crm/config/database.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Configuration de sécurité
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes en secondes

// Configuration des headers pour AJAX et sécurité
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Fonction pour retourner une réponse JSON
function jsonResponse($success, $message, $data = [], $redirect = null) {
    $response = [
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => time()
    ];
    
    if ($redirect) {
        $response['redirect'] = $redirect;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Vérifier si c'est une requête AJAX
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

// Créer les tables nécessaires si elles n'existent pas
function createSecurityTables($pdo) {
    try {
        // Table pour les tentatives de connexion
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS login_attempts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255),
                ip_address VARCHAR(45),
                attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_email_time (email, attempted_at),
                INDEX idx_ip_time (ip_address, attempted_at)
            )
        ");
        
        // Table pour les tokens "Se souvenir de moi"
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS remember_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(64) NOT NULL,
                expires_at TIMESTAMP NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user (user_id),
                INDEX idx_token (token),
                INDEX idx_expires (expires_at),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        
    } catch (PDOException $e) {
        error_log("Erreur création tables sécurité: " . $e->getMessage());
    }
}

// Créer les tables au premier accès
createSecurityTables($pdo);

// Fonction pour obtenir l'adresse IP du client
function getClientIP() {
    $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

// Fonction pour vérifier les tentatives de connexion
function checkLoginAttempts($pdo, $email, $ip) {
    // Nettoyer les anciennes tentatives
    $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL ? SECOND)");
    $stmt->execute([LOCKOUT_TIME]);
    
    // Compter les tentatives récentes
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as attempts 
        FROM login_attempts 
        WHERE (email = ? OR ip_address = ?) 
        AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
    ");
    $stmt->execute([$email, $ip, LOCKOUT_TIME]);
    $result = $stmt->fetch();
    
    return $result['attempts'] < MAX_LOGIN_ATTEMPTS;
}

// Fonction pour enregistrer une tentative de connexion
function logLoginAttempt($pdo, $email, $ip, $success = false) {
    if (!$success) {
        $stmt = $pdo->prepare("
            INSERT INTO login_attempts (email, ip_address, attempted_at) 
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$email, $ip]);
    }
}

// Fonction pour mettre à jour la dernière connexion
function updateLastLogin($pdo, $userId, $ip) {
    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$userId]);
    
    // Enregistrer la session si la table existe
    try {
        $sessionToken = bin2hex(random_bytes(32));
        $stmt = $pdo->prepare("
            INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at) 
            VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))
        ");
        $stmt->execute([
            $userId, 
            $sessionToken, 
            $ip, 
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);
        return $sessionToken;
    } catch (PDOException $e) {
        // Table n'existe pas encore, continuer sans erreur
        return bin2hex(random_bytes(32));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']);
    $clientIP = getClientIP();
    $isAjax = isAjaxRequest();
    
    // Validation des données
    if (empty($email) || empty($password)) {
        if ($isAjax) {
            jsonResponse(false, 'Email et mot de passe requis');
        } else {
            header("Location: ../login.php?error=missing_fields");
            exit;
        }
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        if ($isAjax) {
            jsonResponse(false, 'Format d\'email invalide');
        } else {
            header("Location: ../login.php?error=invalid_email");
            exit;
        }
    }
    
    // Vérifier les tentatives de connexion
    if (!checkLoginAttempts($pdo, $email, $clientIP)) {
        logLoginAttempt($pdo, $email, $clientIP, false);
        if ($isAjax) {
            jsonResponse(false, 'Trop de tentatives de connexion. Veuillez patienter 15 minutes.');
        } else {
            header("Location: ../login.php?error=too_many_attempts");
            exit;
        }
    }
    
    

    // Récupérer l'utilisateur par email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        // Vérifier si l'email est vérifié (optionnel)
        if (isset($user['email_verified']) && !$user['email_verified']) {
            if ($isAjax) {
                jsonResponse(false, 'Veuillez vérifier votre adresse email avant de vous connecter.');
            } else {
                header("Location: ../login.php?error=email_not_verified");
                exit;
            }
        }
        
        // Connexion réussie
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['customer_id'] = $user['customer_id']; 
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['login_time'] = time();
        
        // Générer un token de session
        $sessionToken = updateLastLogin($pdo, $user['id'], $clientIP);
        $_SESSION['session_token'] = $sessionToken;
        
        // Configurer les cookies pour "Se souvenir de moi"
        if ($rememberMe) {
            try {
                $cookieToken = bin2hex(random_bytes(32));
                setcookie('remember_token', $cookieToken, time() + (86400 * 30), '/', '', true, true); // 30 jours
                
                // Stocker le token dans la base de données
                $stmt = $pdo->prepare("
                    INSERT INTO remember_tokens (user_id, token, expires_at) 
                    VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))
                    ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)
                ");
                $stmt->execute([$user['id'], hash('sha256', $cookieToken)]);
            } catch (PDOException $e) {
                // Table n'existe pas encore, continuer sans erreur
            }
        }
        
        // Nettoyer les tentatives de connexion pour cet utilisateur
        try {
            $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE email = ? OR ip_address = ?");
            $stmt->execute([$email, $clientIP]);
        } catch (PDOException $e) {
            // Table n'existe pas encore, continuer sans erreur
        }
        
        // Déterminer l'URL de redirection
        $redirectUrl = $_SESSION['redirect_after_login'] ?? 'crm/index.php';
        unset($_SESSION['redirect_after_login']);
        
        if ($isAjax) {
            // Réponse JSON pour AJAX
            jsonResponse(true, "Bienvenue {$user['username']} ! Connexion réussie.", [
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'customer_id' => $user['customer_id'],
                    'role' => $user['role'],    
                ]
            ], $redirectUrl);
        } else {
            // Redirection classique
            header("Location: ../" . $redirectUrl);
            exit;
        }
        
    } else {
        // Connexion échouée
        logLoginAttempt($pdo, $email, $clientIP, false);
        
        // Attendre un peu pour ralentir les attaques par force brute
        usleep(rand(500000, 1500000)); // 0.5 à 1.5 secondes
        
        if ($isAjax) {
            jsonResponse(false, 'Email ou mot de passe incorrect');
        } else {
            header("Location: ../login.php?error=invalid_credentials");
            exit;
        }
    }
        

    
} else {
    // Méthode non autorisée
    if (isAjaxRequest()) {
        jsonResponse(false, 'Méthode de requête non autorisée');
    } else {
        header("Location: ../login.php?error=invalid_method");
        exit;
    }
}
?>