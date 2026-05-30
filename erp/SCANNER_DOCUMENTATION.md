# 📄 Documentation Scanner de Documents

## 🎯 Fonctionnalités

Le scanner de documents analyse automatiquement vos factures, reçus et relevés bancaires pour extraire les informations importantes.

## 📊 Données extraites automatiquement

### 💰 Données financières
- **Total TTC** : Montant total toutes taxes comprises
- **Total HT** : Montant hors taxes
- **TVA** : Montant de la TVA
- **Taux de TVA** : Pourcentage de TVA (20%, 10%, 5.5%)

### 📝 Informations du document
- **Numéro de facture/reçu** : Identifiant unique du document
- **Numéro de commande** : Référence de la commande
- **Référence client** : Votre numéro de client chez le fournisseur
- **Date du document** : Date d'émission
- **Date d'échéance** : Date limite de paiement

### 🏢 Informations de l'entreprise
- **Fournisseur** : Nom de la société émettrice
- **SIRET** : Numéro d'identification de l'entreprise
- **N° TVA Intracommunautaire** : Pour les transactions européennes
- **Email** : Contact du fournisseur
- **Téléphone** : Numéro de contact

### 💳 Informations de paiement
- **Moyen de paiement** : Carte, virement, chèque, espèces
- **IBAN** : Coordonnées bancaires pour virement

## 🔧 Installation de Tesseract (OCR pour images)

### Sur Hostinger (serveur de production)

Connectez-vous en SSH et installez Tesseract :

```bash
# Pour Ubuntu/Debian
sudo apt-get update
sudo apt-get install -y tesseract-ocr tesseract-ocr-fra

# Vérifier l'installation
tesseract --version
```

### Sur MAMP (développement local macOS)

```bash
# Avec Homebrew
brew install tesseract
brew install tesseract-lang  # Pour le français

# Vérifier
tesseract --version
```

### Sur Windows (XAMPP/Laragon)

1. Télécharger l'installeur : https://github.com/UB-Mannheim/tesseract/wiki
2. Installer avec le pack de langue française
3. Ajouter au PATH : `C:\Program Files\Tesseract-OCR`

## 📸 Types de documents supportés

### PDF
- ✅ Factures
- ✅ Devis
- ✅ Reçus
- ✅ Relevés bancaires
- ✅ Bulletins de paie
- ✅ Contrats

**Méthode** : Extraction de texte avec `pdftotext` (inclus dans la plupart des serveurs Linux)

### Images (JPG, PNG)
- ✅ Photos de tickets de caisse
- ✅ Scans de factures
- ✅ Photos de reçus

**Méthode** : OCR avec Tesseract (nécessite installation)

### Excel/CSV
- ⏳ À venir : Import automatique de relevés bancaires

## 🎨 Patterns de détection

Le système utilise des expressions régulières intelligentes pour détecter :

### Montants
```regex
Total TTC : 1 234,56 €
Montant total : 1234.56€
Net à payer : 1 234.56
```

### Dates
```regex
Date : 20/02/2026
Le : 20-02-2026
Émis le : 20.02.26
```

### Numéros
```regex
Facture n° FA-2026-001
Invoice #INV-123456
Reçu n° 12345
```

## 🚀 Amélioration continue

Le système apprend des patterns les plus courants. Si une donnée n'est pas extraite :

1. Vérifiez le format du document (PDF lisible, image nette)
2. Consultez les logs : `/logs/document_scanner_errors.log`
3. Les données manquantes peuvent être ajoutées manuellement

## 📈 Statistiques disponibles

- Total de documents scannés
- Documents traités avec succès
- Documents en attente de traitement
- Erreurs détectées

## 🔐 Sécurité

- ✅ Authentification obligatoire
- ✅ Isolation par customer_id
- ✅ Fichiers stockés hors du webroot public
- ✅ Validation des types MIME
- ✅ Logs détaillés des opérations

## 💡 Conseils d'utilisation

### Pour de meilleurs résultats

1. **Images** : Privilégier les photos nettes, bien éclairées, sans ombre
2. **PDF** : Utiliser des PDF avec texte extractible (pas des scans d'images)
3. **Nommage** : Inclure des mots-clés dans le nom du fichier (facture, reçu, etc.)
4. **Format** : Préférer PDF pour les documents professionnels

### Cas d'usage

- 📤 **Comptabilité** : Import automatique des factures fournisseurs
- 💳 **Notes de frais** : Scan de tickets de restaurant/transport
- 🏦 **Rapprochement bancaire** : Import des relevés bancaires
- 📊 **Reporting** : Centralisation de tous les justificatifs

## 🆘 Dépannage

### Le document n'est pas reconnu
- Vérifier que le type de fichier est supporté (PDF, JPG, PNG)
- S'assurer que le PDF contient du texte (pas juste une image)
- Installer Tesseract pour l'OCR des images

### L'extraction est incomplète
- Certains formats de factures sont non-standard
- Les données peuvent varier selon le fournisseur
- Vous pouvez compléter manuellement les informations manquantes

### Erreur 500 lors de l'upload
- Vérifier les logs : `/logs/document_scanner_errors.log`
- Vérifier les permissions du dossier `/uploads/scanned_documents/`
- Vérifier les limites PHP : `upload_max_filesize` et `post_max_size`

## 🔮 Fonctionnalités futures

- [ ] IA avancée pour extraction de tableaux
- [ ] Reconnaissance de lignes de produits
- [ ] Détection automatique de doublons
- [ ] Export vers logiciels comptables
- [ ] OCR multilingue avancé
- [ ] Validation automatique des calculs (HT + TVA = TTC)
