# Configuration CRM Hostinger → Oracle Cloud API

## 🎯 But

Configurer le CRM hébergé sur Hostinger pour qu'il communique avec l'API des agents IA hébergée sur Oracle Cloud.

---

## 🔧 Configuration

### Option 1: Modification directe de AIAgentsClient.php (Simple)

**Fichier:** `crm/api/AIAgentsClient.php`

```php
<?php

class AIAgentsClient {
    // CHANGEZ CES VALEURS ⬇️
    private $apiUrl = 'http://VOTRE_IP_ORACLE:8000';  // IP publique Oracle Cloud
    private $apiKey = 'bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps';  // Même clé que dans Oracle .env
    
    // Le reste du code ne change pas
    // ...
}
```

**Remplacez:**
- `VOTRE_IP_ORACLE` par l'IP publique de votre instance Oracle Cloud
- Exemple: `http://123.45.67.89:8000`

---

### Option 2: Configuration via .env (Recommandé)

**1. Créer un fichier `.env` dans le dossier `crm/`:**

```bash
# Sur Hostinger, créez le fichier
cd /home/VOTRE_USER/public_html/crm
nano .env
```

**Contenu du fichier .env:**
```env
# Oracle Cloud - Agents IA API
AI_API_URL=http://VOTRE_IP_ORACLE:8000
AI_API_KEY=bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps
```

**2. Modifier AIAgentsClient.php pour lire .env:**

```php
<?php

class AIAgentsClient {
    private $apiUrl;
    private $apiKey;
    
    public function __construct() {
        // Charger .env si présent
        $this->loadEnv();
        
        // Utiliser variables d'environnement ou valeurs par défaut
        $this->apiUrl = getenv('AI_API_URL') ?: 'http://localhost:8000';
        $this->apiKey = getenv('AI_API_KEY') ?: 'dev-secret-key-change-me';
    }
    
    private function loadEnv() {
        $envFile = __DIR__ . '/../.env';
        
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                // Ignorer commentaires
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }
                
                // Parser KEY=VALUE
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    putenv(trim($key) . '=' . trim($value));
                }
            }
        }
    }
    
    // Le reste du code reste identique
    public function request($endpoint, $data = null) {
        // ...
    }
}
```

**Avantages:**
- ✅ Sécurité: .env peut être exclu du contrôle de version (.gitignore)
- ✅ Flexibilité: Facile de changer les URLs dev/prod
- ✅ Maintenance: Pas besoin de modifier le code PHP

---

## 🔒 Sécurité .env sur Hostinger

**Important: Protégez le fichier .env !**

**1. Créer `.htaccess` dans le dossier `crm/`:**

```apache
# Interdire l'accès au fichier .env
<Files .env>
    Order allow,deny
    Deny from all
</Files>
```

**2. Vérifier les permissions:**
```bash
chmod 600 .env
```

**3. Exclure du contrôle de version (.gitignore):**
```
.env
.env.local
.env.production
```

---

## 📝 Fichiers à modifier

### Résumé des changements

| Fichier | Action | Priorité |
|---------|--------|----------|
| `crm/.env` | Créer avec AI_API_URL et AI_API_KEY | 🔴 CRITIQUE |
| `crm/.htaccess` | Protéger .env | 🟡 IMPORTANT |
| `crm/api/AIAgentsClient.php` | Ajouter méthode loadEnv() | 🟡 IMPORTANT |

---

## ✅ Test de configuration

**1. Créer un fichier de test: `crm/test-oracle-api.php`**

```php
<?php
require_once __DIR__ . '/api/AIAgentsClient.php';

header('Content-Type: application/json');

try {
    $client = new AIAgentsClient();
    
    echo "🔍 Configuration chargée:\n";
    echo "API URL: " . (getenv('AI_API_URL') ?: 'localhost:8000') . "\n";
    echo "API Key: " . substr(getenv('AI_API_KEY') ?: 'default', 0, 10) . "...\n\n";
    
    echo "📡 Test de connexion à Oracle Cloud...\n";
    
    // Test health check
    $health = $client->request('/health');
    
    if ($health && isset($health['status'])) {
        echo "✅ SUCCÈS: API Oracle accessible\n";
        echo json_encode($health, JSON_PRETTY_PRINT) . "\n\n";
        
        // Test endpoint authentifié
        echo "🔐 Test d'authentification...\n";
        $pending = $client->request('/api/inbox/pending-actions', ['customer_id' => 1]);
        
        if ($pending && isset($pending['success'])) {
            echo "✅ SUCCÈS: Authentification valide\n";
            echo json_encode($pending, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "❌ ERREUR: Authentification échouée\n";
            print_r($pending);
        }
        
    } else {
        echo "❌ ERREUR: API Oracle non accessible\n";
        print_r($health);
    }
    
} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
}
```

**2. Tester depuis le navigateur:**
```
https://votre-domaine.com/crm/test-oracle-api.php
```

**3. Tester depuis le terminal:**
```bash
ssh VOTRE_USER@VOTRE_HOSTINGER
cd public_html/crm
php test-oracle-api.php
```

**Résultat attendu:**
```
✅ SUCCÈS: API Oracle accessible
{
    "status": "ok"
}

✅ SUCCÈS: Authentification valide
{
    "success": true,
    "data": {
        "customer_id": 1,
        "count": 0,
        "actions": []
    }
}
```

---

## 🌐 HTTPS (Optionnel mais recommandé)

### Pourquoi HTTPS ?

Actuellement: 
```
CRM HTTPS ━━━━HTTP━━━━> Oracle API HTTP
```

C'est OK, mais **pas sécurisé** si les données transitent sur Internet.

### Solution: Nginx Reverse Proxy sur Oracle

**1. Installer Nginx + Certbot:**
```bash
# Sur Oracle Cloud
sudo apt update
sudo apt install nginx certbot python3-certbot-nginx
```

**2. Configuration Nginx:**
```nginx
# /etc/nginx/sites-available/agents-ia
server {
    listen 80;
    server_name agents-ia.votre-domaine.com;
    
    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

**3. Générer certificat SSL:**
```bash
sudo ln -s /etc/nginx/sites-available/agents-ia /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx

sudo certbot --nginx -d agents-ia.votre-domaine.com
```

**4. Modifier .env CRM Hostinger:**
```env
AI_API_URL=https://agents-ia.votre-domaine.com
AI_API_KEY=bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps
```

Maintenant:
```
CRM HTTPS ━━━━HTTPS━━━━> Oracle API HTTPS
```

**✅ Communication 100% chiffrée !**

---

## 🚨 Dépannage

### Erreur: "Connection timeout"

**Causes possibles:**
1. Firewall Oracle Cloud bloque le port 8000
2. Service agents-ia arrêté sur Oracle

**Solutions:**
```bash
# Sur Oracle Cloud
sudo systemctl status agents-ia
sudo systemctl start agents-ia

# Vérifier firewall
sudo firewall-cmd --list-ports
sudo firewall-cmd --add-port=8000/tcp --permanent
sudo firewall-cmd --reload
```

### Erreur: "API Error (401): Invalid API key"

**Cause:** Clés API différentes

**Solution:**
```bash
# Sur Oracle Cloud
grep API_SECRET_KEY /var/www/agents-ia/.env

# Sur Hostinger
grep AI_API_KEY /home/VOTRE_USER/public_html/crm/.env
```

Les deux clés doivent être **IDENTIQUES**.

### Erreur: "Could not resolve host"

**Cause:** URL incorrecte dans .env

**Solution:**
```env
# ❌ MAUVAIS
AI_API_URL=VOTRE_IP_ORACLE:8000

# ✅ BON
AI_API_URL=http://123.45.67.89:8000
```

N'oubliez pas `http://` !

---

## 📊 Checklist finale

Avant de mettre en production:

- [ ] ✅ Oracle Cloud: Service `agents-ia` actif
- [ ] ✅ Oracle Cloud: Port 8000 ouvert (Security List + firewall)
- [ ] ✅ Oracle Cloud: Connexion MySQL Hostinger validée
- [ ] ✅ Hostinger: Fichier `.env` créé avec bonnes valeurs
- [ ] ✅ Hostinger: `.htaccess` protège `.env`
- [ ] ✅ Hostinger: AIAgentsClient.php chargé avec loadEnv()
- [ ] ✅ Test: `test-oracle-api.php` retourne succès
- [ ] ✅ Test: Dashboard CRM affiche données IA

---

## 📞 URL de test

Une fois configuré, testez ces endpoints:

```bash
# Health check (public)
curl http://VOTRE_IP_ORACLE:8000/health

# Pending actions (avec auth)
curl -H "X-API-Key: bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps" \
     http://VOTRE_IP_ORACLE:8000/api/inbox/pending-actions
```

Depuis le CRM:
- Dashboard: `https://votre-domaine.com/crm/ai-dashboard.php`
- Test API: `https://votre-domaine.com/crm/test-oracle-api.php`

---

**Configuration terminée ! 🎉**
