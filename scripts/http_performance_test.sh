#!/bin/bash

###############################################################################
# HTTP Endpoint Performance Test
#
# Tests real-world HTTP endpoint performance with authentication
# Measures response times for critical admin panel pages
###############################################################################

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
BASE_URL="https://api.askproai.de"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@askproai.de}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-}"

# Performance targets (milliseconds)
TARGET_DASHBOARD=1500
TARGET_LIST=2000
TARGET_DETAIL=1000

# Results storage
RESULTS_FILE="/tmp/http_performance_results.txt"
rm -f "$RESULTS_FILE"

###############################################################################
# Helper Functions
###############################################################################

print_header() {
    echo -e "${BLUE}╔════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║     HTTP Endpoint Performance Test                                ║${NC}"
    echo -e "${BLUE}╚════════════════════════════════════════════════════════════════════╝${NC}"
    echo ""
}

print_section() {
    echo -e "\n${YELLOW}▶ $1${NC}"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
}

measure_endpoint() {
    local name="$1"
    local url="$2"
    local target="$3"
    local auth="$4"

    echo -n "Testing ${name}... "

    # Perform request with timing
    if [ -n "$auth" ]; then
        response=$(curl -s -w "\n%{http_code}\n%{time_total}" \
            -H "Cookie: ${auth}" \
            -H "Accept: text/html,application/json" \
            "${url}" 2>&1)
    else
        response=$(curl -s -w "\n%{http_code}\n%{time_total}" \
            -H "Accept: text/html,application/json" \
            "${url}" 2>&1)
    fi

    # Extract metrics
    http_code=$(echo "$response" | tail -2 | head -1)
    time_total=$(echo "$response" | tail -1)
    time_ms=$(echo "$time_total * 1000" | bc)

    # Check status
    if [ "$http_code" = "200" ]; then
        status="${GREEN}✓${NC}"
    else
        status="${RED}✗ (HTTP ${http_code})${NC}"
    fi

    # Check target
    comparison=$(echo "$time_ms <= $target" | bc)
    if [ "$comparison" -eq 1 ]; then
        target_status="${GREEN}PASS${NC}"
    else
        target_status="${RED}FAIL${NC}"
    fi

    # Print result
    printf "${status} %.0fms (target: %dms) - %b\n" "$time_ms" "$target" "$target_status"

    # Store result
    echo "${name}|${time_ms}|${target}|${http_code}|${comparison}" >> "$RESULTS_FILE"
}

get_auth_cookie() {
    if [ -z "$ADMIN_PASSWORD" ]; then
        echo -e "${YELLOW}⚠ No admin password provided. Set ADMIN_PASSWORD environment variable.${NC}"
        echo -e "${YELLOW}  Skipping authenticated tests.${NC}"
        return 1
    fi

    echo -n "Authenticating... "

    # Get CSRF token
    csrf_response=$(curl -s -c /tmp/cookies.txt "${BASE_URL}/admin/login")
    csrf_token=$(echo "$csrf_response" | grep -o 'name="_token" value="[^"]*"' | sed 's/name="_token" value="//;s/"//')

    if [ -z "$csrf_token" ]; then
        echo -e "${RED}✗ Failed to get CSRF token${NC}"
        return 1
    fi

    # Login
    login_response=$(curl -s -b /tmp/cookies.txt -c /tmp/cookies.txt \
        -X POST "${BASE_URL}/admin/login" \
        -d "_token=${csrf_token}" \
        -d "email=${ADMIN_EMAIL}" \
        -d "password=${ADMIN_PASSWORD}" \
        -w "\n%{http_code}" \
        -L)

    http_code=$(echo "$login_response" | tail -1)

    if [ "$http_code" = "200" ] || [ "$http_code" = "302" ]; then
        # Extract session cookie
        auth_cookie=$(cat /tmp/cookies.txt | grep -v '^#' | grep 'laravel_session' | awk '{print $6"="$7}')
        echo -e "${GREEN}✓${NC}"
        echo "$auth_cookie"
        return 0
    else
        echo -e "${RED}✗ Login failed (HTTP ${http_code})${NC}"
        return 1
    fi
}

###############################################################################
# Main Tests
###############################################################################

print_header

print_section "Public Endpoints (No Authentication)"
measure_endpoint "Health Check" "${BASE_URL}/health" 100 ""
measure_endpoint "Home Page" "${BASE_URL}/" 1000 ""

print_section "Authenticating to Admin Panel"
auth_cookie=$(get_auth_cookie)

if [ -n "$auth_cookie" ]; then
    print_section "Admin Panel Endpoints (Authenticated)"

    measure_endpoint "Admin Dashboard" "${BASE_URL}/admin" "$TARGET_DASHBOARD" "$auth_cookie"
    measure_endpoint "Callback Requests List" "${BASE_URL}/admin/callback-requests" "$TARGET_LIST" "$auth_cookie"
    measure_endpoint "Branches List" "${BASE_URL}/admin/branches" "$TARGET_LIST" "$auth_cookie"
    measure_endpoint "Staff List" "${BASE_URL}/admin/staff" "$TARGET_LIST" "$auth_cookie"
    measure_endpoint "Services List" "${BASE_URL}/admin/services" "$TARGET_LIST" "$auth_cookie"
    measure_endpoint "Customers List" "${BASE_URL}/admin/customers" "$TARGET_LIST" "$auth_cookie"

    # Get first branch ID for detail test
    branch_id=$(curl -s -H "Cookie: ${auth_cookie}" "${BASE_URL}/admin/branches" | grep -o 'branches/[a-f0-9-]*' | head -1 | cut -d'/' -f2)

    if [ -n "$branch_id" ]; then
        measure_endpoint "Branch Detail" "${BASE_URL}/admin/branches/${branch_id}" "$TARGET_DETAIL" "$auth_cookie"
    fi
fi

###############################################################################
# Generate Report
###############################################################################

print_section "Performance Report"

if [ ! -f "$RESULTS_FILE" ]; then
    echo -e "${RED}No results to report${NC}"
    exit 1
fi

total_tests=0
passed_tests=0
failed_tests=0

echo ""
printf "%-30s %10s %10s %10s %s\n" "Endpoint" "Time (ms)" "Target" "HTTP" "Status"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

while IFS='|' read -r name time_ms target http_code comparison; do
    total_tests=$((total_tests + 1))

    if [ "$comparison" -eq 1 ]; then
        status="${GREEN}PASS${NC}"
        passed_tests=$((passed_tests + 1))
    else
        status="${RED}FAIL${NC}"
        failed_tests=$((failed_tests + 1))
    fi

    printf "%-30s %10.0f %10s %10s %b\n" "$name" "$time_ms" "${target}ms" "$http_code" "$status"
done < "$RESULTS_FILE"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo -e "Total Tests: ${total_tests}"
echo -e "${GREEN}Passed: ${passed_tests}${NC}"
echo -e "${RED}Failed: ${failed_tests}${NC}"

if [ "$failed_tests" -eq 0 ]; then
    echo -e "\n${GREEN}✓ All performance targets met!${NC}"
    exit 0
else
    echo -e "\n${RED}✗ ${failed_tests} performance target(s) not met${NC}"
    exit 1
fi
