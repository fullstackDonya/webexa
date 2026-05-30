<?php
/**
 * Système d'envoi de campagnes par email et WhatsApp
 * Support programmation automatique
 */

require_once __DIR__ . '/verify_subscriptions.php';
require_once __DIR__ . '/ai_scoring.php';

// Configuration des services
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'localhost');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('SMTP_FROM', getenv('SMTP_FROM') ?: 'noreply@crm.com');

define('WHATSAPP_API_KEY', getenv('WHATSAPP_API_KEY') ?: '');
define('WHATSAPP_PHONE_ID', getenv('WHATSAPP_PHONE_ID') ?: '');

/**
 * Envoyer une campagne par email
 */
function send_campaign_email($pdo, $campaign_id, $customer_id, $lead_ids = null) {
    try {
        // Récupérer la campagne
        $stmt = $pdo->prepare("SELECT * FROM campaigns WHERE id = ? AND customer_id = ?");
        $stmt->execute([$campaign_id, $customer_id]);
        $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$campaign) {
            return ['success' => false, 'message' => 'Campagne non trouvée'];
        }
        
        // Récupérer les leads cibles
        if ($lead_ids && is_array($lead_ids)) {
            $placeholders = implode(',', array_fill(0, count($lead_ids), '?'));
            $stmt = $pdo->prepare("
                SELECT id, first_name, last_name, email FROM leads 
                WHERE id IN ($placeholders) AND (customer_id = ? OR assigned_to = ?)
            ");
            $params = array_merge($lead_ids, [$customer_id, $_SESSION['user_id'] ?? null]);
            $stmt->execute($params);
        } else {
            // Envoyer à tous les leads du customer
            $stmt = $pdo->prepare("
                SELECT id, first_name, last_name, email FROM leads 
                WHERE customer_id = ? AND email IS NOT NULL
            ");
            $stmt->execute([$customer_id]);
        }
        
        $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $sent_count = 0;
        $failed_count = 0;
        $errors = [];
        
        foreach ($leads as $lead) {
            try {
                // Personnaliser le contenu
                $subject = str_replace(
                    ['{FIRST_NAME}', '{LAST_NAME}', '{EMAIL}'],
                    [$lead['first_name'], $lead['last_name'], $lead['email']],
                    $campaign['subject'] ?? 'Nouvelle offre'
                );
                
                $body = str_replace(
                    ['{FIRST_NAME}', '{LAST_NAME}', '{EMAIL}'],
                    [$lead['first_name'], $lead['last_name'], $lead['email']],
                    $campaign['content'] ?? ''
                );
                
                // Envoyer l'email
                if (send_email($lead['email'], $subject, $body)) {
                    $sent_count++;
                    
                    // Enregistrer dans l'historique
                    $stmt = $pdo->prepare("
                        INSERT INTO campaign_sends (campaign_id, lead_id, channel, sent_at, status)
                        VALUES (?, ?, 'email', NOW(), 'sent')
                    ");
                    $stmt->execute([$campaign_id, $lead['id']]);
                } else {
                    $failed_count++;
                    $errors[] = "Email {$lead['email']}: Erreur d'envoi";
                }
            } catch (Exception $e) {
                $failed_count++;
                $errors[] = "Lead {$lead['id']}: " . $e->getMessage();
            }
        }
        
        // Mettre à jour le statut de la campagne
        $stmt = $pdo->prepare("
            UPDATE campaigns 
            SET status = 'sent', sent_at = NOW(), sent_count = ?
            WHERE id = ?
        ");
        $stmt->execute([$sent_count, $campaign_id]);
        
        error_log("Campaign $campaign_id email sent: $sent_count success, $failed_count failed");
        
        return [
            'success' => $failed_count === 0,
            'sent' => $sent_count,
            'failed' => $failed_count,
            'errors' => $errors,
            'message' => "$sent_count emails envoyés" . ($failed_count > 0 ? ", $failed_count échoués" : '')
        ];
    } catch (Exception $e) {
        error_log("Error sending campaign: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Envoyer une campagne par WhatsApp
 */
function send_campaign_whatsapp($pdo, $campaign_id, $customer_id, $lead_ids = null) {
    try {
        if (!WHATSAPP_API_KEY || !WHATSAPP_PHONE_ID) {
            return ['success' => false, 'message' => 'WhatsApp non configuré'];
        }
        
        // Récupérer la campagne
        $stmt = $pdo->prepare("SELECT * FROM campaigns WHERE id = ? AND customer_id = ?");
        $stmt->execute([$campaign_id, $customer_id]);
        $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$campaign) {
            return ['success' => false, 'message' => 'Campagne non trouvée'];
        }
        
        // Récupérer les leads avec téléphone
        if ($lead_ids && is_array($lead_ids)) {
            $placeholders = implode(',', array_fill(0, count($lead_ids), '?'));
            $stmt = $pdo->prepare("
                SELECT id, first_name, last_name, phone FROM leads 
                WHERE id IN ($placeholders) AND (customer_id = ? OR assigned_to = ?) AND phone IS NOT NULL
            ");
            $params = array_merge($lead_ids, [$customer_id, $_SESSION['user_id'] ?? null]);
            $stmt->execute($params);
        } else {
            $stmt = $pdo->prepare("
                SELECT id, first_name, last_name, phone FROM leads 
                WHERE customer_id = ? AND phone IS NOT NULL
            ");
            $stmt->execute([$customer_id]);
        }
        
        $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $sent_count = 0;
        $failed_count = 0;
        $errors = [];
        
        foreach ($leads as $lead) {
            try {
                // Personnaliser le message
                $message = str_replace(
                    ['{FIRST_NAME}', '{LAST_NAME}'],
                    [$lead['first_name'], $lead['last_name']],
                    $campaign['content'] ?? ''
                );
                
                // Envoyer via WhatsApp API
                if (send_whatsapp($lead['phone'], $message)) {
                    $sent_count++;
                    
                    // Enregistrer dans l'historique
                    $stmt = $pdo->prepare("
                        INSERT INTO campaign_sends (campaign_id, lead_id, channel, sent_at, status)
                        VALUES (?, ?, 'whatsapp', NOW(), 'sent')
                    ");
                    $stmt->execute([$campaign_id, $lead['id']]);
                } else {
                    $failed_count++;
                    $errors[] = "Phone {$lead['phone']}: Erreur d'envoi";
                }
            } catch (Exception $e) {
                $failed_count++;
                $errors[] = "Lead {$lead['id']}: " . $e->getMessage();
            }
        }
        
        // Mettre à jour le statut
        $stmt = $pdo->prepare("
            UPDATE campaigns 
            SET status = 'sent', sent_at = NOW(), sent_count = ?
            WHERE id = ?
        ");
        $stmt->execute([$sent_count, $campaign_id]);
        
        error_log("Campaign $campaign_id WhatsApp sent: $sent_count success, $failed_count failed");
        
        return [
            'success' => $failed_count === 0,
            'sent' => $sent_count,
            'failed' => $failed_count,
            'errors' => $errors,
            'message' => "$sent_count messages WhatsApp envoyés" . ($failed_count > 0 ? ", $failed_count échoués" : '')
        ];
    } catch (Exception $e) {
        error_log("Error sending WhatsApp campaign: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Programmer une campagne pour envoi automatique
 */
function schedule_campaign($pdo, $campaign_id, $customer_id, $scheduled_at, $channel = 'email') {
    try {
        $stmt = $pdo->prepare("
            UPDATE campaigns 
            SET status = 'scheduled', scheduled_at = ?, channel = ?
            WHERE id = ? AND customer_id = ?
        ");
        $stmt->execute([$scheduled_at, $channel, $campaign_id, $customer_id]);
        
        error_log("Campaign $campaign_id scheduled for $scheduled_at via $channel");
        
        return ['success' => true, 'message' => 'Campagne programmée'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Envoyer un email simple (utilitaire)
 */
function send_email($to, $subject, $body) {
    try {
        // Utiliser PHPMailer ou mail() selon la configuration
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . SMTP_FROM . "\r\n";
        
        // Pour production, utiliser PHPMailer
        $result = mail($to, $subject, $body, $headers);
        
        if (!$result) {
            error_log("Email failed to $to");
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("Email error: " . $e->getMessage());
        return false;
    }
}

/**
 * Envoyer un message WhatsApp (via API)
 */
function send_whatsapp($phone, $message) {
    try {
        if (!WHATSAPP_API_KEY) {
            return false;
        }
        
        // Formatter le numéro (enlever espaces, caractères spéciaux)
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // API WhatsApp Business
        $url = "https://graph.instagram.com/v18.0/" . WHATSAPP_PHONE_ID . "/messages";
        
        $data = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $phone,
            'type' => 'text',
            'text' => ['body' => $message]
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . WHATSAPP_API_KEY,
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if ($http_code === 200 && isset($result['messages'][0]['id'])) {
            error_log("WhatsApp sent to $phone");
            return true;
        } else {
            error_log("WhatsApp error to $phone: " . json_encode($result));
            return false;
        }
    } catch (Exception $e) {
        error_log("WhatsApp send error: " . $e->getMessage());
        return false;
    }
}

/**
 * Traiter les campagnes programmées (à appeler via cron)
 */
function process_scheduled_campaigns($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM campaigns 
            WHERE status = 'scheduled' AND scheduled_at <= NOW()
            LIMIT 10
        ");
        $stmt->execute();
        $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $processed = 0;
        foreach ($campaigns as $campaign) {
            $channel = $campaign['channel'] ?? 'email';
            
            if ($channel === 'whatsapp') {
                send_campaign_whatsapp($pdo, $campaign['id'], $campaign['customer_id']);
            } else {
                send_campaign_email($pdo, $campaign['id'], $campaign['customer_id']);
            }
            
            $processed++;
        }
        
        error_log("Processed $processed scheduled campaigns");
        
        return $processed;
    } catch (Exception $e) {
        error_log("Error processing scheduled campaigns: " . $e->getMessage());
        return 0;
    }
}

?>
