#!/bin/bash

echo "=== Portal Login Flow Test ==="
echo

# Clean up old cookie file
rm -f /tmp/portal-cookies.txt

# 1. Get login page (to get CSRF token)
echo "1. Getting login page..."
LOGIN_PAGE=$(curl -s -c /tmp/portal-cookies.txt -L https://api.askproai.de/business/login)
CSRF_TOKEN=$(echo "$LOGIN_PAGE" | grep -oP '<input type="hidden" name="_token" value="\K[^"]+')
echo "   CSRF Token: ${CSRF_TOKEN:0:20}..."

# 2. Check session debug before login
echo
echo "2. Session status before login:"
curl -s -b /tmp/portal-cookies.txt -c /tmp/portal-cookies.txt \
  https://api.askproai.de/business/session-debug | \
  jq -r '.session.id, .auth.portal_check' 2>/dev/null || echo "No jq, raw output:"
curl -s -b /tmp/portal-cookies.txt -c /tmp/portal-cookies.txt \
  https://api.askproai.de/business/session-debug

# 3. Attempt login
echo
echo "3. Attempting login..."
LOGIN_RESPONSE=$(curl -s -i -b /tmp/portal-cookies.txt -c /tmp/portal-cookies.txt \
  -X POST https://api.askproai.de/business/login \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "Accept: text/html" \
  -d "_token=$CSRF_TOKEN" \
  -d "email=demo@askproai.de" \
  -d "password=password" \
  -L)

# Check for redirect
if echo "$LOGIN_RESPONSE" | grep -q "302 Found\|303 See Other"; then
    echo "   Login redirect detected (likely successful)"
else
    echo "   No redirect - checking response..."
    echo "$LOGIN_RESPONSE" | grep -A5 -B5 "error\|fehler" || echo "   No obvious errors found"
fi

# 4. Check session after login
echo
echo "4. Session status after login:"
SESSION_AFTER=$(curl -s -b /tmp/portal-cookies.txt -c /tmp/portal-cookies.txt \
  https://api.askproai.de/business/session-debug)
echo "$SESSION_AFTER" | jq -r '.auth.portal_check' 2>/dev/null || echo "$SESSION_AFTER"

# 5. Try accessing dashboard
echo
echo "5. Accessing dashboard..."
DASHBOARD_RESPONSE=$(curl -s -i -b /tmp/portal-cookies.txt \
  https://api.askproai.de/business/dashboard \
  -H "Accept: text/html")

if echo "$DASHBOARD_RESPONSE" | grep -q "302 Found.*login"; then
    echo "   ❌ Redirected to login - auth failed"
elif echo "$DASHBOARD_RESPONSE" | grep -q "dashboard\|Dashboard"; then
    echo "   ✅ Dashboard content found - auth successful"
else
    echo "   ⚠️  Unexpected response"
fi

# 6. Show cookies
echo
echo "6. Cookies stored:"
cat /tmp/portal-cookies.txt | grep -E "askproai|XSRF"

# Cleanup
rm -f /tmp/portal-cookies.txt