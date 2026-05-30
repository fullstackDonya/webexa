#!/bin/bash
# Post-Deployment Verification Script
# Run this after deployment to verify everything is working

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🔍 WEBEXA POST-DEPLOYMENT VERIFICATION${NC}"
echo "=========================================="
echo ""

FAILED=0
PASSED=0

# Test function
test_service() {
    local name=$1
    local command=$2
    
    if eval "$command" > /dev/null 2>&1; then
        echo -e "${GREEN}✅${NC} $name"
        ((PASSED++))
    else
        echo -e "${RED}❌${NC} $name"
        ((FAILED++))
    fi
}

# 1. File Structure
echo -e "${YELLOW}📁 Checking file structure...${NC}"
test_service "PHP directory exists" "[ -d /var/www/webexa ]"
test_service "Python directory exists" "[ -d /var/www/webexa-ai ]"
test_service "agents-ia directory exists" "[ -d /var/www/webexa-ai/agents-ia ]"
test_service "PHP index.php exists" "[ -f /var/www/webexa/index.php ]"
test_service "Python main.py exists" "[ -f /var/www/webexa-ai/agents-ia/main.py ]"

echo ""

# 2. Services
echo -e "${YELLOW}⚙️  Checking services...${NC}"
test_service "Nginx is running" "systemctl is-active --quiet nginx"
test_service "PHP-FPM is running" "systemctl is-active --quiet php-fpm"
test_service "Python API service exists" "[ -f /etc/systemd/system/webexa-api.service ]"

echo ""

# 3. Nginx Configuration
echo -e "${YELLOW}🌐 Checking Nginx configuration...${NC}"
test_service "Nginx syntax is valid" "nginx -t 2>/dev/null"
test_service "webexa.fr config exists" "[ -f /etc/nginx/sites-available/webexa.fr ]"
test_service "webexa.online config exists" "[ -f /etc/nginx/sites-available/webexa.online ]"
test_service "webexa.fr is enabled" "[ -L /etc/nginx/sites-enabled/webexa.fr ]"
test_service "webexa.online is enabled" "[ -L /etc/nginx/sites-enabled/webexa.online ]"

echo ""

# 4. Permissions
echo -e "${YELLOW}🔐 Checking permissions...${NC}"
test_service "PHP directory owned by www-data" "[ $(stat -c %U /var/www/webexa) = 'www-data' ]"
test_service "Python directory owned by www-data" "[ $(stat -c %U /var/www/webexa-ai) = 'www-data' ]"

echo ""

# 5. Environment Files
echo -e "${YELLOW}🔧 Checking configuration...${NC}"
if [ -f /var/www/webexa/.env ]; then
    echo -e "${GREEN}✅${NC} PHP .env file exists"
    ((PASSED++))
else
    echo -e "${RED}❌${NC} PHP .env file missing (create before running)"
    ((FAILED++))
fi

if [ -f /var/www/webexa-ai/agents-ia/.env ]; then
    echo -e "${GREEN}✅${NC} Python .env file exists"
    ((PASSED++))
else
    echo -e "${RED}❌${NC} Python .env file missing (create before running)"
    ((FAILED++))
fi

echo ""

# 6. Ports and Connectivity
echo -e "${YELLOW}📡 Checking connectivity...${NC}"

# Check if port 80 is listening
if netstat -tlnp 2>/dev/null | grep -q ":80 "; then
    echo -e "${GREEN}✅${NC} Port 80 is listening"
    ((PASSED++))
else
    echo -e "${RED}❌${NC} Port 80 is not listening"
    ((FAILED++))
fi

# Check if port 8000 is listening
if netstat -tlnp 2>/dev/null | grep -q ":8000 "; then
    echo -e "${GREEN}✅${NC} Port 8000 is listening (Python API)"
    ((PASSED++))
else
    echo -e "${YELLOW}⚠️ ${NC} Port 8000 not listening (API may need to be started)"
    ((FAILED++))
fi

echo ""

# 7. PHP FPM Socket
echo -e "${YELLOW}🔌 Checking PHP-FPM socket...${NC}"
if [ -S /run/php/php-fpm.sock ]; then
    echo -e "${GREEN}✅${NC} PHP-FPM socket exists"
    ((PASSED++))
else
    echo -e "${RED}❌${NC} PHP-FPM socket missing"
    ((FAILED++))
fi

echo ""

# 8. System Dependencies
echo -e "${YELLOW}📦 Checking dependencies...${NC}"
test_service "PHP is installed" "which php"
test_service "Python3 is installed" "which python3"
test_service "Composer is installed" "which composer"
test_service "Git is installed" "which git"
test_service "Curl is installed" "which curl"

echo ""

# 9. Directory Permissions
echo -e "${YELLOW}📂 Checking directory permissions...${NC}"

PHP_PERMS=$(stat -c %a /var/www/webexa)
if [ "$PHP_PERMS" = "755" ] || [ "$PHP_PERMS" = "750" ]; then
    echo -e "${GREEN}✅${NC} PHP directory permissions: $PHP_PERMS"
    ((PASSED++))
else
    echo -e "${YELLOW}⚠️ ${NC} PHP directory permissions: $PHP_PERMS (might need adjustment)"
fi

PYTHON_PERMS=$(stat -c %a /var/www/webexa-ai)
if [ "$PYTHON_PERMS" = "755" ] || [ "$PYTHON_PERMS" = "750" ]; then
    echo -e "${GREEN}✅${NC} Python directory permissions: $PYTHON_PERMS"
    ((PASSED++))
else
    echo -e "${YELLOW}⚠️ ${NC} Python directory permissions: $PYTHON_PERMS (might need adjustment)"
fi

echo ""

# 10. Log Files
echo -e "${YELLOW}📊 Checking log files...${NC}"
test_service "Nginx log directory exists" "[ -d /var/log/nginx ]"
test_service "Webexa log directory exists" "[ -d /var/log/webexa ]"

echo ""
echo "=========================================="
echo ""
echo -e "${BLUE}📈 Summary${NC}"
echo -e "${GREEN}Passed: $PASSED${NC}"
echo -e "${RED}Failed: $FAILED${NC}"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}✅ All checks passed!${NC}"
    echo ""
    echo "Next steps:"
    echo "1. Create .env files:"
    echo "   nano /var/www/webexa/.env"
    echo "   nano /var/www/webexa-ai/agents-ia/.env"
    echo ""
    echo "2. Start the Python API:"
    echo "   systemctl start webexa-api"
    echo "   systemctl status webexa-api"
    echo ""
    echo "3. Test the services:"
    echo "   curl -I http://webexa.fr"
    echo "   curl http://webexa.online/docs"
    echo ""
    echo "4. Configure DNS if not done yet"
    exit 0
else
    echo -e "${RED}⚠️ Some checks failed. Please review the errors above.${NC}"
    echo ""
    echo "Common issues:"
    echo "- Services not running: systemctl start service-name"
    echo "- Permission denied: chown -R www-data:www-data /var/www/webexa*"
    echo "- Nginx errors: nginx -t && systemctl restart nginx"
    exit 1
fi
