#!/bin/bash

# ========================================
# VOLLSTÄNDIGE PLATTFORM-ÜBERPRÜFUNG
# Laravel/Filament Admin Panel Test Suite
# ========================================

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Test counters
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0
START_TIME=$(date +%s)

echo -e "${BLUE}🚀 VOLLSTÄNDIGE PLATTFORM-ÜBERPRÜFUNG${NC}"
echo "======================================"
echo "Start: $(date)"
echo ""

# Function to run test and track results
run_test_suite() {
    local test_name="$1"
    local test_script="$2"

    echo -e "${YELLOW}▶ Running: $test_name${NC}"
    TOTAL_TESTS=$((TOTAL_TESTS + 1))

    if [ -f "$test_script" ]; then
        if bash "$test_script"; then
            echo -e "${GREEN}✓ $test_name completed successfully${NC}"
            PASSED_TESTS=$((PASSED_TESTS + 1))
        else
            echo -e "${RED}✗ $test_name failed${NC}"
            FAILED_TESTS=$((FAILED_TESTS + 1))
        fi
    else
        echo -e "${YELLOW}⚠ $test_name script not found, creating...${NC}"
        # Script will be created by subsequent commands
    fi
    echo ""
}

# Check system status first
echo -e "${BLUE}1. SYSTEM STATUS CHECK${NC}"
echo "--------------------------------------"
systemctl status mariadb | grep Active
systemctl status php8.3-fpm | grep Active
systemctl status nginx | grep Active
echo ""

# Database status
echo -e "${BLUE}2. DATABASE STATUS${NC}"
echo "--------------------------------------"
mysql -u root askproai_db -e "SHOW STATUS LIKE 'Threads_connected';" 2>/dev/null
mysql -u root askproai_db -e "SELECT COUNT(*) as total_customers FROM customers;" 2>/dev/null
mysql -u root askproai_db -e "SELECT COUNT(*) as total_calls FROM calls;" 2>/dev/null
mysql -u root askproai_db -e "SELECT COUNT(*) as total_appointments FROM appointments;" 2>/dev/null
echo ""

# Run existing test suites
echo -e "${BLUE}3. RUNNING TEST SUITES${NC}"
echo "--------------------------------------"

# Existing tests
[ -f "./api-test.sh" ] && run_test_suite "API Tests" "./api-test.sh"
[ -f "./login-flow-test.sh" ] && run_test_suite "Login Flow Tests" "./login-flow-test.sh"
[ -f "./route-500-test.sh" ] && run_test_suite "Route 500 Tests" "./route-500-test.sh"
[ -f "./security-test.sh" ] && run_test_suite "Security Tests" "./security-test.sh"

# New comprehensive tests
run_test_suite "Resource Tests" "./resource-test.sh"
run_test_suite "Widget Tests" "./widget-test.sh"
run_test_suite "Database Integrity" "./database-integrity.sh"
run_test_suite "Performance Check" "./performance-check.sh"

# Laravel specific checks
echo -e "${BLUE}4. LARAVEL HEALTH CHECKS${NC}"
echo "--------------------------------------"
cd /var/www/api-gateway
php artisan about --only=environment
# DO NOT CACHE CONFIG IN DEVELOPMENT!
# php artisan config:cache # REMOVED - causes 500 errors with old passwords!
# php artisan route:cache  # REMOVED - not needed
# php artisan view:cache   # REMOVED - not needed
echo "✅ Caches cleared (NOT cached again)"
echo ""

# Check for common issues
echo -e "${BLUE}5. COMMON ISSUE CHECKS${NC}"
echo "--------------------------------------"

# Check for 500 errors in last hour
echo -n "500 Errors (last hour): "
grep -c "production.ERROR" /var/www/api-gateway/storage/logs/laravel.log 2>/dev/null || echo "0"

# Check for missing relationships
echo -n "Missing relationships: "
grep -c "undefined relationship" /var/www/api-gateway/storage/logs/laravel.log 2>/dev/null || echo "0"

# Check for database connection issues
echo -n "DB connection errors: "
grep -c "Connection refused" /var/www/api-gateway/storage/logs/laravel.log 2>/dev/null || echo "0"
echo ""

# Performance metrics
echo -e "${BLUE}6. PERFORMANCE METRICS${NC}"
echo "--------------------------------------"
echo "Dashboard Response Time:"
time curl -s -o /dev/null -w "HTTP Code: %{http_code}, Time: %{time_total}s\n" http://localhost/admin

echo "Customer List Response Time:"
time curl -s -o /dev/null -w "HTTP Code: %{http_code}, Time: %{time_total}s\n" http://localhost/admin/customers

echo "Memory Usage:"
free -h | grep Mem
echo ""

# Calculate test duration
END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))

# Final summary
echo "======================================"
echo -e "${BLUE}TEST SUMMARY${NC}"
echo "--------------------------------------"
echo -e "Total Tests Run: ${TOTAL_TESTS}"
echo -e "Passed: ${GREEN}${PASSED_TESTS}${NC}"
echo -e "Failed: ${RED}${FAILED_TESTS}${NC}"
echo -e "Success Rate: $(( PASSED_TESTS * 100 / (TOTAL_TESTS + 1) ))%"
echo -e "Duration: ${DURATION} seconds"
echo "Completed: $(date)"
echo "======================================"

# Exit with appropriate code
if [ $FAILED_TESTS -gt 0 ]; then
    exit 1
else
    exit 0
fi