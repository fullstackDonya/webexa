<?php
//afficher les erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
session_start();
require_once __DIR__ . '/vendor/autoload.php';

// CONFIGURATION

// Charge les variables d'environnement — priorise `config/.env` puis fallback vers la racine
if (file_exists(__DIR__ . '/crm/config/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/crm/config');
} elseif (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
} else {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
}
$dotenv->safeLoad();

$clientID = $_ENV['GOOGLE_CLIENT_ID'];
$clientSecret = $_ENV['GOOGLE_CLIENT_SECRET'];
$redirectUri = $_ENV['GOOGLE_REDIRECT_URI'];

// Crée le client Google
$client = new Google_Client();
$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->addScope('email');
$client->addScope('profile');

// Toujours afficher le sélecteur de compte
$client->setPrompt('select_account');

// Pré-sélectionner un compte spécifique (hint depuis le popup)
if (!empty($_GET['hint'])) {
    $client->setLoginHint(filter_var($_GET['hint'], FILTER_SANITIZE_EMAIL));
}

// Si retour de Google avec code
if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (!isset($token['error'])) {
        $client->setAccessToken($token['access_token']);
        $oauth2 = new Google_Service_Oauth2($client);
        $google_user = $oauth2->userinfo->get();

        // Récupère les infos
        $email = $google_user->email;
        $name = $google_user->name;
        $google_id = $google_user->id;

        // Connexion à la BDD
        require_once __DIR__ . '/crm/config/database.php';

        // Vérifie si l'utilisateur existe déjà
          
        $stmt = $pdo->prepare("SELECT id, username, email, customer_id, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
  

        if ($user) {
               // Connexion réussie
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['customer_id'] = $user['customer_id']; 
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['login_time'] = time();
                
                
        
        } else {
            // Nouvel utilisateur : inscription
            // Génère un username et un mot de passe aléatoire
            $username = preg_replace('/\s+/', '_', strtolower($name)); // exemple: "John Doe" => "john_doe"
            $password = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
        
            // Sépare le nom Google en prénom et nom
            $nameParts = explode(' ', $name, 2);

            $first_name = $nameParts[0];
            $last_name = $nameParts[1] ?? '';

            $stmt = $pdo->prepare("
                INSERT INTO users 
                (
                    username,
                    email,
                    google_id,
                    password,
                    first_name,
                    last_name,
                    role,
                    avatar,
                    email_verified,
                    created_at,
                    updated_at
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");

            $stmt->execute([
                $username,
                $email,
                $google_id,
                $password,
                $first_name,
                $last_name,
                'sales',
                $google_user->picture,
                1
            ]);
            // Récupère l'ID de l'utilisateur créé
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $_SESSION['user_role'] = 'sales'; // ou la valeur par défaut
            $_SESSION['login_time'] = time();
        }
        // Sauvegarder le dernier compte Google (données d'affichage uniquement)
        $accountCookie = json_encode([
            'name'    => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
            'email'   => $email,
            'picture' => $google_user->picture ?? ''
        ]);
        setcookie('last_google_account', $accountCookie, [
            'expires'  => time() + 365 * 24 * 3600,
            'path'     => '/',
            'httponly' => false,
            'samesite' => 'Lax'
        ]);

        // Redirection vers la page d'accueil ou dashboard
        header('Location: crm/index.php');
        exit;
    } else {
        // Erreur Google
        header('Location: login?error=Erreur Google');
        exit;
    }
}

// Si pas encore connecté, redirige vers Google
$authUrl = $client->createAuthUrl();
header('Location: ' . $authUrl);
exit;
