<?php
/**
 * API pour le scanner de documents comptables
 * Gestion de l'upload, analyse et traitement de documents (factures, reçus, etc.)
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$logDir = __DIR__ . '/../../logs';
if (!file_exists($logDir)) {
    @mkdir($logDir, 0755, true);
}
ini_set('error_log', $logDir . '/document_scanner_errors.log');

// Capturer toutes les erreurs
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error [$errno]: $errstr in $errfile:$errline");
    return false;
});

session_start();
header('Content-Type: application/json');

// Nettoyer tout buffer de sortie avant de commencer
if (ob_get_level()) {
    ob_clean();
}

require_once __DIR__ . '/../../crm/config/database.php';
require_once __DIR__ . '/../../crm/includes/auth.php';
require_once __DIR__ . '/DocumentAIExtractor.php';

// Configuration OpenAI (optionnel - laissez vide pour utiliser seulement pdftotext gratuit)
define('OPENAI_API_KEY', getenv('OPENAI_API_KEY') ?: ''); // Mettre votre clé ici si vous voulez l'IA

// Vérifier l'authentification
if (!isAuthenticated()) {
    error_log("Document scanner: User not authenticated");
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

$customer_id = $_SESSION['customer_id'] ?? null;
if (!$customer_id) {
    error_log("Document scanner: customer_id not found in session");
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'customer_id introuvable']);
    exit;
}

$action = $_GET['action'] ?? '';
error_log("Document scanner: action=$action, method=" . $_SERVER['REQUEST_METHOD']);

try {
    switch ($action) {
        case 'upload':
            handleUpload($pdo, $customer_id);
            break;
            
        case 'details':
            handleDetails($pdo, $customer_id);
            break;
            
        case 'reprocess':
            handleReprocess($pdo, $customer_id);
            break;
            
        case 'delete':
            handleDelete($pdo, $customer_id);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Action invalide']);
            break;
    }
} catch (Exception $e) {
    error_log("Document scanner error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

/**
 * Upload et analyse de documents
 */
function handleUpload($pdo, $customer_id) {
    // Vérifier les limites d'upload PHP
    $maxUploadSize = ini_get('upload_max_filesize');
    $maxPostSize = ini_get('post_max_size');
    
    // Log pour debug
    error_log("Upload called - Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'undefined'));
    error_log("PHP limits - upload_max_filesize: $maxUploadSize, post_max_size: $maxPostSize");
    error_log("FILES count: " . count($_FILES));
    error_log("FILES keys: " . implode(', ', array_keys($_FILES)));
    
    if (empty($_FILES) || !isset($_FILES['files'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'error' => 'Aucun fichier reçu',
            'debug' => [
                'files_array' => array_keys($_FILES),
                'post_vars' => array_keys($_POST),
                'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 0,
                'max_upload' => $maxUploadSize,
                'max_post' => $maxPostSize
            ]
        ]);
        return;
    }
    
    $uploadDir = __DIR__ . '/../../uploads/scanned_documents/';
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Impossible de créer le dossier d\'upload']);
            return;
        }
    }
    
    // Vérifier les permissions d'écriture
    if (!is_writable($uploadDir)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Le dossier d\'upload n\'est pas accessible en écriture']);
        return;
    }
    
    $uploadedCount = 0;
    $errors = [];
    
    // Gérer plusieurs fichiers - normaliser la structure
    $files = $_FILES['files'];
    
    // Si c'est un tableau de fichiers (files[])
    if (is_array($files['name'])) {
        $fileCount = count($files['name']);
        $normalizedFiles = [];
        
        for ($i = 0; $i < $fileCount; $i++) {
            $normalizedFiles[] = [
                'name' => $files['name'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'size' => $files['size'][$i],
                'error' => $files['error'][$i],
                'type' => $files['type'][$i] ?? ''
            ];
        }
    } else {
        // Un seul fichier
        $normalizedFiles = [
            [
                'name' => $files['name'],
                'tmp_name' => $files['tmp_name'],
                'size' => $files['size'],
                'error' => $files['error'],
                'type' => $files['type'] ?? ''
            ]
        ];
    }
    
    foreach ($normalizedFiles as $file) {
        try {
            $fileName = $file['name'];
            $fileTmpName = $file['tmp_name'];
            $fileSize = $file['size'];
            $fileError = $file['error'];
            
            if ($fileError !== UPLOAD_ERR_OK) {
                $errors[] = "Erreur upload ($fileError): $fileName";
                error_log("Upload error for $fileName: code $fileError");
                continue;
            }
            
            // Vérifier que le fichier temporaire existe
            if (!file_exists($fileTmpName)) {
                $errors[] = "Fichier temporaire introuvable: $fileName";
                error_log("Temp file not found: $fileTmpName");
                continue;
            }
        
        // Vérifier le type de fichier
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fileTmpName);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            $errors[] = "Type de fichier non supporté: $fileName";
            continue;
        }
        
        // Générer un nom de fichier unique
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $uniqueName = uniqid('doc_') . '_' . time() . '.' . $extension;
        $filePath = $uploadDir . $uniqueName;
        
        if (move_uploaded_file($fileTmpName, $filePath)) {
            // Analyser le document avec l'IA hybride
            $aiExtractor = new DocumentAIExtractor(OPENAI_API_KEY);
            $analysisResult = $aiExtractor->extractDocument($filePath, $mimeType);
            
            error_log("=== EXTRACTION RESULT ===");
            error_log("Method: " . ($analysisResult['method'] ?? 'unknown'));
            error_log("Type: " . ($analysisResult['type'] ?? 'null'));
            error_log("Amount: " . ($analysisResult['amount'] ?? 'null'));
            error_log("Date: " . ($analysisResult['date'] ?? 'null'));
            error_log("Confidence: " . ($analysisResult['confidence'] ?? '0') . "%");
            error_log("Cost: " . ($analysisResult['cost'] ?? '0') . "€");
            error_log("Data fields: " . count($analysisResult['data'] ?? []));
            if (!empty($analysisResult['data'])) {
                error_log("Data keys: " . implode(', ', array_keys($analysisResult['data'])));
            }
            error_log("======================");
            
            //Enregistrer dans la base de données (colonnes corrigées)
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO erp_scanned_documents (
                        customer_id, filename, file_path, file_type,
                        file_size, document_type, amount,
                        document_date, extracted_data, status, processed_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'processed', NOW())
                ");
                
                $result = $stmt->execute([
                    $customer_id,
                    $fileName,  // filename = nom original
                    'uploads/scanned_documents/' . $uniqueName,
                    $extension,  // file_type = extension
                    $fileSize,
                    $analysisResult['type'] ?? 'other',
                    $analysisResult['amount'] ?? null,
                    $analysisResult['date'] ?? null,
                    json_encode($analysisResult['data'] ?? [])
                ]);
                
                if ($result) {
                    $uploadedCount++;
                    error_log("Document uploaded successfully: $fileName");
                } else {
                    $errors[] = "Erreur base de données: $fileName";
                    error_log("Database insert failed for $fileName: " . json_encode($stmt->errorInfo()));
                }
            } catch (PDOException $e) {
                $errors[] = "Erreur SQL: " . $e->getMessage();
                error_log("PDO Exception for $fileName: " . $e->getMessage());
            }
        } else {
            $errors[] = "Impossible de sauvegarder: $fileName";
            error_log("Failed to move uploaded file: $fileTmpName to $filePath");
        }
        
        } catch (Exception $e) {
            $errors[] = "Erreur traitement $fileName: " . $e->getMessage();
            error_log("Error processing $fileName: " . $e->getMessage());
        }
    }
    
    if ($uploadedCount > 0) {
        echo json_encode([
            'success' => true,
            'count' => $uploadedCount,
            'errors' => $errors
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Aucun fichier n\'a pu être uploadé',
            'details' => $errors
        ]);
    }
}

/**
 * Analyser un document pour extraire les informations
 */
function analyzeDocument($filePath, $mimeType) {
    // Types valides ENUM: 'invoice', 'receipt', 'bank_statement', 'payslip', 'contract', 'other'
    $result = [
        'type' => 'other',  // Valeur par défaut valide
        'amount' => null,
        'date' => null,
        'data' => []
    ];
    
    error_log("=== ANALYZE DOCUMENT START ===");
    error_log("File: " . basename($filePath));
    error_log("MIME: $mimeType");
    
    try {
        // Pour les images, utiliser OCR (Tesseract) si disponible
        if (strpos($mimeType, 'image/') === 0) {
            error_log("Processing IMAGE document");
            // Extraction basique depuis le nom de fichier
            $filename = basename($filePath);
            if (preg_match('/facture|invoice/i', $filename)) {
                $result['type'] = 'invoice';
            } elseif (preg_match('/recu|receipt|ticket/i', $filename)) {
                $result['type'] = 'receipt';
            } elseif (preg_match('/releve|bank|statement/i', $filename)) {
                $result['type'] = 'bank_statement';
            } elseif (preg_match('/contrat|contract/i', $filename)) {
                $result['type'] = 'contract';
            }
            
            // Tenter l'OCR pour extraire le texte de l'image
            $text = extractTextFromImage($filePath);
            
            if (!empty($text)) {
                // Si OCR réussi, améliorer la détection du type
                if (preg_match('/facture|invoice/i', $text)) {
                    $result['type'] = 'invoice';
                } elseif (preg_match('/reçu|receipt|ticket/i', $text)) {
                    $result['type'] = 'receipt';
                } elseif (preg_match('/relevé|releve|bank.*statement/i', $text)) {
                    $result['type'] = 'bank_statement';
                }
                
                // Extraire les données depuis le texte OCR (mêmes patterns que PDF)
                extractDataFromText($text, $result);
            }
        }
        
        // Pour les PDF
        if ($mimeType === 'application/pdf') {
            error_log("Processing PDF document");
            $result['type'] = 'other';  // Par défaut pour PDF
            
            // Essayer d'extraire le texte avec pdftotext si disponible
            $textFile = $filePath . '.txt';
            $command = "pdftotext " . escapeshellarg($filePath) . " " . escapeshellarg($textFile) . " 2>&1";
            exec($command, $output, $returnCode);
            
            error_log("pdftotext command: $command");
            error_log("pdftotext return code: $returnCode");
            
            if ($returnCode === 0 && file_exists($textFile)) {
                $text = file_get_contents($textFile);
                unlink($textFile); // Supprimer le fichier temporaire
                
                error_log("PDF text extracted: " . strlen($text) . " chars");
                error_log("First 500 chars: " . substr($text, 0, 500));
                
                // Détecter le type de document (valeurs ENUM valides uniquement)
                if (preg_match('/facture|invoice/i', $text)) {
                    $result['type'] = 'invoice';
                } elseif (preg_match('/reçu|receipt|ticket/i', $text)) {
                    $result['type'] = 'receipt';
                } elseif (preg_match('/devis|quote|proforma/i', $text)) {
                    $result['type'] = 'invoice';  // Devis considéré comme facture
                } elseif (preg_match('/relevé|releve|bank.*statement|statement.*bank/i', $text)) {
                    $result['type'] = 'bank_statement';
                } elseif (preg_match('/bulletin.*paie|paie|payslip|salary/i', $text)) {
                    $result['type'] = 'payslip';
                } elseif (preg_match('/contrat|contract|agreement/i', $text)) {
                    $result['type'] = 'contract';
                }
                // Sinon reste 'other'
                
                error_log("Document type detected: " . $result['type']);
                
                // Extraire toutes les données du texte
                extractDataFromText($text, $result);
            } else {
                error_log("pdftotext failed or text file not found");
                error_log("Output: " . implode("\n", $output));
            }
        }
        
    } catch (Exception $e) {
        error_log("Document analysis error: " . $e->getMessage());
    }
    
    error_log("Analysis result - Type: " . $result['type'] . ", Amount: " . ($result['amount'] ?? 'null') . ", Date: " . ($result['date'] ?? 'null'));
    error_log("Extracted data fields: " . count($result['data']));
    error_log("=== ANALYZE DOCUMENT END ===");
    
    return $result;
}

/**
 * Extraire toutes les données d'un texte (PDF ou OCR)
 */
function extractDataFromText($text, &$result) {
    error_log(">>> extractDataFromText START");
    
    try {
        // === EXTRACTION DES MONTANTS ===
        
        // Total TTC (prioritaire) - Patterns multiples pour plus de flexibilité
        $amountPatterns = [
            '/(?:total\s*ttc|total\s*général|net\s*à\s*payer|amount\s*due|total|montant)[\s:]*([0-9]+[\s,.]?[0-9]*[,.]?[0-9]{0,2})\s*(?:€|EUR|euros?)?/iu',
            '/([0-9]{1,6}[,.\s][0-9]{2})\s*€/u',  // Pattern simple : 123,45 €
            '/total.*?([0-9]+[,.\s][0-9]{2})/iu'   // Pattern flexible
        ];
        
        foreach ($amountPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $amount = $matches[1];
                // Nettoyer le montant (enlever espaces, remplacer virgule par point)
                $amount = str_replace([' ', "\u{00A0}"], '', $amount); // Enlever espaces normaux et insécables
                $amount = str_replace(',', '.', $amount);
                // Si plusieurs points, garder seulement le dernier
                $dotCount = substr_count($amount, '.');
                if ($dotCount > 1) {
                    $amount = str_replace('.', '', $amount);
                    $lastDotPos = strrpos($amount, '.');
                    if ($lastDotPos === false) {
                        // Ajouter le point pour les centimes
                        if (strlen($amount) > 2) {
                            $amount = substr($amount, 0, -2) . '.' . substr($amount, -2);
                        }
                    }
                }
                
                $result['amount'] = (float)$amount;
                $result['data']['total_ttc'] = $result['amount'];
                error_log(">>> Amount found: " . $result['amount'] . " (from: " . $matches[0] . ")");
                break; // On arrête à la première correspondance
            }
        }
        
        // Total HT
        if (preg_match('/(?:total\s*ht|montant\s*ht|sous-total)[\s:]*([0-9]+[\s,.]?[0-9]*[,.]?[0-9]{0,2})\s*(?:€|EUR)?/iu', $text, $matches)) {
            $amount = str_replace([' ', ',', "\u{00A0}"], ['', '.', ''], $matches[1]);
            $result['data']['total_ht'] = (float)$amount;
            error_log(">>> HT found: " . $result['data']['total_ht']);
        }
        
        // TVA
        if (preg_match('/(?:tva|vat|taxe)[\s:]*([0-9]+[,.]?[0-9]*)\s*€/iu', $text, $matches)) {
            $result['data']['tva'] = (float)str_replace(',', '.', $matches[1]);
            error_log(">>> TVA found: " . $result['data']['tva']);
        }
        
        // Taux de TVA
        if (preg_match('/(?:tva|vat)[\s@:]*([0-9]+[,.]?[0-9]*)\s*%/iu', $text, $matches)) {
            $result['data']['taux_tva'] = (float)str_replace(',', '.', $matches[1]);
        }
        
        // === EXTRACTION DES DATES ===
        
        // Patterns de dates multiples pour plus de flexibilité
        $datePatterns = [
            '/(?:date|émis\s*le|issued|le)[\s:]*([0-9]{1,2}[\/\-\.][0-9]{1,2}[\/\-\.][0-9]{2,4})/iu',
            '/([0-9]{1,2}[\/\-\.][0-9]{1,2}[\/\-\.][0-9]{2,4})/u'  // Pattern générique
        ];
        
        foreach ($datePatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $dateConverted = convertToMySQLDate($matches[1]);
                if ($dateConverted) {
                    $result['date'] = $dateConverted;
                    $result['data']['date_document'] = $matches[1];
                    error_log(">>> Date found: " . $result['date'] . " (from: " . $matches[1] . ")");
                    break;
                }
            }
        }
        
        // Date d'échéance
        if (preg_match('/(?:échéance|due\s*date|à\s*payer\s*avant)[\s:]*([0-9]{1,2}[\/\-\.][0-9]{1,2}[\/\-\.][0-9]{2,4})/iu', $text, $matches)) {
            $result['data']['date_echeance'] = $matches[1];
        }
        
        // === EXTRACTION DES NUMÉROS ===
        
        // Numéro de facture/reçu
        if (preg_match('/(?:facture|invoice|reçu|receipt|ticket)\s*n[°o#]?\s*:?\s*([A-Z0-9\-_\/]+)/iu', $text, $matches)) {
            $result['data']['numero_document'] = trim($matches[1]);
            error_log(">>> Document number found: " . $result['data']['numero_document']);
        }
        
        // Numéro de commande
        if (preg_match('/(?:commande|order|bon\s*de\s*commande)\s*n[°o#]?\s*:?\s*([A-Z0-9\-_\/]+)/iu', $text, $matches)) {
            $result['data']['numero_commande'] = trim($matches[1]);
        }
        
        // Référence client
        if (preg_match('/(?:référence|ref|client\s*ref)\s*:?\s*([A-Z0-9\-_\/]+)/iu', $text, $matches)) {
            $result['data']['reference_client'] = trim($matches[1]);
        }
        
        // === EXTRACTION DES ENTITÉS ===
        
        // Fournisseur/Vendeur (société)
        if (preg_match('/(?:société|company|from)\s*:?\s*([A-ZÀ-Ÿ][A-ZÀ-Ÿa-zà-ÿ\s&\.\-]{3,100})/iu', $text, $matches)) {
            $result['data']['fournisseur'] = trim($matches[1]);
        }
        
        // SIRET
        if (preg_match('/(?:siret|siren)\s*:?\s*([0-9]{9,14})/iu', $text, $matches)) {
            $result['data']['siret'] = $matches[1];
        }
        
        // TVA Intracommunautaire
        if (preg_match('/(?:n[°o]?\s*tva|vat\s*number)\s*:?\s*(FR[0-9A-Z]{11})/iu', $text, $matches)) {
            $result['data']['numero_tva'] = $matches[1];
        }
        
        // Email
        if (preg_match('/([a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,})/iu', $text, $matches)) {
            $result['data']['email'] = strtolower($matches[1]);
        }
        
        // Téléphone
        if (preg_match('/(?:tél|tel|phone)\s*:?\s*((?:\+33|0)[0-9\s\.]{9,14})/iu', $text, $matches)) {
            $result['data']['telephone'] = preg_replace('/\s+/', '', $matches[1]);
        }
        
        // === EXTRACTION DES MOYENS DE PAIEMENT ===
        
        // Moyen de paiement
        if (preg_match('/(?:paiement|payment|payé\s*par)\s*:?\s*(carte|cash|virement|chèque|espèces|card|transfer|check)/iu', $text, $matches)) {
            $result['data']['moyen_paiement'] = strtolower($matches[1]);
        }
        
        // IBAN
        if (preg_match('/(?:iban|rib)\s*:?\s*([A-Z]{2}[0-9]{2}[\sA-Z0-9]{10,30})/iu', $text, $matches)) {
            $result['data']['iban'] = preg_replace('/\s+/', '', $matches[1]);
        }
        
    } catch (Exception $e) {
        error_log("Data extraction error: " . $e->getMessage());
    }
    
    error_log(">>> extractDataFromText END - Fields extracted: " . count($result['data']));
}

/**
 * Convertir une date française en format MySQL (YYYY-MM-DD)
 */
function convertToMySQLDate($dateStr) {
    // Formats supportés: DD/MM/YYYY, DD-MM-YYYY, DD.MM.YYYY
    $dateStr = trim($dateStr);
    
    // Remplacer les séparateurs par /
    $dateStr = str_replace(['-', '.'], '/', $dateStr);
    
    // Parser la date
    $parts = explode('/', $dateStr);
    if (count($parts) === 3) {
        $day = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
        $month = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
        $year = $parts[2];
        
        // Convertir année 2 chiffres en 4 chiffres
        if (strlen($year) === 2) {
            $year = (int)$year;
            $year = $year > 50 ? "19$year" : "20$year";
        }
        
        // Valider et retourner
        if (checkdate((int)$month, (int)$day, (int)$year)) {
            return "$year-$month-$day";
        }
    }
    
    return null;
}

/**
 * Extraire le texte d'une image avec Tesseract OCR (si disponible)
 */
function extractTextFromImage($imagePath) {
    $text = '';
    
    // Vérifier si Tesseract est disponible
    exec('which tesseract 2>&1', $output, $returnCode);
    
    if ($returnCode === 0) {
        $textFile = $imagePath . '_ocr';
        $command = "tesseract " . escapeshellarg($imagePath) . " " . escapeshellarg($textFile) . " -l fra 2>&1";
        exec($command, $output, $returnCode);
        
        $textFilePath = $textFile . '.txt';
        if ($returnCode === 0 && file_exists($textFilePath)) {
            $text = file_get_contents($textFilePath);
            unlink($textFilePath); // Supprimer le fichier temporaire
            error_log("OCR successful for image: " . basename($imagePath));
        } else {
            error_log("Tesseract OCR failed for: " . basename($imagePath));
        }
    } else {
        error_log("Tesseract not available - image OCR skipped");
    }
    
    return $text;
}

/**
 * Récupérer les détails d'un document
 */
function handleDetails($pdo, $customer_id) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID requis']);
        return;
    }
    
    $stmt = $pdo->prepare("
        SELECT * FROM erp_scanned_documents 
        WHERE id = ? AND customer_id = ?
    ");
    $stmt->execute([$id, $customer_id]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$document) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Document non trouvé']);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'document' => $document
    ]);
}

/**
 * Retraiter un document
 */
function handleReprocess($pdo, $customer_id) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID requis']);
        return;
    }
    
    // Récupérer le document
    $stmt = $pdo->prepare("
        SELECT * FROM erp_scanned_documents 
        WHERE id = ? AND customer_id = ?
    ");
    $stmt->execute([$id, $customer_id]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$document) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Document non trouvé']);
        return;
    }
    
    // Re-analyser le document
    $filePath = __DIR__ . '/../../' . $document['file_path'];
    if (file_exists($filePath)) {
        $analysisResult = analyzeDocument($filePath, $document['mime_type']);
        
        // Mettre à jour dans la base
        $stmt = $pdo->prepare("
            UPDATE erp_scanned_documents 
            SET document_type = ?,
                amount = ?,
                document_date = ?,
                extracted_data = ?,
                status = 'processed',
                updated_at = NOW()
            WHERE id = ? AND customer_id = ?
        ");
        
        $stmt->execute([
            $analysisResult['type'] ?? 'unknown',
            $analysisResult['amount'] ?? null,
            $analysisResult['date'] ?? null,
            json_encode($analysisResult['data'] ?? []),
            $id,
            $customer_id
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Document retraité']);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Fichier introuvable']);
    }
}

/**
 * Supprimer un document
 */
function handleDelete($pdo, $customer_id) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID requis']);
        return;
    }
    
    // Récupérer le document pour supprimer le fichier
    $stmt = $pdo->prepare("
        SELECT file_path FROM erp_scanned_documents 
        WHERE id = ? AND customer_id = ?
    ");
    $stmt->execute([$id, $customer_id]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$document) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Document non trouvé']);
        return;
    }
    
    // Supprimer le fichier physique
    $filePath = __DIR__ . '/../../' . $document['file_path'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }
    
    // Supprimer de la base de données
    $stmt = $pdo->prepare("
        DELETE FROM erp_scanned_documents 
        WHERE id = ? AND customer_id = ?
    ");
    $stmt->execute([$id, $customer_id]);
    
    echo json_encode(['success' => true, 'message' => 'Document supprimé']);
}
