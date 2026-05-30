<?php
// filepath: /Applications/MAMP/htdocs/PP/webitech/WEB/crm/api/performance-data.php
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
    // Métriques globales (exemples à adapter selon ta base)
    // Performance globale (exemple : moyenne des scores d'équipe)
    $stmt = $pdo->prepare("
        SELECT AVG(team_score) as teamScore
        FROM teams
        WHERE customer_id = :customer_id
    ");
    $stmt->execute([':customer_id' => $customer_id]);
    $teamScore = round((float)$stmt->fetchColumn(), 2);

    // Croissance mensuelle (exemple : évolution du CA sur 2 mois)
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN MONTH(created_at) = MONTH(CURRENT_DATE) THEN amount ELSE 0 END) as this_month,
            SUM(CASE WHEN MONTH(created_at) = MONTH(CURRENT_DATE) - 1 THEN amount ELSE 0 END) as last_month
        FROM opportunities o
        INNER JOIN companies c ON o.company_id = c.id
        WHERE c.customer_id = :customer_id
    ");
    $stmt->execute([':customer_id' => $customer_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $growth = ($row['last_month'] > 0) ? round((($row['this_month'] - $row['last_month']) / $row['last_month']) * 100, 1) : 0;

    // Objectifs atteints (exemple : nombre d'objectifs mensuels atteints)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM targets
        WHERE customer_id = :customer_id AND period = 'monthly' AND achieved_value >= target_value
    ");
    $stmt->execute([':customer_id' => $customer_id]);
    $goalsAchieved = (int)$stmt->fetchColumn();

    // Performance globale (exemple : % d'opportunités gagnées)
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN o.stage = 'closed_won' THEN 1 ELSE 0 END) as won
        FROM opportunities o
        INNER JOIN companies c ON o.company_id = c.id
        WHERE c.customer_id = :customer_id
    ");
    $stmt->execute([':customer_id' => $customer_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $overall = ($row['total'] > 0) ? round(($row['won'] / $row['total']) * 100, 1) : 0;

    // Détails par équipe (exemple)
    $stmt = $pdo->prepare("
        SELECT t.name as team, COUNT(o.id) as opportunities, 
            SUM(CASE WHEN o.stage = 'closed_won' THEN 1 ELSE 0 END) as won,
            ROUND(AVG(o.amount), 2) as avgDeal
        FROM teams t
        LEFT JOIN users u ON u.team_id = t.id
        LEFT JOIN opportunities o ON o.assigned_to = u.id
        LEFT JOIN companies c ON o.company_id = c.id
        WHERE t.customer_id = :customer_id
        GROUP BY t.id
        ORDER BY t.name
    ");
    $stmt->execute([':customer_id' => $customer_id]);
    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'metrics' => [
            'overall' => $overall,
            'growth' => $growth,
            'goalsAchieved' => $goalsAchieved,
            'teamScore' => $teamScore
        ],
        'details' => $details
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors du chargement des données : ' . $e->getMessage()
    ]);
}
?>