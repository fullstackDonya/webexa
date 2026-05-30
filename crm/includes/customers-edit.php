<?php

require_once  __DIR__ . '/verify_subscriptions.php';



$customer_id = $_SESSION['customer_id'];
$id = (int)$_GET['id'];

// Récupération de la société externe à éditer
$stmt = $pdo->prepare("
    SELECT * FROM companies
    WHERE id = :id AND customer_id = :customer_id AND interne_customer = 0
    LIMIT 1
");
$stmt->execute([
    ':id' => $id,
    ':customer_id' => $customer_id
]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    header('Location: customers.php?notfound=1');
    exit;
}


// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $industry = trim($_POST['industry']);
    $segment = trim($_POST['segment']);
    $annual_revenue = floatval(str_replace(',', '.', $_POST['annual_revenue']));
    $status = trim($_POST['status']);
    $satisfaction = floatval(str_replace(',', '.', $_POST['satisfaction']));
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Définit la longueur max cohérente avec la colonne (ici 255)
    $maxSegmentLen = 255;
    if (mb_strlen($segment) > $maxSegmentLen) {
        // tronquer proprement pour éviter erreur SQL
        $segment = mb_substr($segment, 0, $maxSegmentLen);
    }




    $stmt = $pdo->prepare("
        UPDATE companies SET
            name = :name,
            email = :email,
            phone = :phone,
            industry = :industry,
            segment = :segment,
            annual_revenue = :annual_revenue,
            status = :status,
            satisfaction = :satisfaction,
            is_active = :is_active,
            updated_at = NOW()
        WHERE id = :id AND customer_id = :customer_id AND interne_customer = 0
        LIMIT 1
    ");
    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':phone' => $phone,
        ':industry' => $industry,
        ':segment' => $segment,
        ':annual_revenue' => $annual_revenue,
        ':status' => $status,
        ':satisfaction' => $satisfaction,
        ':is_active' => $is_active,
        ':id' => $id,
        ':customer_id' => $customer_id
    ]);
    header('Location: customers-view.php?id=' . $id . '&updated=1');
    exit;
}


