<?php
require_once 'includes/verify_subscriptions.php';

$customer_id = $_SESSION['customer_id'] ?? null;
$user_id     = isset($user) ? $user['id'] : ($_SESSION['user_id'] ?? null);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: opportunities.php');
    exit;
}

// Charger l'opportunité
try {
    $stmt = $pdo->prepare("
        SELECT o.*, c.name AS company_name
        FROM opportunities o
        LEFT JOIN companies c ON o.company_id = c.id
        WHERE o.id = ? AND (o.customer_id = ? OR o.assigned_to = ?)
        LIMIT 1
    ");
    $stmt->execute([$id, $customer_id, $user_id]);
    $opp = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $opp = null;
}

if (!$opp) {
    header('Location: opportunities.php');
    exit;
}

// Listes pour les selects
$companies = [];
try {
    $s = $pdo->prepare("SELECT id, name FROM companies WHERE customer_id = ? AND (interne_customer = 0 OR interne_customer IS NULL) ORDER BY name");
    $s->execute([$customer_id]);
    $companies = $s->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

$users = [];
try {
    $s = $pdo->prepare("SELECT id, CONCAT(first_name,' ',last_name) AS full_name FROM users WHERE customer_id = ? ORDER BY first_name");
    $s->execute([$customer_id]);
    $users = $s->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

$stages = [
    'prospecting'    => 'Prospection',
    'qualification'  => 'Qualification',
    'needs_analysis' => 'Analyse des besoins',
    'proposal'       => 'Proposition',
    'negotiation'    => 'Négociation',
    'closed_won'     => 'Fermé Gagné',
    'closed_lost'    => 'Fermé Perdu',
];

$success_message = '';
$error_message   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title               = trim($_POST['title'] ?? '');
    $description         = trim($_POST['description'] ?? '');
    $company_id          = !empty($_POST['company_id']) ? intval($_POST['company_id']) : null;
    $stage               = trim($_POST['stage'] ?? $opp['stage']);
    $probability         = isset($_POST['probability']) && $_POST['probability'] !== '' ? intval($_POST['probability']) : 0;
    $amount              = isset($_POST['amount']) && $_POST['amount'] !== '' ? (float)$_POST['amount'] : 0.00;
    $expected_close_date = !empty($_POST['expected_close_date']) ? $_POST['expected_close_date'] : null;
    $source              = trim($_POST['source'] ?? '');
    $competitor          = trim($_POST['competitor'] ?? '');
    $loss_reason         = trim($_POST['loss_reason'] ?? '');
    $next_action         = trim($_POST['next_action'] ?? '');
    $next_action_date    = !empty($_POST['next_action_date']) ? $_POST['next_action_date'] : null;
    $assigned_to         = !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : $user_id;

    if ($title === '') {
        $error_message = 'Le titre est requis.';
    } else {
        try {
            $pdo->prepare("
                UPDATE opportunities SET
                    title               = ?,
                    description         = ?,
                    company_id          = ?,
                    stage               = ?,
                    probability         = ?,
                    amount              = ?,
                    expected_close_date = ?,
                    source              = ?,
                    competitor          = ?,
                    loss_reason         = ?,
                    next_action         = ?,
                    next_action_date    = ?,
                    assigned_to         = ?,
                    updated_at          = NOW()
                WHERE id = ? AND (customer_id = ? OR assigned_to = ?)
            ")->execute([
                $title,
                $description !== '' ? $description : null,
                $company_id,
                $stage,
                $probability,
                $amount,
                $expected_close_date,
                $source !== '' ? $source : null,
                $competitor !== '' ? $competitor : null,
                $loss_reason !== '' ? $loss_reason : null,
                $next_action !== '' ? $next_action : null,
                $next_action_date,
                $assigned_to,
                $id,
                $customer_id,
                $user_id,
            ]);

            // Synchroniser le lead lié si l'opportunité en possède un
            if (!empty($opp['lead_id'])) {
                try {
                    $pdo->prepare("
                        UPDATE leads SET
                            company_id  = ?,
                            source      = ?,
                            assigned_to = ?,
                            updated_at  = NOW()
                        WHERE id = ? AND (customer_id = ? OR assigned_to = ?)
                    ")->execute([
                        $company_id,
                        $source !== '' ? $source : null,
                        $assigned_to,
                        intval($opp['lead_id']),
                        $customer_id,
                        $user_id,
                    ]);
                } catch (Throwable $ignore) {}
            }

            header('Location: opportunities.php?msg=' . rawurlencode('Opportunité mise à jour.'));
            exit;
        } catch (Throwable $e) {
            error_log('opportunities-edit update error: ' . $e->getMessage());
            $error_message = 'Erreur lors de la mise à jour.';
        }
    }

    // Après erreur, remettre les valeurs postées dans $opp pour ré-affichage
    $opp = array_merge($opp, [
        'title'               => $title,
        'description'         => $description,
        'company_id'          => $company_id,
        'stage'               => $stage,
        'probability'         => $probability,
        'amount'              => $amount,
        'expected_close_date' => $expected_close_date,
        'source'              => $source,
        'competitor'          => $competitor,
        'loss_reason'         => $loss_reason,
        'next_action'         => $next_action,
        'next_action_date'    => $next_action_date,
        'assigned_to'         => $assigned_to,
    ]);
}

$page_title = 'Éditer Opportunité - CRM Intelligent';
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
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        <?php include 'includes/topbar.php'; ?>

        <div class="main-content">
            <div class="container-fluid py-4">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h1 class="h4">
                        <i class="fas fa-handshake text-primary"></i> Éditer Opportunité
                    </h1>
                    <a href="opportunities.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle me-1"></i> <?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-1"></i> <?php echo htmlspecialchars($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <form method="POST" class="row g-3 needs-validation" novalidate>

                            <!-- Titre -->
                            <div class="col-md-8">
                                <label class="form-label">Titre <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" required
                                       value="<?php echo htmlspecialchars($opp['title'] ?? ''); ?>">
                                <div class="invalid-feedback">Le titre est requis.</div>
                            </div>

                            <!-- Montant -->
                            <div class="col-md-4">
                                <label class="form-label">Montant (€)</label>
                                <input type="number" name="amount" class="form-control" min="0" step="0.01"
                                       value="<?php echo htmlspecialchars($opp['amount'] ?? '0'); ?>">
                            </div>

                            <!-- Description -->
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($opp['description'] ?? ''); ?></textarea>
                            </div>

                            <!-- Société -->
                            <div class="col-md-6">
                                <label class="form-label">Société</label>
                                <select name="company_id" class="form-select">
                                    <option value="">-- Aucune --</option>
                                    <?php foreach ($companies as $c): ?>
                                        <option value="<?php echo intval($c['id']); ?>"
                                            <?php if ((int)($opp['company_id'] ?? 0) === intval($c['id'])) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($c['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Étape -->
                            <div class="col-md-6">
                                <label class="form-label">Étape <span class="text-danger">*</span></label>
                                <select name="stage" class="form-select" required>
                                    <?php foreach ($stages as $key => $label): ?>
                                        <option value="<?php echo $key; ?>"
                                            <?php if (($opp['stage'] ?? '') === $key) echo 'selected'; ?>>
                                            <?php echo $label; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Veuillez sélectionner une étape.</div>
                            </div>

                            <!-- Probabilité -->
                            <div class="col-md-4">
                                <label class="form-label">Probabilité (%)</label>
                                <input type="number" name="probability" class="form-control" min="0" max="100"
                                       value="<?php echo htmlspecialchars($opp['probability'] ?? '0'); ?>">
                            </div>

                            <!-- Date clôture -->
                            <div class="col-md-4">
                                <label class="form-label">Date de clôture prévue</label>
                                <input type="date" name="expected_close_date" class="form-control"
                                       value="<?php echo htmlspecialchars($opp['expected_close_date'] ?? ''); ?>">
                            </div>

                            <!-- Source -->
                            <div class="col-md-4">
                                <label class="form-label">Source</label>
                                <input type="text" name="source" class="form-control"
                                       value="<?php echo htmlspecialchars($opp['source'] ?? ''); ?>">
                            </div>

                            <!-- Concurrent -->
                            <div class="col-md-6">
                                <label class="form-label">Concurrent</label>
                                <input type="text" name="competitor" class="form-control"
                                       value="<?php echo htmlspecialchars($opp['competitor'] ?? ''); ?>">
                            </div>

                            <!-- Raison de perte -->
                            <div class="col-md-6">
                                <label class="form-label">Raison de perte</label>
                                <input type="text" name="loss_reason" class="form-control"
                                       value="<?php echo htmlspecialchars($opp['loss_reason'] ?? ''); ?>">
                            </div>

                            <!-- Prochaine action -->
                            <div class="col-md-6">
                                <label class="form-label">Prochaine action</label>
                                <input type="text" name="next_action" class="form-control"
                                       value="<?php echo htmlspecialchars($opp['next_action'] ?? ''); ?>">
                            </div>

                            <!-- Date prochaine action -->
                            <div class="col-md-6">
                                <label class="form-label">Date prochaine action</label>
                                <input type="datetime-local" name="next_action_date" class="form-control"
                                       value="<?php echo htmlspecialchars(isset($opp['next_action_date']) ? str_replace(' ', 'T', $opp['next_action_date']) : ''); ?>">
                            </div>

                            <!-- Assigné à -->
                            <?php if (!empty($users)): ?>
                            <div class="col-md-6">
                                <label class="form-label">Assigné à</label>
                                <select name="assigned_to" class="form-select">
                                    <?php foreach ($users as $u): ?>
                                        <option value="<?php echo intval($u['id']); ?>"
                                            <?php if ((int)($opp['assigned_to'] ?? 0) === intval($u['id'])) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($u['full_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($opp['lead_id'])): ?>
                            <div class="col-12">
                                <small class="text-muted">
                                    <i class="fas fa-link"></i>
                                    Lié au lead #<?php echo intval($opp['lead_id']); ?> —
                                    <a href="leads-edit.php?id=<?php echo intval($opp['lead_id']); ?>">Voir le lead</a>
                                </small>
                            </div>
                            <?php endif; ?>

                            <!-- Actions -->
                            <div class="col-12 d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Enregistrer
                                </button>
                                <a href="opportunities.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> Annuler
                                </a>
                            </div>

                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Validation Bootstrap
    (() => {
        const forms = document.querySelectorAll('.needs-validation');
        forms.forEach(form => {
            form.addEventListener('submit', e => {
                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });
    })();
    </script>
</body>
</html>
