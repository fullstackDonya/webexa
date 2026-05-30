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
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_message = 'Erreur de téléchargement du fichier.';
    } else {
        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        $rows = ($ext==='csv') ? readCsvRows($file['tmp_name']) : (($ext==='xlsx') ? readXlsxRows($file['tmp_name']) : []);
        if ($ext!=='csv' && $ext!=='xlsx') { $error_message = 'Format non supporté. Utilisez CSV ou Excel (.xlsx).'; }
        $import_preview = $rows;
        if (empty($error_message) && !empty($rows)) {
            $skip_duplicates = isset($_POST['skip_duplicates']) && $_POST['skip_duplicates']=='1';
            $default_type = $_POST['default_type'] ?? 'newsletter';
            $default_status = $_POST['default_status'] ?? 'draft';

            $ins=0; $upd=0; $skip=0;
            // Ensure base columns exist (defensive)
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS campaigns (id INT AUTO_INCREMENT PRIMARY KEY, customer_id INT NOT NULL, name VARCHAR(255), subject VARCHAR(255), created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                foreach ([
                    ["type","type ENUM('newsletter','promotional','transactional','welcome','automation') NOT NULL DEFAULT 'newsletter'"],
                    ["status","status ENUM('draft','scheduled','sent','active','paused') NOT NULL DEFAULT 'draft'"],
                    ["recipients","recipients INT DEFAULT 0"],
                    ["recipients_emails","recipients_emails TEXT NULL"],
                    ["recipients_count","recipients_count INT DEFAULT 0"],
                    ["open_rate","open_rate FLOAT DEFAULT 0"],
                    ["click_rate","click_rate FLOAT DEFAULT 0"],
                    ["audience","audience VARCHAR(255) NULL"],
                    ["scheduled_at","scheduled_at DATETIME NULL"],
                    ["sender_name","sender_name VARCHAR(150) NULL"],
                    ["sender_email","sender_email VARCHAR(150) NULL"]
                ] as $pair) {
                    [$col,$def] = $pair; $pdo->exec("ALTER TABLE campaigns ADD COLUMN IF NOT EXISTS $def");
                }
            } catch (Exception $e) { /* ignore */ }

            foreach ($rows as $row) {
                $name = trim($row['name'] ?? $row['nom'] ?? '');
                $subject = trim($row['subject'] ?? $row['objet'] ?? '');
                $type = trim($row['type'] ?? $default_type);
                $status = trim($row['status'] ?? $row['statut'] ?? $default_status);
                $scheduled_at = trim($row['scheduled_at'] ?? $row['date_programmation'] ?? '');
                $sender_name = trim($row['sender_name'] ?? $row['expediteur_nom'] ?? '');
                $sender_email = trim($row['sender_email'] ?? $row['expediteur_email'] ?? '');
                $audience = trim($row['audience'] ?? '');
                $recipients_emails_raw = trim($row['recipients_emails'] ?? $row['destinataires'] ?? '');

                if ($name === '') { $skip++; continue; }

                // find duplicate by name scoped to customer
                $stmt = $pdo->prepare('SELECT id FROM campaigns WHERE name = ?' . ($customer_id ? ' AND customer_id = ?' : '') . ' LIMIT 1');
                $stmt->execute($customer_id ? [$name, $customer_id] : [$name]);
                $existingId = $stmt->fetchColumn();

                $emails_json = null; $count = 0;
                if ($recipients_emails_raw !== '') {
                    $list = preg_split('/[\r\n,;]+/', $recipients_emails_raw);
                    $emails = [];
                    foreach ($list as $e) { $e = trim(strtolower($e)); if ($e && filter_var($e, FILTER_VALIDATE_EMAIL)) { $emails[] = $e; } }
                    $emails = array_values(array_unique($emails));
                    $count = count($emails);
                    $emails_json = $count ? json_encode($emails, JSON_UNESCAPED_UNICODE) : null;
                }

                if ($existingId) {
                    if ($skip_duplicates) { $skip++; continue; }
                    $updStmt = $pdo->prepare('UPDATE campaigns SET subject=?, type=?, status=?, scheduled_at=?, sender_name=?, sender_email=?, audience=?, recipients=?, recipients_emails=?, recipients_count=? WHERE id=?');
                    $updStmt->execute([
                        $subject ?: null,
                        $type ?: null,
                        $status ?: 'draft',
                        $scheduled_at ?: null,
                        $sender_name ?: null,
                        $sender_email ?: null,
                        $audience ?: null,
                        $count,
                        $emails_json,
                        $count,
                        $existingId
                    ]);
                    $upd++; continue;
                }

                $insStmt = $pdo->prepare('INSERT INTO campaigns (customer_id,name,subject,type,status,recipients,recipients_emails,recipients_count,open_rate,click_rate,scheduled_at,sender_name,sender_email,audience) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
                $insStmt->execute([
                    $customer_id ?? 0,
                    $name,
                    $subject ?: null,
                    $type ?: 'newsletter',
                    $status ?: 'draft',
                    $count,
                    $emails_json,
                    $count,
                    0.0,
                    0.0,
                    $scheduled_at ?: null,
                    $sender_name ?: null,
                    $sender_email ?: null,
                    $audience ?: null
                ]);
                $ins++;
            }

            $success_message = "Import terminé : {$ins} ajoutés, {$upd} mis à jour, {$skip} ignorés.";
        } else if (empty($error_message)) {
            $error_message = 'Aucune donnée lisible trouvée dans le fichier.';
        }
    }
}

$page_title = 'Import Campagnes - CRM Intelligent';