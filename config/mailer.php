<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

// Charger .env : d'abord dans `config/`, sinon fallback vers la racine
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
} else {
    $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
}
$dotenv->safeLoad();

/**
 * Crée et configure une instance PHPMailer avec les paramètres SMTP.
 */
function createMailer(): PHPMailer {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'] ?? 'smtp.ionos.fr';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USER'] ?? 'contact@webexa.fr';
        $mail->Password   = $_ENV['SMTP_PASS'] ?? '*Saas-creator-w*2026%';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = (int)($_ENV['SMTP_PORT'] ?? 465);
        $mail->CharSet    = 'UTF-8';
        
        // Mode debug en développement
        if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'development') {
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        }
        
        $mail->setFrom(
            $_ENV['SMTP_FROM_EMAIL'] ?? 'noreply@webexa.fr',
            $_ENV['SMTP_FROM_NAME'] ?? 'Webexa'
        );
        
        // Ajouter un Reply-To pour permettre les réponses
        $mail->addReplyTo(
            $_ENV['SMTP_REPLY_TO'] ?? 'contact@webexa.fr',
            $_ENV['SMTP_REPLY_TO_NAME'] ?? 'Support Webexa'
        );
        
        error_log('[MAILER] Configuration réussie - Host: ' . $mail->Host . ', Port: ' . $mail->Port);
    } catch (Exception $e) {
        error_log('[MAILER] Erreur de configuration: ' . $e->getMessage());
    }
    
    return $mail;
}

/**
 * Envoie un email avec gestion d'erreurs robuste
 */
function sendEmail($to, $subject, $body, $altBody = '') {
    try {
        $mail = createMailer();
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        if ($altBody) {
            $mail->AltBody = $altBody;
        }
        
        $result = $mail->send();
        error_log('[MAILER] Email envoyé avec succès à ' . $to . ' - Sujet: ' . $subject);
        return $result;
    } catch (Exception $e) {
        error_log('[MAILER] Échec envoi email à ' . $to . ' - Erreur: ' . $e->getMessage());
        return false;
    }
}
