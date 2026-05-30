<?php

// afficher toutes les erreurs 
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

session_start();

require_once __DIR__ . '/../config/database.php';

// Récupérer l'ID du dossier depuis l'URL
$folder_id = isset($_POST['folder_id']) ? intval($_POST['folder_id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
// Récupérer les statuts (pour missions uniquement)
$stmt = $pdo->query("SELECT id, name FROM statuses");
$all_statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement de l'ajout de mission
$mission_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_mission'])) {
    $type = trim($_POST['type'] ?? 'general');
    $status_id = intval($_POST['status_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');

    // Champs communs (formulaire unique)
    $fields = [
        'folder_id' => $folder_id,
        'type' => $type,
        'status_id' => $status_id,
        'name' => $name,
        'description' => trim($_POST['description'] ?? ''),
        'datetime' => trim($_POST['datetime'] ?? ''),
        'project' => trim($_POST['project'] ?? ''),
        'responsible' => trim($_POST['responsible'] ?? ''),
        'departure' => trim($_POST['departure'] ?? ''),
        'arrival' => trim($_POST['arrival'] ?? ''),
        'driver' => trim($_POST['driver'] ?? ''),
        'vehicle' => trim($_POST['vehicle'] ?? ''),
        'product' => trim($_POST['product'] ?? ''),
        'quantity' => (isset($_POST['quantity']) && $_POST['quantity'] !== '') ? intval($_POST['quantity']) : null,
        'created_at' => date('Y-m-d H:i:s')
    ];

    if ($name === '') {
        $mission_error = "Le nom de la mission est obligatoire.";
    }

    // Insertion si pas d'erreur
    if (!$mission_error) {
        $columns = array_keys($fields);
        $placeholders = array_fill(0, count($fields), '?');
        $sql = "INSERT INTO missions (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($fields));
        // Ajoute le paramètre success=1 à la redirection
        header("Location: folder_view.php?id=" . $folder_id . "&success=1");
        exit;
    }

}



// Charger les infos du dossier
$stmt = $pdo->prepare("SELECT f.*, c.name AS company_name FROM folders f INNER JOIN companies c ON f.company_id = c.id WHERE f.id = ?");
$stmt->execute([$folder_id]);
$folder = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$folder) {
    echo "Dossier introuvable.";
    exit;
}

// Charger les missions liées à ce dossier

$stmt = $pdo->prepare("
    SELECT m.*, s.name AS status_name
    FROM missions m
    LEFT JOIN statuses s ON m.status_id = s.id
    WHERE m.folder_id = ?
    ORDER BY m.created_at DESC
");
$stmt->execute([$folder_id]);
$missions = $stmt->fetchAll(PDO::FETCH_ASSOC);