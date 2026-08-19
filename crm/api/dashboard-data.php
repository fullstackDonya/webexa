<?php
/**
 * API Dashboard Data - Fournit toutes les données analytiques pour le tableau de bord
 * Endpoint: api/dashboard-data.php?period=month
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Cache simple pour éviter trop de requêtes
$cacheFile = __DIR__ . '/../cache/dashboard_' . session_id() . '.json';
$cacheTime = 120; // 2 minutes

session_start();

// Vérifier l'authentification
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

// Vérifier le cache
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
    readfile($cacheFile);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/permission-bootstrap.php';

try {
    // Utiliser la connexion PDO existante de config/database.php
    $conn = $pdo;
    
    $user_id = $_SESSION['user_id'];
    $customer_id = $_SESSION['customer_id'] ?? null;
    $period = $_GET['period'] ?? 'month';
    
    // Définir les plages de dates selon la période
    $dateRanges = calculateDateRanges($period);
    
    // Récupérer toutes les données
    $response = [
        'success' => true,
        'period' => $period,
        'kpis' => getKPIs($conn, $customer_id, $user_id, $dateRanges),
        'stats' => getMiniStats($conn, $customer_id, $user_id, $dateRanges),
        'charts' => getChartsData($conn, $customer_id, $user_id, $dateRanges),
        'topDeals' => getTopDeals($conn, $customer_id, $user_id),
        'activities' => getRecentActivities($conn, $customer_id, $user_id)
    ];
    
    // Sauvegarder le cache
    if (!is_dir(__DIR__ . '/../cache')) {
        mkdir(__DIR__ . '/../cache', 0755, true);
    }
    file_put_contents($cacheFile, json_encode($response));
    
    echo json_encode($response);

} catch (Exception $e) {
    error_log("Dashboard Data Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erreur serveur',
        'debug' => $e->getMessage()
    ]);
}

/**
 * Calculer les plages de dates selon la période
 */
function calculateDateRanges($period) {
    $now = new DateTime();
    $ranges = [];
    
    switch ($period) {
        case 'today':
            $ranges['start'] = $now->format('Y-m-d 00:00:00');
            $ranges['end'] = $now->format('Y-m-d 23:59:59');
            $ranges['previous_start'] = $now->modify('-1 day')->format('Y-m-d 00:00:00');
            $ranges['previous_end'] = $now->format('Y-m-d 23:59:59');
            break;
            
        case 'week':
            $now->modify('this week');
            $ranges['start'] = $now->format('Y-m-d 00:00:00');
            $ranges['end'] = (new DateTime())->format('Y-m-d 23:59:59');
            $ranges['previous_start'] = $now->modify('-1 week')->format('Y-m-d 00:00:00');
            $ranges['previous_end'] = $now->modify('+6 days')->format('Y-m-d 23:59:59');
            break;
            
        case 'quarter':
            $quarter = ceil($now->format('n') / 3);
            $year = $now->format('Y');
            $ranges['start'] = date('Y-m-d 00:00:00', mktime(0, 0, 0, ($quarter - 1) * 3 + 1, 1, $year));
            $ranges['end'] = date('Y-m-d 23:59:59', mktime(23, 59, 59, $quarter * 3 + 1, 0, $year));
            $ranges['previous_start'] = date('Y-m-d 00:00:00', mktime(0, 0, 0, ($quarter - 2) * 3 + 1, 1, $year));
            $ranges['previous_end'] = date('Y-m-d 23:59:59', mktime(23, 59, 59, ($quarter - 1) * 3 + 1, 0, $year));
            break;
            
        case 'year':
            $ranges['start'] = $now->format('Y-01-01 00:00:00');
            $ranges['end'] = $now->format('Y-12-31 23:59:59');
            $ranges['previous_start'] = $now->modify('-1 year')->format('Y-01-01 00:00:00');
            $ranges['previous_end'] = $now->format('Y-12-31 23:59:59');
            break;
            
        case 'month':
        default:
            $ranges['start'] = $now->format('Y-m-01 00:00:00');
            $ranges['end'] = $now->format('Y-m-t 23:59:59');
            $ranges['previous_start'] = $now->modify('-1 month')->format('Y-m-01 00:00:00');
            $ranges['previous_end'] = $now->format('Y-m-t 23:59:59');
            break;
    }
    
    return $ranges;
}

/**
 * Récupérer les KPIs principaux avec comparaison période précédente
 */
function getKPIs($conn, $customer_id, $user_id, $ranges) {
    $kpis = [
        'revenue' => 0,
        'revenueTrend' => ['direction' => 'up', 'text' => '+0%'],
        'clients' => 0,
        'clientsTrend' => ['direction' => 'up', 'text' => '+0'],
        'opportunities' => 0,
        'opportunitiesTrend' => ['direction' => 'up', 'text' => '+0'],
        'conversion' => 0,
        'conversionTrend' => ['direction' => 'up', 'text' => '+0%']
    ];
    
    // Revenue actuel
    $sql = "SELECT COALESCE(SUM(amount), 0) as total 
            FROM opportunities 
            WHERE stage = 'closed_won'
            AND closed_date BETWEEN :start AND :end";
    
    if ($customer_id) {
        $sql .= " AND customer_id = :customer_id";
    } else {
        $sql .= " AND assigned_to = :user_id";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':start', $ranges['start']);
    $stmt->bindParam(':end', $ranges['end']);
    if ($customer_id) {
        $stmt->bindParam(':customer_id', $customer_id);
    } else {
        $stmt->bindParam(':user_id', $user_id);
    }
    $stmt->execute();
    $current = $stmt->fetch(PDO::FETCH_ASSOC);
    $kpis['revenue'] = floatval($current['total']);
    
    // Revenue période précédente
    $stmt->bindParam(':start', $ranges['previous_start']);
    $stmt->bindParam(':end', $ranges['previous_end']);
    if ($customer_id) {
        $stmt->bindParam(':customer_id', $customer_id);
    } else {
        $stmt->bindParam(':user_id', $user_id);
    }
    $stmt->execute();
    $previous = $stmt->fetch(PDO::FETCH_ASSOC);
    $previousRevenue = floatval($previous['total']);
    
    if ($previousRevenue > 0) {
        $change = (($kpis['revenue'] - $previousRevenue) / $previousRevenue) * 100;
        $kpis['revenueTrend'] = [
            'direction' => $change >= 0 ? 'up' : 'down',
            'text' => ($change >= 0 ? '+' : '') . number_format($change, 1) . '% vs période précédente'
        ];
    }
    
    // Clients actifs
    $sql = "SELECT COUNT(*) as total 
            FROM companies 
            WHERE status = 'client' 
            AND is_active = 1";
    
    if ($customer_id) {
        $sql .= " AND customer_id = :customer_id";
    }
    
    $stmt = $conn->prepare($sql);
    if ($customer_id) {
        $stmt->bindParam(':customer_id', $customer_id);
    }
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $kpis['clients'] = intval($result['total']);
    
    // Nouveaux clients cette période
    $sql = "SELECT COUNT(*) as total 
            FROM companies 
            WHERE status = 'client' 
            AND created_at BETWEEN :start AND :end";
    
    if ($customer_id) {
        $sql .= " AND customer_id = :customer_id";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':start', $ranges['start']);
    $stmt->bindParam(':end', $ranges['end']);
    if ($customer_id) {
        $stmt->bindParam(':customer_id', $customer_id);
    }
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $newClients = intval($result['total']);
    
    $kpis['clientsTrend'] = [
        'direction' => 'up',
        'text' => '+' . $newClients . ' nouveaux clients'
    ];
    
    // Opportunités actives
    $sql = "SELECT COUNT(*) as total 
            FROM opportunities 
            WHERE stage NOT IN ('closed_won', 'closed_lost')";
    
    if ($customer_id) {
        $sql .= " AND customer_id = :customer_id";
    } else {
        $sql .= " AND assigned_to = :user_id";
    }
    
    $stmt = $conn->prepare($sql);
    if ($customer_id) {
        $stmt->bindParam(':customer_id', $customer_id);
    } else {
        $stmt->bindParam(':user_id', $user_id);
    }
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $kpis['opportunities'] = intval($result['total']);
    
    // Valeur totale du pipeline
    $sql = "SELECT COALESCE(SUM(amount), 0) as total 
            FROM opportunities 
            WHERE stage NOT IN ('closed_won', 'closed_lost')";
    
    if ($customer_id) {
        $sql .= " AND customer_id = :customer_id";
    } else {
        $sql .= " AND assigned_to = :user_id";
    }
    
    $stmt = $conn->prepare($sql);
    if ($customer_id) {
        $stmt->bindParam(':customer_id', $customer_id);
    } else {
        $stmt->bindParam(':user_id', $user_id);
    }
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $pipelineValue = floatval($result['total']);
    
    $kpis['opportunitiesTrend'] = [
        'direction' => 'up',
        'text' => 'Valeur: €' . number_format($pipelineValue, 0, ',', ' ')
    ];
    
    // Taux de conversion
    $sql = "SELECT 
                COUNT(CASE WHEN stage = 'closed_won' THEN 1 END) as won,
                COUNT(*) as total
            FROM opportunities
            WHERE created_at BETWEEN :start AND :end";
    
    if ($customer_id) {
        $sql .= " AND customer_id = :customer_id";
    } else {
        $sql .= " AND assigned_to = :user_id";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':start', $ranges['start']);
    $stmt->bindParam(':end', $ranges['end']);
    if ($customer_id) {
        $stmt->bindParam(':customer_id', $customer_id);
    } else {
        $stmt->bindParam(':user_id', $user_id);
    }
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['total'] > 0) {
        $kpis['conversion'] = ($result['won'] / $result['total']) * 100;
    }
    
    // Conversion période précédente
    $stmt->bindParam(':start', $ranges['previous_start']);
    $stmt->bindParam(':end', $ranges['previous_end']);
    if ($customer_id) {
        $stmt->bindParam(':customer_id', $customer_id);
    } else {
        $stmt->bindParam(':user_id', $user_id);
    }
    $stmt->execute();
    $prevResult = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($prevResult['total'] > 0) {
        $prevConversion = ($prevResult['won'] / $prevResult['total']) * 100;
        $convChange = $kpis['conversion'] - $prevConversion;
        $kpis['conversionTrend'] = [
            'direction' => $convChange >= 0 ? 'up' : 'down',
            'text' => ($convChange >= 0 ? '+' : '') . number_format($convChange, 1) . '% vs période précédente'
        ];
    }
    
    return $kpis;
}

/**
 * Récupérer les mini statistiques
 */
function getMiniStats($conn, $customer_id, $user_id, $ranges) {
    $stats = [
        'leads' => 0,
        'emails' => 0,
        'whatsapp' => 0,
        'meetings' => 0,
        'tasks' => 0,
        'pipeline' => 0
    ];
    
    // Leads actifs
    $sql = "SELECT COUNT(*) as total FROM leads WHERE status NOT IN ('converted', 'lost')";
    if ($customer_id) {
        $sql .= " AND customer_id = :customer_id";
    }
    $stmt = $conn->prepare($sql);
    if ($customer_id) {
        $stmt->bindParam(':customer_id', $customer_id);
    }
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['leads'] = intval($result['total']);
    
    // Emails envoyés cette période
    $sql = "SELECT COUNT(*) as total FROM campaign_logs 
            WHERE type = 'email' 
            AND sent_at BETWEEN :start AND :end";
    if ($customer_id) {
        $sql .= " AND customer_id = :customer_id";
    }
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':start', $ranges['start']);
    $stmt->bindParam(':end', $ranges['end']);
    if ($customer_id) {
        $stmt->bindParam(':customer_id', $customer_id);
    }
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['emails'] = intval($result['total']);
    
    // Messages WhatsApp
    $sql = "SELECT COUNT(*) as total FROM campaign_logs 
            WHERE type = 'whatsapp' 
            AND sent_at BETWEEN :start AND :end";
    if ($customer_id) {
        $sql .= " AND customer_id = :customer_id";
    }
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':start', $ranges['start']);
    $stmt->bindParam(':end', $ranges['end']);
    if ($customer_id) {
        $stmt->bindParam(':customer_id', $customer_id);
    }
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['whatsapp'] = intval($result['total']);
    
    // RDV cette semaine
    $weekStart = date('Y-m-d 00:00:00', strtotime('this week'));
    $weekEnd = date('Y-m-d 23:59:59', strtotime('this week +6 days'));
    $sql = "SELECT COUNT(*) as total FROM activities 
            WHERE type = 'meeting' 
            AND scheduled_at BETWEEN :start AND :end";
    if ($customer_id) {
        $sql .= " AND customer_id = :customer_id";
    }
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':start', $weekStart);
    $stmt->bindParam(':end', $weekEnd);
    if ($customer_id) {
        $stmt->bindParam(':customer_id', $customer_id);
    }
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['meetings'] = intval($result['total']);
    
    // Tâches en cours
    $sql = "SELECT COUNT(*) as total FROM missions WHERE status IN ('pending', 'in_progress')";
    if ($customer_id) {
        $sql .= " AND customer_id = :customer_id";
    }
    $stmt = $conn->prepare($sql);
    if ($customer_id) {
        $stmt->bindParam(':customer_id', $customer_id);
    }
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['tasks'] = intval($result['total']);
    
    // Valeur du pipeline
    $sql = "SELECT COALESCE(SUM(amount), 0) as total 
            FROM opportunities 
            WHERE stage NOT IN ('closed_won', 'closed_lost')";
    if ($customer_id) {
        $sql .= " AND customer_id = :customer_id";
    } else {
        $sql .= " AND assigned_to = :user_id";
    }
    $stmt = $conn->prepare($sql);
    if ($customer_id) {
        $stmt->bindParam(':customer_id', $customer_id);
    } else {
        $stmt->bindParam(':user_id', $user_id);
    }
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['pipeline'] = floatval($result['total']);
    
    return $stats;
}

/**
 * Récupérer les données pour les graphiques
 */
function getChartsData($conn, $customer_id, $user_id, $ranges) {
    return [
        'revenue' => getRevenueChartData($conn, $customer_id, $user_id),
        'funnel' => getFunnelChartData($conn, $customer_id, $user_id),
        'pipeline' => getPipelineChartData($conn, $customer_id, $user_id),
        'sources' => getSourcesChartData($conn, $customer_id, $user_id)
    ];
}

/**
 * Données graphique revenue (12 derniers mois)
 */
function getRevenueChartData($conn, $customer_id, $user_id) {
    $labels = [];
    $values = [];
    $target = [];
    
    $monthNames = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];
    
    for ($i = 11; $i >= 0; $i--) {
        $date = new DateTime();
        $date->modify("-$i months");
        $labels[] = $monthNames[intval($date->format('n')) - 1];
        
        $start = $date->format('Y-m-01 00:00:00');
        $end = $date->format('Y-m-t 23:59:59');
        
        $sql = "SELECT COALESCE(SUM(amount), 0) as total 
                FROM opportunities 
                WHERE stage = 'closed_won'
                AND closed_date BETWEEN :start AND :end";
        
        if ($customer_id) {
            $sql .= " AND customer_id = :customer_id";
        } else {
            $sql .= " AND assigned_to = :user_id";
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':start', $start);
        $stmt->bindParam(':end', $end);
        if ($customer_id) {
            $stmt->bindParam(':customer_id', $customer_id);
        } else {
            $stmt->bindParam(':user_id', $user_id);
        }
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $values[] = floatval($result['total']);
        $target[] = 50000 + ((11 - $i) * 2500); // Objectif progressif
    }
    
    return [
        'labels' => $labels,
        'values' => $values,
        'target' => $target
    ];
}

/**
 * Données entonnoir de conversion
 */
function getFunnelChartData($conn, $customer_id, $user_id) {
    $labels = ['Visiteurs', 'Leads', 'Qualifiés', 'Opportunités', 'Clients'];
    $values = [];
    
    // Visiteurs (simulation - pourrait venir de Google Analytics)
    $values[] = 1000;
    
    // Leads
    $sql = "SELECT COUNT(*) as total FROM leads";
    if ($customer_id) {
        $sql .= " WHERE customer_id = :customer_id";
    }
    $stmt = $conn->prepare($sql);
    if ($customer_id) {
        $stmt->bindParam(':customer_id', $customer_id);
    }
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $values[] = intval($result['total']);
    
    // Qualifiés (score > 50)
    $sql = "SELECT COUNT(*) as total FROM leads WHERE lead_score > 50";
    if ($customer_id) {
        $sql .= " AND customer_id = :customer_id";
    }
    $stmt = $conn->prepare($sql);
    if ($customer_id) {
        $stmt->bindParam(':customer_id', $customer_id);
    }
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $values[] = intval($result['total']);
    
    // Opportunités
    $sql = "SELECT COUNT(*) as total FROM opportunities";
    if ($customer_id) {
        $sql .= " WHERE customer_id = :customer_id";
    } else {
        $sql .= " WHERE assigned_to = :user_id";
    }
    $stmt = $conn->prepare($sql);
    if ($customer_id) {
        $stmt->bindParam(':customer_id', $customer_id);
    } else {
        $stmt->bindParam(':user_id', $user_id);
    }
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $values[] = intval($result['total']);
    
    // Clients
    $sql = "SELECT COUNT(*) as total FROM companies WHERE status = 'client'";
    if ($customer_id) {
        $sql .= " AND customer_id = :customer_id";
    }
    $stmt = $conn->prepare($sql);
    if ($customer_id) {
        $stmt->bindParam(':customer_id', $customer_id);
    }
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $values[] = intval($result['total']);
    
    return [
        'labels' => $labels,
        'values' => $values
    ];
}

/**
 * Données pipeline par étape
 */
function getPipelineChartData($conn, $customer_id, $user_id) {
    $stages = [
        'prospecting' => 'Prospection',
        'qualification' => 'Qualification',
        'proposal' => 'Proposition',
        'negotiation' => 'Négociation',
        'closing' => 'Closing'
    ];
    
    $labels = [];
    $values = [];
    
    foreach ($stages as $key => $label) {
        $sql = "SELECT COUNT(*) as total FROM opportunities WHERE stage = :stage";
        if ($customer_id) {
            $sql .= " AND customer_id = :customer_id";
        } else {
            $sql .= " AND assigned_to = :user_id";
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':stage', $key);
        if ($customer_id) {
            $stmt->bindParam(':customer_id', $customer_id);
        } else {
            $stmt->bindParam(':user_id', $user_id);
        }
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $labels[] = $label;
        $values[] = intval($result['total']);
    }
    
    return [
        'labels' => $labels,
        'values' => $values
    ];
}

/**
 * Données sources de leads
 */
function getSourcesChartData($conn, $customer_id, $user_id) {
    $sql = "SELECT source, COUNT(*) as total 
            FROM leads 
            WHERE source IS NOT NULL";
    
    if ($customer_id) {
        $sql .= " AND customer_id = :customer_id";
    }
    
    $sql .= " GROUP BY source ORDER BY total DESC LIMIT 5";
    
    $stmt = $conn->prepare($sql);
    if ($customer_id) {
        $stmt->bindParam(':customer_id', $customer_id);
    }
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $labels = [];
    $values = [];
    
    foreach ($results as $row) {
        $labels[] = ucfirst($row['source']);
        $values[] = intval($row['total']);
    }
    
    // Si pas de données, retourner des données par défaut
    if (empty($labels)) {
        $labels = ['Site Web', 'Référencement', 'Cold Calling', 'Réseaux Sociaux', 'Événements'];
        $values = [35, 25, 20, 15, 5];
    }
    
    return [
        'labels' => $labels,
        'values' => $values
    ];
}

/**
 * Récupérer les meilleures opportunités
 */
function getTopDeals($conn, $customer_id, $user_id) {
    $sql = "SELECT o.*, c.name as company 
            FROM opportunities o
            LEFT JOIN companies c ON o.company_id = c.id
            WHERE o.stage NOT IN ('closed_won', 'closed_lost')";
    
    if ($customer_id) {
        $sql .= " AND o.customer_id = :customer_id";
    } else {
        $sql .= " AND o.assigned_to = :user_id";
    }
    
    $sql .= " ORDER BY o.amount DESC, o.probability DESC LIMIT 5";
    
    $stmt = $conn->prepare($sql);
    if ($customer_id) {
        $stmt->bindParam(':customer_id', $customer_id);
    } else {
        $stmt->bindParam(':user_id', $user_id);
    }
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Récupérer les activités récentes
 */
function getRecentActivities($conn, $customer_id, $user_id) {
    $activities = [];
    
    // Nouvelles opportunités
    $sql = "SELECT o.name, o.created_at, c.name as company
            FROM opportunities o
            LEFT JOIN companies c ON o.company_id = c.id
            WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    
    if ($customer_id) {
        $sql .= " AND o.customer_id = :customer_id";
    } else {
        $sql .= " AND o.assigned_to = :user_id";
    }
    
    $sql .= " ORDER BY o.created_at DESC LIMIT 5";
    
    $stmt = $conn->prepare($sql);
    if ($customer_id) {
        $stmt->bindParam(':customer_id', $customer_id);
    } else {
        $stmt->bindParam(':user_id', $user_id);
    }
    $stmt->execute();
    $opps = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($opps as $opp) {
        $activities[] = [
            'icon' => 'bullseye',
            'title' => 'Nouvelle opportunité',
            'description' => $opp['name'] . ' - ' . ($opp['company'] ?? 'Sans entreprise'),
            'time' => timeAgo($opp['created_at'])
        ];
    }
    
    // Nouveaux leads
    $sql = "SELECT name, created_at FROM leads 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    
    if ($customer_id) {
        $sql .= " AND customer_id = :customer_id";
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT 5";
    
    $stmt = $conn->prepare($sql);
    if ($customer_id) {
        $stmt->bindParam(':customer_id', $customer_id);
    }
    $stmt->execute();
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($leads as $lead) {
        $activities[] = [
            'icon' => 'user-plus',
            'title' => 'Nouveau lead',
            'description' => $lead['name'],
            'time' => timeAgo($lead['created_at'])
        ];
    }
    
    // Emails envoyés
    $sql = "SELECT subject, sent_at FROM campaign_logs 
            WHERE type = 'email' 
            AND sent_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    
    if ($customer_id) {
        $sql .= " AND customer_id = :customer_id";
    }
    
    $sql .= " ORDER BY sent_at DESC LIMIT 3";
    
    $stmt = $conn->prepare($sql);
    if ($customer_id) {
        $stmt->bindParam(':customer_id', $customer_id);
    }
    $stmt->execute();
    $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($emails as $email) {
        $activities[] = [
            'icon' => 'envelope',
            'title' => 'Email envoyé',
            'description' => $email['subject'] ?? 'Sans sujet',
            'time' => timeAgo($email['sent_at'])
        ];
    }
    
    // Trier par date
    usort($activities, function($a, $b) {
        return strcmp($b['time'], $a['time']);
    });
    
    return array_slice($activities, 0, 10);
}

/**
 * Convertir datetime en format "il y a X"
 */
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'À l\'instant';
    } elseif ($diff < 3600) {
        return 'Il y a ' . floor($diff / 60) . ' min';
    } elseif ($diff < 86400) {
        return 'Il y a ' . floor($diff / 3600) . ' h';
    } elseif ($diff < 604800) {
        return 'Il y a ' . floor($diff / 86400) . ' j';
    } else {
        return date('d/m/Y', $timestamp);
    }
}
