<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/permission-bootstrap.php';

$customer_id = $_SESSION['customer_id'] ?? null;
$user_id     = $_SESSION['user_id'] ?? null;

if (!$customer_id || !$user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

$lead_id = isset($_POST['lead_id']) ? (int)$_POST['lead_id'] : 0;
if ($lead_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Lead invalide']);
    exit;
}

try {
    // Ajouter la colonne lead_id si elle n'existe pas encore
    try {
        $pdo->exec("ALTER TABLE opportunities ADD COLUMN lead_id INT DEFAULT NULL");
        $pdo->exec("ALTER TABLE opportunities ADD INDEX idx_opp_lead_id (lead_id)");
    } catch (Throwable $ignore) {}

    // Récupérer le lead
    $stmt = $pdo->prepare("SELECT * FROM leads WHERE id = ? AND (customer_id = ? OR assigned_to = ?)");
    $stmt->execute([$lead_id, $customer_id, $user_id]);
    $lead = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$lead) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Lead introuvable']);
        exit;
    }

    // Vérifier doublon
    $s = $pdo->prepare("SELECT id FROM opportunities WHERE lead_id = ? LIMIT 1");
    $s->execute([$lead_id]);
    $existingId = $s->fetchColumn();

    if ($existingId) {
        echo json_encode([
            'success'        => false,
            'already_exists' => true,
            'message'        => 'Ce lead est déjà une opportunité',
            'opportunity_id' => (int)$existingId,
        ]);
        exit;
    }

    // Construire le titre
    $full_name    = trim(($lead['first_name'] ?? '') . ' ' . ($lead['last_name'] ?? ''));
    $company_name = trim((string)($lead['company'] ?? $lead['company_name'] ?? ''));
    $title        = $company_name !== '' ? $company_name : ($full_name !== '' ? $full_name : 'Lead #' . $lead_id);

    // Insérer l'opportunité
    $stmt = $pdo->prepare(
        "INSERT INTO opportunities (title, customer_id, company_id, assigned_to, stage, source, lead_id, created_at)
         VALUES (?, ?, ?, ?, 'prospecting', ?, ?, NOW())"
    );
    $stmt->execute([
        $title,
        $customer_id,
        $lead['company_id'] ?: null,
        $user_id,
        $lead['source'] ?: null,
        $lead_id,
    ]);

    $newId = (int)$pdo->lastInsertId();

    echo json_encode([
        'success'        => true,
        'message'        => 'Opportunité créée depuis le lead',
        'opportunity_id' => $newId,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
