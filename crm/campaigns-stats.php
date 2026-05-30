<?php
require_once __DIR__ . '/config/database.php';
session_start();
$customer_id = $_SESSION['customer_id'] ?? null;

if(!isset($_GET['id'])){ header('Location: campaigns.php?error=missing_id'); exit; }
$id = intval($_GET['id']);

$sql = 'SELECT * FROM campaigns WHERE id = ?'; $params = [$id];
if($customer_id){ $sql .= ' AND customer_id = ?'; $params[] = $customer_id; }
$stmt = $pdo->prepare($sql); $stmt->execute($params); $campaign = $stmt->fetch();
if(!$campaign){ header('Location: campaigns.php?error=not_found'); exit; }

// Gather metrics (use columns if present else placeholders)
$recipients = intval($campaign['recipients'] ?? 0);
$open = is_numeric($campaign['open_rate']) ? floatval($campaign['open_rate']) : null;
$click = is_numeric($campaign['click_rate']) ? floatval($campaign['click_rate']) : null;

?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Statistiques - <?php echo htmlspecialchars($campaign['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
    <a href="campaigns-email.php?id=<?php echo intval($id); ?>" class="btn btn-sm btn-link">← Retour</a>
    <h3>Statistiques : <?php echo htmlspecialchars($campaign['name']); ?></h3>

    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-body">
                    <h5>Destinataires</h5>
                    <div class="fs-3 fw-bold"><?php echo number_format($recipients); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-body">
                    <h5>Taux d'ouverture</h5>
                    <div class="fs-3 fw-bold"><?php echo ($open !== null) ? htmlspecialchars($open) . '%' : '-'; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-body">
                    <h5>Taux de clic</h5>
                    <div class="fs-3 fw-bold"><?php echo ($click !== null) ? htmlspecialchars($click) . '%' : '-'; ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header">Détails</div>
        <div class="card-body">
            <p><strong>Objet :</strong> <?php echo htmlspecialchars($campaign['subject'] ?? ''); ?></p>
            <p><strong>Expéditeur :</strong> <?php echo htmlspecialchars($campaign['sender_name'] ?? '') . ' &lt;' . htmlspecialchars($campaign['sender_email'] ?? '') . '&gt;'; ?></p>
            <p><strong>Statut :</strong> <?php echo htmlspecialchars($campaign['status'] ?? ''); ?></p>
            <p><strong>Date programmée :</strong> <?php echo (!empty($campaign['scheduled_at'])? date('d/m/Y H:i', strtotime($campaign['scheduled_at'])) : '-'); ?></p>
        </div>
    </div>
</div>
</body>
</html>
