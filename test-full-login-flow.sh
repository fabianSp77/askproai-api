#!/bin/bash

echo "🧪 Testing Full Business Portal Login Flow"
echo ""

# Step 1: Get login page and extract CSRF token
echo "1️⃣ Getting CSRF token from login page..."
RESPONSE=$(curl -s -c cookies.txt https://api.askproai.de/business/login)
CSRF_TOKEN=$(echo "$RESPONSE" | grep -oP 'name="_token" value="\K[^"]+' | head -1)

if [ -z "$CSRF_TOKEN" ]; then
    echo "❌ Failed to get CSRF token"
    exit 1
fi

echo "✅ Got CSRF token: ${CSRF_TOKEN:0:20}..."

# Step 2: Get session cookie
SESSION_COOKIE=$(cat cookies.txt | grep askproai_portal_session | awk '{print $7}')
echo "✅ Got session cookie: ${SESSION_COOKIE:0:20}..."

# Step 3: Perform login
echo ""
echo "2️⃣ Performing login..."
LOGIN_RESPONSE=$(curl -s -X POST https://api.askproai.de/business/login \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "Accept: text/html,application/xhtml+xml" \
  -H "Cookie: askproai_portal_session=$SESSION_COOKIE" \
  -b cookies.txt \
  -c cookies.txt \
  -L \
  -d "_token=$CSRF_TOKEN&email=demo@askproai.de&password=password123" \
  -w "\nHTTP_CODE:%{http_code}\nREDIRECT_URL:%{redirect_url}\n")

# Extract HTTP code
HTTP_CODE=$(echo "$LOGIN_RESPONSE" | grep "HTTP_CODE:" | cut -d: -f2)
REDIRECT_URL=$(echo "$LOGIN_RESPONSE" | grep "REDIRECT_URL:" | cut -d: -f2-)

echo "✅ HTTP Response Code: $HTTP_CODE"
echo "✅ Redirect URL: $REDIRECT_URL"

# Step 4: Check if login was successful
if [[ "$LOGIN_RESPONSE" == *"business/dashboard"* ]] || [[ "$REDIRECT_URL" == *"business/dashboard"* ]]; then
    echo "✅ Login successful! Redirected to dashboard"
elif [[ "$LOGIN_RESPONSE" == *"business/two-factor"* ]]; then
    echo "⚠️  Login requires 2FA verification"
elif [[ "$LOGIN_RESPONSE" == *"ungültig"* ]] || [[ "$LOGIN_RESPONSE" == *"invalid"* ]]; then
    echo "❌ Login failed: Invalid credentials"
else
    echo "❓ Unexpected response. Checking for errors..."
    echo "$LOGIN_RESPONSE" | grep -E "(error|exception|500)" | head -5
fi

# Step 5: Test authenticated access
echo ""
echo "3️⃣ Testing authenticated access..."
AUTH_TEST=$(curl -s -b cookies.txt https://api.askproai.de/business/api/user \
  -H "Accept: application/json" \
  -w "\nHTTP_CODE:%{http_code}")

AUTH_CODE=$(echo "$AUTH_TEST" | grep "HTTP_CODE:" | cut -d: -f2)
echo "✅ Auth test HTTP code: $AUTH_CODE"

if [[ "$AUTH_CODE" == "200" ]]; then
    echo "✅ Successfully authenticated!"
    echo "$AUTH_TEST" | grep -v "HTTP_CODE" | jq '.' 2>/dev/null || echo "$AUTH_TEST"
else
    echo "❌ Not authenticated or API endpoint issue"
fi

echo ""
echo "✅ Test complete!"
rm -f cookies.txt