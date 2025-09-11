#!/bin/bash
# Auto-fix Laravel cache issues - SAFE VERSION
# This script automatically fixes common cache-related errors
# IMPORTANT: Always runs PHP commands as www-data to prevent ownership issues

echo "🔧 Auto-fixing Laravel cache issues (Safe Mode)..."

# Navigate to project directory
cd /var/www/api-gateway

# 1. Clear all view cache files (as www-data)
echo "→ Clearing view cache..."
sudo -u www-data rm -rf storage/framework/views/*
sudo -u www-data php artisan view:clear

# 2. Clear bootstrap cache (as www-data)
echo "→ Clearing bootstrap cache..."
sudo -u www-data rm -rf bootstrap/cache/*

# 3. Clear all Laravel caches (as www-data)
echo "→ Clearing all Laravel caches..."
sudo -u www-data php artisan optimize:clear

# 4. Fix permissions and ownership
echo "→ Fixing permissions and ownership..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 5. Clear PHP OPcache (as www-data)
echo "→ Clearing OPcache..."
sudo -u www-data php -r "if(function_exists('opcache_reset')) { opcache_reset(); echo 'OPcache cleared' . PHP_EOL; }"

# 6. Restart PHP-FPM
echo "→ Restarting PHP-FPM..."
systemctl restart php8.3-fpm

# 7. Create .gitignore files (as www-data)
echo "→ Creating .gitignore files..."
sudo -u www-data bash -c 'echo -e "*\n!.gitignore" > storage/framework/views/.gitignore'
sudo -u www-data bash -c 'echo -e "*\n!.gitignore" > storage/framework/cache/.gitignore'
sudo -u www-data bash -c 'echo -e "*\n!.gitignore" > storage/framework/sessions/.gitignore'

# 8. Verify ownership (NEW)
echo "→ Verifying ownership..."
ROOT_COUNT=$(find storage/framework/views -type f -user root 2>/dev/null | wc -l)
if [ "$ROOT_COUNT" -gt 0 ]; then
    echo "⚠️  WARNING: Found $ROOT_COUNT root-owned files! Fixing..."
    chown -R www-data:www-data storage/framework/views/
else
    echo "✅ All files correctly owned by www-data"
fi

echo "✅ Cache issues fixed!"
echo ""
echo "If errors persist, check:"
echo "• Disk space: df -h"
echo "• Inode usage: df -i"
echo "• Laravel logs: tail -50 storage/logs/laravel.log"