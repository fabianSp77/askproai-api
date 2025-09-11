#!/bin/bash

echo "EMERGENCY FIX: Disabling view cache and fixing permissions..."

# Stop services
service php8.3-fpm stop
service nginx stop

# Complete cache purge
rm -rf /var/www/api-gateway/storage/framework/views/*
rm -rf /var/www/api-gateway/storage/framework/cache/*
rm -rf /var/www/api-gateway/storage/framework/sessions/*
rm -rf /var/www/api-gateway/bootstrap/cache/*

# Recreate directories with proper permissions
mkdir -p /var/www/api-gateway/storage/framework/{views,cache,sessions}
mkdir -p /var/www/api-gateway/bootstrap/cache

# Set ownership before starting services
chown -R www-data:www-data /var/www/api-gateway/storage
chown -R www-data:www-data /var/www/api-gateway/bootstrap/cache

# Set permissions with setgid
chmod -R 2775 /var/www/api-gateway/storage
chmod -R 2775 /var/www/api-gateway/bootstrap/cache

# Start services
service php8.3-fpm start
service nginx start

# Clear Laravel caches as www-data
cd /var/www/api-gateway
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan view:clear

# Disable opcache temporarily
echo "opcache.enable=0" > /etc/php/8.3/fpm/conf.d/99-disable-opcache.ini
service php8.3-fpm reload

echo "Emergency fix completed. View caching temporarily disabled."