<?php
require_once __DIR__ . '/config/database.php';

$files = glob(__DIR__ . '/migrations/*.sql');
sort($files);

foreach ($files as $file) {
    $sql = file_get_contents($file);
    try {
        $pdo->exec($sql);
        echo "Applied: " . basename($file) . "\n";
    } catch (Exception $e) {
        echo "Migration failed for " . basename($file) . ": " . $e->getMessage() . "\n";
        exit(1);
    }
}

echo "All migrations applied.\n";