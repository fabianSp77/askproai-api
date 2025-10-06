#!/bin/bash

# Clear ALL Laravel caches to fix 500 errors
echo "🧹 Clearing all caches..."

# Remove corrupted view cache
rm -rf /var/www/api-gateway/storage/framework/views/*
echo "✅ View cache cleared"

# Clear all Laravel caches
cd /var/www/api-gateway
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
php artisan optimize:clear
echo "✅ Laravel caches cleared"

# DO NOT CACHE CONFIG IN DEVELOPMENT!
# This causes the old password to be cached!
# php artisan config:cache # REMOVED - causes 500 errors!
# php artisan route:cache  # REMOVED - not needed
# php artisan view:cache   # REMOVED - not needed
echo "✅ Caches cleared (NOT cached again)"

# Fix permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
echo "✅ Permissions fixed"

echo "✨ Cache clearing complete!"