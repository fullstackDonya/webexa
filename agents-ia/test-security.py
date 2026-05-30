#!/usr/bin/env python3
"""
Test de sécurité avant déploiement en production
"""
import os
import sys
from pathlib import Path

# Couleurs
RED = '\033[0;31m'
GREEN = '\033[0;32m'
YELLOW = '\033[1;33m'
BLUE = '\033[0;34m'
NC = '\033[0m'

def test_env_file():
    """Vérifier le fichier .env"""
    print(f"\n{BLUE}🔍 Test 1: Fichier .env{NC}")
    
    if not Path('.env').exists():
        print(f"{RED}❌ Fichier .env manquant{NC}")
        return False
    
    with open('.env', 'r') as f:
        content = f.read()
    
    # Vérifier les valeurs par défaut dangereuses
    dangerous_patterns = [
        ('dev-secret-key-change-me', 'Clé API par défaut'),
        ('GENEREZ_UNE_CLE', 'Clé API non générée'),
        ('CHANGEZ_CE_MOT_DE_PASSE', 'Mot de passe DB non changé'),
        ('localhost', 'Host en localhost (devrait être un vrai serveur)'),
        ('root:root', 'Credentials root dangereux'),
    ]
    
    issues = []
    for pattern, message in dangerous_patterns:
        if pattern in content:
            issues.append(f"  - {message}")
    
    if issues:
        print(f"{RED}❌ Problèmes détectés :{NC}")
        for issue in issues:
            print(f"{RED}{issue}{NC}")
        return False
    
    print(f"{GREEN}✅ Fichier .env semble sécurisé{NC}")
    return True

def test_api_key_strength():
    """Vérifier la force de la clé API"""
    print(f"\n{BLUE}🔍 Test 2: Force de la clé API{NC}")
    
    from dotenv import load_dotenv
    load_dotenv()
    
    api_key = os.getenv('API_SECRET_KEY', '')
    
    if len(api_key) < 32:
        print(f"{RED}❌ Clé API trop courte ({len(api_key)} caractères, minimum 32){NC}")
        return False
    
    # Vérifier la complexité
    has_upper = any(c.isupper() for c in api_key)
    has_lower = any(c.islower() for c in api_key)
    has_digit = any(c.isdigit() for c in api_key)
    has_special = any(not c.isalnum() for c in api_key)
    
    complexity_score = sum([has_upper, has_lower, has_digit, has_special])
    
    if complexity_score < 3:
        print(f"{YELLOW}⚠️  Clé API faible (score: {complexity_score}/4){NC}")
        print(f"{YELLOW}Recommandation: Utilisez generate-api-key.py{NC}")
        return False
    
    print(f"{GREEN}✅ Clé API forte ({len(api_key)} caractères, score: {complexity_score}/4){NC}")
    return True

def test_file_permissions():
    """Vérifier les permissions des fichiers"""
    print(f"\n{BLUE}🔍 Test 3: Permissions des fichiers{NC}")
    
    env_stat = os.stat('.env')
    env_perms = oct(env_stat.st_mode)[-3:]
    
    if env_perms != '600':
        print(f"{YELLOW}⚠️  Permissions .env: {env_perms} (recommandé: 600){NC}")
        print(f"{BLUE}ℹ️  Exécutez: chmod 600 .env{NC}")
        return False
    
    print(f"{GREEN}✅ Permissions fichiers correctes{NC}")
    return True

def test_database_connection():
    """Tester la connexion à la base de données"""
    print(f"\n{BLUE}🔍 Test 4: Connexion base de données{NC}")
    
    try:
        from database import db
        result = db.execute("SELECT 1")
        print(f"{GREEN}✅ Connexion DB réussie{NC}")
        return True
    except Exception as e:
        print(f"{RED}❌ Erreur connexion DB: {e}{NC}")
        return False

def test_dependencies():
    """Vérifier les dépendances"""
    print(f"\n{BLUE}🔍 Test 5: Dépendances Python{NC}")
    
    try:
        import fastapi
        import uvicorn
        import pymysql
        import openai
        print(f"{GREEN}✅ Dépendances principales installées{NC}")
        return True
    except ImportError as e:
        print(f"{RED}❌ Dépendance manquante: {e}{NC}")
        print(f"{BLUE}ℹ️  Exécutez: pip install -r requirements.txt{NC}")
        return False

def test_env_variables():
    """Vérifier les variables d'environnement critiques"""
    print(f"\n{BLUE}🔍 Test 6: Variables d'environnement{NC}")
    
    from dotenv import load_dotenv
    load_dotenv()
    
    required_vars = [
        'DB_HOST',
        'DB_NAME',
        'DB_USER',
        'DB_PASSWORD',
        'API_SECRET_KEY',
        'OPENAI_API_KEY',
    ]
    
    missing = []
    for var in required_vars:
        if not os.getenv(var):
            missing.append(var)
    
    if missing:
        print(f"{RED}❌ Variables manquantes:{NC}")
        for var in missing:
            print(f"{RED}  - {var}{NC}")
        return False
    
    print(f"{GREEN}✅ Toutes les variables requises sont définies{NC}")
    return True

def test_production_mode():
    """Vérifier la configuration production"""
    print(f"\n{BLUE}🔍 Test 7: Mode production{NC}")
    
    from dotenv import load_dotenv
    load_dotenv()
    
    env = os.getenv('ENV', 'development')
    if env != 'production':
        print(f"{YELLOW}⚠️  ENV={env} (devrait être 'production'){NC}")
        return False
    
    automation_mode = os.getenv('AUTOMATION_MODE', 'assisted')
    if automation_mode == 'autonomous':
        print(f"{YELLOW}⚠️  AUTOMATION_MODE=autonomous (dangereux en production !){NC}")
        return False
    
    print(f"{GREEN}✅ Configuration production correcte{NC}")
    return True

def main():
    """Exécuter tous les tests"""
    print("=" * 60)
    print(f"{BLUE}🔒 TEST DE SÉCURITÉ - DÉPLOIEMENT PRODUCTION{NC}")
    print("=" * 60)
    
    tests = [
        test_env_file,
        test_api_key_strength,
        test_file_permissions,
        test_database_connection,
        test_dependencies,
        test_env_variables,
        test_production_mode,
    ]
    
    results = []
    for test in tests:
        try:
            result = test()
            results.append(result)
        except Exception as e:
            print(f"{RED}❌ Erreur test: {e}{NC}")
            results.append(False)
    
    # Résumé
    print("\n" + "=" * 60)
    passed = sum(results)
    total = len(results)
    
    if passed == total:
        print(f"{GREEN}✅ TOUS LES TESTS RÉUSSIS ({passed}/{total}){NC}")
        print(f"{GREEN}Le déploiement peut continuer 🚀{NC}")
        sys.exit(0)
    else:
        failed = total - passed
        print(f"{RED}❌ {failed} TEST(S) ÉCHOUÉ(S) ({passed}/{total}){NC}")
        print(f"{YELLOW}⚠️  CORRIGEZ LES PROBLÈMES AVANT DE DÉPLOYER{NC}")
        sys.exit(1)

if __name__ == "__main__":
    main()
