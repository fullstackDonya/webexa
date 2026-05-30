# 🔐 Guide : Clé API pour Oracle Cloud + Hostinger

## ❓ Question

**Où récupérer la clé API_SECRET_KEY ?**

## ✅ Réponse

La clé `API_SECRET_KEY` **N'EST PAS** récupérée depuis Oracle Cloud !

C'est une **clé secrète que VOUS créez** et qui doit être **IDENTIQUE** sur les deux serveurs :

```
┌─────────────────────────────┐         ┌──────────────────────────┐
│   ORACLE CLOUD              │         │   HOSTINGER              │
│                             │         │                          │
│   .env                      │         │   .env                   │
│   API_SECRET_KEY=ABC123     │ ◄─────► │   AI_API_KEY=ABC123      │
│   (LA MÊME CLÉ)             │         │   (LA MÊME CLÉ)          │
└─────────────────────────────┘         └──────────────────────────┘
```

---

## 📝 Trois options pour obtenir la clé

### Option 1 : Utiliser la clé actuelle (déjà présente)

Votre fichier `.env.production` contient déjà une clé valide :

```env
API_SECRET_KEY=bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps
```

✅ **Cette clé est bonne** et peut être utilisée directement.

**Action requise : Aucune** - gardez cette clé.

---

### Option 2 : Générer une nouvelle clé avec le script

Si vous voulez une NOUVELLE clé :

```bash
cd /Applications/MAMP/htdocs/PP/webitech/WEB/crm/ia
python3 generate-api-key.py
```

**Résultat :**
```
============================================================
🔐 GÉNÉRATEUR DE CLÉ API SÉCURISÉE - PRODUCTION
============================================================

Voici 3 clés sécurisées générées aléatoirement :

Clé 1: xK9mP2nQ8vL4wR7jT5yU3aB6cE1fH0gI9dJ8kM2nO5pQ
Clé 2: aB3cD4eF5gH6iJ7kL8mN9oP0qR1sT2uV3wX4yZ5A6B7C
Clé 3: 1A2B3C4D5E6F7G8H9I0J1K2L3M4N5O6P7Q8R9S0T1U2V
```

Choisissez une de ces clés et utilisez-la partout.

---

### Option 3 : Générer manuellement avec OpenSSL

```bash
openssl rand -base64 32
```

**Résultat :**
```
pQ8R7tYuI9oK6mN4aS2dF5gH1jK0lZ3xC7vB9nM2qW5E
```

---

## 🔧 Comment utiliser la clé

### Sur ORACLE CLOUD

**Fichier : `/var/www/agents-ia/.env`**

```env
API_SECRET_KEY=bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps
```

---

### Sur HOSTINGER (CRM)

**Option A : Fichier `.env` (recommandé)**

**Fichier : `/home/VOTRE_USER/public_html/crm/.env`**

```env
AI_API_KEY=bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps
```

**Option B : Hardcodé dans AIAgentsClient.php**

**Fichier : `crm/api/AIAgentsClient.php`**

```php
class AIAgentsClient {
    private $apiKey = 'bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps';
    // ...
}
```

---

## ✅ Vérification : Les clés doivent être identiques

### Vérifier sur Oracle Cloud

```bash
ssh opc@VOTRE_IP_ORACLE
grep API_SECRET_KEY /var/www/agents-ia/.env
```

### Vérifier sur Hostinger

```bash
ssh VOTRE_USER@HOSTINGER
grep AI_API_KEY ~/public_html/crm/.env
```

### Les deux doivent afficher la MÊME valeur :

```
API_SECRET_KEY=bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps
AI_API_KEY=bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps
```

---

## ⚠️ Erreurs courantes

### Erreur : "API Error (401): Invalid API key"

**Cause :** Les clés ne sont pas identiques

**Solution :**
```bash
# Sur Oracle
grep API_SECRET_KEY /var/www/agents-ia/.env

# Sur Hostinger
grep AI_API_KEY ~/public_html/crm/.env

# Les deux doivent être EXACTEMENT identiques (pas d'espace, même casse)
```

---

## 🔐 Sécurité

### ✅ À FAIRE

- ✅ Générer une clé aléatoire forte (32+ caractères)
- ✅ Utiliser la même clé sur Oracle et Hostinger
- ✅ Stocker la clé dans un gestionnaire de mots de passe
- ✅ Ajouter `.env` au `.gitignore`
- ✅ Protéger `.env` avec `.htaccess` sur Hostinger

### ❌ À NE PAS FAIRE

- ❌ Utiliser une clé simple comme "123456" ou "password"
- ❌ Commiter la clé dans Git
- ❌ Partager la clé publiquement
- ❌ Utiliser des clés différentes sur Oracle et Hostinger
- ❌ Chercher la clé sur Oracle Cloud Console (elle n'existe pas là-bas)

---

## 📊 Résumé visuel

```
┌──────────────────────────────────────────────────────────┐
│  ÉTAPE 1 : CHOISIR LA CLÉ                                │
│                                                           │
│  Option A : Garder la clé actuelle                       │
│  bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps             │
│                                                           │
│  Option B : Générer nouvelle                             │
│  python3 generate-api-key.py                             │
└──────────────────────────────────────────────────────────┘
                           ▼
┌──────────────────────────────────────────────────────────┐
│  ÉTAPE 2 : UTILISER PARTOUT                              │
│                                                           │
│  Oracle Cloud (.env)                                     │
│  API_SECRET_KEY=votre_clé                                │
│                                                           │
│  Hostinger (.env)                                        │
│  AI_API_KEY=votre_clé                                    │
│                                                           │
│  ⚠️ IDENTIQUES !                                         │
└──────────────────────────────────────────────────────────┘
                           ▼
┌──────────────────────────────────────────────────────────┐
│  ÉTAPE 3 : VÉRIFIER                                      │
│                                                           │
│  curl -H "X-API-Key: votre_clé" \                        │
│       http://IP_ORACLE:8000/health                       │
│                                                           │
│  ✅ Succès : Clés synchronisées                          │
│  ❌ 401 : Clés différentes                               │
└──────────────────────────────────────────────────────────┘
```

---

## 🎯 CONCLUSION

La clé `API_SECRET_KEY` :

1. **N'est PAS** fournie par Oracle Cloud
2. **EST** générée par vous avec `generate-api-key.py` ou `openssl`
3. **DOIT** être identique sur Oracle et Hostinger
4. **NE DOIT PAS** être commitée dans Git

La clé actuelle `bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps` est déjà présente et valide. 

**Vous pouvez la garder tel quel !** ✅
