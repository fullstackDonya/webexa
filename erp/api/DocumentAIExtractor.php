<?php
/**
 * Service d'extraction de documents avec IA
 * Stratégie hybride intelligente pour minimiser les coûts
 */

class DocumentAIExtractor {
    
    private $openaiKey;
    private $useAI = false;
    
    public function __construct($openaiKey = null) {
        $this->openaiKey = $openaiKey;
        $this->useAI = !empty($openaiKey);
    }
    
    /**
     * Extraire les données d'un document avec stratégie intelligente
     * 
     * STRATÉGIE :
     * 1. Essayer pdftotext (GRATUIT) pour PDF
     * 2. Si échec ou image, utiliser GPT-4 Vision (0.01€)
     */
    public function extractDocument($filePath, $mimeType) {
        $result = [
            'method' => 'none',
            'type' => 'other',
            'amount' => null,
            'date' => null,
            'data' => [],
            'confidence' => 0,
            'cost' => 0
        ];
        
        // ÉTAPE 1 : Extraction gratuite (pdftotext ou patterns simples)
        if ($mimeType === 'application/pdf') {
            $textResult = $this->extractWithPdftotext($filePath);
            if ($textResult['success']) {
                $result = array_merge($result, $this->parseTextWithRegex($textResult['text']));
                $result['method'] = 'pdftotext';
                $result['cost'] = 0;
                
                // Si extraction réussie avec confiance élevée, on s'arrête ici
                if ($result['confidence'] > 70) {
                    return $result;
                }
            }
        }
        
        // ÉTAPE 2 : Si échec ou image, utiliser l'IA (seulement si activée)
        if ($this->useAI && $result['confidence'] < 70) {
            error_log("Low confidence extraction, using AI fallback...");
            $aiResult = $this->extractWithGPT4Vision($filePath, $mimeType);
            if ($aiResult['success']) {
                $result = array_merge($result, $aiResult['data']);
                $result['method'] = 'gpt4-vision';
                $result['cost'] = 0.01; // Coût par document avec GPT-4 Vision
            }
        }
        
        return $result;
    }
    
    /**
     * Extraction avec pdftotext (GRATUIT)
     */
    private function extractWithPdftotext($filePath) {
        error_log(">>> Trying pdftotext extraction...");
        
        // Trouver le chemin de pdftotext selon l'environnement
        $pdftotextPath = $this->findPdftotextPath();
        if (!$pdftotextPath) {
            error_log(">>> ERROR: pdftotext not found in any known location");
            return ['success' => false, 'text' => null];
        }
        
        $textFile = $filePath . '.txt';
        $command = $pdftotextPath . " " . escapeshellarg($filePath) . " " . escapeshellarg($textFile) . " 2>&1";
        exec($command, $output, $returnCode);
        
        error_log(">>> pdftotext command: $command");
        error_log(">>> pdftotext return code: $returnCode");
        
        if ($returnCode === 0 && file_exists($textFile)) {
            $text = file_get_contents($textFile);
            unlink($textFile);
            
            error_log(">>> pdftotext SUCCESS - Extracted " . strlen($text) . " characters");
            error_log(">>> First 300 chars: " . substr($text, 0, 300));
            
            return ['success' => true, 'text' => $text];
        }
        
        error_log(">>> pdftotext FAILED");
        if (!empty($output)) {
            error_log(">>> Output: " . implode("\n", $output));
        }
        
        return ['success' => false, 'text' => null];
    }
    
    /**
     * Trouve le chemin de pdftotext selon l'environnement
     */
    private function findPdftotextPath() {
        // Chemins possibles selon l'environnement
        $possiblePaths = [
            '/opt/homebrew/bin/pdftotext',  // Homebrew sur macOS ARM (M1/M2/M3)
            '/usr/local/bin/pdftotext',      // Homebrew sur macOS Intel ou Linux
            '/usr/bin/pdftotext',            // Installation système Linux
            'pdftotext'                      // Dans le PATH
        ];
        
        foreach ($possiblePaths as $path) {
            // Tester si le fichier existe et est exécutable
            if ($path === 'pdftotext') {
                // Pour le PATH, tester avec which
                exec("which pdftotext 2>&1", $output, $returnCode);
                if ($returnCode === 0 && !empty($output[0])) {
                    error_log(">>> Found pdftotext in PATH: " . $output[0]);
                    return $output[0];
                }
            } else {
                if (file_exists($path) && is_executable($path)) {
                    error_log(">>> Found pdftotext at: $path");
                    return $path;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Parser le texte avec regex (patterns améliorés)
     */
    private function parseTextWithRegex($text) {
        error_log(">>> parseTextWithRegex START");
        
        $data = [];
        $confidence = 0;
        
        // Détecter le type de document
        if (preg_match('/facture|invoice/i', $text)) {
            $data['type'] = 'invoice';
            $confidence += 20;
            error_log(">>> Type detected: invoice");
        } elseif (preg_match('/reçu|receipt|ticket/i', $text)) {
            $data['type'] = 'receipt';
            $confidence += 20;
            error_log(">>> Type detected: receipt");
        }
        
        // Extraire le montant (patterns multiples)
        $amountPatterns = [
            '/(?:total\s*ttc|net\s*à\s*payer|total)[\s:]*([0-9]{1,6}[\s,.]?[0-9]{0,3}[,.]?[0-9]{2})\s*(?:€|EUR)?/iu',
            '/([0-9]{1,6}[,.][0-9]{2})\s*€/u'
        ];
        
        foreach ($amountPatterns as $idx => $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $amount = str_replace([' ', ',', "\u{00A0}"], ['', '.', ''], $matches[1]);
                $data['amount'] = (float)$amount;
                $data['data']['total_ttc'] = $data['amount'];
                $confidence += 30;
                error_log(">>> Amount found with pattern $idx: " . $data['amount'] . " (from: " . $matches[0] . ")");
                break;
            }
        }
        
        if (!isset($data['amount'])) {
            error_log(">>> No amount found in text");
        }
        
        // Extraire la date
        if (preg_match('/(?:date|le)[\s:]*([0-9]{1,2}[\/\-\.][0-9]{1,2}[\/\-\.][0-9]{2,4})/iu', $text, $matches)) {
            $data['date'] = $this->convertDate($matches[1]);
            $data['data']['date_document'] = $matches[1];
            $confidence += 20;
            error_log(">>> Date found: " . $data['date'] . " (from: " . $matches[1] . ")");
        } else {
            error_log(">>> No date found in text");
        }
        
        // Numéro de document
        if (preg_match('/(?:facture|invoice|n[°o#])\s*:?\s*([A-Z0-9\-_\/]{3,20})/iu', $text, $matches)) {
            $data['data']['numero_document'] = trim($matches[1]);
            $confidence += 10;
            error_log(">>> Document number: " . $data['data']['numero_document']);
        }
        
        // Fournisseur
        if (preg_match('/(?:de|from|société)[\s:]+([A-ZÀ-Ÿ][^\n]{3,50})/iu', $text, $matches)) {
            $data['data']['fournisseur'] = trim($matches[1]);
            $confidence += 10;
        }
        
        $data['confidence'] = $confidence;
        error_log(">>> parseTextWithRegex END - Confidence: $confidence%");
        
        return $data;
    }
    
    /**
     * Extraction avec GPT-4 Vision (IA avancée)
     * Coût : ~0.01€ par document
     */
    private function extractWithGPT4Vision($filePath, $mimeType) {
        if (!$this->openaiKey) {
            return ['success' => false, 'error' => 'OpenAI API key not configured'];
        }
        
        // Convertir le fichier en base64
        $fileData = file_get_contents($filePath);
        $base64 = base64_encode($fileData);
        
        // Déterminer le format
        $imageType = 'image/jpeg';
        if ($mimeType === 'application/pdf') {
            // Pour PDF, on convertit la première page en image (ou on utilise l'API PDF de GPT-4)
            $imageType = 'application/pdf';
        } elseif (strpos($mimeType, 'image/png') !== false) {
            $imageType = 'image/png';
        }
        
        // Préparer la requête pour GPT-4 Vision
        $prompt = "Analyse ce document comptable et extrais les informations suivantes au format JSON :
{
  \"type\": \"invoice|receipt|bank_statement|contract|other\",
  \"amount\": 123.45,
  \"date\": \"2026-02-20\",
  \"numero_document\": \"FA-2026-001\",
  \"fournisseur\": \"Nom de la société\",
  \"total_ht\": 100.00,
  \"tva\": 20.00,
  \"taux_tva\": 20,
  \"date_echeance\": \"2026-03-20\",
  \"moyen_paiement\": \"carte\",
  \"email\": \"contact@example.com\",
  \"telephone\": \"+33123456789\",
  \"siret\": \"12345678901234\",
  \"iban\": \"FR76...\",
  \"description\": \"Description des produits/services\"
}

Réponds UNIQUEMENT avec le JSON, sans texte additionnel. Si une information n'est pas trouvée, utilise null.";
        
        $requestData = [
            'model' => 'gpt-4-vision-preview',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $prompt
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => "data:$imageType;base64,$base64"
                            ]
                        ]
                    ]
                ]
            ],
            'max_tokens' => 1000,
            'temperature' => 0.1
        ];
        
        // Appel à l'API OpenAI
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->openaiKey
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("OpenAI API error: HTTP $httpCode - $response");
            return ['success' => false, 'error' => 'API request failed'];
        }
        
        $result = json_decode($response, true);
        
        if (!isset($result['choices'][0]['message']['content'])) {
            return ['success' => false, 'error' => 'Invalid API response'];
        }
        
        $content = $result['choices'][0]['message']['content'];
        
        // Parser le JSON retourné par GPT-4
        $extractedData = json_decode($content, true);
        
        if (!$extractedData) {
            // Tenter de nettoyer le contenu si ce n'est pas du JSON pur
            $content = preg_replace('/```json\s*/', '', $content);
            $content = preg_replace('/```\s*/', '', $content);
            $extractedData = json_decode($content, true);
        }
        
        if (!$extractedData) {
            return ['success' => false, 'error' => 'Failed to parse AI response'];
        }
        
        return [
            'success' => true,
            'data' => [
                'type' => $extractedData['type'] ?? 'other',
                'amount' => $extractedData['amount'] ?? null,
                'date' => $extractedData['date'] ?? null,
                'data' => $extractedData,
                'confidence' => 95 // IA a généralement une haute confiance
            ]
        ];
    }
    
    /**
     * Convertir une date en format MySQL
     */
    private function convertDate($dateStr) {
        $dateStr = trim($dateStr);
        $dateStr = str_replace(['-', '.'], '/', $dateStr);
        $parts = explode('/', $dateStr);
        
        if (count($parts) === 3) {
            $day = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
            $month = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
            $year = $parts[2];
            
            if (strlen($year) === 2) {
                $year = (int)$year;
                $year = $year > 50 ? "19$year" : "20$year";
            }
            
            if (checkdate((int)$month, (int)$day, (int)$year)) {
                return "$year-$month-$day";
            }
        }
        
        return null;
    }
}
