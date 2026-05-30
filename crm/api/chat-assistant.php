<?php
header('Content-Type: application/json');
// Pas besoin de verify_subscriptions pour l'API chat

// Base de connaissances du CRM
$knowledge_base = [
    // Email OAuth
    [
        'keywords' => ['gmail', 'email', 'oauth', 'connecter', 'outlook', 'synchronisation', 'sync'],
        'response' => "📧 **Configuration Email OAuth**\n\n" .
                     "Pour connecter votre email :\n" .
                     "1. Allez dans **Email (OAuth)** dans le menu\n" .
                     "2. Cliquez sur **🔐 Connecter Gmail** ou **🔐 Connecter Outlook**\n" .
                     "3. Autorisez l'accès dans la fenêtre Google/Microsoft\n" .
                     "4. Vos emails seront synchronisés automatiquement toutes les 5 minutes\n\n" .
                     "✅ **Avantages :** Synchronisation bidirectionnelle, envoi depuis le CRM, tracking des emails\n\n" .
                     "[📖 Documentation complète](email-settings.php)"
    ],
    
    // WhatsApp
    [
        'keywords' => ['whatsapp', 'template', 'campagne whatsapp', 'meta', 'phone number id'],
        'response' => "💬 **WhatsApp Business**\n\n" .
                     "**Prérequis :**\n" .
                     "1. Créer un Meta Business Account sur business.facebook.com\n" .
                     "2. Créer une App Meta dans developers.facebook.com\n" .
                     "3. Obtenir Phone Number ID et Access Token\n\n" .
                     "**Configuration CRM :**\n" .
                     "1. Allez dans **WhatsApp Business** → Configuration\n" .
                     "2. Cliquez **+ Connecter WhatsApp**\n" .
                     "3. Renseignez Phone Number ID, Business Account ID, Access Token\n\n" .
                     "**Créer un template :**\n" .
                     "Les messages WhatsApp nécessitent des templates approuvés par Meta (24-48h).\n" .
                     "Allez dans **Templates WhatsApp** → Créer Template\n\n" .
                     "[📖 Guide WhatsApp](whatsapp-settings.php)"
    ],
    
    // Leads
    [
        'keywords' => ['lead', 'prospect', 'lead scoring', 'qualifier', 'convertir'],
        'response' => "🎯 **Gestion des Leads**\n\n" .
                     "**Qu'est-ce qu'un lead ?**\n" .
                     "Un prospect potentiel qui a montré un intérêt mais n'est pas encore client.\n\n" .
                     "**Cycle de vie :**\n" .
                     "Nouveau → Contacté → Qualifié → Opportunité\n\n" .
                     "**Lead Scoring automatique (0-100) :**\n" .
                     "• Email professionnel : +10 pts\n" .
                     "• Téléphone : +10 pts\n" .
                     "• Entreprise : +15 pts\n" .
                     "• Email ouvert : +20 pts\n" .
                     "• Email cliqué : +30 pts\n\n" .
                     "💡 **Astuce :** Priorisez les leads avec score > 60\n\n" .
                     "**Convertir en opportunité :**\n" .
                     "Ouvrir fiche lead → Convertir en Opportunité\n\n" .
                     "[📖 Documentation Leads](documentation-commerciaux.php#leads)"
    ],
    
    // Contacts vs Leads
    [
        'keywords' => ['différence', 'contact vs lead', 'contact ou lead'],
        'response' => "👥 **Lead vs Contact**\n\n" .
                     "**Lead (Prospect) :**\n" .
                     "• Personne qui a montré un intérêt\n" .
                     "• Pas encore qualifiée\n" .
                     "• Score automatique (0-100)\n" .
                     "• Statuts : Nouveau, Contacté, Qualifié, Perdu\n\n" .
                     "**Contact :**\n" .
                     "• Relation professionnelle établie\n" .
                     "• Client, partenaire ou lead qualifié\n" .
                     "• Historique complet des interactions\n\n" .
                     "🔄 **Conversion :** Lead qualifié → Contact + Opportunité"
    ],
    
    // Campagnes
    [
        'keywords' => ['campagne', 'marketing', 'newsletter', 'email marketing', 'automation'],
        'response' => "📣 **Campagnes Marketing**\n\n" .
                     "**Types disponibles :**\n" .
                     "• **Email** : Newsletters, promotions, relances\n" .
                     "• **WhatsApp** : Messages avec templates Meta approuvés\n\n" .
                     "**Créer une campagne email :**\n" .
                     "1. Allez dans **Campagnes** → Créer Campagne\n" .
                     "2. Type : Email\n" .
                     "3. Éditeur visuel : glissez-déposez blocs\n" .
                     "4. Personnalisez avec {firstname}, {company}\n" .
                     "5. Sélectionnez destinataires\n" .
                     "6. Programmez ou envoyez\n\n" .
                     "**Statistiques trackées :**\n" .
                     "• Taux d'ouverture (objectif > 25%)\n" .
                     "• Taux de clic CTR (objectif > 3%)\n" .
                     "• Conversions\n" .
                     "• Désabonnements\n\n" .
                     "[📖 Guide Campagnes](documentation-commerciaux.php#campaigns)"
    ],
    
    // Automations
    [
        'keywords' => ['automation', 'automatiser', 'workflow', 'scénario'],
        'response' => "🤖 **Automations**\n\n" .
                     "**Déclencheurs disponibles :**\n" .
                     "• Nouveau contact/lead/opportunité\n" .
                     "• Email ouvert/cliqué\n" .
                     "• Score de lead atteint\n" .
                     "• Date anniversaire\n" .
                     "• Changement de statut\n\n" .
                     "**Actions possibles :**\n" .
                     "• Envoyer email\n" .
                     "• Envoyer WhatsApp\n" .
                     "• Créer tâche\n" .
                     "• Mettre à jour champ\n" .
                     "• Ajouter tag\n" .
                     "• Notifier utilisateur\n" .
                     "• Attendre X jours/heures\n\n" .
                     "**Exemple :** Bienvenue nouveau lead\n" .
                     "Déclencheur : Nouveau lead créé\n" .
                     "→ Attendre 5 min → Envoyer email bienvenue\n" .
                     "→ Attendre 2 jours → Si pas de réponse : Email relance\n\n" .
                     "[📖 Créer automation](automations-list.php)"
    ],
    
    // Import
    [
        'keywords' => ['importer', 'import', 'csv', 'excel', 'contacts'],
        'response' => "📥 **Importer des Contacts**\n\n" .
                     "1. Préparez un fichier **CSV** avec colonnes :\n" .
                     "   • Prénom, Nom, Email, Téléphone, Entreprise\n\n" .
                     "2. Allez dans **Contacts** → Importer\n\n" .
                     "3. Chargez votre fichier CSV\n\n" .
                     "4. Mappez les colonnes (associez colonnes CSV aux champs CRM)\n\n" .
                     "5. Validez l'import\n\n" .
                     "💡 **Astuce :** Exportez d'abord un contact pour voir le format exact"
    ],
    
    // Pipeline
    [
        'keywords' => ['pipeline', 'opportunité', 'deal', 'forecast', 'prévision'],
        'response' => "📊 **Pipeline de Vente**\n\n" .
                     "**Étapes du pipeline :**\n" .
                     "1. Prospection (50% probabilité)\n" .
                     "2. Contact (60%)\n" .
                     "3. Proposition (75%)\n" .
                     "4. Négociation (85%)\n" .
                     "5. Gagné (100%)\n\n" .
                     "**Créer une opportunité :**\n" .
                     "• Depuis lead qualifié : Convertir en Opportunité\n" .
                     "• OU : Opportunités → + Nouvelle\n\n" .
                     "**Vue Kanban :**\n" .
                     "Allez dans **Pipeline Board** pour glisser-déposer entre étapes\n\n" .
                     "**Prévisions (Forecast) :**\n" .
                     "CA prévisionnel = Σ (Montant × Probabilité)\n\n" .
                     "[📖 Guide Pipeline](documentation-commerciaux.php#pipeline)"
    ],
    
    // Analytics
    [
        'keywords' => ['statistiques', 'analytics', 'rapport', 'kpi', 'dashboard'],
        'response' => "📈 **Analytics & Rapports**\n\n" .
                     "**Tableaux de bord disponibles :**\n\n" .
                     "📊 **Analytics Ventes** (analytics-sales.php)\n" .
                     "• CA par période\n" .
                     "• Taux de conversion\n" .
                     "• Durée cycle de vente\n\n" .
                     "🎯 **Analytics Funnel** (analytics-funnel.php)\n" .
                     "• Visiteurs → Leads → Opportunités → Clients\n" .
                     "• Taux par étape\n\n" .
                     "👥 **Analytics Clients** (analytics-customer.php)\n" .
                     "• LTV (valeur vie client)\n" .
                     "• Taux de rétention\n" .
                     "• Segmentation RFM\n\n" .
                     "**KPIs Clés :**\n" .
                     "• Taux conversion Leads → Opportunités (objectif > 30%)\n" .
                     "• Taux de closing (objectif > 25%)\n" .
                     "• Durée cycle de vente (objectif < 90 jours)\n\n" .
                     "**Export :** Tous les rapports exportables en CSV, PDF, Excel"
    ],
    
    // Mot de passe
    [
        'keywords' => ['mot de passe', 'password', 'connexion', 'login', 'identifiant'],
        'response' => "🔐 **Connexion & Sécurité**\n\n" .
                     "**Première connexion :**\n" .
                     "URL : https://webitech.fr/crm/\n" .
                     "Identifiants fournis par email\n\n" .
                     "**Réinitialiser mot de passe :**\n" .
                     "Sur la page de connexion → \"Mot de passe oublié ?\"\n\n" .
                     "**Changer mot de passe :**\n" .
                     "1. Cliquez sur votre profil (en haut à droite)\n" .
                     "2. Paramètres → Sécurité\n" .
                     "3. Nouveau mot de passe (min 8 caractères, majuscules, chiffres, symboles)\n\n" .
                     "**Sécurité :**\n" .
                     "• SSL (HTTPS)\n" .
                     "• Chiffrement AES-256\n" .
                     "• Sauvegardes quotidiennes\n" .
                     "• Conformité RGPD"
    ],
    
    // Support
    [
        'keywords' => ['aide', 'support', 'contact', 'assistance', 'problème', 'erreur'],
        'response' => "🆘 **Support & Assistance**\n\n" .
                     "**Plusieurs canaux disponibles :**\n\n" .
                     "💬 **Chat Assistant** (vous y êtes !)\n" .
                     "Réponse immédiate 24/7\n\n" .
                     "📧 **Email**\n" .
                     "support@webitech.fr\n\n" .
                     "📞 **Téléphone**\n" .
                     "+33 1 23 45 67 89 (9h-18h)\n\n" .
                     "📚 **Documentation :**\n" .
                     "• [Guide Commerciaux](documentation-commerciaux.php)\n" .
                     "• [Guide Clients](documentation-clients.php)\n\n" .
                     "**Pour un problème technique :**\n" .
                     "Décrivez précisément : que faisiez-vous ? quel message d'erreur ?"
    ],
    
    // Token expiré
    [
        'keywords' => ['token', 'expiré', 'expired', 'reconnecter', 'refresh'],
        'response' => "🔄 **Token Expiré**\n\n" .
                     "Si vous voyez un badge \"Expiré\" sur votre configuration email :\n\n" .
                     "1. Allez dans **Email (OAuth)**\n" .
                     "2. Trouvez la configuration expirée\n" .
                     "3. Cliquez sur **\"Reconnecter\"**\n" .
                     "4. Vous serez redirigé vers Google/Microsoft pour ré-autoriser\n" .
                     "5. Le token sera automatiquement rafraîchi\n\n" .
                     "💡 Les tokens sont automatiquement rafraîchis AVANT expiration normalement.\n" .
                     "Si le problème persiste, contactez le support."
    ],
    
    // Mobile
    [
        'keywords' => ['mobile', 'téléphone', 'smartphone', 'tablette', 'app'],
        'response' => "📱 **Accès Mobile**\n\n" .
                     "Le CRM est **100% responsive** et fonctionne parfaitement sur :\n" .
                     "• Smartphones (iOS, Android)\n" .
                     "• Tablettes\n" .
                     "• Ordinateurs\n\n" .
                     "**Aucune installation nécessaire :**\n" .
                     "Utilisez simplement votre navigateur mobile (Chrome, Safari, Firefox)\n\n" .
                     "**Ajouter à l'écran d'accueil :**\n" .
                     "• **iOS :** Safari → Partager → Sur l'écran d'accueil\n" .
                     "• **Android :** Chrome → Menu → Ajouter à l'écran d'accueil\n\n" .
                     "✅ Toutes les fonctionnalités desktop disponibles sur mobile !"
    ],
];

// Récupérer la requête
$input = json_decode(file_get_contents('php://input'), true);
$message = $input['message'] ?? '';
$history = $input['history'] ?? [];

if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Message vide']);
    exit;
}

// Normaliser le message (lowercase, retirer accents)
$normalized_message = strtolower($message);
$normalized_message = iconv('UTF-8', 'ASCII//TRANSLIT', $normalized_message);

// Trouver la meilleure réponse
$best_match = null;
$best_score = 0;

foreach ($knowledge_base as $entry) {
    $score = 0;
    
    foreach ($entry['keywords'] as $keyword) {
        if (stripos($normalized_message, $keyword) !== false) {
            $score += 1;
        }
    }
    
    if ($score > $best_score) {
        $best_score = $score;
        $best_match = $entry;
    }
}

// Générer la réponse
if ($best_match && $best_score > 0) {
    $response = $best_match['response'];
} else {
    // Réponse par défaut si aucune correspondance
    $response = "🤔 **Je n'ai pas trouvé de réponse précise.**\n\n" .
                "Voici ce que je peux faire pour vous aider :\n\n" .
                "1. 📧 **Configuration Email** : connecter Gmail/Outlook\n" .
                "2. 💬 **WhatsApp Business** : créer templates et campagnes\n" .
                "3. 🎯 **Leads & Contacts** : gestion et qualification\n" .
                "4. 📣 **Campagnes Marketing** : email et WhatsApp\n" .
                "5. 🤖 **Automations** : workflows automatisés\n" .
                "6. 📊 **Analytics** : rapports et statistiques\n" .
                "7. 📥 **Import/Export** : données CSV\n\n" .
                "💡 **Suggestion :** Reformulez votre question ou consultez la [documentation complète](documentation-commerciaux.php)\n\n" .
                "📞 **Besoin d'aide urgente ?** Contactez support@webitech.fr";
}

// Convertir Markdown en HTML pour l'affichage
$response_html = $response;
$response_html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $response_html);
$response_html = preg_replace('/\[(.+?)\]\((.+?)\)/', '<a href="$2" target="_blank">$1 <i class="fas fa-external-link-alt fa-xs"></i></a>', $response_html);
$response_html = nl2br($response_html);

echo json_encode([
    'success' => true,
    'response' => $response_html,
    'score' => $best_score,
    'timestamp' => date('Y-m-d H:i:s')
]);
