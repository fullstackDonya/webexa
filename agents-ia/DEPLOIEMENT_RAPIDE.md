# 🚀 Déploiement Production - Guide Rapide

## 📋 Résumé en 5 étapes

### 1️⃣ Générer une clé API sécurisée

```bash
python3 generate-api-key.py
```

**Notez la clé dans un gestionnaire de mots de passe !**

---

### 2️⃣ Configurer les fichiers

**Fichier `.env` (Python) :**
```bash
cp .env.production .env
nano .env
```

Modifiez :
- `API_SECRET_KEY=` → Votre clé générée
- `DB_HOST=`, `DB_USER=`, `DB_PASSWORD=` → Vos infos Oracle
- `OPENAI_API_KEY=` → Votre vraie clé OpenAI
- `ENV=production`

**Fichier `.env` (PHP dans /crm/) :**
```bash
echo "AI_API_URL=https://votre-domaine.com" >> /var/www/crm/.env
echo "AI_API_KEY=VOTRE_CLE_ICI" >> /var/www/crm/.env
```

---

### 3️⃣ Tester la sécurité

```bash
python3 test-security.py
```

**Tous les tests doivent passer ✅**

---

### 4️⃣ Déployer automatiquement

```bash
# Sur votre serveur Oracle
./deploy-production.sh
```

Le script s'occupe de tout :
- Installation des dépendances
- Configuration systemd
- Démarrage du service
- Tests de santé

---

### 5️⃣ Vérifier que tout fonctionne

```bash
# Statut du service
sudo systemctl status crm-ai-agents

# Health check
curl https://votre-domaine.com/health

# Logs en direct
sudo journalctl -u crm-ai-agents -f
```

---

## 📚 Documentation complète

- **[DEPLOIEMENT_PRODUCTION_ORACLE.md](DEPLOIEMENT_PRODUCTION_ORACLE.md)** - Guide complet pas à pas
- **[SECURITE_CHECKLIST.md](SECURITE_CHECKLIST.md)** - Checklist de sécurité
- **[QUICKSTART.md](QUICKSTART.md)** - Guide de développement local

---

## 🆘 Problèmes courants

### Service ne démarre pas
```bash
sudo journalctl -u crm-ai-agents -n 50
```

### DB inaccessible
```bash
mysql -h VOTRE_HOST -u VOTRE_USER -p
```

### API retourne 401
- Vérifiez que la clé API est la même partout
- Redémarrez le service après modification du .env

### Erreur "column folder"
- Vérifiez que vous avez la dernière version du code
- Les colonnes doivent être `mailbox` et `sent_at`

---

## 🔐 Sécurité - Points critiques

⚠️ **JAMAIS en production :**
- Clé API `dev-secret-key-change-me`
- Mot de passe `root:root`
- Mode `AUTOMATION_MODE=autonomous`
- `ENV=development`
- Fichier `.env` commité dans Git

✅ **TOUJOURS en production :**
- Clé API unique de 32+ caractères
- Certificat SSL valide
- Logs de sécurité activés
- Backups automatiques
- Monitoring actif

---

## 📞 Support

En cas de problème :

1. Consultez les logs : `sudo journalctl -u crm-ai-agents`
2. Vérifiez la checklist : [SECURITE_CHECKLIST.md](SECURITE_CHECKLIST.md)
3. Relancez les tests : `python3 test-security.py`

---

**Version :** 1.0  
**Date :** 26 février 2026  
**Environnement :** Oracle Cloud Production
