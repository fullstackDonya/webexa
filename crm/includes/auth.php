<?php
// Protection contre les inclusions multiples
if (!defined('AUTH_INCLUDED')) {
    define('AUTH_INCLUDED', true);
}

if (!session_id()) {
    session_start();
}

// Gestion de l'authentification
if (!function_exists('isAuthenticated')) {
    function isAuthenticated() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

if (!function_exists('getCurrentUser')) {
    function getCurrentUser() {
        global $pdo;
        static $currentUser = null;
        static $loaded = false;
        
        if (!isAuthenticated()) {
            return null;
        }
        if ($loaded) {
            return $currentUser;
        }
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $currentUser = $stmt->fetch() ?: null;
        $loaded = true;
        return $currentUser;
    }
}

if (!function_exists('login')) {
    function login($email, $password) {
        global $pdo;
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
        $_SESSION['customer_id'] = $user['customer_id'];


        

        
        // Log de l'activité
        logActivity($user['id'], 'login', 'Connexion utilisateur');
        
        return true;
    }
    
    return false;
}
}

if (!function_exists('logout')) {
    function logout() {
        if (isAuthenticated()) {
            logActivity($_SESSION['user_id'], 'logout', 'Déconnexion utilisateur');
        }
        
        session_destroy();
    }
}

if (!function_exists('register')) {
    function register($data) {
        global $pdo;
        
        // Vérification si l'email existe déjà
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Cet email est déjà utilisé'];
        }
        
        // Création de l'utilisateur
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, role, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        $role = $data['role'] ?? 'user';
        
        try {
            $stmt->execute([
                $data['name'],
                $data['email'],
                $hashedPassword,
                $role
            ]);
            
            return ['success' => true, 'message' => 'Utilisateur créé avec succès'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erreur lors de la création: ' . $e->getMessage()];
        }
    }
}

if (!function_exists('hasRole')) {
    function hasRole($role) {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
    }
}

if (!function_exists('featurePermissions')) {
    function featurePermissions() {
        return [
            'analytics' => 'can_access_analytics',
            'pipeline' => 'can_access_pipeline',
            'tasks' => 'can_access_tasks',
            'calls' => 'can_access_calls',
            'clients' => 'can_access_clients',
            'leads' => 'can_access_leads',
            'folders' => 'can_access_folders',
            'missions' => 'can_access_missions',
            'billing' => 'can_access_billing',
            'mail' => 'can_access_mail',
            'ai_agents' => 'can_access_ai_agents',
            'ai_actions' => 'can_access_ai_actions',
            'campaigns' => 'can_access_campaigns',
            'whatsapp' => 'can_access_whatsapp',
            'email' => 'can_access_email',
            'invoices' => 'can_access_invoices',
            'quotes' => 'can_access_quotes',
            'sales' => 'can_access_sales',
            'planning' => 'can_access_planning',
            'hr' => 'can_access_hr',
            'payroll' => 'can_access_payroll',
            'generate_payroll' => 'can_generate_payroll',
        ];
    }
}

if (!function_exists('currentUserCan')) {
    function currentUserCan($feature) {
        $user = getCurrentUser();
        if (!$user) return false;
        if (($user['role'] ?? '') === 'admin') return true;

        $field = featurePermissions()[$feature] ?? $feature;
        if (!preg_match('/^can_(access|generate)_[a-z0-9_]+$/', $field)) return false;
        return !array_key_exists($field, $user) || (bool)$user[$field];
    }
}

if (!function_exists('permissionForPath')) {
    function permissionForPath($path = null) {
        $path = strtolower(str_replace('\\', '/', $path ?: ($_SERVER['SCRIPT_NAME'] ?? '')));
        $path = preg_replace('#^.*/crm/#', '', $path);
        $routes = [
            'analytics' => ['analytics-', 'powerbi-'],
            'pipeline' => ['pipeline-'],
            'tasks' => ['tasks.php', 'api/tasks.php'],
            'calls' => ['calls.php', 'api/calls.php'],
            'clients' => ['customers', 'customer'],
            'leads' => ['leads', 'lead-'],
            'folders' => ['folders', 'folder-'],
            'missions' => ['missions', 'mission-', 'api/missions'],
            'campaigns' => ['campaign'],
            'whatsapp' => ['whatsapp'],
            'email' => ['email-', 'email.'],
            'invoices' => ['invoice'],
            'quotes' => ['quote'],
            'sales' => ['sales'],
            'planning' => ['shifts.php'],
            'hr' => ['employees.php', 'employee-', 'hr-'],
            'payroll' => ['payroll'],
            'ai_agents' => ['ai-dashboard', 'ai-logs', 'ai-insights', 'api/ai-', 'api/ai.'],
            'ai_actions' => ['ai-recalculate', 'api/ai-agents'],
        ];
        foreach ($routes as $feature => $patterns) {
            foreach ($patterns as $pattern) {
                if (strpos($path, $pattern) !== false) return $feature;
            }
        }
        return null;
    }
}

if (!function_exists('requireFeature')) {
    function requireFeature($feature = null, $api = false) {
        if (!isAuthenticated()) {
            if ($api) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Non authentifié']);
                exit;
            }
            header('Location: ../login.php');
            exit;
        }
        $feature = $feature ?: permissionForPath();
        if ($feature && !currentUserCan($feature)) {
            if ($api) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Accès interdit']);
                exit;
            }
            http_response_code(403);
            echo 'Accès interdit';
            exit;
        }
    }
}

if (!function_exists('hasPermission')) {
    function hasPermission($permission) {
        global $pdo;
        
        if (!isAuthenticated()) {
            return false;
        }
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM user_permissions up
            JOIN permissions p ON up.permission_id = p.id
            WHERE up.user_id = ? AND p.name = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $permission]);
        
        return $stmt->fetchColumn() > 0;
    }
}

if (!function_exists('logActivity')) {
    function logActivity($userId, $action, $description, $entityType = null, $entityId = null) {
        global $pdo;
        
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, description, entity_type, entity_id, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([$userId, $action, $description, $entityType, $entityId]);
    }
}

if (!function_exists('getRecentActivities')) {
    function getRecentActivities($limit = 10) {
        global $pdo;
        
        $stmt = $pdo->prepare("
            SELECT al.*, u.name as user_name 
            FROM activity_logs al
            JOIN users u ON al.user_id = u.id
            ORDER BY al.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        
        return $stmt->fetchAll();
    }
}
?>
