<?php

require_once 'includes/verify_subscriptions.php';
require_once __DIR__ . '/config/database.php'; // doit définir $pdo

session_start();
$customer_id = $_SESSION['customer_id'] ?? null;
if ($customer_id === null) {
    die("Erreur : client non identifié.");
}

$page_title = "Ajouter un Contact - CRM Intelligent";
$success_message = '';
$error_message = '';

// récupérer entreprises pour select
$companies = [];
try {
    $stmt = $pdo->prepare("SELECT id, name FROM companies WHERE customer_id = ? ORDER BY name");
    $stmt->execute([$customer_id]);
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('companies fetch error: '.$e->getMessage());
}

// traitement POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname  = trim($_POST['lastname'] ?? '');
    $email     = strtolower(trim($_POST['email'] ?? ''));
    $phone     = trim($_POST['phone'] ?? '');
    $company_id= $_POST['company_id'] !== '' ? intval($_POST['company_id']) : null;
    $position  = trim($_POST['position'] ?? '');
    $status    = trim($_POST['status'] ?? '');
    $source    = trim($_POST['source'] ?? '');
    $notes     = trim($_POST['notes'] ?? '');

    if ($firstname === '' || $lastname === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Prénom, nom et email valides sont requis.';
    } else {
        // vérifier doublon pour le même customer
        $stmt = $pdo->prepare("SELECT id FROM leads WHERE email = ? AND customer_id = ? LIMIT 1");
        $stmt->execute([$email, $customer_id]);
        if ($stmt->fetchColumn()) {
            $error_message = 'Un contact avec cet email existe déjà pour ce client.';
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO leads
                    (firstname, lastname, email, phone, company_id, position, status, source, notes, customer_id, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([
                    $firstname,
                    $lastname,
                    $email,
                    $phone !== '' ? $phone : null,
                    $company_id,
                    $position !== '' ? $position : null,
                    $status !== '' ? $status : null,
                    $source !== '' ? $source : 'import',
                    $notes !== '' ? $notes : null,
                    $customer_id
                ]);
                $success_message = 'Contact ajouté avec succès.';
                header('Location: contacts.php?msg=' . rawurlencode($success_message));
                exit;
            } catch (Throwable $e) {
                error_log('contacts-add insert error: '.$e->getMessage());
                $error_message = 'Erreur lors de l\'ajout en base.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo htmlspecialchars($page_title); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/topbar.php'; ?>
    <div class="main-content">
        <div class="container-fluid py-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="h4"><i class="fas fa-user-plus text-primary"></i> Nouveau Contact</h1>
                <a href="contacts.php" class="btn btn-secondary">Retour</a>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <div class="card shadow">
                <div class="card-body">
                    <form method="post" id="contact-form">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Prénom *</label>
                                <input name="firstname" class="form-control" required value="<?php echo isset($firstname) ? htmlspecialchars($firstname) : ''; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nom *</label>
                                <input name="lastname" class="form-control" required value="<?php echo isset($lastname) ? htmlspecialchars($lastname) : ''; ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email *</label>
                                <input name="email" type="email" class="form-control" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Téléphone</label>
                                <input name="phone" class="form-control" value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Entreprise</label>
                                <select name="company_id" class="form-control">
                                    <option value="">— Aucune —</option>
                                    <?php foreach ($companies as $co): ?>
                                        <option value="<?php echo (int)$co['id']; ?>" <?php echo (isset($company_id) && $company_id == $co['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($co['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Poste</label>
                                <input name="position" class="form-control" value="<?php echo isset($position) ? htmlspecialchars($position) : ''; ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Statut</label>
                                <select name="status" class="form-control">
                                    <option value="">—</option>
                                    <option value="lead">Lead</option>
                                    <option value="prospect">Prospect</option>
                                    <option value="client">Client</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Source</label>
                                <select name="source" class="form-control">
                                    <option value="website">Site web</option>
                                    <option value="referral">Recommandation</option>
                                    <option value="social_media">Réseaux sociaux</option>
                                    <option value="email_campaign">Campagne email</option>
                                    <option value="trade_show">Salon</option>
                                    <option value="cold_call">Appel</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="4"><?php echo isset($notes) ? htmlspecialchars($notes) : ''; ?></textarea>
                        </div>

                        <div class="text-end">
                            <button type="reset" class="btn btn-secondary me-2">Annuler</button>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>