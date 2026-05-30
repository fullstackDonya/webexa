<?php
// filepath: /Applications/MAMP/htdocs/PP/webitech/WEB/crm/contacts-view.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once 'includes/verify_subscriptions.php';

$customer_id = $_SESSION['customer_id'] ?? null;
if ($customer_id === null) {
    die("Erreur : client non identifié.");
}

$page_title = "Voir Contact - CRM Intelligent";

// --- Récupération de l'ID du contact ---
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: contacts.php');
    exit;
}

// --- Récupération des données du contact ---
try {
    $stmt = $pdo->prepare("SELECT * FROM leads WHERE id = ? AND (customer_id = ? OR assigned_to = ?) LIMIT 1");
    $stmt->execute([$id, $customer_id, $user_id]);
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$contact) {
        $_SESSION['error_message'] = "Contact introuvable.";
        header('Location: contacts.php');
        exit;
    }
} catch (Throwable $e) {
    error_log('contacts-view fetch error: ' . $e->getMessage());
    $_SESSION['error_message'] = "Erreur lors du chargement du contact.";
    header('Location: contacts.php');
    exit;
}

// --- Récupération du nom de la société (si besoin) ---
$company_name = null;
try {
    if (!empty($contact['company_id'])) {
        $stmt = $pdo->prepare("SELECT name FROM companies WHERE id = ? AND customer_id = ?");
        $stmt->execute([$contact['company_id'], $customer_id]);
        $company_name = $stmt->fetchColumn();
    }
} catch (Throwable $e) {
    error_log('contacts-view company fetch error: ' . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($page_title) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_SESSION['error_message']) ?>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        <?php include 'includes/topbar.php'; ?>
        <div class="main-content">
            <div class="container-fluid py-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h1 class="h4"><i class="fas fa-user text-primary"></i> Détails du Contact</h1>
                    <div>
                        <a href="contacts-edit.php?id=<?= $id ?>" class="btn btn-warning me-2"><i class="fas fa-edit"></i> Modifier</a>
                        <a href="contacts.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
                    </div>
                </div>

                <div class="card shadow">
                    <div class="card-body">
                        <h5 class="card-title mb-3 text-primary">
                            <?= htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name']) ?>
                        </h5>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p><strong>Email :</strong> <?= htmlspecialchars($contact['email'] ?? '-') ?></p>
                                <p><strong>Téléphone :</strong> <?= htmlspecialchars($contact['phone'] ?? '-') ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Poste :</strong> <?= htmlspecialchars($contact['poste'] ?? '-') ?></p>
                                <p><strong>Entreprise :</strong> <?= htmlspecialchars($company_name ?? '-') ?></p>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p><strong>Statut :</strong>
                                    <?php
                                    switch ($contact['status'] ?? '') {
                                        case 'active': echo '<span class="badge bg-success">Actif</span>'; break;
                                        case 'inactive': echo '<span class="badge bg-secondary">Inactif</span>'; break;
                                        case 'do_not_contact': echo '<span class="badge bg-danger">Ne pas contacter</span>'; break;
                                        default: echo '<span class="badge bg-light text-dark">—</span>';
                                    }
                                    ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Source :</strong>
                                    <?php
                                    $source_labels = [
                                        'website' => 'Site web',
                                        'referral' => 'Recommandation',
                                        'social_media' => 'Réseaux sociaux',
                                        'email_campaign' => 'Campagne email'
                                    ];
                                    echo htmlspecialchars($source_labels[$contact['source']] ?? '—');
                                    ?>
                                </p>
                            </div>
                        </div>

                        <div class="mb-3">
                            <p><strong>Notes :</strong></p>
                            <div class="border rounded p-3 bg-light" style="min-height:80px;">
                                <?= nl2br(htmlspecialchars($contact['notes'] ?? 'Aucune note.')) ?>
                            </div>
                        </div>

                        <hr>

                        <p class=" small mb-0">
                            Créé le : <?= htmlspecialchars($contact['created_at'] ?? '-') ?><br>
                            Mis à jour le : <?= htmlspecialchars($contact['updated_at'] ?? '-') ?>
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</body>
</html>
