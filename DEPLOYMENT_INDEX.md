# 📚 INDEX DES FICHIERS DE DÉPLOIEMENT

## 🎯 COMMENCEZ PAR ICI

### 1. **DEPLOYMENT_SUMMARY.md** ⭐ **À LIRE EN PREMIER**
   - Vue d'ensemble complète du projet
   - Architecture cible sur le VPS
   - Étapes rapides
   - Checklist
   - **Temps de lecture:** 5 minutes

### 2. **DEPLOYMENT_README.md** 📖 **GUIDE PRINCIPAL**
   - Guide complet avec tous les détails
   - Configuration des variables d'environnement
   - Monitoring et logs
   - Troubleshooting
   - **Temps de lecture:** 10-15 minutes

---

## 🚀 SCRIPTS DE DÉPLOIEMENT

### **quick-deploy.sh** ✨ **RECOMMANDÉ**
```bash
chmod +x quick-deploy.sh
./quick-deploy.sh
```
- Déploiement entièrement automatisé
- Interactif (demande l'IP, domaine, etc.)
- Gère toutes les étapes du début à la fin
- **Durée:** ~10-15 minutes

### **setup-vps.sh**
- Configure l'environnement VPS
- Installe les packages requis
- Configure Nginx et PHP-FPM
- Appelé automatiquement par `quick-deploy.sh`
- **Durée:** ~5 minutes

### **deploy.sh**
- Alternative à `quick-deploy.sh`
- Plus simple, moins interactif
- Utilise `rsync` pour copier les fichiers
- Utile pour les redeploiements

---

## ⚙️ FICHIERS DE CONFIGURATION

### **nginx-webexa.fr.conf**
- Configuration Nginx pour le domaine PHP
- À copier dans `/etc/nginx/sites-available/webexa.fr`
- Gère le serveur web PHP

### **nginx-webexa.online.conf**
- Configuration Nginx pour le domaine Python
- À copier dans `/etc/nginx/sites-available/webexa.online`
- Reverse proxy pour l'API FastAPI

### **webexa-api.service**
- Service systemd pour l'API Python
- À copier dans `/etc/systemd/system/webexa-api.service`
- Gère le démarrage/arrêt automatique

---

## 🧪 VÉRIFICATION & MONITORING

### **verify-deployment.sh**
```bash
chmod +x verify-deployment.sh
./verify-deployment.sh
```
- Vérifie l'installation complète
- Teste les services, permissions, logs
- Donne un rapport détaillé
- **À exécuter après le déploiement**

### **CHEAT_SHEET.md**
- Référence rapide de toutes les commandes
- Utile pour l'administration courante
- Section troubleshooting
- À garder à proximité

---

## 📝 GUIDES DÉTAILLÉS

### **DEPLOYMENT_GUIDE.md**
- Instructions pas à pas manuelles
- Détails techniques complets
- Explications pour chaque étape
- Référence pour les déploiements manuels

### **DEPLOYMENT_SUMMARY.md**
- Résumé exécutif du projet
- Architecture et structure
- Checklist de vérification
- Résolution de problèmes courants

---

## 🗂️ STRUCTURE DE FICHIERS CRÉÉS

```
Votre répertoire Webexa local:
├── 📋 DEPLOYMENT_SUMMARY.md    ← COMMENCEZ ICI
├── 📖 DEPLOYMENT_README.md     ← Guide principal
├── 📚 DEPLOYMENT_GUIDE.md      ← Instructions détaillées
├── 📝 CHEAT_SHEET.md           ← Commandes rapides
├── 📋 DEPLOYMENT_INDEX.md      ← Ce fichier
├── 🚀 quick-deploy.sh          ← SCRIPT PRINCIPAL
├── ⚙️  setup-vps.sh            ← Configuration VPS
├── 📦 deploy.sh                ← Déploiement alternatif
├── 🧪 verify-deployment.sh     ← Vérification post-déploiement
├── ⚙️  nginx-webexa.fr.conf    ← Config Nginx (PHP)
├── ⚙️  nginx-webexa.online.conf ← Config Nginx (Python)
└── ⚙️  webexa-api.service      ← Service systemd

Sur le VPS (/var/www/):
├── webexa/                     ← Application PHP
│   ├── crm/
│   ├── erp/
│   ├── forms/
│   ├── assets/
│   ├── .env                    ← À créer
│   └── composer.json
└── webexa-ai/                  ← Application Python
    ├── agents-ia/
    ├── venv/
    └── .env                    ← À créer
```

---

## 📋 WORKFLOW DE DÉPLOIEMENT

```
┌─────────────────────────────────────────┐
│  ÉTAPE 1: PRÉPARATION (5 min)           │
│  ├─ Lire DEPLOYMENT_SUMMARY.md          │
│  ├─ Vérifier IP VPS et accès SSH        │
│  └─ Avoir domaines et clés API prêtes   │
└─────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────┐
│  ÉTAPE 2: DÉPLOIEMENT (10-15 min)       │
│  ├─ Rendre scripts exécutables:         │
│  │  chmod +x *.sh                       │
│  │                                       │
│  └─ Lancer quick-deploy.sh:             │
│     ./quick-deploy.sh                   │
└─────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────┐
│  ÉTAPE 3: CONFIGURATION (5 min)         │
│  ├─ SSH au VPS                          │
│  ├─ Créer .env pour PHP                 │
│  ├─ Créer .env pour Python              │
│  └─ Démarrer services                   │
└─────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────┐
│  ÉTAPE 4: VÉRIFICATION (5 min)          │
│  ├─ Exécuter verify-deployment.sh       │
│  ├─ Tester webexa.fr                    │
│  └─ Tester webexa.online/docs           │
└─────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────┐
│  ÉTAPE 5: DNS & HTTPS (24h+)            │
│  ├─ Configurer DNS chez registraire     │
│  ├─ Attendre propagation DNS            │
│  └─ Installer certificats Let's Encrypt │
└─────────────────────────────────────────┘
```

---

## 🎯 UTILISATION RAPIDE

### Pour les utilisateurs impatients ⚡
```bash
# 1. Une seule commande:
chmod +x quick-deploy.sh && ./quick-deploy.sh

# 2. Puis sur VPS:
ssh root@votre-ip
nano /var/www/webexa/.env
nano /var/www/webexa-ai/agents-ia/.env
systemctl restart webexa-api

# 3. Test:
curl http://webexa.fr
curl http://webexa.online/docs
```

### Pour les utilisateurs prudents 🛡️
```bash
# 1. Lire d'abord:
cat DEPLOYMENT_README.md

# 2. Puis déployer manuellement selon DEPLOYMENT_GUIDE.md
# ou utiliser les scripts d'une étape à la fois
```

---

## 🆘 HELP & SUPPORT

### Avant de commencer:
1. ✅ Lire `DEPLOYMENT_SUMMARY.md` (5 min)
2. ✅ Vérifier tous les prérequis
3. ✅ Avoir domaines et API keys

### Si ça bloque:
1. ✅ Consulter `CHEAT_SHEET.md` pour les commandes
2. ✅ Consulter `DEPLOYMENT_GUIDE.md` pour les détails
3. ✅ Exécuter `verify-deployment.sh` pour diagnostic

### En cas d'erreur:
1. ✅ Lire les logs: `tail -f /var/log/nginx/*.error.log`
2. ✅ Consulter section "Troubleshooting" dans README
3. ✅ Vérifier permissions: `chmod -R 755 /var/www/webexa*`

---

## 📞 COMMANDES ESSENTIELLES

```bash
# Rendez les scripts exécutables:
chmod +x quick-deploy.sh setup-vps.sh deploy.sh verify-deployment.sh

# Lancez le déploiement:
./quick-deploy.sh

# Après déploiement, sur VPS:
ssh root@votre-ip
systemctl status nginx php-fpm webexa-api
tail -f /var/log/nginx/webexa.fr.error.log

# Redémarrer services si besoin:
systemctl restart nginx php-fpm webexa-api
```

---

## 🎓 APPRENTISSAGE

- **Débutant?** → Lire `DEPLOYMENT_SUMMARY.md` + exécuter `quick-deploy.sh`
- **Intermédiaire?** → Lire `DEPLOYMENT_README.md` + `CHEAT_SHEET.md`
- **Avancé?** → Voir `DEPLOYMENT_GUIDE.md` + modifier les scripts

---

## ✨ PROCHAINES ÉTAPES

1. ✅ Lire `DEPLOYMENT_SUMMARY.md`
2. ✅ Préparer configuration (IP, domaines, API keys)
3. ✅ Exécuter `./quick-deploy.sh`
4. ✅ Configurer .env files sur VPS
5. ✅ Configurer DNS
6. ✅ Installer SSL (certbot)

---

**Créé:** 30 mai 2026  
**Version:** 1.0.0  
**Pour:** Deployment Webexa sur VPS  

🚀 **Bon déploiement!**
