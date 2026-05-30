<?php

require_once __DIR__.'/includes/bootstrap.php'; // charge $pdo, sessions si besoin

$cid = intval($_GET['cid'] ?? 0);
$tok = $_GET['tok'] ?? null; // token optionnel pour identifier destinataire
if(!$cid){ // renvoyer pixel quand même
    header('Content-Type: image/gif');
    echo base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==');
    exit;
}

try {
    // insert événement (optionnel) : table campaign_events si tu veux historique
    // incrémente opens_count et recalcule open_rate
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('UPDATE campaigns SET opens_count = opens_count + 1 WHERE id = ?');
    $stmt->execute([$cid]);

    $stmt = $pdo->prepare('UPDATE campaigns c
        SET open_rate = ROUND(100 * c.opens_count / GREATEST(c.recipients_count,1),2)
        WHERE id = ?');
    $stmt->execute([$cid]);
    $pdo->commit();
} catch (Exception $e){
    if($pdo->inTransaction()) $pdo->rollBack();
    // ignore silently
}

// Retourne un 1x1 transparent
header('Content-Type: image/gif');
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==');
exit;