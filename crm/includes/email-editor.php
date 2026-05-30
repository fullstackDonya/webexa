<?php
require_once '../includes/bootstrap.php';

$page_title = "Éditeur d'Email - CRM Intelligent";

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/editor.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Éditeur d'Email</h1>
        <form id="emailEditorForm">
            <div class="mb-3">
                <label for="templateSelect" class="form-label">Sélectionner un Modèle</label>
                <select class="form-select" id="templateSelect" required>
                    <option value="">Choisissez un modèle...</option>
                    <option value="default">Modèle par défaut</option>
                    <option value="newsletter">Newsletter</option>
                    <option value="promotional">Promotionnelle</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="emailSubject" class="form-label">Objet de l'Email</label>
                <input type="text" class="form-control" id="emailSubject" required>
            </div>
            <div class="mb-3">
                <label for="emailContent" class="form-label">Contenu de l'Email</label>
                <textarea class="form-control" id="emailContent" rows="10" required></textarea>
            </div>
            <div class="mb-3">
                <label for="logoUpload" class="form-label">Télécharger un Logo</label>
                <input type="file" class="form-control" id="logoUpload" accept="image/*">
            </div>
            <div class="mb-3">
                <button type="button" class="btn btn-primary" id="saveEmail">Enregistrer l'Email</button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/editor.js"></script>
</body>
</html>