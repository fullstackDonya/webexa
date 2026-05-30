<?php
// exécuter en CLI : php cron/send-scheduled-campaigns.php
chdir(dirname(__DIR__)); // se placer dans WEB/crm
require_once __DIR__ . '/../includes/verify_subscriptions.php'; // doit exposer $pdo
require_once __DIR__ . '/../includes/send_campaign.php';

try {
    // sélectionner les campagnes programmées prêtes à l'envoi
    $stmt = $pdo->prepare("SELECT * FROM campaigns WHERE status = 'scheduled' AND scheduled_at IS NOT NULL AND scheduled_at <= NOW() LIMIT 20");
    $stmt->execute();
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($list as $c) {
        // marque temporaire (optionnel) : éviter double envoi concurrents
        $lock = $pdo->prepare("UPDATE campaigns SET status = ? WHERE id = ? AND status = 'scheduled'");
        $lock->execute(['sending', $c['id']]);
        if ($lock->rowCount() === 0) continue; // déjà pris

        // envoyer
        $sent = send_campaign($pdo, $c);
        // log basique
        file_put_contents(__DIR__.'/send.log', date('c') . " - campaign {$c['id']} sent to: {$sent}\n", FILE_APPEND);
    }
} catch (Exception $e) {
    file_put_contents(__DIR__.'/send.log', date('c') . " - error: ".$e->getMessage()."\n", FILE_APPEND);
}