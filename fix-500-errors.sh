#!/bin/bash

echo "=== FIXING 500 ERRORS ==="
echo ""

# 1. Fix permissions
echo "1. Fixing permissions..."
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
sudo chmod -R 775 public/js
echo "✅ Permissions fixed"
echo ""

# 2. Clear all caches
echo "2. Clearing all caches..."
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
echo "✅ Caches cleared"
echo ""

# 3. Regenerate autoload
echo "3. Regenerating autoload..."
composer dump-autoload
echo "✅ Autoload regenerated"
echo ""

# 4. Restart services
echo "4. Restarting services..."
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx
echo "✅ Services restarted"
echo ""

# 5. Create missing JS files to prevent 404s
echo "5. Creating placeholder JS files..."
mkdir -p public/admin/js
touch public/admin/js/portal-universal-fix.js
touch public/admin/js/emergency-framework-loader.js
touch public/admin/js/widget-display-fix.js
echo "✅ Placeholder files created"
echo ""

# 6. Check Laravel log
echo "6. Recent errors in Laravel log:"
tail -20 storage/logs/laravel.log | grep -i "error\|exception" || echo "No recent errors found"
echo ""

echo "=== FIX COMPLETE ==="
echo ""
echo "Next steps:"
echo "1. Clear browser cache (Ctrl+F5)"
echo "2. Try accessing /admin/appointments"
echo "3. Try accessing /business/login"
echo ""
echo "If still getting 500 errors, check:"
echo "- storage/logs/laravel.log"
echo "- /var/log/nginx/error.log"