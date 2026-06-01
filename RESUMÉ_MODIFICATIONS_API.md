# 📝 Résumé des Modifications - Configuration API Agents IA

## 🎯 Objectif

Configurer l'application Webexa pour utiliser **deux domaines séparés**:
- **webexa.fr** → CRM/ERP (PHP) + Nginx + PHP-FPM
- **webexa.online** → API Agents IA (Python FastAPI) + Nginx + Gunicorn

---

## ✅ Changements Effectués

### 1. 📄 Fichiers PHP Modifiés

#### `/crm/api/AIAgentsClient.php`
**Changement:** Configuration URL de base

**Avant:**
```php
public function __construct(
    string $baseUrl = 'http://localhost:8000',
```

**Après:**
```php
public function __construct(
    string $baseUrl = '',
    ?string $apiKey = null,
    int $timeout = 30
) {
    // Use environment variable if no baseUrl provided
    if (empty($baseUrl)) {
        $baseUrl = $_ENV['AI_API_BASE_URL'] ?? 'https://webexa.online';
    }
```

**Impact:** 
- La classe charge maintenant l'URL depuis `.env` ou utilise `https://webexa.online` par défaut
- Permet une configuration flexible selon l'environnement (dev/prod)

---

#### `/crm/api/ai-agents.php`
**Changement:** Initialisation du client avec variables d'environnement

**Avant:**
```php
// Initialiser le client API
$aiClient = new AIAgentsClient();
```

**Après:**
```php
// Initialiser le client API avec l'URL de base depuis .env
$aiClientConfig = [
    'baseUrl' => $_ENV['AI_API_BASE_URL'] ?? 'https://webexa.online',
    'apiKey' => $_ENV['AI_API_KEY'] ?? 'bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps'
];
$aiClient = new AIAgentsClient($aiClientConfig['baseUrl'], $aiClientConfig['apiKey']);
```

**Impact:** 
- Configurations centralisées depuis `.env`
- Pas besoin de modifier le code pour changer l'URL d'API

---

#### `/crm/api/test-ai-connection.php`
**Changement:** Mise à jour de la configuration pour production

**Avant:**
```php
$apiUrl = getenv('AI_API_URL') ?: 'http://localhost:8000';
$apiKey = getenv('AI_API_KEY') ?: 'dev-secret-key-change-me';

if (!$envLoaded && $apiUrl === 'http://localhost:8000') {
    echo "⚠️  Configuration par défaut utilisée\n";
    echo "   AI_API_URL=http://VOTRE_IP_ORACLE:8000\n";
```

**Après:**
```php
$apiUrl = getenv('AI_API_BASE_URL') ?: 'https://webexa.online';
$apiKey = getenv('AI_API_KEY') ?: 'bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps';

if (!$envLoaded && $apiUrl === 'https://webexa.online') {
    echo "⚠️  Configuration par défaut utilisée\n";
    echo "   AI_API_BASE_URL=https://webexa.online\n";
```

**Impact:** 
- Fichier de test utilise maintenant les bonnes variables d'environnement
- Affiche les bons conseils pour la configuration

---

### 2. 🐍 Fichiers Python Modifiés

#### `/agents-ia/examples.py`
**Changement:** Mise à jour des exemples pour production

**Avant:**
```python
API_URL = "http://localhost:8000"
API_KEY = "your-secret-key"
```

**Après:**
```python
API_URL = "https://webexa.online"
API_KEY = "bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps"
```

**Impact:** 
- Les exemples maintenant pointent vers le bon domaine
- Les développeurs obtiennent des exemples fonctionnels directement

---

### 3. 🔧 Fichiers de Configuration Modifiés

#### `/.env`
**Changement:** Ajout de la configuration API agents IA

**Avant:**
```bash
# AI Agents Configuration
AI_API_KEY=bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps
```

**Après:**
```bash
# AI Agents Configuration
AI_API_BASE_URL=https://webexa.online
AI_API_KEY=bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps
```

**Impact:** 
- Nouvelle variable `AI_API_BASE_URL` permet de configurer l'URL de l'API
- Centralize toutes les configurations d'intégration

---

### 4. 📚 Nouveau Fichier de Documentation

#### `/CONFIGURATION_DEUX_DOMAINES.md` (NOUVEAU)
**Contenu:** Guide complet de configuration pour deux domaines

**Sections:**
- Vue d'ensemble de l'architecture
- Diagramme de communication
- Configuration des fichiers `.env`
- Points d'intégration (endpoints API)
- Flux de communication complets
- Considérations de sécurité (CORS, authentification)
- Guide de déploiement étape par étape
- Tests de connectivité
- Dépannage des problèmes courants
- Checklist de déploiement

---

## 🔐 Configuration des Clés API

### Clé API Partagée
```
API_KEY: bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps
```

**Localisation:**
- `/var/www/webexa/.env` → `AI_API_KEY`
- `/var/www/webexa-ai/agents-ia/.env` → `API_KEY`

**Authentification:**
- Header HTTP: `X-API-Key: bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps`

---

## 🌐 URLs de Production

| Service | URL | Port |
|---------|-----|------|
| CRM/ERP | `https://webexa.fr` | 443 |
| API Agents | `https://webexa.online` | 443 |
| API Agents (Interne) | `http://127.0.0.1:8000` | 8000 |

---

## 📡 Impact sur les Flux

### Avant (Monolithe local)
```
webexa.fr (PHP) → http://localhost:8000 → Agents IA (Python)
```

### Après (Deux domaines)
```
User → webexa.fr (PHP) 
        ↓
       Nginx (webexa.fr) 
        ↓
       PHP-FPM
        ↓
      AIAgentsClient.php 
        ↓
       CURL POST → https://webexa.online
        ↓
       Nginx (webexa.online)
        ↓
       Gunicorn:8000 (FastAPI)
        ↓
      Agents IA (Python)
        ↓
       MySQL (shared)
```

---

## ✅ Vérifications Post-Déploiement

### 1. Vérifier les endpoints
```bash
# Depuis votre machine locale
curl -H "X-API-Key: bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps" \
  https://webexa.online/health
```

### 2. Tester depuis le CRM
```
https://webexa.fr/crm/api/test-ai-connection.php
```

### 3. Vérifier les logs
```bash
# Sur le VPS
tail -f /var/log/nginx/webexa.online.error.log
tail -f /var/log/webexa/api.error.log
```

---

## 🔄 Variables d'Environnement à Mettre à Jour

### Sur webexa.fr (PHP)
```bash
AI_API_BASE_URL=https://webexa.online
AI_API_KEY=bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps
```

### Sur webexa-ai (Python)
```bash
API_KEY=bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps
DB_HOST=localhost
DB_USER=webexa_user
DB_PASSWORD=secure_password
DB_NAME=webexa
```

---

## 🚀 Prochaines Étapes

1. ✅ **Transfert des fichiers** → `rsync` depuis votre machine
2. ✅ **Configuration .env** → Mettre à jour les variables
3. ✅ **Installation dépendances** → `composer install` et `pip install`
4. ✅ **Base de données** → Créer et importer le schéma
5. ✅ **Démarrage services** → PHP-FPM, Nginx, Gunicorn
6. ✅ **Tests** → Vérifier la connectivité
7. ✅ **DNS** → Pointer les domaines vers le VPS
8. ✅ **SSL** → Certificats Let's Encrypt

---

## 📞 Questions Fréquentes

**Q: Comment changer la clé API?**
A: Mettre à jour dans `/var/www/webexa/.env` et `/var/www/webexa-ai/.env`, puis redémarrer les services.

**Q: Puis-je tester localement?**
A: Oui, changer `AI_API_BASE_URL=http://localhost:8000` dans `.env` et relancer le serveur local.

**Q: Comment déboguer les appels API?**
A: Activez `OAUTH_DEBUG=true` dans `.env` et consultez les logs Nginx.

---

**Date:** 30 mai 2026  
**Version:** 1.0  
**Status:** ✅ Prêt pour déploiement
