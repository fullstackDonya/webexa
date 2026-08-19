<?php

// voir toutes les erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/verify_subscriptions.php';
require_once 'config/database.php';

// Vérification de l'authentification
if (!isAuthenticated()) {
    header('Location: ../../login.php');
    exit;
}
$user_id = $_SESSION['user_id'] ?? null;

// Vérifier si l'onboarding est complété
$stmt = $pdo->prepare("SELECT onboarding_completed, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$user_email = $user['email'] ?? '';

// Rediriger vers setup-wizard si onboarding non complété (sauf si déjà sur setup-wizard ou settings)
$currentPage = basename($_SERVER['PHP_SELF']);
if (!$user['onboarding_completed'] && $currentPage !== 'setup-wizard.php' && $currentPage !== 'settings.php') {
    header('Location: setup-wizard.php');
    exit;
}

// Récupère le customer_id du user connecté
$stmt = $pdo->prepare("SELECT customer_id FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$customer_id = $stmt->fetchColumn();

$customer = null;
if ($customer_id) {
    // Charge les infos du customer
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
}



// Récupérer tous les customers pour la liste déroulante
$all_customers = $pdo->query("SELECT id, name, email FROM customers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);


// Pour pré-remplir le formulaire société si besoin
$existing_company = null;
if ($customer && !empty($customer['name'])) {
    $stmt = $pdo->prepare("SELECT * FROM companies WHERE name = ?");
    $stmt->execute([$customer['name']]);
    $existing_company = $stmt->fetch(PDO::FETCH_ASSOC);
}

// --- KPI server-side pour l'affichage initial du dashboard ---
$total_revenue = 0.0;
$active_clients = 0;
$conversion_rate = 0.0; // en %
$opportunities = 0;


if ($customer_id) {
    try {
        // Total revenue (somme des opportunités gagnées pour ce customer)
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(o.amount),0) FROM opportunities o LEFT JOIN companies c ON o.company_id = c.id WHERE (o.customer_id = ? OR c.customer_id = ?) AND o.stage = 'closed_won'");
        $stmt->execute([$customer_id, $customer_id]);
        $total_revenue = floatval($stmt->fetchColumn());

        // Active clients count (companies with status 'client' and active)
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT c.id) FROM companies c WHERE c.customer_id = ? AND c.status = 'client' AND c.is_active = 1 AND c.interne_customer = 0");
        $stmt->execute([$customer_id]);
        $active_clients = intval($stmt->fetchColumn());

        // Opportunities active (non fermées)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM opportunities o LEFT JOIN companies c ON o.company_id = c.id WHERE (o.customer_id = ? OR c.customer_id = ?) AND o.stage NOT IN ('closed_won','closed_lost')");
        $stmt->execute([$customer_id, $customer_id]);
        $opportunities = intval($stmt->fetchColumn());

        // Conversion rate: ratio closed_won / total deals sur les 30 derniers jours
        $stmt = $pdo->prepare("SELECT \
            SUM(CASE WHEN o.stage = 'closed_won' THEN 1 ELSE 0 END) as won_deals, \
            COUNT(*) as total_deals \
            FROM opportunities o LEFT JOIN companies c ON o.company_id = c.id 
            WHERE (o.customer_id = ? OR c.customer_id = ?) AND o.created_at BETWEEN DATE_SUB(NOW(), INTERVAL 30 DAY) AND NOW()");
        $stmt->execute([$customer_id, $customer_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && $row['total_deals'] > 0) {
            $conversion_rate = round(($row['won_deals'] / $row['total_deals']) * 100, 1);
        } else {
            $conversion_rate = 0.0;
        }
    } catch (Exception $e) {
        error_log('KPI load error: ' . $e->getMessage());
        // garder valeurs par défaut
    }
} else {
    // Fallback: si pas de customer_id, calculer des KPI basés sur l'utilisateur (assigned_to)
    try {
        // Total revenue for user's closed_won opportunities
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM opportunities WHERE assigned_to = ? AND stage = 'closed_won'");
        $stmt->execute([$user_id]);
        $total_revenue = floatval($stmt->fetchColumn());

        // Active clients assigned to user
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM companies WHERE assigned_to = ? AND status = 'client' AND is_active = 1 AND interne_customer = 0");
        $stmt->execute([$user_id]);
        $active_clients = intval($stmt->fetchColumn());

        // Open opportunities assigned to user
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM opportunities WHERE assigned_to = ? AND stage NOT IN ('closed_won','closed_lost')");
        $stmt->execute([$user_id]);
        $opportunities = intval($stmt->fetchColumn());

        // Conversion rate for user's deals in last 30 days
        $stmt = $pdo->prepare("SELECT SUM(CASE WHEN stage = 'closed_won' THEN 1 ELSE 0 END) as won_deals, COUNT(*) as total_deals FROM opportunities WHERE assigned_to = ? AND created_at BETWEEN DATE_SUB(NOW(), INTERVAL 30 DAY) AND NOW()");
        $stmt->execute([$user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && $row['total_deals'] > 0) {
            $conversion_rate = round(($row['won_deals'] / $row['total_deals']) * 100, 1);
        } else {
            $conversion_rate = 0.0;
        }
    } catch (Exception $e) {
        error_log('KPI fallback load error: ' . $e->getMessage());
    }
}

// // Debug flag: visit /crm/index.php?debug_dashboard=1 to enable extra server-side logging
// $debug_dashboard = isset($_GET['debug_dashboard']) && $_GET['debug_dashboard'] == '1';

// // If debug flag set, log samples of key DB queries (companies, opportunities, invoices, missions)
// if ($debug_dashboard) {
//     try {
//         // Companies for this customer or assigned to user
//         if ($customer_id) {
//             $stmt = $pdo->prepare("SELECT id, name, status, is_active FROM companies WHERE customer_id = ? LIMIT 10");
//             $stmt->execute([$customer_id]);
//             $companies_sample = $stmt->fetchAll(PDO::FETCH_ASSOC);
//         } else {
//             $stmt = $pdo->prepare("SELECT id, name, status, is_active FROM companies WHERE assigned_to = ? LIMIT 10");
//             $stmt->execute([$user_id]);
//             $companies_sample = $stmt->fetchAll(PDO::FETCH_ASSOC);
//         }

//         // Opportunities sample
//         $stmt = $pdo->prepare("SELECT id, company_id, assigned_to, stage, amount, created_at FROM opportunities " . ($customer_id ? "INNER JOIN companies c ON opportunities.company_id = c.id WHERE c.customer_id = ?" : "WHERE assigned_to = ?") . " ORDER BY created_at DESC LIMIT 10");
//         $stmt->execute([$customer_id ? $customer_id : $user_id]);
//         $opps_sample = $stmt->fetchAll(PDO::FETCH_ASSOC);

//         // Invoices sample
//         if ($customer_id) {
//             $stmt = $pdo->prepare("SELECT i.id, i.issued_at, i.total, c.name as company_name FROM invoices i INNER JOIN folders f ON i.folder_id = f.id INNER JOIN companies c ON f.company_id = c.id WHERE c.customer_id = ? ORDER BY i.issued_at DESC LIMIT 10");
//             $stmt->execute([$customer_id]);
//             $invoices_sample = $stmt->fetchAll(PDO::FETCH_ASSOC);
//         } else {
//             $invoices_sample = [];
//         }

//         // Missions sample
//         if ($customer_id) {
//             $stmt = $pdo->prepare("SELECT m.id, m.status, m.created_at, c.name as company_name FROM missions m INNER JOIN folders f ON m.folder_id = f.id INNER JOIN companies c ON f.company_id = c.id WHERE c.customer_id = ? ORDER BY m.created_at DESC LIMIT 10");
//             $stmt->execute([$customer_id]);
//             $missions_sample = $stmt->fetchAll(PDO::FETCH_ASSOC);
//         } else {
//             $missions_sample = [];
//         }

//         error_log('debug_dashboard: user_id=' . intval($user_id) . ' customer_id=' . ($customer_id ? $customer_id : 'NULL'));
//         error_log('debug_dashboard: total_revenue=' . $total_revenue . ' active_clients=' . $active_clients . ' conversion_rate=' . $conversion_rate . ' opportunities=' . $opportunities);
//         error_log('debug_dashboard: companies_sample=' . json_encode($companies_sample, JSON_UNESCAPED_UNICODE));
//         error_log('debug_dashboard: opps_sample=' . json_encode($opps_sample, JSON_UNESCAPED_UNICODE));
//         error_log('debug_dashboard: invoices_sample=' . json_encode($invoices_sample, JSON_UNESCAPED_UNICODE));
//         error_log('debug_dashboard: missions_sample=' . json_encode($missions_sample, JSON_UNESCAPED_UNICODE));

//         // Optionally expose a tiny debug object to the template (for quick visibility)
//         $dashboard_debug = [
//             'user_id' => intval($user_id),
//             'customer_id' => $customer_id ? intval($customer_id) : null,
//             'total_revenue' => $total_revenue,
//             'active_clients' => $active_clients,
//             'conversion_rate' => $conversion_rate,
//             'opportunities' => $opportunities,
//             'companies_sample' => $companies_sample,
//             'opps_sample' => $opps_sample,
//             'invoices_sample' => $invoices_sample,
//             'missions_sample' => $missions_sample
//         ];
//     } catch (Exception $e) {
//         error_log('debug_dashboard fetch failed: ' . $e->getMessage());
//     }
// }