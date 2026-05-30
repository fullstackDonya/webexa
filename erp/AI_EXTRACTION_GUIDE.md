# Configuration du Scanner de Documents IA

## 🎯 Stratégie hybride intelligente

Ce système utilise une approche **hybride** pour minimiser les coûts :

### Niveau 1 : Extraction gratuite (pdftotext)
- ✅ 95% des PDF sont du texte pur
- ✅ Extraction instantanée
- ✅ **Coût : 0€**

### Niveau 2 : IA avancée (GPT-4 Vision) - Fallback
- ⚡ Utilisé seulement si extraction gratuite échoue
- ⚡ Pour images de tickets, PDF scannés, documents complexes
- ⚡ **Coût : ~0.01€ par document**

## 📊 Coûts réels estimés

```
Volume mensuel | Coût avec pdftotext seul | Coût avec IA fallback
---------------|-------------------------|---------------------
100 docs       | 0€                      | 0.50-1€
500 docs       | 0€                      | 2-5€
1000 docs      | 0€                      | 5-10€
```

## 🚀 Installation

### Étape 1 : Installer pdftotext (OBLIGATOIRE - Gratuit)

#### Sur serveur Hostinger (SSH)
```bash
chmod +x install-pdftotext.sh
./install-pdftotext.sh
```

Ou manuellement :
```bash
# Debian/Ubuntu
sudo apt-get update && sudo apt-get install -y poppler-utils

# Vérifier
pdftotext -v
```

#### Sur macOS (MAMP local)
```bash
brew install poppler
```

### Étape 2 : Configurer l'IA (OPTIONNEL)

Seulement si vous voulez l'IA pour les cas difficiles :

1. Créer un compte OpenAI : https://platform.openai.com/
2. Générer une clé API : https://platform.openai.com/api-keys
3. Ajouter 5-10€ de crédit sur votre compte

4. Configurer la clé dans votre fichier :

**Option A : Variable d'environnement (recommandé)**
```bash
# Dans .env ou configuration serveur
export OPENAI_API_KEY="sk-proj-xxxxxxxxxxxxx"
```

**Option B : Directement dans le code**
```php
// Dans erp/api/document_scanner.php ligne 32
define('OPENAI_API_KEY', 'sk-proj-xxxxxxxxxxxxx');
```

## 🧪 Test du système

1. **Sans IA (gratuit)** :
   - Uploadez un PDF de facture normal
   - L'extraction se fera avec pdftotext (0€)
   - Vérifiez les logs : `method: pdftotext`

2. **Avec IA (si configurée)** :
   - Uploadez une photo de ticket de caisse floue
   - L'IA prendra le relais automatiquement
   - Vérifiez les logs : `method: gpt4-vision, cost: 0.01€`

## 📈 Monitoring des coûts

Les logs indiquent pour chaque document :
```
Document analyzed - Method: pdftotext, Cost: 0€, Confidence: 85%
Document analyzed - Method: gpt4-vision, Cost: 0.01€, Confidence: 95%
```

Statistiques mensuelles disponibles dans l'interface.

## ⚙️ Configuration avancée

### Désactiver complètement l'IA
```php
define('OPENAI_API_KEY', ''); // Laissez vide
```

### Ajuster le seuil de confiance
```php
// Dans DocumentAIExtractor.php ligne 45
if ($result['confidence'] > 70) { // Changer 70 en 50 ou 90
```

70 = équilibre optimal (95% gratuit, 5% IA)
50 = utilise plus l'IA (meilleure précision, coût++)
90 = utilise rarement l'IA (économie maximale)

## 💡 Recommandations

1. **Commencez sans IA** (OPENAI_API_KEY vide)
2. Installez pdftotext et testez
3. Si 95%+ de vos documents fonctionnent → restez gratuit !
4. Si problèmes persistants → ajoutez l'IA

## 🆘 Dépannage

### pdftotext introuvable
```bash
which pdftotext  # Vérifier installation
sudo apt-get install poppler-utils  # Réinstaller
```

### IA ne fonctionne pas
- Vérifiez la clé API OpenAI
- Vérifiez le crédit sur votre compte OpenAI
- Consultez les logs : `logs/document_scanner_errors.log`

### Coûts trop élevés
- Vérifiez que pdftotext est installé
- Augmentez le seuil de confiance à 90
- Désactivez l'IA temporairement

## 📞 Support

En cas de problème, consultez les logs détaillés :
```bash
tail -100 logs/document_scanner_errors.log
```
