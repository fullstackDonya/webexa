<?php
require_once __DIR__ . '/verify_subscriptions.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$customer_id = $_SESSION['customer_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? ($user['id'] ?? null);

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
            $ref = (string)$c['r'];
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
    } elseif ($file['size'] > 10 * 1024 * 1024) {
        $error_message = "Le fichier dépasse la taille maximale de 10MB.";
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
            $skip_duplicates = isset($_POST['skip_duplicates']) && $_POST['skip_duplicates'] == '1';
            $inserted = 0; $updated = 0; $skipped = 0;

            foreach ($rows as $row) {
                $name = trim($row['name'] ?? $row['nom'] ?? $row['entreprise'] ?? $row['company'] ?? '');
                $email = trim(strtolower($row['email'] ?? ''));
                $phone = trim($row['phone'] ?? $row['téléphone'] ?? $row['telephone'] ?? '');
                $industry = trim($row['industry'] ?? $row['secteur'] ?? '');
                $address = trim($row['address'] ?? $row['adresse'] ?? '');
                $city = trim($row['city'] ?? $row['ville'] ?? '');
                $postal_code = trim($row['postal_code'] ?? $row['code_postal'] ?? '');
                $country = trim($row['country'] ?? $row['pays'] ?? '');
                $website = trim($row['website'] ?? $row['site_web'] ?? '');
                $employee_count = $row['employee_count'] ?? $row['nb_employes'] ?? $row['nombre_employés'] ?? null;
                $annual_revenue = $row['annual_revenue'] ?? $row['ca'] ?? $row['chiffre_affaires'] ?? null;
                $status = trim($row['status'] ?? $row['statut'] ?? '');
                $source = trim($row['source'] ?? '');
                $notes = trim($row['notes'] ?? '');

                if ($name === '') { $skipped++; continue; }
                if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { $email = ''; }

                // Find duplicate: prefer by email if present else by name
                $existingId = null;
                if ($email !== '') {
                    $stmt = $pdo->prepare("SELECT id FROM companies WHERE email = ?" . ($customer_id ? " AND customer_id = ?" : "") . " LIMIT 1");
                    $stmt->execute($customer_id ? [$email, $customer_id] : [$email]);
                    $existingId = $stmt->fetchColumn();
                }
                if (!$existingId) {
                    $stmt = $pdo->prepare("SELECT id FROM companies WHERE name = ?" . ($customer_id ? " AND customer_id = ?" : "") . " LIMIT 1");
                    $stmt->execute($customer_id ? [$name, $customer_id] : [$name]);
                    $existingId = $stmt->fetchColumn();
                }

                // Normalize optional numerics
                $employee_count_val = is_numeric($employee_count) ? (int)$employee_count : null;
                $annual_revenue_val = is_numeric($annual_revenue) ? (float)$annual_revenue : null;

                if ($existingId) {
                    if ($skip_duplicates) { $skipped++; continue; }
                    $stmt = $pdo->prepare("UPDATE companies SET name=?, email=?, phone=?, industry=?, address=?, city=?, postal_code=?, country=?, website=?, employee_count=?, annual_revenue=?, status=COALESCE(NULLIF(?,''), status), source=COALESCE(NULLIF(?,''), source), notes=COALESCE(NULLIF(?,''), notes) WHERE id=?");
                    $stmt->execute([
                        $name ?: null,
                        $email ?: null,
                        $phone ?: null,
                        $industry ?: null,
                        $address ?: null,
                        $city ?: null,
                        $postal_code ?: null,
                        $country ?: null,
                        $website ?: null,
                        $employee_count_val,
                        $annual_revenue_val,
                        $status,
                        $source,
                        $notes,
                        $existingId
                    ]);
                    // ensure ownership if customer_id is present column
                    if ($customer_id) {
                        try { $pdo->prepare("UPDATE companies SET customer_id=? WHERE id=?")->execute([$customer_id, $existingId]); } catch (Exception $e) {}
                    }
                    if ($user_id) {
                        try { $pdo->prepare("UPDATE companies SET assigned_to=? WHERE id=? AND (assigned_to IS NULL OR assigned_to=0)")->execute([$user_id, $existingId]); } catch (Exception $e) {}
                    }
                    $updated++;
                    continue;
                }

                // Insert new company
                $columns = ['name','email','phone','industry','address','city','postal_code','country','website','employee_count','annual_revenue','status','source','notes'];
                $placeholders = rtrim(str_repeat('?,', count($columns)), ',');
                $sql = "INSERT INTO companies (" . implode(',', $columns) . ") VALUES (".$placeholders.")";
                $values = [
                    $name ?: null,
                    $email ?: null,
                    $phone ?: null,
                    $industry ?: null,
                    $address ?: null,
                    $city ?: null,
                    $postal_code ?: null,
                    $country ?: null,
                    $website ?: null,
                    $employee_count_val,
                    $annual_revenue_val,
                    $status ?: null,
                    $source ?: null,
                    $notes ?: null
                ];
                try { $pdo->prepare($sql)->execute($values); } catch (Exception $e) {
                    // Fallback if some columns don't exist in older schema
                    try { $pdo->prepare("INSERT INTO companies (name,email,phone) VALUES (?,?,?)")->execute([$name ?: null, $email ?: null, $phone ?: null]); } catch (Exception $e2) { $skipped++; continue; }
                }
                $newId = (int)$pdo->lastInsertId();
                if ($customer_id) { try { $pdo->prepare("UPDATE companies SET customer_id=? WHERE id=?")->execute([$customer_id, $newId]); } catch (Exception $e) {} }
                if ($user_id) { try { $pdo->prepare("UPDATE companies SET assigned_to=? WHERE id=?")->execute([$user_id, $newId]); } catch (Exception $e) {} }
                $inserted++;
            }

            $success_message = "Import terminé : {$inserted} ajoutés, {$updated} mis à jour, {$skipped} ignorés.";
        } elseif (empty($error_message)) {
            $error_message = "Aucune donnée lisible trouvée dans le fichier.";
        }
    }
}

$page_title = "Import Clients - CRM Intelligent";