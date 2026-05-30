<?php

// Include campaign config helper
require_once __DIR__ . '/campaign-config-helper.php';

// helper pour envoyer une campagne (basique, remplacer par PHPMailer/SMTP en prod)
function ensureColumnIfMissing(PDO $pdo, string $table, string $column, string $definition) {
    $sql = "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$table, $column]);
    if ((int)$stmt->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN {$definition}");
    }
}

function send_campaign(PDO $pdo, array $campaign) {
    // ensure sent_at exists
    ensureColumnIfMissing($pdo, 'campaigns', 'sent_at', "sent_at DATETIME NULL");
    
    $customer_id = $campaign['customer_id'] ?? null;
    $channel = $campaign['channel'] ?? 'email';
    $email_config_id = $campaign['email_config_id'] ?? null;
    $whatsapp_config_id = $campaign['whatsapp_config_id'] ?? null;

    // Récupérer automatiquement l'expéditeur depuis la configuration
    $senderInfo = get_sender_from_config($pdo, $customer_id, $channel, 
        $channel === 'email' ? $email_config_id : $whatsapp_config_id);
    
    // Utiliser l'expéditeur de la configuration ou celui stocké dans la campagne
    $fromName = $senderInfo['sender_name'] ?: ($campaign['sender_name'] ?? 'No Reply');
    $fromEmail = $senderInfo['sender_email'] ?: ($campaign['sender_email'] ?? 'noreply@localhost');

    $recipients = [];
    if (!empty($campaign['recipients_emails'])) {
        $r = $campaign['recipients_emails'];
        // support JSON array or comma/newline list
        $decoded = json_decode($r, true);
        if (is_array($decoded)) $recipients = $decoded;
        else $recipients = preg_split('/[\r\n,;]+/', $r);
    }

    // fallback: if no stored list, try building from audience (best-effort)
    if (empty($recipients)) {
        // simple fallback for common audiences
        $cid = $campaign['customer_id'] ?? null;
        if ($campaign['audience'] === 'all' && $cid) {
            $stmt = $pdo->prepare("SELECT email FROM contacts WHERE customer_id = ? AND email <> ''");
            $stmt->execute([$cid]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) if (!empty($r['email'])) $recipients[] = $r['email'];
        }
    }

    // normalisation & dedupe
    $recipients = array_values(array_unique(array_filter(array_map('trim', $recipients))));
    if (empty($recipients)) return false;

    // message (simple). Replace with real template rendering.
    $subject = $campaign['subject'] ?? $campaign['name'] ?? 'Campagne';
    
    // Si c'est un envoi WhatsApp, utiliser une logique différente
    if ($channel === 'whatsapp' && $whatsapp_config_id) {
        return send_whatsapp_campaign($pdo, $campaign, $recipients, $whatsapp_config_id);
    }
    
    // Envoi par email
    if ($channel === 'email') {
        // Si une configuration email est spécifiée, utiliser ses paramètres SMTP
        if ($email_config_id) {
            $config = get_email_config_by_id($pdo, $email_config_id, $customer_id);
            if ($config) {
                // TODO: Implémenter l'envoi via SMTP avec les paramètres de la configuration
                // Pour l'instant, on utilise l'envoi PHP mail() basique
                $fromEmail = $config['email'];
            }
        }
        
        $bodyHtml = "<html><body><h3>".htmlspecialchars($campaign['name'])."</h3><p>Contenu de la campagne...</p></body></html>";
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: ".addslashes($fromName)." <".addslashes($fromEmail).">\r\n";

        // send (basic). For beaucoup de destinataires, remplacer par envoi via SMTP / queue.
        $sent = 0;
        foreach ($recipients as $to) {
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) continue;
            // throttle minimal
            @mail($to, $subject, $bodyHtml, $headers);
            $sent++;
            // small sleep to avoid hammering
            usleep(20000);
        }

        // update campaign state
        $upd = $pdo->prepare("UPDATE campaigns SET status = ?, recipients = ?, recipients_count = ?, sent_at = ?, opens_count = COALESCE(opens_count,0), clicks_count = COALESCE(clicks_count,0) WHERE id = ?");
        $upd->execute(['sent', count($recipients), count($recipients), date('Y-m-d H:i:s'), intval($campaign['id'])]);

        return $sent;
    }
    
    return false;
}

/**
 * Fonction pour envoyer une campagne WhatsApp
 */
function send_whatsapp_campaign(PDO $pdo, array $campaign, array $recipients, int $config_id) {
    // TODO: Implémenter l'envoi via WhatsApp Business API
    // Pour l'instant, on retourne false
    error_log("WhatsApp campaign sending not yet implemented for campaign ID: " . $campaign['id']);
    return false;
}