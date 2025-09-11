#!/bin/bash
# Performance Optimization Script for AskProAI

echo "🚀 Starting Performance Optimization..."
echo "=================================="

# 1. Clear all caches first
echo "🧹 Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 2. Optimize composer autoload
echo "📦 Optimizing Composer autoload..."
composer dump-autoload --optimize --no-dev

# 3. Rebuild and cache everything
echo "🔧 Rebuilding application caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Optimize database
echo "💾 Optimizing database..."
mysql -u askproai_user -p'Fm#23Lp19!x' askproai_db -e "
-- Analyze tables for better query planning
ANALYZE TABLE users, customers, appointments, calls, services, staff;

-- Optimize tables to reclaim space and defragment
OPTIMIZE TABLE users, customers, appointments, calls, services, staff;
" 2>/dev/null || echo "Database optimization skipped (manual run recommended)"

# 5. Redis optimization
echo "⚡ Optimizing Redis..."
redis-cli BGREWRITEAOF
redis-cli CONFIG SET maxmemory-policy allkeys-lru
redis-cli CONFIG SET maxmemory 512mb

# 6. PHP-FPM optimization
echo "🔄 Restarting PHP-FPM for fresh memory..."
sudo systemctl restart php8.3-fpm 2>/dev/null || echo "PHP-FPM restart requires sudo"

# 7. Simple cache warmup
echo "🔥 Running cache warmup..."
php artisan tinker --execute="
// Warm critical caches
Cache::remember('stats:total_users', 3600, fn() => DB::table('users')->count());
Cache::remember('stats:total_customers', 3600, fn() => DB::table('customers')->count());
Cache::remember('stats:total_calls', 3600, fn() => DB::table('calls')->count());
Cache::remember('stats:today_appointments', 600, fn() => 
    DB::table('appointments')
        ->whereDate('starts_at', today())
        ->count()
);

// Pre-cache common queries
\$recentCalls = Cache::remember('calls:recent:10', 600, fn() => 
    DB::table('calls')
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get()
);

\$activeServices = Cache::remember('services:active', 3600, fn() =>
    DB::table('services')
        ->orderBy('name')
        ->get()
);

echo 'Cache warmed successfully!';
"

# 8. Check performance metrics
echo ""
echo "📊 Performance Metrics:"
echo "----------------------"

# Redis stats
REDIS_INFO=$(redis-cli INFO stats | grep -E "keyspace_hits|keyspace_misses" | tr '\r' ' ')
echo "Redis: $REDIS_INFO"

# PHP memory
PHP_MEM=$(php -r "echo 'PHP Memory Limit: ' . ini_get('memory_limit');")
echo "$PHP_MEM"

# Database connections
MYSQL_CONN=$(mysql -u askproai_user -p'Fm#23Lp19!x' askproai_db -e "SHOW STATUS LIKE 'Threads_connected';" 2>/dev/null | tail -1)
echo "MySQL Connections: $MYSQL_CONN"

echo ""
echo "✅ Performance optimization complete!"
echo "=================================="
echo ""
echo "Recommendations:"
echo "1. Monitor response times for the next hour"
echo "2. Check error logs for any issues"
echo "3. Run load testing to validate improvements"
echo "4. Consider adding more Redis memory if hit rate < 60%"