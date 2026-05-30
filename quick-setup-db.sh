#!/bin/bash
# 🗄️ QUICK DATABASE SETUP FOR WEBEXA

DB_NAME="webexa"
DB_USER="webexa_user"

echo "🗄️  Creating Webexa Database..."
echo ""

# Get root password
read -sp "MySQL root password (press Enter if none): " MYSQL_PASS
echo ""

# Read new user password
read -sp "New password for webexa_user: " DB_PASS
echo ""

# Create database and user
if [ -z "$MYSQL_PASS" ]; then
    mysql -u root << EOF
CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
EOF
else
    mysql -u root -p"$MYSQL_PASS" << EOF
CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
EOF
fi

echo "✅ Database and user created!"
echo ""

# Import schema
echo "📥 Importing database schema..."
echo ""

if [ -z "$MYSQL_PASS" ]; then
    mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < crm/crm_database.sql
else
    mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < crm/crm_database.sql
fi

echo ""
echo "✅ Schema imported!"
echo ""

# Count tables
TABLE_COUNT=$(mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='$DB_NAME';" 2>/dev/null | tail -1)

echo "📊 Database Summary:"
echo "  Database: $DB_NAME"
echo "  User: $DB_USER"
echo "  Tables created: $TABLE_COUNT"
echo ""

echo "🔐 Add these to your .env file:"
echo "  DB_HOST=localhost"
echo "  DB_USERNAME=$DB_USER"
echo "  DB_PASSWORD=$DB_PASS"
echo "  DB_DATABASE=$DB_NAME"
echo ""

echo "✅ Done! Update .env and restart PHP-FPM:"
echo "  systemctl restart php-fpm nginx webexa-api"
echo ""
