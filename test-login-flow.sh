#!/bin/bash

echo "=== TESTING PORTAL LOGIN FLOW ==="
echo

# Use a cookie jar to persist cookies between requests
COOKIE_JAR=$(mktemp)
BASE_URL="https://api.askproai.de"

# Step 1: Get login page and extract CSRF token
echo "1. Getting login page..."
LOGIN_PAGE=$(curl -s -c "$COOKIE_JAR" -k "$BASE_URL/business/login")
CSRF_TOKEN=$(echo "$LOGIN_PAGE" | grep -oP 'name="csrf-token" content="\K[^"]+')
echo "CSRF Token: ${CSRF_TOKEN:0:8}..."

# Step 2: Submit login form
echo -e "\n2. Submitting login..."
LOGIN_RESPONSE=$(curl -s -k -L -w "\n%{http_code}" -c "$COOKIE_JAR" -b "$COOKIE_JAR" \
    -X POST "$BASE_URL/business/login" \
    -H "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8" \
    -H "Content-Type: application/x-www-form-urlencoded" \
    -d "email=demo@askproai.de&password=password&_token=$CSRF_TOKEN")

HTTP_CODE=$(echo "$LOGIN_RESPONSE" | tail -n1)
echo "Login response code: $HTTP_CODE"

# Step 3: Check session debug
echo -e "\n3. Checking session after login..."
SESSION_DEBUG=$(curl -s -k -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
    -H "Accept: application/json" \
    "$BASE_URL/business/session-debug")

echo "$SESSION_DEBUG" | python3 -m json.tool 2>/dev/null || echo "$SESSION_DEBUG"

# Step 4: Check auth test view
echo -e "\n4. Checking auth test view..."
AUTH_TEST=$(curl -s -k -L -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
    -H "Accept: text/html" \
    "$BASE_URL/business/auth-test")

echo "$AUTH_TEST" | grep -E "(✅|❌|true|false)" | head -10

# Step 5: Try to access test dashboard
echo -e "\n5. Accessing test dashboard..."
TEST_DASHBOARD=$(curl -s -k -L -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
    -H "Accept: text/html" \
    "$BASE_URL/business/test-dashboard")

echo "$TEST_DASHBOARD" | head -20

# Step 6: Try to access React dashboard
echo -e "\n6. Accessing React dashboard..."
DASHBOARD_RESPONSE=$(curl -s -k -L -w "\n%{http_code}" -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
    -H "Accept: text/html" \
    "$BASE_URL/business/dashboard")

DASHBOARD_CODE=$(echo "$DASHBOARD_RESPONSE" | tail -n1)
echo "Dashboard response code: $DASHBOARD_CODE"

# Check if we got redirected to login
if echo "$DASHBOARD_RESPONSE" | grep -q "business/login"; then
    echo "❌ Redirected back to login page"
else
    echo "✅ Dashboard accessed successfully"
fi

# Cleanup
rm -f "$COOKIE_JAR"

echo -e "\n=== TEST COMPLETE ==="