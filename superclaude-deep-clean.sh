#!/bin/bash

echo "╔══════════════════════════════════════════════════════════════════════╗"
echo "║           SUPERCLAUDE DEEP CLEAN & REBUILD                            ║"
echo "╚══════════════════════════════════════════════════════════════════════╝"
echo ""

echo "▶ STEP 1: Stopping services"
echo "──────────────────────────────────────────────────────────────────────"
systemctl stop php8.3-fpm
systemctl stop nginx
echo "✓ Services stopped"
echo ""

echo "▶ STEP 2: Clearing ALL caches"
echo "──────────────────────────────────────────────────────────────────────"
# Laravel caches
rm -rf /var/www/api-gateway/storage/framework/cache/*
rm -rf /var/www/api-gateway/storage/framework/sessions/*
rm -rf /var/www/api-gateway/storage/framework/views/*
rm -rf /var/www/api-gateway/bootstrap/cache/*
echo "✓ Laravel caches cleared"

# PHP OPcache
rm -rf /var/cache/php-opcache/*
echo "✓ PHP OPcache cleared"

# System temp files
rm -rf /tmp/php*
rm -rf /tmp/sess_*
echo "✓ Temp files cleared"
echo ""

echo "▶ STEP 3: Fixing permissions"
echo "──────────────────────────────────────────────────────────────────────"
chown -R www-data:www-data /var/www/api-gateway/storage
chown -R www-data:www-data /var/www/api-gateway/bootstrap/cache
chmod -R 775 /var/www/api-gateway/storage
chmod -R 775 /var/www/api-gateway/bootstrap/cache
echo "✓ Permissions fixed"
echo ""

echo "▶ STEP 4: Starting services"
echo "──────────────────────────────────────────────────────────────────────"
systemctl start php8.3-fpm
systemctl start nginx
echo "✓ Services started"
echo ""

echo "▶ STEP 5: Rebuilding Laravel caches"
echo "──────────────────────────────────────────────────────────────────────"
cd /var/www/api-gateway
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
php artisan filament:clear-cached-components
echo "✓ Caches cleared"

# DO NOT CACHE CONFIG IN DEVELOPMENT!
# php artisan config:cache # REMOVED - causes 500 errors with old passwords!
# php artisan route:cache  # REMOVED - not needed
# php artisan view:cache   # REMOVED - not needed
php artisan filament:cache-components
echo "✓ Component cache rebuilt (config cache NOT rebuilt to prevent errors)"
echo ""

echo "▶ STEP 6: Testing the application"
echo "──────────────────────────────────────────────────────────────────────"
php artisan tinker --execute="echo 'Database: ' . (DB::connection()->getPdo() ? 'OK' : 'FAILED');"
curl -s -o /dev/null -w "HTTP Status: %{http_code}\n" http://localhost/api/health
echo ""

echo "╔══════════════════════════════════════════════════════════════════════╗"
echo "║                     DEEP CLEAN COMPLETED                              ║"
echo "╚══════════════════════════════════════════════════════════════════════╝"
