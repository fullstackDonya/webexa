<?php
/**
 * EmailSyncManager - Gestion complète de la synchronisation des emails
 * Synchronise les emails entrants via IMAP et OAuth2 (Gmail/Outlook)
 */

require_once __DIR__ . '/EmailCrypto.php';

class EmailSyncManager {
    private $pdo;
    private $crypto;
    private $config;
    private $imapStream = null;
    
    public function __construct($pdo, $configId) {
        $this->pdo = $pdo;
        $this->crypto = new EmailCrypto();
        
        // Charger la configuration
        $stmt = $pdo->prepare("SELECT * FROM email_configurations WHERE id = ? AND is_active = 1");
        $stmt->execute([$configId]);
        $this->config = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$this->config) {
            throw new Exception("Configuration email non trouvée ou inactive: $configId");
        }
    }
    
    /**
     * Synchroniser tous les emails depuis le serveur
     */
    public function syncAllEmails($limit = 100) {
        try {
            // Connexion IMAP
            if (!$this->connectImap()) {
                throw new Exception("Impossible de se connecter au serveur IMAP");
            }
            
            $stats = [
                'success' => true,
                'emails_synced' => 0,
                'emails_new' => 0,
                'emails_updated' => 0,
                'errors' => []
            ];
            
            // Récupérer les emails
            $emails = $this->fetchEmails($limit);
            
            foreach ($emails as $email) {
                try {
                    $result = $this->saveEmail($email);
                    $stats['emails_synced']++;
                    if ($result === 'new') {
                        $stats['emails_new']++;
                    } elseif ($result === 'updated') {
                        $stats['emails_updated']++;
                    }
                } catch (Exception $e) {
                    $stats['errors'][] = [
                        'email_id' => $email['message_id'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            // Mettre à jour la date de dernière sync
            $this->updateLastSync();
            
            return $stats;
            
        } catch (Exception $e) {
            $this->logError($e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'emails_synced' => 0
            ];
        } finally {
            $this->disconnect();
        }
    }
    
    /**
     * Connecter au serveur IMAP
     */
    private function connectImap() {
        try {
            // Vérifier que les paramètres IMAP sont configurés
            if (empty($this->config['imap_server'])) {
                throw new Exception("Serveur IMAP non configuré pour {$this->config['email']}");
            }
            
            if (empty($this->config['password'])) {
                throw new Exception("Mot de passe non configuré pour {$this->config['email']}");
            }
            
            // Déchiffrer le mot de passe
            try {
                $password = $this->crypto->decrypt($this->config['password']);
            } catch (Exception $e) {
                throw new Exception("Impossible de déchiffrer le mot de passe: " . $e->getMessage());
            }
            
            // Construire l'URL IMAP
            $flags = '/imap/ssl';
            if ($this->config['provider'] === 'gmail') {
                $flags .= '/novalidate-cert';
            }
            
            $imapPort = $this->config['imap_port'] ?? 993;
            
            $mailbox = sprintf(
                "{%s:%d%s}INBOX",
                $this->config['imap_server'],
                $imapPort,
                $flags
            );
            
            error_log("Attempting IMAP connection to: $mailbox with user: {$this->config['email']}");
            
            // Se connecter
            $this->imapStream = @imap_open(
                $mailbox,
                $this->config['email'],
                $password,
                0,
                1,
                ['DISABLE_AUTHENTICATOR' => 'GSSAPI']
            );
            
            if (!$this->imapStream) {
                $error = imap_last_error();
                error_log("IMAP connection failed: $error");
                throw new Exception("Connexion IMAP échouée: $error. Vérifiez vos identifiants et paramètres IMAP.");
            }
            
            error_log("IMAP connection successful for {$this->config['email']}");
            return true;
            
        } catch (Exception $e) {
            error_log("IMAP Connection Error for {$this->config['email']}: " . $e->getMessage());
            throw $e; // Re-lancer l'exception au lieu de retourner false
        }
    }
    
    /**
     * Récupérer les emails du serveur
     */
    private function fetchEmails($limit = 100) {
        if (!$this->imapStream) {
            return [];
        }
        
        $emails = [];
        
        try {
            // Nombre total de messages
            $messageCount = imap_num_msg($this->imapStream);
            
            if ($messageCount === 0) {
                return [];
            }
            
            // Déterminer la plage de messages à récupérer
            $start = max(1, $messageCount - $limit + 1);
            $end = $messageCount;
            
            // Récupérer les messages (du plus récent au plus ancien)
            for ($i = $end; $i >= $start; $i--) {
                try {
                    $email = $this->fetchEmailDetails($i);
                    if ($email) {
                        $emails[] = $email;
                    }
                } catch (Exception $e) {
                    error_log("Error fetching email #$i: " . $e->getMessage());
                }
            }
            
        } catch (Exception $e) {
            error_log("Fetch Emails Error: " . $e->getMessage());
        }
        
        return $emails;
    }
    
    /**
     * Récupérer les détails d'un email spécifique
     */
    private function fetchEmailDetails($messageNumber) {
        try {
            // Header de l'email
            $header = imap_headerinfo($this->imapStream, $messageNumber);
            $structure = imap_fetchstructure($this->imapStream, $messageNumber);
            
            if (!$header) {
                return null;
            }
            
            // UID unique du message
            $uid = imap_uid($this->imapStream, $messageNumber);
            
            // Extraire les informations de l'expéditeur
            $from = $header->from[0] ?? null;
            $fromAddress = $from ? ($from->mailbox . '@' . $from->host) : 'unknown';
            $fromName = $from->personal ?? $fromAddress;
            
            // Extraire le destinataire
            $to = $header->to[0] ?? null;
            $toAddress = $to ? ($to->mailbox . '@' . $to->host) : '';
            
            // Décoder le sujet
            $subject = $this->decodeHeader($header->subject ?? '(Pas de sujet)');
            
            // Récupérer le corps du message
            $body = $this->getMessageBody($messageNumber);
            
            // Statut de lecture
            $isRead = !isset($header->Unseen);
            
            // Pièces jointes
            $hasAttachments = $this->hasAttachments($structure);
            
            // Message-ID pour déduplication
            $messageIdHeader = $header->message_id ?? '';
            $inReplyTo = $header->in_reply_to ?? '';
            $references = $header->references ?? '';
            
            return [
                'imap_uid' => $uid,
                'message_id' => $messageNumber,
                'message_id_header' => $messageIdHeader,
                'in_reply_to' => $inReplyTo,
                'references' => $references,
                'from_address' => $fromAddress,
                'from_name' => $this->decodeHeader($fromName),
                'to_address' => $toAddress,
                'subject' => $subject,
                'body' => $body,
                'email_date' => date('Y-m-d H:i:s', $header->udate),
                'is_read' => $isRead,
                'has_attachments' => $hasAttachments,
                'mailbox' => 'INBOX'
            ];
            
        } catch (Exception $e) {
            error_log("Error fetching email details: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Récupérer le corps du message
     */
    private function getMessageBody($messageNumber) {
        $body = '';
        
        try {
            $structure = imap_fetchstructure($this->imapStream, $messageNumber);
            
            if (!$structure) {
                // Fallback: récupérer le body complet
                $body = imap_body($this->imapStream, $messageNumber);
                return $this->decodeBody($body, '1', $messageNumber);
            }
            
            // Si c'est un message multipart
            if (isset($structure->parts) && count($structure->parts) > 0) {
                $body = $this->getMultipartBody($messageNumber, $structure);
            } else {
                // Message simple (non-multipart)
                $body = imap_body($this->imapStream, $messageNumber);
                $body = $this->decodeBody($body, '1', $messageNumber);
            }
            
        } catch (Exception $e) {
            error_log("Get body error: " . $e->getMessage());
            // Fallback final
            try {
                $body = imap_body($this->imapStream, $messageNumber);
                $body = $this->decodeBody($body, '1', $messageNumber);
            } catch (Exception $e2) {
                $body = '';
            }
        }
        
        return $body;
    }
    
    /**
     * Récupérer le corps d'un message multipart
     */
    private function getMultipartBody($messageNumber, $structure) {
        $body = '';
        $htmlBody = '';
        $textBody = '';
        
        // Parcourir toutes les parties
        foreach ($structure->parts as $partNum => $part) {
            $partNumber = ($partNum + 1);
            
            // Récupérer le type MIME
            $mimeType = $this->getMimeType($part);
            
            // Si c'est du HTML
            if ($mimeType === 'text/html') {
                $partBody = imap_fetchbody($this->imapStream, $messageNumber, $partNumber);
                // Décoder selon l'encodage
                $partBody = $this->decodePartBody($partBody, $part);
                $htmlBody = $partBody;
            }
            // Si c'est du texte brut
            elseif ($mimeType === 'text/plain') {
                $partBody = imap_fetchbody($this->imapStream, $messageNumber, $partNumber);
                $partBody = $this->decodePartBody($partBody, $part);
                $textBody = $partBody;
            }
            // Si c'est multipart imbriqué
            elseif ($part->type == 1 && isset($part->parts)) {
                // Structure imbriquée (ex: multipart/alternative dans multipart/mixed)
                foreach ($part->parts as $subPartNum => $subPart) {
                    $subPartNumber = $partNumber . '.' . ($subPartNum + 1);
                    $subMimeType = $this->getMimeType($subPart);
                    
                    if ($subMimeType === 'text/html') {
                        $subBody = imap_fetchbody($this->imapStream, $messageNumber, $subPartNumber);
                        $htmlBody = $this->decodePartBody($subBody, $subPart);
                    } elseif ($subMimeType === 'text/plain') {
                        $subBody = imap_fetchbody($this->imapStream, $messageNumber, $subPartNumber);
                        $textBody = $this->decodePartBody($subBody, $subPart);
                    }
                }
            }
        }
        
        // Préférer HTML si disponible, sinon texte
        if (!empty($htmlBody)) {
            $body = $htmlBody;
        } elseif (!empty($textBody)) {
            $body = nl2br(htmlspecialchars($textBody, ENT_QUOTES, 'UTF-8'));
        }
        
        return $body;
    }
    
    /**
     * Obtenir le type MIME d'une partie
     */
    private function getMimeType($part) {
        $primaryType = ['text', 'multipart', 'message', 'application', 'audio', 'image', 'video', 'other'];
        $type = $primaryType[$part->type] ?? 'other';
        $subtype = strtolower($part->subtype ?? 'plain');
        return $type . '/' . $subtype;
    }
    
    /**
     * Décoder le corps d'une partie selon son encodage
     */
    private function decodePartBody($body, $part) {
        // Décoder selon l'encodage
        switch ($part->encoding ?? 0) {
            case 1: // 8BIT
                $body = imap_8bit($body);
                break;
            case 2: // BINARY
                $body = imap_binary($body);
                break;
            case 3: // BASE64
                $body = base64_decode($body);
                break;
            case 4: // QUOTED-PRINTABLE
                $body = quoted_printable_decode($body);
                break;
        }
        
        // Convertir en UTF-8
        if (!mb_check_encoding($body, 'UTF-8')) {
            $encoding = mb_detect_encoding($body, ['UTF-8', 'ISO-8859-1', 'ISO-8859-15', 'Windows-1252'], true);
            if ($encoding) {
                $body = mb_convert_encoding($body, 'UTF-8', $encoding);
            }
        }
        
        return $body;
    }
    
    /**
     * Décoder le corps du message selon l'encodage
     */
    private function decodeBody($body, $section, $messageNumber) {
        $structure = imap_fetchstructure($this->imapStream, $messageNumber);
        
        // Déterminer l'encodage
        $encoding = 0;
        if ($structure) {
            if ($section === '1' && !isset($structure->parts)) {
                $encoding = $structure->encoding ?? 0;
            } elseif (isset($structure->parts)) {
                $parts = explode('.', $section);
                $part = $structure;
                foreach ($parts as $p) {
                    if (isset($part->parts[$p - 1])) {
                        $part = $part->parts[$p - 1];
                    }
                }
                $encoding = $part->encoding ?? 0;
            }
        }
        
        // Décoder selon le type d'encodage
        switch ($encoding) {
            case 1: // 8BIT
                $body = imap_8bit($body);
                break;
            case 2: // BINARY
                $body = imap_binary($body);
                break;
            case 3: // BASE64
                $body = base64_decode($body);
                break;
            case 4: // QUOTED-PRINTABLE
                $body = quoted_printable_decode($body);
                break;
            default: // 7BIT ou autre
                break;
        }
        
        // Convertir en UTF-8 si nécessaire
        if (!mb_check_encoding($body, 'UTF-8')) {
            $body = mb_convert_encoding($body, 'UTF-8', 'auto');
        }
        
        return $body;
    }
    
    /**
     * Décoder un header MIME
     */
    private function decodeHeader($header) {
        $decoded = imap_mime_header_decode($header);
        $result = '';
        
        foreach ($decoded as $part) {
            $charset = ($part->charset === 'default') ? 'UTF-8' : $part->charset;
            $result .= mb_convert_encoding($part->text, 'UTF-8', $charset);
        }
        
        return $result;
    }
    
    /**
     * Vérifier si le message a des pièces jointes
     */
    private function hasAttachments($structure) {
        if (!isset($structure->parts) || !is_array($structure->parts)) {
            return false;
        }
        
        foreach ($structure->parts as $part) {
            if (isset($part->disposition) && strtolower($part->disposition) === 'attachment') {
                return true;
            }
            if (isset($part->parts)) {
                if ($this->hasAttachments($part)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Sauvegarder un email dans la base de données
     */
    private function saveEmail($emailData) {
        // Vérifier si l'email existe déjà (par UID ou Message-ID)
        $stmt = $this->pdo->prepare("
            SELECT id FROM emails 
            WHERE config_id = ? 
            AND (imap_uid = ? OR message_id_header = ?)
            LIMIT 1
        ");
        $stmt->execute([
            $this->config['id'],
            $emailData['imap_uid'],
            $emailData['message_id_header']
        ]);
        
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Mettre à jour l'email existant
            $stmt = $this->pdo->prepare("
                UPDATE emails SET
                    is_read = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");

            $stmt->execute([
                $emailData['is_read'],
                $existing['id']
            ]);
            return 'updated';
        } else {
            // Insérer un nouvel email
            $stmt = $this->pdo->prepare("
                INSERT INTO emails (
                    customer_id, config_id, imap_uid, mailbox,
                    message_id_header, in_reply_to, references_header,
                    from_address, from_name, to_address, subject, body,
                    email_date, is_read, has_attachments, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $this->config['customer_id'],
                $this->config['id'],
                $emailData['imap_uid'],
                $emailData['mailbox'],
                $emailData['message_id_header'],
                $emailData['in_reply_to'],
                $emailData['references'],
                $emailData['from_address'],
                $emailData['from_name'],
                $emailData['to_address'],
                $emailData['subject'],
                $emailData['body'],
                $emailData['email_date'],
                $emailData['is_read'],
                $emailData['has_attachments']
            ]);
            
            return 'new';
        }
    }
    
    /**
     * Mettre à jour la date de dernière synchronisation
     */
    private function updateLastSync() {
        $stmt = $this->pdo->prepare("
            UPDATE email_configurations 
            SET last_sync = NOW(), last_error = NULL 
            WHERE id = ?
        ");
        $stmt->execute([$this->config['id']]);
    }
    
    /**
     * Enregistrer une erreur
     */
    private function logError($message) {
        $stmt = $this->pdo->prepare("
            UPDATE email_configurations 
            SET last_error = ? 
            WHERE id = ?
        ");
        $stmt->execute([substr($message, 0, 500), $this->config['id']]);
        error_log("EmailSync Error (config_id={$this->config['id']}): $message");
    }
    
    /**
     * Déconnecter IMAP
     */
    private function disconnect() {
        if ($this->imapStream) {
            @imap_close($this->imapStream);
            $this->imapStream = null;
        }
    }
    
    public function __destruct() {
        $this->disconnect();
    }
}
