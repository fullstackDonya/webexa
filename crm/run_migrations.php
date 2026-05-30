<?php
require_once __DIR__ . '/config/database.php';

$sqlFile = __DIR__ . '/migrations/001_create_automations.sql';
if(!file_exists($sqlFile)){
    echo "Migration file not found: $sqlFile\n";
    exit(1);
}

$sql = file_get_contents($sqlFile);
try{
    $pdo->exec($sql);
    echo "Migrations applied successfully.\n";
}catch(Exception $e){
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(2);
}

?>
