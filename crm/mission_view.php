<?php

session_start();
require_once 'config/database.php';

include 'includes/mission_view.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mission M-<?php echo htmlspecialchars($mission['id']); ?> | Dossier D-<?php echo htmlspecialchars($mission['folder_id']); ?></title>
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
                        <i class="fas fa-eye text-primary"></i> Détail de la Mission
                    </h1>
                    <a href="missions.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Retour à la liste
                    </a>
                </div>
                <div class="row">
                    <div class="col-lg-9">
                        <div class="card shadow mb-4">
                            <div class="card-body">
                                <h4>
                                    <span class="badge bg-primary">Mission M-<?php echo htmlspecialchars($mission['id']); ?></span>
                                    <span class="badge bg-secondary">Dossier D-<?php echo htmlspecialchars($mission['folder_id']); ?> (<?php echo htmlspecialchars($mission['folder_name']); ?>)</span>
                                </h4>
                                <p><strong>Société :</strong> <?php echo htmlspecialchars($mission['company_name']); ?></p>
                                <hr>
                                <div class="row mb-2">
                                    <div class="col-md-6">
                                        <p><i class="fas fa-user"></i> <strong>Client :</strong> <?php echo htmlspecialchars($mission['client'] ?? ''); ?></p>
                                        <p><i class="fas fa-map-marker-alt"></i> <strong>Départ :</strong> <?php echo htmlspecialchars($mission['departure'] ?? ''); ?></p>
                                        <p><i class="fas fa-flag-checkered"></i> <strong>Arrivée :</strong> <?php echo htmlspecialchars($mission['arrival'] ?? ''); ?></p>
                                        <p><i class="fas fa-calendar-alt"></i> <strong>Date/Heure :</strong>
                                            <?php
                                            $datetime = $mission['datetime'] ?? '';
                                            echo $datetime ? htmlspecialchars(date('d/m/Y H:i', strtotime($datetime))) : '';
                                            ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><i class="fas fa-user-tie"></i> <strong>Chauffeur :</strong> <?php echo htmlspecialchars($mission['driver'] ?? ''); ?></p>
                                        <p><i class="fas fa-car"></i> <strong>Véhicule :</strong> <?php echo htmlspecialchars($mission['vehicle'] ?? ''); ?></p>
                                        <p><i class="fas fa-info-circle"></i> <strong>Statut :</strong>
                                            <span class="badge bg-secondary">
                                                <?php echo htmlspecialchars($mission['status_name'] ?? 'Non défini'); ?>
                                            </span>
                                        </p>
                                        <p><i class="fas fa-calendar-plus"></i> <strong>Créée le :</strong>
                                            <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($mission['created_at']))); ?>
                                        </p>
                                    </div>
                                </div>
                                <a href="mission_edit.php?id=<?php echo $mission['id']; ?>" class="btn btn-warning">
                                    <i class="fas fa-edit"></i> Modifier
                                </a>
                                <a href="mission_delete.php?id=<?php echo $mission['id']; ?>" class="btn btn-danger" onclick="return confirm('Supprimer cette mission ?');">
                                    <i class="fas fa-trash"></i> Supprimer
                                </a>
                                <a href="missions.php" class="btn btn-secondary">Retour à la liste</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="card shadow mb-4">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">Aide</h6>
                            </div>
                            <div class="card-body">
                                <ul class="small text-muted">
                                    <li>Consultez tous les détails de la mission.</li>
                                    <li>Utilisez les boutons pour modifier ou supprimer la mission.</li>
                                    <li>Le statut est géré automatiquement selon l’avancement.</li>
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