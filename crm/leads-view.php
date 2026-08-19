<?php
include("includes/verify_subscriptions.php");

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: leads.php");
    exit;
}

$lead_id = intval($_GET['id']);
$customer_id = $_SESSION['customer_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

try {
    // Récupérer le lead
    $stmt = $pdo->prepare("
        SELECT l.*, c.name as company_name
        FROM leads l
        LEFT JOIN companies c ON l.company_id = c.id
        WHERE l.id = ? AND (l.customer_id = ? OR l.assigned_to = ?)
    ");
    $stmt->execute([$lead_id, $customer_id, $user_id]);
    $lead = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lead) {
        header("Location: leads.php");
        exit;
    }

} catch (Exception $e) {
    error_log("Error fetching lead: " . $e->getMessage());
    header("Location: leads.php");
    exit;
}

$page_title = "Consulter Lead - CRM Intelligent";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
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
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-user-circle text-primary"></i> Consulter Lead
                    </h1>
                    <div class="btn-group">
                        <a href="leads-edit.php?id=<?php echo intval($lead['id']); ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Éditer
                        </a>
                        <a href="leads.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Retour
                        </a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <!-- Informations principales -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    Informations du Lead
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="text-muted small">Prénom</label>
                                        <h5><?php echo htmlspecialchars($lead['first_name'] ?? '--'); ?></h5>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="text-muted small">Nom</label>
                                        <h5><?php echo htmlspecialchars($lead['last_name'] ?? '--'); ?></h5>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="text-muted small">Email</label>
                                        <p><a href="mailto:<?php echo htmlspecialchars($lead['email'] ?? ''); ?>"><?php echo htmlspecialchars($lead['email'] ?? '--'); ?></a></p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="text-muted small">Téléphone</label>
                                        <p><?php echo htmlspecialchars($lead['phone'] ?? '--'); ?></p>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="text-muted small">Téléphone +</label>
                                        <p><a href="mailto:<?php echo htmlspecialchars($lead['phone2'] ?? ''); ?>"><?php echo htmlspecialchars($lead['phone2'] ?? '--'); ?></a></p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="text-muted small">Téléphone +</label>
                                        <p><?php echo htmlspecialchars($lead['phone2'] ?? '--'); ?></p>
                                    </div>
                                </div>


                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="text-muted small">Poste</label>
                                        <p><?php echo htmlspecialchars($lead['position'] ?? '--'); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="text-muted small">Entreprise</label>
                                        <p><?php echo htmlspecialchars($lead['company_name'] ?? '--'); ?></p>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="text-muted small">Tags</label>
                                        <p><?php echo htmlspecialchars($lead['tags'] ?? '--'); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="text-muted small">Secteur d'activité</label>
                                        <p><?php echo htmlspecialchars($lead['sector'] ?? '--'); ?></p>
                                    </div>
                                </div>

                                   <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="text-muted small">Heure d'ouverture</label>
                                        <p><?php echo htmlspecialchars($lead['opening_hours'] ?? '--'); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="text-muted small">Description</label>
                                        <p><?php echo htmlspecialchars($lead['description'] ?? '--'); ?></p>
                                    </div>
                                </div>
                              


                                <hr>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="text-muted small">Statut</label>
                                        <p>
                                            <span class="badge bg-<?php echo in_array(($lead['stage'] ?? $lead['status'] ?? ''), ['qualified', 'converted']) ? 'success' : 'warning'; ?>">
                                                <?php 
                                                $stage_labels = [
                                                    'lead' => 'Nouveau',
                                                    'contacted' => 'Contacté',
                                                    'qualified' => 'Qualifié',
                                                    'proposal' => 'Proposition',
                                                    'converted' => 'Converti',
                                                    'unqualified' => 'Perdu'
                                                ];
                                                $current_stage = $lead['stage'] ?? $lead['status'] ?? 'lead';
                                                echo $stage_labels[$current_stage] ?? htmlspecialchars($current_stage);
                                                ?>
                                            </span>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="text-muted small">Source</label>
                                        <p><?php echo htmlspecialchars($lead['source'] ?? '--'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <!-- Score IA et statistiques -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-brain"></i> Score IA
                                </h6>
                            </div>
                            <div class="card-body text-center">
                                <div class="h2 font-weight-bold text-primary">
                                    <?php echo isset($lead['ai_score']) && $lead['ai_score'] !== null ? intval($lead['ai_score']) : '--'; ?>
                                </div>
                                <p class="text-muted small">Score de qualification</p>
                            </div>
                        </div>

                        <!-- Dates -->
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-calendar"></i> Historique
                                </h6>
                            </div>
                            <div class="card-body">
                                <p>
                                    <strong>Créé le:</strong><br>
                                    <?php echo htmlspecialchars(isset($lead['created_at']) ? date('d/m/Y à H:i', strtotime($lead['created_at'])) : '--'); ?>
                                </p>
                                <p>
                                    <strong>Mis à jour le:</strong><br>
                                    <?php echo htmlspecialchars(isset($lead['updated_at']) ? date('d/m/Y à H:i', strtotime($lead['updated_at'])) : '--'); ?>
                                </p>
                                <p>
                                    <strong>ID:</strong><br>
                                    #<?php echo intval($lead['id']); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
