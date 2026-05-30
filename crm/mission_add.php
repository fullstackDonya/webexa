<?php
require_once 'includes/mission_add.php';

$page_title = "Ajouter une Mission - CRM Intelligent";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($page_title); ?></title>
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
                    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-tasks text-primary"></i> Nouvelle Mission</h1>
                    <a href="missions.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour à la liste</a>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card shadow">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">Informations de la Mission</h6>
                            </div>
                            <div class="card-body">
                                <form method="post" action="./includes/mission_add.php" id="mission-form" novalidate autocomplete="off">
                                    <input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['csrf_token'] ?? ''); ?>">

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="mission_name" class="form-label">Nom de la mission *</label>
                                            <input type="text" name="name" id="mission_name" class="form-control" required maxlength="255" value="">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="folder_id" class="form-label">Dossier</label>
                                            <select name="folder_id" id="folder_id" class="form-select">
                                                <option value="">Sélectionner un dossier</option>
                                                <?php foreach ($folders as $f): ?>
                                                    <option value="<?php echo intval($f['id']); ?>"><?php echo h($f['name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="start_date" class="form-label">Date de début</label>
                                            <input type="date" name="start_date" id="start_date" class="form-control" value="">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="end_date" class="form-label">Date de fin</label>
                                            <input type="date" name="end_date" id="end_date" class="form-control" value="">
                                        </div>

                                        <div class="col-md-6">
                                            <label for="status_id" class="form-label">Statut</label>
                                            <select name="status_id" id="status_id" class="form-select">
                                                <option value="">-- Choisir --</option>
                                                <?php foreach ($statuses as $s): ?>
                                                    <option value="<?php echo intval($s['id']); ?>"><?php echo h($s['name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="assigned_to" class="form-label">Assigné à</label>
                                            <select name="assigned_to" id="assigned_to" class="form-select">
                                                <option value="">-- Aucun --</option>
                                                <?php foreach ($users as $u): ?>
                                                    <option value="<?php echo intval($u['id']); ?>" <?php if(isset($assigned_to) && $assigned_to == $u['id']) echo 'selected'; ?>>
                                                        <?php echo h($u['username']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-12">
                                            <label for="description" class="form-label">Description</label>
                                            <textarea name="description" id="description" class="form-control" rows="4"></textarea>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="departure" class="form-label">Départ</label>
                                            <input type="text" name="departure" id="departure" class="form-control" maxlength="255" value="">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="arrival" class="form-label">Arrivée</label>
                                            <input type="text" name="arrival" id="arrival" class="form-control" maxlength="255" value="">
                                        </div>

                                        <div class="col-md-6">
                                            <label for="datetime" class="form-label">Horodatage</label>
                                            <input type="datetime-local" name="datetime" id="datetime" class="form-control" value="">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="driver" class="form-label">Chauffeur</label>
                                            <input type="text" name="driver" id="driver" class="form-control" maxlength="255" value="">
                                        </div>

                                        <div class="col-md-4">
                                            <label for="vehicle" class="form-label">Véhicule</label>
                                            <input type="text" name="vehicle" id="vehicle" class="form-control" maxlength="255" value="">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="prix" class="form-label">Prix (€)</label>
                                            <input type="number" name="prix" id="prix" class="form-control" step="0.01" value="0.00">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="quantity" class="form-label">Quantité</label>
                                            <input type="number" name="quantity" id="quantity" class="form-control" value="">
                                        </div>

                                        <div class="col-md-4">
                                            <label for="type" class="form-label">Type</label>
                                            <input type="text" name="type" id="type" class="form-control" maxlength="50" value="vtc">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="project" class="form-label">Projet</label>
                                            <input type="text" name="project" id="project" class="form-control" maxlength="255" value="">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="product" class="form-label">Produit</label>
                                            <input type="text" name="product" id="product" class="form-control" maxlength="255" value="">
                                        </div>

                                        <div class="mt-4 text-end">
                                            <button type="reset" class="btn btn-secondary me-2">Annuler</button>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Enregistrer
                                            </button>
                                        </div>

                                    </div>

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
                                <h6>Conseils pour ajouter une mission :</h6>
                                <ul class="small">
                                    <li>Le nom de la mission est obligatoire</li>
                                    <li>Associez la mission à un dossier si possible</li>
                                    <li>Indiquez les dates de début et de fin si connues</li>
                                    <li>Choisissez le bon statut selon l'avancement</li>
                                    <li>Ajoutez une description pour plus de détails</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

<script>
// validation minimale côté client
document.getElementById('mission-form').addEventListener('submit', function(e){
    var name = document.getElementById('mission_name').value.trim();
    if(!name){
        e.preventDefault();
        alert('Le nom de la mission est requis.');
        document.getElementById('mission_name').focus();
        return false;
    }
    return true;
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
