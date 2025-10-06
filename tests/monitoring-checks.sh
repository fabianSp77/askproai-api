#!/usr/bin/env bash
set -euo pipefail

echo "======================================================"
echo "MONITORING CHECKS"
echo "======================================================"

# ---- 1. Routes Check ----
echo -e "\n== 1. Route Summary =="
echo "Total Routes: $(php artisan route:list --json 2>/dev/null | jq 'length')"
echo "API Routes: $(php artisan route:list --json 2>/dev/null | jq '[.[] | select(.uri | startswith("api"))] | length')"
echo "V2 Routes: $(php artisan route:list --json 2>/dev/null | jq '[.[] | select(.uri | startswith("api/v2"))] | length')"

echo -e "\n== 2. V2 Route List =="
php artisan route:list --path=v2 2>/dev/null | head -20

# ---- 2. Queue Status ----
echo -e "\n== 3. Queue Status =="
echo "Failed Jobs:"
php artisan queue:failed --raw 2>/dev/null | jq 'length' || echo "0"

echo -e "\nRecent Failed Jobs (if any):"
php artisan queue:failed 2>/dev/null | head -5 || echo "No failed jobs"

# ---- 3. Redis Status ----
echo -e "\n== 4. Redis Status =="
redis-cli ping 2>/dev/null && echo "Redis: ✅ Running" || echo "Redis: ❌ Not running"
redis-cli info stats 2>/dev/null | grep -E "^(total_connections_received|instantaneous_ops_per_sec|used_memory_human):" || true

# ---- 4. Database Status ----
echo -e "\n== 5. Database Status =="
php artisan tinker --execute="
    \$companies = \App\Models\Company::count();
    \$branches = \App\Models\Branch::count();
    \$services = \App\Models\Service::count();
    \$appointments = \App\Models\Appointment::count();
    echo \"Companies: \$companies, Branches: \$branches, Services: \$services, Appointments: \$appointments\";
" 2>/dev/null || echo "Database check failed"

# ---- 5. Recent Logs (sanitized) ----
echo -e "\n== 6. Recent Log Entries (last 20, sanitized) =="
if [ -f storage/logs/laravel.log ]; then
    tail -n 20 storage/logs/laravel.log | \
    sed -E 's/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/[email]/g' | \
    sed -E 's/\+49[0-9]{9,}/[phone]/g' | \
    sed -E 's/Bearer [A-Za-z0-9._-]+/Bearer [token]/g'
else
    echo "No log file found"
fi

# ---- 6. Performance Metrics ----
echo -e "\n== 7. Performance Metrics =="
php artisan tinker --execute="
    \$avgResponseTime = \Cache::get('average_response_time', 'N/A');
    \$totalRequests = \Cache::get('total_requests', 0);
    \$slowRequests = \Cache::get('slow_requests', 0);
    echo \"Avg Response Time: \$avgResponseTime ms, Total Requests: \$totalRequests, Slow Requests: \$slowRequests\";
" 2>/dev/null || echo "No metrics available"

# ---- 7. Feature Flag Status ----
echo -e "\n== 8. Feature Flag Status =="
grep -E '^FEATURE_' .env | grep -v "SECRET\|KEY\|PASSWORD" | sort

# ---- 8. Service Health ----
echo -e "\n== 9. Service Health =="
echo -n "Nginx: "
systemctl is-active nginx 2>/dev/null && echo "✅ Running" || echo "❌ Not running"

echo -n "PHP-FPM: "
systemctl is-active php8.3-fpm 2>/dev/null && echo "✅ Running" || echo "❌ Not running"

echo -n "MySQL: "
systemctl is-active mysql 2>/dev/null && echo "✅ Running" || echo "❌ Not running"

echo -n "Redis: "
systemctl is-active redis 2>/dev/null && echo "✅ Running" || echo "❌ Not running"

# ---- 9. Disk Usage ----
echo -e "\n== 10. Disk Usage =="
df -h / | grep -v Filesystem
echo ""
echo "Storage Directory:"
du -sh storage/* 2>/dev/null | head -10

echo -e "\n======================================================"
echo "✅ MONITORING CHECKS COMPLETED"
echo "======================================================