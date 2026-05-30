<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/verify_subscriptions.php';

$customer_id = $_SESSION['customer_id'] ?? null;
$campaign_id = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$campaign_id) {
    echo '<div class="alert alert-danger">ID de campagne manquant.</div>';
    exit;
}

$stmt = $pdo->prepare("SELECT c.*, ec.email AS sender_email_config FROM campaigns c LEFT JOIN email_configurations ec ON c.email_config_id = ec.id WHERE c.id = ? AND c.customer_id = ? LIMIT 1");
$stmt->execute([$campaign_id, $customer_id]);
$campaign = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$campaign) {
    echo '<div class="alert alert-danger">Campagne introuvable.</div>';
    exit;
}

$page_title = 'Visualisation Campagne';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .color-light {
            color: #efe8e8 !important;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/topbar.php'; ?>
    <div class="main-content">
        <div class="container-fluid mt-4 color-light">
            <div class="card shadow">
                <div class="card-header">
                    <h4 class="m-0 font-weight-bold color-light">
                        <i class="fas fa-bullhorn"></i> Détails de la campagne
                    </h4>
                </div>
                <div class="card-body color-light">
                    <table class="table table-bordered color-light">
                        <tr><th>Nom</th><td><?php echo htmlspecialchars($campaign['name']); ?></td></tr>
                        <tr><th>Type</th><td><?php echo htmlspecialchars($campaign['type']); ?></td></tr>
                        <tr><th>Objet</th><td><?php echo htmlspecialchars($campaign['subject']); ?></td></tr>
                        <tr><th>Nom expéditeur</th><td><?php echo htmlspecialchars($campaign['sender_name']); ?></td></tr>
                        <tr><th>Email expéditeur</th><td><?php echo htmlspecialchars($campaign['sender_email']); ?></td></tr>
                        <tr><th>Email config (technique)</th><td><?php echo htmlspecialchars($campaign['sender_email_config']); ?></td></tr>
                        <tr><th>Audience</th><td><?php echo htmlspecialchars($campaign['audience']); ?></td></tr>
                        <tr><th>Statut</th><td><?php echo htmlspecialchars($campaign['status']); ?></td></tr>
                        <tr><th>Date programmée</th><td><?php echo $campaign['scheduled_at'] !== null ? htmlspecialchars($campaign['scheduled_at']) : ''; ?></td></tr>
                        <tr><th>Créée le</th><td><?php echo htmlspecialchars($campaign['created_at'] ?? ''); ?></td></tr>
                    </table>
                    <a href="campaigns.php" class="btn btn-secondary mt-3"><i class="fas fa-arrow-left"></i> Retour à la liste</a>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
