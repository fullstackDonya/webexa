"""
Exemples d'utilisation des agents IA
"""

# ========================================
# EXEMPLE 1: Analyser un email
# ========================================

from agents import inbox_agent

# Analyser un email entrant
result = inbox_agent.analyze_email(
    email_id=123,
    subject="Demande de renseignements sur vos services",
    body="""
    Bonjour,
    
    Je suis responsable IT chez TechCorp et nous cherchons un partenaire
    pour développer une application mobile.
    
    Budget : environ 50 000€
    Urgence : projet à démarrer dans 2 semaines
    
    Pouvez-vous me rappeler ?
    
    Cordialement,
    Jean Dupont
    +33 6 12 34 56 78
    """,
    sender="jean.dupont@techcorp.com",
    customer_id=1
)

print("Résultat de l'analyse email:")
print(f"Type: {result['analysis']['type']}")
print(f"Priorité: {result['analysis']['priority']}")
print(f"Sentiment: {result['analysis']['sentiment']}")
print(f"Résumé: {result['analysis']['summary']}")
print(f"Lead créé: ID {result['lead_id']}")

if result.get('suggested_response'):
    print(f"\nRéponse suggérée:\n{result['suggested_response']}")


# ========================================
# EXEMPLE 2: Traiter tous les emails en attente
# ========================================

result = inbox_agent.process_pending_emails(
    customer_id=1,
    limit=20  # Traiter maximum 20 emails
)

print(f"\n{result['processed']} emails traités")
for r in result['results']:
    if r['success']:
        print(f"✅ Email #{r['email_id']} - {r['analysis']['type']}")


# ========================================
# EXEMPLE 3: Scorer un lead
# ========================================

from agents import lead_analyst

result = lead_analyst.score_lead(
    lead_id=456,
    customer_id=1
)

print("\nRésultat du scoring:")
print(f"Score: {result['score']}/100")
print(f"Catégorie: {result['category']}")
print(f"Raisonnement: {result['reasoning']}")
print(f"Actions recommandées: {', '.join(result['recommended_actions'])}")


# ========================================
# EXEMPLE 4: Récupérer les leads chauds
# ========================================

hot_leads = lead_analyst.get_hot_leads(
    customer_id=1,
    limit=10
)

print(f"\n{len(hot_leads)} leads chauds trouvés:")
for lead in hot_leads:
    print(f"- {lead['name']} (score: {lead['score']})")


# ========================================
# EXEMPLE 5: Détecter les leads froids
# ========================================

cold_leads = lead_analyst.detect_cold_leads(
    customer_id=1,
    days=30  # Pas d'interaction depuis 30 jours
)

print(f"\n{len(cold_leads)} leads froids détectés:")
for lead in cold_leads:
    print(f"- {lead['name']} (dernière MAJ: {lead['updated_at']})")


# ========================================
# EXEMPLE 6: Utiliser l'API directement
# ========================================

import requests

API_URL = "https://webexa.online"
API_KEY = "bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps"

headers = {
    "Content-Type": "application/json",
    "X-API-Key": API_KEY
}

# Analyser un email via l'API
response = requests.post(
    f"{API_URL}/api/inbox/analyze",
    headers=headers,
    json={
        "email_id": 789,
        "subject": "Question technique",
        "body": "J'ai besoin d'aide pour...",
        "sender": "client@example.com",
        "customer_id": 1
    }
)

data = response.json()
print("\nRéponse API:")
print(data)


# ========================================
# EXEMPLE 7: Depuis PHP (CRM)
# ========================================

"""
<?php
require_once 'api/AIAgentsClient.php';

$aiClient = new AIAgentsClient();

// Analyser un email
$result = $aiClient->analyzeEmail(
    emailId: 123,
    subject: "Demande de devis",
    body: "Bonjour, je voudrais...",
    sender: "client@example.com",
    customerId: 1
);

echo "Type: " . $result['analysis']['type'] . "\n";
echo "Score: " . $result['analysis']['priority'] . "\n";

// Récupérer les actions en attente
$actions = $aiClient->getPendingActions(customerId: 1);
echo "Actions en attente: " . $actions['count'] . "\n";

// Approuver une action
$aiClient->approveAction(actionId: 456);
?>
"""


# ========================================
# EXEMPLE 8: Utiliser le LLM directement
# ========================================

from llm_service import llm

# Analyse de texte personnalisée
result = llm.chat(
    messages=[
        {
            "role": "user",
            "content": "Extrait les informations de contact de ce texte: ..."
        }
    ],
    system="Tu es un assistant qui extrait des informations structurées."
)

print("Résultat LLM:")
print(result)


# ========================================
# EXEMPLE 9: Gestion des actions
# ========================================

from database import AgentAction

# Créer une action
action_id = AgentAction.create(
    agent_name="inbox_agent",
    action_type="send_email",
    target_type="contact",
    target_id=123,
    data={"subject": "Re: Votre demande", "body": "..."},
    status="pending",
    customer_id=1
)

# Récupérer les actions en attente
pending = AgentAction.get_pending(customer_id=1)
print(f"{len(pending)} actions en attente")

# Mettre à jour une action
AgentAction.update_status(
    action_id=action_id,
    status="approved",
    result={"sent": True, "timestamp": "2026-02-25 10:30:00"}
)


# ========================================
# EXEMPLE 10: Logger une action personnalisée
# ========================================

from database import AgentLog

# Créer un log
AgentLog.create(
    agent_name="custom_agent",
    action="custom_task",
    input_data={"param1": "value1"},
    output_data={"result": "success"},
    status="success",
    customer_id=1
)

# Récupérer les logs récents
logs = AgentLog.get_recent(agent_name="inbox_agent", limit=10)
for log in logs:
    print(f"{log['created_at']}: {log['action']} - {log['status']}")
