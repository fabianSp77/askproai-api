#!/bin/bash
#
# Post-Deployment Cache Refresh Script
# Created: 2025-10-01
# Purpose: Prevent stale cache issues after code deployments
#

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}Post-Deployment Cache Refresh${NC}"
echo -e "${BLUE}════════════════════════════════════════════════════${NC}"
echo ""

# Navigate to project root
cd "$PROJECT_ROOT"

# 1. Clear all caches completely
echo -e "${YELLOW}[1/5] Clearing all caches...${NC}"
php artisan optimize:clear
echo -e "${GREEN}✅ All caches cleared${NC}"
echo ""

# 2. Rebuild config cache
echo -e "${YELLOW}[2/5] Rebuilding config cache...${NC}"
php artisan config:cache
echo -e "${GREEN}✅ Config cached${NC}"
echo ""

# 3. Rebuild route cache
echo -e "${YELLOW}[3/5] Rebuilding route cache...${NC}"
php artisan route:cache
echo -e "${GREEN}✅ Routes cached${NC}"
echo ""

# 4. Rebuild event cache
echo -e "${YELLOW}[4/5] Rebuilding event cache...${NC}"
php artisan event:cache
echo -e "${GREEN}✅ Events cached${NC}"
echo ""

# 5. Graceful PHP-FPM reload
echo -e "${YELLOW}[5/5] Reloading PHP-FPM (zero downtime)...${NC}"
systemctl reload php8.3-fpm

# Wait for reload to complete
sleep 2

# Verify PHP-FPM is running
if systemctl is-active --quiet php8.3-fpm; then
    echo -e "${GREEN}✅ PHP-FPM reloaded successfully${NC}"
else
    echo -e "${RED}❌ WARNING: PHP-FPM may not be running!${NC}"
    exit 1
fi

echo ""
echo -e "${BLUE}════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}✅ Deployment cache refresh completed successfully${NC}"
echo -e "${BLUE}════════════════════════════════════════════════════${NC}"
echo ""

# Optional: Run quick health check
echo -e "${YELLOW}Running quick health check...${NC}"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" https://api.askproai.de/api/health 2>/dev/null || echo "000")

if [ "$HTTP_CODE" = "200" ]; then
    echo -e "${GREEN}✅ API responding: HTTP $HTTP_CODE${NC}"
else
    echo -e "${YELLOW}⚠️  API response: HTTP $HTTP_CODE (may need investigation)${NC}"
fi

echo ""
echo -e "${BLUE}Next steps:${NC}"
echo "  1. Check logs: tail -f storage/logs/laravel.log"
echo "  2. Monitor errors: grep ERROR storage/logs/laravel.log | tail -20"
echo "  3. Test endpoints: curl https://api.askproai.de/api/health/detailed"
echo ""
