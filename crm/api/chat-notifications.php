<?php
// Returns counts for live chat notifications
header('Content-Type: application/json; charset=utf-8');

// CORS headers (optional for same-origin; harmless if present)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('isAuthenticated') || !isAuthenticated()) {
    echo json_encode(['success' => false, 'error' => 'unauthenticated']);
    exit;
}

try {
    // Verify required tables exist; if not, return zeros gracefully
    $tablesOk = true;
    foreach (['conversations', 'chat'] as $tbl) {
        $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
        $stmt->execute([$tbl]);
        if ($stmt->rowCount() === 0) { $tablesOk = false; break; }
    }

    if (!$tablesOk) {
        echo json_encode([
            'success' => true,
            'counts' => [
                'open_conversations' => 0,
                'pending_conversations' => 0
            ],
            'generated_at' => date('Y-m-d H:i:s')
        ]);
        exit;
    }

    // Count all open conversations
    $stmt = $pdo->query('SELECT COUNT(*) AS c FROM conversations WHERE is_closed = 0');
    $openConversations = (int)($stmt->fetchColumn() ?: 0);

    // Count conversations where the latest message is from a visitor (is_admin = 0)
    // Proxy for pending attention by support/admin.
    $sqlPending = "
        SELECT COUNT(*) AS c
        FROM conversations c
        WHERE c.is_closed = 0
          AND COALESCE((
                SELECT m.is_admin
                FROM chat m
                WHERE m.conversation_id = c.id
                ORDER BY m.id DESC
                LIMIT 1
          ), 0) = 0
    ";
    $stmt = $pdo->query($sqlPending);
    $pendingConversations = (int)($stmt->fetchColumn() ?: 0);

    echo json_encode([
        'success' => true,
        'counts' => [
            'open_conversations' => $openConversations,
            'pending_conversations' => $pendingConversations
        ],
        'generated_at' => date('Y-m-d H:i:s')
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'server_error',
        'message' => $e->getMessage()
    ]);
}