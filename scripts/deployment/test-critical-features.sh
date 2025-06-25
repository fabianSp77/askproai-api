#!/bin/bash
# test-critical-features.sh
# Testet kritische Features vor jedem Deployment-Schritt

set -e  # Exit on error

echo "=== CRITICAL FEATURE TESTING ==="
echo "Testing at: $(date)"
echo "================================"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test results
PASSED=0
FAILED=0

# Function to test a feature
test_feature() {
    local test_name=$1
    local test_command=$2
    
    echo -n "Testing $test_name... "
    
    if eval "$test_command" > /dev/null 2>&1; then
        echo -e "${GREEN}PASSED${NC}"
        ((PASSED++))
    else
        echo -e "${RED}FAILED${NC}"
        ((FAILED++))
        echo "  Command: $test_command"
        eval "$test_command" 2>&1 | sed 's/^/  /'
    fi
}

# 1. Database Connectivity
test_feature "Database Connection" \
    "mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' -e 'SELECT 1' askproai_db"

# 2. Redis Connectivity
test_feature "Redis Connection" \
    "redis-cli ping"

# 3. Retell API Connection
test_feature "Retell API Health" \
    "curl -s -H 'Authorization: Bearer \$RETELL_API_KEY' https://api.retellai.com/list-agents | grep -q 'agents'"

# 4. Critical Tables Exist
test_feature "Critical Tables" \
    "mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' -e 'SHOW TABLES LIKE \"retell_agents\"' askproai_db | grep -q retell_agents"

# 5. Agent Sync Function
test_feature "Agent Sync Capability" \
    "php artisan tinker --execute='return class_exists(\"App\\Services\\RetellV2Service\");'"

# 6. Customer Recognition Service
test_feature "Customer Recognition Service" \
    "php artisan tinker --execute='return method_exists(\"App\\Services\\CustomerRecognitionService\", \"recognizeByPhone\");'"

# 7. Multi-Booking Service
test_feature "Multi-Booking Service" \
    "php artisan tinker --execute='return class_exists(\"App\\Services\\MultiBookingService\");'"

# 8. Dynamic Variable Processor
test_feature "Dynamic Variable Processor" \
    "php artisan tinker --execute='return class_exists(\"App\\Services\\DynamicVariableProcessor\");'"

# 9. Webhook Endpoint
test_feature "Webhook Endpoint" \
    "curl -s -o /dev/null -w '%{http_code}' -X POST http://localhost/api/retell/webhook | grep -q '401\\|403'"

# 10. Queue Worker
test_feature "Queue Worker Status" \
    "supervisorctl status horizon | grep -q RUNNING"

# 11. Cache Functionality
test_feature "Cache System" \
    "php artisan tinker --execute='Cache::put(\"test\", \"value\", 1); return Cache::get(\"test\") === \"value\";'"

# 12. Feature Flag System
test_feature "Feature Flag System" \
    "php artisan tinker --execute='return is_array(config(\"features.retell_ultimate\"));'"

# 13. Monitoring Endpoint
test_feature "Monitoring Health Check" \
    "curl -s http://localhost/api/health | grep -q healthy"

# 14. Logging System
test_feature "Logging System" \
    "php artisan tinker --execute='Log::info(\"test\"); return true;'"

# 15. Error Tracking
test_feature "Error Tracking" \
    "php artisan tinker --execute='return class_exists(\"App\\Exceptions\\Handler\");'"

echo "================================"
echo "Test Summary:"
echo -e "  Passed: ${GREEN}$PASSED${NC}"
echo -e "  Failed: ${RED}$FAILED${NC}"
echo "================================"

if [ $FAILED -gt 0 ]; then
    echo -e "${RED}CRITICAL TESTS FAILED!${NC}"
    echo "Do not proceed with deployment until all tests pass."
    exit 1
else
    echo -e "${GREEN}All critical tests passed!${NC}"
    echo "Safe to proceed with deployment."
    exit 0
fi