<?php
/**
 * Helper pour récupérer les configurations email et WhatsApp actives
 * Ce fichier doit être inclus dans les pages de gestion des campagnes
 * 
 * Usage: include 'includes/campaign-config-helper.php';
 */

if (!isset($pdo) || !isset($customer_id)) {
    die("PDO connection and customer_id required");
}

/**
 * Récupère les configurations email actives pour le client
 * @return array Liste des configurations email actives
 */
function get_active_email_configs($pdo, $customer_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, email, provider, is_active 
            FROM email_configurations 
            WHERE customer_id = ? AND is_active = 1 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$customer_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching email configs: " . $e->getMessage());
        return [];
    }
}

/**
 * Récupère les configurations WhatsApp actives pour le client
 * @return array Liste des configurations WhatsApp actives
 */
function get_active_whatsapp_configs($pdo, $customer_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, display_phone_number, business_account_id, is_active 
            FROM whatsapp_configurations 
            WHERE customer_id = ? AND is_active = 1 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$customer_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching whatsapp configs: " . $e->getMessage());
        return [];
    }
}

/**
 * Récupère la configuration email par ID
 * @param int $config_id ID de la configuration
 * @return array|null Configuration ou null si non trouvée
 */
function get_email_config_by_id($pdo, $config_id, $customer_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, email, provider 
            FROM email_configurations 
            WHERE id = ? AND customer_id = ?
        ");
        $stmt->execute([$config_id, $customer_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching email config: " . $e->getMessage());
        return null;
    }
}

/**
 * Récupère la configuration WhatsApp par ID
 * @param int $config_id ID de la configuration
 * @return array|null Configuration ou null si non trouvée
 */
function get_whatsapp_config_by_id($pdo, $config_id, $customer_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, display_phone_number, business_account_id 
            FROM whatsapp_configurations 
            WHERE id = ? AND customer_id = ?
        ");
        $stmt->execute([$config_id, $customer_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching whatsapp config: " . $e->getMessage());
        return null;
    }
}

/**
 * Détermine automatiquement l'expéditeur basé sur la configuration sélectionnée
 * @param string $channel Canal de communication ('email' ou 'whatsapp')
 * @param int|null $config_id ID de la configuration
 * @return array ['sender_name' => string, 'sender_email' => string]
 */
function get_sender_from_config($pdo, $customer_id, $channel, $config_id = null) {
    $result = [
        'sender_name' => 'Mon Entreprise',
        'sender_email' => 'noreply@monentreprise.com'
    ];
    
    if ($channel === 'email' && $config_id) {
        $config = get_email_config_by_id($pdo, $config_id, $customer_id);
        if ($config) {
            $result['sender_email'] = $config['email'];
            $result['sender_name'] = explode('@', $config['email'])[0];
        }
    } elseif ($channel === 'whatsapp' && $config_id) {
        $config = get_whatsapp_config_by_id($pdo, $config_id, $customer_id);
        if ($config) {
            $result['sender_name'] = $config['display_phone_number'] ?? 'WhatsApp Business';
            $result['sender_email'] = ''; // Not used for WhatsApp
        }
    }
    
    return $result;
}

/**
 * Ajoute automatiquement les colonnes nécessaires à la table campaigns si elles n'existent pas
 */
function ensure_campaign_config_columns($pdo) {
    $ensureColumn = function($table, $column, $definition) use ($pdo) {
        try {
            $sql = "SELECT COUNT(*) FROM information_schema.COLUMNS 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = ? 
                    AND COLUMN_NAME = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$table, $column]);
            $exists = (int)$stmt->fetchColumn() > 0;
            
            if (!$exists) {
                $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN {$definition}");
                error_log("Added column {$column} to table {$table}");
            }
        } catch (Exception $e) {
            error_log("Error ensuring column {$column}: " . $e->getMessage());
        }
    };
    
    // Ajouter les colonnes pour lier aux configurations
    $ensureColumn('campaigns', 'email_config_id', "email_config_id INT DEFAULT NULL");
    $ensureColumn('campaigns', 'whatsapp_config_id', "whatsapp_config_id INT DEFAULT NULL");
    $ensureColumn('campaigns', 'channel', "channel VARCHAR(20) DEFAULT 'email'");
}
