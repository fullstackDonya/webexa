#!/usr/bin/env python
# -*- coding: utf-8 -*-
"""
Script de vérification des tables de l'Assistant Agent
Vérifie que toutes les tables et colonnes nécessaires existent
"""

import sys
import os

# Ajouter le répertoire parent au path
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from database import db

def check_table_exists(table_name: str) -> bool:
    """Vérifier si une table existe"""
    result = db.execute_one(
        "SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = %s",
        (table_name,)
    )
    return result['count'] > 0 if result else False

def check_column_exists(table_name: str, column_name: str) -> bool:
    """Vérifier si une colonne existe dans une table"""
    result = db.execute_one(
        """
        SELECT COUNT(*) as count 
        FROM information_schema.columns 
        WHERE table_schema = DATABASE() 
        AND table_name = %s 
        AND column_name = %s
        """,
        (table_name, column_name)
    )
    return result['count'] > 0 if result else False

def main():
    print("🔍 Vérification des tables de l'Assistant Agent\n")
    print("=" * 60)
    
    # Tables à vérifier
    tables_config = {
        "emails": {
            "required": True,
            "columns": ["id", "customer_id", "from_address", "subject", "email_date", "is_read", "priority", "status", "user_id", "is_replied"]
        },
        "whatsapp_messages": {
            "required": True,
            "columns": ["id", "customer_id", "from_phone", "content", "created_at", "is_read", "user_id", "is_replied", "direction"]
        },
        "tasks": {
            "required": True,
            "columns": ["id", "customer_id", "title", "due_date", "priority", "status"]
        },
        "call_reminders": {
            "required": True,
            "columns": ["id", "customer_id", "contact_name", "phone", "scheduled_time", "status"]
        },
        "erp_shifts": {
            "required": True,
            "columns": ["id", "customer_id", "employee_id", "start_datetime", "end_datetime", "role"]
        },
        "erp_employees": {
            "required": True,
            "columns": ["id", "customer_id", "first_name", "last_name", "job_title"]
        },
        "integrations": {
            "required": True,
            "columns": ["id", "customer_id", "integration_type", "is_active"]
        },
        "crm_notifications": {
            "required": True,
            "columns": ["id", "customer_id", "type", "title", "message", "is_read", "priority"]
        }
    }
    
    all_ok = True
    
    for table_name, config in tables_config.items():
        print(f"\n📋 Table: {table_name}")
        
        # Vérifier l'existence de la table
        table_exists = check_table_exists(table_name)
        
        if not table_exists:
            if config["required"]:
                print(f"   ❌ Table manquante (REQUIS)")
                all_ok = False
            else:
                print(f"   ⚠️  Table manquante (optionnel)")
            continue
        else:
            print(f"   ✅ Table existe")
        
        # Vérifier les colonnes
        missing_columns = []
        for column in config["columns"]:
            if not check_column_exists(table_name, column):
                missing_columns.append(column)
        
        if missing_columns:
            print(f"   ❌ Colonnes manquantes: {', '.join(missing_columns)}")
            all_ok = False
        else:
            print(f"   ✅ Toutes les colonnes présentes ({len(config['columns'])} colonnes)")
    
    print("\n" + "=" * 60)
    
    if all_ok:
        print("✅ Toutes les tables et colonnes sont présentes !")
        print("\n💡 Vous pouvez maintenant lancer:")
        print("   python test_assistant.py")
        return 0
    else:
        print("❌ Certaines tables ou colonnes sont manquantes")
        print("\n💡 Exécutez la migration pour créer les tables:")
        print("   mysql -u root -proot webitech < migrations/create_assistant_agent_tables.sql")
        return 1

if __name__ == "__main__":
    try:
        exit_code = main()
        sys.exit(exit_code)
    except Exception as e:
        print(f"\n❌ Erreur: {e}")
        sys.exit(1)
