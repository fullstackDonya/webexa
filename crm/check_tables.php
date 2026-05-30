<?php
require_once 'config/database.php';

$tables = ['tasks', 'call_reminders', 'opportunities', 'missions', 'folders'];

foreach ($tables as $table) {
    echo "\n=== STRUCTURE TABLE $table ===\n";
    try {
        $result = $pdo->query("DESCRIBE $table");
        $columns = $result->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo "{$col['Field']}\n";
        }
    } catch (Exception $e) {
        echo "Erreur: " . $e->getMessage() . "\n";
    }
}
