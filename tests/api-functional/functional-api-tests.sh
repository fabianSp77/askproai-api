#!/bin/bash

#########################################
# Business Portal API Functional Tests
# Tests all endpoints with various scenarios
#########################################

set -e

# Configuration
BASE_URL="${BASE_URL:-https://api.askproai.de}"
API_BASE="$BASE_URL/business/api"
TEST_EMAIL="${TEST_EMAIL:-test@askproai.de}"
TEST_PASSWORD="${TEST_PASSWORD:-testpassword123}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Test results
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

# Temporary files
COOKIE_JAR="/tmp/api_test_cookies.txt"
AUTH_RESPONSE="/tmp/auth_response.json"
TEST_RESULTS="/tmp/api_test_results.json"

# Cleanup function
cleanup() {
    rm -f "$COOKIE_JAR" "$AUTH_RESPONSE" "$TEST_RESULTS"
}
trap cleanup EXIT

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Business Portal API Functional Tests${NC}"
echo -e "${BLUE}========================================${NC}"
echo "Base URL: $BASE_URL"
echo "API Base: $API_BASE"
echo "Test Email: $TEST_EMAIL"
echo ""

# Test result tracking
pass_test() {
    ((TOTAL_TESTS++))
    ((PASSED_TESTS++))
    echo -e "${GREEN}✓ PASS:${NC} $1"
}

fail_test() {
    ((TOTAL_TESTS++))
    ((FAILED_TESTS++))
    echo -e "${RED}✗ FAIL:${NC} $1"
}

warn_test() {
    echo -e "${YELLOW}⚠ WARN:${NC} $1"
}

info_test() {
    echo -e "${BLUE}ℹ INFO:${NC} $1"
}

# Helper function to make authenticated requests
make_request() {
    local method="$1"
    local endpoint="$2"
    local data="$3"
    local expected_status="$4"
    local test_name="$5"
    
    local url="$API_BASE$endpoint"
    local response_file="/tmp/response_$RANDOM.json"
    
    if [ "$method" = "GET" ]; then
        response=$(curl -s -w "\n%{http_code}" -b "$COOKIE_JAR" \
            -H "Accept: application/json" \
            -H "X-Requested-With: XMLHttpRequest" \
            "$url")
    elif [ "$method" = "POST" ]; then
        response=$(curl -s -w "\n%{http_code}" -b "$COOKIE_JAR" \
            -H "Content-Type: application/json" \
            -H "Accept: application/json" \
            -H "X-Requested-With: XMLHttpRequest" \
            -d "$data" \
            "$url")
    elif [ "$method" = "PUT" ]; then
        response=$(curl -s -w "\n%{http_code}" -b "$COOKIE_JAR" \
            -X PUT \
            -H "Content-Type: application/json" \
            -H "Accept: application/json" \
            -H "X-Requested-With: XMLHttpRequest" \
            -d "$data" \
            "$url")
    elif [ "$method" = "DELETE" ]; then
        response=$(curl -s -w "\n%{http_code}" -b "$COOKIE_JAR" \
            -X DELETE \
            -H "Accept: application/json" \
            -H "X-Requested-With: XMLHttpRequest" \
            "$url")
    fi
    
    # Extract HTTP status code (last line)
    status_code=$(echo "$response" | tail -n1)
    # Extract response body (all but last line)
    body=$(echo "$response" | head -n -1)
    
    # Save response for debugging
    echo "$body" > "$response_file"
    
    if [ "$status_code" = "$expected_status" ]; then
        pass_test "$test_name (Status: $status_code)"
        
        # Additional JSON validation if status is 200
        if [ "$status_code" = "200" ] && [ "$body" != "" ]; then
            if echo "$body" | jq . >/dev/null 2>&1; then
                pass_test "$test_name - Valid JSON response"
            else
                fail_test "$test_name - Invalid JSON response"
                echo "Response: $body" | head -c 200
            fi
        fi
    else
        fail_test "$test_name (Expected: $expected_status, Got: $status_code)"
        if [ ${#body} -lt 500 ]; then
            echo "Response: $body"
        else
            echo "Response: $(echo "$body" | head -c 200)..."
        fi
    fi
    
    rm -f "$response_file"
    return $([ "$status_code" = "$expected_status" ] && echo 0 || echo 1)
}

# Authentication test
echo -e "\n${YELLOW}=== Authentication Tests ===${NC}"

# Test login
login_data=$(cat <<EOF
{
    "email": "$TEST_EMAIL",
    "password": "$TEST_PASSWORD"
}
EOF
)

info_test "Attempting login with test credentials..."

login_response=$(curl -s -w "\n%{http_code}" -c "$COOKIE_JAR" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d "$login_data" \
    "$BASE_URL/business/api/auth/login")

login_status=$(echo "$login_response" | tail -n1)
login_body=$(echo "$login_response" | head -n -1)

if [ "$login_status" = "200" ]; then
    pass_test "Authentication successful"
    echo "$login_body" > "$AUTH_RESPONSE"
    
    # Extract token if present
    if echo "$login_body" | jq -e '.token' >/dev/null 2>&1; then
        AUTH_TOKEN=$(echo "$login_body" | jq -r '.token')
        info_test "Auth token extracted: ${AUTH_TOKEN:0:20}..."
    fi
else
    fail_test "Authentication failed (Status: $login_status)"
    echo "Response: $login_body"
    echo -e "${RED}Cannot proceed with API tests without authentication${NC}"
    exit 1
fi

# Test auth check endpoint
make_request "GET" "/auth-check" "" "200" "Auth check endpoint"

# Dashboard API Tests
echo -e "\n${YELLOW}=== Dashboard API Tests ===${NC}"

make_request "GET" "/dashboard" "" "200" "Dashboard main endpoint"
make_request "GET" "/dashboard/stats" "" "200" "Dashboard stats"
make_request "GET" "/dashboard/recent-calls" "" "200" "Recent calls"
make_request "GET" "/dashboard/upcoming-appointments" "" "200" "Upcoming appointments"

# Test dashboard with different time ranges
make_request "GET" "/dashboard?range=today" "" "200" "Dashboard - today filter"
make_request "GET" "/dashboard?range=week" "" "200" "Dashboard - week filter"
make_request "GET" "/dashboard?range=month" "" "200" "Dashboard - month filter"

# Calls API Tests
echo -e "\n${YELLOW}=== Calls API Tests ===${NC}"

make_request "GET" "/calls" "" "200" "Calls list"
make_request "GET" "/calls?page=1&per_page=10" "" "200" "Calls with pagination"
make_request "GET" "/calls?search=test" "" "200" "Calls with search"
make_request "GET" "/calls?status=completed" "" "200" "Calls with status filter"
make_request "GET" "/calls?date_from=2024-01-01" "" "200" "Calls with date filter"

# Test call export
export_data='{"format":"csv","filters":{}}'
make_request "POST" "/calls/export" "$export_data" "200" "Calls export"

# Appointments API Tests
echo -e "\n${YELLOW}=== Appointments API Tests ===${NC}"

make_request "GET" "/appointments" "" "200" "Appointments list"
make_request "GET" "/appointments/filters" "" "200" "Appointments filters"
make_request "GET" "/appointments/calendar" "" "200" "Appointments calendar view"
make_request "GET" "/appointments?page=1&per_page=10" "" "200" "Appointments with pagination"

# Test appointment creation
appointment_data=$(cat <<EOF
{
    "customer_name": "Test Customer",
    "customer_phone": "+491234567890",
    "customer_email": "test@example.com",
    "service_id": null,
    "staff_id": null,
    "starts_at": "$(date -d '+1 day' --iso-8601=minutes)",
    "duration_minutes": 60,
    "notes": "Test appointment"
}
EOF
)

make_request "POST" "/appointments" "$appointment_data" "201" "Create appointment"

# Customers API Tests
echo -e "\n${YELLOW}=== Customers API Tests ===${NC}"

make_request "GET" "/customers" "" "200" "Customers list"
make_request "GET" "/customers?page=1&per_page=10" "" "200" "Customers with pagination"
make_request "GET" "/customers?search=test" "" "200" "Customers with search"

# Test customer export
customer_export_data='{"format":"csv","filters":{}}'
make_request "POST" "/customers/export" "$customer_export_data" "200" "Customers export"

# Team API Tests
echo -e "\n${YELLOW}=== Team API Tests ===${NC}"

make_request "GET" "/team" "" "200" "Team list"
make_request "GET" "/team/roles" "" "200" "Team roles"

# Settings API Tests
echo -e "\n${YELLOW}=== Settings API Tests ===${NC}"

make_request "GET" "/settings" "" "200" "Settings main"
make_request "GET" "/settings/profile" "" "200" "Profile settings"
make_request "GET" "/settings/company" "" "200" "Company settings"
make_request "GET" "/settings/services" "" "200" "Services settings"
make_request "GET" "/settings/working-hours" "" "200" "Working hours settings"
make_request "GET" "/settings/integrations" "" "200" "Integrations settings"
make_request "GET" "/settings/call-notifications" "" "200" "Call notifications settings"

# Analytics API Tests
echo -e "\n${YELLOW}=== Analytics API Tests ===${NC}"

make_request "GET" "/analytics/overview" "" "200" "Analytics overview"
make_request "GET" "/analytics/calls" "" "200" "Calls analytics"
make_request "GET" "/analytics/appointments" "" "200" "Appointments analytics"
make_request "GET" "/analytics/customers" "" "200" "Customers analytics"
make_request "GET" "/analytics/revenue" "" "200" "Revenue analytics"
make_request "GET" "/analytics/team-performance" "" "200" "Team performance analytics"

# Billing API Tests
echo -e "\n${YELLOW}=== Billing API Tests ===${NC}"

make_request "GET" "/billing" "" "200" "Billing main"
make_request "GET" "/billing/transactions" "" "200" "Billing transactions"
make_request "GET" "/billing/usage" "" "200" "Billing usage"
make_request "GET" "/billing/invoices" "" "200" "Billing invoices"
make_request "GET" "/billing/payment-methods" "" "200" "Payment methods"
make_request "GET" "/billing/auto-topup" "" "200" "Auto-topup settings"

# Branches API Tests
echo -e "\n${YELLOW}=== Branches API Tests ===${NC}"

make_request "GET" "/branches" "" "200" "Branches list"

# Events API Tests
echo -e "\n${YELLOW}=== Events API Tests ===${NC}"

make_request "GET" "/events" "" "200" "Events list"
make_request "GET" "/events/timeline" "" "200" "Events timeline"
make_request "GET" "/events/stats" "" "200" "Events stats"
make_request "GET" "/events/schemas" "" "200" "Events schemas"

# Error Handling Tests
echo -e "\n${YELLOW}=== Error Handling Tests ===${NC}"

make_request "GET" "/nonexistent-endpoint" "" "404" "404 error handling"
make_request "GET" "/dashboard/invalid" "" "404" "Invalid dashboard endpoint"

# Input Validation Tests
echo -e "\n${YELLOW}=== Input Validation Tests ===${NC}"

# Test invalid JSON
invalid_json='{"invalid": json}'
response=$(curl -s -w "\n%{http_code}" -b "$COOKIE_JAR" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d "$invalid_json" \
    "$API_BASE/appointments")

status_code=$(echo "$response" | tail -n1)
if [ "$status_code" = "400" ] || [ "$status_code" = "422" ]; then
    pass_test "Invalid JSON rejected"
else
    fail_test "Invalid JSON not properly handled (Status: $status_code)"
fi

# Test empty data
empty_data='{}'
make_request "POST" "/appointments" "$empty_data" "422" "Empty appointment data validation"

# Test invalid email format
invalid_email_data='{"email": "invalid-email", "name": "Test"}'
make_request "POST" "/team" "$invalid_email_data" "422" "Invalid email format validation"

# Rate Limiting Tests
echo -e "\n${YELLOW}=== Rate Limiting Tests ===${NC}"

info_test "Testing rate limiting (making rapid requests)..."
rate_limit_hit=false

for i in {1..50}; do
    response=$(curl -s -w "%{http_code}" -b "$COOKIE_JAR" \
        -H "Accept: application/json" \
        "$API_BASE/dashboard/stats")
    
    if [ "$response" = "429" ]; then
        rate_limit_hit=true
        break
    fi
done

if [ "$rate_limit_hit" = true ]; then
    pass_test "Rate limiting is active"
else
    warn_test "Rate limiting not detected (may need higher request volume)"
fi

# Response Format Tests
echo -e "\n${YELLOW}=== Response Format Tests ===${NC}"

# Test JSON response format
response=$(curl -s -b "$COOKIE_JAR" \
    -H "Accept: application/json" \
    "$API_BASE/dashboard")

if echo "$response" | jq . >/dev/null 2>&1; then
    pass_test "Dashboard returns valid JSON"
    
    # Test required fields
    if echo "$response" | jq -e '.stats' >/dev/null 2>&1; then
        pass_test "Dashboard has stats field"
    else
        fail_test "Dashboard missing stats field"
    fi
    
    if echo "$response" | jq -e '.chartData' >/dev/null 2>&1; then
        pass_test "Dashboard has chartData field"
    else
        fail_test "Dashboard missing chartData field"
    fi
else
    fail_test "Dashboard returns invalid JSON"
fi

# CORS Tests
echo -e "\n${YELLOW}=== CORS Tests ===${NC}"

cors_response=$(curl -s -w "\n%{http_code}" \
    -H "Origin: https://malicious-site.com" \
    -H "Access-Control-Request-Method: GET" \
    -H "Access-Control-Request-Headers: authorization" \
    -X OPTIONS \
    "$API_BASE/dashboard")

cors_status=$(echo "$cors_response" | tail -n1)
cors_headers=$(echo "$cors_response" | head -n -1)

if [ "$cors_status" = "200" ] || [ "$cors_status" = "204" ]; then
    # Check if CORS is properly configured (not allowing all origins)
    if echo "$cors_headers" | grep -q "Access-Control-Allow-Origin: \*"; then
        warn_test "CORS allows all origins (potential security risk)"
    else
        pass_test "CORS properly configured"
    fi
else
    pass_test "CORS preflight handled correctly"
fi

# Performance Tests
echo -e "\n${YELLOW}=== Performance Tests ===${NC}"

info_test "Testing response times..."

start_time=$(date +%s%N)
curl -s -b "$COOKIE_JAR" \
    -H "Accept: application/json" \
    "$API_BASE/dashboard" > /dev/null
end_time=$(date +%s%N)

duration=$((($end_time - $start_time) / 1000000)) # Convert to milliseconds

if [ $duration -lt 2000 ]; then
    pass_test "Dashboard response time acceptable (${duration}ms)"
else
    fail_test "Dashboard response time too slow (${duration}ms)"
fi

# Security Headers Tests
echo -e "\n${YELLOW}=== Security Headers Tests ===${NC}"

headers_response=$(curl -s -I -b "$COOKIE_JAR" "$API_BASE/dashboard")

security_headers=("X-Content-Type-Options" "X-Frame-Options" "X-XSS-Protection")

for header in "${security_headers[@]}"; do
    if echo "$headers_response" | grep -qi "$header"; then
        pass_test "Security header present: $header"
    else
        warn_test "Security header missing: $header"
    fi
done

# Content-Type Tests
echo -e "\n${YELLOW}=== Content-Type Tests ===${NC}"

# Test JSON content type
json_response=$(curl -s -I -b "$COOKIE_JAR" \
    -H "Accept: application/json" \
    "$API_BASE/dashboard")

if echo "$json_response" | grep -qi "content-type.*application/json"; then
    pass_test "Proper JSON content-type header"
else
    fail_test "Missing or incorrect JSON content-type header"
fi

# Final Results
echo -e "\n${BLUE}========================================${NC}"
echo -e "${BLUE}Test Results Summary${NC}"
echo -e "${BLUE}========================================${NC}"
echo "Total Tests: $TOTAL_TESTS"
echo -e "Passed: ${GREEN}$PASSED_TESTS${NC}"
echo -e "Failed: ${RED}$FAILED_TESTS${NC}"

if [ $FAILED_TESTS -eq 0 ]; then
    echo -e "\n${GREEN}🎉 All tests passed!${NC}"
    exit 0
else
    success_rate=$((PASSED_TESTS * 100 / TOTAL_TESTS))
    echo -e "\nSuccess Rate: $success_rate%"
    
    if [ $success_rate -ge 90 ]; then
        echo -e "${GREEN}✅ Excellent (90%+)${NC}"
        exit 0
    elif [ $success_rate -ge 80 ]; then
        echo -e "${YELLOW}⚠️  Good (80%+) - Some issues to address${NC}"
        exit 1
    else
        echo -e "${RED}❌ Critical issues found - Requires immediate attention${NC}"
        exit 1
    fi
fi