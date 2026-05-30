<?php
/**
 * Client API pour communiquer avec les agents IA Python
 */

class AIAgentsClient
{
    private string $baseUrl;
    private string $apiKey;
    private int $timeout;

    public function __construct(
        string $baseUrl = '',
        ?string $apiKey = null,
        int $timeout = 30
    ) {
        // Utiliser AI_API_URL du .env, sinon localhost:8000 (développement)
        if (empty($baseUrl)) {
            $baseUrl = $_ENV['AI_API_URL'] ?? getenv('AI_API_URL') ?? 'http://localhost:8000';
        }
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey ?? ($_ENV['AI_API_KEY'] ?? 'bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps');
        $this->timeout = $timeout;
    }

    /**
     * Effectuer une requête HTTP vers l'API IA
     */
    private function request(string $method, string $endpoint, ?array $data = null): array
    {
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-API-Key: ' . $this->apiKey
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("API Request Error: $error");
        }

        if (empty($response)) {
            throw new Exception("Empty response from API (HTTP $httpCode)");
        }

        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON response: " . json_last_error_msg() . " (HTTP $httpCode)");
        }
        
        if ($httpCode >= 400) {
            $errorMsg = $result['detail'] ?? $result['error'] ?? 'Unknown error';
            throw new Exception("API Error ($httpCode): $errorMsg");
        }

        return $result;
    }

    /**
     * Vérifier le statut de l'API
     */
    public function healthCheck(): array
    {
        return $this->request('GET', '/health');
    }

    /**
     * Analyser un email
     */
    public function analyzeEmail(
        int $emailId,
        string $subject,
        string $body,
        string $sender,
        int $customerId
    ): array {
        return $this->request('POST', '/api/inbox/analyze', [
            'email_id' => $emailId,
            'subject' => $subject,
            'body' => $body,
            'sender' => $sender,
            'customer_id' => $customerId
        ]);
    }

    /**
     * Traiter les emails en attente
     */
    public function processPendingEmails(int $customerId, int $limit = 10): array
    {
        return $this->request('POST', '/api/inbox/process-pending', [
            'customer_id' => $customerId,
            'limit' => $limit
        ]);
    }

    /**
     * Scorer un lead
     */
    public function scoreLead(int $leadId, int $customerId): array
    {
        return $this->request('POST', '/api/leads/score', [
            'lead_id' => $leadId,
            'customer_id' => $customerId
        ]);
    }

    /**
     * Récupérer les leads chauds
     */
    public function getHotLeads(int $customerId, int $limit = 10): array
    {
        return $this->request('GET', "/api/leads/hot/$customerId?limit=$limit");
    }

    /**
     * Récupérer les leads froids
     */
    public function getColdLeads(int $customerId, int $days = 30): array
    {
        return $this->request('GET', "/api/leads/cold/$customerId?days=$days");
    }

    /**
     * Récupérer les actions en attente
     */
    public function getPendingActions(int $customerId): array
    {
        return $this->request('GET', "/api/actions/pending/$customerId");
    }

    /**
     * Mettre à jour une action
     */
    public function updateAction(
        int $actionId,
        string $status,
        ?array $result = null
    ): array {
        $data = [
            'action_id' => $actionId,
            'status' => $status
        ];
        
        if ($result) {
            $data['result'] = $result;
        }

        return $this->request('POST', '/api/actions/update', $data);
    }

    /**
     * Approuver une action
     */
    public function approveAction(int $actionId): array
    {
        return $this->updateAction($actionId, 'approved');
    }

    /**
     * Rejeter une action
     */
    public function rejectAction(int $actionId): array
    {
        return $this->updateAction($actionId, 'rejected');
    }

    /**
     * Marquer une action comme exécutée
     */
    public function executeAction(int $actionId, array $result): array
    {
        return $this->updateAction($actionId, 'executed', $result);
    }

    /**
     * Récupérer les logs
     */
    public function getLogs(?string $agentName = null, int $limit = 50): array
    {
        $params = ['limit' => $limit];
        if ($agentName) {
            $params['agent_name'] = $agentName;
        }
        
        $queryString = http_build_query($params);
        return $this->request('GET', "/api/logs?$queryString");
    }
}
