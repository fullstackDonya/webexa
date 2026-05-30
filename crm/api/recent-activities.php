<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

try {
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    
    // Récupération des activités récentes avec les informations utilisateur
    $stmt = $pdo->prepare("
        SELECT 
            al.id,
            al.action,
            al.table_name,
            al.record_id,
            al.created_at,
            al.ip_address,
            CONCAT(u.first_name, ' ', u.last_name) as user_name,
            u.avatar,
            u.role,
            CASE 
                WHEN al.action = 'CREATE' AND al.table_name = 'opportunities' THEN 'Nouvelle opportunité créée'
                WHEN al.action = 'UPDATE' AND al.table_name = 'opportunities' THEN 'Opportunité mise à jour'
                WHEN al.action = 'CREATE' AND al.table_name = 'companies' THEN 'Nouvelle entreprise ajoutée'
                WHEN al.action = 'UPDATE' AND al.table_name = 'companies' THEN 'Entreprise mise à jour'
                WHEN al.action = 'CREATE' AND al.table_name = 'contacts' THEN 'Nouveau contact ajouté'
                WHEN al.action = 'UPDATE' AND al.table_name = 'contacts' THEN 'Contact mis à jour'
                WHEN al.action = 'CREATE' AND al.table_name = 'activities' THEN 'Nouvelle activité planifiée'
                WHEN al.action = 'LOGIN' THEN 'Connexion au système'
                WHEN al.action = 'LOGOUT' THEN 'Déconnexion du système'
                WHEN al.action = 'POWERBI_ACCESS' THEN 'Accès au rapport Power BI'
                ELSE CONCAT(al.action, ' sur ', al.table_name)
            END as description
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.created_at DESC
        LIMIT :limit
    ");
    
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $activities = $stmt->fetchAll();

    // Enrichissement des données d'activité
    $enriched_activities = [];
    foreach ($activities as $activity) {
        $enriched_activity = [
            'id' => $activity['id'],
            'action' => $activity['action'],
            'description' => $activity['description'],
            'user_name' => $activity['user_name'] ?? 'Système',
            'user_avatar' => $activity['avatar'],
            'user_role' => $activity['role'],
            'created_at' => $activity['created_at'],
            'ip_address' => $activity['ip_address'],
            'table_name' => $activity['table_name'],
            'record_id' => $activity['record_id']
        ];

        // Ajout d'informations contextuelles selon le type d'action
        if ($activity['table_name'] && $activity['record_id']) {
            $enriched_activity['context'] = getContextInfo($pdo, $activity['table_name'], $activity['record_id']);
        }

        $enriched_activities[] = $enriched_activity;
    }

    // Statistiques d'activité du jour
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as today_total,
            COUNT(CASE WHEN al.action = 'CREATE' THEN 1 END) as today_creates,
            COUNT(CASE WHEN al.action = 'UPDATE' THEN 1 END) as today_updates,
            COUNT(DISTINCT al.user_id) as active_users_today
        FROM activity_logs al
        WHERE DATE(al.created_at) = CURDATE()
    ");
    $stmt->execute();
    $today_stats = $stmt->fetch();

    $response = [
        'success' => true,
        'activities' => $enriched_activities,
        'stats' => [
            'total_today' => intval($today_stats['today_total']),
            'creates_today' => intval($today_stats['today_creates']),
            'updates_today' => intval($today_stats['today_updates']),
            'active_users_today' => intval($today_stats['active_users_today'])
        ],
        'generated_at' => date('Y-m-d H:i:s')
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors du chargement des activités: ' . $e->getMessage(),
        'activities' => []
    ]);
}

// Fonction pour récupérer les informations contextuelles
function getContextInfo($pdo, $table_name, $record_id) {
    try {
        switch ($table_name) {
            case 'opportunities':
                $stmt = $pdo->prepare("SELECT title, amount FROM opportunities WHERE id = ?");
                $stmt->execute([$record_id]);
                $data = $stmt->fetch();
                return $data ? ['title' => $data['title'], 'amount' => $data['amount']] : null;
                
            case 'companies':
                $stmt = $pdo->prepare("SELECT name, status FROM companies WHERE id = ?");
                $stmt->execute([$record_id]);
                $data = $stmt->fetch();
                return $data ? ['name' => $data['name'], 'status' => $data['status']] : null;
                
            case 'contacts':
                $stmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) as name, email FROM contacts WHERE id = ?");
                $stmt->execute([$record_id]);
                $data = $stmt->fetch();
                return $data ? ['name' => $data['name'], 'email' => $data['email']] : null;
                
            case 'activities':
                $stmt = $pdo->prepare("SELECT subject, type FROM activities WHERE id = ?");
                $stmt->execute([$record_id]);
                $data = $stmt->fetch();
                return $data ? ['subject' => $data['subject'], 'type' => $data['type']] : null;
                
            default:
                return null;
        }
    } catch (Exception $e) {
        return null;
    }
}
?>
