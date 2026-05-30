<?php

header('Content-Type: application/json');
require_once '../config/database.php';

// Vérification de l'authentification
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}
$customer_id = $_SESSION['customer_id'];

try {
    // Récupération des métriques globales
    $metrics = [
        'total' => 0,
        'new' => 0,
        'churn' => 0,
        'lifetime' => 0
    ];

    // Total sociétés externes
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM companies WHERE customer_id = :customer_id AND interne_customer = 0");
    $stmt->execute([':customer_id' => $customer_id]);
    $metrics['total'] = (int)$stmt->fetchColumn();

    // Nouvelles sociétés externes ce mois-ci
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM companies WHERE customer_id = :customer_id AND interne_customer = 0 AND created_at >= DATE_FORMAT(NOW(),'%Y-%m-01')");
    $stmt->execute([':customer_id' => $customer_id]);
    $metrics['new'] = (int)$stmt->fetchColumn();

    // Taux de churn (sociétés externes inactives ce mois-ci / total)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM companies WHERE customer_id = :customer_id AND interne_customer = 0 AND is_active = 0 AND updated_at >= DATE_FORMAT(NOW(),'%Y-%m-01')");
    $stmt->execute([':customer_id' => $customer_id]);
    $inactives = (int)$stmt->fetchColumn();
    $metrics['churn'] = $metrics['total'] > 0 ? round(($inactives / $metrics['total']) * 100, 1) : 0;

    // Lifetime value moyenne (exemple: totalSpent moyen)
    $stmt = $pdo->prepare("SELECT AVG(total_spent) FROM companies WHERE customer_id = :customer_id AND interne_customer = 0");
    $stmt->execute([':customer_id' => $customer_id]);
    $metrics['lifetime'] = round((float)$stmt->fetchColumn(), 2);

    // Liste des sociétés externes (exemple simplifié)
    $stmt = $pdo->prepare("
        SELECT 
            id,
            name,
            email,
            avatar,
            segment,
            total_spent AS totalSpent,
            orders,
            last_purchase AS lastPurchase,
            satisfaction
        FROM companies
        WHERE customer_id = :customer_id AND interne_customer = 0
        ORDER BY last_purchase DESC
        LIMIT 100
    ");
    $stmt->execute([':customer_id' => $customer_id]);
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'metrics' => $metrics,
        'companies' => $companies
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors du chargement des données : ' . $e->getMessage()
    ]);
}
?>