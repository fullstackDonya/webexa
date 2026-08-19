# Guide complet d'import de Leads

## 🚀 Vue d'ensemble

Le système d'import est **flexible et tolérant aux champs manquants**. Vous pouvez importer des leads même s'ils ne sont pas complets - les champs manquants peuvent être remplis manuellement après.

## 📋 Champs supportés

### Identifiant minimum (au moins UN requis)
Pour qu'un lead soit importé, vous devez fournir **au minimum l'un de ces éléments** :
- ✅ **email** (valide)
- ✅ **phone** (téléphone)
- ✅ **first_name + company** (prénom + entreprise)

### Champs disponibles

| Colonne CSV | Format | Exemple | Notes |
|---|---|---|---|
| **first_name** (prénom) | Texte | Jean | Alias: `prénom` |
| **last_name** (nom) | Texte | Dupont | Alias: `nom` |
| **email** | Email | jean@example.com | Validé automatiquement |
| **phone** (téléphone1) | Texte | +33612345678 | Alias: `téléphone`, `phone1` |
| **phone2** (téléphone2) | Texte | +33698765432 | Optionnel |
| **phone3** (téléphone3) | Texte | +33123456789 | Optionnel |
| **company** (entreprise) | Texte | Acme Corp | Créée automatiquement si manquante |
| **display_name** | Texte | Acme | Alias: `nom_societe`, `company_display` |
| **secteur_activite** | Texte | Informatique | Alias: `secteur`, `activity`, `activité` |
| **position** (poste) | Texte | Directeur IT | Alias: `poste` |
| **heures_ouverture** | Texte | 09:00-18:00 | Alias: `horaires`, `hours`, `bi-hours` |
| **address** (adresse) | Texte | 123 Rue de Paris | Alias: `adresse` |
| **url** (website) | URL | https://acme.com | Alias: `website`, `site` |
| **tags** | Texte | PME, startup | Alias: `tag` - comment appeler la société |
| **description** | Texte long | Excellent prospect | Alias: `notes` |
| **source** | Texte | LinkedIn | Défaut: `import` |
| **status** | new/contacted/qualified/unqualified | new | Défaut: `new` |
| **budget** | Nombre | 50000 | Numérique uniquement |

## 📝 Exemples de CSV valides

### ✅ Exemple 1: Contact complet
```csv
first_name,last_name,email,phone,company,display_name,secteur_activite,position,address,heures_ouverture,url,tags,description
Jean,Dupont,jean@example.com,+33612345678,Acme Corp,Acme,Informatique,Directeur IT,123 Rue de Paris,09:00-18:00,https://acme.com,IT Solutions,Prospect excellent
```

### ✅ Exemple 2: Données minimales par email
```csv
first_name,email,company
Jean,jean@example.com,Acme
```

### ✅ Exemple 3: Restaurant/Commerce (pas de contact personnel)
```csv
company,display_name,phone,phone2,secteur_activite,address,heures_ouverture,url,tags
Restaurant Étoile,L'Étoile,+33812345678,+33812345679,Restauration,456 Boulevard,11:30-23:00,https://resto-etoile.com,Restaurant,3 étoiles
```

### ✅ Exemple 4: Données très partielles (sera complété manuellement)
```csv
company,phone,url,activity
TechSoft,+33712345678,https://techsoft.fr,Télécommunications
```

## 🔄 Algorithme d'import

1. **Lecture du CSV** → Normalisation des en-têtes (minuscules, accents ignorés)
2. **Extraction des champs** → Accepte les alias en français/anglais
   - `activity` = `secteur` = `secteur_activite`
   - `horaires` = `heures_ouverture` = `hours` = `bi-hours`
   - `site` = `url` = `website`
   - Et bien d'autres (voir la liste complète ci-dessus)
3. **Validation minimale** → Vérifie qu'au moins email OU phone OU (nom + entreprise) existent
4. **Recherche de doublons** → Par email (si email fourni)
5. **Résolution de l'entreprise** → Cherche ou crée la company si manquante
6. **Insertion/Mise à jour** → Dans la table `leads`

## ⚠️ Cas de gestion des doublons

### Option 1: Ignorer les doublons (cochée par défaut)
Si un lead avec le même email existe déjà → **Skip** (non importé)

### Option 2: Mettre à jour les doublons (décochez la case)
Si un lead avec le même email existe déjà → **Update** (remplace les données)

## 🛠️ Installation / Migration DB

Avant le premier import, exécutez la migration SQL:

```bash
cd crm/migrations
mysql -u root -p webexa < add_leads_fields.sql
```

Ou via phpMyAdmin:
1. Allez dans l'onglet "SQL"
2. Copiez-collez le contenu de `add_leads_fields.sql`
3. Exécutez

## 📊 Format du fichier

- **Encodage**: UTF-8 (avec BOM pour Excel: `UTF-8-SIG`)
- **Séparateur**: Virgule (`,`)
- **En-tête**: Ligne 1 = noms des colonnes
- **Taille max**: Dépend de votre serveur (généralement 100+ Mo par fichier)

### Exporter depuis Excel vers CSV:
1. Ouvrir le fichier Excel
2. Fichier → Enregistrer sous
3. Format: **CSV UTF-8 (séparé par des virgules)**
4. ✅ Valider

### Depuis Google Sheets:
1. Fichier → Télécharger
2. Format: **CSV (Valeurs séparées par des virgules)**
3. ✅ Télécharger

## 📈 Résultats d'import

Après import, vous verrez:
```
Import terminé : 42 ajoutés, 8 mis à jour, 3 ignorés.
```

- **Ajoutés**: Nouveaux leads créés
- **Mis à jour**: Doublons mises à jour (si option décochée)
- **Ignorés**: Lignes sans identifiant valide

## ❓ Dépannage

### "Erreur de téléchargement du fichier"
→ Vérifier la taille du fichier et les permissions du serveur

### "Format non supporté. Utilisez CSV"
→ Doit être `.csv` (pas `.xls`, `.xlsx`, `.txt`)

### "Aucune donnée lisible trouvée"
→ Vérifier l'encodage (doit être UTF-8)

### "0 leads importés"
→ Vérifier que chaque ligne a au minimum: email OU phone OU (nom + entreprise)

## 🎯 Bonnes pratiques

1. **Testez d'abord** → Importez 5-10 leads pour vérifier le format
2. **Nettoyez les données** → Supprimez les doublons avant import
3. **Cochez "Ignorer doublons"** → Évite les overwrite accidentels
4. **Nommez la source** → Mettez le source (LinkedIn, Email, Web scraping, etc.)
5. **Validez les emails** → Certains fichiers contiennent des emails invalides
6. **Vérifiez les téléphones** → Préférez le format international (+33 pour France)

## 📞 Support

Pour des questions techniques, consultez:
- Le fichier exemple: `crm/examples/import-leads-example.csv`
- Logs du serveur: `agents-ia/logs/`
