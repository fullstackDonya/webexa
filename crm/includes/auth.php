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
        
        if (!isAuthenticated()) {
            return null;
        }
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
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
