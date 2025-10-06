#!/bin/bash
# Production Deployment Verification Script
# Purpose: Automated non-destructive verification of new components
# Author: Quality Engineer
# Date: 2025-10-01

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Test counters
TESTS_PASSED=0
TESTS_FAILED=0
TESTS_WARNING=0

# Function to print colored status
print_status() {
    local status=$1
    local message=$2

    case $status in
        "PASS")
            echo -e "${GREEN}✅ PASS${NC}: $message"
            ((TESTS_PASSED++))
            ;;
        "FAIL")
            echo -e "${RED}❌ FAIL${NC}: $message"
            ((TESTS_FAILED++))
            ;;
        "WARN")
            echo -e "${YELLOW}⚠️  WARN${NC}: $message"
            ((TESTS_WARNING++))
            ;;
        "CRITICAL")
            echo -e "${RED}🚨 CRITICAL${NC}: $message"
            ((TESTS_FAILED++))
            ;;
        "INFO")
            echo -e "${BLUE}ℹ️  INFO${NC}: $message"
            ;;
    esac
}

# Header
echo "==================================================================="
echo "           Production Deployment Verification"
echo "==================================================================="
echo "Date: $(date)"
echo "Components: LogSanitizer, CircuitBreaker, RateLimiter, +5 more"
echo "==================================================================="
echo ""

# Test 1: Health Check
echo "[1/12] Application Health Check..."
HEALTH_RESPONSE=$(curl -s http://localhost/api/health)
HEALTH_STATUS=$(echo $HEALTH_RESPONSE | jq -r '.status' 2>/dev/null || echo "error")

if [ "$HEALTH_STATUS" = "healthy" ]; then
    print_status "PASS" "Application is healthy"
elif [ "$HEALTH_STATUS" = "degraded" ]; then
    print_status "WARN" "Application is degraded"
else
    print_status "FAIL" "Health check failed (status: $HEALTH_STATUS)"
    echo "Response: $HEALTH_RESPONSE"
fi
echo ""

# Test 2: HTTP Response Code
echo "[2/12] HTTP Response Code..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/api/health)
if [ "$HTTP_CODE" = "200" ]; then
    print_status "PASS" "HTTP 200 OK"
else
    print_status "FAIL" "Unexpected HTTP code: $HTTP_CODE"
fi
echo ""

# Test 3: Middleware Registration
echo "[3/12] Middleware Registration..."
MIDDLEWARE_COUNT=$(php artisan route:list 2>/dev/null | grep -c "api\.rate-limit\|api\.performance\|api\.logging" || echo "0")
if [ "$MIDDLEWARE_COUNT" -gt 0 ]; then
    print_status "PASS" "Middleware registered ($MIDDLEWARE_COUNT routes)"
else
    print_status "WARN" "Middleware may not be registered"
fi
echo ""

# Test 4: Redis Connection
echo "[4/12] Redis Connection..."
REDIS_PING=$(redis-cli PING 2>/dev/null || echo "ERROR")
if [ "$REDIS_PING" = "PONG" ]; then
    print_status "PASS" "Redis is accessible"
else
    print_status "FAIL" "Redis not responding"
fi
echo ""

# Test 5: Circuit Breaker Instantiation
echo "[5/12] Circuit Breaker Service..."
CB_RESULT=$(php artisan tinker --execute="
try {
    \$breaker = new \App\Services\CircuitBreaker('test_service');
    \$status = \$breaker->getStatus();
    echo \$status['state'] . '|' . \$status['failure_count'];
} catch (\Exception \$e) {
    echo 'ERROR|' . \$e->getMessage();
}" 2>&1 | tail -1)

CB_STATE=$(echo $CB_RESULT | cut -d'|' -f1)
CB_FAILURES=$(echo $CB_RESULT | cut -d'|' -f2)

if [ "$CB_STATE" = "closed" ]; then
    print_status "PASS" "Circuit breaker instantiated (state: $CB_STATE, failures: $CB_FAILURES)"
elif [[ "$CB_STATE" == *"ERROR"* ]]; then
    print_status "FAIL" "Circuit breaker instantiation error: $CB_FAILURES"
else
    print_status "WARN" "Circuit breaker state: $CB_STATE (failures: $CB_FAILURES)"
fi
echo ""

# Test 6: Rate Limiter Instantiation
echo "[6/12] Rate Limiter Service..."
RL_RESULT=$(php artisan tinker --execute="
try {
    \$limiter = new \App\Services\CalcomApiRateLimiter();
    echo \$limiter->getRemainingRequests();
} catch (\Exception \$e) {
    echo 'ERROR: ' . \$e->getMessage();
}" 2>&1 | tail -1)

if [[ "$RL_RESULT" =~ ^[0-9]+$ ]]; then
    print_status "PASS" "Rate limiter working (remaining: $RL_RESULT)"
elif [[ "$RL_RESULT" == *"ERROR"* ]]; then
    print_status "FAIL" "Rate limiter error: $RL_RESULT"
else
    print_status "WARN" "Rate limiter unexpected result: $RL_RESULT"
fi
echo ""

# Test 7: Log Sanitizer
echo "[7/12] Log Sanitizer..."
LS_RESULT=$(php artisan tinker --execute="
\$test = ['email' => 'test@example.com', 'password' => 'secret123', 'token' => 'abc123'];
\$sanitized = \App\Helpers\LogSanitizer::sanitize(\$test);
\$json = json_encode(\$sanitized);
echo (str_contains(\$json, 'REDACTED') ? 'PASS' : 'FAIL');
" 2>&1 | tail -1)

if [ "$LS_RESULT" = "PASS" ]; then
    print_status "PASS" "Log sanitizer redacting sensitive data"
else
    print_status "WARN" "Log sanitizer may not be active"
fi
echo ""

# Test 8: Recent Error Count
echo "[8/12] Recent Error Count..."
LOG_FILE="storage/logs/laravel-$(date +%Y-%m-%d).log"
if [ -f "$LOG_FILE" ]; then
    ERROR_COUNT=$(grep -c "ERROR" "$LOG_FILE" 2>/dev/null || echo "0")
    CRITICAL_COUNT=$(grep -c "CRITICAL" "$LOG_FILE" 2>/dev/null || echo "0")

    if [ "$CRITICAL_COUNT" -gt 0 ]; then
        print_status "FAIL" "Found $CRITICAL_COUNT CRITICAL errors today"
    elif [ "$ERROR_COUNT" -lt 10 ]; then
        print_status "PASS" "Error count acceptable ($ERROR_COUNT errors today)"
    elif [ "$ERROR_COUNT" -lt 50 ]; then
        print_status "WARN" "Elevated error count ($ERROR_COUNT errors today)"
    else
        print_status "FAIL" "High error count ($ERROR_COUNT errors today)"
    fi
else
    print_status "INFO" "Today's log file not found (no activity yet)"
fi
echo ""

# Test 9: PII Exposure Check (CRITICAL GDPR COMPLIANCE)
echo "[9/12] PII Exposure Check (GDPR)..."
if [ -f "$LOG_FILE" ]; then
    # Check for unredacted email addresses
    EMAIL_COUNT=$(grep -oE "[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}" "$LOG_FILE" 2>/dev/null | \
                  grep -v "EMAIL_REDACTED" | \
                  grep -v "noreply@" | \
                  grep -v "example.com" | \
                  wc -l || echo "0")

    # Check for unredacted bearer tokens
    BEARER_COUNT=$(grep -o "Bearer [A-Za-z0-9\-._~+/]" "$LOG_FILE" 2>/dev/null | wc -l || echo "0")

    # Check for API keys (long hex strings)
    API_KEY_COUNT=$(grep -oE "\b[a-f0-9]{32,}\b" "$LOG_FILE" 2>/dev/null | \
                    grep -v "API_KEY_REDACTED" | \
                    wc -l || echo "0")

    TOTAL_PII=$((EMAIL_COUNT + BEARER_COUNT + API_KEY_COUNT))

    if [ "$TOTAL_PII" -eq 0 ]; then
        print_status "PASS" "No PII exposure detected (GDPR compliant)"
    else
        print_status "CRITICAL" "PII EXPOSURE DETECTED! Emails: $EMAIL_COUNT, Tokens: $BEARER_COUNT, Keys: $API_KEY_COUNT"
        echo ""
        echo "🚨🚨🚨 GDPR VIOLATION DETECTED 🚨🚨🚨"
        echo "This is a critical security and compliance issue."
        echo "IMMEDIATE ACTION REQUIRED: Review logs and consider rollback"
        echo ""

        if [ "$EMAIL_COUNT" -gt 0 ]; then
            echo "Sample exposed emails:"
            grep -oE "[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}" "$LOG_FILE" | \
            grep -v "EMAIL_REDACTED" | \
            grep -v "noreply@" | \
            grep -v "example.com" | \
            head -3
            echo ""
        fi
    fi
else
    print_status "INFO" "Cannot check PII (log file not found)"
fi
echo ""

# Test 10: Cache Hit Ratio
echo "[10/12] Cache Performance..."
HITS=$(redis-cli INFO stats 2>/dev/null | grep "keyspace_hits:" | cut -d: -f2 | tr -d '\r' || echo "0")
MISSES=$(redis-cli INFO stats 2>/dev/null | grep "keyspace_misses:" | cut -d: -f2 | tr -d '\r' || echo "0")

if [ "$HITS" -eq 0 ] && [ "$MISSES" -eq 0 ]; then
    print_status "INFO" "No cache activity yet"
elif [ "$HITS" -gt "$MISSES" ]; then
    HIT_RATIO=$((HITS * 100 / (HITS + MISSES)))
    print_status "PASS" "Cache performing well (${HIT_RATIO}% hit ratio: $HITS hits, $MISSES misses)"
else
    HIT_RATIO=$((HITS * 100 / (HITS + MISSES)))
    print_status "WARN" "Low cache hit ratio (${HIT_RATIO}%: $HITS hits, $MISSES misses)"
fi
echo ""

# Test 11: Circuit Breaker State (Cal.com API)
echo "[11/12] Circuit Breaker State (Cal.com API)..."
CALCOM_CB_STATE=$(redis-cli GET "circuit_breaker:calcom_api:state" 2>/dev/null || echo "not_set")

if [ "$CALCOM_CB_STATE" = "closed" ] || [ "$CALCOM_CB_STATE" = "not_set" ]; then
    print_status "PASS" "Cal.com circuit breaker healthy (state: $CALCOM_CB_STATE)"
elif [ "$CALCOM_CB_STATE" = "half_open" ]; then
    print_status "WARN" "Cal.com circuit breaker testing recovery (state: half_open)"
elif [ "$CALCOM_CB_STATE" = "open" ]; then
    print_status "CRITICAL" "Cal.com circuit breaker is OPEN - service may be down!"
else
    print_status "INFO" "Cal.com circuit breaker state: $CALCOM_CB_STATE"
fi
echo ""

# Test 12: Response Time
echo "[12/12] Response Time..."
RESPONSE_TIME=$(curl -o /dev/null -s -w '%{time_total}' http://localhost/api/health 2>/dev/null || echo "error")

if [ "$RESPONSE_TIME" = "error" ]; then
    print_status "FAIL" "Could not measure response time"
elif (( $(echo "$RESPONSE_TIME < 0.5" | bc -l) )); then
    print_status "PASS" "Response time: ${RESPONSE_TIME}s (excellent)"
elif (( $(echo "$RESPONSE_TIME < 1.0" | bc -l) )); then
    print_status "PASS" "Response time: ${RESPONSE_TIME}s (good)"
elif (( $(echo "$RESPONSE_TIME < 2.0" | bc -l) )); then
    print_status "WARN" "Response time: ${RESPONSE_TIME}s (elevated)"
else
    print_status "FAIL" "Response time: ${RESPONSE_TIME}s (too slow)"
fi
echo ""

# Summary
echo "==================================================================="
echo "                    Verification Summary"
echo "==================================================================="
echo -e "${GREEN}Passed${NC}:   $TESTS_PASSED"
echo -e "${YELLOW}Warnings${NC}: $TESTS_WARNING"
echo -e "${RED}Failed${NC}:   $TESTS_FAILED"
echo "==================================================================="
echo ""

# Overall Status
if [ "$TESTS_FAILED" -eq 0 ] && [ "$TESTS_WARNING" -eq 0 ]; then
    echo -e "${GREEN}✅ ALL TESTS PASSED${NC}"
    echo "Deployment verification successful!"
    EXIT_CODE=0
elif [ "$TESTS_FAILED" -eq 0 ]; then
    echo -e "${YELLOW}⚠️  TESTS PASSED WITH WARNINGS${NC}"
    echo "Deployment appears stable but requires monitoring."
    echo "Review warnings above and monitor for next 30 minutes."
    EXIT_CODE=1
else
    echo -e "${RED}❌ TESTS FAILED${NC}"
    echo "Deployment verification failed!"
    echo "Review failures above and consider rollback."
    EXIT_CODE=2
fi
echo ""

# Recommendations
echo "==================================================================="
echo "                      Recommendations"
echo "==================================================================="

if [ "$TESTS_FAILED" -gt 0 ]; then
    echo "🚨 IMMEDIATE ACTION REQUIRED:"
    echo "1. Review failed tests above"
    echo "2. Check application logs: tail -f storage/logs/laravel.log"
    echo "3. Consider rollback if critical issues detected"
    echo ""
fi

if [ "$TESTS_WARNING" -gt 0 ]; then
    echo "⚠️  MONITORING REQUIRED:"
    echo "1. Continue monitoring for next 30 minutes"
    echo "2. Watch error logs: tail -f storage/logs/laravel.log"
    echo "3. Monitor circuit breaker: watch redis-cli GET circuit_breaker:calcom_api:state"
    echo ""
fi

echo "📊 CONTINUOUS MONITORING:"
echo "1. Monitor logs:        tail -f storage/logs/laravel.log"
echo "2. Watch errors:        tail -f storage/logs/laravel.log | grep ERROR"
echo "3. Circuit breakers:    redis-cli KEYS 'circuit_breaker:*:state'"
echo "4. Rate limiters:       redis-cli KEYS 'calcom_api_rate_limit:*'"
echo "5. Performance:         tail -f storage/logs/laravel.log | grep -i 'slow\|timeout'"
echo ""

echo "📋 GENERATE FULL REPORT:"
echo "   See: claudedocs/production-verification-strategy.md"
echo ""

echo "==================================================================="

exit $EXIT_CODE
