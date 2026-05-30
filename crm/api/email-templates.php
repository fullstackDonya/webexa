<?php
session_start();

// Vérification d'authentification
if (!isset($_SESSION['user_id']) || !isset($_SESSION['customer_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

require_once '../includes/email_templates.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

// Obtenir les catégories de templates
if ($action === 'categories') {
    echo json_encode(get_template_categories());
    exit;
}

// Obtenir les templates d'une catégorie
if ($action === 'templates') {
    $category = $_GET['category'] ?? null;
    
    if ($category) {
        $templates = get_templates_by_category($category);
    } else {
        $templates = get_email_templates();
    }
    
    // Reformater pour l'affichage
    $result = [];
    foreach ($templates as $id => $template) {
        $result[] = [
            'id' => $id,
            'name' => $template['name'],
            'category' => $template['category'],
            'subject' => $template['subject']
        ];
    }
    
    echo json_encode($result);
    exit;
}

// Obtenir le contenu d'un template spécifique
if ($action === 'template' || $action === 'get') {
    $template_id = $_GET['id'] ?? '';
    $template = get_template($template_id);
    
    if (!$template) {
        http_response_code(404);
        echo json_encode(['error' => 'Template non trouvé']);
        exit;
    }
    
    // Remplacer {SIGNATURE} par la signature personnalisée
    $signature = generate_email_signature();
    $template['body'] = str_replace('{SIGNATURE}', $signature, $template['body']);
    
    echo json_encode($template);
    exit;
}

// Générer le contenu personnalisé
if ($action === 'generate') {
    $template_id = $_POST['template_id'] ?? '';
    $lead_id = $_POST['lead_id'] ?? null;
    
    $lead_data = [];
    
    // Récupérer les données du lead si fourni
    if ($lead_id) {
        try {
            require_once '../includes/db_connect.php';
            $stmt = $pdo->prepare('SELECT first_name, last_name, email, company_name FROM leads WHERE id = ? AND customer_id = ?');
            $stmt->execute([$lead_id, $_SESSION['customer_id']]);
            $lead_data = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            // Silencieusement ignorer les erreurs de requête
        }
    }
    
    $content = generate_email_content($template_id, $lead_data);
    
    if (!$content) {
        http_response_code(404);
        echo json_encode(['error' => 'Template non trouvé']);
        exit;
    }
    
    echo json_encode($content);
    exit;
}

// Obtenir les suggestions (templates pertinents selon le contexte)
if ($action === 'suggestions') {
    $context = $_GET['context'] ?? 'lead_nurturing'; // lead_nurturing, sales, retention, etc.
    
    $suggestions = match($context) {
        'new_lead' => [
            'lead_welcome',
            'product_demo',
            'pricing_inquiry'
        ],
        'warm_lead' => [
            'lead_followup_1',
            'trial_reminder',
            'product_demo'
        ],
        'hot_lead' => [
            'pricing_inquiry',
            'trial_reminder',
            'onboarding'
        ],
        'inactive_customer' => [
            'win_back',
            'feature_announcement',
            'seasonal_promotion'
        ],
        'active_customer' => [
            'feature_announcement',
            'referral_program',
            'event_invitation'
        ],
        'event' => [
            'event_invitation',
            'partnership_proposal'
        ],
        default => array_keys(get_email_templates())
    };
    
    $templates = get_email_templates();
    $result = [];
    
    foreach ($suggestions as $template_id) {
        if (isset($templates[$template_id])) {
            $result[] = [
                'id' => $template_id,
                'name' => $templates[$template_id]['name'],
                'category' => $templates[$template_id]['category'],
                'subject' => $templates[$template_id]['subject']
            ];
        }
    }
    
    echo json_encode($result);
    exit;
}

// Obtenir un template par nom
if ($action === 'get_by_name') {
    $name = $_GET['name'] ?? '';
    
    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['error' => 'Nom requis']);
        exit;
    }
    
    $templates = get_email_templates();
    
    foreach ($templates as $id => $template) {
        if ($template['name'] === $name) {
            echo json_encode([
                'success' => true,
                'template' => [
                    'id' => $id,
                    'name' => $template['name'],
                    'category' => $template['category'],
                    'subject' => $template['subject'],
                    'content_html' => $template['body']
                ]
            ]);
            exit;
        }
    }
    
    http_response_code(404);
    echo json_encode(['error' => 'Template non trouvé']);
    exit;
}

// Assurer que les defaults existent
if ($action === 'ensure_defaults') {
    // Les templates sont déjà définis dans includes/email_templates.php
    echo json_encode(['success' => true]);
    exit;
}

// Sans action spécifiée, retourner la liste complète
if (empty($action)) {
    $templates = get_email_templates();
    $result = [];
    
    foreach ($templates as $id => $template) {
        $result[] = [
            'id' => $id,
            'name' => $template['name'],
            'category' => $template['category'],
            'subject' => $template['subject']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'templates' => $result
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Action inconnue']);

?>