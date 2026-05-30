<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/verify_subscriptions.php';

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$customer_id = $_SESSION['customer_id'] ?? null;

$page_title = "Ajouter une Campagne - CRM Intelligent";

$error = null;
// Récupère le nom de l'expéditeur depuis customer
$stmtCustomer = $pdo->prepare("SELECT `name` FROM customers WHERE id = ? LIMIT 1");
$stmtCustomer->execute([$customer_id]);
$rowCustomer = $stmtCustomer->fetch(PDO::FETCH_ASSOC);

$old = [
    'campaign_name' => $_POST['campaign_name'] ?? '',
    'campaign_type' => $_POST['campaign_type'] ?? 'newsletter',
    'campaign_subject' => $_POST['campaign_subject'] ?? '',
    'sender_name' => (isset($rowCustomer["Nom de l'expéditeur"]) && $rowCustomer["Nom de l'expéditeur"]) ? $rowCustomer["Nom de l'expéditeur"] : ($_POST['sender_name'] ?? 'Mon Entreprise'),
    'sender_email' => (isset($rowEmailCfg['email']) && $rowEmailCfg['email']) ? $rowEmailCfg['email'] : (isset($_POST['sender_email']) && $_POST['sender_email'] !== '' ? $_POST['sender_email'] : 'noreply@monentreprise.com'),
    'audience' => $_POST['audience'] ?? 'all',
    'company_ids' => $_POST['company_ids'] ?? [],
    'custom_emails' => $_POST['custom_emails'] ?? '',
    'status' => $_POST['status'] ?? 'draft',
    'scheduled_at' => $_POST['scheduled_at'] ?? ''
];

// helper: add column if missing (compatible older MySQL)
$ensureColumn = function(PDO $pdo, string $table, string $column, string $definition) {
    $sql = "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$table, $column]);
    $exists = (int)$stmt->fetchColumn() > 0;
    if (!$exists) {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN {$definition}");
    }
};

// Debug email_configurations en dehors du POST
$stmtEmailCfg = $pdo->prepare("SELECT id, email FROM email_configurations WHERE customer_id = ? AND is_active = 1 LIMIT 1");
$stmtEmailCfg->execute([$customer_id]);
$rowEmailCfg = $stmtEmailCfg->fetch(PDO::FETCH_ASSOC);
var_dump($rowEmailCfg['id']);

// Ajout de la campagne et redirection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['campaign_name'])) {
    try {
        $scheduled_at = isset($_POST['scheduled_at']) && $_POST['scheduled_at'] !== '' ? $_POST['scheduled_at'] : null;
        $email_config_id = isset($rowEmailCfg['id']) ? $rowEmailCfg['id'] : null;
        $stmt = $pdo->prepare("INSERT INTO campaigns (customer_id, email_config_id, name, type, subject, sender_name, sender_email, audience, status, scheduled_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $customer_id,
            $email_config_id,
            $_POST['campaign_name'],
            $_POST['campaign_type'],
            $_POST['campaign_subject'],
            $_POST['sender_name'],
            $_POST['sender_email'],
            $_POST['audience'],
            $_POST['status'],
            $scheduled_at
        ]);
        $newCampaignId = $pdo->lastInsertId();
        header("Location: campaigns-view.php?id=" . $newCampaignId);
        exit;
    } catch (Exception $e) {
        $error = "Erreur lors de l'ajout de la campagne : " . $e->getMessage();
    }
}

// Fetch companies for the form multi-select
$companies_for_select = [];
try {
    $cstmt = $pdo->prepare('SELECT id,name FROM companies WHERE customer_id = ? ORDER BY name ASC');
    $cstmt->execute([$customer_id]);
    $companies_for_select = $cstmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // ignore
}
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
        <div class="main-content">
            <div class="container-fluid">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-bullhorn text-primary"></i> Nouvelle Campagne
                    </h1>
                    <a href="campaigns.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Retour à la liste
                    </a>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card shadow">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">Informations de la Campagne</h6>
                            </div>
                            <div class="card-body">
                                <?php if(!empty($error)): ?>
                                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                                <?php endif; ?>
                                <form id="campaign-form" method="post" action="campaigns-add.php">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="campaign_name" class="form-label">Nom de la campagne *</label>
                                                <input type="text" name="campaign_name" class="form-control" id="campaign_name" required value="<?php echo htmlspecialchars($old['campaign_name']); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="campaign_type" class="form-label">Type</label>
                                                <select class="form-control" id="campaign_type" name="campaign_type">
                                                    <?php
                                                    $types = ['newsletter'=>'Newsletter','promotional'=>'Promotionnelle','transactional'=>'Transactionnelle','welcome'=>'Bienvenue','automation'=>'Automatisée'];
                                                    foreach($types as $k=>$v) {
                                                        echo '<option value="'.htmlspecialchars($k).'"'.($old['campaign_type']=== $k ? ' selected':'').'>'.htmlspecialchars($v).'</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="campaign_subject" class="form-label">Objet</label>
                                                <input type="text" name="campaign_subject" class="form-control" id="campaign_subject" value="<?php echo htmlspecialchars($old['campaign_subject']); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="sender_name" class="form-label">Nom de l'expéditeur</label>
                                                <input type="text" name="sender_name" class="form-control" id="sender_name" value="<?php echo htmlspecialchars(isset($rowCustomer['name']) ? $rowCustomer['name'] : ''); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="sender_email" class="form-label">Email expéditeur</label>
                                        <input type="email" name="sender_email" class="form-control" id="sender_email" value="<?php echo htmlspecialchars(isset($rowEmailCfg['email']) ? $rowEmailCfg['email'] : ''); ?>">
                                    </div>

                                    <!-- Sélecteur de templates -->
                                    <?php include 'includes/template_selector.php'; ?>

                                    <div class="mb-3">
                                        <label for="audience" class="form-label">
                                            <i class="fas fa-users"></i> Audience cible
                                        </label>
                                        <select class="form-control" id="audience" name="audience">
                                            <option value="all" <?php if($old['audience']==='all') echo 'selected'; ?>>📬 Tous les contacts</option>
                                            <option value="lead_stage" <?php if($old['audience']==='lead_stage') echo 'selected'; ?>>🎯 Par étape du pipeline (Leads)</option>
                                            <option value="subscribers" <?php if($old['audience']==='subscribers') echo 'selected'; ?>>📧 Abonnés newsletter</option>
                                            <option value="customers" <?php if($old['audience']==='customers') echo 'selected'; ?>>👥 Clients</option>
                                            <option value="prospects" <?php if($old['audience']==='prospects') echo 'selected'; ?>>🔍 Prospects</option>
                                            <option value="custom" <?php if($old['audience']==='custom') echo 'selected'; ?>>✏️ Liste personnalisée</option>
                                            <option value="companies" <?php if($old['audience']==='companies') echo 'selected'; ?>>🏢 Sélectionner les clients</option>
                                        </select>
                                    </div>

                                    <!-- Sélection par étape du pipeline -->
                                    <div id="lead-stage-wrapper" style="display:none; margin-bottom:20px;">
                                        <label class="form-label">
                                            <i class="fas fa-funnel"></i> Étapes du pipeline
                                        </label>
                                        <div id="stage-checkboxes" class="border rounded p-3 bg-light">
                                            <div class="text-center text-muted">
                                                <div class="spinner-border spinner-border-sm" role="status">
                                                    <span class="visually-hidden">Chargement...</span>
                                                </div>
                                                Chargement des étapes...
                                            </div>
                                        </div>
                                        <input type="hidden" name="lead_stages[]" id="lead_stages_input">
                                        <small class="text-muted d-block mt-2">
                                            <i class="fas fa-info-circle"></i> 
                                            <span id="leads-count-info">Sélectionnez une ou plusieurs étapes</span>
                                        </small>
                                    </div>

                                    <div id="company-select-wrapper" style="display:none; margin-bottom:10px;">
                                        <label for="company_ids">Choisir une ou plusieurs sociétés</label>
                                        <select id="company_ids" name="company_ids[]" multiple class="form-control" size="6">
                                            <?php
                                            if(!empty($companies_for_select)){
                                                foreach($companies_for_select as $cp){
                                                    $sel = in_array($cp['id'], array_map('intval', (array)$old['company_ids'])) ? ' selected' : '';
                                                    echo '<option value="'.intval($cp['id']).'"'.$sel.'>'.htmlspecialchars($cp['name']).'</option>';
                                                }
                                            } else {
                                                echo '<option disabled>Aucune société disponible</option>';
                                            }
                                            ?>
                                        </select>
                                        <small class="text-muted">Maintenez Ctrl/Cmd pour sélectionner plusieurs sociétés.</small>
                                    </div>

                                    <div id="custom-emails-wrapper" style="display:none; margin-bottom:10px;">
                                        <label for="custom_emails">Emails personnalisés (séparés par virgule/retour)</label>
                                        <textarea name="custom_emails" id="custom_emails" class="form-control" rows="3" placeholder="email1@exemple.com, email2@exemple.com"><?php echo htmlspecialchars($old['custom_emails']); ?></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="status" class="form-label">Statut</label>
                                        <select class="form-control" id="status" name="status">
                                            <option value="draft" <?php if($old['status']==='draft') echo 'selected'; ?>>Brouillon</option>
                                            <option value="scheduled" <?php if($old['status']==='scheduled') echo 'selected'; ?>>Programmée</option>
                                            <option value="sent" <?php if($old['status']==='sent') echo 'selected'; ?>>Envoyée</option>
                                            <option value="active" <?php if($old['status']==='active') echo 'selected'; ?>>Active</option>
                                            <option value="paused" <?php if($old['status']==='paused') echo 'selected'; ?>>Suspendue</option>
                                        </select>
                                    </div>

                                    <div class="mb-3" id="scheduleDateDiv" style="display:none;">
                                        <label for="scheduled_at" class="form-label">Date et heure</label>
                                        <input type="datetime-local" name="scheduled_at" class="form-control" id="scheduled_at" value="<?php echo htmlspecialchars($old['scheduled_at']); ?>">
                                    </div>

                                    <div class="text-end">
                                        <button type="reset" class="btn btn-secondary me-2">Annuler</button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Enregistrer la campagne
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card shadow">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">Aide</h6>
                            </div>
                            <div class="card-body">
                                <h6>Conseils pour ajouter une campagne :</h6>
                                <ul class="small">
                                    <li>Le nom de la campagne est obligatoire</li>
                                    <li>Sélectionnez une audience pour cibler vos destinataires</li>
                                    <li>Programmez l'envoi si nécessaire</li>
                                    <li>Renseignez un expéditeur professionnel pour améliorer la délivrabilité</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const schedulingType = document.getElementById('status');
    const scheduleDateDiv = document.getElementById('scheduleDateDiv');
    const audience = document.getElementById('audience');
    const compWrap = document.getElementById('company-select-wrapper');
    const customWrap = document.getElementById('custom-emails-wrapper');

    function updateScheduleUI() {
        scheduleDateDiv.style.display = (schedulingType.value === 'scheduled') ? 'block' : 'none';
    }
    function updateAudienceUI(){
        const v = audience.value;
        compWrap.style.display = (v === 'companies') ? 'block' : 'none';
        customWrap.style.display = (v === 'custom') ? 'block' : 'none';
    }

    schedulingType.addEventListener('change', updateScheduleUI);
    audience.addEventListener('change', updateAudienceUI);

    updateScheduleUI();
    updateAudienceUI();
});
</script>
</body>
</html>