<?php

require_once __DIR__ . '/includes/customers-edit.php';
// helper sûr pour échapper les valeurs (évite htmlspecialchars(null) deprecated)


?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Éditer Client - <?php echo htmlspecialchars($company['name']); ?></title>
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
                    <i class="fas fa-user-edit text-warning"></i>
                    Éditer le Client
                </h1>
                <a href="customers-view.php?id=<?php echo $company['id']; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Annuler
                </a>
            </div>
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        Modifier les informations de <?php echo htmlspecialchars($company['name']); ?>
                    </h6>
                </div>
                <div class="card-body">
          
                <form method="post"  autocomplete="off">
                    <input type="hidden" name="id" value="<?php echo intval($company['id'] ?? 0); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nom *</label>
                            <input type="text" name="name" class="form-control" required maxlength="255" value="<?php echo htmlspecialchars((string)($company['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" maxlength="255" value="<?php echo htmlspecialchars((string)($company['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>
                
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Téléphone</label>
                            <input type="text" name="phone" class="form-control" maxlength="50" value="<?php echo htmlspecialchars((string)($company['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Secteur d'activité</label>
                            <input type="text" name="industry" class="form-control" maxlength="255" value="<?php echo htmlspecialchars((string)($company['industry'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>
                
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Segment</label>
                            <input type="text" name="segment" class="form-control" maxlength="255" value="<?php echo htmlspecialchars((string)($company['segment'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Revenus annuels (€)</label>
                            <input type="number" step="0.01" name="annual_revenue" class="form-control" value="<?php echo isset($company['annual_revenue']) && $company['annual_revenue'] !== null ? htmlspecialchars((string)$company['annual_revenue'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                        </div>
                    </div>
                
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Statut</label>
                            <select name="status" class="form-select">
                                <option value="client" <?php if(($company['status'] ?? '') === 'client') echo 'selected'; ?>>Client</option>
                                <option value="prospect" <?php if(($company['status'] ?? '') === 'prospect') echo 'selected'; ?>>Prospect</option>
                                <option value="partner" <?php if(($company['status'] ?? '') === 'partner') echo 'selected'; ?>>Partenaire</option>
                            </select>
                        </div>
                
                        <div class="col-md-4">
                            <label class="form-label">Satisfaction (/10)</label>
                            <input type="number" step="0.1" min="0" max="10" name="satisfaction" class="form-control" value="<?php echo isset($company['satisfaction']) && $company['satisfaction'] !== null ? htmlspecialchars((string)$company['satisfaction'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                        </div>
                
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" <?php if(!empty($company['is_active'])) echo 'checked'; ?>>
                                <label class="form-check-label" for="is_active">Actif</label>
                            </div>
                        </div>
                    </div>
                
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                    <a href="customers-view.php?id=<?php echo intval($company['id'] ?? 0); ?>" class="btn btn-secondary ms-2">
                        Annuler
                    </a>
                </form>

                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>