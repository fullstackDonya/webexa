<?php

include 'includes/folder_add.php';
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
                        <i class="fas fa-folder-plus text-primary"></i> Nouveau Dossier
                    </h1>
                    <a href="folders.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Retour à la liste
                    </a>
                </div>
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card shadow">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">Informations du Dossier</h6>
                            </div>
                            <div class="card-body">
                                 <form method="POST" class="row g-3 needs-validation" novalidate>
                                    <div class="row">
                                        <div class="col-md-6">
                                          <div class="mb-3">
                                                <label for="folder_number" class="form-label">Numéro du dossier</label>
                                                <input type="text" class="form-control" id="folder_number" name="folder_number" value="<?php echo $folder_number; ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="company_id" class="form-label">Entreprise</label>
                                                <select class="form-control" id="company_id" name="company_id">
                                                    <option value="">Sélectionner une entreprise</option>
                                                    <?php foreach ($companies as $company): ?>
                                                        <option value="<?php echo htmlspecialchars($company['id']); ?>">
                                                            <?php echo htmlspecialchars($company['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                  
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Nom du dossier</label>
                                            <input type="text" class="form-control" id="name" name="name" required>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" rows="3"></textarea>
                                    </div>
                                    <div class="text-end">
                                        <button type="reset" class="btn btn-secondary me-2">Annuler</button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Enregistrer
                                        </button>
                                    </div>

                                     <?php if (!empty($errors)): ?>
                                        <div class="alert alert-danger">
                                            <?php foreach ($errors as $e) echo "<div>$e</div>"; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($success_message)): ?>
                                        <div class="alert alert-success">
                                            <?php echo $success_message; ?>
                                        </div>
                                    <?php endif; ?>
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
                                <h6>Conseils pour ajouter un dossier :</h6>
                                <ul class="small ">
                                    <li>Le nom du dossier est obligatoire</li>
                                    <li>Associez le dossier à une entreprise si possible</li>
                                    <li>Ajoutez une description pour plus de détails</li>
                                    <li>Vérifiez que le nom est unique pour éviter les doublons</li>
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
    // Validation du formulaire (Bootstrap)
    (function() {
        'use strict';
        window.addEventListener('load', function() {
            var forms = document.getElementsByClassName('needs-validation');
            Array.prototype.forEach.call(forms, function(form) {
                form.addEventListener('submit', function(event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);
    })();
    </script>
</body>
</html>