# 🎉 WEBEXA PRÊT POUR LE DÉPLOIEMENT VPS

## 📌 RÉSUMÉ DE CE QUI A ÉTÉ PRÉPARÉ

J'ai préparé **une suite complète** pour déployer Webexa sur votre VPS avec:

### ✨ 2 DOMAINES SÉPARÉS
- **webexa.fr** → Application PHP (CRM/ERP)
- **webexa.online** → API Python (Agents IA)

### 📦 10 FICHIERS CRÉÉS
1. **README_DEPLOYMENT.txt** ← Lisez ça en premier!
2. DEPLOYMENT_SUMMARY.md - Résumé exécutif
3. DEPLOYMENT_README.md - Guide complet
4. DEPLOYMENT_GUIDE.md - Instructions détaillées
5. DEPLOYMENT_INDEX.md - Index de navigation
6. CHEAT_SHEET.md - Référence de commandes
7. quick-deploy.sh ⭐ **SCRIPT PRINCIPAL**
8. setup-vps.sh - Configuration VPS
9. deploy.sh - Déploiement alternatif
10. verify-deployment.sh - Vérification post-déploiement
11. Fichiers de configuration Nginx & Systemd

### ⚙️ STRUCTURE SUR VOTRE VPS

```
your-vps:/var/www/
├── html/              (Site existant)
├── siteo/             (Site existant)
├── webexa/            ← PHP App (webexa.fr)
└── webexa-ai/         ← Python API (webexa.online)
```

---

## 🚀 LES 3 ÉTAPES SIMPLES

### ÉTAPE 1: Rendre les scripts exécutables (30 secondes)
```bash
cd /Applications/MAMP/htdocs/webexa
chmod +x quick-deploy.sh setup-vps.sh deploy.sh verify-deployment.sh
```

### ÉTAPE 2: Lire le résumé (5 minutes)
```bash
cat DEPLOYMENT_SUMMARY.md
```

### ÉTAPE 3: Lancer le déploiement (10-15 minutes)
```bash
./quick-deploy.sh
```

Le script demandera:
- Adresse IP du VPS
- Nom d'utilisateur SSH (par défaut: root)
- Noms de domaines (par défaut: webexa.fr, webexa.online)

**C'est tout!** ✨

---

## 📝 APRÈS LE DÉPLOIEMENT

Une fois que `quick-deploy.sh` a terminé:

```bash
# 1. Connectez-vous au VPS
ssh root@votre-ip-vps

# 2. Créez le fichier .env pour PHP
nano /var/www/webexa/.env

# Ajoutez:
DB_HOST=localhost
DB_USERNAME=votre_user
DB_PASSWORD=votre_password
DB_DATABASE=webexa_db
OPENAI_API_KEY=sk-...
ANTHROPIC_API_KEY=sk-ant-...

# 3. Créez le fichier .env pour Python
nano /var/www/webexa-ai/agents-ia/.env

# Ajoutez les mêmes paramètres DB + API keys

# 4. Redémarrez les services
systemctl restart webexa-api nginx

# 5. Testez
curl http://webexa.fr
curl http://webexa.online/docs
```

---

## 🎯 CE QUE VOUS OBTIENDREZ

### Sur webexa.fr (PHP)
✅ Dashboard CRM/ERP  
✅ Gestion des clients/contacts  
✅ Campagnes email  
✅ Automations  
✅ Analytics  
✅ Tout avec HTTPS

### Sur webexa.online (Python IA)
✅ API FastAPI avec documentation auto  
✅ Agents IA pour emails  
✅ Scoring des leads  
✅ Automations intelligentes  
✅ Accessible via HTTPS

### Infrastructure VPS
✅ Nginx comme serveur web  
✅ PHP-FPM pour PHP  
✅ Gunicorn pour Python  
✅ Auto-redémarrage des services  
✅ Certificats SSL/TLS  
✅ Configuration de sécurité

---

## 📚 GUIDE DES FICHIERS

| Fichier | Contenu | Quand l'utiliser |
|---------|---------|-----------------|
| README_DEPLOYMENT.txt | Index général | Première visite |
| DEPLOYMENT_SUMMARY.md | Vue d'ensemble | Avant déploiement |
| DEPLOYMENT_README.md | Guide complet | Consultation rapide |
| DEPLOYMENT_GUIDE.md | Instructions détaillées | Déploiement manuel |
| CHEAT_SHEET.md | Commandes rapides | Administration quotidienne |
| quick-deploy.sh | **Déploiement auto** | **À exécuter!** |

---

## ⏱️ COMBIEN DE TEMPS?

- Lecture du résumé: **5 min**
- Exécution du déploiement: **10-15 min**
- Configuration .env sur VPS: **5 min**
- Configuration DNS: **5 min** (+ 24h d'attente)
- Installation HTTPS: **5 min**

**Total: ~45 minutes** (+ attente DNS 24h)

---

## ✅ AVANT DE COMMENCER

Préparez:
- [ ] IP de votre VPS
- [ ] Identifiant SSH (root ou sudo)
- [ ] Domaines enregistrés (webexa.fr, webexa.online)
- [ ] Base de données (MySQL/Oracle)
- [ ] Clés API (OpenAI, Anthropic)
- [ ] Identifiants email

---

## 🔒 APRÈS LE DÉPLOIEMENT

Assurez-vous de:
1. Configurer les fichiers .env
2. Vérifier que les services tournent
3. Configurer DNS chez votre registraire
4. Installer certificats SSL (certbot)
5. Vérifier les logs pour les erreurs

---

## 💻 COMMANDES CLÉS

```bash
# Vérifier les services
systemctl status nginx php-fpm webexa-api

# Voir les logs
tail -f /var/log/nginx/webexa.fr.error.log
tail -f /var/log/webexa/api.error.log

# Redémarrer services
systemctl restart nginx php-fpm webexa-api

# Test de connectivité
curl -I http://webexa.fr
curl http://webexa.online/docs
```

---

## 🆘 EN CAS DE PROBLÈME

1. **Lisez CHEAT_SHEET.md** pour les commandes
2. **Exécutez verify-deployment.sh** pour le diagnostic
3. **Vérifiez les logs** `/var/log/nginx/*.error.log`
4. **Relisez DEPLOYMENT_README.md** section troubleshooting

---

## 🎯 PROCHAIN ÉTAPE

```bash
# C'est simple! Une seule commande:
chmod +x quick-deploy.sh
./quick-deploy.sh
```

Puis suivez les instructions à l'écran.

---

## 📞 RESSOURCES

- **Questions rapidement?** → CHEAT_SHEET.md
- **Besoin de détails?** → DEPLOYMENT_README.md
- **Installation manuelle?** → DEPLOYMENT_GUIDE.md
- **Vérifier l'installation?** → verify-deployment.sh

---

## 🚀 VOUS ÊTES PRÊT!

Tout est préparé pour un déploiement fluide et professionnel.

**Lancez simplement:** `./quick-deploy.sh`

Bonne chance! 🍀

---

**Créé:** 30 mai 2026  
**Version:** 1.0.0  
**Status:** ✅ Prêt pour le déploiement

Pour plus d'information, lisez **DEPLOYMENT_SUMMARY.md**
