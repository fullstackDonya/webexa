#!/bin/bash
# Essential Webexa Deployment Commands Cheat Sheet

echo "📋 WEBEXA DEPLOYMENT CHEAT SHEET"
echo "================================="
echo ""

cat << 'EOF'
## 🚀 QUICK START

# 1. Make scripts executable
chmod +x quick-deploy.sh setup-vps.sh deploy.sh verify-deployment.sh

# 2. Run deployment
./quick-deploy.sh

---

## 📡 SSH TO YOUR VPS

ssh root@your-vps-ip

---

## 📁 DIRECTORY STRUCTURE

/var/www/
├── webexa/              # PHP Application (webexa.fr)
│   ├── crm/
│   ├── erp/
│   ├── forms/
│   ├── assets/
│   ├── .env             # Configuration (create this)
│   └── composer.json
│
└── webexa-ai/           # Python API (webexa.online)
    ├── agents-ia/       # FastAPI application
    ├── venv/            # Virtual environment
    └── .env             # Configuration (create this)

---

## 🔧 CONFIGURATION

### Create PHP Configuration
cat > /var/www/webexa/.env << 'ENV'
APP_NAME=Webexa
APP_ENV=production
APP_DEBUG=false
APP_URL=https://webexa.fr

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=webexa_db
DB_USERNAME=webexa_user
DB_PASSWORD=your_password

OPENAI_API_KEY=sk-...
ANTHROPIC_API_KEY=sk-ant-...
ENV

### Create Python Configuration
cat > /var/www/webexa-ai/agents-ia/.env << 'ENV'
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=webexa_db
DB_USERNAME=webexa_user
DB_PASSWORD=your_password

OPENAI_API_KEY=sk-...
ANTHROPIC_API_KEY=sk-ant-...
ENV

---

## 🌐 NGINX

# Test configuration
nginx -t

# Reload Nginx
systemctl reload nginx

# Restart Nginx
systemctl restart nginx

# View Nginx status
systemctl status nginx

---

## 🐘 PHP-FPM

# Restart PHP-FPM
systemctl restart php-fpm

# View status
systemctl status php-fpm

# View PHP-FPM log
tail -f /var/log/php-fpm.log

---

## 🐍 PYTHON API

# Start the service
systemctl start webexa-api

# Stop the service
systemctl stop webexa-api

# Restart the service
systemctl restart webexa-api

# View service status
systemctl status webexa-api

# View API logs
tail -f /var/log/webexa/api.error.log
tail -f /var/log/webexa/api.log

# Check if API is running
curl http://127.0.0.1:8000/docs

# Manually run API (for debugging)
cd /var/www/webexa-ai
source venv/bin/activate
cd agents-ia
python main.py

---

## 📦 DEPENDENCIES

### PHP Dependencies
cd /var/www/webexa
composer install --optimize-autoloader --no-dev
composer update

### Python Dependencies
cd /var/www/webexa-ai
source venv/bin/activate
pip install -r agents-ia/requirements.txt
pip install --upgrade pip

---

## 📊 MONITORING & LOGS

# View Nginx errors
tail -f /var/log/nginx/webexa.fr.error.log
tail -f /var/log/nginx/webexa.online.error.log

# View API logs
journalctl -u webexa-api -f

# View all system logs
tail -f /var/log/syslog

# Check disk usage
df -h /var/www

# Monitor processes
htop

---

## 🔒 SSL CERTIFICATES

# Install certbot
apt-get install certbot python3-certbot-nginx

# Get certificate for webexa.fr
certbot --nginx -d webexa.fr -d www.webexa.fr

# Get certificate for webexa.online
certbot --nginx -d webexa.online -d www.webexa.online

# Renew certificates
certbot renew

# List certificates
certbot certificates

---

## 🧹 MAINTENANCE

# Update system packages
apt-get update && apt-get upgrade -y

# Clean package cache
apt-get clean && apt-get autoclean

# Remove old logs
find /var/log -name "*.log" -mtime +30 -delete

# Update PHP dependencies
cd /var/www/webexa && composer update

# Update Python dependencies
cd /var/www/webexa-ai && pip install --upgrade -r agents-ia/requirements.txt

---

## 🔐 PERMISSIONS

# Fix PHP permissions
chown -R www-data:www-data /var/www/webexa
chmod -R 755 /var/www/webexa

# Fix Python permissions
chown -R www-data:www-data /var/www/webexa-ai
chmod -R 755 /var/www/webexa-ai

# Make PHP files readable (not executable)
find /var/www/webexa -type f -name "*.php" -exec chmod 644 {} \;

---

## 🧪 TESTING

# Test PHP
curl -I http://webexa.fr
curl -I https://webexa.fr

# Test Python API
curl http://webexa.online
curl http://webexa.online/docs
curl http://127.0.0.1:8000/docs

# Check port availability
netstat -tlnp | grep :80
netstat -tlnp | grep :8000

# DNS resolution
nslookup webexa.fr
dig webexa.fr
nslookup webexa.online

---

## 🆘 TROUBLESHOOTING

# Restart all services
systemctl restart nginx php-fpm webexa-api

# Check what's using port 80
lsof -i :80

# Check what's using port 8000
lsof -i :8000

# Kill process on port 8000
fuser -k 8000/tcp

# Check system resources
free -h
df -h

# View recent errors
dmesg | tail -20

# Check service dependencies
systemctl list-dependencies nginx
systemctl list-dependencies webexa-api

---

## 📱 USEFUL TOOLS

# Install additional monitoring tools
apt-get install htop iotop nethogs

# Real-time monitoring
htop

# Check open ports
ss -tlnp

# Check network connections
netstat -tuln

# Monitor API requests in real-time
tcpdump -i eth0 -n 'tcp port 80 or tcp port 8000'

---

## 🚀 DEPLOYMENT VERIFICATION

# Run verification script (from your local machine)
scp verify-deployment.sh root@your-vps-ip:/tmp/
ssh root@your-vps-ip "bash /tmp/verify-deployment.sh"

# Or manually check
systemctl status nginx
systemctl status php-fpm
systemctl status webexa-api
curl http://webexa.fr
curl http://webexa.online/docs

---

## 📝 BACKUP & RESTORE

# Backup PHP application
tar -czf webexa-backup-$(date +%Y%m%d).tar.gz /var/www/webexa/

# Backup database (MySQL)
mysqldump -u webexa_user -p webexa_db > webexa-db-backup-$(date +%Y%m%d).sql

# Restore database
mysql -u webexa_user -p webexa_db < webexa-db-backup.sql

# Backup Python application
tar -czf webexa-ai-backup-$(date +%Y%m%d).tar.gz /var/www/webexa-ai/

---

## 🔄 GIT DEPLOYMENT (Optional)

# Deploy from Git
cd /var/www/webexa
git pull origin main
composer install --optimize-autoloader --no-dev
systemctl restart php-fpm

# Deploy Python updates
cd /var/www/webexa-ai
git pull origin main
source venv/bin/activate
pip install -r agents-ia/requirements.txt
systemctl restart webexa-api

---

## 📞 GETTING HELP

# Check Nginx version
nginx -v

# Check PHP version
php -v

# Check Python version
python3 --version

# Check Composer version
composer --version

# List installed packages
apt list --installed | grep -E "php|python|nginx"

EOF

echo ""
echo "================================="
echo "📌 Bookmark this file for quick reference!"
echo ""
echo "Save as: /var/www/webexa/CHEAT_SHEET.md"
