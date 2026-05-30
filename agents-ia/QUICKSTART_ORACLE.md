# 🚀 Déploiement Agents IA - Oracle Cloud + Hostinger MySQL

## Architecture

```
┌─────────────────────────┐         ┌──────────────────────────┐
│   HOSTINGER             │         │   ORACLE CLOUD           │
│                         │         │                          │
│  ┌──────────────────┐   │ réseau  │   ┌────────────────┐     │
│  │  CRM PHP App     │◄──┼─────────┼───┤  Agents IA     │     │
│  │  (Frontal)       │   │  HTTP   │   │  FastAPI       │     │
│  └──────────────────┘   │         │   │  Port 8000     │     │
│                         │         │   └────────────────┘     │
│  ┌──────────────────┐   │         │          │               │
│  │  MySQL Database  │◄──┼─────────┼──────────┘               │
│  │  Port 3306       │   │  MySQL  │   Connexion distante     │
│  └──────────────────┘   │         │                          │
└─────────────────────────┘         └──────────────────────────┘
```

## 📋 Prérequis

### Sur Hostinger (CRM + Base de données)
- ✅ CRM PHP déjà déployé
- ✅ Base MySQL créée
- [ ] **Accès distant MySQL activé** (CRITIQUE)
- [ ] **IP Oracle autorisée dans cPanel**

### Sur Oracle Cloud (Agents IA uniquement)
- [ ] Instance Oracle Cloud créée
- [ ] Accès SSH configuré
- [ ] Python 3.10+ installé
- [ ] Port 8000 ouvert dans le firewall

---

## 🎯 Guide de déploiement en 3 étapes

### ÉTAPE 1️⃣ : Configuration MySQL Hostinger (OBLIGATOIRE)

**Sur Hostinger cPanel:**

1. Allez dans **Bases de données MySQL** → **Accès distant MySQL**
2. Ajoutez l'IP publique de votre instance Oracle Cloud
3. Testez la connexion:
   ```bash
   # Depuis Oracle Cloud
   mysql -h VOTRE_SERVEUR_HOSTINGER.mysql.eu.hostinger.com \
         -u VOTRE_USER \
         -p \
         VOTRE_DATABASE
   ```

**Récupérez ces informations:**
- ✍️ DB_HOST: `_______________________________`
- ✍️ DB_USER: `_______________________________`
- ✍️ DB_PASSWORD: `_______________________________`
- ✍️ DB_NAME: `_______________________________`

---

### ÉTAPE 2️⃣ : Déploiement sur Oracle Cloud

**A. Connexion SSH:**
```bash
ssh opc@VOTRE_IP_ORACLE -i ~/.ssh/votre-cle-privee.key
```

**B. Transfert des fichiers:**
```bash
# Depuis votre machine locale
cd /Applications/MAMP/htdocs/PP/webitech/WEB/crm

# Compresser le dossier ia
tar -czf agents-ia.tar.gz ia/

# Envoyer sur Oracle
scp agents-ia.tar.gz opc@VOTRE_IP_ORACLE:~

# Sur Oracle Cloud
ssh opc@VOTRE_IP_ORACLE
sudo mkdir -p /var/www/agents-ia
cd /var/www/agents-ia
sudo tar -xzf ~/agents-ia.tar.gz --strip-components=1
sudo chown -R opc:opc /var/www/agents-ia
```

**C. Configuration .env:**
```bash
cd /var/www/agents-ia
cp .env.production .env
nano .env
```

Modifiez ces lignes avec VOS VRAIES VALEURS:
```env
# Base de données MySQL Hostinger (DISTANT)
DB_HOST=votre-serveur-hostinger.mysql.eu.hostinger.com
DB_USER=votre_user_mysql
DB_PASSWORD=votre_password_mysql
DB_NAME=votre_database
DB_PORT=3306

# Clé API (IDENTIQUE au CRM)
API_SECRET_KEY=bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps

# OpenAI
OPENAI_API_KEY=sk-...votre-clé...
```

**D. Test de connexion MySQL:**
```bash
cd /var/www/agents-ia
./test-mysql-hostinger.py
```

Si tous les tests passent ✅, continuez:

**E. Déploiement automatique:**
```bash
./deploy-oracle.sh
```

Le script va:
- ✅ Installer Python et dépendances
- ✅ Créer l'environnement virtuel
- ✅ Configurer le service systemd
- ✅ Ouvrir le firewall (port 8000)
- ✅ Démarrer l'API

---

### ÉTAPE 3️⃣ : Configuration Oracle Cloud Console

**Ouvrir le port 8000:**

1. Aller dans **Oracle Cloud Console**
2. **Networking** → **Virtual Cloud Networks**
3. Cliquer sur votre VCN
4. **Security Lists** → Default Security List
5. **Add Ingress Rules**:
   - Source CIDR: `0.0.0.0/0`
   - Destination Port: `8000`
   - Protocol: TCP
   - Description: `Agents IA CRM`
6. **Save**

---

## ✅ Vérification

### 1. Test local sur Oracle
```bash
curl http://localhost:8000/health
# Réponse attendue: {"status":"ok"}
```

### 2. Test distant depuis votre machine
```bash
curl http://VOTRE_IP_ORACLE:8000/health
# Réponse attendue: {"status":"ok"}
```

### 3. Test avec authentification
```bash
curl -H "X-API-Key: bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps" \
     http://VOTRE_IP_ORACLE:8000/api/inbox/pending-actions
```

### 4. Configuration CRM Hostinger

**Depuis Hostinger, éditez votre configuration CRM:**

Fichier: `crm/api/AIAgentsClient.php`
```php
private $apiUrl = 'http://VOTRE_IP_ORACLE:8000';
private $apiKey = 'bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps';
```

OU créez `.env` dans le dossier `crm/`:
```env
AI_API_URL=http://VOTRE_IP_ORACLE:8000
AI_API_KEY=bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps
```

### 5. Test depuis le dashboard CRM
Allez sur: `https://votre-domaine-hostinger.com/ai-dashboard.php`

---

## 🔧 Commandes utiles

### Gestion du service
```bash
# Statut
sudo systemctl status agents-ia

# Redémarrer
sudo systemctl restart agents-ia

# Arrêter
sudo systemctl stop agents-ia

# Logs en temps réel
sudo journalctl -u agents-ia -f
```

### Logs applicatifs
```bash
# Logs API
tail -f /var/log/crm-ai/api.log

# Logs erreurs
tail -f /var/log/crm-ai/api-error.log
```

### Mise à jour du code
```bash
cd /var/www/agents-ia
git pull  # Si vous utilisez Git
# OU
# scp depuis votre machine locale

sudo systemctl restart agents-ia
```

---

## 🐛 Dépannage

### Erreur: "Can't connect to MySQL server"
- ❌ L'IP Oracle n'est pas autorisée dans cPanel Hostinger
- 🔧 Solution: Ajoutez l'IP dans **Accès distant MySQL**

### Erreur: "Access denied for user"
- ❌ Mauvais credentials dans .env
- 🔧 Solution: Vérifiez DB_USER et DB_PASSWORD dans cPanel

### API retourne 401 Unauthorized
- ❌ Mauvaise API_SECRET_KEY
- 🔧 Solution: Vérifiez que la clé est IDENTIQUE dans:
  - Oracle: `/var/www/agents-ia/.env`
  - Hostinger: `crm/api/AIAgentsClient.php`

### Port 8000 non accessible depuis l'extérieur
- ❌ Security List Oracle Cloud mal configurée
- 🔧 Solution: Ajoutez Ingress Rule pour port 8000 (voir Étape 3)

### Le service ne démarre pas
```bash
# Vérifier les logs
sudo journalctl -u agents-ia -n 100

# Tester manuellement
cd /var/www/agents-ia
source venv/bin/activate
python -m uvicorn main:app --host 0.0.0.0 --port 8000
```

---

## 📊 Architecture de production

**Flux de données:**

1. **Utilisateur** → Dashboard CRM (Hostinger)
2. **CRM PHP** → API Agents IA (Oracle Cloud) via HTTP
3. **Agents IA** → MySQL (Hostinger) via connexion distante
4. **Agents IA** → OpenAI API
5. **Résultats** → CRM → Utilisateur

**Avantages:**
- ✅ Séparation des responsabilités
- ✅ Scalabilité (Oracle peut gérer de grosses charges IA)
- ✅ Base de données centralisée (Hostinger)
- ✅ Pas de duplication des données

**Inconvénients:**
- ⚠️  Latence réseau (Oracle ↔ Hostinger)
- ⚠️  Dépendance à la connexion MySQL distante
- ⚠️  Nécessite une bonne configuration réseau

---

## 🔒 Sécurité

### Recommandations:

1. **API Key forte:**
   - Changez `API_SECRET_KEY` par une valeur aléatoire complexe
   - Utilisez: `openssl rand -base64 32`

2. **HTTPS avec Nginx** (recommandé):
   ```bash
   sudo apt install nginx certbot python3-certbot-nginx
   # Configuration Nginx reverse proxy
   ```

3. **Firewall MySQL:**
   - Sur Hostinger, limitez l'accès à l'IP Oracle uniquement

4. **Monitoring:**
   - Configurez des alertes sur Oracle Cloud
   - Surveillez les logs d'erreurs

---

## 📖 Documentation complète

Pour plus de détails, voir:
- [DEPLOIEMENT_ORACLE_HOSTINGER.md](DEPLOIEMENT_ORACLE_HOSTINGER.md) - Guide complet
- [DEPLOIEMENT_PRODUCTION.md](DEPLOIEMENT_PRODUCTION.md) - Guide général

---

## 🆘 Support

Si vous rencontrez des problèmes:

1. Vérifiez les logs: `sudo journalctl -u agents-ia -n 100`
2. Testez la connexion MySQL: `./test-mysql-hostinger.py`
3. Vérifiez la config réseau: `curl -v http://localhost:8000/health`
4. Consultez les documentations ci-dessus

---

**Dernière mise à jour:** $(date)
**Version:** 1.0.0
