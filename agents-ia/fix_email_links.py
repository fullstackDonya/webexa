#!/usr/bin/env python3
"""
Script pour corriger les liens des notifications email existantes
Remplace emails-view.php par email-inbox.php
"""
from database import Database

def fix_email_notification_links():
    """Corriger tous les liens vers emails-view.php dans les notifications"""
    db = Database()
    conn = db.connect()
    cursor = conn.cursor()
    
    try:
        # Mettre à jour les liens des notifications email
        update_query = """
        UPDATE crm_notifications 
        SET link = REPLACE(link, 'emails-view.php', 'email-inbox.php')
        WHERE link LIKE '%emails-view.php%'
        """
        
        cursor.execute(update_query)
        rows_updated = cursor.rowcount
        
        conn.commit()
        
        print(f"✅ {rows_updated} notification(s) mise(s) à jour")
        print(f"   emails-view.php → email-inbox.php")
        
        # Afficher quelques exemples
        cursor.execute("""
            SELECT id, title, link 
            FROM crm_notifications 
            WHERE link LIKE '%email-inbox.php%' 
            AND type = 'email_new'
            LIMIT 5
        """)
        
        examples = cursor.fetchall()
        if examples:
            print("\n📋 Exemples de notifications corrigées:")
            for notif in examples:
                print(f"   ID #{notif['id']}: {notif['title']}")
                print(f"   → {notif['link']}")
        
    except Exception as e:
        print(f"❌ Erreur: {e}")
        conn.rollback()
    finally:
        cursor.close()
        conn.close()

if __name__ == "__main__":
    print("🔧 Correction des liens des notifications email...\n")
    fix_email_notification_links()
    print("\n✨ Terminé!")
