<?php
require_once __DIR__ . '/../config/database.php';
include("verify_subscriptions.php");

session_start();

$customer_id = $_SESSION['customer_id'] ?? null;
if ($customer_id === null) {
    die("Erreur : client non identifié.");
}

// Récupérer les filtres
$search  = $_GET['search'] ?? '';
$company = $_GET['company'] ?? '';
$status  = $_GET['status'] ?? '';

try {
    // Base de la requête
    $query = "
        SELECT c.*, COALESCE(co.name, '') AS company_name
        FROM leads c
        LEFT JOIN companies co ON co.id = c.company_id
        WHERE c.customer_id = :cid
    ";
    $params = [':cid' => $customer_id];

    // Appliquer les filtres si présents
    if (!empty($search)) {
        $query .= " AND (c.firstname LIKE :search OR c.lastname LIKE :search OR c.email LIKE :search OR c.phone LIKE :search)";
        $params[':search'] = "%$search%";
    }

    if (!empty($company)) {
        $query .= " AND c.company_id = :company";
        $params[':company'] = $company;
    }

    if (!empty($status)) {
        $query .= " AND c.status = :status";
        $params[':status'] = $status;
    }

    $query .= " ORDER BY COALESCE(c.updated_at, c.created_at) DESC, c.id DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupération des entreprises (pour le filtre)
    $stmt2 = $pdo->prepare("SELECT id, name FROM companies WHERE customer_id = :cid ORDER BY name ASC");
    $stmt2->execute([':cid' => $customer_id]);
    $companies = $stmt2->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    error_log('contacts fetch error: ' . $e->getMessage());
    $contacts = [];
    $companies = [];
}
