<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("[$errno] $errstr in $errfile:$errline");
});

set_exception_handler(function($e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
    exit;
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erreur fatale: ' . $error['message']]);
    }
});

header('Content-Type: application/json');

try {
    // Inclure la config
    $basePath = dirname(__DIR__);
    require_once $basePath . '/config/database.php';
    
    // Vérifier l'authentification
    if (!isset($_SESSION) || !isset($_SESSION['customer_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Non authentifié']);
        exit;
    }
    
    $customer_id = $_SESSION['customer_id'];
    $user_id = $_SESSION['user_id'] ?? null;
    
    // Vérifier si la table existe
    $checkTable = $pdo->query("SHOW TABLES LIKE 'email_configurations'");
    $tableExists = $checkTable->rowCount() > 0;
    
    if (!$tableExists) {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Tables email non créées. Exécutez: database/email_tables.sql'
        ]);
        exit;
    }
    
    // Traiter POST : ajouter configuration
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['provider'])) {
        $provider = $_POST['provider'] ?? null;
        $email = $_POST['email'] ?? null;
        $password = $_POST['password'] ?? null;
        
        if (!$provider || !$email || !$password) {
            echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
            exit;
        }
        
        // Vérifier doublon
        $check = $pdo->prepare("SELECT id FROM email_configurations WHERE customer_id = ? AND email = ?");
        $check->execute([$customer_id, $email]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Cet email est déjà configuré']);
            exit;
        }
        
        // Paramètres serveur par défaut
        $servers = [
            'gmail' => ['imap.gmail.com', 993, 'smtp.gmail.com', 587],
            'outlook' => ['outlook.office365.com', 993, 'smtp.office365.com', 587],
            'hostinger' => ['imap.hostinger.com', 993, 'smtp.hostinger.com', 465],
        ];
        
        if ($provider === 'custom') {
            $imap_server = $_POST['imap_server'] ?? '';
            $imap_port = (int)($_POST['imap_port'] ?? 993);
            $smtp_server = $_POST['smtp_server'] ?? '';
            $smtp_port = (int)($_POST['smtp_port'] ?? 587);
        } else {
            [$imap_server, $imap_port, $smtp_server, $smtp_port] = $servers[$provider] ?? ['', 993, '', 587];
        }
        
        // Chiffrer le mot de passe
        $iv = substr(hash('sha256', $email), 0, 16);
        $encrypted = openssl_encrypt($password, 'AES-256-CBC', $_ENV['ENCRYPTION_KEY'] ?? 'default-key-12345', 0, $iv);
        
        $stmt = $pdo->prepare("
            INSERT INTO email_configurations 
            (customer_id, user_id, provider, email, password, imap_server, imap_port, smtp_server, smtp_port, is_active, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
        ");
        
        $stmt->execute([
            $customer_id,
            $user_id,
            $provider,
            $email,
            $encrypted,
            $imap_server,
            $imap_port,
            $smtp_server,
            $smtp_port
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Configuration sauvegardée']);
        exit;
    }
    
    // Traiter JSON : actions
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['action'])) {
        $action = $input['action'];
        
        if ($action === 'sync') {
            $config_id = $input['config_id'] ?? null;
            
            if (!$config_id) {
                echo json_encode(['success' => false, 'message' => 'Config ID manquante']);
                exit;
            }
            
            $stmt = $pdo->prepare("SELECT * FROM email_configurations WHERE id = ? AND customer_id = ?");
            $stmt->execute([$config_id, $customer_id]);
            $config = $stmt->fetch();
            
            if (!$config) {
                echo json_encode(['success' => false, 'message' => 'Configuration non trouvée']);
                exit;
            }
            
            // Mettre à jour la date
            $update = $pdo->prepare("UPDATE email_configurations SET last_sync = NOW() WHERE id = ?");
            $update->execute([$config_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Synchronisation lancée pour ' . htmlspecialchars($config['email'])
            ]);
            exit;
        }
        
        if ($action === 'delete') {
            $config_id = $input['config_id'] ?? null;
            
            if (!$config_id) {
                echo json_encode(['success' => false, 'message' => 'Config ID manquante']);
                exit;
            }
            
            $stmt = $pdo->prepare("DELETE FROM email_configurations WHERE id = ? AND customer_id = ?");
            $stmt->execute([$config_id, $customer_id]);
            
            echo json_encode(['success' => true, 'message' => 'Supprimé']);
            exit;
        }
    }
    
    echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur BDD: ' . $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
