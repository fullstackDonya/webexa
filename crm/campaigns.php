<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/includes/verify_subscriptions.php';


$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$customer_id = $_SESSION['customer_id'] ?? null;


$page_title = "Campagnes Marketing - CRM Intelligent";

// Fetch campaigns for display (filter by customer_id if in session)
$customer_id = $_SESSION['customer_id'] ?? null;
try{
    if($customer_id){
        $stmt = $pdo->prepare('SELECT * FROM campaigns WHERE customer_id = ? ORDER BY created_at DESC LIMIT 200');
        $stmt->execute([$customer_id]);
    } else {
        $stmt = $pdo->query('SELECT * FROM campaigns ORDER BY created_at DESC LIMIT 200');
    }
    $campaigns = $stmt->fetchAll();
}catch(Exception $e){
    $campaigns = [];
}

// Quick KPIs
$activeCount = 0; $totalOpenRate = 0; $countWithOpen = 0; $leadsGenerated = 0; $avgRoi = 0;
foreach($campaigns as $c){
    if(isset($c['status']) && $c['status'] === 'active') $activeCount++;
    // placeholder fields for open rate / leads / roi if present
    if(!empty($c['open_rate'])){ $totalOpenRate += floatval($c['open_rate']); $countWithOpen++; }
    if(!empty($c['leads'])) $leadsGenerated += intval($c['leads']);
    if(!empty($c['roi'])) $avgRoi += floatval($c['roi']);
}
if($countWithOpen) $avgOpen = round($totalOpenRate / $countWithOpen,1); else $avgOpen = 0;
if(count($campaigns)) $avgRoi = $avgRoi ? round($avgRoi / count($campaigns),1) : 0; else $avgRoi = 0;



// récupération des filtres GET
$search = trim($_GET['q'] ?? '');
$filter_status = $_GET['status'] ?? '';
$filter_type = $_GET['type'] ?? '';
$filter_channel = $_GET['channel'] ?? '';

try {
    $conditions = [];
    $params = [];

    if ($customer_id) {
        $conditions[] = 'customer_id = ?';
        $params[] = $customer_id;
    }

    if ($search !== '') {
        $conditions[] = '(name LIKE ? OR subject LIKE ?)';
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }
    if ($filter_status !== '') {
        $conditions[] = 'status = ?';
        $params[] = $filter_status;
    }
    if ($filter_type !== '') {
        $conditions[] = 'type = ?';
        $params[] = $filter_type;
    }
    if ($filter_channel !== '') {
        $conditions[] = 'channel = ?';
        $params[] = $filter_channel;
    }

    $sql = 'SELECT * FROM campaigns' . ($conditions ? ' WHERE ' . implode(' AND ', $conditions) : '') . ' ORDER BY created_at DESC LIMIT 200';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('campaigns fetch error: ' . $e->getMessage());
    $campaigns = [];
}

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
    <style>
        .table{
            color:#fff !important;
        }
        .modal{
            color:#000 !important;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/topbar.php'; ?>
            
            <div class="container-fluid">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-bullhorn text-primary"></i> Campagnes Marketing
                    </h1>
                    <a href="campaigns-add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nouvelle Campagne
                    </a>
                </div>

       
                <!-- KPIs Campagnes -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Campagnes Actives
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="active-campaigns"><?php echo intval($activeCount); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-bullhorn fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Taux d'Ouverture Moyen
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="avg-open-rate"><?php echo htmlspecialchars($avgOpen); ?>%</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-envelope-open fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Leads Générés
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="leads-generated"><?php echo intval($leadsGenerated); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            ROI Moyen
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="avg-roi"><?php echo htmlspecialchars($avgRoi); ?>%</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

        
                <!-- Filtres -->
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <form id="campaigns-filters" method="get" class="row g-2 align-items-center">
                            <div class="col-md-3">
                                <input type="search" name="q" class="form-control" id="search-campaigns" placeholder="Rechercher une campagne..." value="<?php echo htmlspecialchars($search, ENT_QUOTES); ?>">
                            </div>
                            <div class="col-md-2">
                                <select class="form-control" id="filter-status" name="status">
                                    <option value="">Tous les statuts</option>
                                    <option value="draft" <?php if($filter_status==='draft') echo 'selected'; ?>>Brouillon</option>
                                    <option value="scheduled" <?php if($filter_status==='scheduled') echo 'selected'; ?>>Programmée</option>
                                    <option value="active" <?php if($filter_status==='active') echo 'selected'; ?>>Active</option>
                                    <option value="paused" <?php if($filter_status==='paused') echo 'selected'; ?>>En pause</option>
                                    <option value="completed" <?php if($filter_status==='completed') echo 'selected'; ?>>Terminée</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-control" id="filter-type" name="type">
                                    <option value="">Tous les types</option>
                                    <option value="email" <?php if($filter_type==='email') echo 'selected'; ?>>Email</option>
                                    <option value="whatsapp" <?php if($filter_type==='whatsapp') echo 'selected'; ?>>WhatsApp</option>
                                    <option value="social" <?php if($filter_type==='social') echo 'selected'; ?>>Réseaux sociaux</option>
                                    <option value="display" <?php if($filter_type==='display') echo 'selected'; ?>>Display</option>
                                    <option value="search" <?php if($filter_type==='search') echo 'selected'; ?>>Search</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-control" id="filter-channel" name="channel">
                                    <option value="">Tous les canaux</option>
                                    <option value="email" <?php if($filter_channel==='email') echo 'selected'; ?>>Email</option>
                                    <option value="whatsapp" <?php if($filter_channel==='whatsapp') echo 'selected'; ?>>WhatsApp</option>
                                    <option value="mailchimp" <?php if($filter_channel==='mailchimp') echo 'selected'; ?>>Mailchimp</option>
                                    <option value="facebook" <?php if($filter_channel==='facebook') echo 'selected'; ?>>Facebook</option>
                                    <option value="google" <?php if($filter_channel==='google') echo 'selected'; ?>>Google Ads</option>
                                    <option value="linkedin" <?php if($filter_channel==='linkedin') echo 'selected'; ?>>LinkedIn</option>
                                </select>
                            </div>
                            <div class="col-md-3 text-end">
                                <a href="campaigns.php" class="btn btn-outline-secondary me-2">
                                    <i class="fas fa-undo"></i> Reset
                                </a>
                                <button type="submit" class="btn btn-info" onclick="return true;">
                                    <i class="fas fa-search"></i> Rechercher / Appliquer
                                </button>
                                <a href="campaigns-export.php?<?php echo htmlspecialchars($_SERVER['QUERY_STRING']); ?>" class="btn btn-outline-primary ms-2">Exporter</a>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- End Filtres -->
                <script>
                    // petit script d'UX : soumettre le form quand un select change
                    (function(){
                        var selects = document.querySelectorAll('#campaigns-filters select');
                        selects.forEach(function(s){ s.addEventListener('change', function(){ document.getElementById('campaigns-filters').submit(); }); });
                    })();
                </script>
         

                <!-- Liste des campagnes -->
                <div class="row" id="campaigns-grid">
                    <?php if(empty($campaigns)): ?>
                        <div class="col-12">
                            <div class="alert alert-info">Aucune campagne trouvée. Créez-en une nouvelle.</div>
                        </div>
                    <?php else: foreach($campaigns as $camp): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?php echo htmlspecialchars($camp['name']); ?></h5>
                                    <p class="card-text text-muted mb-2">Type: <?php echo htmlspecialchars($camp['type'] ?? '-'); ?></p>
                                    <p class="mb-3"><?php echo htmlspecialchars(substr($camp['subject'] ?? '',0,120)); ?></p>
                                    <div class="mt-auto d-flex justify-content-between align-items-center">
                                        <small class="text-muted"><?php echo date('d/m/Y', strtotime($camp['created_at'])); ?></small>
                                        <div>
                                            <button class="btn btn-sm btn-success" onclick="openSendModal(<?php echo intval($camp['id']); ?>)" title="Envoyer">
                                                <i class="fas fa-paper-plane"></i>
                                            </button>
                                            <a href="campaigns-email.php?id=<?php echo intval($camp['id']); ?>" class="btn btn-sm btn-outline-primary">Voir</a>
                                            <a href="campaigns-edit.php?id=<?php echo intval($camp['id']); ?>" class="btn btn-sm btn-secondary">Éditer</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>

                <!-- Performance Overview -->
                <div class="row mt-4">
                    <div class="col-lg-8">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Performance des Campagnes</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="campaign-performance-chart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Top Campagnes</h6>
                            </div>
                            <div class="card-body">
                                <div id="top-campaigns-list">
                                    <!-- Top campagnes chargées via JS -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/campaigns.js"></script>

    <!-- Modal Envoi Campagne -->
    <?php include 'includes/campaign_send_modal.php'; ?>
    
    <!-- Modal Templates -->
    <?php include 'includes/template_suggestions_modal.php'; ?>
    
    <script>
    // Fonction pour charger un template dans une campagne
    window.loadTemplateInCampaign = function(templateId) {
        fetch(`api/email-templates.php?action=template&id=${templateId}`)
            .then(r => r.ok ? r.json() : Promise.reject('Erreur'))
            .then(template => {
                // Rediriger vers email-editor avec le template
                window.location.href = `email-editor.php?template=${templateId}`;
            })
            .catch(err => {
                console.error(err);
                alert('❌ Erreur lors du chargement du template');
            });
    };
    </script>
</body>
</html>
</body>
</html>
