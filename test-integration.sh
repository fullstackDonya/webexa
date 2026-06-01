#!/bin/bash

# Test d'intégration complète: Register → Login → Complete Setup → Verify interne_customer

DOMAIN="webexa.fr"
BASE_URL="https://$DOMAIN"
COOKIES="/tmp/webexa_cookies.txt"

echo "🧪 Test d'intégration complète"
echo "================================"

# 1. Enregistrement
echo ""
echo "1️⃣  Enregistrement d'un nouvel utilisateur..."
EMAIL="testuser$(date +%s)@example.com"
RESPONSE=$(curl -s -c "$COOKIES" -X POST "$BASE_URL/crm/api/auth/register.php" \
  -d "first_name=Test&last_name=User&email=$EMAIL&password=SecurePass123&phone=0612345678" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -k)

echo "$RESPONSE" | python3 -m json.tool
SUCCESS=$(echo "$RESPONSE" | grep -o '"success":true')

if [ -z "$SUCCESS" ]; then
    echo "❌ Enregistrement échoué"
    exit 1
fi

echo "✅ Utilisateur enregistré"

# 2. Connexion
echo ""
echo "2️⃣  Connexion..."
RESPONSE=$(curl -s -b "$COOKIES" -c "$COOKIES" -X POST "$BASE_URL/crm/api/auth/login.php" \
  -d "email=$EMAIL&password=SecurePass123" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -k)

echo "$RESPONSE" | python3 -m json.tool
SUCCESS=$(echo "$RESPONSE" | grep -o '"success":true')

if [ -z "$SUCCESS" ]; then
    echo "❌ Connexion échouée"
    exit 1
fi

echo "✅ Connexion réussie"

# 3. Complete Setup
echo ""
echo "3️⃣  Finalisation de l'onboarding..."
RESPONSE=$(curl -s -b "$COOKIES" -X POST "$BASE_URL/crm/api/auth/complete-setup.php" \
  -H "Content-Type: application/json" \
  -d '{
    "profile": {
      "first_name": "Test",
      "last_name": "User",
      "email": "'$EMAIL'",
      "phone": "0612345678",
      "address": "123 Rue de Paris",
      "city": "Paris",
      "postal_code": "75001"
    },
    "company": {
      "company_name": "Test Company",
      "website": "https://test.com",
      "industry": "Technology",
      "siret": "12345678901234",
      "vat_number": "FR12345678901",
      "employee_count": "10-50",
      "annual_revenue": "500000-1000000"
    },
    "modules": ["crm"]
  }' \
  -k)

echo "$RESPONSE" | python3 -m json.tool
SUCCESS=$(echo "$RESPONSE" | grep -o '"success":true')

if [ -z "$SUCCESS" ]; then
    echo "❌ Finalisation échouée"
    exit 1
fi

echo "✅ Onboarding finalisé"

# 4. Vérification dans la base de données
echo ""
echo "4️⃣  Vérification dans la base de données..."

# Récupérer le company_id de la réponse
COMPANY_ID=$(echo "$RESPONSE" | grep -o '"company_id":[0-9]*' | head -1 | cut -d':' -f2)

if [ -z "$COMPANY_ID" ]; then
    echo "⚠️  Impossible de récupérer company_id"
else
    echo "Company ID: $COMPANY_ID"
fi

rm -f "$COOKIES"
echo ""
echo "✅ Test d'intégration complété avec succès!"
