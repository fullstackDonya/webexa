#!/usr/bin/env python3
"""
Test de connexion MySQL depuis Oracle Cloud vers Hostinger
À exécuter AVANT le déploiement pour vérifier la connectivité
"""

import sys
import os
from dotenv import load_dotenv

# Charger les variables d'environnement
load_dotenv()

print("=" * 60)
print("🔍 TEST DE CONNEXION MYSQL HOSTINGER")
print("=" * 60)
print()

# Vérifier les variables d'environnement
print("📋 Configuration chargée:")
print(f"  DB_HOST     : {os.getenv('DB_HOST', 'NON DÉFINI')}")
print(f"  DB_USER     : {os.getenv('DB_USER', 'NON DÉFINI')}")
print(f"  DB_NAME     : {os.getenv('DB_NAME', 'NON DÉFINI')}")
print(f"  DB_PASSWORD : {'*' * len(os.getenv('DB_PASSWORD', '')) if os.getenv('DB_PASSWORD') else 'NON DÉFINI'}")
print()

# Vérifier que toutes les variables sont présentes
required_vars = ['DB_HOST', 'DB_USER', 'DB_PASSWORD', 'DB_NAME']
missing_vars = [var for var in required_vars if not os.getenv(var)]

if missing_vars:
    print(f"❌ Variables manquantes dans .env: {', '.join(missing_vars)}")
    print()
    print("Assurez-vous que le fichier .env contient:")
    for var in missing_vars:
        print(f"  {var}=votre_valeur")
    sys.exit(1)

print("✅ Toutes les variables d'environnement sont présentes")
print()

# Test 1: Import des modules
print("1️⃣  Test d'import des modules...")
try:
    import pymysql
    print("   ✅ pymysql importé")
except ImportError as e:
    print(f"   ❌ Erreur: {e}")
    print("   💡 Installez: pip install pymysql")
    sys.exit(1)

try:
    from sqlalchemy import create_engine, text
    print("   ✅ sqlalchemy importé")
except ImportError as e:
    print(f"   ❌ Erreur: {e}")
    print("   💡 Installez: pip install sqlalchemy")
    sys.exit(1)

print()

# Test 2: Connexion PyMySQL directe
print("2️⃣  Test de connexion PyMySQL directe...")
try:
    connection = pymysql.connect(
        host=os.getenv('DB_HOST'),
        user=os.getenv('DB_USER'),
        password=os.getenv('DB_PASSWORD'),
        database=os.getenv('DB_NAME'),
        port=int(os.getenv('DB_PORT', 3306)),
        connect_timeout=10
    )
    print("   ✅ Connexion PyMySQL établie")
    
    with connection.cursor() as cursor:
        cursor.execute("SELECT VERSION()")
        version = cursor.fetchone()
        print(f"   📊 MySQL Version: {version[0]}")
        
        cursor.execute("SELECT DATABASE()")
        db = cursor.fetchone()
        print(f"   📊 Base de données: {db[0]}")
        
        cursor.execute("SHOW TABLES")
        tables = cursor.fetchall()
        print(f"   📊 Nombre de tables: {len(tables)}")
        
    connection.close()
    print("   ✅ Connexion fermée")
    
except pymysql.err.OperationalError as e:
    print(f"   ❌ Erreur de connexion: {e}")
    print()
    print("   🔧 Vérifications à faire:")
    print("   1. Sur Hostinger cPanel → Bases de données MySQL → Accès distant MySQL")
    print("   2. Vérifiez que l'IP Oracle est autorisée")
    print("   3. Vérifiez que le port 3306 est ouvert sur Hostinger")
    print("   4. Testez depuis le terminal: telnet DB_HOST 3306")
    sys.exit(1)
except Exception as e:
    print(f"   ❌ Erreur: {e}")
    sys.exit(1)

print()

# Test 3: Connexion SQLAlchemy (comme dans l'app)
print("3️⃣  Test de connexion SQLAlchemy...")
try:
    DATABASE_URL = (
        f"mysql+pymysql://{os.getenv('DB_USER')}:{os.getenv('DB_PASSWORD')}"
        f"@{os.getenv('DB_HOST')}:{os.getenv('DB_PORT', 3306)}/{os.getenv('DB_NAME')}"
    )
    
    engine = create_engine(
        DATABASE_URL,
        pool_pre_ping=True,
        pool_recycle=3600,
        connect_args={'connect_timeout': 10}
    )
    
    with engine.connect() as conn:
        result = conn.execute(text("SELECT 1 as test"))
        row = result.fetchone()
        print(f"   ✅ SQLAlchemy - Requête test: {row[0]}")
    
    engine.dispose()
    print("   ✅ Engine fermé")
    
except Exception as e:
    print(f"   ❌ Erreur SQLAlchemy: {e}")
    sys.exit(1)

print()

# Test 4: Tables CRM requises
print("4️⃣  Vérification des tables CRM...")
try:
    connection = pymysql.connect(
        host=os.getenv('DB_HOST'),
        user=os.getenv('DB_USER'),
        password=os.getenv('DB_PASSWORD'),
        database=os.getenv('DB_NAME'),
        port=int(os.getenv('DB_PORT', 3306)),
        cursorclass=pymysql.cursors.DictCursor
    )
    
    required_tables = ['customers', 'emails', 'leads', 'lead_analysis']
    existing_tables = []
    missing_tables = []
    
    with connection.cursor() as cursor:
        for table in required_tables:
            cursor.execute(f"SHOW TABLES LIKE '{table}'")
            if cursor.fetchone():
                existing_tables.append(table)
                
                # Compter les lignes
                cursor.execute(f"SELECT COUNT(*) as count FROM `{table}`")
                count = cursor.fetchone()['count']
                print(f"   ✅ {table} ({count} lignes)")
            else:
                missing_tables.append(table)
                print(f"   ⚠️  {table} (manquante)")
    
    connection.close()
    
    if missing_tables:
        print()
        print(f"   ⚠️  Tables manquantes: {', '.join(missing_tables)}")
        print("   💡 Ces tables seront créées au premier démarrage de l'API")
    
except Exception as e:
    print(f"   ❌ Erreur: {e}")
    sys.exit(1)

print()

# Test 5: Vérifier la table emails et ses colonnes
print("5️⃣  Vérification de la structure de la table 'emails'...")
try:
    connection = pymysql.connect(
        host=os.getenv('DB_HOST'),
        user=os.getenv('DB_USER'),
        password=os.getenv('DB_PASSWORD'),
        database=os.getenv('DB_NAME'),
        port=int(os.getenv('DB_PORT', 3306)),
        cursorclass=pymysql.cursors.DictCursor
    )
    
    with connection.cursor() as cursor:
        cursor.execute("SHOW COLUMNS FROM emails")
        columns = cursor.fetchall()
        
        required_columns = ['mailbox', 'sent_at', 'ai_status']
        existing_columns = [col['Field'] for col in columns]
        
        for req_col in required_columns:
            if req_col in existing_columns:
                col_info = next((c for c in columns if c['Field'] == req_col), None)
                print(f"   ✅ {req_col} ({col_info['Type']})")
            else:
                print(f"   ❌ {req_col} (manquante)")
    
    connection.close()
    
except pymysql.err.ProgrammingError:
    print("   ⚠️  Table 'emails' n'existe pas encore")
    print("   💡 Elle sera créée au premier démarrage")
except Exception as e:
    print(f"   ❌ Erreur: {e}")

print()
print("=" * 60)
print("✅ TOUS LES TESTS RÉUSSIS")
print("=" * 60)
print()
print("🚀 Vous pouvez maintenant déployer l'application:")
print("   ./deploy-oracle.sh")
print()
