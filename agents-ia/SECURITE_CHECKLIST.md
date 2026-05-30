# 🔒 Checklist de Sécurité - Production

## ✅ Avant le déploiement

### 1. Clés et Secrets
- [ ] Nouvelle clé API générée (32+ caractères)
- [ ] Clé API différente de "dev-secret-key-change-me"
- [ ] Clés OpenAI/Anthropic sécurisées et actives
- [ ] Mot de passe base de données fort (16+ caractères, mixte)
- [ ] Fichier `.env` JAMAIS commité dans Git
- [ ] `.env` ajouté à `.gitignore`

### 2. Base de données
- [ ] Utilisateur DB spécifique créé (pas root)
- [ ] Privilèges minimaux accordés
- [ ] Connexions SSL activées si possible
- [ ] Backups automatiques configurés
- [ ] Accès limité par IP

### 3. Réseau et Firewall
- [ ] Port 8000 accessible UNIQUEMENT depuis le serveur web
- [ ] HTTPS configuré (pas de HTTP en production)
- [ ] Certificat SSL valide (Let's Encrypt)
- [ ] Rate limiting configuré sur Nginx
- [ ] IP whitelisting si possible

### 4. Fichiers et Permissions
- [ ] Ownership www-data:www-data
- [ ] Permissions 755 pour dossiers
- [ ] Permissions 644 pour fichiers
- [ ] `.env` en 600 (lecture seule propriétaire)
- [ ] Logs accessibles uniquement par root/www-data

---

## 🛡️ Sécurité applicative

### 5. API FastAPI
- [ ] Mode debug désactivé (ENV=production)
- [ ] CORS restreint aux domaines autorisés
- [ ] Validation stricte des entrées
- [ ] Rate limiting par IP/clé API
- [ ] Logs d'audit activés

### 6. Authentification
- [ ] Authentification par clé API obligatoire
- [ ] Rotation des clés planifiée (tous les 90j)
- [ ] Logging des accès non autorisés
- [ ] Timeout de session configuré

### 7. Données sensibles
- [ ] Pas de credentials en dur dans le code
- [ ] Chiffrement des données sensibles
- [ ] Logs sanitizés (pas de mots de passe)
- [ ] Variables d'environnement sécurisées

---

## 📊 Monitoring et Alertes

### 8. Surveillance
- [ ] Monitoring du service (systemd)
- [ ] Alertes en cas de crash
- [ ] Monitoring CPU/RAM/Disk
- [ ] Logs centralisés
- [ ] Dashboard de santé

### 9. Logs
- [ ] Rotation des logs configurée
- [ ] Rétention définie (30j minimum)
- [ ] Logs d'erreur séparés
- [ ] Alertes sur erreurs critiques

---

## 🔄 Maintenance

### 10. Mises à jour
- [ ] Processus de mise à jour documenté
- [ ] Tests en pré-production
- [ ] Rollback possible
- [ ] Dépendances à jour
- [ ] CVE surveillées

### 11. Backups
- [ ] Backup automatique quotidien
- [ ] Sauvegarde de la base de données
- [ ] Sauvegarde des configurations
- [ ] Tests de restauration mensuel
- [ ] Backups hors-site

### 12. Documentation
- [ ] Procédures d'urgence documentées
- [ ] Contacts d'astreinte à jour
- [ ] Diagramme d'architecture
- [ ] Runbook opérationnel
- [ ] Plan de reprise d'activité (PRA)

---

## ⚠️ Points critiques

### À VÉRIFIER ABSOLUMENT :

1. **Clé API**
   ```bash
   # Doit être différent !
   grep API_SECRET_KEY /var/www/crm/ia/.env
   # Ne doit PAS être : dev-secret-key-change-me
   ```

2. **Permissions fichiers**
   ```bash
   ls -la /var/www/crm/ia/.env
   # Doit être : -rw------- (600)
   ```

3. **Service actif**
   ```bash
   systemctl is-active crm-ai-agents
   # Doit retourner : active
   ```

4. **API accessible**
   ```bash
   curl -H "X-API-Key: VOTRE_CLE" http://localhost:8000/health
   # Doit retourner un JSON avec status: healthy
   ```

5. **Logs sans erreur**
   ```bash
   journalctl -u crm-ai-agents --since "10 minutes ago" | grep -i error
   # Ne doit PAS retourner d'erreurs critiques
   ```

---

## 🚨 En cas de compromission

Si vous suspectez une compromission de sécurité :

1. **IMMÉDIATEMENT :**
   ```bash
   # Arrêter le service
   sudo systemctl stop crm-ai-agents
   
   # Bloquer l'accès réseau
   sudo ufw deny 8000/tcp
   ```

2. **Générer nouvelle clé :**
   ```bash
   python3 generate-api-key.py
   # Mettre à jour .env
   # Redéployer
   ```

3. **Vérifier les logs :**
   ```bash
   grep "401\|403\|500" /var/log/crm-ai/api.log
   grep "Unauthorized" /var/log/nginx/crm-ai-access.log
   ```

4. **Notifier :**
   - Équipe sécurité
   - Administrateur système
   - Responsable technique

---

## 📞 Contacts d'urgence

```
Admin Sys  : +33 X XX XX XX XX
DevOps     : +33 X XX XX XX XX
Sécurité   : security@votre-entreprise.com
Astreinte  : +33 X XX XX XX XX
```

---

**Dernière révision :** 26 février 2026
**Statut :** Production
**Responsable :** [Votre nom]
