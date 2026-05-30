<?php
require_once __DIR__ . '/includes/verify_subscriptions.php';
require_once __DIR__ . '/config/database.php';

$customer_id = $_SESSION['customer_id'] ?? null;
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    // handle save
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $trigger = trim($_POST['trigger_type'] ?? '');
    $audience = trim($_POST['audience_filter'] ?? '');
    if($id <= 0 || !$name){
        header('Location: campaigns-automation.php?msg=Parametres+manquants'); exit;
    }
    try{
        // ensure ownership
        $check = $pdo->prepare('SELECT customer_id FROM automations WHERE id = ?');
        $check->execute([$id]);
        $row = $check->fetch();
        if($row && !empty($customer_id) && $row['customer_id'] != $customer_id){
            header('HTTP/1.1 403 Forbidden'); echo 'Accès refusé'; exit;
        }
        $u = $pdo->prepare('UPDATE automations SET name = ?, type = ?, trigger_type = ?, audience_filter = ?, updated_at = NOW() WHERE id = ?');
        $u->execute([$name,$type,$trigger,$audience,$id]);
        header('Location: campaigns-automation.php?msg=Automatisation+mise+à+jour'); exit;
    }catch(Exception $e){ error_log('automations-edit POST error: '.$e->getMessage()); header('Location: campaigns-automation.php?msg=Erreur'); exit; }
}

// GET: render simple edit form
try{
    if($id <= 0) { header('Location: campaigns-automation.php'); exit; }
    $stmt = $pdo->prepare('SELECT * FROM automations WHERE id = ?');
    $stmt->execute([$id]);
    $automation = $stmt->fetch();
    if(!$automation){ header('Location: campaigns-automation.php?msg=Introuvable'); exit; }
    if(!empty($customer_id) && $automation['customer_id'] != $customer_id){ header('HTTP/1.1 403 Forbidden'); echo 'Accès refusé'; exit; }
}catch(Exception $e){ error_log('automations-edit GET error: '.$e->getMessage()); header('Location: campaigns-automation.php?msg=Erreur'); exit; }
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Modifier l'automatisation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <a href="campaigns-automation.php" class="btn btn-link mb-3">← Retour</a>
    <div class="card">
        <div class="card-header">Modifier</div>
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="id" value="<?php echo intval($automation['id']); ?>">
                <div class="mb-3">
                    <label class="form-label">Nom</label>
                    <input class="form-control" name="name" value="<?php echo htmlspecialchars($automation['name']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Type</label>
                    <input class="form-control" name="type" value="<?php echo htmlspecialchars($automation['type']); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Déclencheur</label>
                    <input class="form-control" name="trigger_type" value="<?php echo htmlspecialchars($automation['trigger_type']); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Audience</label>
                    <input class="form-control" name="audience_filter" value="<?php echo htmlspecialchars($automation['audience_filter'] ?? ''); ?>">
                </div>
                <button class="btn btn-primary">Enregistrer</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
