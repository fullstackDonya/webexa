# ✅ Checklist de déploiement - Oracle Cloud + Hostinger

## 📋 AVANT LE DÉPLOIEMENT

### Sur votre machine locale (MacOS)

- [ ] **Code à jour**
  - [ ] Tous les fichiers de `ia/` sont prêts
  - [ ] Fichier `.env.production` configuré
  - [ ] Dépendances listées dans `requirements.txt`
  - [ ] Scripts de déploiement sont exécutables (`chmod +x`)

- [ ] **Informations récupérées**
  - [ ] IP publique Oracle Cloud: `__________________`
  - [ ] Clé SSH Oracle Cloud: `__________________`
  - [ ] User Oracle (opc/ubuntu): `__________________`

---

### Sur Hostinger (MySQL)

- [ ] **Base de données accessible**
  - [ ] Hostname MySQL Hostinger: `__________________`
  - [ ] Database name: `__________________`
  - [ ] User MySQL: `__________________`
  - [ ] Password MySQL: `__________________`
  - [ ] Port (généralement 3306): `__________________`

- [ ] **Accès distant MySQL configuré**
  - [ ] Se connecter à cPanel Hostinger
  - [ ] Aller dans **Bases de données MySQL** → **Accès distant MySQL**
  - [ ] Ajouter l'IP publique Oracle Cloud
  - [ ] Status: ✅ IP autorisée

- [ ] **Tables CRM présentes**
  - [ ] Table `customers` existe
  - [ ] Table `emails` existe
  - [ ] Table `leads` existe
  - [ ] Table `lead_analysis` existe (sera créée auto si absente)

---

### API Keys & Sécurité

- [ ] **Clé API générée**
  - [ ] Clé secrète générée (32+ caractères aléatoires)
  - [ ] Clé stockée dans un endroit sûr
  - [ ] **Clé utilisée:** `bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps`

- [ ] **OpenAI API Key**
  - [ ] Compte OpenAI créé
  - [ ] Clé API récupérée: `sk-...`
  - [ ] Crédits suffisants sur OpenAI

---

## 🚀 DÉPLOIEMENT ORACLE CLOUD

### Préparation de l'instance Oracle

- [ ] **Instance créée**
  - [ ] OS: Ubuntu 22.04 ou Oracle Linux
  - [ ] RAM minimale: 2GB (recommandé: 4GB+)
  - [ ] Stockage: 20GB minimum
  - [ ] IP publique assignée: `__________________`

- [ ] **Connexion SSH fonctionnelle**
  ```bash
  ssh opc@VOTRE_IP_ORACLE -i ~/.ssh/votre-cle.key
  ```
  - [ ] Connexion réussie

- [ ] **Security List configuré**
  - [ ] Port 22 (SSH) ouvert
  - [ ] Port 8000 (API) ouvert: ⚠️ **CRITIQUE**
  - [ ] Ingress Rule ajoutée:
    - Source: `0.0.0.0/0`
    - Port: `8000`
    - Protocol: `TCP`

---

### Transfert des fichiers

- [ ] **Fichiers compressés**
  ```bash
  cd /Applications/MAMP/htdocs/PP/webitech/WEB/crm
  tar -czf agents-ia.tar.gz ia/
  ```

- [ ] **Envoi sur Oracle**
  ```bash
  scp agents-ia.tar.gz opc@VOTRE_IP_ORACLE:~
  ```
  - [ ] Transfert réussi

- [ ] **Extraction sur Oracle**
  ```bash
  ssh opc@VOTRE_IP_ORACLE
  sudo mkdir -p /var/www/agents-ia
  sudo tar -xzf ~/agents-ia.tar.gz -C /var/www/agents-ia --strip-components=1
  sudo chown -R opc:opc /var/www/agents-ia
  ```

---

### Configuration .env sur Oracle

- [ ] **Fichier .env créé**
  ```bash
  cd /var/www/agents-ia
  cp .env.production .env
  nano .env
  ```

- [ ] **Variables modifiées**
  - [ ] `DB_HOST` = hostname MySQL Hostinger
  - [ ] `DB_USER` = votre user MySQL
  - [ ] `DB_PASSWORD` = votre password MySQL
  - [ ] `DB_NAME` = nom de la base
  - [ ] `DB_PORT` = 3306
  - [ ] `API_SECRET_KEY` = clé secrète (même que CRM)
  - [ ] `OPENAI_API_KEY` = votre clé OpenAI

---

### Test de connectivité MySQL

- [ ] **Test depuis Oracle**
  ```bash
  cd /var/www/agents-ia
  python3 test-mysql-hostinger.py
  ```
  - [ ] ✅ Tous les tests passent

- [ ] **Si échec:**
  - [ ] Vérifier IP Oracle autorisée dans cPanel
  - [ ] Vérifier credentials dans .env
  - [ ] Tester avec `mysql` CLI:
    ```bash
    mysql -h HOSTINGER_HOST -u USER -p DATABASE
    ```

---

### Déploiement automatique

- [ ] **Exécution du script**
  ```bash
  cd /var/www/agents-ia
  chmod +x deploy-oracle.sh
  ./deploy-oracle.sh
  ```

- [ ] **Vérifications post-déploiement**
  - [ ] Environnement virtuel créé
  - [ ] Dépendances installées
  - [ ] Service systemd configuré
  - [ ] Service démarré: `sudo systemctl status agents-ia`
  - [ ] Logs sans erreur: `sudo journalctl -u agents-ia -n 50`

---

### Tests fonctionnels sur Oracle

- [ ] **Health check local**
  ```bash
  curl http://localhost:8000/health
  ```
  - [ ] Réponse: `{"status":"ok"}`

- [ ] **Health check distant (depuis votre Mac)**
  ```bash
  curl http://VOTRE_IP_ORACLE:8000/health
  ```
  - [ ] Réponse: `{"status":"ok"}`

- [ ] **Test authentification**
  ```bash
  curl -H "X-API-Key: bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps" \
       http://VOTRE_IP_ORACLE:8000/api/inbox/pending-actions
  ```
  - [ ] Réponse 200 (pas 401)

---

## 🌐 CONFIGURATION CRM HOSTINGER

### Création du fichier .env

- [ ] **Connexion Hostinger**
  - [ ] SSH ou File Manager cPanel

- [ ] **Créer `.env` dans `crm/`**
  ```env
  AI_API_URL=http://VOTRE_IP_ORACLE:8000
  AI_API_KEY=bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps
  ```

- [ ] **Protéger .env**
  - [ ] Créer `.htaccess` dans `crm/`:
    ```apache
    <Files .env>
        Order allow,deny
        Deny from all
    </Files>
    ```
  - [ ] `chmod 600 .env`

---

### Modification de AIAgentsClient.php

- [ ] **Ajouter méthode loadEnv()**
  - [ ] Code ajouté selon CONFIGURATION_CRM_ORACLE.md
  - [ ] Variables chargées depuis .env

- [ ] **Vérification manuelle**
  ```php
  <?php
  // test-env.php
  require_once 'api/AIAgentsClient.php';
  $client = new AIAgentsClient();
  echo "Configuration OK";
  ?>
  ```

---

### Test de bout en bout

- [ ] **Upload test-oracle-api.php sur Hostinger**
  - [ ] Fichier dans `/public_html/crm/`

- [ ] **Exécution depuis navigateur**
  ```
  https://votre-domaine.com/crm/test-oracle-api.php
  ```
  - [ ] ✅ Configuration chargée
  - [ ] ✅ Connectivité réseau OK
  - [ ] ✅ Health check réussi
  - [ ] ✅ Authentification validée
  - [ ] ✅ Tous les tests passent

- [ ] **Dashboard CRM**
  ```
  https://votre-domaine.com/crm/ai-dashboard.php
  ```
  - [ ] Pas d'erreur 401
  - [ ] Données affichées correctement
  - [ ] Actions IA visibles

---

## 🔒 SÉCURITÉ & MONITORING

### Sécurité

- [ ] **Firewall configuré**
  - [ ] Oracle Cloud: Security List validé
  - [ ] Oracle Linux: firewalld configuré
  - [ ] Port 8000 ouvert uniquement

- [ ] **Clés secrètes**
  - [ ] API_SECRET_KEY != "dev-secret-key-change-me"
  - [ ] Clé de 32+ caractères aléatoires
  - [ ] Clé identique sur Oracle et Hostinger

- [ ] **Fichiers sensibles protégés**
  - [ ] `.env` non accessible via HTTP
  - [ ] `.env` exclu de Git (.gitignore)

---

### Monitoring

- [ ] **Logs configurés**
  - [ ] `/var/log/crm-ai/api.log` existe
  - [ ] `/var/log/crm-ai/api-error.log` existe
  - [ ] Rotation des logs configurée (optionnel)

- [ ] **Service systemd**
  - [ ] Service démarre au boot
  - [ ] Redémarrage automatique en cas d'erreur

- [ ] **Surveillance**
  - [ ] Vérifier logs quotidiennement (début)
  - [ ] Mettre en place alertes (optionnel)

---

## 📊 PERFORMANCE & OPTIMISATION

- [ ] **Base de données**
  - [ ] Index sur `emails.ai_status`
  - [ ] Index sur `customers.id`
  - [ ] Requêtes optimisées

- [ ] **API**
  - [ ] Workers configurés (2+)
  - [ ] Timeout approprié
  - [ ] Pool MySQL configuré

- [ ] **Réseau**
  - [ ] Latence Oracle ↔ Hostinger mesurée: `_____ ms`
  - [ ] Acceptable (< 200ms recommandé)

---

## 🎉 MISE EN PRODUCTION

### Validation finale

- [ ] **Tests utilisateurs**
  - [ ] Dashboard accessible
  - [ ] Analyses IA fonctionnent
  - [ ] Pas d'erreurs dans les logs

- [ ] **Documentation**
  - [ ] Équipe informée de la nouvelle architecture
  - [ ] Procédures de dépannage partagées
  - [ ] Contact support défini

- [ ] **Backup**
  - [ ] Backup de la base de données Hostinger
  - [ ] Snapshot de l'instance Oracle (optionnel)

---

### Go-Live

- [ ] **Basculement progressif**
  - [ ] Tester avec 1-2 utilisateurs test
  - [ ] Valider les résultats
  - [ ] Déployer pour tous les utilisateurs

- [ ] **Surveillance post-déploiement**
  - [ ] Surveiller logs pendant 24h
  - [ ] Vérifier performance
  - [ ] Répondre aux demandes utilisateurs

---

## 🆘 ROLLBACK (en cas de problème)

Si problème critique:

1. **Désactiver l'API Oracle**
   ```bash
   sudo systemctl stop agents-ia
   ```

2. **Revenir en local (ou ancienne config)**
   
   Sur Hostinger, éditer `.env`:
   ```env
   AI_API_URL=http://localhost:8000  # ou ancienne URL
   ```

3. **Analyser les logs**
   ```bash
   sudo journalctl -u agents-ia -n 200
   tail -f /var/log/crm-ai/api-error.log
   ```

4. **Corriger et redéployer**

---

## 📞 SUPPORT

### Commandes utiles

**Oracle Cloud:**
```bash
# Statut service
sudo systemctl status agents-ia

# Logs en temps réel
sudo journalctl -u agents-ia -f

# Redémarrer
sudo systemctl restart agents-ia

# Vérifier port
sudo netstat -tlnp | grep 8000
```

**Hostinger:**
```bash
# Tester API
curl http://VOTRE_IP_ORACLE:8000/health

# Vérifier .env
cat /home/USER/public_html/crm/.env
```

---

## ✅ CHECKLIST COMPLÉTÉE

Date de déploiement: `__________________`

Déployé par: `__________________`

Statut final:
- [ ] ✅ Déploiement réussi
- [ ] ⚠️ Déploiement partiel (préciser):
- [ ] ❌ Déploiement échoué (raison):

Notes:
```
__________________________________________________________________
__________________________________________________________________
__________________________________________________________________
```

---

**Signature:** `__________________`
