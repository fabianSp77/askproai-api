#!/bin/bash

echo "=== Testing JSON API Login ===="
echo ""

URL="https://api.askproai.de/admin-v2/api/login"
COOKIE_JAR="/tmp/api-login.txt"

# Get CSRF from login-api page
rm -f $COOKIE_JAR
echo "1. Getting CSRF token..."
LOGIN_PAGE=$(curl -s -c $COOKIE_JAR "https://api.askproai.de/admin-v2/login-api")
CSRF=$(echo "$LOGIN_PAGE" | grep -oP 'name="csrf-token" content="\K[^"]+' | head -1)

echo "   CSRF: ${CSRF:0:20}..."
echo ""

# Test JSON login
echo "2. Testing JSON API login..."
RESPONSE=$(curl -s -w "\nHTTP_CODE: %{http_code}" \
    -X POST \
    -b $COOKIE_JAR \
    -c $COOKIE_JAR \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "X-CSRF-TOKEN: $CSRF" \
    -d '{"email":"fabian@askproai.de","password":"Fl3ischmann!"}' \
    "$URL")

HTTP_CODE=$(echo "$RESPONSE" | grep "HTTP_CODE:" | cut -d' ' -f2)
JSON_RESPONSE=$(echo "$RESPONSE" | grep -v "HTTP_CODE:")

echo "   HTTP Status: $HTTP_CODE"
echo "   Response: $JSON_RESPONSE" | head -200
echo ""

# Parse success from JSON
if echo "$JSON_RESPONSE" | grep -q '"success":true'; then
    echo "✅ JSON Login successful!"
    
    # Extract session ID from response
    SESSION_ID=$(echo "$JSON_RESPONSE" | grep -oP '"session_id":"\K[^"]+' || echo "not found")
    echo "   Session ID: $SESSION_ID"
    
    # Test authenticated access
    echo ""
    echo "3. Testing authenticated access to dashboard..."
    DASHBOARD_STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b $COOKIE_JAR "https://api.askproai.de/admin-v2/dashboard")
    echo "   Dashboard Status: $DASHBOARD_STATUS"
    
    # Test API check endpoint
    echo ""
    echo "4. Testing API check endpoint..."
    CHECK_RESPONSE=$(curl -s -b $COOKIE_JAR -H "Accept: application/json" "https://api.askproai.de/admin-v2/api/check")
    echo "   Check Response: $CHECK_RESPONSE"
else
    echo "❌ JSON Login failed"
fi

rm -f $COOKIE_JAR