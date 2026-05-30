<?php include 'includes/folder_view.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dossier : <?php echo htmlspecialchars($folder['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/folder_view.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content fade-in">
            <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <strong>Succès !</strong> La mission a été ajoutée avec succès.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- En-tête -->
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h1><i class="fas fa-folder-open"></i> <?php echo htmlspecialchars($folder['name']); ?></h1>
                        <p><i class="fas fa-building"></i> <?php echo htmlspecialchars($folder['company_name']); ?></p>
                    </div>
                    <a href="folders.php" class="btn btn-light">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>

            <!-- Cartes d'information -->
            <div class="info-grid">
                <div class="stat-card">
                    <i class="fas fa-file-alt"></i>
                    <h3><?php echo htmlspecialchars(substr($folder['name'], 0, 25)); ?></h3>
                    <p>Dossier</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-building"></i>
                    <h3><?php echo htmlspecialchars(substr($folder['company_name'], 0, 25)); ?></h3>
                    <p>Entreprise</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-calendar"></i>
                    <h3><?php echo htmlspecialchars(date('d/m/Y', strtotime($folder['created_at']))); ?></h3>
                    <p>Créé le</p>
                </div>
                <div class="stat-card">
                    <a href="generate_invoice.php?folder_id=<?php echo $folder_id; ?>" class="btn btn-success" style="width: 100%; margin-top: 0.5rem;">
                        <i class="fas fa-file-invoice"></i> Facture
                    </a>
                </div>
            </div>

            <!-- Détails du dossier -->
            <div class="form-section">
                <h4><i class="fas fa-info-circle"></i> Détails du dossier</h4>
                <div class="info-row">
                    <span class="info-label">📋 Description</span>
                    <span class="info-value"><?php echo htmlspecialchars($folder['description'] ?? 'Aucune description'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">📅 Créé le</span>
                    <span class="info-value"><?php echo htmlspecialchars(date('d/m/Y à H:i', strtotime($folder['created_at']))); ?></span>
                </div>
            </div>

            <!-- Formulaire d'ajout de mission -->
            <div class="form-section">
                <h4><i class="fas fa-plus-circle"></i> Ajouter une mission</h4>
                <?php if (!empty($mission_error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> <strong>Erreur :</strong> <?php echo $mission_error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <form method="post" class="needs-validation" id="mission-form" novalidate>
                    <input type="hidden" name="folder_id" value="<?php echo htmlspecialchars($folder_id); ?>">
                    <input type="hidden" name="type" value="general">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-heading"></i> Nom de la mission *</label>
                            <input type="text" name="name" class="form-control" placeholder="Ex: Audit CRM, Installation..." required>
                            <div class="invalid-feedback">Le nom est requis.</div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label"><i class="fas fa-tasks"></i> Statut *</label>
                            <select name="status_id" class="form-select" required>
                                <option value="">-- Sélectionner --</option>
                                <?php foreach ($all_statuses as $stat): ?>
                                    <option value="<?php echo $stat['id']; ?>"><?php echo htmlspecialchars($stat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Veuillez sélectionner un statut.</div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label"><i class="fas fa-clock"></i> Date / Heure</label>
                            <input type="datetime-local" name="datetime" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label"><i class="fas fa-project-diagram"></i> Projet</label>
                            <input type="text" name="project" class="form-control" placeholder="Référence">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label"><i class="fas fa-user-tie"></i> Responsable</label>
                            <input type="text" name="responsible" class="form-control" placeholder="Nom">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label"><i class="fas fa-boxes"></i> Quantité</label>
                            <input type="number" name="quantity" class="form-control" min="1" placeholder="Qté">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-map-marker-alt"></i> Départ</label>
                            <input type="text" name="departure" class="form-control" placeholder="Adresse de départ">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-location-dot"></i> Arrivée</label>
                            <input type="text" name="arrival" class="form-control" placeholder="Adresse d'arrivée">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label"><i class="fas fa-user"></i> Chauffeur</label>
                            <input type="text" name="driver" class="form-control" placeholder="Nom">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label"><i class="fas fa-car"></i> Véhicule</label>
                            <input type="text" name="vehicle" class="form-control" placeholder="Immat.">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-shopping-bag"></i> Produit</label>
                            <input type="text" name="product" class="form-control" placeholder="Produit/service">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label"><i class="fas fa-align-left"></i> Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Détails importants..."></textarea>
                        </div>

                        <div class="col-md-12 text-end">
                            <button type="button" class="btn btn-secondary me-2" onclick="document.getElementById('mission-form').reset();">
                                <i class="fas fa-times"></i> Annuler
                            </button>
                            <button type="submit" name="add_mission" class="btn btn-success">
                                <i class="fas fa-plus"></i> Ajouter
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Missions liées -->
            <div class="form-section">
                <h4><i class="fas fa-list-check"></i> Missions (<?php echo count($missions ?? []); ?>)</h4>
                <div class="table-responsive mt-3">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-tag"></i> Mission</th>
                                <th><i class="fas fa-info-circle"></i> Détails</th>
                                <th><i class="fas fa-calendar"></i> Date</th>
                                <th><i class="fas fa-check-double"></i> Statut</th>
                                <th><i class="fas fa-tools"></i> Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($missions)): ?>
                            <?php foreach ($missions as $mission): ?>
                                <?php
                                $details = [];
                                if (!empty($mission['departure']) || !empty($mission['arrival'])) {
                                    $details[] = trim(($mission['departure'] ?? '') . ' → ' . ($mission['arrival'] ?? ''));
                                }
                                if (!empty($mission['driver'])) { $details[] = '👤 ' . $mission['driver']; }
                                if (!empty($mission['vehicle'])) { $details[] = '🚗 ' . $mission['vehicle']; }
                                if (!empty($mission['product'])) { $details[] = '📦 ' . $mission['product']; }
                                if (!empty($mission['quantity'])) { $details[] = '📊 ' . $mission['quantity']; }
                                if (!empty($mission['project'])) { $details[] = '📋 ' . $mission['project']; }
                                if (!empty($mission['responsible'])) { $details[] = '👥 ' . $mission['responsible']; }
                                $detailsText = implode(' • ', $details);
                                $datetime = $mission['datetime'] ?? '';
                                $statusClass = $mission['status_name'] === 'Terminée' ? 'bg-success' : ($mission['status_name'] === 'En cours' ? 'bg-info' : 'bg-warning');
                                ?>
                                <tr>
                                    <td>
                                        <strong>M-<?php echo htmlspecialchars($mission['id']); ?></strong>
                                        <br><small><?php echo htmlspecialchars($mission['name'] ?? ''); ?></small>
                                    </td>
                                    <td><small><?php echo htmlspecialchars($detailsText ?: '–'); ?></small></td>
                                    <td><?php echo $datetime ? htmlspecialchars(date('d/m/Y H:i', strtotime($datetime))) : '–'; ?></td>
                                    <td><span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($mission['status_name'] ?? ''); ?></span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="mission_view.php?id=<?php echo $mission['id']; ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                                            <a href="mission_edit.php?id=<?php echo $mission['id']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                            <a href="mission_delete.php?id=<?php echo $mission['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer ?');"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox" style="font-size: 2rem; opacity: 0.3;"></i>
                                    <p class="mt-2">Aucune mission.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.querySelectorAll('.needs-validation');
                Array.prototype.slice.call(forms).forEach(function(form) {
                    form.addEventListener('submit', function(event) {
                        if (!form.checkValidity()) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();

        // Fermeture auto des alertes
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
    </script>
    <script src="assets/js/folder_view.js" defer></script>
</body>
</html>
