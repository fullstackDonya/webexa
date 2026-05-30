<?php
// initialize messages to avoid undefined variable warnings
$success_message = $error_message = null;

// include the handler (filename uses dash in includes folder)
include __DIR__ . "/includes/opportunities-add.php";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>CRM Intelligent - Ajouter une Opportunité</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .invalid-feedback { display: block; }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        <?php include 'includes/topbar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header
             <include 'includes/header.php';>
            -->

            <!-- Page Content -->
            <div class="container-fluid">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Ajouter une Opportunité</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
                            <li class="breadcrumb-item"><a href="opportunities.php">Opportunités</a></li>
                            <li class="breadcrumb-item active">Ajouter</li>
                        </ol>
                    </nav>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-1"></i>
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Informations de l'Opportunité</h5>
                        <form method="POST" class="row g-3 needs-validation" novalidate>
                             <!-- customer_id from session (hidden) -->
                            <input type="hidden" name="customer_id" value="<?php echo htmlspecialchars($customer_id ?? ''); ?>">

                            <div class="col-md-6">
                                <label class="form-label">Titre <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" required>
                                <div class="invalid-feedback">Veuillez saisir le titre.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Montant (€)</label>
                                <input type="number" name="amount" class="form-control" min="0" step="0.01">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control"></textarea>
                            </div>
                   
                            <!-- Company (filtered by session customer_id) -->
                            <div class="col-md-6">
                                <label class="form-label">Société (client)</label>
                                <?php if (!empty($companies)): ?>
                                    <select name="company_id" class="form-select">
                                        <option value="">-- Aucune --</option>
                                        <?php foreach ($companies as $c): ?>
                                            <option value="<?php echo intval($c['id']); ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <select name="company_id" class="form-select">
                                        <option value="">Aucune société disponible</option>
                                    </select>
                                    <div class="form-text">Aucune société rattachée à ce customer.</div>
                                <?php endif; ?>
                            </div>
                    
                                             
      
                            <div class="col-md-6">
                                <label class="form-label">Étape <span class="text-danger">*</span></label>
                                <select name="stage" class="form-select" required>
                                    <option value="">Sélectionner une étape</option>
                                    <?php foreach ($stages as $key => $label): ?>
                                        <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Veuillez sélectionner une étape.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Probabilité (%)</label>
                                <input type="number" name="probability" class="form-control" min="0" max="100">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date de clôture prévue</label>
                                <input type="date" name="expected_close_date" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Source</label>
                                <input type="text" name="source" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Concurrents</label>
                                <input type="text" name="competitor" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Raison de perte</label>
                                <input type="text" name="loss_reason" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Prochaine action</label>
                                <input type="text" name="next_action" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date prochaine action</label>
                                <input type="datetime-local" name="next_action_date" class="form-control">
                            </div>
                            <div class="col-12">
                                <button class="btn btn-primary" type="submit">
                                    <i class="bi bi-plus-circle me-1"></i>
                                    Ajouter l'Opportunité
                                </button>
                                <a href="opportunities.php" class="btn btn-secondary">
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