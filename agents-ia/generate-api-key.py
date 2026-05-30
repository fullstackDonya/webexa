#!/usr/bin/env python3
"""
Générateur de clé API sécurisée pour la production
"""
import secrets
import sys

def generate_secure_key(length=32):
    """Génère une clé API sécurisée"""
    return secrets.token_urlsafe(length)

if __name__ == "__main__":
    print("=" * 60)
    print("🔐 GÉNÉRATEUR DE CLÉ API SÉCURISÉE - PRODUCTION")
    print("=" * 60)
    print()
    
    # Générer plusieurs clés
    print("Voici 3 clés sécurisées générées aléatoirement :")
    print()
    
    for i in range(1, 4):
        key = generate_secure_key(32)
        print(f"Clé {i}: {key}")
    
    print()
    print("=" * 60)
    print("⚠️  IMPORTANT - SÉCURITÉ")
    print("=" * 60)
    print()
    print("1. Choisissez UNE SEULE de ces clés")
    print("2. Notez-la dans un gestionnaire de mots de passe sécurisé")
    print("3. Utilisez la MÊME clé dans :")
    print("   - /var/www/crm/ia/.env (API_SECRET_KEY)")
    print("   - /var/www/crm/.env (AI_API_KEY)")
    print("   - /var/www/crm/api/AIAgentsClient.php")
    print()
    print("4. NE JAMAIS commiter cette clé dans Git !")
    print("5. Changez cette clé si elle est compromise")
    print()
    print("=" * 60)
    
    # Option pour en générer d'autres
    while True:
        response = input("\nGénérer une nouvelle clé ? (o/N) : ").strip().lower()
        if response == 'o':
            new_key = generate_secure_key(32)
            print(f"\nNouvelle clé : {new_key}")
        else:
            break
    
    print("\n✅ Terminé\n")
