<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../../config/database.php'; // doit exposer $pdo

// Handle recompute trigger (POST to ?action=recompute) — lance le worker insights.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_GET['action'] ?? '') === 'recompute')) {
    // Ne pas afficher warnings -> réponse JSON propre
    @ini_set('display_errors', '0');
    @error_reporting(E_ALL);

    $php    = PHP_BINARY ?: 'php';
    $worker = __DIR__ . '/insights.php'; // script worker (doit renvoyer JSON)

    if (!file_exists($worker)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Worker introuvable: insights.php']);
        exit;
    }

    try {
        // commande CLI pour exécuter le worker en arrière-plan (Unix/macOS)
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            $cmd = escapeshellcmd($php) . ' -f ' . escapeshellarg($worker) . ' > /dev/null 2>&1 &';

            $rc = 1;
            $output = [];

            if (function_exists('exec')) {
                exec($cmd, $output, $rc);
            } elseif (function_exists('proc_open')) {
                $shCmd = '/bin/sh -c ' . escapeshellarg($cmd);
                $descriptors = [
                    0 => ['pipe', 'r'],
                    1 => ['pipe', 'w'],
                    2 => ['pipe', 'w']
                ];
                $proc = @proc_open($shCmd, $descriptors, $pipes);
                if (is_resource($proc)) {
                    foreach ($pipes as $p) { @fclose($p); }
                    $rc = @proc_close($proc);
                } else {
                    $rc = 1;
                }
            } elseif (function_exists('popen')) {
                $p = @popen($cmd, 'r');
                if ($p !== false) { pclose($p); $rc = 0; } else { $rc = 1; }
            } else {
                // Aucune fonction shell disponible -> exécution synchrone du worker (fallback)
                ob_start();
                include $worker;
                $workerOutput = ob_get_clean();
                // si le worker a déjà renvoyé JSON, on le retourne
                $decoded = json_decode($workerOutput, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    echo json_encode(['success' => true, 'message' => 'Recompute exécuté (synchrone)', 'result' => $decoded]);
                } else {
                    echo json_encode(['success' => true, 'message' => 'Recompute exécuté (synchrone)', 'output' => substr($workerOutput,0,10000)]);
                }
                exit;
            }

            if ($rc === 0) {
                echo json_encode(['success' => true, 'message' => 'Recompute déclenché en arrière-plan']);
                exit;
            } else {
                http_response_code(500);
                error_log("generate-insights.php: background exec/proc_open/popen rc={$rc} output=" . implode("\n", $output));
                echo json_encode(['success' => false, 'message' => 'Échec lancement arrière-plan', 'rc' => $rc]);
                exit;
            }
        } else {
            // Windows : tenter start /B
            $cmd = 'cmd /c start "" /B ' . escapeshellarg($php) . ' -f ' . escapeshellarg($worker);
            if (function_exists('popen')) {
                $proc = @popen($cmd, 'r');
                if ($proc !== false) { pclose($proc); echo json_encode(['success'=>true,'message'=>'Recompute déclenché en arrière-plan (Windows)']); exit; }
            }
            // fallback synchrone Windows
            ob_start();
            include $worker;
            $workerOutput = ob_get_clean();
            $decoded = json_decode($workerOutput, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                echo json_encode(['success' => true, 'message' => 'Recompute exécuté (synchrone Windows)', 'result' => $decoded]);
            } else {
                echo json_encode(['success' => true, 'message' => 'Recompute exécuté (synchrone Windows)', 'output' => substr($workerOutput,0,10000)]);
            }
            exit;
        }
    } catch (Throwable $e) {
        http_response_code(500);
        error_log("generate-insights.php exception: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Exception: '.$e->getMessage()]);
        exit;
    }
}