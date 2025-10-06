#!/bin/bash

# API Testing Script for Laravel Admin Panel
# Tests all critical endpoints and functionality

echo "=========================================="
echo "API ENDPOINT TESTING"
echo "=========================================="

BASE_URL="https://api.askproai.de"
COOKIE_JAR="/tmp/test-cookies.txt"

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test counter
TESTS_RUN=0
TESTS_PASSED=0
TESTS_FAILED=0

# Test function
run_test() {
    local test_name="$1"
    local command="$2"
    local expected_code="$3"

    TESTS_RUN=$((TESTS_RUN + 1))
    echo -n "Testing: $test_name... "

    # Execute command and capture response code
    response_code=$(eval "$command")

    if [ "$response_code" = "$expected_code" ]; then
        echo -e "${GREEN}✓ PASSED${NC} (HTTP $response_code)"
        TESTS_PASSED=$((TESTS_PASSED + 1))
    else
        echo -e "${RED}✗ FAILED${NC} (Expected: $expected_code, Got: $response_code)"
        TESTS_FAILED=$((TESTS_FAILED + 1))
    fi
}

echo ""
echo "1. HEALTH & MONITORING ENDPOINTS"
echo "------------------------------------------"

run_test "Health Check Endpoint" \
    "curl -s -o /dev/null -w '%{http_code}' -k $BASE_URL/api/health" \
    "200"

run_test "Monitoring Dashboard" \
    "curl -s -o /dev/null -w '%{http_code}' -k $BASE_URL/monitor/dashboard" \
    "200"

run_test "Metrics Endpoint" \
    "curl -s -o /dev/null -w '%{http_code}' -k $BASE_URL/monitor/health" \
    "200"

echo ""
echo "2. AUTHENTICATION ENDPOINTS"
echo "------------------------------------------"

run_test "Login Page Access" \
    "curl -s -o /dev/null -w '%{http_code}' -k -c $COOKIE_JAR $BASE_URL/admin/login" \
    "200"

run_test "Admin Dashboard (Unauthenticated)" \
    "curl -s -o /dev/null -w '%{http_code}' -k $BASE_URL/admin" \
    "302"

echo ""
echo "3. API VERSIONING"
echo "------------------------------------------"

run_test "API v1 Customers Endpoint" \
    "curl -s -o /dev/null -w '%{http_code}' -k $BASE_URL/api/v1/customers" \
    "501"

run_test "API v1 Calls Endpoint" \
    "curl -s -o /dev/null -w '%{http_code}' -k $BASE_URL/api/v1/calls" \
    "501"

run_test "API v1 Appointments Endpoint" \
    "curl -s -o /dev/null -w '%{http_code}' -k $BASE_URL/api/v1/appointments" \
    "501"

echo ""
echo "4. STATIC RESOURCES"
echo "------------------------------------------"

run_test "Favicon Access" \
    "curl -s -o /dev/null -w '%{http_code}' -k $BASE_URL/favicon.ico" \
    "404"

echo ""
echo "5. PERFORMANCE TESTING"
echo "------------------------------------------"

# Test response times
echo -n "Average Response Time (5 requests): "
total_time=0
for i in {1..5}; do
    time=$(curl -s -o /dev/null -w '%{time_total}' -k $BASE_URL/api/health)
    total_time=$(echo "$total_time + $time" | bc)
done
avg_time=$(echo "scale=3; $total_time / 5" | bc)

if (( $(echo "$avg_time < 0.5" | bc -l) )); then
    echo -e "${GREEN}$avg_time seconds ✓${NC}"
    TESTS_PASSED=$((TESTS_PASSED + 1))
else
    echo -e "${YELLOW}$avg_time seconds (slow)${NC}"
fi
TESTS_RUN=$((TESTS_RUN + 1))

echo ""
echo "6. ERROR HANDLING"
echo "------------------------------------------"

run_test "404 Error Page" \
    "curl -s -o /dev/null -w '%{http_code}' -k $BASE_URL/non-existent-page" \
    "404"

run_test "Method Not Allowed" \
    "curl -X POST -s -o /dev/null -w '%{http_code}' -k $BASE_URL/api/health" \
    "405"

echo ""
echo "7. SECURITY HEADERS"
echo "------------------------------------------"

echo -n "Checking Security Headers... "
headers=$(curl -I -s -k $BASE_URL/admin/login)

security_headers=(
    "X-Response-Time"
    "X-Memory-Usage"
)

headers_found=0
for header in "${security_headers[@]}"; do
    if echo "$headers" | grep -qi "$header"; then
        headers_found=$((headers_found + 1))
    fi
done

if [ $headers_found -eq ${#security_headers[@]} ]; then
    echo -e "${GREEN}✓ All security headers present${NC}"
    TESTS_PASSED=$((TESTS_PASSED + 1))
else
    echo -e "${YELLOW}⚠ Some headers missing (found $headers_found/${#security_headers[@]})${NC}"
fi
TESTS_RUN=$((TESTS_RUN + 1))

echo ""
echo "=========================================="
echo "TEST RESULTS SUMMARY"
echo "=========================================="
echo -e "Total Tests Run: $TESTS_RUN"
echo -e "Tests Passed: ${GREEN}$TESTS_PASSED${NC}"
echo -e "Tests Failed: ${RED}$TESTS_FAILED${NC}"

# Calculate pass rate
if [ $TESTS_RUN -gt 0 ]; then
    pass_rate=$(echo "scale=1; $TESTS_PASSED * 100 / $TESTS_RUN" | bc)
    echo -e "Pass Rate: $pass_rate%"

    if (( $(echo "$pass_rate >= 80" | bc -l) )); then
        echo -e "\n${GREEN}✓ TEST SUITE PASSED${NC}"
        exit 0
    else
        echo -e "\n${RED}✗ TEST SUITE FAILED${NC}"
        exit 1
    fi
fi