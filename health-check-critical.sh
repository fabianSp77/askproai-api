#\!/bin/bash

echo "=== CRITICAL SYSTEMS HEALTH CHECK ==="
echo "Date: $(date)"

echo -e "\n1. Business Portal Login Test"
response=$(curl -s -o /dev/null -w "%{http_code}" -X POST https://api.askproai.de/business/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"demo@askproai.de","password":"DemoPass2024\!"}')
  
if [ "$response" = "200" ]; then
    echo "✅ Business Portal Login: OK (HTTP $response)"
else
    echo "❌ Business Portal Login: FAILED (HTTP $response)"
fi

echo -e "\n2. Admin Portal Access Test"
admin_response=$(curl -s -o /dev/null -w "%{http_code}" https://api.askproai.de/admin/login)
if [ "$admin_response" = "200" ]; then
    echo "✅ Admin Portal: Accessible (HTTP $admin_response)"
else
    echo "❌ Admin Portal: Not accessible (HTTP $admin_response)"
fi

echo -e "\n3. Database Connection"
php -r "
try {
    \$pdo = new PDO('mysql:host=127.0.0.1;dbname=askproai_db', 'askproai_user', 'lkZ57Dju9EDjrMxn');
    echo '✅ Database: Connected' . PHP_EOL;
    \$calls = \$pdo->query('SELECT COUNT(*) FROM calls')->fetchColumn();
    \$appointments = \$pdo->query('SELECT COUNT(*) FROM appointments')->fetchColumn();
    echo '   - Calls: ' . \$calls . PHP_EOL;
    echo '   - Appointments: ' . \$appointments . PHP_EOL;
} catch (Exception \$e) {
    echo '❌ Database: FAILED - ' . \$e->getMessage() . PHP_EOL;
}
"

echo -e "\n4. Horizon Queue Status"
horizon_status=$(php artisan horizon:status 2>&1)
if echo "$horizon_status"  < /dev/null |  grep -q "Horizon is running"; then
    echo "✅ Horizon: Running"
else
    echo "⚠️  Horizon: Not running or status unknown"
fi

echo -e "\n5. Recent Laravel Errors (last 5)"
if [ -f storage/logs/laravel.log ]; then
    error_count=$(grep -c "ERROR\|Exception" storage/logs/laravel.log || echo "0")
    echo "Found $error_count total errors in log"
    echo "Last 5 errors:"
    grep -E "ERROR|Exception" storage/logs/laravel.log | tail -5 || echo "No recent errors found"
else
    echo "Log file not found"
fi

echo -e "\n6. System Resources"
echo "Disk Usage:"
df -h | grep -E "/$|Filesystem"
echo -e "\nMemory Usage:"
free -h | head -2

echo -e "\n=== END HEALTH CHECK ==="
echo "Next steps: Check CRITICAL_ISSUES_DEBUG_GUIDE_2025-07-22.md for detailed debugging"
