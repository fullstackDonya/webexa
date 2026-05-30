<?php
include("verify_subscriptions.php");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$customer_id = $_SESSION['customer_id'] ?? null;
if ($customer_id === null) {
    die("Erreur : client non identifié.");
}
// Helpers pour lecture CSV/XLSX sans dépendances externes
function normalizeHeaderKeys(array $header): array {
    $normalized = [];
    foreach ($header as $key) {
        if ($key === null) { $normalized[] = ''; continue; }
        $keyString = is_string($key) ? $key : (string)$key;
        $keyString = trim($keyString);
        $keyString = function_exists('mb_strtolower') ? mb_strtolower($keyString, 'UTF-8') : strtolower($keyString);
        $normalized[] = $keyString;
    }
    return $normalized;
}

function readCsvRows(string $filePath): array {
    $rows = [];
    if (($handle = @fopen($filePath, 'r')) === false) { return $rows; }
    $header = fgetcsv($handle, 0, ",");
    if ($header === false) { fclose($handle); return $rows; }
    $header = normalizeHeaderKeys($header);
    while (($data = fgetcsv($handle, 0, ",")) !== false) {
        if (count($data) < count($header)) { $data = array_pad($data, count($header), null); }
        $row = [];
        foreach ($header as $index => $key) {
            if ($key === '') { continue; }
            $row[$key] = isset($data[$index]) ? trim((string)$data[$index]) : null;
        }
        $rows[] = $row;
    }
    fclose($handle);
    return $rows;
}

function xlsxColumnLettersToIndex(string $letters): int {
    $letters = strtoupper($letters);
    $index = 0;
    $length = strlen($letters);
    for ($i = 0; $i < $length; $i++) {
        $index = $index * 26 + (ord($letters[$i]) - ord('A') + 1);
    }
    return $index - 1; // zero-based
}

function readXlsxRows(string $filePath): array {
    $rows = [];
    if (!class_exists('ZipArchive')) { return $rows; }
    $zip = new ZipArchive();
    if ($zip->open($filePath) !== true) { return $rows; }

    // Shared strings
    $sharedStrings = [];
    $ssXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($ssXml !== false) {
        $sx = @simplexml_load_string($ssXml);
        if ($sx && isset($sx->si)) {
            foreach ($sx->si as $si) {
                if (isset($si->t)) { $sharedStrings[] = (string)$si->t; }
                elseif (isset($si->r)) { $text = ''; foreach ($si->r as $run) { $text .= (string)$run->t; } $sharedStrings[] = $text; }
                else { $sharedStrings[] = ''; }
            }
        }
    }

    // Determine first sheet path
    $sheetPath = 'xl/worksheets/sheet1.xml';
    $workbookXml = $zip->getFromName('xl/workbook.xml');
    $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
    if ($workbookXml !== false && $relsXml !== false) {
        $wb = @simplexml_load_string($workbookXml);
        $rels = @simplexml_load_string($relsXml);
        if ($wb && $rels && isset($wb->sheets->sheet)) {
            $firstSheet = $wb->sheets->sheet[0] ?? null;
            if ($firstSheet) {
                $firstSheet->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
                $rid = (string)$firstSheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')->id;
                foreach ($rels->Relationship as $rel) {
                    if ((string)$rel['Id'] === $rid) { $sheetPath = 'xl/' . ltrim((string)$rel['Target'], '/'); break; }
                }
            }
        }
    }

    $sheetXml = $zip->getFromName($sheetPath);
    if ($sheetXml === false) {
        foreach (['xl/worksheets/sheet1.xml', 'xl/worksheets/sheet01.xml'] as $fallback) {
            $sheetXml = $zip->getFromName($fallback);
            if ($sheetXml !== false) { break; }
        }
    }
    if ($sheetXml === false) { $zip->close(); return $rows; }

    $sx = @simplexml_load_string($sheetXml);
    if (!$sx) { $zip->close(); return $rows; }

    $header = [];
    foreach ($sx->sheetData->row as $row) {
        $cells = [];
        $maxIndex = -1;
        foreach ($row->c as $c) {
            $ref = (string)$c['r']; // e.g. A1
            $colLetters = preg_replace('/\d+/', '', $ref);
            $colIndex = xlsxColumnLettersToIndex($colLetters);
            if ($colIndex > $maxIndex) { $maxIndex = $colIndex; }
            $type = (string)$c['t'];
            $value = '';
            if ($type === 's') { $idx = (int)$c->v; $value = $sharedStrings[$idx] ?? ''; }
            elseif ($type === 'inlineStr' && isset($c->is->t)) { $value = (string)$c->is->t; }
            else { $value = isset($c->v) ? (string)$c->v : ''; }
            $cells[$colIndex] = trim($value);
        }
        for ($i = 0; $i <= $maxIndex; $i++) { if (!array_key_exists($i, $cells)) { $cells[$i] = null; } }
        ksort($cells);

        if (empty($header)) { $header = normalizeHeaderKeys(array_values($cells)); continue; }

        $assoc = [];
        foreach ($header as $idx => $key) {
            if ($key === '') { continue; }
            $assoc[$key] = $cells[$idx] ?? null;
        }
        if (count(array_filter($assoc, function($v){ return $v !== null && $v !== ''; })) === 0) { continue; }
        $rows[] = $assoc;
    }

    $zip->close();
    return $rows;
}

$success_message = '';
$error_message = '';
$import_preview = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_message = "Erreur de téléchargement du fichier.";
    } elseif ($file['size'] > 5 * 1024 * 1024) { // 5MB
        $error_message = "Le fichier dépasse la taille maximale de 5MB.";
    } else {
        $tmpPath = $file['tmp_name'];
        $originalName = $file['name'] ?? '';
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        $rows = [];
        if ($ext === 'csv') {
            $rows = readCsvRows($tmpPath);
        } elseif ($ext === 'xlsx') {
            $rows = readXlsxRows($tmpPath);
        } else {
            $error_message = "Format non supporté. Utilisez CSV ou Excel (.xlsx).";
        }

        $import_preview = $rows;

        if (empty($error_message) && !empty($rows)) {
            $default_source = isset($_POST['default_source']) ? trim((string)$_POST['default_source']) : 'import';
            $skip_duplicates = isset($_POST['skip_duplicates']) && $_POST['skip_duplicates'] == '1';

            $inserted = 0;
            $updated = 0;
            $skipped = 0;

            foreach ($rows as $row) {
                $first_name = trim($row['prénom'] ?? $row['first_name'] ?? '');
                $last_name  = trim($row['nom'] ?? $row['last_name'] ?? '');
                $email     = strtolower(trim($row['email'] ?? ''));
                $phone = trim($row['téléphone'] ?? $row['phone'] ?? '');
                $poste = trim($row['poste'] ?? $row['job'] ?? '');
                $source = trim($row['source'] ?? $default_source);

                if (!$first_name || !$last_name || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $skipped++;
                    continue;
                }


                // Vérifier doublon email
                $stmt = $pdo->prepare("SELECT id FROM leads WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $existingId = $stmt->fetchColumn();

                if ($existingId) {
                    if ($skip_duplicates) {
                        $skipped++;
                        continue;
                    }
                    // Mise à jour si non ignoré
                    $stmt = $pdo->prepare("
                        UPDATE leads
                        SET first_name = ?, last_name = ?, phone = ?, customer_id = ?, poste = ?, source = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $first_name,
                        $last_name,
                        $phone ?: null,
                        $customer_id,
                        $poste ?: null,
                        $source ?: null,
                        $existingId
                    ]);
                    $updated++;
                    continue;
                }

                // Insertion du contact avec customer_id
                $stmt = $pdo->prepare("
                    INSERT INTO leads
                    (first_name, last_name, email, phone, customer_id, poste, source) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $first_name,
                    $last_name,
                    $email,
                    $phone ?: null,
                    $customer_id,
                    $poste ?: null,
                    $source ?: null
                ]);
                $inserted++;
            }

            $success_message = "Import terminé : {$inserted} ajoutés, {$updated} mis à jour, {$skipped} ignorés.";
        } elseif (empty($error_message)) {
            $error_message = "Aucune donnée lisible trouvée dans le fichier.";
        }
    }
}

$page_title = "Import Contacts - CRM Intelligent";