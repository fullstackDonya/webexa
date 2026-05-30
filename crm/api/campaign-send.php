<?php
/**
 * API pour envoyer ou programmer les campagnes
 */

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

// Charger d'abord la configuration de la base de données
require_once __DIR__ . '/../config/database.php';

// Démarrer la session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier l'authentification
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

// Charger les includes nécessaires
require_once __DIR__ . '/../includes/campaign_send.php';

$response = ['success' => false, 'message' => 'Erreur'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_SESSION['customer_id'] ?? null;
    $action = $_POST['action'] ?? '';
    $campaign_id = intval($_POST['campaign_id'] ?? 0);
    $channel = $_POST['channel'] ?? 'email'; // email ou whatsapp
    
    // Valider les paramètres
    if (!$customer_id || !$campaign_id) {
        $response['message'] = 'Paramètres manquants';
        echo json_encode($response);
        exit;
    }
    
    try {
        if ($action === 'send') {
            // Envoi immédiat
            $lead_ids = isset($_POST['lead_ids']) ? array_map('intval', explode(',', $_POST['lead_ids'])) : null;
            
            if ($channel === 'whatsapp') {
                $response = send_campaign_whatsapp($pdo, $campaign_id, $customer_id, $lead_ids);
            } else {
                $response = send_campaign_email($pdo, $campaign_id, $customer_id, $lead_ids);
            }
        } elseif ($action === 'schedule') {
            // Programmer l'envoi
            $scheduled_at = $_POST['scheduled_at'] ?? null;
            
            if (!$scheduled_at) {
                $response['message'] = 'Date de programmation manquante';
            } else {
                $response = schedule_campaign($pdo, $campaign_id, $customer_id, $scheduled_at, $channel);
            }
        } else {
            $response['message'] = 'Action non reconnue';
        }
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => $e->getMessage()];
        error_log("Campaign API error: " . $e->getMessage());
    }
}

echo json_encode($response);
?>
