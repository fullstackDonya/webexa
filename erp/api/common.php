<?php
declare(strict_types=1);

require_once __DIR__ . '/../../crm/config/database.php';

// Helper function to respond with JSON
function jsonResponse(array $data, int $status = 200): void {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Function to check if a record exists by ID
function recordExists(PDO $pdo, string $table, int $id): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE id = :id");
    $stmt->execute([':id' => $id]);
    return (bool)$stmt->fetchColumn();
}

// Function to sanitize input data
function sanitizeInput(array $data): array {
    return array_map('htmlspecialchars', $data);
}

// Function to handle database errors
function handleDbError(PDOException $e): void {
    jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
}
?>