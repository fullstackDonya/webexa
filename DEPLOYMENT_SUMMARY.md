# 🎯 WEBEXA DEPLOYMENT SUMMARY

## Your Project Structure

Your Webexa application has **two parts**:

### 1️⃣ **PHP Web Application** → `webexa.fr`
- CRM, ERP, Forms modules
- Dashboard, Analytics, Customer management
- Built with PHP + HTML/CSS/JavaScript
- Located in: `/crm`, `/erp`, `/forms` directories

### 2️⃣ **Python AI API** → `webexa.online`
- FastAPI with Uvicorn
- AI Agents for email analysis, lead scoring, automation
- Requires Python 3.8+
- Located in: `/agents-ia` directory

---

## 🏗️ VPS Structure After Deployment

```
your-vps:/var/www/

├── html/              ← Existing site
├── siteo/             ← Existing site
│
├── webexa/            ← NEW: PHP Application
│   ├── crm/
│   ├── erp/
│   ├── forms/
│   ├── assets/
│   ├── vendor/        (auto-installed)
│   ├── index.php
│   ├── .env           (you create)
│   └── composer.json
│
└── webexa-ai/         ← NEW: Python API
    ├── agents-ia/
    │   ├── main.py
    │   ├── config.py
    │   ├── database.py
    │   ├── llm_service.py
    │   └── agents/
    ├── venv/          (auto-created)
    └── .env           (you create)
```

---

## 🚀 DEPLOYMENT STEPS

### **BEFORE STARTING**
- [ ] Have VPS IP address ready
- [ ] Have SSH access to VPS (root or sudo user)
- [ ] Domains `webexa.fr` and `webexa.online` registered
- [ ] Database credentials (MySQL/Oracle)
- [ ] API keys (OpenAI, Anthropic, etc.)

### **STEP 1: Run Automated Deployment** (5 minutes)
```bash
# On your local machine (where Webexa is located)
chmod +x quick-deploy.sh
./quick-deploy.sh
```

The script will:
- ✅ Connect to your VPS
- ✅ Install all required packages
- ✅ Create directories
- ✅ Install PHP dependencies
- ✅ Install Python dependencies
- ✅ Configure Nginx for both domains
- ✅ Setup systemd service for Python API

### **STEP 2: Configure Environment Variables** (5 minutes)
```bash
# SSH to your VPS
ssh root@your-vps-ip

# Edit PHP configuration
nano /var/www/webexa/.env

# Edit Python configuration
nano /var/www/webexa-ai/agents-ia/.env
```

### **STEP 3: Configure DNS** (Varies by registrar)
Update your domain provider:
```
webexa.fr      → A record → your-vps-ip
webexa.online  → A record → your-vps-ip
```

### **STEP 4: Enable HTTPS** (Optional but recommended)
```bash
ssh root@your-vps-ip
certbot --nginx
```

---

## 📋 What Gets Installed

| Component | Version | Purpose |
|-----------|---------|---------|
| Nginx | Latest | Web server + reverse proxy |
| PHP-FPM | 8.1+ | PHP execution |
| Python | 3.8+ | Python runtime |
| Composer | Latest | PHP dependencies |
| Gunicorn | Latest | Python app server |
| FastAPI | 0.109.0 | API framework |
| SQLAlchemy | 2.0.25 | Database ORM |
| OpenAI | 1.10.0 | LLM integration |

---

## 🔧 Configuration Files to Create

### `/var/www/webexa/.env`
```env
# Essential variables
DB_HOST=localhost
DB_USERNAME=webexa_user
DB_PASSWORD=your_secure_password
DB_DATABASE=webexa_db

OPENAI_API_KEY=sk-...
ANTHROPIC_API_KEY=sk-ant-...
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...

APP_URL=https://webexa.fr
APP_ENV=production
```

### `/var/www/webexa-ai/agents-ia/.env`
```env
# Database
DB_HOST=localhost
DB_USERNAME=webexa_user
DB_PASSWORD=your_secure_password
DB_DATABASE=webexa_db

# APIs
OPENAI_API_KEY=sk-...
ANTHROPIC_API_KEY=sk-ant-...

# Server
API_HOST=127.0.0.1
API_PORT=8000
API_WORKERS=4
```

---

## ✅ Verification Checklist

After deployment runs, verify everything:

```bash
# On your VPS
ssh root@your-vps-ip

# Test PHP website
curl -I http://webexa.fr

# Test Python API
curl http://webexa.online/docs

# Check services
systemctl status nginx
systemctl status php-fpm
systemctl status webexa-api

# View logs if needed
tail -f /var/log/nginx/webexa.*.error.log
tail -f /var/log/webexa/api.error.log
```

---

## 📊 Expected Results

### ✅ Success Indicators
- [ ] Nginx running on port 80/443
- [ ] PHP-FPM socket active
- [ ] Python API listening on 127.0.0.1:8000
- [ ] Domain resolves to your VPS IP
- [ ] https://webexa.fr loads (PHP app)
- [ ] https://webexa.online/docs accessible (FastAPI docs)
- [ ] Both services auto-restart on failure

---

## 🎛️ Managing Your Application

### Daily Commands

```bash
# Check everything is running
systemctl status nginx php-fpm webexa-api

# View recent errors
tail -f /var/log/nginx/webexa.fr.error.log

# Restart Python API (if needed)
systemctl restart webexa-api

# View API logs
journalctl -u webexa-api -n 50 -f
```

### Updating Code

```bash
# Update PHP code
cd /var/www/webexa
git pull
composer install --no-dev
systemctl restart php-fpm

# Update Python code
cd /var/www/webexa-ai
git pull
source venv/bin/activate
pip install -r agents-ia/requirements.txt
systemctl restart webexa-api
```

---

## 🐛 Common Issues & Solutions

| Issue | Solution |
|-------|----------|
| **PHP shows 404** | Check index.php routing, restart php-fpm |
| **API won't start** | Check .env file, verify Python dependencies |
| **Domain not resolving** | Wait 24h for DNS, or check DNS settings |
| **Permission denied** | Run: `chown -R www-data:www-data /var/www/webexa*` |
| **High RAM usage** | Reduce workers in gunicorn (4 → 2) |

---

## 📁 Files Created in Your Project

```
/Applications/MAMP/htdocs/webexa/

├── DEPLOYMENT_README.md      ← Start here
├── DEPLOYMENT_GUIDE.md       ← Detailed instructions
├── CHEAT_SHEET.md            ← Command reference
├── quick-deploy.sh           ← Run this!
├── setup-vps.sh              ← VPS setup
├── deploy.sh                 ← Alternative deployment
├── verify-deployment.sh      ← Post-deployment check
├── nginx-webexa.fr.conf      ← PHP domain config
├── nginx-webexa.online.conf  ← API domain config
└── webexa-api.service        ← Python service config
```

---

## 🎯 QUICK REFERENCE: First Time Setup

### On Your Local Machine
```bash
cd /Applications/MAMP/htdocs/webexa
chmod +x quick-deploy.sh
./quick-deploy.sh
# Follow the prompts (IP, username, domains)
```

### On Your VPS (after deployment)
```bash
# 1. Create configuration files
nano /var/www/webexa/.env
nano /var/www/webexa-ai/agents-ia/.env

# 2. Start services
systemctl start webexa-api
systemctl restart nginx

# 3. Verify
curl http://webexa.fr
curl http://webexa.online/docs
```

### At Your DNS Provider
```
Add A records:
webexa.fr → your-vps-ip
webexa.online → your-vps-ip
```

---

## 📞 Need Help?

Check these files for detailed info:
- **Getting started?** → Read `DEPLOYMENT_README.md`
- **Step-by-step guide?** → Read `DEPLOYMENT_GUIDE.md`
- **Quick commands?** → Read `CHEAT_SHEET.md`
- **Troubleshooting?** → Run `./verify-deployment.sh`

---

## 🎉 You're Ready!

Your Webexa application is ready for deployment! 

**Next Action:** Run `./quick-deploy.sh` from your local machine.

Good luck! 🚀

---

**Created:** May 30, 2026
**Version:** 1.0.0
**For:** Webexa CRM/ERP + AI API Platform
