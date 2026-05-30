<?php

require_once 'verify_subscriptions.php';

// Récupérer l'ID du client connecté (exemple : stocké dans $_SESSION['customer_id'])
$customer_id = $_SESSION['customer_id'] ?? 0;

$page_title = "Missions - CRM Intelligent";

// Charger toutes les missions liées aux dossiers de la société du client connecté
$stmt = $pdo->prepare("
    SELECT 
        m.id AS mission_id,
        m.departure,
        m.arrival,
        m.datetime,
        m.driver,
        m.vehicle,
        m.status_id,
        s.name AS status_name,
        m.created_at,
        f.id AS folder_id,
        f.name AS folder_name,
        c.name AS company_name
    FROM missions m
    INNER JOIN folders f ON m.folder_id = f.id
    INNER JOIN companies c ON f.company_id = c.id
    LEFT JOIN statuses s ON m.status_id = s.id
    WHERE c.customer_id = ?
    ORDER BY m.created_at DESC
");
$stmt->execute([$customer_id]);
$missions = $stmt->fetchAll(PDO::FETCH_ASSOC);