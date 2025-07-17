#!/bin/bash
# System Health Verification after Emergency Fix
# Created: 2025-01-15

echo "🔍 SYSTEM HEALTH CHECK"
echo "======================"
echo ""

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to check status
check_status() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✓${NC} $2"
    else
        echo -e "${RED}✗${NC} $2"
        ERRORS=$((ERRORS + 1))
    fi
}

ERRORS=0

# 1. Check Debug Mode
echo "1️⃣ Security Settings:"
grep -q "APP_DEBUG=false" .env
check_status $? "Debug mode disabled"

grep -q "APP_ENV=production" .env
check_status $? "Production environment set"

# 2. Check File Permissions
echo ""
echo "2️⃣ File Permissions:"
if [ -r .env ] && [ $(stat -c %a .env) = "600" ]; then
    check_status 0 ".env permissions secure (600)"
else
    check_status 1 ".env permissions NOT secure"
fi

# 3. Check Test Files
echo ""
echo "3️⃣ Test Files:"
TEST_FILES=$(find public -name "test-*.php" -o -name "admin-*.php" -o -name "debug-*.php" 2>/dev/null | wc -l)
if [ $TEST_FILES -eq 0 ]; then
    check_status 0 "No test files in public directory"
else
    check_status 1 "$TEST_FILES test files still in public!"
fi

# 4. Check Services
echo ""
echo "4️⃣ Service Status:"

# PHP-FPM
systemctl is-active --quiet php8.3-fpm
check_status $? "PHP-FPM running"

# Nginx
systemctl is-active --quiet nginx
check_status $? "Nginx running"

# MySQL
systemctl is-active --quiet mysql
check_status $? "MySQL running"

# Redis
systemctl is-active --quiet redis-server
check_status $? "Redis running"

# 5. Check Laravel
echo ""
echo "5️⃣ Laravel Status:"

# Artisan command test
php artisan --version > /dev/null 2>&1
check_status $? "Artisan commands working"

# Queue connection
timeout 5 php artisan queue:work --stop-when-empty > /dev/null 2>&1
check_status $? "Queue system responsive"

# 6. Check Web Access
echo ""
echo "6️⃣ Web Endpoints:"

# Admin panel
curl -s -o /dev/null -w "%{http_code}" https://api.askproai.de/admin | grep -q "200\|302"
check_status $? "Admin panel accessible"

# API health
curl -s -o /dev/null -w "%{http_code}" https://api.askproai.de/test | grep -q "200"
check_status $? "API endpoint responsive"

# 7. Check Logs
echo ""
echo "7️⃣ Error Logs:"
RECENT_ERRORS=$(tail -n 100 storage/logs/laravel.log | grep -c "ERROR\|CRITICAL" || echo 0)
if [ $RECENT_ERRORS -eq 0 ]; then
    check_status 0 "No recent errors in logs"
else
    check_status 1 "$RECENT_ERRORS errors found in recent logs"
fi

# 8. Performance Check
echo ""
echo "8️⃣ Performance:"
START_TIME=$(date +%s.%N)
curl -s https://api.askproai.de/test > /dev/null
END_TIME=$(date +%s.%N)
RESPONSE_TIME=$(echo "$END_TIME - $START_TIME" | bc)

if (( $(echo "$RESPONSE_TIME < 2" | bc -l) )); then
    check_status 0 "API response time: ${RESPONSE_TIME}s"
else
    check_status 1 "API response time slow: ${RESPONSE_TIME}s"
fi

# 9. Memory Usage
echo ""
echo "9️⃣ Resource Usage:"
MEMORY_USAGE=$(free -m | awk 'NR==2{printf "%.0f", $3*100/$2}')
if [ $MEMORY_USAGE -lt 80 ]; then
    check_status 0 "Memory usage: ${MEMORY_USAGE}%"
else
    check_status 1 "High memory usage: ${MEMORY_USAGE}%"
fi

# 10. Disk Space
DISK_USAGE=$(df -h / | awk 'NR==2{print $5}' | sed 's/%//')
if [ $DISK_USAGE -lt 80 ]; then
    check_status 0 "Disk usage: ${DISK_USAGE}%"
else
    check_status 1 "High disk usage: ${DISK_USAGE}%"
fi

# Summary
echo ""
echo "======================"
if [ $ERRORS -eq 0 ]; then
    echo -e "${GREEN}✅ SYSTEM HEALTHY${NC}"
    echo "All checks passed!"
else
    echo -e "${RED}⚠️  ISSUES FOUND${NC}"
    echo "$ERRORS problems detected"
    echo ""
    echo "Next steps:"
    echo "1. Check detailed logs: tail -f storage/logs/laravel.log"
    echo "2. Run performance analysis: php analyze-performance-issues.php"
    echo "3. Review security settings"
fi
echo "======================"

exit $ERRORS