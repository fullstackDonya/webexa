<?php
/**
 * Email Integration Helper
 * Gère la connexion et synchronisation avec les serveurs email IMAP/SMTP
 */

class EmailIntegration {
    private $pdo;
    private $config;
    private $imap_stream;

    public function __construct($pdo, $config) {
        $this->pdo = $pdo;
        $this->config = $config;
    }

    /**
     * Connecter au serveur IMAP
     */
    public function connectImap() {
        try {
            $password = $this->decryptPassword($this->config['password']);
            
            $imap_url = "{{$this->config['imap_server']}:{$this->config['imap_port']}/imap/ssl/novalidate-cert}";
            
            $this->imap_stream = @imap_open($imap_url, $this->config['email'], $password);
            
            if (!$this->imap_stream) {
                throw new Exception("Impossible de se connecter: " . imap_last_error());
            }
            
            return true;
        } catch (Exception $e) {
            error_log("IMAP Connection Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer les emails non lus
     */
    public function fetchUnreadEmails() {
        if (!$this->imap_stream) {
            return [];
        }

        try {
            $emails = [];
            $mailboxes = imap_list($this->imap_stream, "{{$this->config['imap_server']}}", "*");
            
            if (!is_array($mailboxes)) {
                return [];
            }

            foreach ($mailboxes as $mailbox) {
                $num_messages = imap_num_msg($this->imap_stream);
                
                for ($i = $num_messages; $i > max(1, $num_messages - 100); $i--) {
                    $header = imap_header($this->imap_stream, $i);
                    $structure = imap_fetchstructure($this->imap_stream, $i);
                    
                    if (isset($header->unseen) && $header->unseen == 'U') {
                        $emails[] = [
                            'email_id' => $header->Uid,
                            'from' => $header->from[0]->mailbox . '@' . $header->from[0]->host,
                            'from_name' => $header->from[0]->personal ?? '',
                            'to' => isset($header->to) ? $header->to[0]->mailbox . '@' . $header->to[0]->host : '',
                            'subject' => $header->subject,
                            'date' => date('Y-m-d H:i:s', strtotime($header->date)),
                            'message_id' => $i,
                            'has_attachments' => $this->hasAttachments($structure)
                        ];
                    }
                }
            }
            
            return $emails;
        } catch (Exception $e) {
            error_log("Fetch Emails Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Vérifier si un email a des pièces jointes
     */
    private function hasAttachments($structure) {
        if (!isset($structure->parts)) {
            return false;
        }
        
        foreach ($structure->parts as $part) {
            if ($part->ifdisposition && strtolower($part->disposition) == 'attachment') {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Extraire les leads depuis les emails
     */
    public function extractLeads($customer_id, $config_id) {
        if (!$this->imap_stream) {
            return ['success' => false, 'message' => 'Not connected'];
        }

        try {
            $emails = $this->fetchUnreadEmails();
            $leads_extracted = 0;

            foreach ($emails as $email) {
                // Vérifier si c'est un email valide
                if (filter_var($email['from'], FILTER_VALIDATE_EMAIL)) {
                    // Insérer ou mettre à jour le lead
                    $stmt = $this->pdo->prepare("
                        INSERT IGNORE INTO leads (
                            customer_id, 
                            email, 
                            name, 
                            stage, 
                            source,
                            created_at
                        ) VALUES (?, ?, ?, 'new', 'email', NOW())
                    ");
                    
                    $stmt->execute([
                        $customer_id,
                        $email['from'],
                        $email['from_name'] ?? 'Contact'
                    ]);

                    $leads_extracted += $stmt->rowCount();

                    // Enregistrer l'email synchronisé
                    $stmt = $this->pdo->prepare("
                        INSERT INTO emails (
                            customer_id,
                            config_id,
                            email_id,
                            from_address,
                            from_name,
                            to_address,
                            subject,
                            email_date,
                            has_attachments
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $customer_id,
                        $config_id,
                        $email['email_id'],
                        $email['from'],
                        $email['from_name'],
                        $email['to'],
                        $email['subject'],
                        $email['date'],
                        $email['has_attachments'] ? 1 : 0
                    ]);
                }
            }

            // Mettre à jour la date de sync
            $stmt = $this->pdo->prepare("
                UPDATE email_configurations 
                SET last_sync = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$config_id]);

            return [
                'success' => true,
                'leads_extracted' => $leads_extracted,
                'emails_synced' => count($emails)
            ];
        } catch (Exception $e) {
            error_log("Extract Leads Error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Envoyer un email via SMTP
     */
    public function sendEmail($to, $subject, $body, $from = null) {
        try {
            $from = $from ?? $this->config['email'];
            $password = $this->decryptPassword($this->config['password']);

            // Utiliser PHPMailer si disponible
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                return $this->sendWithPHPMailer($to, $subject, $body, $from, $password);
            }

            // Fallback: utiliser mail() de PHP
            $headers = "From: " . $from . "\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

            return mail($to, $subject, $body, $headers);
        } catch (Exception $e) {
            error_log("Send Email Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoyer avec PHPMailer
     */
    private function sendWithPHPMailer($to, $subject, $body, $from, $password) {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $this->config['smtp_server'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['email'];
            $mail->Password = $password;
            $mail->SMTPSecure = 'tls';
            $mail->Port = $this->config['smtp_port'];

            $mail->setFrom($from);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;

            return $mail->send();
        } catch (Exception $e) {
            error_log("PHPMailer Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Déchiffrer le mot de passe
     */
    private function decryptPassword($encrypted) {
        try {
            $encryption_key = $_ENV['ENCRYPTION_KEY'] ?? 'default-key-change-me';
            $iv = substr(hash('sha256', $this->config['email']), 0, 16);
            return openssl_decrypt($encrypted, 'AES-256-CBC', $encryption_key, 0, $iv);
        } catch (Exception $e) {
            error_log("Decrypt Error: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Fermer la connexion IMAP
     */
    public function closeImap() {
        if ($this->imap_stream) {
            imap_close($this->imap_stream);
        }
    }

    /**
     * Destructeur
     */
    public function __destruct() {
        $this->closeImap();
    }
}