#!/bin/bash

###############################################################################
# AskProAI Functional Test Suite
# Version: 1.0
# Created: 2025-09-03
###############################################################################

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
BASE_URL="${BASE_URL:-https://api.askproai.de}"
LOG_FILE="/var/www/api-gateway/storage/logs/functional_test_$(date +%Y%m%d_%H%M%S).log"
FAILED_TESTS=0
PASSED_TESTS=0
TOTAL_TESTS=0

# Test credentials (should be configured for test environment)
TEST_EMAIL="${TEST_EMAIL:-admin@askproai.de}"
TEST_PASSWORD="${TEST_PASSWORD:-admin123}"

# Function to log test results
log_test() {
    local status="$1"
    local test_name="$2"
    local details="$3"
    
    echo "$(date '+%Y-%m-%d %H:%M:%S') [$status] $test_name - $details" >> "$LOG_FILE"
}

# Function to print test results
print_test() {
    local status="$1"
    local test_name="$2"
    local details="$3"
    
    ((TOTAL_TESTS++))
    
    case "$status" in
        "PASS")
            echo -e "${GREEN}✓${NC} $test_name"
            [ -n "$details" ] && echo "  $details"
            log_test "PASS" "$test_name" "$details"
            ((PASSED_TESTS++))
            ;;
        "FAIL")
            echo -e "${RED}✗${NC} $test_name"
            [ -n "$details" ] && echo "  $details"
            log_test "FAIL" "$test_name" "$details"
            ((FAILED_TESTS++))
            ;;
        "SKIP")
            echo -e "${YELLOW}⚠${NC} $test_name (SKIPPED)"
            [ -n "$details" ] && echo "  $details"
            log_test "SKIP" "$test_name" "$details"
            ;;
    esac
}

# Function to make HTTP requests
make_request() {
    local method="$1"
    local url="$2"
    local data="$3"
    local headers="$4"
    local timeout="${5:-30}"
    
    local cmd="curl -s -w 'HTTPSTATUS:%{http_code}|SIZE:%{size_download}|TIME:%{time_total}' --connect-timeout $timeout"
    
    if [ -n "$headers" ]; then
        cmd="$cmd $headers"
    fi
    
    case "$method" in
        "POST")
            if [ -n "$data" ]; then
                cmd="$cmd -X POST -d '$data'"
            fi
            ;;
        "PUT")
            if [ -n "$data" ]; then
                cmd="$cmd -X PUT -d '$data'"
            fi
            ;;
        "DELETE")
            cmd="$cmd -X DELETE"
            ;;
    esac
    
    cmd="$cmd '$url'"
    eval $cmd
}

# Function to extract HTTP status from curl response
get_http_status() {
    echo "$1" | grep -o "HTTPSTATUS:[0-9]*" | cut -d: -f2
}

# Function to extract response time from curl response
get_response_time() {
    echo "$1" | grep -o "TIME:[0-9.]*" | cut -d: -f2
}

# Function to extract response body from curl response
get_response_body() {
    echo "$1" | sed 's/HTTPSTATUS:.*$//'
}

# Test 1: Basic HTTP Endpoints
test_basic_endpoints() {
    echo -e "${BLUE}=== Basic HTTP Endpoints ===${NC}"
    
    # Test main page
    local response
    response=$(make_request "GET" "$BASE_URL")
    local status
    status=$(get_http_status "$response")
    
    if [ "$status" = "200" ]; then
        print_test "PASS" "Main page accessibility" "Status: $status"
    else
        print_test "FAIL" "Main page accessibility" "Expected: 200, Got: $status"
    fi
    
    # Test admin page
    response=$(make_request "GET" "$BASE_URL/admin")
    status=$(get_http_status "$response")
    
    if [ "$status" = "200" ]; then
        print_test "PASS" "Admin page accessibility" "Status: $status"
    else
        print_test "FAIL" "Admin page accessibility" "Expected: 200, Got: $status"
    fi
    
    # Test API health endpoint
    response=$(make_request "GET" "$BASE_URL/api/health")
    status=$(get_http_status "$response")
    
    if [ "$status" = "200" ]; then
        local body
        body=$(get_response_body "$response")
        if [[ "$body" == *"status"* ]]; then
            print_test "PASS" "API health endpoint" "Status: $status, Contains status info"
        else
            print_test "FAIL" "API health endpoint" "Status: $status, Missing status info"
        fi
    else
        print_test "FAIL" "API health endpoint" "Expected: 200, Got: $status"
    fi
}

# Test 2: Authentication System
test_authentication() {
    echo -e "${BLUE}=== Authentication System ===${NC}"
    
    # Test admin login page
    local response
    response=$(make_request "GET" "$BASE_URL/admin/login")
    local status
    status=$(get_http_status "$response")
    
    if [ "$status" = "200" ]; then
        local body
        body=$(get_response_body "$response")
        if [[ "$body" == *"login"* ]] || [[ "$body" == *"email"* ]]; then
            print_test "PASS" "Admin login page" "Status: $status, Login form present"
        else
            print_test "FAIL" "Admin login page" "Status: $status, Login form not detected"
        fi
    else
        print_test "FAIL" "Admin login page" "Expected: 200, Got: $status"
    fi
    
    # Test protected route without auth
    response=$(make_request "GET" "$BASE_URL/admin/dashboard")
    status=$(get_http_status "$response")
    
    if [ "$status" = "302" ] || [ "$status" = "401" ] || [ "$status" = "403" ]; then
        print_test "PASS" "Protected route security" "Status: $status (properly redirected/blocked)"
    elif [ "$status" = "200" ]; then
        print_test "FAIL" "Protected route security" "Status: $status (should be protected)"
    else
        print_test "SKIP" "Protected route security" "Unexpected status: $status"
    fi
}

# Test 3: API Endpoints
test_api_endpoints() {
    echo -e "${BLUE}=== API Endpoints ===${NC}"
    
    # Test webhook endpoints exist (should return 405 for GET requests)
    local endpoints=(
        "api/calcom/webhook"
        "api/retell/webhook"
    )
    
    for endpoint in "${endpoints[@]}"; do
        local response
        response=$(make_request "GET" "$BASE_URL/$endpoint")
        local status
        status=$(get_http_status "$response")
        
        if [ "$status" = "405" ] || [ "$status" = "419" ] || [ "$status" = "403" ]; then
            print_test "PASS" "Webhook endpoint: $endpoint" "Status: $status (correctly rejects GET)"
        elif [ "$status" = "200" ]; then
            print_test "FAIL" "Webhook endpoint: $endpoint" "Status: $status (should not accept GET)"
        else
            print_test "SKIP" "Webhook endpoint: $endpoint" "Unexpected status: $status"
        fi
    done
    
    # Test CORS handling
    local response
    response=$(curl -s -I -H "Origin: https://example.com" -H "Access-Control-Request-Method: POST" -H "Access-Control-Request-Headers: X-Requested-With" -X OPTIONS "$BASE_URL/api/health" 2>/dev/null)
    
    if echo "$response" | grep -qi "access-control-allow"; then
        print_test "PASS" "CORS configuration" "CORS headers present"
    else
        print_test "SKIP" "CORS configuration" "No CORS headers detected (may be configured differently)"
    fi
}

# Test 4: Database Connectivity
test_database() {
    echo -e "${BLUE}=== Database Connectivity ===${NC}"
    
    cd "$PROJECT_ROOT"
    
    # Test basic database connection
    if php artisan tinker --execute="DB::connection()->getPdo(); echo 'OK';" 2>/dev/null | grep -q "OK"; then
        print_test "PASS" "Database connection" "Connection established"
        
        # Test table existence
        local tables=("customers" "calls" "appointments" "users")
        for table in "${tables[@]}"; do
            if php artisan tinker --execute="DB::table('$table')->count();" 2>/dev/null >/dev/null; then
                local count
                count=$(php artisan tinker --execute="echo DB::table('$table')->count();" 2>/dev/null | tail -1)
                print_test "PASS" "Table exists: $table" "Records: $count"
            else
                print_test "FAIL" "Table exists: $table" "Table not accessible"
            fi
        done
    else
        print_test "FAIL" "Database connection" "Cannot connect to database"
    fi
}

# Test 5: File System Operations
test_filesystem() {
    echo -e "${BLUE}=== File System Operations ===${NC}"
    
    cd "$PROJECT_ROOT"
    
    # Test storage directory writability
    local test_file="storage/logs/test_write_$(date +%s).tmp"
    if echo "test" > "$test_file" 2>/dev/null; then
        print_test "PASS" "Storage write permissions" "Can write to storage/logs"
        rm -f "$test_file"
    else
        print_test "FAIL" "Storage write permissions" "Cannot write to storage/logs"
    fi
    
    # Test cache directory writability
    local cache_test="storage/framework/cache/test_$(date +%s).tmp"
    if echo "test" > "$cache_test" 2>/dev/null; then
        print_test "PASS" "Cache write permissions" "Can write to cache directory"
        rm -f "$cache_test"
    else
        print_test "FAIL" "Cache write permissions" "Cannot write to cache directory"
    fi
    
    # Test view cache
    if [ -d "storage/framework/views" ]; then
        print_test "PASS" "View cache directory" "Directory exists"
    else
        print_test "FAIL" "View cache directory" "Directory missing"
    fi
}

# Test 6: Asset Loading
test_assets() {
    echo -e "${BLUE}=== Asset Loading ===${NC}"
    
    # Test CSS assets
    local response
    response=$(make_request "GET" "$BASE_URL/build/assets/app-BgEEAtcF.css" "" "" 10)
    local status
    status=$(get_http_status "$response")
    
    if [ "$status" = "200" ]; then
        print_test "PASS" "CSS asset loading" "Main CSS file accessible"
    else
        print_test "FAIL" "CSS asset loading" "Status: $status (expected 200)"
    fi
    
    # Test JS assets
    response=$(make_request "GET" "$BASE_URL/build/assets/app-RXyt_GKD.js" "" "" 10)
    status=$(get_http_status "$response")
    
    if [ "$status" = "200" ]; then
        print_test "PASS" "JS asset loading" "Main JS file accessible"
    else
        print_test "FAIL" "JS asset loading" "Status: $status (expected 200)"
    fi
    
    # Test for missing assets from logs
    local missing_assets=(
        "wizard-progress-enhancer-BntUnTIW.js"
        "askproai-state-manager-BtNc_89J.js"
        "responsive-zoom-handler-DaecGYuG.js"
    )
    
    for asset in "${missing_assets[@]}"; do
        response=$(make_request "GET" "$BASE_URL/build/assets/$asset" "" "" 5)
        status=$(get_http_status "$response")
        
        if [ "$status" = "200" ]; then
            print_test "PASS" "Missing asset check: $asset" "Asset is now available"
        else
            print_test "FAIL" "Missing asset check: $asset" "Asset still missing (Status: $status)"
        fi
    done
}

# Test 7: Queue System
test_queue_system() {
    echo -e "${BLUE}=== Queue System ===${NC}"
    
    cd "$PROJECT_ROOT"
    
    # Test queue connection
    if php artisan queue:monitor --once 2>/dev/null; then
        print_test "PASS" "Queue connection" "Queue system accessible"
    else
        print_test "FAIL" "Queue connection" "Cannot connect to queue"
    fi
    
    # Test Horizon (if configured)
    if command -v supervisorctl >/dev/null 2>&1; then
        if supervisorctl status 2>/dev/null | grep -q horizon; then
            local horizon_status
            horizon_status=$(supervisorctl status | grep horizon | awk '{print $2}')
            if [ "$horizon_status" = "RUNNING" ]; then
                print_test "PASS" "Horizon queue processor" "Status: $horizon_status"
            else
                print_test "FAIL" "Horizon queue processor" "Status: $horizon_status"
            fi
        else
            print_test "SKIP" "Horizon queue processor" "Not configured"
        fi
    else
        print_test "SKIP" "Horizon queue processor" "Supervisorctl not available"
    fi
}

# Test 8: Cache System
test_cache_system() {
    echo -e "${BLUE}=== Cache System ===${NC}"
    
    cd "$PROJECT_ROOT"
    
    # Test cache connection
    if php artisan cache:clear >/dev/null 2>&1; then
        print_test "PASS" "Cache clear operation" "Cache can be cleared"
    else
        print_test "FAIL" "Cache clear operation" "Cannot clear cache"
    fi
    
    # Test Redis connection (if configured)
    if grep -q "CACHE_DRIVER=redis" .env; then
        if redis-cli ping >/dev/null 2>&1; then
            print_test "PASS" "Redis cache connection" "Redis responding to ping"
        else
            print_test "FAIL" "Redis cache connection" "Redis not responding"
        fi
    else
        print_test "SKIP" "Redis cache connection" "Redis not configured as cache driver"
    fi
}

# Test 9: Configuration Validation
test_configuration() {
    echo -e "${BLUE}=== Configuration Validation ===${NC}"
    
    cd "$PROJECT_ROOT"
    
    # Test Laravel configuration
    if php artisan config:cache >/dev/null 2>&1; then
        print_test "PASS" "Configuration caching" "Config can be cached"
    else
        print_test "FAIL" "Configuration caching" "Config caching failed"
    fi
    
    # Test route caching
    if php artisan route:cache >/dev/null 2>&1; then
        print_test "PASS" "Route caching" "Routes can be cached"
    else
        print_test "FAIL" "Route caching" "Route caching failed"
    fi
    
    # Test environment configuration
    local required_vars=(
        "APP_NAME"
        "APP_KEY"
        "APP_URL"
        "DB_CONNECTION"
    )
    
    local missing_vars=()
    for var in "${required_vars[@]}"; do
        if ! grep -q "^$var=" .env; then
            missing_vars+=("$var")
        fi
    done
    
    if [ ${#missing_vars[@]} -eq 0 ]; then
        print_test "PASS" "Required environment variables" "All required variables present"
    else
        print_test "FAIL" "Required environment variables" "Missing: ${missing_vars[*]}"
    fi
}

# Test 10: Performance Benchmarks
test_performance() {
    echo -e "${BLUE}=== Performance Benchmarks ===${NC}"
    
    # Test page load times
    local response
    response=$(make_request "GET" "$BASE_URL")
    local time
    time=$(get_response_time "$response")
    local time_ms
    time_ms=$(echo "$time * 1000" | bc 2>/dev/null || echo "0")
    
    if (( $(echo "$time < 2.0" | bc -l 2>/dev/null || echo "0") )); then
        print_test "PASS" "Page load time" "Main page: ${time}s (< 2s)"
    else
        print_test "FAIL" "Page load time" "Main page: ${time}s (should be < 2s)"
    fi
    
    # Test API response time
    response=$(make_request "GET" "$BASE_URL/api/health")
    time=$(get_response_time "$response")
    
    if (( $(echo "$time < 1.0" | bc -l 2>/dev/null || echo "0") )); then
        print_test "PASS" "API response time" "Health endpoint: ${time}s (< 1s)"
    else
        print_test "FAIL" "API response time" "Health endpoint: ${time}s (should be < 1s)"
    fi
}

###############################################################################
# MAIN EXECUTION
###############################################################################

echo "=========================================="
echo "  AskProAI Functional Test Suite"
echo "  $(date '+%Y-%m-%d %H:%M:%S')"
echo "  Base URL: $BASE_URL"
echo "=========================================="
echo

# Initialize log file
mkdir -p "$(dirname "$LOG_FILE")"
echo "Functional Test Suite Started - $(date)" > "$LOG_FILE"

# Run all test suites
test_basic_endpoints
echo
test_authentication
echo
test_api_endpoints
echo
test_database
echo
test_filesystem
echo
test_assets
echo
test_queue_system
echo
test_cache_system
echo
test_configuration
echo
test_performance
echo

# Summary
echo "=========================================="
echo "Test Results Summary:"
echo "  Total Tests: $TOTAL_TESTS"
echo -e "  ${GREEN}Passed: $PASSED_TESTS${NC}"
echo -e "  ${RED}Failed: $FAILED_TESTS${NC}"
echo

if [ $FAILED_TESTS -eq 0 ]; then
    echo -e "${GREEN}✓ All tests passed!${NC}"
    echo "Log file: $LOG_FILE"
    exit 0
else
    local success_rate
    success_rate=$(echo "scale=1; $PASSED_TESTS * 100 / $TOTAL_TESTS" | bc 2>/dev/null || echo "0")
    echo -e "${YELLOW}⚠ $FAILED_TESTS tests failed (${success_rate}% success rate)${NC}"
    echo "Log file: $LOG_FILE"
    exit 1
fi