<?php
include 'includes/verify_subscriptions.php';
$customer_id = $_SESSION['customer_id'];
$id = (int)$_GET['id'];

// On récupère la société externe (company) correspondant à l'id et au customer connecté
$stmt = $pdo->prepare("
    SELECT *
    FROM companies
    WHERE id = :id AND customer_id = :customer_id AND interne_customer = 0
    LIMIT 1
");
$stmt->execute([
    ':id' => $id,
    ':customer_id' => $customer_id
]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    // Redirection si non trouvé ou non autorisé
    header('Location: customers.php?notfound=1');
    exit;
}