<?php
/**
 * API pour gérer les données des campagnes
 * Actions: performance, top, get, kpis
 */

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

// Vérifier la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$customer_id = $_SESSION['customer_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

$action = $_GET['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Action non reconnue'];

try {
    switch ($action) {
        case 'performance':
            $response = getCampaignPerformance($pdo, $customer_id);
            break;
            
        case 'top':
            $response = getTopCampaigns($pdo, $customer_id);
            break;
            
        case 'get':
            $campaign_id = intval($_GET['id'] ?? 0);
            $response = getCampaignById($pdo, $campaign_id, $customer_id);
            break;
            
        case 'kpis':
            $response = getCampaignKPIs($pdo, $customer_id);
            break;
            
        case 'list':
            $response = getCampaignsList($pdo, $customer_id);
            break;
            
        default:
            $response = ['status' => 'error', 'message' => 'Action non valide'];
    }
} catch (Exception $e) {
    error_log("API Campaigns error: " . $e->getMessage());
    $response = ['status' => 'error', 'message' => 'Erreur serveur: ' . $e->getMessage()];
}

echo json_encode($response);
exit;

/**
 * Récupérer les données de performance des campagnes (graphique)
 */
function getCampaignPerformance($pdo, $customer_id) {
    try {
        // Récupérer les 4 dernières semaines
        $labels = [];
        $openRates = [];
        $clickRates = [];
        $conversionRates = [];
        
        for ($i = 3; $i >= 0; $i--) {
            $weekStart = date('Y-m-d', strtotime("-$i weeks"));
            $weekEnd = date('Y-m-d', strtotime("-$i weeks +6 days"));
            $labels[] = "Sem " . (4 - $i);
            
            // Taux d'ouverture moyen de la semaine
            $stmt = $pdo->prepare("
                SELECT AVG(open_rate) as avg_open, AVG(click_rate) as avg_click, AVG(conversion_rate) as avg_conversion
                FROM campaigns 
                WHERE customer_id = ? 
                AND created_at BETWEEN ? AND ?
            ");
            $stmt->execute([$customer_id, $weekStart, $weekEnd]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $openRates[] = round(floatval($data['avg_open'] ?? 0), 1);
            $clickRates[] = round(floatval($data['avg_click'] ?? 0), 1);
            $conversionRates[] = round(floatval($data['avg_conversion'] ?? 0), 1);
        }
        
        return [
            'status' => 'success',
            'performance' => [
                'labels' => $labels,
                'openRate' => $openRates,
                'clickRate' => $clickRates,
                'conversionRate' => $conversionRates
            ]
        ];
    } catch (Exception $e) {
        error_log("Error getting campaign performance: " . $e->getMessage());
        return [
            'status' => 'success',
            'performance' => [
                'labels' => ['Sem 1', 'Sem 2', 'Sem 3', 'Sem 4'],
                'openRate' => [0, 0, 0, 0],
                'clickRate' => [0, 0, 0, 0],
                'conversionRate' => [0, 0, 0, 0]
            ]
        ];
    }
}

/**
 * Récupérer les top campagnes
 */
function getTopCampaigns($pdo, $customer_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, name, type, open_rate, leads, sent_count
            FROM campaigns 
            WHERE customer_id = ? 
            AND status IN ('sent', 'active', 'completed')
            ORDER BY open_rate DESC, leads DESC 
            LIMIT 5
        ");
        $stmt->execute([$customer_id]);
        $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'status' => 'success',
            'campaigns' => $campaigns
        ];
    } catch (Exception $e) {
        error_log("Error getting top campaigns: " . $e->getMessage());
        return [
            'status' => 'success',
            'campaigns' => []
        ];
    }
}

/**
 * Récupérer une campagne par ID
 */
function getCampaignById($pdo, $campaign_id, $customer_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM campaigns 
            WHERE id = ? AND customer_id = ?
        ");
        $stmt->execute([$campaign_id, $customer_id]);
        $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$campaign) {
            return [
                'status' => 'error',
                'message' => 'Campagne non trouvée'
            ];
        }
        
        return [
            'status' => 'success',
            'campaign' => $campaign
        ];
    } catch (Exception $e) {
        error_log("Error getting campaign: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Erreur de récupération'
        ];
    }
}

/**
 * Récupérer les KPIs des campagnes
 */
function getCampaignKPIs($pdo, $customer_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_count,
                AVG(CASE WHEN open_rate IS NOT NULL THEN open_rate END) as avg_open_rate,
                SUM(CASE WHEN leads IS NOT NULL THEN leads ELSE 0 END) as total_leads,
                AVG(CASE WHEN roi IS NOT NULL THEN roi END) as avg_roi
            FROM campaigns 
            WHERE customer_id = ?
        ");
        $stmt->execute([$customer_id]);
        $kpis = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'status' => 'success',
            'kpis' => [
                'activeCount' => intval($kpis['active_count'] ?? 0),
                'avgOpenRate' => round(floatval($kpis['avg_open_rate'] ?? 0), 1),
                'leadsGenerated' => intval($kpis['total_leads'] ?? 0),
                'avgRoi' => round(floatval($kpis['avg_roi'] ?? 0), 1)
            ]
        ];
    } catch (Exception $e) {
        error_log("Error getting campaign KPIs: " . $e->getMessage());
        return [
            'status' => 'success',
            'kpis' => [
                'activeCount' => 0,
                'avgOpenRate' => 0,
                'leadsGenerated' => 0,
                'avgRoi' => 0
            ]
        ];
    }
}

/**
 * Récupérer la liste des campagnes
 */
function getCampaignsList($pdo, $customer_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, name, type, status, channel, subject, 
                   created_at, sent_at, sent_count, open_rate, click_rate
            FROM campaigns 
            WHERE customer_id = ?
            ORDER BY created_at DESC 
            LIMIT 100
        ");
        $stmt->execute([$customer_id]);
        $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'status' => 'success',
            'campaigns' => $campaigns
        ];
    } catch (Exception $e) {
        error_log("Error getting campaigns list: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Erreur de récupération'
        ];
    }
}
?>
