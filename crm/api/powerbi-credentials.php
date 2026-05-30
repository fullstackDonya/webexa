<?php
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../includes/auth.php';

session_start();

try {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Non authentifié']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $customer_id = $_SESSION['customer_id'] ?? null;
    if (!$customer_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Aucun customer associé']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Requête invalide']);
        exit;
    }

    $client_id = trim($input['powerbi_client_id'] ?? '');
    $client_secret = trim($input['powerbi_client_secret'] ?? '');
    $tenant_id = trim($input['powerbi_tenant_id'] ?? '');

    if ($client_id === '' || $client_secret === '' || $tenant_id === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'Les champs Client ID, Client Secret et Tenant ID sont obligatoires']);
        exit;
    }

    // Met à jour uniquement les champs requis
    $stmt = $pdo->prepare("UPDATE customers SET powerbi_client_id = ?, powerbi_client_secret = ?, powerbi_tenant_id = ? WHERE id = ?");
    $stmt->execute([$client_id, $client_secret, $tenant_id, $customer_id]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    error_log('powerbi-credentials error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur serveur']);