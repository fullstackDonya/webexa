<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/verify_subscriptions.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/ai_scoring.php';

$customer_id = isset($_SESSION['customer_id']) ? (int)$_SESSION['customer_id'] : null;
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : ($user['id'] ?? null);

// Log pour débogage
error_log("leads-add.php - user_id: $user_id, customer_id: " . ($customer_id ?? 'NULL'));

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Récupération des données du formulaire
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim(strtolower($_POST['email'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');
        $position = trim($_POST['position'] ?? '');
        $source = trim($_POST['source'] ?? 'website');
        $stage = trim($_POST['status'] ?? 'lead'); // stage pour le pipeline
        $budget = !empty($_POST['budget']) ? (float)$_POST['budget'] : null;

        // Validation
        if (empty($first_name) || empty($last_name) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Prénom, nom et email valide sont requis.');
        }

        error_log("leads-add.php - Ajout lead: $first_name $last_name ($email), customer_id: " . ($customer_id ?? 'NULL'));

        // Vérifier si un lead avec cet email existe déjà (warning uniquement, pas bloquant)
        $stmt = $pdo->prepare("
            SELECT id FROM leads 
            WHERE email = ? AND (customer_id = ? OR assigned_to = ?)
            LIMIT 1
        ");
        $stmt->execute([$email, $customer_id, $user_id]);
        if ($stmt->fetchColumn()) {
            error_log("leads-add.php - WARNING: Un lead avec l'email $email existe déjà pour ce customer");
        }

        // Calculer le score IA AVANT d'insérer
        $lead_data = [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'phone' => $phone,
            'position' => $position,
            'company_id' => null,
            'stage' => $stage,
            'source' => $source,
            'assigned_to' => $user_id
        ];
        $ai_score = calculate_lead_score($lead_data);
        error_log("leads-add.php - Calculated AI score: $ai_score");

        // Insertion directe dans la table leads AVEC le score IA
        $stmt = $pdo->prepare("
            INSERT INTO leads (
                first_name, last_name, email, phone, position, 
                stage, assigned_to, customer_id, source, ai_score, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $first_name,
            $last_name,
            $email,
            $phone ?: null,
            $position ?: null,
            $stage,
            $user_id,
            $customer_id,
            $source,
            $ai_score
        ]);

        $newId = $pdo->lastInsertId();
        error_log("leads-add.php - Lead créé avec succès, ID: $newId, AI score: $ai_score");

        header('Location: leads.php?success=1');
        exit;

    } catch (PDOException $e) {
        error_log('leads-add.php PDO error: ' . $e->getMessage());
        $error_message = 'Erreur lors de l\'ajout du lead: ' . $e->getMessage();
        header('Location: leads-add.php?error=' . urlencode($error_message));
        exit;
    } catch (Exception $e) {
        error_log('leads-add.php error: ' . $e->getMessage());
        $error_message = $e->getMessage();
        header('Location: leads-add.php?error=' . urlencode($error_message));
        exit;
    }
}
