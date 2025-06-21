#!/bin/bash

# AskProAI Post-Deployment Verification Script
# Version: 1.0.0
# Description: Comprehensive verification after deployment with automated testing

set -euo pipefail
IFS=$'\n\t'

# Configuration
readonly APP_DIR="${APP_DIR:-/var/www/api-gateway}"
readonly BASE_URL="${BASE_URL:-https://api.askproai.de}"
readonly TEST_PHONE="+49301234567890"  # Test phone number
readonly REPORT_FILE="/tmp/askproai-post-deployment-$(date +%Y%m%d_%H%M%S).txt"
readonly SLACK_WEBHOOK="${SLACK_WEBHOOK_URL:-}"

# Colors
readonly RED='\033[0;31m'
readonly GREEN='\033[0;32m'
readonly YELLOW='\033[1;33m'
readonly BLUE='\033[0;34m'
readonly NC='\033[0m'

# Test results
declare -A TEST_RESULTS
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

# Logging
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$REPORT_FILE"
}

log_success() {
    echo -e "${GREEN}✅ $*${NC}" | tee -a "$REPORT_FILE"
}

log_error() {
    echo -e "${RED}❌ $*${NC}" | tee -a "$REPORT_FILE"
}

log_warn() {
    echo -e "${YELLOW}⚠️  $*${NC}" | tee -a "$REPORT_FILE"
}

log_section() {
    echo -e "\n${BLUE}=== $* ===${NC}" | tee -a "$REPORT_FILE"
}

# Test execution wrapper
run_test() {
    local test_name="$1"
    local test_command="$2"
    local expected_result="${3:-0}"
    
    ((TOTAL_TESTS++))
    
    echo -n "Testing $test_name... "
    
    if eval "$test_command" &>/dev/null; then
        if [ "$expected_result" -eq 0 ]; then
            log_success "PASSED"
            TEST_RESULTS["$test_name"]="PASSED"
            ((PASSED_TESTS++))
        else
            log_error "FAILED (expected failure but passed)"
            TEST_RESULTS["$test_name"]="FAILED"
            ((FAILED_TESTS++))
        fi
    else
        if [ "$expected_result" -ne 0 ]; then
            log_success "PASSED (correctly failed)"
            TEST_RESULTS["$test_name"]="PASSED"
            ((PASSED_TESTS++))
        else
            log_error "FAILED"
            TEST_RESULTS["$test_name"]="FAILED"
            ((FAILED_TESTS++))
        fi
    fi
}

# HTTP test helper
test_endpoint() {
    local endpoint="$1"
    local expected_status="${2:-200}"
    local method="${3:-GET}"
    local data="${4:-}"
    
    local curl_opts="-s -o /dev/null -w %{http_code}"
    
    if [ -n "$data" ]; then
        curl_opts="$curl_opts -d '$data' -H 'Content-Type: application/json'"
    fi
    
    local status=$(curl $curl_opts -X "$method" "$BASE_URL$endpoint")
    
    [ "$status" == "$expected_status" ]
}

# 1. Basic connectivity tests
test_basic_connectivity() {
    log_section "Basic Connectivity Tests"
    
    run_test "Application responds to requests" \
        "curl -s -o /dev/null -w '%{http_code}' $BASE_URL | grep -q '200'"
    
    run_test "HTTPS certificate valid" \
        "curl -s $BASE_URL 2>&1 | grep -v 'SSL certificate problem'"
    
    run_test "Response time < 2 seconds" \
        "[ $(curl -s -o /dev/null -w '%{time_total}' $BASE_URL | cut -d. -f1) -lt 2 ]"
}

# 2. API health endpoints
test_health_endpoints() {
    log_section "Health Check Endpoints"
    
    run_test "Main health endpoint" \
        "test_endpoint '/api/health' 200"
    
    run_test "Database health" \
        "test_endpoint '/api/health/database' 200"
    
    run_test "Redis health" \
        "test_endpoint '/api/health/redis' 200"
    
    run_test "Queue health" \
        "test_endpoint '/api/health/queue' 200"
    
    run_test "Storage health" \
        "test_endpoint '/api/health/storage' 200"
}

# 3. Critical API endpoints
test_api_endpoints() {
    log_section "API Endpoint Tests"
    
    # Test webhook endpoints respond correctly
    run_test "Cal.com webhook endpoint exists" \
        "test_endpoint '/api/webhooks/calcom' 401 POST"
    
    run_test "Retell webhook endpoint exists" \
        "test_endpoint '/api/webhooks/retell' 401 POST"
    
    # Test API versioning
    run_test "API version endpoint" \
        "test_endpoint '/api/version' 200"
    
    # Test authentication endpoints
    run_test "Login endpoint available" \
        "test_endpoint '/api/auth/login' 422 POST"
}

# 4. Database connectivity
test_database() {
    log_section "Database Tests"
    
    cd "$APP_DIR"
    
    run_test "Database migrations current" \
        "php artisan migrate:status | grep -v 'Pending'"
    
    run_test "Can query database" \
        "php artisan tinker --execute='DB::select(\"SELECT 1\");' | grep -q '1'"
    
    run_test "Models can be loaded" \
        "php artisan tinker --execute='App\Models\Company::count();' | grep -E '[0-9]'"
}

# 5. Queue system
test_queue_system() {
    log_section "Queue System Tests"
    
    cd "$APP_DIR"
    
    run_test "Queue connection working" \
        "php artisan queue:monitor default | grep -q 'OK'"
    
    run_test "Horizon is running" \
        "php artisan horizon:status | grep -q 'running'"
    
    # Dispatch a test job
    run_test "Can dispatch jobs" \
        "php artisan tinker --execute='dispatch(new \App\Jobs\TestJob());'"
    
    # Check if job was processed (wait a bit)
    sleep 5
    
    run_test "Jobs are being processed" \
        "[ $(php artisan queue:size default) -eq 0 ]"
}

# 6. External service connectivity
test_external_services() {
    log_section "External Service Tests"
    
    cd "$APP_DIR"
    
    # Test Cal.com connectivity
    run_test "Cal.com API accessible" \
        "php artisan tinker --execute='app(\App\Services\CalcomV2Service::class)->testConnection();' | grep -q 'true'"
    
    # Test Retell.ai connectivity
    run_test "Retell.ai API accessible" \
        "php artisan tinker --execute='app(\App\Services\RetellService::class)->testConnection();' | grep -q 'true'"
    
    # Test email configuration
    run_test "Mail configuration valid" \
        "php artisan tinker --execute='Mail::raw(\"test\", function(\$m) { \$m->to(\"test@example.com\"); });'"
}

# 7. File permissions
test_file_permissions() {
    log_section "File Permission Tests"
    
    cd "$APP_DIR"
    
    run_test "Storage directory writable" \
        "[ -w storage ] && [ -w storage/logs ] && [ -w storage/framework/cache ]"
    
    run_test "Bootstrap cache writable" \
        "[ -w bootstrap/cache ]"
    
    run_test "Log files writable" \
        "touch storage/logs/test-$(date +%s).log && rm storage/logs/test-*.log"
}

# 8. Performance tests
test_performance() {
    log_section "Performance Tests"
    
    # Test response times for critical endpoints
    local endpoints=(
        "/api/health"
        "/api/version"
    )
    
    for endpoint in "${endpoints[@]}"; do
        local response_time=$(curl -s -o /dev/null -w '%{time_total}' "$BASE_URL$endpoint")
        local response_ms=$(echo "$response_time * 1000" | bc | cut -d. -f1)
        
        run_test "Response time for $endpoint < 500ms" \
            "[ $response_ms -lt 500 ]"
    done
    
    # Test concurrent requests
    run_test "Handles concurrent requests" \
        "seq 1 10 | xargs -P10 -I{} curl -s -o /dev/null -w '%{http_code}\n' $BASE_URL/api/health | grep -v '5[0-9][0-9]' | wc -l | grep -q '10'"
}

# 9. Security tests
test_security() {
    log_section "Security Tests"
    
    run_test "Debug mode disabled" \
        "! curl -s $BASE_URL | grep -q 'DebugBar'"
    
    run_test "No exposed .env file" \
        "test_endpoint '/.env' 404"
    
    run_test "No exposed .git directory" \
        "test_endpoint '/.git/config' 404"
    
    run_test "HTTPS redirect working" \
        "curl -s -o /dev/null -w '%{redirect_url}' http://${BASE_URL#https://} | grep -q 'https://'"
    
    run_test "Security headers present" \
        "curl -s -I $BASE_URL | grep -q 'X-Frame-Options'"
}

# 10. Caching tests
test_caching() {
    log_section "Caching Tests"
    
    cd "$APP_DIR"
    
    run_test "Config cache loaded" \
        "[ -f bootstrap/cache/config.php ]"
    
    run_test "Route cache loaded" \
        "[ -f bootstrap/cache/routes-v7.php ]"
    
    run_test "View cache working" \
        "[ -d storage/framework/views ] && [ $(ls storage/framework/views | wc -l) -gt 0 ]"
    
    run_test "OPcache enabled" \
        "php -i | grep -q 'opcache.enable => On => On'"
}

# 11. Business logic tests
test_business_logic() {
    log_section "Business Logic Tests"
    
    cd "$APP_DIR"
    
    # Test phone number resolution
    run_test "Phone number resolver working" \
        "php artisan tinker --execute='app(\App\Services\PhoneNumberResolver::class)->resolveBranch(\"$TEST_PHONE\");' | grep -v 'null'"
    
    # Test booking availability
    run_test "Availability service working" \
        "php artisan tinker --execute='app(\App\Services\CalcomV2Service::class)->getAvailability(1, now()->addDay()->format(\"Y-m-d\"));' | grep -q 'array'"
}

# 12. Monitoring and logging
test_monitoring() {
    log_section "Monitoring & Logging Tests"
    
    cd "$APP_DIR"
    
    run_test "Application logs being written" \
        "[ -f storage/logs/laravel-$(date +%Y-%m-%d).log ]"
    
    run_test "Metrics endpoint available" \
        "test_endpoint '/api/metrics' 200"
    
    if [ -n "${SENTRY_LARAVEL_DSN:-}" ]; then
        run_test "Sentry error tracking active" \
            "php artisan tinker --execute='app(\"sentry\")->captureMessage(\"Post-deployment test\");' | grep -v 'error'"
    fi
}

# 13. Scheduled tasks
test_scheduled_tasks() {
    log_section "Scheduled Task Tests"
    
    cd "$APP_DIR"
    
    run_test "Scheduler can list tasks" \
        "php artisan schedule:list"
    
    run_test "Scheduler can run" \
        "php artisan schedule:run"
}

# 14. End-to-end booking flow test
test_booking_flow() {
    log_section "End-to-End Booking Flow Test"
    
    # This is a simplified test - in reality, you'd want more comprehensive E2E tests
    
    # Test webhook can receive data
    local test_payload='{"event":"test","timestamp":"'$(date -u +%Y-%m-%dT%H:%M:%SZ)'"}'
    
    run_test "Webhook accepts valid payload" \
        "curl -s -X POST $BASE_URL/api/webhooks/health-check -H 'Content-Type: application/json' -d '$test_payload' -o /dev/null -w '%{http_code}' | grep -q '200'"
}

# Generate summary report
generate_report() {
    log_section "Test Summary"
    
    local pass_rate=$(echo "scale=2; $PASSED_TESTS * 100 / $TOTAL_TESTS" | bc)
    
    log "Total Tests: $TOTAL_TESTS"
    log "Passed: $PASSED_TESTS"
    log "Failed: $FAILED_TESTS"
    log "Pass Rate: ${pass_rate}%"
    
    echo -e "\n${BLUE}=== Failed Tests ===${NC}" | tee -a "$REPORT_FILE"
    for test_name in "${!TEST_RESULTS[@]}"; do
        if [ "${TEST_RESULTS[$test_name]}" == "FAILED" ]; then
            echo "  - $test_name" | tee -a "$REPORT_FILE"
        fi
    done
    
    # Deployment verification status
    if [ $FAILED_TESTS -eq 0 ]; then
        log_success "\n✅ All post-deployment tests passed! Deployment verified."
        local status="SUCCESS"
        local color="good"
    else
        log_error "\n❌ $FAILED_TESTS tests failed. Investigation required!"
        local status="FAILURE"
        local color="danger"
    fi
    
    # Send notification
    if [ -n "$SLACK_WEBHOOK" ]; then
        curl -X POST -H 'Content-type: application/json' \
            --data "{
                \"text\": \"Post-Deployment Verification: $status\",
                \"attachments\": [{
                    \"color\": \"$color\",
                    \"fields\": [
                        {\"title\": \"Total Tests\", \"value\": \"$TOTAL_TESTS\", \"short\": true},
                        {\"title\": \"Passed\", \"value\": \"$PASSED_TESTS\", \"short\": true},
                        {\"title\": \"Failed\", \"value\": \"$FAILED_TESTS\", \"short\": true},
                        {\"title\": \"Pass Rate\", \"value\": \"${pass_rate}%\", \"short\": true}
                    ]
                }]
            }" \
            "$SLACK_WEBHOOK" 2>/dev/null || true
    fi
}

# Performance metrics collection
collect_metrics() {
    log_section "Collecting Performance Metrics"
    
    cd "$APP_DIR"
    
    # Response times
    log "Average response times:"
    for i in {1..5}; do
        time=$(curl -s -o /dev/null -w '%{time_total}' "$BASE_URL/api/health")
        echo "  Attempt $i: ${time}s" | tee -a "$REPORT_FILE"
        sleep 1
    done
    
    # Memory usage
    log "\nPHP-FPM Memory Usage:"
    ps aux | grep php-fpm | grep -v grep | awk '{sum+=$6} END {print "  Total: " sum/1024 " MB"}' | tee -a "$REPORT_FILE"
    
    # Database connections
    log "\nDatabase Connections:"
    mysql -e "SHOW STATUS LIKE 'Threads_connected';" | grep -v Variable_name | tee -a "$REPORT_FILE"
    
    # Redis info
    log "\nRedis Memory Usage:"
    redis-cli info memory | grep used_memory_human | tee -a "$REPORT_FILE"
}

# Main execution
main() {
    log "AskProAI Post-Deployment Verification"
    log "Started: $(date)"
    log "Environment: $BASE_URL"
    log "Report: $REPORT_FILE"
    
    # Run all test suites
    test_basic_connectivity
    test_health_endpoints
    test_api_endpoints
    test_database
    test_queue_system
    test_external_services
    test_file_permissions
    test_performance
    test_security
    test_caching
    test_business_logic
    test_monitoring
    test_scheduled_tasks
    test_booking_flow
    
    # Collect metrics
    collect_metrics
    
    # Generate report
    generate_report
    
    log "\nCompleted: $(date)"
    log "Full report: $REPORT_FILE"
    
    # Exit with appropriate code
    [ $FAILED_TESTS -eq 0 ] && exit 0 || exit 1
}

# Run main function
main "$@"