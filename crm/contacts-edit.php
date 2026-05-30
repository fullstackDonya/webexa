<?php
// filepath: /Applications/MAMP/htdocs/PP/webitech/WEB/crm/contacts-edit.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/verify_subscriptions.php';


$customer_id = $_SESSION['customer_id'] ?? null;
if ($customer_id === null) {
    die("Erreur : client non identifié.");
}
$page_title = "Éditer Contact - CRM Intelligent";
$success_message = '';
$error_message = '';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) { header('Location: contacts.php'); exit; }

// récupérer contact
try {
    $stmt = $pdo->prepare("SELECT * FROM leads WHERE id = ? AND (customer_id = ? OR assigned_to = ?) LIMIT 1");
    $stmt->execute([$id, $customer_id, $user_id]);
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$contact) { header('Location: contacts.php'); exit; }
} catch (Throwable $e) {
    error_log('contacts-edit fetch error: '.$e->getMessage());
    header('Location: contacts.php'); exit;
}

// récupérer companies pour select
$companies = [];
try {
    $stmt = $pdo->prepare("SELECT id, name FROM companies WHERE customer_id = ? ORDER BY name");
    $stmt->execute([$customer_id]);
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('companies fetch error: '.$e->getMessage());
}

// traitement POST (mise à jour)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? $contact['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? $contact['last_name'] ?? '');
    $email     = strtolower(trim($_POST['email'] ?? $contact['email'] ?? ''));
    $phone     = trim($_POST['phone'] ?? $contact['phone'] ?? '');
    /*$company_id= $_POST['company_id'] !== '' ? intval($_POST['company_id']) : null;*/
    $poste  = trim($_POST['poste'] ?? $contact['poste'] ?? '');
    $status    = trim($_POST['status'] ?? $contact['status'] ?? '');
    $source    = trim($_POST['source'] ?? $contact['source'] ?? '');
    $notes     = trim($_POST['notes'] ?? $contact['notes'] ?? '');

    if ($first_name === '' || $last_name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Prénom, nom et email valides sont requis.';
    } else {
        // vérifier doublon email autre id pour même customer
        $stmt = $pdo->prepare("SELECT id FROM leads WHERE email = ? AND customer_id = ? AND id <> ? LIMIT 1");
        $stmt->execute([$email, $customer_id, $id]);
        if ($stmt->fetchColumn()) {
            $error_message = 'Un autre contact avec cet email existe déjà pour ce client.';
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE leads SET
                        first_name = ?, last_name = ?, email = ?, phone = ?, poste = ?, status = ?, source = ?, notes = ?, updated_at = NOW()
                    WHERE id = ? AND customer_id = ?
                ");
                $stmt->execute([
                    $first_name,
                    $last_name,
                    $email,
                    $phone !== '' ? $phone : null,
                    $poste !== '' ? $poste : null,
                    $status !== '' ? $status : null,
                    $source !== '' ? $source : null,
                    $notes !== '' ? $notes : null,
                    $id,
                    $customer_id
                ]);
                $success_message = 'Contact mis à jour.';
                header('Location: contacts.php?msg=' . rawurlencode($success_message));
                exit;
            } catch (Throwable $e) {
                 error_log('contacts-edit update error: ' . $e->getMessage());
                $_SESSION['error_message'] = 'Erreur lors de la mise à jour.';
                header('Location: contacts-edit.php');
                exit;
            }
        }
    }
}
$success_message = '';
$error_message = '';

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
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_SESSION['error_message']) ?>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['success_message']) ?>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        <?php include 'includes/topbar.php'; ?>
        <div class="main-content">
            <div class="container-fluid py-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h1 class="h4"><i class="fas fa-pen text-primary"></i> Éditer Contact</h1>
                    <a href="contacts.php" class="btn btn-secondary">Retour</a>
                </div>
    
                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
    
                <div class="card shadow">
                    <div class="card-body">
                        <form method="post" id="contact-edit-form">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Prénom *</label>
                                    <input name="first_name" class="form-control" required value="<?php echo htmlspecialchars($contact['first_name'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nom *</label>
                                    <input name="last_name" class="form-control" required value="<?php echo htmlspecialchars($contact['last_name'] ?? ''); ?>">
                                </div>
                            </div>
    
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email *</label>
                                    <input name="email" type="email" class="form-control" required value="<?php echo htmlspecialchars($contact['email'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Téléphone</label>
                                    <input name="phone" class="form-control" value="<?php echo htmlspecialchars($contact['phone'] ?? $contact['telephone'] ?? ''); ?>">
                                </div>
                            </div>
    
                            <div class="row">
                         
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Poste</label>
                                    <input name="poste" class="form-control" value="<?php echo htmlspecialchars($contact['poste'] ?? ''); ?>">
                                </div>
                            </div>
    
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Statut</label>
                                    <select name="status" class="form-control">
                                        <option value="">—</option>
                                        <option value="active" <?php echo ($contact['status'] ?? '') === 'active' ? 'selected' : ''; ?>>active</option>
                                        <option value="inactive" <?php echo ($contact['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>inactive</option>
                                        <option value="do_not_contact" <?php echo ($contact['status'] ?? '') === 'do_not_contact' ? 'selected' : ''; ?>>do_not_contact</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Source</label>
                                    <select name="source" class="form-control">
                                        <option value="">—</option>
                                        <option value="website" <?php echo ($contact['source'] ?? '') === 'website' ? 'selected' : ''; ?>>Site web</option>
                                        <option value="referral" <?php echo ($contact['source'] ?? '') === 'referral' ? 'selected' : ''; ?>>Recommandation</option>
                                        <option value="social_media" <?php echo ($contact['source'] ?? '') === 'social_media' ? 'selected' : ''; ?>>Réseaux sociaux</option>
                                        <option value="email_campaign" <?php echo ($contact['source'] ?? '') === 'email_campaign' ? 'selected' : ''; ?>>Campagne email</option>
                                    </select>
                                </div>
                            </div>
    
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="4"><?php echo htmlspecialchars($contact['notes'] ?? ''); ?></textarea>
                            </div>
    
                            <div class="text-end">
                                <a href="contacts.php" class="btn btn-secondary me-2">Annuler</a>
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