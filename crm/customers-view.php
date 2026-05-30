<?php

require_once 'includes/customers-view.php'; 



?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détail Client - <?php echo htmlspecialchars($company['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .table{
            color: #fff;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/topbar.php'; ?>
    <div class="main-content">
        <div class="container-fluid">
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-user-tie text-primary"></i>
                    Détail du Client
                </h1>
                <a href="customers.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Retour à la liste
                </a>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <?php echo htmlspecialchars($company['name']); ?>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Email :</strong> <?php echo htmlspecialchars($company['email']); ?><br>
                            <strong>Téléphone :</strong> <?php echo htmlspecialchars($company['phone']); ?><br>
                            <strong>Adresse :</strong> <?php echo htmlspecialchars($company['address']); ?><br>
                            <strong>Ville :</strong> <?php echo htmlspecialchars($company['city']); ?><br>
                            <strong>Pays :</strong> <?php echo htmlspecialchars($company['country']); ?><br>
                        </div>
                        <div class="col-md-4">
                            <strong>Type :</strong> <?php echo htmlspecialchars($company['industry']); ?><br>
                            <strong>Segment :</strong> <?php echo htmlspecialchars($company['segment']); ?><br>
                            <strong>Statut :</strong>
                            <span class="badge bg-<?php
                                switch ($company['status']) {
                                    case 'client': echo 'success'; break;
                                    case 'prospect': echo 'info'; break;
                                    case 'partner': echo 'primary'; break;
                                    default: echo 'secondary';
                                }
                            ?>">
                                <?php echo htmlspecialchars($company['status']); ?>
                            </span><br>
                            <strong>Dernière activité :</strong>
                            <?php echo isset($company['updated_at']) ? date('d/m/Y', strtotime($company['updated_at'])) : ''; ?><br>
                        </div>
                        <div class="col-md-4">
                            <strong>Revenus annuels :</strong> €<?php echo $company['annual_revenue'] ? number_format($company['annual_revenue'], 2, ',', ' ') : '0'; ?><br>
                            <strong>Satisfaction :</strong> <?php echo $company['satisfaction'] ? number_format($company['satisfaction'], 1, ',', ' ') : '0'; ?>/10<br>
                            <strong>Nombre de commandes :</strong> <?php echo (int)$company['orders']; ?><br>
                            <strong>Dernier achat :</strong> <?php echo isset($company['last_purchase']) ? date('d/m/Y', strtotime($company['last_purchase'])) : ''; ?><br>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="customers-edit.php?id=<?php echo $company['id']; ?>" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Modifier
                        </a>
                        <a href="customers-delete.php?id=<?php echo $company['id']; ?>" class="btn btn-danger" onclick="return confirm('Supprimer ce client ?');">
                            <i class="fas fa-trash"></i> Supprimer
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>