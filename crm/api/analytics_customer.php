<?php
// filepath: /Applications/MAMP/htdocs/PP/webitech/WEB/crm/api/customer-analytics.php

header('Content-Type: application/json');
require_once '../includes/verify_subscriptions.php';

// Vérification de l'authentification
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}
$customer_id = $_SESSION['customer_id'];

try {
    // CLV moyen (exemple : montant total moyen par société externe)
    $stmt = $pdo->prepare("SELECT AVG(total_spent) FROM companies WHERE customer_id = :customer_id AND interne_customer = 0");
    $stmt->execute([':customer_id' => $customer_id]);
    $avg_clv = round((float)$stmt->fetchColumn(), 2);

    // Taux de rétention (sociétés actives / total sociétés externes)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM companies WHERE customer_id = :customer_id AND interne_customer = 0");
    $stmt->execute([':customer_id' => $customer_id]);
    $total_companies = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM companies WHERE customer_id = :customer_id AND interne_customer = 0 AND is_active = 1");
    $stmt->execute([':customer_id' => $customer_id]);
    $active_companies = (int)$stmt->fetchColumn();

    $retention_rate = $total_companies > 0 ? round(($active_companies / $total_companies) * 100, 1) : 0;

    // Fréquence d'achat moyenne (nombre de commandes moyen par société externe)
    $stmt = $pdo->prepare("SELECT AVG(orders) FROM companies WHERE customer_id = :customer_id AND interne_customer = 0");
    $stmt->execute([':customer_id' => $customer_id]);
    $purchase_frequency = round((float)$stmt->fetchColumn(), 2);

    // Score d'engagement moyen (exemple : champ engagement_score)
    $stmt = $pdo->prepare("SELECT AVG(engagement_score) FROM companies WHERE customer_id = :customer_id AND interne_customer = 0");
    $stmt->execute([':customer_id' => $customer_id]);
    $engagement_score = round((float)$stmt->fetchColumn(), 1);

    // Évolution du comportement (exemple : total commandes par mois)
    $stmt = $pdo->prepare("
        SELECT DATE_FORMAT(last_purchase, '%Y-%m') as period, SUM(orders) as total_orders
        FROM companies
        WHERE customer_id = :customer_id AND interne_customer = 0
        GROUP BY period
        ORDER BY period ASC
        LIMIT 12
    ");
    $stmt->execute([':customer_id' => $customer_id]);
    $behavior_trend = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Segments sociétés (exemple : segment, nombre)
    $stmt = $pdo->prepare("
        SELECT segment, COUNT(*) as count
        FROM companies
        WHERE customer_id = :customer_id AND interne_customer = 0
        GROUP BY segment
    ");
    $stmt->execute([':customer_id' => $customer_id]);
    $segments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Analyse RFM (exemple : score_rfm, nombre)
    $stmt = $pdo->prepare("
        SELECT score_rfm, COUNT(*) as count
        FROM companies
        WHERE customer_id = :customer_id AND interne_customer = 0
        GROUP BY score_rfm
    ");
    $stmt->execute([':customer_id' => $customer_id]);
    $rfm = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Risque de churn (exemple : churn_risk, nombre)
    $stmt = $pdo->prepare("
        SELECT churn_risk, COUNT(*) as count
        FROM companies
        WHERE customer_id = :customer_id AND interne_customer = 0
        GROUP BY churn_risk
    ");
    $stmt->execute([':customer_id' => $customer_id]);
    $churn = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Liste détaillée des sociétés externes (top 100)
    $stmt = $pdo->prepare("
        SELECT 
            id, name, segment, total_spent AS clv, score_rfm, churn_risk, last_purchase, engagement_score
        FROM companies
        WHERE customer_id = :customer_id AND interne_customer = 0
        ORDER BY total_spent DESC
        LIMIT 100
    ");
    $stmt->execute([':customer_id' => $customer_id]);
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'metrics' => [
            'avg_clv' => $avg_clv,
            'retention_rate' => $retention_rate,
            'purchase_frequency' => $purchase_frequency,
            'engagement_score' => $engagement_score
        ],
        'behavior_trend' => $behavior_trend,
        'segments' => $segments,
        'rfm' => $rfm,
        'churn' => $churn,
        'companies' => $companies
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors du chargement des données : ' . $e->getMessage()
    ]);
}
?>