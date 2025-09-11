#!/bin/bash

# Comprehensive View Cache Fix Validation Script
# This script validates that all view cache fixes are working correctly

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo "========================================="
echo "View Cache Fix Validation"
echo "========================================="
echo ""

ERRORS=0
WARNINGS=0

# Test 1: Check middleware is enabled
echo -n "1. AutoFixViewCache middleware enabled: "
if grep -q "App\\\Http\\\Middleware\\\AutoFixViewCache::class," /var/www/api-gateway/bootstrap/app.php && ! grep -q "//.*App\\\Http\\\Middleware\\\AutoFixViewCache::class," /var/www/api-gateway/bootstrap/app.php; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${RED}✗${NC}"
    ERRORS=$((ERRORS + 1))
fi

# Test 2: Check view cache directory exists and is writable
echo -n "2. View cache directory writable: "
if [ -w /var/www/api-gateway/storage/framework/views ]; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${RED}✗${NC}"
    ERRORS=$((ERRORS + 1))
fi

# Test 3: Check circuit breaker implementation
echo -n "3. Circuit breaker implemented: "
if grep -q "staticErrorResponse" /var/www/api-gateway/app/Http/Middleware/AutoFixViewCache.php; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${RED}✗${NC}"
    ERRORS=$((ERRORS + 1))
fi

# Test 4: Check ViewCacheService has Redis locks
echo -n "4. ViewCacheService has Redis locks: "
if grep -q "REBUILD_LOCK_KEY" /var/www/api-gateway/app/Services/ViewCacheService.php; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${RED}✗${NC}"
    ERRORS=$((ERRORS + 1))
fi

# Test 5: Check scheduled tasks
echo -n "5. Scheduled health checks configured: "
if grep -q "view:health-check --fix" /var/www/api-gateway/app/Console/Kernel.php; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${YELLOW}⚠${NC}"
    WARNINGS=$((WARNINGS + 1))
fi

# Test 6: Check health check command exists
echo -n "6. Health check command available: "
if php artisan list | grep -q "view:health-check"; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${RED}✗${NC}"
    ERRORS=$((ERRORS + 1))
fi

# Test 7: Check compiled views exist
echo -n "7. Compiled views exist: "
VIEW_COUNT=$(find /var/www/api-gateway/storage/framework/views -name "*.php" 2>/dev/null | wc -l)
if [ "$VIEW_COUNT" -gt 0 ]; then
    echo -e "${GREEN}✓ ($VIEW_COUNT views)${NC}"
else
    echo -e "${YELLOW}⚠ (No compiled views)${NC}"
    WARNINGS=$((WARNINGS + 1))
fi

# Test 8: Check Filament caches
echo -n "8. Filament caches exist: "
if [ -f /var/www/api-gateway/bootstrap/cache/filament/panels/admin.php ]; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${YELLOW}⚠${NC}"
    WARNINGS=$((WARNINGS + 1))
fi

# Test 9: Check for recent errors
echo -n "9. No recent view cache errors: "
RECENT_ERRORS=$(grep -c "filemtime(): stat failed" /var/www/api-gateway/storage/logs/laravel.log 2>/dev/null | tail -100 | wc -l)
if [ "$RECENT_ERRORS" -eq 0 ]; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${YELLOW}⚠ ($RECENT_ERRORS errors in last 100 lines)${NC}"
    WARNINGS=$((WARNINGS + 1))
fi

# Test 10: Test enhanced call view endpoint
echo -n "10. Enhanced call view accessible: "
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" https://api.askproai.de/admin/enhanced-calls/3)
if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "302" ]; then
    echo -e "${GREEN}✓ (HTTP $HTTP_CODE)${NC}"
else
    echo -e "${YELLOW}⚠ (HTTP $HTTP_CODE)${NC}"
    WARNINGS=$((WARNINGS + 1))
fi

echo ""
echo "========================================="
echo "Validation Results"
echo "========================================="

if [ $ERRORS -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    echo -e "${GREEN}✅ All tests passed!${NC}"
    echo "The view cache fix is working perfectly."
elif [ $ERRORS -eq 0 ]; then
    echo -e "${YELLOW}⚠ Validation completed with $WARNINGS warning(s)${NC}"
    echo "The system is functional but could be optimized."
else
    echo -e "${RED}❌ Validation failed with $ERRORS error(s) and $WARNINGS warning(s)${NC}"
    echo "Critical issues detected that need immediate attention."
fi

echo ""
echo "Run 'php artisan view:health-check --fix' to attempt automatic fixes."

exit $ERRORS