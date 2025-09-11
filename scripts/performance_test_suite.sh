#!/bin/bash

###############################################################################
# AskProAI Performance Test Suite
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
LOG_FILE="/var/www/api-gateway/storage/logs/performance_test_$(date +%Y%m%d_%H%M%S).log"
RESULTS_FILE="/var/www/api-gateway/storage/logs/performance_results_$(date +%Y%m%d_%H%M%S).json"

# Performance thresholds (in seconds)
PAGE_LOAD_THRESHOLD=3.0
API_RESPONSE_THRESHOLD=1.0
DATABASE_QUERY_THRESHOLD=0.5
ASSET_LOAD_THRESHOLD=2.0

# Test configuration
CONCURRENT_USERS=10
REQUEST_COUNT=100
WARMUP_REQUESTS=5

# Function to log performance data
log_performance() {
    local test_name="$1"
    local metric="$2"
    local value="$3"
    local threshold="$4"
    local status="$5"
    
    echo "$(date '+%Y-%m-%d %H:%M:%S') [$status] $test_name: $metric = $value (threshold: $threshold)" >> "$LOG_FILE"
    
    # Also log to JSON for analysis
    local json_entry="{\"timestamp\":\"$(date -Iseconds)\",\"test\":\"$test_name\",\"metric\":\"$metric\",\"value\":$value,\"threshold\":$threshold,\"status\":\"$status\"}"
    echo "$json_entry," >> "$RESULTS_FILE"
}

# Function to print performance results
print_performance() {
    local status="$1"
    local test_name="$2"
    local metric="$3"
    local value="$4"
    local threshold="$5"
    local details="$6"
    
    case "$status" in
        "PASS")
            echo -e "${GREEN}✓${NC} $test_name: $metric = ${value}s (< ${threshold}s)"
            [ -n "$details" ] && echo "  $details"
            log_performance "$test_name" "$metric" "$value" "$threshold" "PASS"
            ;;
        "FAIL")
            echo -e "${RED}✗${NC} $test_name: $metric = ${value}s (>= ${threshold}s)"
            [ -n "$details" ] && echo "  $details"
            log_performance "$test_name" "$metric" "$value" "$threshold" "FAIL"
            ;;
        "WARN")
            echo -e "${YELLOW}⚠${NC} $test_name: $metric = ${value}s"
            [ -n "$details" ] && echo "  $details"
            log_performance "$test_name" "$metric" "$value" "$threshold" "WARN"
            ;;
    esac
}

# Function to measure HTTP response time
measure_http_response() {
    local url="$1"
    local method="${2:-GET}"
    local data="$3"
    
    local cmd="curl -w '%{time_total}|%{http_code}|%{size_download}|%{speed_download}|%{time_namelookup}|%{time_connect}|%{time_pretransfer}|%{time_starttransfer}' -s -o /dev/null"
    
    if [ "$method" = "POST" ] && [ -n "$data" ]; then
        cmd="$cmd -X POST -d '$data' -H 'Content-Type: application/json'"
    fi
    
    cmd="$cmd '$url'"
    eval $cmd
}

# Function to perform load testing
load_test() {
    local url="$1"
    local concurrent="$2"
    local requests="$3"
    local test_name="$4"
    
    echo "  Running load test: $requests requests with $concurrent concurrent users"
    
    # Use Apache Bench if available, otherwise use curl loop
    if command -v ab >/dev/null 2>&1; then
        local ab_result
        ab_result=$(ab -n "$requests" -c "$concurrent" -q "$url" 2>/dev/null)
        
        if [ $? -eq 0 ]; then
            local avg_time
            local req_per_sec
            avg_time=$(echo "$ab_result" | grep "Time per request:" | head -1 | awk '{print $4}')
            req_per_sec=$(echo "$ab_result" | grep "Requests per second:" | awk '{print $4}')
            
            # Convert to seconds
            avg_time=$(echo "scale=3; $avg_time / 1000" | bc 2>/dev/null || echo "0")
            
            echo "    Average response time: ${avg_time}s"
            echo "    Requests per second: $req_per_sec"
            
            log_performance "$test_name" "avg_response_time" "$avg_time" "N/A" "INFO"
            log_performance "$test_name" "requests_per_second" "$req_per_sec" "N/A" "INFO"
            
            return 0
        fi
    fi
    
    # Fallback: Simple curl-based load test
    echo "    Using fallback load testing (limited concurrency)"
    local total_time=0
    local success_count=0
    
    for ((i=1; i<=requests; i++)); do
        local response_time
        response_time=$(measure_http_response "$url" | cut -d'|' -f1)
        if [ -n "$response_time" ] && (( $(echo "$response_time > 0" | bc -l 2>/dev/null || echo "0") )); then
            total_time=$(echo "$total_time + $response_time" | bc)
            ((success_count++))
        fi
        
        # Show progress every 10 requests
        if (( i % 10 == 0 )); then
            echo "    Progress: $i/$requests requests completed"
        fi
    done
    
    if [ $success_count -gt 0 ]; then
        local avg_time
        avg_time=$(echo "scale=3; $total_time / $success_count" | bc)
        echo "    Average response time: ${avg_time}s"
        echo "    Success rate: $success_count/$requests"
        
        log_performance "$test_name" "avg_response_time" "$avg_time" "N/A" "INFO"
        log_performance "$test_name" "success_rate" "$(echo "scale=2; $success_count * 100 / $requests" | bc)%" "N/A" "INFO"
    fi
}

# Test 1: Page Load Performance
test_page_performance() {
    echo -e "${BLUE}=== Page Load Performance ===${NC}"
    
    # Warmup requests
    echo "  Performing warmup requests..."
    for ((i=1; i<=WARMUP_REQUESTS; i++)); do
        measure_http_response "$BASE_URL" >/dev/null 2>&1
    done
    
    # Test main page
    local response
    response=$(measure_http_response "$BASE_URL")
    local time_total
    local http_code
    local size
    time_total=$(echo "$response" | cut -d'|' -f1)
    http_code=$(echo "$response" | cut -d'|' -f2)
    size=$(echo "$response" | cut -d'|' -f3)
    
    if [ "$http_code" = "200" ] && [ -n "$time_total" ]; then
        if (( $(echo "$time_total < $PAGE_LOAD_THRESHOLD" | bc -l) )); then
            print_performance "PASS" "Main page load" "response_time" "$time_total" "$PAGE_LOAD_THRESHOLD" "Size: ${size} bytes"
        else
            print_performance "FAIL" "Main page load" "response_time" "$time_total" "$PAGE_LOAD_THRESHOLD" "Size: ${size} bytes"
        fi
    else
        print_performance "FAIL" "Main page load" "error" "HTTP $http_code" "200" "Request failed"
    fi
    
    # Test admin page
    response=$(measure_http_response "$BASE_URL/admin")
    time_total=$(echo "$response" | cut -d'|' -f1)
    http_code=$(echo "$response" | cut -d'|' -f2)
    
    if [ "$http_code" = "200" ] && [ -n "$time_total" ]; then
        if (( $(echo "$time_total < $PAGE_LOAD_THRESHOLD" | bc -l) )); then
            print_performance "PASS" "Admin page load" "response_time" "$time_total" "$PAGE_LOAD_THRESHOLD"
        else
            print_performance "FAIL" "Admin page load" "response_time" "$time_total" "$PAGE_LOAD_THRESHOLD"
        fi
    else
        print_performance "FAIL" "Admin page load" "error" "HTTP $http_code" "200" "Request failed"
    fi
}

# Test 2: API Performance
test_api_performance() {
    echo -e "${BLUE}=== API Performance ===${NC}"
    
    # Test health endpoint
    local response
    response=$(measure_http_response "$BASE_URL/api/health")
    local time_total
    local http_code
    time_total=$(echo "$response" | cut -d'|' -f1)
    http_code=$(echo "$response" | cut -d'|' -f2)
    
    if [ "$http_code" = "200" ] && [ -n "$time_total" ]; then
        if (( $(echo "$time_total < $API_RESPONSE_THRESHOLD" | bc -l) )); then
            print_performance "PASS" "API health endpoint" "response_time" "$time_total" "$API_RESPONSE_THRESHOLD"
        else
            print_performance "FAIL" "API health endpoint" "response_time" "$time_total" "$API_RESPONSE_THRESHOLD"
        fi
    else
        print_performance "FAIL" "API health endpoint" "error" "HTTP $http_code" "200" "Request failed"
    fi
    
    # Test webhook endpoints (should be fast even when rejecting)
    local endpoints=(
        "api/calcom/webhook"
        "api/retell/webhook"
    )
    
    for endpoint in "${endpoints[@]}"; do
        response=$(measure_http_response "$BASE_URL/$endpoint")
        time_total=$(echo "$response" | cut -d'|' -f1)
        http_code=$(echo "$response" | cut -d'|' -f2)
        
        if [ -n "$time_total" ]; then
            if (( $(echo "$time_total < $API_RESPONSE_THRESHOLD" | bc -l) )); then
                print_performance "PASS" "Webhook endpoint: $endpoint" "response_time" "$time_total" "$API_RESPONSE_THRESHOLD" "HTTP $http_code"
            else
                print_performance "FAIL" "Webhook endpoint: $endpoint" "response_time" "$time_total" "$API_RESPONSE_THRESHOLD" "HTTP $http_code"
            fi
        else
            print_performance "FAIL" "Webhook endpoint: $endpoint" "error" "No response" "N/A" "Request failed"
        fi
    done
}

# Test 3: Database Performance
test_database_performance() {
    echo -e "${BLUE}=== Database Performance ===${NC}"
    
    cd "$PROJECT_ROOT"
    
    # Test simple query performance
    local start_time
    local end_time
    local duration
    
    start_time=$(date +%s.%N)
    local result
    result=$(php artisan tinker --execute="echo DB::table('customers')->count();" 2>/dev/null | tail -1)
    end_time=$(date +%s.%N)
    duration=$(echo "$end_time - $start_time" | bc)
    
    if [ -n "$result" ] && [ "$result" != "0" ]; then
        if (( $(echo "$duration < $DATABASE_QUERY_THRESHOLD" | bc -l) )); then
            print_performance "PASS" "Simple count query" "response_time" "$duration" "$DATABASE_QUERY_THRESHOLD" "Result: $result records"
        else
            print_performance "FAIL" "Simple count query" "response_time" "$duration" "$DATABASE_QUERY_THRESHOLD" "Result: $result records"
        fi
    else
        print_performance "FAIL" "Simple count query" "error" "No result" "N/A" "Query failed"
    fi
    
    # Test join query performance
    start_time=$(date +%s.%N)
    result=$(php artisan tinker --execute="echo DB::table('calls')->join('customers', 'calls.customer_id', '=', 'customers.id')->count();" 2>/dev/null | tail -1)
    end_time=$(date +%s.%N)
    duration=$(echo "$end_time - $start_time" | bc)
    
    if [ -n "$result" ]; then
        local complex_threshold
        complex_threshold=$(echo "$DATABASE_QUERY_THRESHOLD * 2" | bc)
        if (( $(echo "$duration < $complex_threshold" | bc -l) )); then
            print_performance "PASS" "Join query" "response_time" "$duration" "$complex_threshold" "Result: $result records"
        else
            print_performance "FAIL" "Join query" "response_time" "$duration" "$complex_threshold" "Result: $result records"
        fi
    else
        print_performance "FAIL" "Join query" "error" "No result" "N/A" "Query failed"
    fi
}

# Test 4: Asset Performance
test_asset_performance() {
    echo -e "${BLUE}=== Asset Performance ===${NC}"
    
    # Test CSS asset loading
    local response
    response=$(measure_http_response "$BASE_URL/build/assets/app-BgEEAtcF.css")
    local time_total
    local http_code
    local size
    time_total=$(echo "$response" | cut -d'|' -f1)
    http_code=$(echo "$response" | cut -d'|' -f2)
    size=$(echo "$response" | cut -d'|' -f3)
    
    if [ "$http_code" = "200" ] && [ -n "$time_total" ]; then
        if (( $(echo "$time_total < $ASSET_LOAD_THRESHOLD" | bc -l) )); then
            print_performance "PASS" "CSS asset loading" "response_time" "$time_total" "$ASSET_LOAD_THRESHOLD" "Size: ${size} bytes"
        else
            print_performance "FAIL" "CSS asset loading" "response_time" "$time_total" "$ASSET_LOAD_THRESHOLD" "Size: ${size} bytes"
        fi
    else
        print_performance "FAIL" "CSS asset loading" "error" "HTTP $http_code" "200" "Request failed"
    fi
    
    # Test JS asset loading
    response=$(measure_http_response "$BASE_URL/build/assets/app-RXyt_GKD.js")
    time_total=$(echo "$response" | cut -d'|' -f1)
    http_code=$(echo "$response" | cut -d'|' -f2)
    size=$(echo "$response" | cut -d'|' -f3)
    
    if [ "$http_code" = "200" ] && [ -n "$time_total" ]; then
        if (( $(echo "$time_total < $ASSET_LOAD_THRESHOLD" | bc -l) )); then
            print_performance "PASS" "JS asset loading" "response_time" "$time_total" "$ASSET_LOAD_THRESHOLD" "Size: ${size} bytes"
        else
            print_performance "FAIL" "JS asset loading" "response_time" "$time_total" "$ASSET_LOAD_THRESHOLD" "Size: ${size} bytes"
        fi
    else
        print_performance "FAIL" "JS asset loading" "error" "HTTP $http_code" "200" "Request failed"
    fi
}

# Test 5: Memory and CPU Usage
test_system_resources() {
    echo -e "${BLUE}=== System Resource Usage ===${NC}"
    
    # Memory usage
    local mem_info
    mem_info=$(free -m)
    local mem_total
    local mem_used
    local mem_percent
    mem_total=$(echo "$mem_info" | awk 'NR==2{print $2}')
    mem_used=$(echo "$mem_info" | awk 'NR==2{print $3}')
    mem_percent=$(echo "scale=1; $mem_used * 100 / $mem_total" | bc)
    
    if (( $(echo "$mem_percent < 80" | bc -l) )); then
        print_performance "PASS" "Memory usage" "percent" "$mem_percent" "80" "${mem_used}MB / ${mem_total}MB"
    elif (( $(echo "$mem_percent < 90" | bc -l) )); then
        print_performance "WARN" "Memory usage" "percent" "$mem_percent" "80" "${mem_used}MB / ${mem_total}MB"
    else
        print_performance "FAIL" "Memory usage" "percent" "$mem_percent" "80" "${mem_used}MB / ${mem_total}MB"
    fi
    
    # Disk usage
    local disk_info
    disk_info=$(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')
    
    if [ "$disk_info" -lt 70 ]; then
        print_performance "PASS" "Disk usage" "percent" "$disk_info" "70" "$(df -h / | awk 'NR==2 {print $3"/"$2}')"
    elif [ "$disk_info" -lt 85 ]; then
        print_performance "WARN" "Disk usage" "percent" "$disk_info" "70" "$(df -h / | awk 'NR==2 {print $3"/"$2}')"
    else
        print_performance "FAIL" "Disk usage" "percent" "$disk_info" "70" "$(df -h / | awk 'NR==2 {print $3"/"$2}')"
    fi
    
    # Load average
    local load_avg
    load_avg=$(uptime | awk -F'load average:' '{print $2}' | awk '{print $1}' | sed 's/,//')
    local cpu_cores
    cpu_cores=$(nproc)
    local load_percent
    load_percent=$(echo "scale=1; $load_avg * 100 / $cpu_cores" | bc 2>/dev/null || echo "0")
    
    if (( $(echo "$load_percent < 70" | bc -l) )); then
        print_performance "PASS" "CPU load average" "percent" "$load_percent" "70" "$load_avg (${cpu_cores} cores)"
    elif (( $(echo "$load_percent < 90" | bc -l) )); then
        print_performance "WARN" "CPU load average" "percent" "$load_percent" "70" "$load_avg (${cpu_cores} cores)"
    else
        print_performance "FAIL" "CPU load average" "percent" "$load_percent" "70" "$load_avg (${cpu_cores} cores)"
    fi
}

# Test 6: Load Testing
test_load_performance() {
    echo -e "${BLUE}=== Load Testing ===${NC}"
    
    # Test main page under load
    echo "Testing main page load handling..."
    load_test "$BASE_URL" "$CONCURRENT_USERS" "$REQUEST_COUNT" "main_page_load_test"
    
    echo
    echo "Testing API endpoint load handling..."
    load_test "$BASE_URL/api/health" "$CONCURRENT_USERS" "$REQUEST_COUNT" "api_health_load_test"
}

# Test 7: Cache Performance
test_cache_performance() {
    echo -e "${BLUE}=== Cache Performance ===${NC}"
    
    cd "$PROJECT_ROOT"
    
    # Test cache write performance
    local start_time
    local end_time
    local duration
    
    start_time=$(date +%s.%N)
    php artisan cache:clear >/dev/null 2>&1
    end_time=$(date +%s.%N)
    duration=$(echo "$end_time - $start_time" | bc)
    
    if (( $(echo "$duration < 5.0" | bc -l) )); then
        print_performance "PASS" "Cache clear operation" "response_time" "$duration" "5.0"
    else
        print_performance "FAIL" "Cache clear operation" "response_time" "$duration" "5.0"
    fi
    
    # Test Redis performance (if configured)
    if redis-cli ping >/dev/null 2>&1; then
        start_time=$(date +%s.%N)
        redis-cli set test_key "test_value" >/dev/null 2>&1
        redis-cli get test_key >/dev/null 2>&1
        redis-cli del test_key >/dev/null 2>&1
        end_time=$(date +%s.%N)
        duration=$(echo "$end_time - $start_time" | bc)
        
        if (( $(echo "$duration < 0.1" | bc -l) )); then
            print_performance "PASS" "Redis cache operations" "response_time" "$duration" "0.1"
        else
            print_performance "FAIL" "Redis cache operations" "response_time" "$duration" "0.1"
        fi
    fi
}

###############################################################################
# MAIN EXECUTION
###############################################################################

echo "=========================================="
echo "  AskProAI Performance Test Suite"
echo "  $(date '+%Y-%m-%d %H:%M:%S')"
echo "  Base URL: $BASE_URL"
echo "  Load Test Config: $CONCURRENT_USERS users, $REQUEST_COUNT requests"
echo "=========================================="
echo

# Initialize log files
mkdir -p "$(dirname "$LOG_FILE")"
mkdir -p "$(dirname "$RESULTS_FILE")"

echo "Performance Test Suite Started - $(date)" > "$LOG_FILE"
echo "[" > "$RESULTS_FILE"

# Check dependencies
if ! command -v bc >/dev/null 2>&1; then
    echo -e "${RED}Error: bc (basic calculator) is required but not installed${NC}"
    exit 1
fi

# Run all performance tests
test_page_performance
echo
test_api_performance
echo
test_database_performance
echo
test_asset_performance
echo
test_system_resources
echo
test_cache_performance
echo
test_load_performance
echo

# Close JSON results file
echo "{\"timestamp\":\"$(date -Iseconds)\",\"test\":\"suite_completed\",\"status\":\"COMPLETE\"}]" >> "$RESULTS_FILE"

# Generate summary report
echo "=========================================="
echo "Performance Test Summary"
echo "=========================================="
echo "Detailed logs: $LOG_FILE"
echo "Results data: $RESULTS_FILE"
echo
echo "Thresholds used:"
echo "  Page load time: ${PAGE_LOAD_THRESHOLD}s"
echo "  API response time: ${API_RESPONSE_THRESHOLD}s"
echo "  Database query time: ${DATABASE_QUERY_THRESHOLD}s"
echo "  Asset load time: ${ASSET_LOAD_THRESHOLD}s"
echo

# Count results
local passes
local failures
passes=$(grep -c "PASS" "$LOG_FILE" 2>/dev/null || echo "0")
failures=$(grep -c "FAIL" "$LOG_FILE" 2>/dev/null || echo "0")

if [ "$failures" -eq 0 ]; then
    echo -e "${GREEN}✓ All performance tests passed ($passes tests)${NC}"
    exit 0
else
    echo -e "${RED}⚠ Performance issues detected: $failures failures, $passes passes${NC}"
    echo "Review the detailed logs for optimization recommendations."
    exit 1
fi