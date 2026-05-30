<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/verify_subscriptions.php';
require_once 'includes/ai_scoring.php';

$customer_id = $_SESSION['customer_id'] ?? null;
if ($customer_id === null) {
    die("Erreur : client non identifié.");
}

// Déterminer user_id depuis $user global ou $_SESSION
$user_id = isset($user) ? $user['id'] : ($_SESSION['user_id'] ?? null);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) { 
    header('Location: leads.php'); 
    exit; 
}

// Récupérer le lead
try {
    $stmt = $pdo->prepare("
        SELECT l.*, c.name as company_name
        FROM leads l
        LEFT JOIN companies c ON l.company_id = c.id
        WHERE l.id = ? AND (l.customer_id = ? OR l.assigned_to = ?)
        LIMIT 1
    ");
    $stmt->execute([$id, $customer_id, $user_id]);
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$contact) { 
        header('Location: leads.php'); 
        exit; 
    }
} catch (Throwable $e) {
    error_log('leads-edit fetch error: '.$e->getMessage());
    header('Location: leads.php'); 
    exit;
}

// Récupérer les entreprises
$companies = [];
try {
    $stmt = $pdo->prepare("SELECT id, name FROM companies WHERE customer_id = ? OR customer_id IS NULL ORDER BY name");
    $stmt->execute([$customer_id]);
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('companies fetch error: '.$e->getMessage());
}

// Récupérer les utilisateurs pour assigned_to
$users = [];
try {
    $stmt = $pdo->prepare("
        SELECT id, name FROM users 
        WHERE (customer_id = ? OR customer_id IS NULL) AND status = 'active'
        ORDER BY name
    ");
    $stmt->execute([$customer_id]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('users fetch error: '.$e->getMessage());
}

// Traitement POST
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? $contact['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? $contact['last_name'] ?? '');
    $email      = strtolower(trim($_POST['email'] ?? $contact['email'] ?? ''));
    $phone      = trim($_POST['phone'] ?? $contact['phone'] ?? '');
    $position   = trim($_POST['position'] ?? $contact['position'] ?? '');
    $company_id = isset($_POST['company_id']) && $_POST['company_id'] !== '' ? intval($_POST['company_id']) : null;
    $stage      = trim($_POST['stage'] ?? $contact['stage'] ?? 'lead');
    $source     = trim($_POST['source'] ?? $contact['source'] ?? '');
    $assigned_to = isset($_POST['assigned_to']) && $_POST['assigned_to'] !== '' ? intval($_POST['assigned_to']) : null;

    if ($first_name === '' || $last_name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Prénom, nom et email valides sont requis.';
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE leads SET
                    first_name = ?, 
                    last_name = ?, 
                    email = ?, 
                    phone = ?, 
                    position = ?, 
                    company_id = ?, 
                    stage = ?, 
                    source = ?, 
                    assigned_to = ?,
                    updated_at = NOW()
                WHERE id = ? AND (customer_id = ? OR assigned_to = ?)
            ");
            $stmt->execute([
                $first_name,
                $last_name,
                $email,
                $phone !== '' ? $phone : null,
                $position !== '' ? $position : null,
                $company_id,
                $stage,
                $source,
                $assigned_to,
                $id,
                $customer_id,
                $user_id
            ]);
            
            // Calculer et mettre à jour le score IA
            $lead_data = [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'phone' => $phone,
                'position' => $position,
                'company_id' => $company_id,
                'stage' => $stage,
                'source' => $source,
                'assigned_to' => $assigned_to
            ];
            $ai_score = update_lead_ai_score($pdo, $id, $lead_data);
            
            $success_message = 'Lead mis à jour.';
            header('Location: leads.php?msg=' . rawurlencode($success_message));
            exit;
        } catch (Throwable $e) {
            error_log('leads-edit update error: ' . $e->getMessage());
            $error_message = 'Erreur lors de la mise à jour.';
        }
    }
}

$page_title = "Éditer Lead - CRM Intelligent";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        <?php include 'includes/topbar.php'; ?>
        <div class="main-content">
            <div class="container-fluid py-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h1 class="h4">
                        <i class="fas fa-user-edit text-primary"></i> Éditer Lead
                    </h1>
                    <a href="leads.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            Informations du Lead
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Prénom *</label>
                                    <input name="first_name" class="form-control" required value="<?php echo htmlspecialchars($contact['first_name'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nom *</label>
                                    <input name="last_name" class="form-control" required value="<?php echo htmlspecialchars($contact['last_name'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Email *</label>
                                    <input name="email" type="email" class="form-control" required value="<?php echo htmlspecialchars($contact['email'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Téléphone</label>
                                    <input name="phone" class="form-control" value="<?php echo htmlspecialchars($contact['phone'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Poste</label>
                                    <input name="position" class="form-control" value="<?php echo htmlspecialchars($contact['position'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Entreprise</label>
                                    <select name="company_id" class="form-control">
                                        <option value="">-- Sélectionner une entreprise --</option>
                                        <?php foreach ($companies as $company): ?>
                                            <option value="<?php echo intval($company['id']); ?>" 
                                                <?php echo ($contact['company_id'] ?? null) == $company['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($company['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Statut *</label>
                                    <select name="stage" class="form-control" required>
                                        <option value="lead" <?php echo ($contact['stage'] ?? 'lead') === 'lead' ? 'selected' : ''; ?>>Nouveau</option>
                                        <option value="contacted" <?php echo ($contact['stage'] ?? 'lead') === 'contacted' ? 'selected' : ''; ?>>Contacté</option>
                                        <option value="qualified" <?php echo ($contact['stage'] ?? 'lead') === 'qualified' ? 'selected' : ''; ?>>Qualifié</option>
                                        <option value="proposal" <?php echo ($contact['stage'] ?? 'lead') === 'proposal' ? 'selected' : ''; ?>>Proposition</option>
                                        <option value="converted" <?php echo ($contact['stage'] ?? 'lead') === 'converted' ? 'selected' : ''; ?>>Converti</option>
                                        <option value="unqualified" <?php echo ($contact['stage'] ?? 'lead') === 'unqualified' ? 'selected' : ''; ?>>Perdu</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Source</label>
                                    <select name="source" class="form-control">
                                        <option value="">-- Sélectionner une source --</option>
                                        <option value="website" <?php echo ($contact['source'] ?? '') === 'website' ? 'selected' : ''; ?>>Site web</option>
                                        <option value="social_media" <?php echo ($contact['source'] ?? '') === 'social_media' ? 'selected' : ''; ?>>Réseaux sociaux</option>
                                        <option value="referral" <?php echo ($contact['source'] ?? '') === 'referral' ? 'selected' : ''; ?>>Recommandation</option>
                                        <option value="direct" <?php echo ($contact['source'] ?? '') === 'direct' ? 'selected' : ''; ?>>Contact direct</option>
                                        <option value="event" <?php echo ($contact['source'] ?? '') === 'event' ? 'selected' : ''; ?>>Événement</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Assigné à</label>
                                    <select name="assigned_to" class="form-control">
                                        <option value="">-- Non assigné --</option>
                                        <?php foreach ($users as $user_option): ?>
                                            <option value="<?php echo intval($user_option['id']); ?>" 
                                                <?php echo ($contact['assigned_to'] ?? null) == $user_option['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($user_option['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Score IA</label>
                                    <input type="number" class="form-control" value="<?php echo htmlspecialchars($contact['ai_score'] ?? ''); ?>" disabled>
                                    <small class="text-muted">Calculé automatiquement</small>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Créé le</label>
                                    <input type="text" class="form-control" 
                                        value="<?php echo htmlspecialchars(isset($contact['created_at']) ? date('d/m/Y H:i', strtotime($contact['created_at'])) : ''); ?>" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Mis à jour le</label>
                                    <input type="text" class="form-control" 
                                        value="<?php echo htmlspecialchars(isset($contact['updated_at']) ? date('d/m/Y H:i', strtotime($contact['updated_at'])) : ''); ?>" disabled>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Enregistrer les modifications
                                </button>
                                <a href="leads.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> Annuler
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Info supplémentaires -->
                <div class="card shadow mt-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-info-circle"></i> Informations supplémentaires
                        </h6>
                    </div>
                    <div class="card-body">
                        <p><strong>ID Lead:</strong> #<?php echo intval($contact['id']); ?></p>
                        <p><strong>Entreprise:</strong> <?php echo htmlspecialchars($contact['company_name'] ?? 'Non assignée'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>