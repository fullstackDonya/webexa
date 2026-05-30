<?php

include 'includes/leads-add.php';
$page_title = "Ajouter Lead - CRM Intelligent";
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
                        <i class="fas fa-bullseye text-primary"></i> Nouveau Lead
                    </h1>
                    <a href="leads.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Retour aux leads
                    </a>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card shadow">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">Informations du Lead</h6>
                            </div>
                            <div class="card-body">
                                <form id="lead-form" method="POST" >
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="first_name" class="form-label">Prénom *</label>
                                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="last_name" class="form-label">Nom *</label>
                                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="email" class="form-label">Email *</label>
                                                <input type="email" class="form-control" id="email" name="email" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="phone" class="form-label">Téléphone</label>
                                                <input type="tel" class="form-control" id="phone" name="phone">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <!-- <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="company" class="form-label">Entreprise</label>
                                                <input type="text" class="form-control" id="company" name="company">
                                            </div>
                                        </div> -->
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="position" class="form-label">Poste</label>
                                                <input type="text" class="form-control" id="position" name="position">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="source" class="form-label">Source *</label>
                                                <select class="form-control" id="source" name="source" required>
                                                    <option value="">Sélectionner une source</option>
                                                    <option value="website">Site web</option>
                                                    <option value="referral">Recommandation</option>
                                                    <option value="social_media">Réseaux sociaux</option>
                                                    <option value="email_campaign">Campagne email</option>
                                                    <option value="trade_show">Salon professionnel</option>
                                                    <option value="cold_call">Appel à froid</option>
                                                    <option value="advertising">Publicité</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="stage" class="form-label">Statut</label>
                                                <select class="form-control" id="stage" name="stage">
                                                    <option value="lead">Nouveau</option>
                                                    <option value="contacted">Contacté</option>
                                                    <option value="qualified">Qualifié</option>
                                                    <option value="proposal">Proposition</option>
                                                    <option value="converted">Converti</option>
                                                    <option value="unqualified">Perdu</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="budget" class="form-label">Budget estimé (€)</label>
                                                <input type="number" class="form-control" id="budget" name="budget" min="0">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="interest" class="form-label">Centres d'intérêt</label>
                                        <textarea class="form-control" id="interest" name="interest" rows="3" placeholder="Produits ou services qui l'intéressent..."></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="notes" class="form-label">Notes</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="4" placeholder="Informations additionnelles..."></textarea>
                                    </div>

                                    <div class="text-end">
                                        <button type="reset" class="btn btn-secondary me-2">Annuler</button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Enregistrer Lead
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card shadow mb-4">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">Scoring IA</h6>
                            </div>
                            <div class="card-body text-center">
                                <div id="ai-score-display" class="mb-3">
                                    <div class="h2 ">--</div>
                                    <small class="">Score sera calculé après sauvegarde</small>
                                </div>
                                <button type="button" class="btn btn-info btn-sm" onclick="previewScore()">
                                    <i class="fas fa-brain"></i> Prévisualiser Score
                                </button>
                            </div>
                        </div>

                        <div class="card shadow">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">Guide Lead</h6>
                            </div>
                            <div class="card-body">
                                <h6>Informations importantes :</h6>
                                <ul class="small ">
                                    <li>Le scoring IA sera calculé automatiquement</li>
                                    <li>Plus d'informations = meilleur scoring</li>
                                    <li>La source influence la qualité du lead</li>
                                    <li>Le budget aide à prioriser</li>
                                </ul>

                                <h6 class="mt-4">Prochaines étapes :</h6>
                                <ol class="small ">
                                    <li>Enregistrer le lead</li>
                                    <li>Contacter dans les 24h</li>
                                    <li>Qualifier le besoin</li>
                                    <li>Créer une opportunité si qualifié</li>
                                </ol>
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