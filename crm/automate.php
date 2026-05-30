<?php
// Script to be called by CRON every X minutes to execute automations
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/mailer.php';

// keep a short runtime
set_time_limit(300);

// fetch active automations
try{
    $automations = $pdo->query("SELECT * FROM automations WHERE status = 'active'")->fetchAll();
}catch(Exception $e){
    echo "Erreur lecture automations: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

foreach($automations as $automation){
    // simple examples for triggers
    if($automation['trigger_type'] === 'date'){
        // contacts with birthday today (month-day)
        if(!empty($automation['customer_id'])){
            $stmt = $pdo->prepare("SELECT * FROM contacts WHERE customer_id = ? AND DATE_FORMAT(birthday,'%m-%d') = DATE_FORMAT(NOW(),'%m-%d')");
            $stmt->execute([$automation['customer_id']]);
        }else{
            $stmt = $pdo->prepare("SELECT * FROM contacts WHERE DATE_FORMAT(birthday,'%m-%d') = DATE_FORMAT(NOW(),'%m-%d')");
            $stmt->execute();
        }
        $contacts = $stmt->fetchAll();
        foreach($contacts as $c){
            // build email from the first email step if present
            $step = $pdo->prepare('SELECT * FROM automation_steps WHERE automation_id = ? AND action_type = "email" ORDER BY step_order LIMIT 1');
            $step->execute([$automation['id']]);
            $s = $step->fetch();
            if($s){
                $subject = 'Automatisation: ' . ($automation['name'] ?? 'Message');
                $body = $s['content'] ?: 'Bonjour ' . ($c['firstname'] ?? '') . ', bonne anniversaire !';
                sendEmail($c['email'],$subject,$body);
                echo "Envoyé à {$c['email']} pour automation {$automation['id']}\n";
            }
        }
    }

    if($automation['trigger_type'] === 'inactivity'){
        // contacts inactive longer than a threshold; assume threshold is encoded in audience_filter like 'inactivity:90'
        $threshold = 90;
        if(!empty($automation['audience_filter']) && preg_match('/inactivity:(\d+)/',$automation['audience_filter'],$m)){
            $threshold = intval($m[1]);
        }
        if(!empty($automation['customer_id'])){
            $stmt = $pdo->prepare("SELECT * FROM contacts WHERE customer_id = ? AND last_activity < (NOW() - INTERVAL ? DAY)");
            $stmt->execute([$automation['customer_id'], $threshold]);
        }else{
            $stmt = $pdo->prepare("SELECT * FROM contacts WHERE last_activity < (NOW() - INTERVAL ? DAY)");
            $stmt->execute([$threshold]);
        }
        $contacts = $stmt->fetchAll();
        foreach($contacts as $c){
            $step = $pdo->prepare('SELECT * FROM automation_steps WHERE automation_id = ? AND action_type = "email" ORDER BY step_order LIMIT 1');
            $step->execute([$automation['id']]);
            $s = $step->fetch();
            if($s){
                $subject = 'On ne vous a pas vu depuis un moment';
                $body = $s['content'] ?: 'Revenez profiter d\'une offre exclusive';
                sendEmail($c['email'],$subject,$body);
                echo "Envoyé à {$c['email']} pour automation {$automation['id']}\n";
            }
        }
    }

    // additional triggers could be added here
}

echo "Automatisations exécutées à " . date('c') . PHP_EOL;

?>
