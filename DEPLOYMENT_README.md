# 🚀 Webexa Deployment Guide

## 📋 Overview

This guide helps you deploy the Webexa application to your VPS with two separate domains:
- **webexa.fr** - PHP Web Application
- **webexa.online** - Python AI API (FastAPI)

## 🏗️ Architecture

```
Your VPS (/var/www)
├── html/          (Existing site)
├── siteo/         (Existing site)
├── webexa/        ← PHP App (webexa.fr)
│   ├── crm/
│   ├── erp/
│   ├── forms/
│   ├── assets/
│   ├── index.php
│   └── .env
└── webexa-ai/     ← Python API (webexa.online)
    ├── agents-ia/
    ├── venv/
    └── .env
```

## 🚀 Quick Start (Automated)

### Option 1: Full Automated Deployment
```bash
# Make scripts executable
chmod +x quick-deploy.sh setup-vps.sh deploy.sh

# Run the automated deployment
./quick-deploy.sh
```

The script will ask for:
- VPS IP address
- VPS username (default: root)
- Domains (defaults: webexa.fr, webexa.online)

This handles:
- ✅ VPS setup (packages, configuration)
- ✅ File synchronization
- ✅ Dependency installation
- ✅ Service configuration
- ✅ Nginx setup

### Option 2: Manual Deployment
Follow the steps in `DEPLOYMENT_GUIDE.md`

## 📝 Pre-Deployment Checklist

Before deploying, ensure:

- [ ] VPS access via SSH
- [ ] Domain names registered (webexa.fr, webexa.online)
- [ ] VPS has at least 2GB RAM
- [ ] PHP version compatible (7.4+)
- [ ] Python 3.8+ installed
- [ ] MySQL/Oracle database access configured
- [ ] .env files prepared with:
  - Database credentials
  - API keys (OpenAI, Anthropic, etc.)
  - Redis connection (if needed)
  - Email configuration

## 🔧 Configuration Files

### 1. PHP Configuration (.env)

Create `/var/www/webexa/.env`:
```env
APP_NAME=Webexa CRM/ERP
APP_ENV=production
APP_DEBUG=false
APP_URL=https://webexa.fr

# Database
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=webexa_db
DB_USERNAME=webexa_user
DB_PASSWORD=secure_password

# Email
MAIL_DRIVER=smtp
MAIL_HOST=mail.your-domain.com
MAIL_PORT=587
MAIL_USERNAME=noreply@webexa.fr
MAIL_PASSWORD=email_password

# API Keys
OPENAI_API_KEY=sk-...
ANTHROPIC_API_KEY=sk-ant-...

# Google OAuth
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
```

### 2. Python Configuration (.env)

Create `/var/www/webexa-ai/agents-ia/.env`:
```env
# API
API_HOST=0.0.0.0
API_PORT=8000
API_WORKERS=4

# Database
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=webexa_db
DB_USERNAME=webexa_user
DB_PASSWORD=secure_password

# LLM APIs
OPENAI_API_KEY=sk-...
ANTHROPIC_API_KEY=sk-ant-...

# Redis (if using)
REDIS_URL=redis://localhost:6379/0

# Logging
LOG_LEVEL=info
```

## 🌐 DNS Configuration

After deployment, configure your DNS provider:

```
Type  | Name       | Value
------|------------|----------
A     | webexa     | your-vps-ip
A     | webexa     | your-vps-ip
CNAME | www        | webexa.fr
CNAME | www        | webexa.online
```

## 📊 Monitoring & Logs

### Check Services
```bash
# SSH to VPS
ssh root@your-vps-ip

# Check Nginx
systemctl status nginx
curl -I http://webexa.fr

# Check PHP-FPM
systemctl status php-fpm

# Check Python API
systemctl status webexa-api
curl http://localhost:8000/docs
```

### View Logs
```bash
# Nginx errors
tail -f /var/log/nginx/webexa.fr.error.log
tail -f /var/log/nginx/webexa.online.error.log

# API logs
tail -f /var/log/webexa/api.error.log
tail -f /var/log/webexa/api.log

# System logs
journalctl -u webexa-api -f
```

## 🔒 SSL/HTTPS Setup

After DNS is configured:

```bash
ssh root@your-vps-ip

# Install SSL certificates
certbot --nginx -d webexa.fr -d www.webexa.fr
certbot --nginx -d webexa.online -d www.webexa.online

# Auto-renewal
systemctl enable certbot.timer
```

## 🔄 Updating Your Application

### Update PHP Code
```bash
cd /var/www/webexa
git pull origin main
composer install
systemctl restart php-fpm
```

### Update Python Code
```bash
cd /var/www/webexa-ai
git pull origin main
source venv/bin/activate
pip install -r agents-ia/requirements.txt
systemctl restart webexa-api
```

## 🆘 Troubleshooting

### 1. PHP Not Working
```bash
systemctl restart php-fpm
systemctl status php-fpm
# Check if socket exists: ls -l /run/php/php-fpm.sock
```

### 2. Python API Not Starting
```bash
systemctl status webexa-api
# Check for missing dependencies
cd /var/www/webexa-ai
source venv/bin/activate
python -c "import agents_ia.main"
```

### 3. Domain Not Resolving
```bash
# Check DNS
nslookup webexa.fr
dig webexa.fr

# Check Nginx
nginx -t
systemctl restart nginx
```

### 4. Permission Issues
```bash
chown -R www-data:www-data /var/www/webexa
chown -R www-data:www-data /var/www/webexa-ai
chmod -R 755 /var/www/webexa
chmod -R 755 /var/www/webexa-ai
```

## 📚 File Reference

| File | Purpose |
|------|---------|
| `DEPLOYMENT_GUIDE.md` | Detailed manual deployment steps |
| `quick-deploy.sh` | Automated deployment script (Recommended) |
| `setup-vps.sh` | VPS environment setup script |
| `deploy.sh` | Alternative deployment script |
| `nginx-webexa.fr.conf` | Nginx config for PHP |
| `nginx-webexa.online.conf` | Nginx config for Python |
| `webexa-api.service` | Systemd service for Python API |

## 🎯 Success Indicators

Once deployed, you should see:
- ✅ PHP site loads at https://webexa.fr
- ✅ API documentation at https://webexa.online/docs
- ✅ Both services auto-restart on failure
- ✅ Nginx handles reverse proxy for API
- ✅ SSL certificates active (https)

## 📞 Support

If you encounter issues:
1. Check logs: `tail -f /var/log/nginx/*.error.log`
2. Test connectivity: `curl -v http://domain.com`
3. Verify services: `systemctl status service-name`
4. Check permissions: `ls -la /var/www/webexa*`

---

**Last Updated:** 2026-05-30
**Version:** 1.0.0
