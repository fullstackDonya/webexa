<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/permission-bootstrap.php';

$customer_id = $_SESSION['customer_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

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
    $stmt = $pdo->prepare("SELECT * FROM leads WHERE id = ? AND (customer_id = ? OR assigned_to = ?)");
    $stmt->execute([$lead_id, $customer_id, $user_id]);
    $lead = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$lead) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Lead introuvable']);
        exit;
    }

    $company_name = trim((string)($lead['company'] ?? $lead['company_name'] ?? ''));
    if ($company_name === '') {
        $full_name = trim(($lead['first_name'] ?? '') . ' ' . ($lead['last_name'] ?? ''));
        $company_name = $full_name !== '' ? $full_name . ' (Lead)' : 'Lead #' . $lead_id;
    }

    $email = trim((string)($lead['email'] ?? ''));
    $phone = trim((string)($lead['phone'] ?? $lead['mobile'] ?? ''));
    $industry = trim((string)($lead['industry'] ?? ''));
    $address = trim((string)($lead['address'] ?? ''));
    $city = trim((string)($lead['city'] ?? ''));
    $country = trim((string)($lead['country'] ?? ''));
    $website = trim((string)($lead['website'] ?? ''));
    $notes = trim((string)($lead['notes'] ?? ''));

    // Ajouter la colonne lead_id dans companies si absente
    try {
        $pdo->exec("ALTER TABLE companies ADD COLUMN lead_id INT DEFAULT NULL");
        $pdo->exec("ALTER TABLE companies ADD INDEX idx_companies_lead_id (lead_id)");
    } catch (Throwable $ignore) {}

    $existingId = null;
    // Vérifier par lead_id en priorité (doublon fiable)
    $s = $pdo->prepare("SELECT id FROM companies WHERE lead_id = ? AND customer_id = ? LIMIT 1");
    $s->execute([$lead_id, $customer_id]);
    $existingId = $s->fetchColumn();

    if (!$existingId && $email !== '') {
        $s = $pdo->prepare("SELECT id FROM companies WHERE email = ? AND customer_id = ? LIMIT 1");
        $s->execute([$email, $customer_id]);
        $existingId = $s->fetchColumn();
    }
    if (!$existingId) {
        $s = $pdo->prepare("SELECT id FROM companies WHERE name = ? AND customer_id = ? LIMIT 1");
        $s->execute([$company_name, $customer_id]);
        $existingId = $s->fetchColumn();
    }

    if ($existingId) {
        echo json_encode(['success' => false, 'already_exists' => true, 'message' => 'Ce lead est déjà un client', 'company_id' => (int)$existingId]);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO companies (
            name, email, phone, industry, address, city, country, website, status, notes, assigned_to, customer_id, interne_customer, lead_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        $company_name,
        $email !== '' ? $email : null,
        $phone !== '' ? $phone : null,
        $industry !== '' ? $industry : null,
        $address !== '' ? $address : null,
        $city !== '' ? $city : null,
        $country !== '' ? $country : null,
        $website !== '' ? $website : null,
        'prospect',
        $notes !== '' ? $notes : null,
        $user_id,
        $customer_id,
        0,
        $lead_id,
    ]);

    $newId = (int)$pdo->lastInsertId();

    // Lier le lead à la company créée
    $pdo->prepare("UPDATE leads SET company_id = ? WHERE id = ?")->execute([$newId, $lead_id]);

    echo json_encode([
        'success'    => true,
        'message'    => 'Client créé depuis le lead',
        'company_id' => $newId,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
