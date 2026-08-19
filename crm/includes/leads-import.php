<?php
require_once __DIR__ . '/verify_subscriptions.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$customer_id = $_SESSION['customer_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? ($user['id'] ?? null);

// Support pour traiter Excel, Word, PDF
function convertFileToRows($filepath, $filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if ($ext === 'csv') {
        return readCsvRows($filepath);
    } 
    elseif (in_array($ext, ['xlsx', 'xls'])) {
        return readExcelRows($filepath);
    }
    elseif (in_array($ext, ['pdf'])) {
        return readPdfRows($filepath);
    }
    elseif (in_array($ext, ['docx', 'doc'])) {
        return readWordRows($filepath);
    }
    
    return [];
}

// Traiter Excel avec regex/simple parsing si PhpSpreadsheet indisponible
function readExcelRows($filepath) {
    $rows = [];
    
    // Essayer avec PhpSpreadsheet si disponible
    if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filepath);
            $worksheet = $spreadsheet->getActiveSheet();
            $header = null;
            
            foreach ($worksheet->getRowIterator() as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                $data = [];
                
                foreach ($cellIterator as $cell) {
                    $data[] = $cell->getValue();
                }
                
                if ($header === null) {
                    $header = normalizeHeaderKeys($data);
                } else {
                    $row_data = [];
                    foreach ($header as $i => $key) {
                        if ($key === '') continue;
                        $row_data[$key] = isset($data[$i]) ? trim((string)$data[$i]) : null;
                    }
                    if (array_filter($row_data)) { // Skip empty rows
                        $rows[] = $row_data;
                    }
                }
            }
            return $rows;
        } catch (Exception $e) {
            error_log("Excel parse error: " . $e->getMessage());
            return [];
        }
    }
    
    // Fallback: Excel est un ZIP contenant XML
    return [];
}

// Traiter PDF (extraction de texte simple)
function readPdfRows($filepath) {
    $rows = [];
    
    // Essayer avec extraction PDF si disponible
    if (function_exists('pdf_get_info') || class_exists('Smalot\PdfParser\Parser')) {
        try {
            // Tentative avec Smalot PdfParser
            if (class_exists('Smalot\PdfParser\Parser')) {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($filepath);
                $text = $pdf->getText();
                
                // Parser le texte en lignes (très simplifié)
                $lines = explode("\n", $text);
                $header = null;
                
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line)) continue;
                    
                    // Séparer par tabulation ou espaces multiples
                    $parts = preg_split('/\s{2,}|\t/', $line);
                    $parts = array_map('trim', $parts);
                    
                    if ($header === null && count($parts) > 2) {
                        $header = normalizeHeaderKeys($parts);
                    } elseif ($header !== null && count($parts) >= count($header)) {
                        $row_data = [];
                        foreach ($header as $i => $key) {
                            if ($key === '') continue;
                            $row_data[$key] = isset($parts[$i]) ? trim((string)$parts[$i]) : null;
                        }
                        if (array_filter($row_data)) {
                            $rows[] = $row_data;
                        }
                    }
                }
                return $rows;
            }
        } catch (Exception $e) {
            error_log("PDF parse error: " . $e->getMessage());
        }
    }
    
    return [];
}

// Traiter Word (docx est aussi un ZIP contenant XML)
function readWordRows($filepath) {
    $rows = [];
    
    if (class_exists('PhpOffice\PhpWord\IOFactory')) {
        try {
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($filepath);
            $fullText = '';
            
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if ($element instanceof \PhpOffice\PhpWord\Element\Table) {
                        // Parser les tableaux
                        foreach ($element->getRows() as $rowIdx => $row) {
                            $rowData = [];
                            foreach ($row->getCells() as $cell) {
                                $rowData[] = trim($cell->getText());
                            }
                            
                            if ($rowIdx === 0) {
                                $header = normalizeHeaderKeys($rowData);
                            } else {
                                $row_obj = [];
                                foreach ($header as $i => $key) {
                                    if ($key === '') continue;
                                    $row_obj[$key] = isset($rowData[$i]) ? trim((string)$rowData[$i]) : null;
                                }
                                if (array_filter($row_obj)) {
                                    $rows[] = $row_obj;
                                }
                            }
                        }
                    } else {
                        // Texte ordinaire
                        if (method_exists($element, 'getText')) {
                            $fullText .= $element->getText() . "\n";
                        }
                    }
                }
            }
            
            return $rows;
        } catch (Exception $e) {
            error_log("Word parse error: " . $e->getMessage());
        }
    }
    
    return [];
}

// Auto-migration: Créer les colonnes manquantes
function ensureLeadsColumnsExist($pdo) {
    try {
        $columns_to_add = [
            'phone2' => "ALTER TABLE leads ADD COLUMN phone2 VARCHAR(20) AFTER phone",
            'phone3' => "ALTER TABLE leads ADD COLUMN phone3 VARCHAR(20) AFTER phone2",
            'sector' => "ALTER TABLE leads ADD COLUMN sector VARCHAR(100) AFTER company_id",
            'opening_hours' => "ALTER TABLE leads ADD COLUMN opening_hours VARCHAR(255) AFTER sector",
            'website' => "ALTER TABLE leads ADD COLUMN website VARCHAR(255) AFTER opening_hours",
            'address' => "ALTER TABLE leads ADD COLUMN address VARCHAR(500) AFTER website",
            'tags' => "ALTER TABLE leads ADD COLUMN tags VARCHAR(255) AFTER address",
            'description' => "ALTER TABLE leads ADD COLUMN description LONGTEXT AFTER tags",
        ];

        // Vérifier quelles colonnes existent
        $result = $pdo->query("SHOW COLUMNS FROM leads");
        $existing = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $existing[] = $row['Field'];
        }

        // Créer les colonnes manquantes
        foreach ($columns_to_add as $col => $sql) {
            if (!in_array($col, $existing)) {
                try {
                    $pdo->exec($sql);
                } catch (Exception $e) {
                    error_log("Migration leads[$col]: " . $e->getMessage());
                }
            }
        }

        // Ajouter colonnes à companies si manquantes
        try {
            $result = $pdo->query("SHOW COLUMNS FROM companies");
            $existing_co = [];
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $existing_co[] = $row['Field'];
            }

           
        } catch (Exception $e) {
            error_log("Migration companies failed: " . $e->getMessage());
        }
    } catch (Exception $e) {
        error_log("Migration leads failed: " . $e->getMessage());
    }
}

// Appeler la migration
ensureLeadsColumnsExist($pdo);


function normalizeHeaderKeys(array $header): array { $n=[]; foreach($header as $k){ if($k===null){$n[]='';continue;} $s=is_string($k)?$k:(string)$k; $s=trim($s); $s=function_exists('mb_strtolower')?mb_strtolower($s,'UTF-8'):strtolower($s); $n[]=$s;} return $n; }
function readCsvRows(string $p): array
{
    $rows = [];

    if (($h = @fopen($p, 'r')) === false) {
        return $rows;
    }

    // Détecter le séparateur
    $firstLine = fgets($h);
    if ($firstLine === false) {
        fclose($h);
        return $rows;
    }

    // Supprimer le BOM UTF-8
    $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine);

    $delimiters = [
        ';'  => substr_count($firstLine, ';'),
        ','  => substr_count($firstLine, ','),
        "\t" => substr_count($firstLine, "\t"),
    ];

    arsort($delimiters);
    $delimiter = array_key_first($delimiters);

    rewind($h);

    $head = fgetcsv($h, 0, $delimiter);
    if ($head === false) {
        fclose($h);
        return $rows;
    }

    // Supprimer le BOM de la première colonne
    if (isset($head[0])) {
        $head[0] = preg_replace('/^\xEF\xBB\xBF/', '', $head[0]);
    }

    $head = normalizeHeaderKeys($head);

    while (($data = fgetcsv($h, 0, $delimiter)) !== false) {

        if (count($data) < count($head)) {
            $data = array_pad($data, count($head), null);
        }

        $row = [];

        foreach ($head as $i => $key) {
            if ($key === '') {
                continue;
            }

            $row[$key] = isset($data[$i]) ? trim((string)$data[$i]) : null;
        }

        if (array_filter($row)) {
            $rows[] = $row;
        }
    }

    fclose($h);

    return $rows;
}
$success_message=''; $error_message=''; $import_preview=[];

function cleanPhone($phone){
        if (empty($phone)) {
            return null;
        }

        // Garder uniquement chiffres et +
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // Limiter à une longueur raisonnable
        if (strlen($phone) > 20) {
            $phone = substr($phone, 0, 20);
        }

        return $phone ?: null;
}


if($_SERVER['REQUEST_METHOD']==='POST' && isset($_FILES['csv_file'])){
    $f = $_FILES['csv_file'];
    if($f['error']!==UPLOAD_ERR_OK){ $error_message='Erreur de téléchargement du fichier.'; }
    else{
        $ext=strtolower(pathinfo($f['name']??'', PATHINFO_EXTENSION));
        $allowed_formats = ['csv', 'xlsx', 'xls', 'pdf', 'docx', 'doc'];
        
        if(!in_array($ext, $allowed_formats)){ 
            $error_message='Format non supporté. Utilisez: CSV, Excel (XLSX/XLS), Word (DOCX/DOC) ou PDF.'; 
        }
        else{
            $rows = convertFileToRows($f['tmp_name'], $f['name']);
            $import_preview = $rows;
            
            if(!empty($rows)){
                $skip_duplicates = isset($_POST['skip_duplicates']) && $_POST['skip_duplicates']=='1';
                $ins=0; $upd=0; $skip=0;
                foreach($rows as $row){
                    // Extraire tous les champs (flexibles)
                    $first = trim($row['first_name'] ?? $row['prénom'] ?? '');
                    $last  = trim($row['last_name'] ?? $row['nom'] ?? '');
                    $email = !empty($row['email']) ? trim(strtolower($row['email'])) : '';
                    // $phone1 = trim($row['phone'] ?? $row['téléphone'] ?? $row['phone1'] ?? $row['téléphone1'] ?? '');
                    // $phone2 = trim($row['phone2'] ?? $row['téléphone2'] ?? '');
                    // $phone3 = trim($row['phone3'] ?? $row['téléphone3'] ?? '');

             

                    $phone1 = cleanPhone(
                        $row['phone']
                        ?? $row['téléphone']
                        ?? $row['phone1']
                        ?? $row['téléphone1']
                        ?? ''
                    );

                    $phone2 = cleanPhone(
                        $row['phone2']
                        ?? $row['téléphone2']
                        ?? ''
                    );

                    $phone3 = cleanPhone(
                        $row['phone3']
                        ?? $row['téléphone3']
                        ?? ''
                    );
                   $company_name = trim($row['company'] ?? $row['entreprise'] ?? '');
                   
                   $company_display_name = trim(
                        $row['display_name'] 
                        ?? $row['nom_societe'] 
                        ?? $row['company_display'] 
                        ?? $row['entreprise'] 
                        ?? $row['company'] 
                        ?? ''
                    );
                    $position = trim($row['position'] ?? $row['poste'] ?? '');
                    $source_raw = strtolower(trim($row['source'] ?? $row['url'] ?? ''));

                    $source_mapping = [
                        'google' => 'website',
                        'site web' => 'website',
                        'website' => 'website',
                        'linkedin' => 'social_media',
                        'facebook' => 'social_media',
                        'instagram' => 'social_media',
                        'référence' => 'referral',
                        'referral' => 'referral',
                        'salon' => 'event',
                        'event' => 'event',
                        'direct' => 'direct',
                        'page jaune' => 'page_jaune'
                    ];

                    $source = $source_mapping[$source_raw] ?? 'direct';
                    $status = trim($row['status'] ?? 'new');
                    $budget = $row['budget'] ?? null;
                    $secteur = trim($row['secteur'] ?? $row['secteur_activite'] ?? $row['activité'] ?? $row['activity'] ?? '');
                    $heures_ouverture = trim($row['heures_ouverture'] ?? $row['horaires'] ?? $row['hours'] ?? $row['bi-hours'] ?? $row['bi_hours'] ?? '');
                    $url = trim($row['url'] ?? $row['website'] ?? $row['site'] ?? '');
                    $address = trim($row['address'] ?? $row['adresse'] ?? '');
                    $tags = trim($row['tags'] ?? $row['tag'] ?? '');
                    $description = trim($row['description'] ?? $row['notes'] ?? '');

                    // Validation flexible: au minimum un identifiant (email OU téléphone OU nom+entreprise)
                    $has_email = !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL);
                    $has_phone = !empty($phone1);
                    $has_name_and_company = !empty($first) && !empty($company_name);
                    $has_any_name = !empty($first) || !empty($last);

                    if(!$has_email && !$has_phone && !$has_name_and_company && !$has_any_name) { 
                        $skip++; 
                        continue; 
                    }

                    // Resolve company
                    $company_id = null;

                    if ($company_name !== '') {

                        $s = $pdo->prepare(
                            'SELECT id FROM companies WHERE name = ?' .
                            ($customer_id ? ' AND customer_id = ?' : '') .
                            ' LIMIT 1'
                        );

                        $s->execute(
                            $customer_id ? [$company_name, $customer_id] : [$company_name]
                        );

                        $company_id = $s->fetchColumn();

                        // Si entreprise inexistante -> création
                        if (!$company_id) {

                            try {

                                $insertCompany = $pdo->prepare(
                                    'INSERT INTO companies 
                                    (name, industry, customer_id, website, address, source, notes, phone, assigned_to) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?,?,?)'
                                );

                                $insertCompany->execute([
                                    $company_name,
                                    $secteur ?: null,
                                    $customer_id,
                                    $url ?: null,
                                    $address ?: null,
                                    $source ?: 'import',
                                    $description ?: null,
                                    $phone1 ?: null,
                                    $user_id

                                ]);

                                $company_id = (int)$pdo->lastInsertId();

                            } catch (Exception $e) {

                                error_log("Erreur création company : " . $e->getMessage());

                                $company_id = null;
                            }
                        }
                    }
                    // Vérifier duplicata par email si email existe
                    $existingId = null;
                    if($has_email){
                        $stmt=$pdo->prepare('SELECT id FROM leads WHERE email = ? LIMIT 1');
                        $stmt->execute([$email]);
                        $existingId = $stmt->fetchColumn();
                    }

                    if($existingId){
                        if($skip_duplicates){ $skip++; continue; }
                        // Mise à jour du lead existant
                        $u=$pdo->prepare('UPDATE leads SET first_name=?, last_name=?, phone=?, phone2=?, phone3=?, company_id=?, position=?, source=?, status=?, budget=?, sector=?, opening_hours=?, website=?, address=?, tags=?, description=?, customer_id=?, assigned_to=? WHERE id=?');
                        $u->execute([
                            $first ?: null,
                            $last ?: null,
                            $phone1 ?: null,
                            $phone2 ?: null,
                            $phone3 ?: null,
                            $company_id,
                            $position ?: null,
                            $source ?: 'import',
                            $status ?: 'new',
                            is_numeric($budget)?(float)$budget:null,
                            $secteur ?: null,
                            $heures_ouverture ?: null,
                            $url ?: null,
                            $address ?: null,
                            $tags ?: null,
                            $description ?: null,
                            $customer_id,
                            $user_id,
                            $existingId
                        ]);
                        $upd++; 
                        continue;
                    }

                    // Nouveau lead
                    $i=$pdo->prepare('INSERT INTO leads (first_name,last_name,email,phone,phone2,phone3,company_id,position,source,status,budget,sector,opening_hours,website,address,tags,description,customer_id,assigned_to,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())');
                    $i->execute([
                        $first ?: null,
                        $last ?: null,
                        $email ?: null,
                        $phone1 ?: null,
                        $phone2 ?: null,
                        $phone3 ?: null,
                        $company_id,
                        $position ?: null,
                        $source ?: 'import',
                        $status ?: 'new',
                        is_numeric($budget)?(float)$budget:null,
                        $secteur ?: null,
                        $heures_ouverture ?: null,
                        $url ?: null,
                        $address ?: null,
                        $tags ?: null,
                        $description ?: null,
                        $customer_id,
                        $user_id
                    ]);
                    $ins++;
                }
                $success_message = "Import terminé : {$ins} ajoutés, {$upd} mis à jour, {$skip} ignorés.";
            } else {
                $error_message = 'Aucune donnée lisible trouvée dans le fichier.';
            }
        }
    }
}

$page_title = 'Import Leads - CRM Intelligent';