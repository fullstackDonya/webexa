<?php

include 'includes/mission_edit.php';
function h($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

// helper pour datetime-local value
function dt_local($v){
    if (empty($v)) return '';
    $ts = strtotime($v);
    if ($ts === false) return '';
    return date('Y-m-d\TH:i', $ts);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mission M-<?php echo h($mission['id']); ?> | Dossier D-<?php echo h($mission['folder_id']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        <div class="main-content">
            <div class="container-fluid">
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <h3>Éditer la mission M-<?php echo h($mission['id']); ?></h3>

                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger"><?php echo h($error_message); ?></div>
                        <?php endif; ?>

                        <form method="post" autocomplete="off">
                            <input type="hidden" name="id" value="<?php echo intval($mission['id'] ?? 0); ?>">

                            <div class="col-md-3">
                                <label for="folder_id">Dossier</label>
                                <select name="folder_id" id="folder_id" class="form-control">
                                    <?php foreach ($folders as $f): ?>
                                        <option value="<?php echo (int)$f['id']; ?>" <?php echo ((int)$f['id'] === (int)($mission['folder_id'] ?? 0) ? 'selected' : ''); ?>>
                                            <?php echo h($f['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (!empty($_SESSION['flash_error'])): ?>
                                    <div class="text-danger"><?php echo h($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Titre</label>
                                <input type="text" name="name" class="form-control" required maxlength="255" value="<?php echo h($mission['name']); ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="4"><?php echo h($mission['description']); ?></textarea>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Date de début</label>
                                    <input type="date" name="start_date" class="form-control" value="<?php echo h($mission['start_date']); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Date de fin</label>
                                    <input type="date" name="end_date" class="form-control" value="<?php echo h($mission['end_date']); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Horodatage (datetime)</label>
                                    <input type="datetime-local" name="datetime" class="form-control" value="<?php echo dt_local($mission['datetime']); ?>">
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Départ</label>
                                    <input type="text" name="departure" class="form-control" maxlength="255" value="<?php echo h($mission['departure']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Arrivée</label>
                                    <input type="text" name="arrival" class="form-control" maxlength="255" value="<?php echo h($mission['arrival']); ?>">
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Chauffeur</label>
                                    <input type="text" name="driver" class="form-control" maxlength="255" value="<?php echo h($mission['driver']); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Véhicule</label>
                                    <input type="text" name="vehicle" class="form-control" maxlength="255" value="<?php echo h($mission['vehicle']); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Prix (€)</label>
                                    <input type="number" name="prix" step="0.01" class="form-control" value="<?php echo h($mission['prix']); ?>">
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                   <select name="status_id" id="status_id" class="form-select">
                                        <option value="">-- Choisir --</option>
                                        <?php foreach ($statuses as $s): ?>
                                            <option value="<?php echo intval($s['id']); ?>" <?php echo (intval($s['id']) === (int)($mission['status_id'] ?? 0) ? 'selected' : ''); ?>>
                                                <?php echo h($s['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Type</label>
                                    <input type="text" name="type" class="form-control" maxlength="50" value="<?php echo h($mission['type']); ?>">
                                </div>

                                <div class="col-md-6">
                                    <label for="assigned_to">Assigné à</label>
                                    <select name="assigned_to" id="assigned_to" class="form-control">
                                        <option value="">— Aucun —</option>
                                        <?php foreach ($users as $u): ?>
                                            <option value="<?php echo (int)$u['id']; ?>" <?php echo ((int)$u['id'] === (int)($mission['assigned_to'] ?? 0) ? 'selected' : ''); ?>>
                                                <?php echo h($u['username']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Projet</label>
                                    <input type="text" name="project" class="form-control" value="<?php echo h($mission['project']); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Produit</label>
                                    <input type="text" name="product" class="form-control" value="<?php echo h($mission['product']); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Quantité</label>
                                    <input type="number" name="quantity" class="form-control" value="<?php echo h($mission['quantity']); ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <small class="">Créé : <?php echo h($mission['created_at']); ?> — Dernière modification (serveur) : <?php echo h($mission['updated_at']); ?></small>
                                <div id="clientUpdatedAt" class="small text-muted"></div>
                            </div>

                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                            <a href="mission_view.php?id=<?php echo urlencode($mission['id']); ?>" class="btn btn-secondary">Annuler</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
// affichage temps réel côté client (heure locale)
function updateClientTime(){
    const el = document.getElementById('clientUpdatedAt');
    if(!el) return;
    const now = new Date();
    el.textContent = 'Heure locale : ' + now.toLocaleString();
}
updateClientTime();
setInterval(updateClientTime, 1000);
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>