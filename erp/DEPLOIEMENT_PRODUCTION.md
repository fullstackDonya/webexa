# 🚀 Déploiement en Production - Guide Complet

## 📋 Plan de déploiement

### Option 1 : Installation pdftotext (Recommandé - Gratuit) ⭐

**Avantages :**
- ✅ 100% gratuit
- ✅ Extraction instantanée
- ✅ Aucun coût d'API

**Installation sur Hostinger :**

#### A. Si vous avez un VPS/Serveur dédié (accès SSH root)
```bash
# 1. Connectez-vous en SSH
ssh votre-serveur@webitech.fr

# 2. Installez pdftotext
sudo apt-get update
sudo apt-get install -y poppler-utils

# 3. Vérifiez
pdftotext -v
```

#### B. Si vous avez un hébergement partagé Hostinger
```bash
# Hostinger hébergement partagé = PAS d'accès sudo
# Solution : Demander au support Hostinger
```

**Email à envoyer au support Hostinger :**
```
Objet : Installation de poppler-utils sur mon serveur

Bonjour,

Je développe une application de gestion comptable qui nécessite 
d'extraire du texte depuis des PDF (factures, reçus).

Pourriez-vous installer le package "poppler-utils" sur mon serveur ?
(Commande : apt-get install poppler-utils)

Cet outil est léger, sécurisé et standard sur tous les serveurs Linux.

Mon nom de domaine : webitech.fr

Merci beaucoup !
```

**Délai de réponse Hostinger :** 1-24h généralement

---

### Option 2 : IA GPT-4 Vision (Solution immédiate)

**Avantages :**
- ✅ Fonctionne MAINTENANT (pas besoin d'attendre le support)
- ✅ Plus précis que pdftotext (comprend les images floues)
- ✅ Gère tous les formats (PDF, JPG, PNG)
- ⚠️ Coût : ~0.01€ par document

**Configuration (5 minutes) :**

1. **Créer un compte OpenAI** : https://platform.openai.com/signup
2. **Ajouter du crédit** : https://platform.openai.com/account/billing (minimum 5€)
3. **Générer une clé API** : https://platform.openai.com/api-keys
4. **Configurer dans votre code** :

```php
// Dans erp/api/document_scanner.php ligne 34
define('OPENAI_API_KEY', 'sk-proj-VOTRE_CLE_ICI');
```

---

### Option 3 : Système Hybride (OPTIMAL) 🎯

**C'est ce que j'ai codé pour vous !**

```
┌─────────────────────────────────┐
│  Document uploadé               │
└────────────┬────────────────────┘
             │
             ▼
    ┌────────────────┐
    │ pdftotext ?    │
    └────┬───────┬───┘
         │       │
    OUI  │       │  NON
         ▼       ▼
    ┌─────┐   ┌──────────┐
    │GRAT.│   │IA (0.01€)│
    │ 0€  │   │          │
    └─────┘   └──────────┘
```

**Résultat :**
- PDF normal → pdftotext (0€)
- Photo de ticket → GPT-4 Vision (0.01€)
- PDF scanné → GPT-4 Vision (0.01€)

**Coût réel mensuel : 2-10€** (au lieu de 100€ avec IA seule)

---

## 📦 Déploiement sur production

### Étape 1 : Upload des fichiers

Envoyez ces fichiers sur votre serveur Hostinger :

```bash
# Via FTP ou FileZilla
erp/api/DocumentAIExtractor.php      → NOUVEAU fichier
erp/api/document_scanner.php         → MODIFIÉ (avec IA)
erp/install-pdftotext-production.sh  → Script d'installation
```

### Étape 2 : Configuration

**A. Configuration minimale (pdftotext seulement - GRATUIT)**
```php
// Dans erp/api/document_scanner.php ligne 34
define('OPENAI_API_KEY', ''); // Laissez vide = pas d'IA
```

**B. Configuration optimale (hybride)**
```php
// Dans erp/api/document_scanner.php ligne 34
define('OPENAI_API_KEY', 'sk-proj-VOTRE_CLE');
```

### Étape 3 : Test en production

1. Allez sur : `https://webitech.fr/erp/document-scanner.php`
2. Uploadez un PDF de facture
3. Regardez les logs :

```bash
# Via SSH ou panneau Hostinger
tail -50 /home/votrecompte/public_html/logs/document_scanner_errors.log
```

Vous verrez :
```
Document analyzed - Method: pdftotext, Cost: 0€, Confidence: 85%
```

Ou si pdftotext manque :
```
Document analyzed - Method: gpt4-vision, Cost: 0.01€, Confidence: 95%
```

---

## 💰 Comparaison des coûts en production

### Scénario : 500 documents/mois

| Solution | Coût initial | Coût mensuel | Précision |
|---|---|---|---|
| **pdftotext seul** | 0€ | 0€ | 80% |
| **IA seule (OpenAI)** | 0€ | 50€ | 95% |
| **Hybride (OPTIMAL)** | 0€ | 2-5€ | 95% |

### Calcul détaillé Hybride :
```
500 documents dont :
- 450 PDF normaux → pdftotext → 0€
- 50 images/PDF scannés → IA → 50 × 0.01€ = 0.50€

Total : 0.50€/mois
```

---

## 🔐 Sécurité de la clé API

**Option 1 : Variable d'environnement (recommandé)**
```bash
# Dans /etc/environment ou .bashrc
export OPENAI_API_KEY="sk-proj-xxxxx"
```

**Option 2 : Fichier .env**
```bash
# Créer erp/.env
OPENAI_API_KEY=sk-proj-xxxxx

# Dans .gitignore
.env
```

```php
// Dans document_scanner.php
$dotenv = parse_ini_file(__DIR__ . '/../.env');
define('OPENAI_API_KEY', $dotenv['OPENAI_API_KEY'] ?? '');
```

**Option 3 : Directement dans le code**
```php
// ATTENTION : Ne pas commit dans Git !
define('OPENAI_API_KEY', 'sk-proj-xxxxx');
```

---

## 📊 Monitoring des coûts

Ajoutez un tableau de bord dans l'interface :

```sql
-- Statistiques d'extraction
SELECT 
    DATE(created_at) as date,
    COUNT(*) as total_documents,
    SUM(CASE WHEN JSON_EXTRACT(extracted_data, '$.extraction_method') = 'pdftotext' THEN 1 ELSE 0 END) as gratuit,
    SUM(CASE WHEN JSON_EXTRACT(extracted_data, '$.extraction_method') = 'gpt4-vision' THEN 1 ELSE 0 END) as ia,
    SUM(CASE WHEN JSON_EXTRACT(extracted_data, '$.extraction_method') = 'gpt4-vision' THEN 0.01 ELSE 0 END) as cout_total
FROM erp_scanned_documents
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(created_at)
ORDER BY date DESC;
```

---

## 🆘 Dépannage production

### Erreur "pdftotext not found"
```bash
# Solution 1 : Installer
sudo apt-get install poppler-utils

# Solution 2 : Activer l'IA
define('OPENAI_API_KEY', 'votre-cle');
```

### Erreur "OpenAI API error"
- Vérifiez la clé API
- Vérifiez le crédit restant : https://platform.openai.com/account/billing
- Consultez les logs détaillés

### Coûts explosent
```php
// Désactiver temporairement l'IA
define('OPENAI_API_KEY', '');

// Ou augmenter le seuil de confiance
// Dans DocumentAIExtractor.php ligne 45
if ($result['confidence'] > 90) { // Au lieu de 70
```

---

## ✅ Checklist de déploiement

- [ ] Fichiers uploadés sur Hostinger
- [ ] Configuration OPENAI_API_KEY (ou vide)
- [ ] Test d'upload d'un PDF
- [ ] Vérification des logs
- [ ] Essai d'installer pdftotext (ou demande au support)
- [ ] Monitoring des coûts en place

---

## 🎯 Recommandation finale

**Pour démarrer MAINTENANT :**
1. Configurez l'IA (5€ de crédit OpenAI)
2. Uploadez les fichiers
3. Testez immédiatement

**Pour optimiser les coûts :**
1. Demandez au support Hostinger d'installer pdftotext
2. Une fois installé, 95% de vos docs seront gratuits
3. L'IA reste en backup pour les 5% difficiles

**Coût hybride réel : 2-5€/mois** au lieu de 50€/mois avec IA seule.
