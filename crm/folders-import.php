<?php
include("includes/folders-import.php");
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
        <div class="main-content">
            <div class="container-fluid">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-file-import text-primary"></i> Import de Dossiers
                    </h1>
                    <a href="folders.php" class="btn btn-secondary">
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
                                    <strong>Format requis :</strong> Colonnes acceptées : name, company (nom de la société), description
                                </div>
                                <form id="import-form" enctype="multipart/form-data" method="post" action="">
                                    <div class="mb-3">
                                        <label for="csv_file" class="form-label">Fichier CSV ou Excel (.xlsx) *</label>
                                        <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv,.xlsx" required>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="skip_duplicates" name="skip_duplicates" value="1" checked>
                                            <label class="form-check-label" for="skip_duplicates">Ignorer les doublons (nom + société)</label>
                                        </div>
                                    </div>
                                    <div class="text-end">
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
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card shadow">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">Guide d'import</h6>
                            </div>
                            <div class="card-body">
                                <ul class="small text-muted">
                                    <li>Le champ <strong>name</strong> est obligatoire</li>
                                    <li>La colonne <strong>company</strong> doit contenir le nom exact de la société</li>
                                </ul>
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