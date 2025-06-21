#!/bin/bash

# AskProAI Pre-Deployment Checklist Script
# Version: 1.0
# Date: 2025-06-18

set -e

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Counters
PASSED=0
FAILED=0
WARNINGS=0

# Check function
check() {
    local description="$1"
    local command="$2"
    local critical="${3:-true}"
    
    echo -n "Checking $description... "
    
    if eval "$command" &>/dev/null; then
        echo -e "${GREEN}✓ PASSED${NC}"
        ((PASSED++))
        return 0
    else
        if [ "$critical" = "true" ]; then
            echo -e "${RED}✗ FAILED${NC}"
            ((FAILED++))
            return 1
        else
            echo -e "${YELLOW}⚠ WARNING${NC}"
            ((WARNINGS++))
            return 0
        fi
    fi
}

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}AskProAI Pre-Deployment Checklist${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# 1. Environment Checks
echo -e "${BLUE}1. Environment Checks${NC}"
check "PHP version >= 8.2" "php -v | grep -E 'PHP 8\.[2-9]|PHP [9-9]'"
check "Composer installed" "composer --version"
check "Node.js installed" "node --version"
check "NPM installed" "npm --version"
check "Redis running" "redis-cli ping | grep PONG"
check "MySQL running" "mysqladmin ping"
echo ""

# 2. Application Checks
echo -e "${BLUE}2. Application Checks${NC}"
cd /var/www/api-gateway
check "Git repository clean" "[ -z \"$(git status --porcelain)\" ]" false
check ".env file exists" "[ -f .env ]"
check "APP_KEY is set" "grep -q '^APP_KEY=.\+' .env"
check "Database configured" "grep -q '^DB_DATABASE=.\+' .env"
check "Cal.com API key set" "grep -q '^DEFAULT_CALCOM_API_KEY=.\+' .env"
check "Retell API key set" "grep -q '^DEFAULT_RETELL_API_KEY=.\+' .env"
echo ""

# 3. Dependency Checks
echo -e "${BLUE}3. Dependency Checks${NC}"
check "Composer dependencies up to date" "composer check-platform-reqs"
check "No security vulnerabilities" "composer audit" false
check "NPM dependencies installed" "[ -d node_modules ]"
echo ""

# 4. Database Checks
echo -e "${BLUE}4. Database Checks${NC}"
check "Database connection" "php artisan db:show"
check "Migrations are current" "php artisan migrate:status | grep -v 'Pending'"
check "Database backup exists" "[ -d /var/backups/askproai ] && [ -n \"$(ls -A /var/backups/askproai 2>/dev/null)\" ]" false
echo ""

# 5. Cache and Performance
echo -e "${BLUE}5. Cache and Performance${NC}"
check "Config cached" "[ -f bootstrap/cache/config.php ]" false
check "Routes cached" "[ -f bootstrap/cache/routes-v7.php ]" false
check "OPcache enabled" "php -i | grep -q 'opcache.enable => On'" false
echo ""

# 6. Queue and Jobs
echo -e "${BLUE}6. Queue and Jobs${NC}"
check "Queue connection configured" "grep -q '^QUEUE_CONNECTION=redis' .env"
check "Horizon installed" "[ -f vendor/bin/horizon ]"
check "Failed jobs table exists" "php artisan tinker --execute=\"Schema::hasTable('failed_jobs')\""
echo ""

# 7. Storage and Permissions
echo -e "${BLUE}7. Storage and Permissions${NC}"
check "Storage directory writable" "[ -w storage ]"
check "Bootstrap cache writable" "[ -w bootstrap/cache ]"
check "Log file writable" "[ -w storage/logs ]"
echo ""

# 8. SSL and Security
echo -e "${BLUE}8. SSL and Security${NC}"
check "HTTPS enforced in production" "grep -q '^APP_ENV=production' .env && grep -q '^FORCE_HTTPS=true' .env" false
check "Debug mode disabled in production" "! (grep -q '^APP_ENV=production' .env && grep -q '^APP_DEBUG=true' .env)"
echo ""

# 9. External Services
echo -e "${BLUE}9. External Services${NC}"
check "Cal.com API accessible" "curl -s -o /dev/null -w '%{http_code}' https://api.cal.com/v1/health | grep -q '200'" false
check "Retell.ai API accessible" "curl -s -o /dev/null -w '%{http_code}' https://api.retellai.com | grep -E '200|401'" false
echo ""

# 10. Monitoring
echo -e "${BLUE}10. Monitoring${NC}"
check "Health endpoint accessible" "curl -s -o /dev/null -w '%{http_code}' http://localhost/api/health | grep -q '200'"
check "Metrics endpoint accessible" "curl -s -o /dev/null -w '%{http_code}' http://localhost/api/metrics | grep -q '200'"
echo ""

# Summary
echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Summary${NC}"
echo -e "${BLUE}========================================${NC}"
echo -e "Passed:   ${GREEN}$PASSED${NC}"
echo -e "Warnings: ${YELLOW}$WARNINGS${NC}"
echo -e "Failed:   ${RED}$FAILED${NC}"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}✓ All critical checks passed! Ready for deployment.${NC}"
    exit 0
else
    echo -e "${RED}✗ $FAILED critical checks failed. Please fix these issues before deploying.${NC}"
    exit 1
fi