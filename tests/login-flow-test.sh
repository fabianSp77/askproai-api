#!/bin/bash

echo "=== Complete Login Flow Test ==="
echo "================================"
echo ""

COOKIE_JAR="/tmp/login-cookies.txt"
BASE_URL="https://api.askproai.de"

# Clean up old cookies
rm -f $COOKIE_JAR

echo "1. GET Login Page:"
LOGIN_RESPONSE=$(curl -c $COOKIE_JAR -s -w "\nHTTP_CODE:%{http_code}\n" -k "$BASE_URL/admin/login")
LOGIN_CODE=$(echo "$LOGIN_RESPONSE" | grep "HTTP_CODE:" | cut -d: -f2)
echo "   Status: $LOGIN_CODE"

# Extract XSRF token from cookies
XSRF_TOKEN=$(grep "XSRF-TOKEN" $COOKIE_JAR | awk '{print $7}')
echo "   XSRF Token: ${XSRF_TOKEN:0:20}..."

# Extract session cookie
SESSION=$(grep "askpro_ai_gateway_session" $COOKIE_JAR | awk '{print $7}')
echo "   Session: ${SESSION:0:20}..."

echo ""
echo "2. Attempting Dashboard Access (without auth):"
DASHBOARD_CODE=$(curl -b $COOKIE_JAR -s -o /dev/null -w "%{http_code}" -k -L "$BASE_URL/admin")
echo "   Status: $DASHBOARD_CODE (Expected: 302 redirect to login)"

echo ""
echo "3. Testing authenticated session:"
# Create test session manually
php artisan tinker --execute="
\$user = \App\Models\User::find(6);
if (\$user) {
    // Create session
    \$sessionId = Str::random(40);

    // Store in session
    session(['user_id' => \$user->id]);
    Auth::login(\$user);

    echo 'User logged in: ' . Auth::check() . PHP_EOL;
    echo 'User ID: ' . Auth::id() . PHP_EOL;
}
"

echo ""
echo "4. Testing Livewire Update Endpoint:"
LIVEWIRE_CODE=$(curl -X POST "$BASE_URL/livewire/update" \
    -H "X-Livewire: true" \
    -H "X-XSRF-TOKEN: $XSRF_TOKEN" \
    -b $COOKIE_JAR \
    -s -o /dev/null -w "%{http_code}" -k)
echo "   Livewire update status: $LIVEWIRE_CODE"

echo ""
echo "5. Testing JavaScript Resources:"
JS_FILES=(
    "/vendor/livewire/livewire.js"
    "/js/livewire-fix.js"
    "/vendor/filament/filament.js"
)

for file in "${JS_FILES[@]}"; do
    code=$(curl -s -o /dev/null -w "%{http_code}" -k "$BASE_URL$file")
    echo "   $file: $code"
done

echo ""
echo "6. Checking for 500 errors in recent logs:"
ERRORS=$(tail -n 100 /var/www/api-gateway/storage/logs/laravel.log | grep -c "production.ERROR" || echo "0")
echo "   Recent errors in log: $ERRORS"

echo ""
echo "================================"
echo "Login Flow Test Complete"
echo "================================"