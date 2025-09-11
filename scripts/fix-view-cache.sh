#!/bin/bash
# Fix view cache issues for Laravel/Filament
# Run this script when encountering 500 errors with view cache

echo "🔧 Fixing Laravel view cache issues..."

# Stop services
echo "Stopping services..."
sudo systemctl stop php8.3-fpm

# Clear all caches
echo "Clearing all caches..."
sudo rm -rf /var/www/api-gateway/storage/framework/views/*
sudo rm -rf /var/www/api-gateway/storage/framework/cache/*
sudo rm -rf /var/www/api-gateway/bootstrap/cache/*.php

# Recreate directories
echo "Recreating cache directories..."
sudo mkdir -p /var/www/api-gateway/storage/framework/views
sudo mkdir -p /var/www/api-gateway/storage/framework/cache
sudo mkdir -p /var/www/api-gateway/storage/framework/sessions

# Set ownership and permissions
echo "Setting correct ownership..."
sudo chown -R www-data:www-data /var/www/api-gateway/storage/
sudo chown -R www-data:www-data /var/www/api-gateway/bootstrap/cache/
sudo chmod -R 775 /var/www/api-gateway/storage/
sudo chmod -R 775 /var/www/api-gateway/bootstrap/cache/

# Clear Laravel caches as www-data
echo "Clearing Laravel caches..."
cd /var/www/api-gateway
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan view:clear
sudo -u www-data php artisan optimize:clear

# Clear Redis
echo "Flushing Redis cache..."
redis-cli FLUSHALL > /dev/null

# Restart services
echo "Restarting services..."
sudo systemctl start php8.3-fpm
sudo systemctl restart nginx

# Rebuild caches
echo "Rebuilding caches..."
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan filament:cache-components

echo "✅ View cache issues fixed!"
echo ""
echo "If the issue persists, check:"
echo "  - Laravel logs: tail -f storage/logs/laravel.log"
echo "  - PHP-FPM logs: tail -f /var/log/php8.3-fpm.log"
echo "  - Nginx logs: tail -f /var/log/nginx/error.log"