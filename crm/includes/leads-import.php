<?php
require_once __DIR__ . '/verify_subscriptions.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$customer_id = $_SESSION['customer_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? ($user['id'] ?? null);


function normalizeHeaderKeys(array $header): array { $n=[]; foreach($header as $k){ if($k===null){$n[]='';continue;} $s=is_string($k)?$k:(string)$k; $s=trim($s); $s=function_exists('mb_strtolower')?mb_strtolower($s,'UTF-8'):strtolower($s); $n[]=$s;} return $n; }
function readCsvRows(string $p): array { $r=[]; if(($h=@fopen($p,'r'))===false){return $r;} $head=fgetcsv($h,0,','); if($head===false){fclose($h);return $r;} $head=normalizeHeaderKeys($head); while(($d=fgetcsv($h,0,','))!==false){ if(count($d)<count($head)){$d=array_pad($d,count($head),null);} $row=[]; foreach($head as $i=>$key){ if($key==='')continue; $row[$key]=isset($d[$i])?trim((string)$d[$i]):null; } $r[]=$row; } fclose($h); return $r; }

$success_message=''; $error_message=''; $import_preview=[];

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_FILES['csv_file'])){
    $f = $_FILES['csv_file'];
    if($f['error']!==UPLOAD_ERR_OK){ $error_message='Erreur de téléchargement du fichier.'; }
    else{
        $ext=strtolower(pathinfo($f['name']??'', PATHINFO_EXTENSION));
        if($ext!=='csv'){ $error_message='Format non supporté. Utilisez CSV.'; }
        else{
            $rows = readCsvRows($f['tmp_name']);
            $import_preview = $rows;
            if(!empty($rows)){
                $skip_duplicates = isset($_POST['skip_duplicates']) && $_POST['skip_duplicates']=='1';
                $ins=0; $upd=0; $skip=0;
                foreach($rows as $row){
                    $first = trim($row['first_name'] ?? $row['prénom'] ?? '');
                    $last  = trim($row['last_name'] ?? $row['nom'] ?? '');
                    $email = trim(strtolower($row['email'] ?? ''));
                    $phone = trim($row['phone'] ?? $row['téléphone'] ?? '');
                    $company_name = trim($row['company'] ?? $row['entreprise'] ?? '');
                    $position = trim($row['position'] ?? $row['poste'] ?? '');
                    $source = trim($row['source'] ?? 'import');
                    $status = trim($row['status'] ?? 'new');
                    $budget = $row['budget'] ?? null;

                    if($first==='' || $last==='' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { $skip++; continue; }

                    // Resolve company
                    $company_id = null;
                    if($company_name!==''){
                        $s=$pdo->prepare('SELECT id FROM companies WHERE name=?' . ($customer_id ? ' AND customer_id = ?' : '') . ' LIMIT 1');
                        $s->execute($customer_id ? [$company_name, $customer_id] : [$company_name]);
                        $company_id = $s->fetchColumn();
                        if(!$company_id){ try { $pdo->prepare('INSERT INTO companies (name, customer_id) VALUES (?,?)')->execute([$company_name, $customer_id]); $company_id=(int)$pdo->lastInsertId(); } catch (Exception $e) {} }
                    }

                    // Duplicate by email
                    $stmt=$pdo->prepare('SELECT id FROM contacts WHERE email = ? LIMIT 1');
                    $stmt->execute([$email]);
                    $existingId = $stmt->fetchColumn();

                    if($existingId){
                        if($skip_duplicates){ $skip++; continue; }
                        $u=$pdo->prepare('UPDATE contacts SET first_name=?, last_name=?, phone=?, company_id=?, position=?, source=?, status=?, budget=?, customer_id=?, assigned_to=? WHERE id=?');
                        $u->execute([
                            $first,
                            $last,
                            $phone ?: null,
                            $company_id,
                            $position ?: null,
                            $source ?: null,
                            $status ?: 'new',
                            is_numeric($budget)?(float)$budget:null,
                            $customer_id,
                            $user_id,
                            $existingId
                        ]);
                        $upd++; continue;
                    }

                    $i=$pdo->prepare('INSERT INTO leads (first_name,last_name,email,phone,company_id,position,source,status,budget,customer_id,assigned_to,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())');
                    $i->execute([
                        $first,
                        $last,
                        $email,
                        $phone ?: null,
                        $company_id,
                        $position ?: null,
                        $source ?: null,
                        $status ?: 'new',
                        is_numeric($budget)?(float)$budget:null,
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