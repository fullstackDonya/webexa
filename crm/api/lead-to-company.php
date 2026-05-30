<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

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

    $existingId = null;
    if ($email !== '') {
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
        echo json_encode(['success' => true, 'message' => 'Company déjà existante', 'company_id' => (int)$existingId]);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO companies (
            name, email, phone, industry, address, city, country, website, status, notes, assigned_to, customer_id, interne_customer
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

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
        0
    ]);

    $newId = (int)$pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Company créée depuis le lead',
        'company_id' => $newId
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
