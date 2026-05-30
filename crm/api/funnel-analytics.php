<?php
// filepath: /Applications/MAMP/htdocs/PP/webitech/WEB/crm/api/funnel-analytics.php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

// Vérification de l'authentification
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}
$customer_id = $_SESSION['customer_id'];

try {
    // Étapes de l'entonnoir (adapter selon ton modèle)
    $stages = [
        ['id' => 'visitors', 'name' => 'Visiteurs'],
        ['id' => 'leads', 'name' => 'Leads'],
        ['id' => 'prospects', 'name' => 'Prospects qualifiés'],
        ['id' => 'opportunities', 'name' => 'Opportunités'],
        ['id' => 'customers', 'name' => 'Clients']
    ];

    // Récupération des métriques principales (exemples à adapter)
    // Visiteurs (exemple: table visits)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM visits WHERE customer_id = :customer_id AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stmt->execute([':customer_id' => $customer_id]);
    $visitors = (int)$stmt->fetchColumn();

    // Leads (exemple: table leads)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE customer_id = :customer_id AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stmt->execute([':customer_id' => $customer_id]);
    $leads = (int)$stmt->fetchColumn();

    // Prospects qualifiés (exemple: leads status = 'qualified')
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE customer_id = :customer_id AND status = 'qualified' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stmt->execute([':customer_id' => $customer_id]);
    $prospects = (int)$stmt->fetchColumn();

    // Opportunités (exemple: table opportunities)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM opportunities o LEFT JOIN companies c ON o.company_id = c.id WHERE (o.customer_id = :customer_id OR c.customer_id = :customer_id) AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stmt->execute([':customer_id' => $customer_id]);
    $opportunities = (int)$stmt->fetchColumn();

    // Clients (exemple: companies status = 'client')
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM companies WHERE customer_id = :customer_id AND status = 'client' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stmt->execute([':customer_id' => $customer_id]);
    $customers = (int)$stmt->fetchColumn();

    // Calcul des taux de conversion par étape
    function conversion($from, $to) {
        return $from > 0 ? round(($to / $from) * 100, 1) : 0;
    }

    $metrics = [
        'visitors' => $visitors,
        'leads' => $leads,
        'prospects' => $prospects,
        'opportunities' => $opportunities,
        'customers' => $customers
    ];

    // Détail par étape pour le tableau
    $details = [
        [
            'id' => 'visitors',
            'name' => 'Visiteurs',
            'count' => $visitors,
            'conversion' => 100,
            'trend' => 2, // exemple : +2% vs période précédente
            'avgTime' => '—'
        ],
        [
            'id' => 'leads',
            'name' => 'Leads',
            'count' => $leads,
            'conversion' => conversion($visitors, $leads),
            'trend' => 1,
            'avgTime' => '2j'
        ],
        [
            'id' => 'prospects',
            'name' => 'Prospects qualifiés',
            'count' => $prospects,
            'conversion' => conversion($leads, $prospects),
            'trend' => -1,
            'avgTime' => '3j'
        ],
        [
            'id' => 'opportunities',
            'name' => 'Opportunités',
            'count' => $opportunities,
            'conversion' => conversion($prospects, $opportunities),
            'trend' => 0,
            'avgTime' => '5j'
        ],
        [
            'id' => 'customers',
            'name' => 'Clients',
            'count' => $customers,
            'conversion' => conversion($opportunities, $customers),
            'trend' => 3,
            'avgTime' => '7j'
        ]
    ];

    echo json_encode([
        'success' => true,
        'metrics' => $metrics,
        'details' => $details
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors du chargement des données : ' . $e->getMessage()
    ]);
}
?>