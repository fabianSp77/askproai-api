#!/bin/bash

echo "=== Manual Login Test ==="
echo ""

# Step 1: Get login page
echo "1. Getting login page..."
curl -s -c cookies.txt https://api.askproai.de/business/login > login.html

# Extract CSRF token
CSRF_TOKEN=$(grep -oP 'name="_token" value="\K[^"]+' login.html | head -1)
echo "   CSRF Token: ${CSRF_TOKEN:0:20}..."

# Step 2: Login
echo ""
echo "2. Attempting login..."
RESPONSE=$(curl -s -L -b cookies.txt -c cookies.txt \
  -X POST https://api.askproai.de/business/login \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "Accept: text/html,application/xhtml+xml" \
  -d "_token=$CSRF_TOKEN" \
  -d "email=demo@askproai.de" \
  -d "password=password" \
  -w "\nHTTP_CODE:%{http_code}\nURL_EFFECTIVE:%{url_effective}\n")

# Extract status
HTTP_CODE=$(echo "$RESPONSE" | grep "HTTP_CODE:" | cut -d: -f2)
URL_EFFECTIVE=$(echo "$RESPONSE" | grep "URL_EFFECTIVE:" | cut -d: -f2-)

echo "   HTTP Code: $HTTP_CODE"
echo "   Final URL: $URL_EFFECTIVE"

# Check for error message
if echo "$RESPONSE" | grep -q "Die angegebenen Zugangsdaten sind ungültig"; then
    echo "   ❌ Error: Invalid credentials message found"
elif [[ "$URL_EFFECTIVE" == *"business/dashboard"* ]]; then
    echo "   ✅ Success: Redirected to dashboard"
else
    echo "   Status: Unknown"
fi

# Step 3: Test authenticated access
echo ""
echo "3. Testing authenticated access..."
API_RESPONSE=$(curl -s -b cookies.txt https://api.askproai.de/business/api/user \
  -H "Accept: application/json" \
  -w "\nHTTP_CODE:%{http_code}")

API_CODE=$(echo "$API_RESPONSE" | grep "HTTP_CODE:" | cut -d: -f2)
echo "   API Response Code: $API_CODE"

if [[ "$API_CODE" == "200" ]]; then
    echo "   ✅ Authenticated successfully"
    echo "$API_RESPONSE" | grep -v "HTTP_CODE" | jq '.' 2>/dev/null || echo "   (JSON parsing failed)"
else
    echo "   ❌ Not authenticated"
fi

# Clean up
rm -f cookies.txt login.html