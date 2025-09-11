#!/bin/bash

echo "Fixing specific view cache issue..."

# Clear all Laravel caches
php artisan view:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Remove all view cache files
rm -rf /tmp/laravel-views/*

# Recreate directory with proper permissions
mkdir -p /tmp/laravel-views
chown -R www-data:www-data /tmp/laravel-views
chmod -R 775 /tmp/laravel-views

# Also clear bootstrap cache
rm -rf /var/www/api-gateway/bootstrap/cache/*.php

# Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set final permissions
chown -R www-data:www-data /var/www/api-gateway/bootstrap/cache
chown -R www-data:www-data /var/www/api-gateway/storage
chown -R www-data:www-data /tmp/laravel-views

# Restart PHP-FPM to clear opcache
service php8.3-fpm restart

echo "View cache issue fixed!"