<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'verify_subscriptions.php';

$page_title = "Clients - CRM Intelligent";

$user_id = $_SESSION["user_id"];
$customer_id = $_SESSION['customer_id'] ?? null;

// Récupérer tous les clients externes du customer connecté
$stmt = $pdo->prepare("SELECT * FROM companies WHERE customer_id = ? AND interne_customer = 0 ORDER BY created_at DESC");
$stmt->execute([$customer_id]);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Compter les clients (statut client)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM companies WHERE status = 'client' AND customer_id = ? AND interne_customer = 0");
$stmt->execute([$customer_id]);
$total_clients = $stmt->fetchColumn();

// Compter les clients actifs
$stmt = $pdo->prepare("SELECT COUNT(*) FROM companies WHERE status = 'client' AND is_active = 1 AND customer_id = ? AND interne_customer = 0");
$stmt->execute([$customer_id]);
$active_clients = $stmt->fetchColumn();

// Revenus moyens
$stmt = $pdo->prepare("SELECT AVG(annual_revenue) FROM companies WHERE annual_revenue IS NOT NULL AND customer_id = ? AND interne_customer = 0");
$stmt->execute([$customer_id]);
$avg_revenue = $stmt->fetchColumn();

// Satisfaction moyenne
$stmt = $pdo->prepare("SELECT AVG(satisfaction_score) FROM companies WHERE satisfaction_score IS NOT NULL AND customer_id = ? AND interne_customer = 0");
$stmt->execute([$customer_id]);
$satisfaction_score = $stmt->fetchColumn();

