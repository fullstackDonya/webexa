#!/usr/bin/env python3
"""
Test de l'Assistant Agent
"""
from agents.assistant_agent import assistant
from database import db
import json

def test_assistant_agent():
    """Tester l'agent assistant"""
    
    print("=" * 60)
    print("TEST DE L'ASSISTANT AGENT")
    print("=" * 60)
    
    # Utiliser customer_id = 22 (comme dans les notifications de démo)
    customer_id = 22
    
    print(f"\n1. Test de vérification quotidienne pour customer_id={customer_id}")
    print("-" * 60)
    
    result = assistant.run_daily_check(customer_id)
    
    if result['success']:
        print("✓ Vérification réussie!")
        print(f"\nRésultats:")
        print(f"  - Nouveaux emails: {result['results']['new_emails']}")
        print(f"  - Nouveaux WhatsApp: {result['results']['new_whatsapp']}")
        print(f"  - Tâches du jour: {result['results']['tasks_due']}")
        print(f"  - Tâches en retard: {result['results']['tasks_overdue']}")
        print(f"  - Notifications créées: {result['results']['notifications_created']}")
        print(f"  - Intégrations synchronisées: {result['results']['integrations_synced']}")
        
        if result['results']['errors']:
            print(f"\n⚠ Erreurs:")
            for error in result['results']['errors']:
                print(f"  - {error['integration']}: {error['error']}")
    else:
        print(f"✗ Erreur: {result.get('error', 'Erreur inconnue')}")
    
    # Vérifier les notifications créées
    print(f"\n2. Vérification des notifications créées")
    print("-" * 60)
    
    notifications = db.execute("""
        SELECT id, type, title, message, priority, created_at, is_read
        FROM crm_notifications
        WHERE customer_id = %s
        ORDER BY created_at DESC
        LIMIT 10
    """, (customer_id,))
    
    if notifications:
        print(f"Dernières {len(notifications)} notifications:")
        for notif in notifications:
            status = "✓" if notif['is_read'] else "✗"
            priority_emoji = {
                'low': '🔵',
                'medium': '🟡',
                'high': '🟠',
                'urgent': '🔴'
            }.get(notif['priority'], '⚪')
            
            print(f"\n  {status} {priority_emoji} [{notif['type']}] {notif['title']}")
            print(f"     {notif['message']}")
            print(f"     Créée: {notif['created_at']}")
    else:
        print("Aucune notification trouvée")
    
    # Tester les différentes vérifications individuellement
    print(f"\n3. Test des vérifications individuelles")
    print("-" * 60)
    
    print("\n  a) Vérification emails:")
    email_result = assistant._check_new_emails(customer_id)
    print(f"     {email_result['count']} nouveaux emails, {email_result['notifications']} notifications")
    
    print("\n  b) Vérification WhatsApp:")
    whatsapp_result = assistant._check_new_whatsapp(customer_id)
    print(f"     {whatsapp_result['count']} nouveaux messages, {whatsapp_result['notifications']} notifications")
    
    print("\n  c) Vérification tâches:")
    tasks_result = assistant._check_tasks_due(customer_id)
    print(f"     {tasks_result['due_count']} tâches du jour")
    print(f"     {tasks_result['overdue_count']} tâches en retard")
    print(f"     {tasks_result['notifications']} notifications créées")
    
    print("\n  d) Vérification leads chauds:")
    leads_result = assistant._check_hot_leads(customer_id)
    print(f"     {leads_result['count']} leads chauds, {leads_result['notifications']} notifications")
    
    print("\n  e) Vérification shifts ERP:")
    shifts_result = assistant._check_upcoming_shifts(customer_id)
    print(f"     {shifts_result['count']} shifts à venir, {shifts_result['notifications']} notifications")
    
    print("\n  f) Synchronisation intégrations:")
    sync_result = assistant._synchronize_integrations(customer_id)
    print(f"     {sync_result['synced']} intégrations synchronisées")
    if sync_result['errors']:
        print(f"     {len(sync_result['errors'])} erreurs")
    
    print("\n" + "=" * 60)
    print("FIN DES TESTS")
    print("=" * 60)


if __name__ == "__main__":
    try:
        test_assistant_agent()
    except Exception as e:
        print(f"\n❌ ERREUR: {e}")
        import traceback
        traceback.print_exc()
