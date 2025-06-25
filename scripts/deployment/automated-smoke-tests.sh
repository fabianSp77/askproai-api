#!/bin/bash
# automated-smoke-tests.sh
# Automatisierte Smoke Tests nach jedem Deployment

set -e

echo "🔥 Running Automated Smoke Tests"
echo "================================"

# Configuration
API_BASE="http://localhost"
AUTH_TOKEN="${API_AUTH_TOKEN:-test-token}"
PHONE_NUMBER="+4915912345678"
AGENT_ID="${TEST_AGENT_ID:-1}"

# Test results
TESTS_PASSED=0
TESTS_FAILED=0
FAILED_TESTS=()

# Helper function for API calls
api_call() {
    local method=$1
    local endpoint=$2
    local data=$3
    
    if [ -z "$data" ]; then
        curl -s -X "$method" \
             -H "Authorization: Bearer $AUTH_TOKEN" \
             -H "Content-Type: application/json" \
             "$API_BASE$endpoint"
    else
        curl -s -X "$method" \
             -H "Authorization: Bearer $AUTH_TOKEN" \
             -H "Content-Type: application/json" \
             -d "$data" \
             "$API_BASE$endpoint"
    fi
}

# Test function
run_test() {
    local test_name=$1
    local test_function=$2
    
    echo -n "Testing: $test_name... "
    
    if $test_function > /dev/null 2>&1; then
        echo "✅ PASSED"
        ((TESTS_PASSED++))
    else
        echo "❌ FAILED"
        ((TESTS_FAILED++))
        FAILED_TESTS+=("$test_name")
    fi
}

# Test 1: Health Check
test_health_check() {
    response=$(api_call GET /api/health)
    echo "$response" | grep -q "healthy"
}

# Test 2: List Agents
test_list_agents() {
    response=$(api_call GET /api/retell/agents)
    echo "$response" | grep -q "data"
}

# Test 3: Get Single Agent
test_get_agent() {
    response=$(api_call GET "/api/retell/agents/$AGENT_ID")
    echo "$response" | grep -q "agent_name"
}

# Test 4: Update Agent (dry run)
test_update_agent() {
    data='{
        "agent_name": "Test Agent Update",
        "voice_id": "11labs-Rachel",
        "language": "de"
    }'
    
    response=$(api_call PUT "/api/retell/agents/$AGENT_ID/dry-run" "$data")
    echo "$response" | grep -q "success"
}

# Test 5: Customer Recognition
test_customer_recognition() {
    data="{\"phone\": \"$PHONE_NUMBER\"}"
    response=$(api_call POST /api/customers/recognize "$data")
    
    # Should return either customer data or not found
    echo "$response" | grep -qE "(customer_id|not_found)"
}

# Test 6: Dynamic Variables
test_dynamic_variables() {
    response=$(api_call GET /api/company/dynamic-variables)
    echo "$response" | grep -q "variables"
}

# Test 7: Webhook Endpoint (should require signature)
test_webhook_security() {
    response=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$API_BASE/api/retell/webhook")
    [ "$response" = "401" ] || [ "$response" = "403" ]
}

# Test 8: Agent Sync Status
test_sync_status() {
    response=$(api_call GET /api/retell/agents/sync-status)
    echo "$response" | grep -qE "(synced|pending|failed)"
}

# Test 9: Custom Functions List
test_custom_functions() {
    response=$(api_call GET "/api/retell/agents/$AGENT_ID/functions")
    echo "$response" | grep -q "functions"
}

# Test 10: Knowledge Base
test_knowledge_base() {
    response=$(api_call GET "/api/retell/agents/$AGENT_ID/knowledge-base")
    # Should return knowledge base or empty array
    echo "$response" | grep -qE "(\[\]|knowledge_base)"
}

# Test 11: Database Connection
test_database() {
    php -r "
        require '/var/www/api-gateway/vendor/autoload.php';
        \$app = require '/var/www/api-gateway/bootstrap/app.php';
        \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        
        try {
            \DB::select('SELECT 1');
            exit(0);
        } catch (\Exception \$e) {
            exit(1);
        }
    "
}

# Test 12: Redis Connection
test_redis() {
    redis-cli ping | grep -q PONG
}

# Test 13: Queue System
test_queue() {
    supervisorctl status horizon | grep -q RUNNING
}

# Test 14: Feature Flags
test_feature_flags() {
    php -r "
        require '/var/www/api-gateway/vendor/autoload.php';
        \$app = require '/var/www/api-gateway/bootstrap/app.php';
        \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        
        \$features = config('features.retell_ultimate');
        exit(is_array(\$features) ? 0 : 1);
    "
}

# Test 15: Logging System
test_logging() {
    php -r "
        require '/var/www/api-gateway/vendor/autoload.php';
        \$app = require '/var/www/api-gateway/bootstrap/app.php';
        \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        
        try {
            \Log::info('Smoke test log entry');
            exit(0);
        } catch (\Exception \$e) {
            exit(1);
        }
    "
}

# Run all tests
echo "Running smoke tests..."
echo ""

run_test "Health Check API" test_health_check
run_test "List Agents API" test_list_agents
run_test "Get Single Agent" test_get_agent
run_test "Update Agent (Dry Run)" test_update_agent
run_test "Customer Recognition" test_customer_recognition
run_test "Dynamic Variables" test_dynamic_variables
run_test "Webhook Security" test_webhook_security
run_test "Agent Sync Status" test_sync_status
run_test "Custom Functions" test_custom_functions
run_test "Knowledge Base" test_knowledge_base
run_test "Database Connection" test_database
run_test "Redis Connection" test_redis
run_test "Queue System" test_queue
run_test "Feature Flags" test_feature_flags
run_test "Logging System" test_logging

echo ""
echo "================================"
echo "Smoke Test Results:"
echo "  ✅ Passed: $TESTS_PASSED"
echo "  ❌ Failed: $TESTS_FAILED"
echo "================================"

if [ $TESTS_FAILED -gt 0 ]; then
    echo ""
    echo "Failed tests:"
    for test in "${FAILED_TESTS[@]}"; do
        echo "  - $test"
    done
    echo ""
    echo "⚠️  SMOKE TESTS FAILED! Do not proceed with deployment."
    exit 1
else
    echo ""
    echo "✅ All smoke tests passed! Safe to proceed."
    
    # Generate success report
    cat > /tmp/smoke_test_report.json << EOF
{
    "timestamp": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
    "status": "success",
    "total_tests": $((TESTS_PASSED + TESTS_FAILED)),
    "passed": $TESTS_PASSED,
    "failed": $TESTS_FAILED,
    "environment": {
        "api_base": "$API_BASE",
        "php_version": "$(php -v | head -n1)",
        "mysql_version": "$(mysql --version | cut -d' ' -f6)",
        "redis_version": "$(redis-cli --version | cut -d' ' -f2)"
    }
}
EOF
    
    echo "Report saved to: /tmp/smoke_test_report.json"
    exit 0
fi