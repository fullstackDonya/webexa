<?php
require_once __DIR__ . '/verify_subscriptions.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$customer_id = $_SESSION['customer_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? ($user['id'] ?? null);

function normalizeHeaderKeys(array $header): array { $n=[]; foreach($header as $k){ if($k===null){$n[]='';continue;} $s=is_string($k)?$k:(string)$k; $s=trim($s); $s=function_exists('mb_strtolower')?mb_strtolower($s,'UTF-8'):strtolower($s); $n[]=$s;} return $n; }
function readCsvRows(string $p): array { $r=[]; if(($h=@fopen($p,'r'))===false){return $r;} $head=fgetcsv($h,0,','); if($head===false){fclose($h);return $r;} $head=normalizeHeaderKeys($head); while(($d=fgetcsv($h,0,','))!==false){ if(count($d)<count($head)){$d=array_pad($d,count($head),null);} $row=[]; foreach($head as $i=>$key){ if($key==='')continue; $row[$key]=isset($d[$i])?trim((string)$d[$i]):null; } $r[]=$row; } fclose($h); return $r; }
function xlsxColumnLettersToIndex(string $l): int { $l=strtoupper($l); $i=0; $len=strlen($l); for($x=0;$x<$len;$x++){ $i=$i*26+(ord($l[$x])-ord('A')+1);} return $i-1; }
function readXlsxRows(string $f): array { $rows=[]; if(!class_exists('ZipArchive')) return $rows; $z=new ZipArchive(); if($z->open($f)!==true) return $rows; $ss=[]; $ssXml=$z->getFromName('xl/sharedStrings.xml'); if($ssXml!==false){ $sx=@simplexml_load_string($ssXml); if($sx&&isset($sx->si)){ foreach($sx->si as $si){ if(isset($si->t)){$ss[]=(string)$si->t;} elseif(isset($si->r)){ $t=''; foreach($si->r as $run){ $t.=(string)$run->t; } $ss[]=$t; } else { $ss[]=''; } } } }
    $sheetPath='xl/worksheets/sheet1.xml'; $wb=$z->getFromName('xl/workbook.xml'); $rels=$z->getFromName('xl/_rels/workbook.xml.rels'); if($wb!==false&&$rels!==false){ $w=@simplexml_load_string($wb); $r=@simplexml_load_string($rels); if($w&&$r&&isset($w->sheets->sheet)){ $fs=$w->sheets->sheet[0]??null; if($fs){ $fs->registerXPathNamespace('r','http://schemas.openxmlformats.org/officeDocument/2006/relationships'); $rid=(string)$fs->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')->id; foreach($r->Relationship as $rel){ if((string)$rel['Id']===$rid){ $sheetPath='xl/'.ltrim((string)$rel['Target'],'/'); break; } } } } }
    $sheetXml=$z->getFromName($sheetPath); if($sheetXml===false){ foreach(['xl/worksheets/sheet1.xml','xl/worksheets/sheet01.xml'] as $fb){ $sheetXml=$z->getFromName($fb); if($sheetXml!==false) break; } } if($sheetXml===false){ $z->close(); return $rows; }
    $sx=@simplexml_load_string($sheetXml); if(!$sx){ $z->close(); return $rows; }
    $header=[]; foreach($sx->sheetData->row as $row){ $cells=[]; $max=-1; foreach($row->c as $c){ $ref=(string)$c['r']; $letters=preg_replace('/\d+/','',$ref); $idx=xlsxColumnLettersToIndex($letters); if($idx>$max){$max=$idx;} $type=(string)$c['t']; $val=''; if($type==='s'){ $id=(int)$c->v; $val=$ss[$id] ?? ''; } elseif($type==='inlineStr' && isset($c->is->t)){ $val=(string)$c->is->t; } else { $val=isset($c->v)?(string)$c->v:''; } $cells[$idx]=trim($val); } for($i=0;$i<=$max;$i++){ if(!array_key_exists($i,$cells)) $cells[$i]=null; } ksort($cells); if(empty($header)){ $header=normalizeHeaderKeys(array_values($cells)); continue; } $assoc=[]; foreach($header as $i=>$k){ if($k==='') continue; $assoc[$k]=$cells[$i] ?? null; } if(count(array_filter($assoc,fn($v)=>$v!==null && $v!==''))===0) continue; $rows[]=$assoc; }
    $z->close(); return $rows; }

$success_message = '';
$error_message = '';
$import_preview = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    if ($file['error'] !== UPLOAD_ERR_OK) { $error_message = 'Erreur de téléchargement du fichier.'; }
    else {
        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        $rows = ($ext==='csv') ? readCsvRows($file['tmp_name']) : (($ext==='xlsx') ? readXlsxRows($file['tmp_name']) : []);
        if ($ext!=='csv' && $ext!=='xlsx') { $error_message = 'Format non supporté. Utilisez CSV ou Excel (.xlsx).'; }
        $import_preview = $rows;
        if (empty($error_message) && !empty($rows)) {
            $skip_duplicates = isset($_POST['skip_duplicates']) && $_POST['skip_duplicates']=='1';
            $ins=0; $upd=0; $skip=0;
            foreach ($rows as $row) {
                $name = trim($row['name'] ?? $row['nom'] ?? '');
                $company_name = trim($row['company'] ?? $row['entreprise'] ?? $row['societe'] ?? '');
                $description = trim($row['description'] ?? '');

                if ($name === '') { $skip++; continue; }

                // Resolve company_id by name within current customer
                $company_id = null;
                if ($company_name !== '') {
                    $stmt = $pdo->prepare('SELECT id FROM companies WHERE name = ?' . ($customer_id ? ' AND customer_id = ?' : '') . ' LIMIT 1');
                    $stmt->execute($customer_id ? [$company_name, $customer_id] : [$company_name]);
                    $company_id = $stmt->fetchColumn();
                    if (!$company_id) {
                        // create company shell if missing
                        try {
                            $pdo->prepare('INSERT INTO companies (name, customer_id' . ($user_id ? ', assigned_to' : '') . ') VALUES (' . ($user_id ? '?,?,?' : '?,?') . ')')->execute($user_id ? [$company_name, $customer_id ?? null, $user_id] : [$company_name, $customer_id ?? null]);
                            $company_id = (int)$pdo->lastInsertId();
                        } catch (Exception $e) {
                            // fallback minimal insert if columns missing
                            try { $pdo->prepare('INSERT INTO companies (name) VALUES (?)')->execute([$company_name]); $company_id = (int)$pdo->lastInsertId(); } catch (Exception $e2) { $company_id = null; }
                        }
                    }
                }

                // Duplicate check by name + company_id
                $existingId = null;
                if ($company_id) {
                    $stmt = $pdo->prepare('SELECT f.id FROM folders f WHERE f.name = ? AND f.company_id = ? LIMIT 1');
                    $stmt->execute([$name, $company_id]);
                    $existingId = $stmt->fetchColumn();
                } else {
                    $stmt = $pdo->prepare('SELECT id FROM folders WHERE name = ? LIMIT 1');
                    $stmt->execute([$name]);
                    $existingId = $stmt->fetchColumn();
                }

                if ($existingId) {
                    if ($skip_duplicates) { $skip++; continue; }
                    $u = $pdo->prepare('UPDATE folders SET name = ?, company_id = COALESCE(?, company_id), description = COALESCE(NULLIF(?,\'\'), description) WHERE id = ?');
                    $u->execute([$name, $company_id, $description, $existingId]);
                    if ($user_id) { try { $pdo->prepare('UPDATE folders SET assigned_to=? WHERE id=? AND (assigned_to IS NULL OR assigned_to=0)')->execute([$user_id, $existingId]); } catch (Exception $e) {} }
                    $upd++; continue;
                }

                // Insert new folder
                $sql = 'INSERT INTO folders (company_id, assigned_to, name, description, created_at) VALUES (?,?,?,?, NOW())';
                $vals = [$company_id, $user_id, $name, $description ?: null];
                try { $pdo->prepare($sql)->execute($vals); }
                catch (Exception $e) {
                    // Fallback minimal
                    try { $pdo->prepare('INSERT INTO folders (name) VALUES (?)')->execute([$name]); } catch (Exception $e2) { $skip++; continue; }
                }
                $ins++;
            }
            $success_message = "Import terminé : {$ins} ajoutés, {$upd} mis à jour, {$skip} ignorés.";
        } elseif (empty($error_message)) {
            $error_message = 'Aucune donnée lisible trouvée dans le fichier.';
        }
    }
}

$page_title = 'Import Dossiers - CRM Intelligent';