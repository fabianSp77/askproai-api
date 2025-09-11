#!/bin/bash

echo "🚀 ULTIMATE VIEW CACHE FIX - PERMANENT SOLUTION"
echo "================================================"

# Step 1: Stop web server to prevent concurrent access
echo "→ Stopping services..."
service nginx stop
service php8.3-fpm stop

# Step 2: Clear ALL caches completely
echo "→ Clearing all caches..."
rm -rf /var/www/api-gateway/storage/framework/views/*
rm -rf /var/www/api-gateway/storage/framework/cache/*
rm -rf /var/www/api-gateway/storage/framework/sessions/*
rm -rf /var/www/api-gateway/bootstrap/cache/*
rm -rf /tmp/laravel-views/*

# Step 3: Recreate directories with proper permissions
echo "→ Creating cache directories..."
mkdir -p /var/www/api-gateway/storage/framework/views
mkdir -p /var/www/api-gateway/storage/framework/cache
mkdir -p /var/www/api-gateway/storage/framework/sessions
mkdir -p /var/www/api-gateway/bootstrap/cache
mkdir -p /tmp/laravel-views

# Step 4: Set ownership to www-data
echo "→ Setting permissions..."
chown -R www-data:www-data /var/www/api-gateway/storage
chown -R www-data:www-data /var/www/api-gateway/bootstrap/cache
chown -R www-data:www-data /tmp/laravel-views
chmod -R 775 /var/www/api-gateway/storage
chmod -R 775 /var/www/api-gateway/bootstrap/cache
chmod -R 775 /tmp/laravel-views

# Step 5: Clear Laravel caches
echo "→ Clearing Laravel caches..."
cd /var/www/api-gateway
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan view:clear

# Step 6: Rebuild all caches
echo "→ Rebuilding caches..."
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
sudo -u www-data php artisan filament:optimize

# Step 7: Clear OPcache
echo "→ Clearing OPcache..."
echo '<?php opcache_reset(); echo "OPcache reset\n";' | php

# Step 8: Restart services
echo "→ Starting services..."
service php8.3-fpm start
service nginx start

# Step 9: Warm up cache
echo "→ Warming up cache..."
sleep 2
curl -s -o /dev/null https://api.askproai.de/admin/login
curl -s -o /dev/null https://api.askproai.de/admin

echo ""
echo "✅ ULTIMATE FIX COMPLETE!"
echo "The view cache has been completely rebuilt with proper permissions."
echo ""

# Run health check
php artisan view:health-check