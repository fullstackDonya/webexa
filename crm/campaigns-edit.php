<?php
require_once __DIR__ . '/includes/verify_subscriptions.php';
 

$customer_id = $_SESSION['customer_id'] ?? null;

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $type = $_POST['type'];
    $subject = trim($_POST['subject']);
    $sender_name = trim($_POST['sender_name']);
    $sender_email = trim($_POST['sender_email']);
    $audience = $_POST['audience'];
    $status = $_POST['status'];
    $scheduled_at = !empty($_POST['scheduled_at']) ? $_POST['scheduled_at'] : null;

    // Ensure campaign belongs to customer
    $sql = 'SELECT id FROM campaigns WHERE id = ?'; $params = [$id];
    if($customer_id){ $sql .= ' AND customer_id = ?'; $params[] = $customer_id; }
    $stmt = $pdo->prepare($sql); $stmt->execute($params); $camp = $stmt->fetch();
    if(!$camp){ header('Location: campaigns.php?error=not_found'); exit; }

    $upd = $pdo->prepare('UPDATE campaigns SET name=?,type=?,subject=?,sender_name=?,sender_email=?,audience=?,status=?,scheduled_at=? WHERE id=?');
    $upd->execute([$name,$type,$subject,$sender_name,$sender_email,$audience,$status,$scheduled_at,$id]);

    // optional: send immediately if scheduled_at is in the past or status requires immediate send
    require_once __DIR__ . '/includes/send_campaign.php';
    $s = $pdo->prepare('SELECT * FROM campaigns WHERE id = ?');
    $s->execute([$id]);
    $camp = $s->fetch(PDO::FETCH_ASSOC);
    if ($camp) {
        if ($scheduled_at !== null) {
            $t = strtotime($scheduled_at);
            if ($t !== false && $t <= time()) {
                send_campaign($pdo, $camp);
            }
        } elseif ($status === 'active' || $status === 'sent') {
            send_campaign($pdo, $camp);
        }
    }

    header('Location: campaigns.php?success=updated'); exit;

    header('Location: campaigns.php?success=updated'); exit;
}

// GET: show edit form
if(!isset($_GET['id'])){ header('Location: campaigns.php?error=missing_id'); exit; }
$id = intval($_GET['id']);
$sql = 'SELECT * FROM campaigns WHERE id = ?';
$params = [$id];
if($customer_id){ $sql .= ' AND customer_id = ?';
     $params[] = $customer_id; }
$stmt = $pdo->prepare($sql);
$stmt->execute($params);


  $campaign = $stmt->fetch();
if(!$campaign){ header('Location: campaigns.php?error=not_found'); exit; }

// Récupérer companies pour le select audience
$companies = [];
if($customer_id){
    try{
        $cstmt = $pdo->prepare('SELECT id, name FROM companies WHERE customer_id = ? ORDER BY name ASC');
        $cstmt->execute([$customer_id]);
        $companies = $cstmt->fetchAll();
    }catch(Exception $e){ $companies = []; }
}

// Format scheduled_at pour datetime-local
$scheduled_val = '';
if(!empty($campaign['scheduled_at'])){
    $t = strtotime($campaign['scheduled_at']);
    if($t !== false) $scheduled_val = date('Y-m-d\TH:i', $t);
}

// Normaliser le statut pour correspondre au pipeline
$status_for_select = strtolower((string)($campaign['status'] ?? 'draft'));
if (in_array($status_for_select, ['scheduled', 'paused'], true)) {
    $status_for_select = 'planning';
}
if (in_array($status_for_select, ['completed', 'closed', 'sent'], true)) {
    $status_for_select = 'archived';
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
        <?php include 'includes/topbar.php'; ?>
        <div class="main-content">
            <div class="container-fluid">
                <h3>Éditer : <?php echo htmlspecialchars($campaign['name']); ?></h3>
                <form method="post">
                    <input type="hidden" name="id" value="<?php echo intval($campaign['id']); ?>">
                    <div class="mb-3">
                        <label>Nom</label>
                        <input class="form-control" name="name" value="<?php echo htmlspecialchars($campaign['name']); ?>">
                    </div>
                    <div class="mb-3">
                        <label>Type</label>
                        <select name="type" class="form-control">
                            <option value="newsletter" <?php echo ($campaign['type']==='newsletter')? 'selected':''; ?>>Newsletter</option>
                            <option value="promotional" <?php echo ($campaign['type']==='promotional')? 'selected':''; ?>>Promotionnelle</option>
                            <option value="transactional" <?php echo ($campaign['type']==='transactional')? 'selected':''; ?>>Transactionnelle</option>
                            <option value="welcome" <?php echo ($campaign['type']==='welcome')? 'selected':''; ?>>Bienvenue</option>
                            <option value="automation" <?php echo ($campaign['type']==='automation')? 'selected':''; ?>>Automatisée</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Objet</label>
                        <input class="form-control" name="subject" value="<?php echo htmlspecialchars($campaign['subject']); ?>">
                    </div>
                    <div class="mb-3">
                        <label>Expéditeur</label>
                        <input class="form-control" name="sender_name" value="<?php echo htmlspecialchars($campaign['sender_name']); ?>">
                    </div>
                    <div class="mb-3">
                        <label>Email expéditeur</label>
                        <input class="form-control" name="sender_email" value="<?php echo htmlspecialchars($campaign['sender_email']); ?>">
                    </div>
                    
                    <!-- Sélecteur de templates -->
                    <?php include 'includes/template_selector.php'; ?>
                    
                    <div class="mb-3">
                        <label for="audience" class="form-label">
                            <i class="fas fa-users"></i> Audience cible
                        </label>
                        <select name="audience" id="audience" class="form-control">
                            <option value="all" <?php echo ($campaign['audience']==='all')? 'selected':''; ?>>📬 Tous les contacts</option>
                            <option value="lead_stage" <?php echo ($campaign['audience']==='lead_stage')? 'selected':''; ?>>🎯 Par étape du pipeline (Leads)</option>
                            <option value="subscribers" <?php echo ($campaign['audience']==='subscribers')? 'selected':''; ?>>📧 Abonnés newsletter</option>
                            <option value="customers" <?php echo ($campaign['audience']==='customers')? 'selected':''; ?>>👥 Clients</option>
                            <option value="prospects" <?php echo ($campaign['audience']==='prospects')? 'selected':''; ?>>🔍 Prospects</option>
                            <option value="custom" <?php echo ($campaign['audience']==='custom')? 'selected':''; ?>>✏️ Liste personnalisée</option>
                            <?php if(!empty($companies)): ?>
                                <optgroup label="🏢 Sociétés">
                                    <?php foreach($companies as $comp): $val = 'company_' . intval($comp['id']); ?>
                                        <option value="<?php echo $val; ?>" <?php echo ($campaign['audience']===$val)? 'selected':''; ?>><?php echo htmlspecialchars($comp['name']); ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
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
                    <div class="mb-3">
                        <label for="status" class="form-label">
                            <i class="fas fa-flag"></i> Statut (synchronisé avec le pipeline)
                        </label>
                        <select name="status" id="status" class="form-control">
                            <option value="draft" <?php echo ($status_for_select==='draft')? 'selected':''; ?>>📝 Brouillon</option>
                            <option value="planning" <?php echo ($status_for_select==='planning')? 'selected':''; ?>>📋 Planification</option>
                            <option value="active" <?php echo ($status_for_select==='active')? 'selected':''; ?>>🚀 Diffusion (Active)</option>
                            <option value="analysis" <?php echo ($status_for_select==='analysis')? 'selected':''; ?>>📊 Analyse</option>
                            <option value="archived" <?php echo ($status_for_select==='archived')? 'selected':''; ?>>🗄️ Clôturée (Archivée)</option>
                        </select>
                        <small class="text-muted">
                            <i class="fas fa-sync"></i> Le statut sera synchronisé avec le tableau pipeline
                        </small>
                    </div>
                    <div class="mb-3">
                        <label>Date de programmation</label>
                        <input type="datetime-local" name="scheduled_at" class="form-control" value="<?php echo htmlspecialchars($scheduled_val); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Enregistrer les modifications
                    </button>
                    <a href="campaigns.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                </form>
            </div> 
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
let leadStages = [];
let selectedStageIds = [];

document.addEventListener('DOMContentLoaded', function() {
    const audience = document.getElementById('audience');
    const leadStageWrap = document.getElementById('lead-stage-wrapper');

    // Charger les stages au démarrage
    loadLeadStages();

    function updateAudienceUI(){
        const v = audience.value;
        leadStageWrap.style.display = (v === 'lead_stage') ? 'block' : 'none';
    }
    
    // Charger les stages depuis le pipeline
    async function loadLeadStages() {
        try {
            const response = await fetch('api/leads-by-stage.php?action=stages');
            const data = await response.json();
            
            if (data.success && data.stages) {
                leadStages = data.stages;
                renderStageCheckboxes();
            }
        } catch (error) {
            console.error('Erreur chargement stages:', error);
            document.getElementById('stage-checkboxes').innerHTML = 
                '<div class="alert alert-danger">Erreur de chargement</div>';
        }
    }
    
    // Afficher les checkboxes de stages
    function renderStageCheckboxes() {
        const container = document.getElementById('stage-checkboxes');
        
        if (leadStages.length === 0) {
            container.innerHTML = '<div class="text-muted">Aucune étape disponible</div>';
            return;
        }
        
        let html = '<div class="row g-2">';
        
        leadStages.forEach(stage => {
            const color = stage.color_code || '#6c757d';
            html += `
                <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input stage-checkbox" 
                               type="checkbox" 
                               value="${stage.stage_id}" 
                               id="stage_${stage.stage_id}"
                               data-slug="${stage.slug}"
                               onchange="updateSelectedStages()">
                        <label class="form-check-label" for="stage_${stage.stage_id}">
                            <span class="badge" style="background-color: ${color}">
                                ${stage.name}
                            </span>
                            <small class="text-muted">(${stage.lead_count} leads)</small>
                        </label>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        container.innerHTML = html;
    }
    
    // Mettre à jour la sélection
    window.updateSelectedStages = function() {
        selectedStageIds = [];
        document.querySelectorAll('.stage-checkbox:checked').forEach(cb => {
            selectedStageIds.push(cb.value);
        });
        
        // Mettre à jour le champ caché
        document.getElementById('lead_stages_input').value = selectedStageIds.join(',');
        
        // Compter les leads totaux
        updateLeadsCount();
    };
    
    // Compter les leads pour les stages sélectionnés
    async function updateLeadsCount() {
        if (selectedStageIds.length === 0) {
            document.getElementById('leads-count-info').innerHTML = 
                '<i class="fas fa-info-circle"></i> Sélectionnez une ou plusieurs étapes';
            return;
        }
        
        let totalLeads = 0;
        leadStages.forEach(stage => {
            if (selectedStageIds.includes(String(stage.stage_id))) {
                totalLeads += parseInt(stage.lead_count) || 0;
            }
        });
        
        document.getElementById('leads-count-info').innerHTML = 
            `<i class="fas fa-check-circle text-success"></i> ${totalLeads} lead(s) ciblé(s) dans ${selectedStageIds.length} étape(s)`;
    }

    audience.addEventListener('change', updateAudienceUI);
    updateAudienceUI();
});
</script>
</body>
</html>