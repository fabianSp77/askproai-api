#!/bin/bash
# Deployment Fix Command Reference
# Run these commands AFTER deploying new code

set -e  # Exit on error

PROJECT_DIR="/var/www/api-gateway"
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')

echo "========================================================"
echo "DEPLOYMENT CACHE REFRESH"
echo "Started: $TIMESTAMP"
echo "========================================================"

cd "$PROJECT_DIR" || exit 1

# STEP 1: Clear OPCache
echo ""
echo "[1/4] Clearing OPCache..."
php -r 'if(function_exists("opcache_reset")) { opcache_reset(); echo "✓ OPCache cleared\n"; }' || echo "⚠ OPCache not available"

# STEP 2: Clear Laravel Caches
echo ""
echo "[2/4] Clearing Laravel caches..."
php artisan cache:clear
echo "✓ Application cache cleared"

php artisan view:clear
echo "✓ View cache cleared"

php artisan config:clear
echo "✓ Config cache cleared"

php artisan route:clear
echo "✓ Route cache cleared"

# STEP 3: Rebuild Config Cache
echo ""
echo "[3/4] Rebuilding configuration cache..."
php artisan config:cache
echo "✓ Config cache rebuilt"

# STEP 4: Reload PHP-FPM
echo ""
echo "[4/4] Reloading PHP-FPM..."
systemctl reload php8.3-fpm
echo "✓ PHP-FPM gracefully reloaded"

# VERIFICATION
sleep 2

echo ""
echo "========================================================"
echo "VERIFICATION"
echo "========================================================"

# Check file timestamps
echo ""
echo "File timestamps:"
stat "$PROJECT_DIR/app/Http/Controllers/RetellFunctionCallHandler.php" | grep Modify
stat "$PROJECT_DIR/bootstrap/cache/config.php" | grep Modify

# Check PHP-FPM processes
echo ""
echo "PHP-FPM status:"
WORKER_COUNT=$(ps aux | grep "php-fpm: pool" | grep -v grep | wc -l)
echo "Active workers: $WORKER_COUNT"

# Check OPCache
echo ""
echo "OPCache status:"
php -r '
$s = opcache_get_status();
echo "Enabled: " . ($s["opcache_enabled"] ? "YES" : "NO") . "\n";
echo "Files cached: " . $s["opcache_statistics"]["num_cached_scripts"] . "\n";
if ($s["opcache_statistics"]["hits"] + $s["opcache_statistics"]["misses"] > 0) {
    $rate = $s["opcache_statistics"]["hits"] / ($s["opcache_statistics"]["hits"] + $s["opcache_statistics"]["misses"]);
    echo "Hit rate: " . round($rate * 100, 1) . "%\n";
}
'

FINISH_TIME=$(date '+%Y-%m-%d %H:%M:%S')
echo ""
echo "========================================================"
echo "✓ DEPLOYMENT CACHE REFRESH COMPLETE"
echo "Finished: $FINISH_TIME"
echo "========================================================"
echo ""
echo "NEXT STEPS:"
echo "1. Run test call to verify fix is active"
echo "2. Check logs: tail -f $PROJECT_DIR/storage/logs/laravel-*.log"
echo "3. Look for '🔧 Function routing' messages"
echo "4. Verify no 'Call context not found' errors"
echo ""
