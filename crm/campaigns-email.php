<?php

include_once __DIR__ . '/includes/campaigns-email.php';


?>

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
        .dropdown-item{
            color:#fff !important;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">

            
            <div class="container-fluid">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Campagnes Email</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newCampaignModal">
                                <i class="fas fa-plus"></i> Nouvelle Campagne
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-file-export"></i> Exporter
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Statistiques rapides -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="card bg-primary text-white mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="fs-4 fw-bold">24</div>
                                        <div>Campagnes actives</div>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-envelope-open fs-2"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card bg-success text-white mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="fs-4 fw-bold">89.2%</div>
                                        <div>Taux de délivrance</div>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-check-circle fs-2"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card bg-warning text-white mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="fs-4 fw-bold">23.4%</div>
                                        <div>Taux d'ouverture</div>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-eye fs-2"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card bg-info text-white mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="fs-4 fw-bold">4.7%</div>
                                        <div>Taux de clic</div>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-mouse-pointer fs-2"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Filtres</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="statusFilter" class="form-label">Statut</label>
                                <select class="form-select" id="statusFilter">
                                    <option value="">Tous les statuts</option>
                                    <option value="draft">Brouillon</option>
                                    <option value="scheduled">Programmée</option>
                                    <option value="sent">Envoyée</option>
                                    <option value="active">Active</option>
                                    <option value="paused">Suspendue</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="typeFilter" class="form-label">Type</label>
                                <select class="form-select" id="typeFilter">
                                    <option value="">Tous les types</option>
                                    <option value="newsletter">Newsletter</option>
                                    <option value="promotional">Promotionnelle</option>
                                    <option value="transactional">Transactionnelle</option>
                                    <option value="welcome">Bienvenue</option>
                                    <option value="automation">Automatisée</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="dateFilter" class="form-label">Période</label>
                                <select class="form-select" id="dateFilter">
                                    <option value="">Toutes les périodes</option>
                                    <option value="today">Aujourd'hui</option>
                                    <option value="week">Cette semaine</option>
                                    <option value="month">Ce mois</option>
                                    <option value="quarter">Ce trimestre</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="searchCampaign" class="form-label">Recherche</label>
                                <input type="text" class="form-control" id="searchCampaign" placeholder="Nom de campagne...">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Liste des campagnes -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Campagnes Email</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Type</th>
                                        <th>Statut</th>
                                        <th>Destinataires</th>
                                        <th>Taux d'ouverture</th>
                                        <th>Taux de clic</th>
                                        <th>Date d'envoi</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="campaignsTableBody">
                                    <?php if(!empty($campaigns)): ?>
                                        <?php foreach($campaigns as $c): ?>
                                            <?php $isSelected = (!empty($campaign) && $campaign['id'] == $c['id']); ?>
                                            <tr class="<?php echo $isSelected ? 'table-primary' : ''; ?>">
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="campaign-icon me-2">
                                                            <i class="fas fa-envelope text-primary"></i>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold"><?php echo htmlspecialchars($c['name']); ?></div>
                                                            <small class="text-muted"><?php echo htmlspecialchars($c['subject'] ?? ''); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><span class="badge bg-info"><?php echo htmlspecialchars($c['type'] ?? ''); ?></span></td>
                                                <td><span class="badge bg-<?php echo ($c['status']==='sent')? 'success':'secondary'; ?>"><?php echo htmlspecialchars($c['status'] ?? ''); ?></span></td>
                                                <td><?php echo number_format(intval($c['recipients'] ?? 0)); ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="me-2"><?php echo htmlspecialchars($c['open_rate'] ?? '-'); ?></span>
                                                        <div class="progress" style="width: 50px; height: 8px;">
                                                            <div class="progress-bar bg-warning" style="width: <?php echo floatval($c['open_rate'] ?? 0); ?>%"></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="me-2"><?php echo htmlspecialchars($c['click_rate'] ?? '-'); ?></span>
                                                        <div class="progress" style="width: 50px; height: 8px;">
                                                            <div class="progress-bar bg-info" style="width: <?php echo floatval($c['click_rate'] ?? 0); ?>%"></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo (!empty($c['scheduled_at'])? date('d/m/Y', strtotime($c['scheduled_at'])) : '-'); ?></td>
                                                <td>
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                            Actions
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li><a class="dropdown-item" href="campaigns-edit.php?id=<?php echo intval($c['id']); ?>"><i class="fas fa-edit me-2"></i>Modifier</a></li>
                                                            <li><a class="dropdown-item" href="campaigns-duplicate.php?id=<?php echo intval($c['id']); ?>"><i class="fas fa-copy me-2"></i>Dupliquer</a></li>
                                                            <li><a class="dropdown-item" href="campaigns-stats.php?id=<?php echo intval($c['id']); ?>"><i class="fas fa-chart-bar me-2"></i>Statistiques</a></li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li><a class="dropdown-item text-danger" href="campaigns-delete.php?id=<?php echo intval($c['id']); ?>" onclick="return confirm('Supprimer cette campagne ?');"><i class="fas fa-trash me-2"></i>Supprimer</a></li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="8">Aucune campagne trouvée.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="campaign-icon me-2">
                                                    <i class="fas fa-percentage text-success"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold">Promotion Hiver</div>
                                                    <small class="text-muted">Réduction 30% sur tout</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-warning">Promotionnelle</span></td>
                                        <td><span class="badge bg-primary">Programmée</span></td>
                                        <td>5,234</td>
                                        <td>-</td>
                                        <td>-</td>
                                        <td>20/01/2024</td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                    Actions
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="#"><i class="fas fa-play me-2"></i>Envoyer</a></li>
                                                    <li><a class="dropdown-item" href="#"><i class="fas fa-edit me-2"></i>Modifier</a></li>
                                                    <li><a class="dropdown-item" href="#"><i class="fas fa-pause me-2"></i>Suspendre</a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><a class="dropdown-item text-danger" href="#"><i class="fas fa-trash me-2"></i>Supprimer</a></li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="campaign-icon me-2">
                                                    <i class="fas fa-heart text-danger"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold">Séquence Bienvenue</div>
                                                    <small class="text-muted">Nouveaux clients</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-secondary">Automatisée</span></td>
                                        <td><span class="badge bg-success">Active</span></td>
                                        <td>∞</td>
                                        <td>31.7%</td>
                                        <td>8.9%</td>
                                        <td>Automatique</td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                    Actions
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <?php if(!empty($campaign['id'])): // actions for a real campaign ?>
                                                        <li><a class="dropdown-item" href="campaigns-edit.php?id=<?php echo intval($campaign['id']); ?>"><i class="fas fa-cog me-2"></i>Configurer</a></li>
                                                        <li><a class="dropdown-item" href="campaigns-stats.php?id=<?php echo intval($campaign['id']); ?>"><i class="fas fa-chart-line me-2"></i>Performance</a></li>
                                                        <li><a class="dropdown-item" href="campaigns-pause.php?id=<?php echo intval($campaign['id']); ?>"><i class="fas fa-pause me-2"></i>Suspendre</a></li>
                                                    <?php else: // example/static row: show informative fallback ?>
                                                        <li><a class="dropdown-item" href="#" onclick="alert('Action non disponible pour cet exemple. Utilisez une campagne réelle depuis la liste.'); return false;"><i class="fas fa-cog me-2"></i>Configurer</a></li>
                                                        <li><a class="dropdown-item" href="#" onclick="alert('Action non disponible pour cet exemple. Utilisez une campagne réelle depuis la liste.'); return false;"><i class="fas fa-chart-line me-2"></i>Performance</a></li>
                                                        <li><a class="dropdown-item" href="#" onclick="alert('Action non disponible pour cet exemple. Utilisez une campagne réelle depuis la liste.'); return false;"><i class="fas fa-pause me-2"></i>Suspendre</a></li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Nouvelle Campagne -->
        <div class="modal fade" id="newCampaignModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Nouvelle Campagne Email</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="newCampaignForm" method="post" action="campaigns-add.php?return=campaigns-email.php">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="campaignName" class="form-label">Nom de la campagne</label>
                                        <input type="text" name="campaign_name" class="form-control" id="campaignName" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="campaignType" class="form-label">Type</label>
                                            <select class="form-select" id="campaignType" name="campaign_type" required>
                                            <option value="">Sélectionner...</option>
                                            <option value="newsletter">Newsletter</option>
                                            <option value="promotional">Promotionnelle</option>
                                            <option value="transactional">Transactionnelle</option>
                                            <option value="welcome">Bienvenue</option>
                                            <option value="automation">Automatisée</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="campaignSubject" class="form-label">Objet</label>
                                <input type="text" name="campaign_subject" class="form-control" id="campaignSubject" required>
                            </div>
                            
                            <!-- Sélecteur de templates -->
                            <?php include 'includes/template_selector.php'; ?>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="senderName" class="form-label">Nom de l'expéditeur</label>
                                        <input type="text" name="sender_name" class="form-control" id="senderName" value="Mon Entreprise">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="senderEmail" class="form-label">Email expéditeur</label>
                                        <input type="email" name="sender_email" class="form-control" id="senderEmail" value="noreply@monentreprise.com">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="audienceSelect" class="form-label">Audience</label>
                                <select class="form-select" id="audienceSelect" name="audience" required>
                                    <option value="">Sélectionner une liste...</option>
                                    <option value="all">Tous les contacts</option>
                                    <option value="subscribers">Abonnés newsletter</option>
                                    <option value="customers">Clients</option>
                                    <option value="prospects">Prospects</option>
                                    <option value="custom">Liste personnalisée</option>
                                    <?php if(!empty($companies)): ?>
                                        <optgroup label="Companies">
                                            <?php foreach($companies as $comp): ?>
                                                <option value="company_<?php echo intval($comp['id']); ?>"><?php echo htmlspecialchars($comp['name']); ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="schedulingType" class="form-label">Envoi</label>
                                        <select class="form-select" id="schedulingType">
                                            <option value="now">Envoyer maintenant</option>
                                            <option value="scheduled">Programmer</option>
                                            <option value="draft">Sauvegarder comme brouillon</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6" id="scheduleDateDiv" style="display: none;">
                                    <div class="mb-3">
                                        <label for="scheduleDate" class="form-label">Date et heure</label>
                                        <input type="datetime-local" class="form-control" id="scheduleDate">
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" form="newCampaignForm" class="btn btn-primary">Créer la campagne</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Modal pour les suggestions de templates -->
    <?php include 'includes/template_suggestions_modal.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du chargement de template
    window.loadTemplateInCampaign = function(templateId) {
        fetch(`api/email-templates.php?action=template&id=${templateId}`)
            .then(r => r.ok ? r.json() : Promise.reject('Erreur'))
            .then(template => {
                document.getElementById('campaignSubject').value = template.subject || '';
                document.getElementById('campaignContent').value = template.body ? template.body.replace(/<[^>]+>/g, '') : '';
                
                // Fermer le modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('templateSuggestionsModal'));
                if (modal) modal.hide();
                
                // Notification
                alert(`✅ Template "${template.name}" chargé !`);
            })
            .catch(err => {
                console.error(err);
                alert('❌ Erreur lors du chargement du template');
            });
    };
    
    // Gestion de l'affichage du champ date de programmation
    const schedulingType = document.getElementById('schedulingType');
    const scheduleDateDiv = document.getElementById('scheduleDateDiv');
    
    schedulingType.addEventListener('change', function() {
        if (this.value === 'scheduled') {
            scheduleDateDiv.style.display = 'block';
        } else {
            scheduleDateDiv.style.display = 'none';
        }
    });

    // Filtres en temps réel
    const filters = ['statusFilter', 'typeFilter', 'dateFilter', 'searchCampaign'];
    filters.forEach(filterId => {
        document.getElementById(filterId).addEventListener('change', function() {
            filterCampaigns();
        });
    });

    function filterCampaigns() {
        // Logique de filtrage des campagnes
        console.log('Filtrage des campagnes...');
    }
});
</script>


</body>
</html>

