<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

try {
    require_once '../config/database.php';

    // Vérifier si les identifiants Power BI sont configurés correctement
    if (!defined('POWERBI_CLIENT_ID') || 
        POWERBI_CLIENT_ID === 'votre-client-id-azure' || 
        empty(POWERBI_CLIENT_ID)) {
        
        echo json_encode([
            'success' => false,
            'error' => 'Configuration Power BI non trouvée ou incomplète',
            'message' => 'Identifiants Azure AD non configurés - Mode démo disponible',
            'demo_mode' => true
        ]);
        exit;
    }

    // Configuration Power BI
    $clientId = POWERBI_CLIENT_ID;
    $clientSecret = POWERBI_CLIENT_SECRET;
    $tenantId = POWERBI_TENANT_ID;
    $workspaceId = POWERBI_WORKSPACE_ID;
    $reportId = POWERBI_REPORT_ID;

/**
 * Obtenir un token d'accès Azure AD pour Power BI
 */
function getPowerBIAccessToken($clientId, $clientSecret, $tenantId) {
    $url = "https://login.microsoftonline.com/$tenantId/oauth2/v2.0/token";
    
    $postData = [
        'grant_type' => 'client_credentials',
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'scope' => 'https://analysis.windows.net/powerbi/api/.default'
    ];
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($postData),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded'
        ]
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($httpCode === 200) {
        $tokenData = json_decode($response, true);
        return $tokenData['access_token'];
    } else {
        error_log("Erreur authentification Azure AD: " . $response);
        return false;
    }
}

/**
 * Obtenir l'URL d'embed pour un rapport Power BI
 */
function getPowerBIEmbedUrl($accessToken, $workspaceId, $reportId) {
    $url = "https://api.powerbi.com/v1.0/myorg/groups/$workspaceId/reports/$reportId";
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $accessToken",
            'Content-Type: application/json'
        ]
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($httpCode === 200) {
        $reportData = json_decode($response, true);
        return $reportData['embedUrl'];
    } else {
        error_log("Erreur récupération embed URL: " . $response);
        return false;
    }
}

/**
 * Générer un token d'embed pour Power BI
 */
function generatePowerBIEmbedToken($accessToken, $workspaceId, $reportId) {
    $url = "https://api.powerbi.com/v1.0/myorg/groups/$workspaceId/reports/$reportId/GenerateToken";
    
    $postData = json_encode([
        'accessLevel' => 'View',
        'allowSaveAs' => false
    ]);
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $accessToken",
            'Content-Type: application/json'
        ]
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($httpCode === 200) {
        $tokenData = json_decode($response, true);
        return $tokenData['token'];
    } else {
        error_log("Erreur génération embed token: " . $response);
        return false;
    }
}

try {
    // Étape 1: Obtenir le token d'accès Azure AD
    $accessToken = getPowerBIAccessToken($clientId, $clientSecret, $tenantId);
    
    if (!$accessToken) {
        throw new Exception('Impossible d\'obtenir le token d\'accès Azure AD');
    }
    
    // Étape 2: Obtenir l'URL d'embed du rapport
    $embedUrl = getPowerBIEmbedUrl($accessToken, $workspaceId, $reportId);
    
    if (!$embedUrl) {
        throw new Exception('Impossible d\'obtenir l\'URL d\'embed du rapport');
    }
    
    // Étape 3: Générer un token d'embed (optionnel pour plus de sécurité)
    $embedToken = generatePowerBIEmbedToken($accessToken, $workspaceId, $reportId);
    
    // Préparer la réponse
    $response = [
        'success' => true,
        'accessToken' => $embedToken ?: $accessToken, // Utiliser embed token si disponible
        'embedUrl' => $embedUrl,
        'reportId' => $reportId,
        'workspaceId' => $workspaceId,
        'tokenExpiry' => time() + 3600, // Token valide 1 heure
        'message' => 'Token Power BI généré avec succès'
    ];
    
    // Log de l'activité
    if (isset($_SESSION['user_id'])) {
        try {
            $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([
                $_SESSION['user_id'],
                'powerbi_access',
                'Accès aux rapports Power BI'
            ]);
        } catch (Exception $e) {
            // Log error but don't fail the main request
            error_log("Erreur log activité: " . $e->getMessage());
        }
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => true
    ]);
    
    error_log("Erreur API Power BI: " . $e->getMessage());
}
?>
