# Guide de Migration - Table Emails

## 🔴 Problème Détecté

**Erreur**: `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'e.config_id' in 'ON'`

Cette erreur signifie que la table `emails` dans votre base de données ne contient pas les colonnes nécessaires au bon fonctionnement du système d'emails.

## ✅ Solution

### Option 1: Migration via Interface Web (Recommandé)

1. **Accédez à la page de migration:**
   ```
   https://webitech.fr/crm/migrate-email-customer-id.php
   ```

2. **La page affichera:**
   - Les colonnes manquantes (config_id et/ou customer_id)
   - Un bouton pour lancer la migration

3. **Cliquez sur "Exécuter la migration"**
   - La migration est sécurisée et idempotente (peut être exécutée plusieurs fois sans problème)
   - Elle ajoute uniquement ce qui manque

4. **Vérification:**
   - La page affichera un résumé de ce qui a été fait
   - Cliquez sur "Accéder à la boîte de réception" pour tester

### Option 2: Migration via Ligne de Commande

```bash
# Depuis le serveur
cd /path/to/webitech/crm
mysql -u root -p webitech < database/migrate_add_customer_id_to_emails.sql
```

## 📋 Ce que Fait la Migration

### Colonnes Ajoutées

1. **config_id** (INT)
   - Lie chaque email à une configuration email (compte Gmail, Outlook, etc.)
   - Foreign key vers `email_configurations(id)`
   - Nécessaire pour savoir depuis quel compte l'email a été reçu

2. **customer_id** (INT)
   - Lie chaque email à un client
   - Foreign key vers `customers(id)`
   - Nécessaire pour isoler les emails par client (multi-tenant)

### Index Créés

- `idx_emails_config_id` - Performances sur les filtres par configuration
- `idx_emails_customer_id` - Performances sur les filtres par client

### Foreign Keys Ajoutées

- `emails_ibfk_1` - Intégrité référentielle vers email_configurations
- `fk_emails_customer` - Intégrité référentielle vers customers

### Remplissage Automatique

Si des emails existent déjà dans la table:
- `customer_id` sera rempli depuis `email_configurations.customer_id`
- Basé sur la relation `emails.config_id = email_configurations.id`

## ⚠️ Important

### Avant la Migration

1. **Backup recommandé:**
   ```bash
   mysqldump -u root -p webitech emails > backup_emails_$(date +%Y%m%d_%H%M%S).sql
   ```

2. **Vérifier l'existence de la table email_configurations:**
   ```sql
   SHOW TABLES LIKE 'email_configurations';
   ```
   Si elle n'existe pas, exécutez d'abord:
   ```bash
   mysql -u root -p webitech < database/email_tables.sql
   ```

### Après la Migration

1. **Vérifier les colonnes:**
   ```sql
   DESCRIBE emails;
   ```
   Vous devriez voir `config_id` et `customer_id`

2. **Vérifier les données:**
   ```sql
   SELECT COUNT(*) FROM emails WHERE customer_id IS NULL;
   SELECT COUNT(*) FROM emails WHERE config_id IS NULL;
   ```
   Ces requêtes devraient retourner 0

## 🔍 Dépannage

### Si la Migration Échoue

1. **Erreur de foreign key:**
   - Vérifiez que `email_configurations` existe et contient des données
   - Vérifiez que `customers` existe

2. **Erreur de NULL:**
   - La migration garde les colonnes NULL si elles ne peuvent pas être remplies
   - Vérifiez manuellement les données manquantes:
     ```sql
     SELECT id, email_id, from_address FROM emails WHERE config_id IS NULL LIMIT 10;
     ```

3. **Erreur de permissions:**
   - Assurez-vous que l'utilisateur MySQL a les droits ALTER TABLE

### Si l'Erreur Persiste Après Migration

1. **Vider le cache PHP:**
   ```bash
   # Si vous utilisez OPcache
   sudo service php-fpm restart
   ```

2. **Vérifier les logs:**
   ```bash
   tail -f /var/log/apache2/error.log
   # ou
   tail -f /var/log/php-fpm/error.log
   ```

3. **Tester une requête manuelle:**
   ```sql
   SELECT e.*, ec.email 
   FROM emails e 
   JOIN email_configurations ec ON e.config_id = ec.id 
   WHERE e.customer_id = 1 
   LIMIT 1;
   ```

## 📞 Support

Si vous rencontrez toujours des problèmes après avoir suivi ce guide:

1. Vérifiez la structure actuelle:
   ```sql
   SHOW CREATE TABLE emails;
   ```

2. Exportez le résultat et partagez-le pour diagnostic

3. Vérifiez les versions:
   ```bash
   mysql --version
   php --version
   ```
