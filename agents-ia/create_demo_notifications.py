#!/usr/bin/env python
# -*- coding: utf-8 -*-
"""
Script de démonstration pour créer des notifications de test
Permet de voir le système de notifications en action
"""

import sys
import os

# Ajouter le répertoire parent au path
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from database import db
from agents.assistant_agent import assistant

def create_demo_notifications(customer_id: int = 22):
    """Créer des notifications de démonstration"""
    
    print("🎯 Création de notifications de démonstration pour l'Agent IA")
    print("=" * 60)
    
    notifications_created = []
    
    # 1. Email important
    print("\n📧 Création notification: Nouvel email important...")
    notif_id = assistant._create_notification(
        customer_id=customer_id,
        type="email_new",
        title="📧 Nouvel email important",
        message="De: client@example.com - Demande de devis urgente",
        link="email-inbox.php?id=1",
        priority="high"
    )
    notifications_created.append(("Email important", notif_id))
    
    # 2. Message WhatsApp
    print("💬 Création notification: Nouveau message WhatsApp...")
    notif_id = assistant._create_notification(
        customer_id=customer_id,
        type="whatsapp_new",
        title="💬 Nouveau message WhatsApp",
        message="De: Jean Dupont - Question sur le produit",
        link="whatsapp.php?id=1",
        priority="medium"
    )
    notifications_created.append(("WhatsApp", notif_id))
    
    # 3. Tâche en retard
    print("⚠️ Création notification: Tâche en retard...")
    notif_id = assistant._create_notification(
        customer_id=customer_id,
        type="task_overdue",
        title="⚠️ Tâche en retard",
        message="Rappeler Marie Martin - En retard depuis 2 jours",
        link="tasks.php?id=3",
        priority="urgent"
    )
    notifications_created.append(("Tâche en retard", notif_id))
    
    # 4. Rappel d'appel
    print("📞 Création notification: Rappel d'appel...")
    notif_id = assistant._create_notification(
        customer_id=customer_id,
        type="call_reminder",
        title="📞 Rappel d'appel",
        message="Appeler Pierre Durand à 14:00",
        link="calls.php?id=1",
        priority="high"
    )
    notifications_created.append(("Rappel d'appel", notif_id))
    
    # 5. Shift ERP à venir
    print("🕐 Création notification: Shift ERP à venir...")
    notif_id = assistant._create_notification(
        customer_id=customer_id,
        type="shift_reminder",
        title="🕐 Shift à venir",
        message="Sophie Martin (Chauffeur) - Début: 08:00",
        link="../erp/shifts.php?id=1",
        priority="high"
    )
    notifications_created.append(("Shift ERP", notif_id))
    
    # 6. Lead chaud
    print("🔥 Création notification: Lead chaud...")
    notif_id = assistant._create_notification(
        customer_id=customer_id,
        type="lead_hot",
        title="🔥 Lead chaud à contacter",
        message="Paul Bernard - Score IA: 85/100 - Non contacté depuis 4 jours",
        link="leads-view.php?id=1",
        priority="urgent"
    )
    notifications_created.append(("Lead chaud", notif_id))
    
    # 7. Intégration synchronisée
    print("✅ Création notification: Intégration synchronisée...")
    notif_id = assistant._create_notification(
        customer_id=customer_id,
        type="integration_sync",
        title="✅ Email IMAP synchronisé",
        message="12 nouveaux emails récupérés avec succès",
        link="integrations.php",
        priority="low"
    )
    notifications_created.append(("Intégration sync", notif_id))
    
    # 8. Tâche du jour
    print("📋 Création notification: Tâche du jour...")
    notif_id = assistant._create_notification(
        customer_id=customer_id,
        type="task_due",
        title="📋 Tâche à faire aujourd'hui",
        message="Préparer présentation client ACME",
        link="tasks.php?id=2",
        priority="high"
    )
    notifications_created.append(("Tâche du jour", notif_id))
    
    print("\n" + "=" * 60)
    print("✅ Notifications créées avec succès !")
    print("=" * 60)
    
    for name, notif_id in notifications_created:
        status = "✅" if notif_id > 0 else "❌"
        print(f"{status} {name}: ID #{notif_id}")
    
    print("\n💡 Consultez les notifications sur: http://localhost/crm/notifications.php")
    print("🤖 Ces notifications ont été créées par l'Agent IA Assistant")
    
    return len([n for n in notifications_created if n[1] > 0])

if __name__ == "__main__":
    try:
        customer_id = int(sys.argv[1]) if len(sys.argv) > 1 else 22
        count = create_demo_notifications(customer_id)
        print(f"\n🎉 {count} notifications créées pour le client #{customer_id}")
    except Exception as e:
        print(f"\n❌ ERREUR: {e}")
        import traceback
        traceback.print_exc()
        sys.exit(1)
