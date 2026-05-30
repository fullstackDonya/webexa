<?php

require_once __DIR__.'/includes/bootstrap.php';

$cid = intval($_GET['cid'] ?? 0);
$tok = $_GET['tok'] ?? null;
$dest = $_GET['url'] ?? '/';

if($cid){
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('UPDATE campaigns SET clicks_count = clicks_count + 1 WHERE id = ?');
        $stmt->execute([$cid]);

        $stmt = $pdo->prepare('UPDATE campaigns c
            SET click_rate = ROUND(100 * c.clicks_count / GREATEST(c.recipients_count,1),2)
            WHERE id = ?');
        $stmt->execute([$cid]);
        $pdo->commit();
    } catch (Exception $e){
        if($pdo->inTransaction()) $pdo->rollBack();
    }
}
// sécurité: whitelist ou nettoyage de $dest
$dest = filter_var($dest, FILTER_SANITIZE_URL);
header('Location: ' . $dest);
exit;