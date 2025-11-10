#!/bin/bash
###############################################################################
# Security Test Suite - PR #723 Fixes
# Tests for session fixation and bearer token vulnerabilities
###############################################################################

set -euo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
BASE_URL="${1:-https://staging.askproai.de}"
HEALTHCHECK_URL="$BASE_URL/healthcheck.php"
LOGIN_URL="$BASE_URL/docs/backup-system/login"
DOCS_URL="$BASE_URL/docs/backup-system/"

# Test credentials (from .env)
USERNAME="admin"
PASSWORD="Qwe421as1!11"

# Old hardcoded token (should be rejected)
OLD_TOKEN="PewleIAhHC8IliNYhuKYG8W9iMMdrz0Ed3If6kCIMH0="

# New token (should be accepted - get from .env)
NEW_TOKEN="${HEALTHCHECK_TOKEN:-}"

PASSED=0
FAILED=0

###############################################################################
# Helper Functions
###############################################################################

print_test() {
    echo -e "${BLUE}[TEST]${NC} $1"
}

print_pass() {
    echo -e "${GREEN}[PASS]${NC} $1"
    ((PASSED++))
}

print_fail() {
    echo -e "${RED}[FAIL]${NC} $1"
    ((FAILED++))
}

print_info() {
    echo -e "${YELLOW}[INFO]${NC} $1"
}

print_header() {
    echo ""
    echo "==========================================================================="
    echo "$1"
    echo "==========================================================================="
}

###############################################################################
# Issue 1: Bearer Token Tests
###############################################################################

test_bearer_token() {
    print_header "Issue 1: Bearer Token Security Tests"

    # Test 1: Valid token from .env should work
    print_test "Test 1: Valid token authentication"
    if [ -n "$NEW_TOKEN" ]; then
        response=$(curl -s -w "\n%{http_code}" -H "Authorization: Bearer $NEW_TOKEN" "$HEALTHCHECK_URL")
        http_code=$(echo "$response" | tail -1)
        body=$(echo "$response" | head -n -1)

        if [ "$http_code" = "200" ]; then
            if echo "$body" | grep -q '"status":"healthy"'; then
                print_pass "Valid token accepted (200 OK)"
            else
                print_fail "Valid token accepted but unexpected response body"
            fi
        else
            print_fail "Valid token rejected (HTTP $http_code)"
        fi
    else
        print_info "Skipped: HEALTHCHECK_TOKEN not set in environment"
    fi

    # Test 2: Old hardcoded token should be rejected
    print_test "Test 2: Old hardcoded token rejection"
    response=$(curl -s -w "\n%{http_code}" -H "Authorization: Bearer $OLD_TOKEN" "$HEALTHCHECK_URL")
    http_code=$(echo "$response" | tail -1)

    if [ "$http_code" = "403" ]; then
        print_pass "Old hardcoded token rejected (403 Forbidden)"
    else
        print_fail "Old hardcoded token NOT rejected (HTTP $http_code) - SECURITY ISSUE!"
    fi

    # Test 3: No token should return 403
    print_test "Test 3: Missing token rejection"
    response=$(curl -s -w "\n%{http_code}" "$HEALTHCHECK_URL")
    http_code=$(echo "$response" | tail -1)

    if [ "$http_code" = "403" ]; then
        print_pass "Missing token rejected (403 Forbidden)"
    else
        print_fail "Missing token NOT rejected (HTTP $http_code)"
    fi

    # Test 4: Invalid token should return 403
    print_test "Test 4: Invalid token rejection"
    response=$(curl -s -w "\n%{http_code}" -H "Authorization: Bearer INVALID_TOKEN_12345" "$HEALTHCHECK_URL")
    http_code=$(echo "$response" | tail -1)

    if [ "$http_code" = "403" ]; then
        print_pass "Invalid token rejected (403 Forbidden)"
    else
        print_fail "Invalid token NOT rejected (HTTP $http_code)"
    fi
}

###############################################################################
# Issue 2: Session Fixation Tests
###############################################################################

test_session_fixation() {
    print_header "Issue 2: Session Fixation Security Tests"

    # Test 1: Session ID should change after login
    print_test "Test 1: Session regeneration after login"

    # Get initial session ID
    cookies1=$(mktemp)
    curl -s -c "$cookies1" "$LOGIN_URL" > /dev/null
    session_id_1=$(grep PHPSESSID "$cookies1" | awk '{print $7}')

    # Login with credentials
    cookies2=$(mktemp)
    csrf_token=$(curl -s -b "$cookies1" "$LOGIN_URL" | grep -oP 'name="_token" value="\K[^"]+')

    curl -s -b "$cookies1" -c "$cookies2" -X POST \
        -d "username=$USERNAME" \
        -d "password=$PASSWORD" \
        -d "_token=$csrf_token" \
        -L "$LOGIN_URL" > /dev/null

    session_id_2=$(grep PHPSESSID "$cookies2" | awk '{print $7}')

    if [ -n "$session_id_1" ] && [ -n "$session_id_2" ] && [ "$session_id_1" != "$session_id_2" ]; then
        print_pass "Session ID regenerated after login"
        print_info "  Before: ${session_id_1:0:16}..."
        print_info "  After:  ${session_id_2:0:16}..."
    else
        print_fail "Session ID NOT regenerated - SESSION FIXATION VULNERABILITY!"
        print_info "  Before: $session_id_1"
        print_info "  After:  $session_id_2"
    fi

    # Cleanup
    rm -f "$cookies1" "$cookies2"

    # Test 2: Old session should be invalid after login
    print_test "Test 2: Old session invalidation"

    # Get new session
    cookies3=$(mktemp)
    curl -s -c "$cookies3" "$LOGIN_URL" > /dev/null
    old_session=$(grep PHPSESSID "$cookies3" | awk '{print $7}')

    # Login (regenerates session)
    csrf_token=$(curl -s -b "$cookies3" "$LOGIN_URL" | grep -oP 'name="_token" value="\K[^"]+')
    curl -s -b "$cookies3" -c "$cookies3" -X POST \
        -d "username=$USERNAME" \
        -d "password=$PASSWORD" \
        -d "_token=$csrf_token" \
        -L "$LOGIN_URL" > /dev/null

    # Try to access docs with OLD session
    response=$(curl -s -w "\n%{http_code}" -b "PHPSESSID=$old_session" "$DOCS_URL")
    http_code=$(echo "$response" | tail -1)

    # Should redirect to login (302/401) or deny access
    if [ "$http_code" = "302" ] || [ "$http_code" = "401" ] || [ "$http_code" = "403" ]; then
        print_pass "Old session invalidated after login (HTTP $http_code)"
    else
        print_fail "Old session still valid - SECURITY ISSUE! (HTTP $http_code)"
    fi

    rm -f "$cookies3"
}

###############################################################################
# Additional Security Tests
###############################################################################

test_rate_limiting() {
    print_header "Additional Security: Rate Limiting Tests"

    print_test "Test 1: Rate limiting on failed login attempts"

    cookies=$(mktemp)
    failed_count=0

    for i in {1..7}; do
        csrf_token=$(curl -s -b "$cookies" -c "$cookies" "$LOGIN_URL" | grep -oP 'name="_token" value="\K[^"]+')
        response=$(curl -s -w "\n%{http_code}" -b "$cookies" -c "$cookies" -X POST \
            -d "username=admin" \
            -d "password=wrongpassword" \
            -d "_token=$csrf_token" \
            "$LOGIN_URL")

        http_code=$(echo "$response" | tail -1)

        if [ "$http_code" = "429" ]; then
            print_pass "Rate limiting active - blocked at attempt $i (429 Too Many Requests)"
            failed_count=$i
            break
        fi
    done

    if [ $failed_count -eq 0 ]; then
        print_info "Rate limiting not detected (may not be implemented yet)"
    fi

    rm -f "$cookies"
}

test_security_headers() {
    print_header "Additional Security: HTTP Security Headers"

    print_test "Test 1: Security headers on documentation pages"

    headers=$(curl -s -I "$BASE_URL/docs/backup-system/")

    # Check for critical security headers
    if echo "$headers" | grep -qi "X-Frame-Options"; then
        print_pass "X-Frame-Options header present"
    else
        print_fail "X-Frame-Options header missing"
    fi

    if echo "$headers" | grep -qi "X-Content-Type-Options"; then
        print_pass "X-Content-Type-Options header present"
    else
        print_fail "X-Content-Type-Options header missing"
    fi

    if echo "$headers" | grep -qi "Content-Security-Policy"; then
        print_pass "Content-Security-Policy header present"
    else
        print_fail "Content-Security-Policy header missing"
    fi
}

###############################################################################
# Main Execution
###############################################################################

main() {
    echo "==========================================================================="
    echo "Security Test Suite - PR #723 Fixes"
    echo "==========================================================================="
    echo "Target: $BASE_URL"
    echo "Date: $(date)"
    echo ""

    # Run test suites
    test_bearer_token
    test_session_fixation
    test_rate_limiting
    test_security_headers

    # Summary
    print_header "Test Summary"
    echo -e "Tests Passed: ${GREEN}$PASSED${NC}"
    echo -e "Tests Failed: ${RED}$FAILED${NC}"
    echo ""

    if [ $FAILED -eq 0 ]; then
        echo -e "${GREEN}✓ All tests passed!${NC}"
        exit 0
    else
        echo -e "${RED}✗ Some tests failed!${NC}"
        exit 1
    fi
}

# Run tests
main
