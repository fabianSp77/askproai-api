#!/bin/bash

echo "==================================="
echo "AGGRESSIVE FIX FOR 500 ERROR"
echo "==================================="

# 1. Kill all PHP and web server processes
echo "1. Killing all PHP processes..."
killall -9 php-fpm 2>/dev/null || true
killall -9 php 2>/dev/null || true
sleep 2

# 2. Clear EVERYTHING
echo "2. Clearing ALL caches and sessions..."
cd /var/www/api-gateway

# Database sessions
mysql -u root -p'tL34!kLm#2)K' askpro -e "TRUNCATE TABLE sessions;" 2>/dev/null || true
mysql -u root -p'tL34!kLm#2)K' askpro -e "TRUNCATE TABLE cache;" 2>/dev/null || true
mysql -u root -p'tL34!kLm#2)K' askpro -e "TRUNCATE TABLE cache_locks;" 2>/dev/null || true

# File sessions and cache
rm -rf storage/framework/sessions/*
rm -rf storage/framework/cache/*
rm -rf storage/framework/views/*
rm -rf storage/framework/livewire-components/*
rm -rf storage/framework/livewire-tmp/*
rm -rf bootstrap/cache/*
rm -rf /var/lib/php/sessions/*

# Redis cache
redis-cli FLUSHALL 2>/dev/null || true

# 3. Clear Laravel caches
php artisan cache:clear --force
php artisan config:clear
php artisan route:clear 
php artisan view:clear
php artisan optimize:clear
php artisan livewire:discover

# 4. Rebuild everything
echo "3. Rebuilding caches..."
composer dump-autoload -o
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:cache-components

# 5. Fix permissions
echo "4. Fixing permissions..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# 6. Restart all services
echo "5. Restarting services..."
systemctl restart php8.3-fpm
systemctl restart nginx
systemctl restart redis-server
systemctl restart mysql

sleep 3

echo "==================================="
echo "AGGRESSIVE FIX COMPLETE"
echo "==================================="
