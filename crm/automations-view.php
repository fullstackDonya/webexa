<?php
require_once __DIR__ . '/includes/verify_subscriptions.php';
require_once __DIR__ . '/config/database.php';

$customer_id = $_SESSION['customer_id'] ?? null;
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if($id <= 0){
    header('Location: campaigns-automation.php');
    exit;
}

// fetch automation
try{
    $stmt = $pdo->prepare('SELECT * FROM automations WHERE id = ?');
    $stmt->execute([$id]);
    $automation = $stmt->fetch();
    if(!$automation){
        header('Location: campaigns-automation.php?msg=Automatisation+introuvable');
        exit;
    }
    // tenant check
    if(!empty($customer_id) && (!isset($automation['customer_id']) || $automation['customer_id'] != $customer_id)){
        header('HTTP/1.1 403 Forbidden');
        echo 'Accès refusé';
        exit;
    }
    // fetch steps if table exists
    $steps = [];
    try{
        $s = $pdo->prepare('SELECT * FROM automation_steps WHERE automation_id = ? ORDER BY `order_index` ASC');
        $s->execute([$id]);
        $steps = $s->fetchAll();
    }catch(Exception $e){ /* ignore if table missing */ }
    // fetch recent logs
    $logs = [];
    try{
        $l = $pdo->prepare('SELECT * FROM automation_logs WHERE automation_id = ? ORDER BY created_at DESC LIMIT 50');
        $l->execute([$id]);
        $logs = $l->fetchAll();
    }catch(Exception $e){ /* ignore */ }
}catch(Exception $e){
    error_log('automations-view error: '.$e->getMessage());
    header('Location: campaigns-automation.php?msg=Erreur+serveur');
    exit;
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Automatisation — <?php echo htmlspecialchars($automation['name'] ?? ''); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <a href="campaigns-automation.php" class="btn btn-link mb-3">← Retour aux automatisations</a>
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h2><?php echo htmlspecialchars($automation['name']); ?></h2>
            <div class="text-muted">Type: <?php echo htmlspecialchars($automation['type'] ?? ''); ?> — Déclencheur: <?php echo htmlspecialchars($automation['trigger_type'] ?? ''); ?></div>
        </div>
        <div>
            <a class="btn btn-outline-secondary" href="automations-edit.php?id=<?php echo intval($automation['id']); ?>"><i class="fas fa-edit"></i> Modifier</a>
            <?php if(($automation['status'] ?? '') === 'active'): ?>
                <a class="btn btn-outline-danger" href="automations-pause.php?id=<?php echo intval($automation['id']); ?>">Suspendre</a>
            <?php else: ?>
                <a class="btn btn-outline-success" href="automations-toggle.php?id=<?php echo intval($automation['id']); ?>">Reprendre</a>
            <?php endif; ?>
            <a class="btn btn-outline-danger" href="automations-delete.php?id=<?php echo intval($automation['id']); ?>" onclick="return confirm('Supprimer cette automatisation ?')">Supprimer</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-7">
            <div class="card mb-3">
                <div class="card-header">Détails</div>
                <div class="card-body">
                    <p><strong>Nom :</strong> <?php echo htmlspecialchars($automation['name']); ?></p>
                    <p><strong>Statut :</strong> <?php echo htmlspecialchars($automation['status']); ?></p>
                    <p><strong>Audience :</strong> <?php echo htmlspecialchars($automation['audience_filter'] ?? 'Tous'); ?></p>
                    <p><strong>Créée le :</strong> <?php echo htmlspecialchars($automation['created_at'] ?? '—'); ?></p>
                    <p><strong>Dernière mise à jour :</strong> <?php echo htmlspecialchars($automation['updated_at'] ?? '—'); ?></p>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Étapes</div>
                <div class="card-body">
                    <?php if(empty($steps)): ?>
                        <div class="text-muted">Aucune étape définie.</div>
                    <?php else: ?>
                        <ol>
                            <?php foreach($steps as $st): ?>
                                <li>
                                    <strong><?php echo htmlspecialchars($st['type'] ?? ''); ?></strong>
                                    <div class="small text-muted"><?php echo htmlspecialchars($st['meta'] ?? ''); ?></div>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card mb-3">
                <div class="card-header">Logs récents</div>
                <div class="card-body p-0">
                    <?php if(empty($logs)): ?>
                        <div class="p-3 text-muted">Aucun log disponible.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead><tr><th>Heure</th><th>Niveau</th><th>Message</th></tr></thead>
                                <tbody>
                                <?php foreach($logs as $lg): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($lg['created_at'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($lg['level'] ?? 'info'); ?></td>
                                        <td><?php echo htmlspecialchars($lg['message'] ?? ''); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
