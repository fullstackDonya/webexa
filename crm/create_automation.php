<?php
require_once __DIR__ . '/config/database.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

$user_id = $_SESSION['user_id'] ?? null;
$customer_id = $_SESSION['customer_id'] ?? null;

$data = json_decode(file_get_contents('php://input'), true);
if(!$data || empty($data['name']) || empty($data['type']) || empty($data['trigger_type'])){
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Données invalides']);
    exit;
}

$name = trim($data['name']);
$type = trim($data['type']);
$trigger_type = trim($data['trigger_type']);
$audience_filter = trim($data['audience_filter'] ?? 'all');

try{
    // Create automations tables if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS automations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        customer_id INT NULL,
        name VARCHAR(255),
        type VARCHAR(50),
        trigger_type VARCHAR(50),
        audience_filter VARCHAR(100),
        status ENUM('active','paused') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS automation_steps (
        id INT AUTO_INCREMENT PRIMARY KEY,
        automation_id INT NOT NULL,
        step_order INT NOT NULL,
        action_type ENUM('email','wait','sms') DEFAULT 'email',
        content TEXT,
        delay_hours INT DEFAULT 0,
        FOREIGN KEY (automation_id) REFERENCES automations(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $ins = $pdo->prepare('INSERT INTO automations (user_id,customer_id,name,type,trigger_type,audience_filter,status) VALUES (?,?,?,?,?,?,"active")');
    $ins->execute([$user_id ?? 0, $customer_id ?? null, $name, $type, $trigger_type, $audience_filter]);
    $automation_id = $pdo->lastInsertId();

    // optionally accept steps array
    if(!empty($data['steps']) && is_array($data['steps'])){
        $stepStmt = $pdo->prepare('INSERT INTO automation_steps (automation_id,step_order,action_type,content,delay_hours) VALUES (?,?,?,?,?)');
        $order = 1;
        foreach($data['steps'] as $s){
            $atype = $s['action_type'] ?? 'email';
            $content = $s['content'] ?? null;
            $delay = intval($s['delay_hours'] ?? 0);
            $stepStmt->execute([$automation_id,$order,$atype,$content,$delay]);
            $order++;
        }
    }

    echo json_encode(['success'=>true,'message'=>'Automatisation créée avec succès','id'=>$automation_id]);
}catch(Exception $e){
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Erreur serveur: '.$e->getMessage()]);
}

?>
