# 🚀 Guide de Démarrage Rapide

## Installation en 5 minutes

### 1. Prérequis
```bash
python3 --version  # Doit être 3.10+
mysql --version    # MySQL ou MariaDB
```

### 2. Installation automatique
```bash
cd /Applications/MAMP/htdocs/PP/webitech/WEB/crm/ia
./start.sh
```

Le script va :
- ✅ Créer l'environnement virtuel Python
- ✅ Installer toutes les dépendances
- ✅ Vérifier la base de données
- ✅ Exécuter les migrations si nécessaire
- ✅ Démarrer l'API sur http://localhost:8000

### 3. Configuration minimale

Éditer `.env` :
```env
# 1. Ajouter votre clé API OpenAI
OPENAI_API_KEY=sk-votre-cle-here

# 2. Changer la clé secrète API
API_SECRET_KEY=une-cle-unique-et-securisee

# 3. Vérifier la config DB (doit correspondre au CRM)
DB_NAME=webitech
DB_USER=root
DB_PASSWORD=root
```

### 4. Premier test

**Option A : Via le CRM**
1. Ouvrir : http://localhost/crm/ai-dashboard.php
2. Cliquer sur "État du système"
3. Devrait afficher : ✅ Système opérationnel

**Option B : Via curl**
```bash
curl http://localhost:8000/health
```

Devrait retourner :
```json
{
  "status": "healthy",
  "database": "connected",
  "agents": {...}
}
```

### 5. Premier email analysé

**Via le dashboard :**
1. Aller sur : http://localhost/crm/ai-dashboard.php
2. Cliquer : "📧 Analyser les emails"
3. L'IA va traiter les emails non analysés

**Via curl :**
```bash
curl -X POST http://localhost:8000/api/inbox/process-pending \
  -H "Content-Type: application/json" \
  -H "X-API-Key: votre-cle-secrete" \
  -d '{"customer_id": 1, "limit": 10}'
```

## 🎯 Utilisation quotidienne

### Workflow typique

1. **Matin** : Lancer `./start.sh` pour démarrer l'API
2. **Dashboard** : Consulter http://localhost/crm/ai-dashboard.php
3. **Actions** : Approuver/Rejeter les suggestions IA
4. **Automatique** : L'IA analyse les nouveaux emails en continu

### Commandes utiles

```bash
# Démarrer l'API
./start.sh

# Arrêter l'API
# Appuyez sur Ctrl+C dans le terminal

# Voir les logs en temps réel
tail -f logs/agents.log

# Tester la connexion
curl http://localhost:8000/health

# Voir les endpoints disponibles
curl http://localhost:8000
```

## 🔥 Actions fréquentes

### Analyser tous les emails non traités
```bash
curl -X POST http://localhost:8000/api/inbox/process-pending \
  -H "X-API-Key: your-key" \
  -H "Content-Type: application/json" \
  -d '{"customer_id": 1, "limit": 50}'
```

### Récupérer les leads chauds
```bash
curl http://localhost:8000/api/leads/hot/1?limit=10 \
  -H "X-API-Key: your-key"
```

### Voir les actions en attente
```bash
curl http://localhost:8000/api/actions/pending/1 \
  -H "X-API-Key: your-key"
```

## ❓ Problèmes courants

### "API non accessible"
```bash
# Vérifier si l'API tourne
curl http://localhost:8000/health

# Si non, la redémarrer
./start.sh
```

### "Database connection error"
```bash
# Vérifier MySQL
mysql -u root -proot -e "SHOW DATABASES;"

# Vérifier la config .env
cat .env | grep DB_
```

### "Invalid API key"
```bash
# Vérifier la clé dans .env
cat .env | grep API_SECRET_KEY

# Mettre à jour la clé côté CRM
# Dans /crm/api/AIAgentsClient.php
```

### "LLM error"
```bash
# Vérifier la clé OpenAI
cat .env | grep OPENAI_API_KEY

# Tester directement
python -c "from llm_service import llm; print(llm.chat([{'role': 'user', 'content': 'test'}]))"
```

## 📊 Monitoring

### Vérifier l'activité
```sql
-- Logs récents
SELECT * FROM agent_logs 
ORDER BY created_at DESC 
LIMIT 20;

-- Actions en attente
SELECT COUNT(*) FROM agent_actions 
WHERE status = 'pending';

-- Stats par agent
SELECT * FROM agent_stats 
WHERE date = CURDATE();
```

### Dashboard en temps réel
```bash
# Ouvrir dans le navigateur
open http://localhost/crm/ai-dashboard.php
```

## 🎓 Aller plus loin

**Voir les exemples de code :**
```bash
cat examples.py
```

**Lire le cahier des charges complet :**
```bash
cat notes
```

**Documentation complète :**
```bash
cat README.md
```

## 🆘 Support

Si vous rencontrez un problème :

1. **Logs** : `tail -f logs/agents.log`
2. **Database** : `SELECT * FROM agent_logs ORDER BY created_at DESC LIMIT 10`
3. **API** : `curl http://localhost:8000/health`
4. **Dashboard** : http://localhost/crm/ai-dashboard.php

---

**🎉 C'est parti ! L'IA travaille maintenant pour votre CRM.**
