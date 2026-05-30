<?php
include("includes/contacts-import.php");

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
                        <i class="fas fa-file-import text-primary"></i> Import de Contacts
                    </h1>
                    <a href="contacts.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Retour à la liste
                    </a>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card shadow">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">Importer depuis un fichier CSV/Excel</h6>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Format requis :</strong> Fichier CSV ou Excel (.xlsx) avec les colonnes : prénom, nom, email, téléphone, entreprise, poste
                                </div>

                                <form id="import-form" enctype="multipart/form-data" method="post" action="">
                                    <div class="mb-3">
                                        <label for="csv_file" class="form-label">Fichier CSV ou Excel (.xlsx) *</label>
                                        <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv,.xlsx" required>
                                        <div class="form-text">Taille maximum : 5MB</div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="skip_duplicates" name="skip_duplicates" value="1" checked>
                                            <label class="form-check-label" for="skip_duplicates">
                                                Ignorer les doublons (basé sur l'email)
                                            </label>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="default_source" class="form-label">Source par défaut</label>
                                        <select class="form-control" id="default_source" name="default_source">
                                            <option value="import">Import CSV</option>
                                            <option value="website">Site web</option>
                                            <option value="trade_show">Salon professionnel</option>
                                            <option value="email_campaign">Campagne email</option>
                                        </select>
                                    </div>

                                    <div class="text-end">
                                        <button type="button" class="btn btn-secondary me-2">
                                            <i class="fas fa-download"></i> Télécharger modèle
                                        </button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-upload"></i> Importer
                                        </button>
                                    </div>
                                </form>
                                <?php if (!empty($success_message) || !empty($error_message)): ?>
                                    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                                        <?php if (!empty($success_message)): ?>
                                            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                                        <?php endif; ?>
                                        <?php if (!empty($error_message)): ?>
                                            <div class="text-danger mt-2"><i class="fas fa-triangle-exclamation"></i> <?php echo $error_message; ?></div>
                                        <?php endif; ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                                    </div>
                                <?php endif; ?>

                                <div id="import-progress" class="mt-4" style="display: none;">
                                    <h6>Progression de l'import :</h6>
                                    <div class="progress">
                                        <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                    </div>
                                    <div id="import-status" class="mt-2"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card shadow">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">Guide d'import</h6>
                            </div>
                            <div class="card-body">
                                <h6>Étapes d'import :</h6>
                                <ol class="small">
                                    <li>Téléchargez le modèle CSV</li>
                                    <li>Remplissez vos données dans le fichier</li>
                                    <li>Vérifiez le format des emails</li>
                                    <li>Chargez le fichier et lancez l'import</li>
                                </ol>

                                <h6 class="mt-4">Colonnes obligatoires :</h6>
                                <ul class="small text-muted">
                                    <li><strong>prénom</strong> : Prénom du contact</li>
                                    <li><strong>nom</strong> : Nom du contact</li>
                                    <li><strong>email</strong> : Adresse email (unique)</li>
                                </ul>

                                <h6 class="mt-4">Colonnes optionnelles :</h6>
                                <ul class="small text-muted">
                                    <li><strong>téléphone</strong> : Numéro de téléphone</li>
                                    <li><strong>entreprise</strong> : Nom de l'entreprise</li>
                                    <li><strong>poste</strong> : Fonction du contact</li>
                                </ul>
                            </div>
                        </div>

                        <div class="card shadow mt-4">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">Historique des imports</h6>
                            </div>
                            <div class="card-body">
                                <div id="import-history">
                                    <!-- Historique chargé via JavaScript -->
                                </div>
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