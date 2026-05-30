<?php
include 'includes/customers-add.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Intelligent - Ajouter un Client</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #004085;
        }
        .card {
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        .invalid-feedback {
            display: block;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        <?php include 'includes/topbar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
-            <!--  include 'includes/header.php';--> --- IGNORE ---
            
            <!-- Page Content -->
            <div class="container-fluid">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Ajouter un Client</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
                            <li class="breadcrumb-item"><a href="customers.php">Clients</a></li>
                            <li class="breadcrumb-item active">Ajouter</li>
                        </ol>
                    </nav>
                </div>
                
                <div class="row">
                    <div class="col-lg-12">

                <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-1"></i>
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Informations du Client</h5>

                        <!-- Formulaire d'ajout -->
                        <form method="POST" class="row g-3 needs-validation" novalidate>
                            
                            <!-- Informations principales -->
                            <div class="col-md-6">
                                <label for="name" class="form-label">Nom de l'entreprise <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required>
                                <div class="invalid-feedback">
                                    Veuillez saisir le nom de l'entreprise.
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>

                            <div class="col-md-6">
                                <label for="phone" class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>

                            <div class="col-md-6">
                                <label for="website" class="form-label">Site web</label>
                                <input type="url" class="form-control" id="website" name="website" placeholder="https://">
                            </div>

                            <!-- Secteur et statut -->
                            <div class="col-md-6">
                                <label for="industry" class="form-label">Secteur d'activité</label>
                                <select class="form-select" id="industry" name="industry">
                                    <option value="">Sélectionner un secteur</option>
                                    <?php foreach ($industries as $industry): ?>
                                        <option value="<?php echo $industry; ?>"><?php echo $industry; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="status" class="form-label">Statut <span class="text-danger">*</span></label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="">Sélectionner un statut</option>
                                    <option value="prospect">Prospect</option>
                                    <option value="active">Client actif</option>
                                    <option value="inactive">Client inactif</option>
                                    <option value="lost">Perdu</option>
                                </select>
                                <div class="invalid-feedback">
                                    Veuillez sélectionner un statut.
                                </div>
                            </div>

                            <!-- Informations financières -->
                            <div class="col-md-6">
                                <label for="annual_revenue" class="form-label">Chiffre d'affaires annuel (€)</label>
                                <input type="number" class="form-control" id="annual_revenue" name="annual_revenue" min="0">
                            </div>

                            <div class="col-md-6">
                                <label for="employee_count" class="form-label">Nombre d'employés</label>
                                <input type="number" class="form-control" id="employee_count" name="employee_count" min="1">
                            </div>

                            <!-- Adresse -->
                            <div class="col-12">
                                <label for="address" class="form-label">Adresse</label>
                                <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                            </div>

                            <div class="col-md-6">
                                <label for="city" class="form-label">Ville</label>
                                <input type="text" class="form-control" id="city" name="city">
                            </div>

                            <div class="col-md-6">
                                <label for="country" class="form-label">Pays</label>
                                <input type="text" class="form-control" id="country" name="country" value="France">
                            </div>

                            <!-- Notes -->
                            <div class="col-12">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                            </div>

                            <!-- Boutons -->
                            <div class="col-12">
                                <button class="btn btn-primary" type="submit">
                                    <i class="bi bi-plus-circle me-1"></i>
                                    Ajouter le Client
                                </button>
                                <a href="customers.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left me-1"></i>
                                    Annuler
                                </a>
                            </div>
                        </form>

                    </div>
                </div>

            </div>
        </div>
        
            </div>
        </div>
    </div>

<!-- Vendor JS Files -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Template Main JS File -->
<script>
// Validation du formulaire
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
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

// Auto-hide alerts
setTimeout(function() {
    var alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        var bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);
</script>

</body>
</html>
