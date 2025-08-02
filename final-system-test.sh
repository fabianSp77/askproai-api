#\!/bin/bash

echo "=== Final System Test ==="
echo "Testing critical functionality after cleanup..."

# 1. Test Business Portal authentication
echo -e "\n[1/5] Testing Business Portal login..."
response=$(curl -s -X POST https://api.askproai.de/business/api/auth/login \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d '{"email":"demo@askproai.de","password":"DemoPass2024\!"}' \
    2>/dev/null)

if echo "$response"  < /dev/null |  grep -q '"success":true'; then
    echo "✅ Business Portal login: PASS"
else
    echo "❌ Business Portal login: FAIL"
    echo "Response: $response"
fi

# 2. Test Admin Panel access
echo -e "\n[2/5] Testing Admin Panel..."
status=$(curl -s -o /dev/null -w "%{http_code}" https://api.askproai.de/admin/login)
if [ "$status" = "200" ]; then
    echo "✅ Admin Panel accessible: PASS"
else
    echo "❌ Admin Panel accessible: FAIL (Status: $status)"
fi

# 3. Test API endpoints
echo -e "\n[3/5] Testing API health endpoint..."
api_response=$(curl -s https://api.askproai.de/api/health)
if echo "$api_response" | grep -q "ok"; then
    echo "✅ API health check: PASS"
else
    echo "❌ API health check: FAIL"
fi

# 4. Test database connectivity
echo -e "\n[4/5] Testing database connection..."
php -r "
try {
    \$pdo = new PDO('mysql:host=127.0.0.1;dbname=askproai_db', 'askproai_user', 'lkZ57Dju9EDjrMxn');
    echo '✅ Database connection: PASS' . PHP_EOL;
} catch (Exception \$e) {
    echo '❌ Database connection: FAIL' . PHP_EOL;
    echo 'Error: ' . \$e->getMessage() . PHP_EOL;
}
"

# 5. Test Horizon status
echo -e "\n[5/5] Testing Horizon queue system..."
horizon_status=$(php artisan horizon:status 2>&1)
if echo "$horizon_status" | grep -q "Horizon is running"; then
    echo "✅ Horizon status: PASS"
else
    echo "⚠️  Horizon status: NOT RUNNING (may need to start)"
fi

echo -e "\n=== System Test Complete ==="
