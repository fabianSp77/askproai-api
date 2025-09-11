#!/bin/bash

# Emergency View Cache Fix Script
# This script fixes Laravel view cache issues immediately

echo "========================================="
echo "Laravel View Cache Emergency Fix"
echo "========================================="

# 1. Clear all Laravel caches
echo "→ Clearing all Laravel caches..."
cd /var/www/api-gateway
php artisan view:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan optimize:clear

# 2. Ensure view cache directory exists with correct permissions
echo "→ Setting up view cache directory..."
VIEW_CACHE_DIR="/tmp/laravel-views"

# Create if doesn't exist
if [ ! -d "$VIEW_CACHE_DIR" ]; then
    mkdir -p "$VIEW_CACHE_DIR"
    echo "  Created view cache directory"
fi

# Set correct ownership and permissions
chown -R www-data:www-data "$VIEW_CACHE_DIR"
chmod -R 775 "$VIEW_CACHE_DIR"
echo "  Fixed permissions for $VIEW_CACHE_DIR"

# 3. Clear any orphaned view files
echo "→ Cleaning orphaned view files..."
find "$VIEW_CACHE_DIR" -type f -name "*.php" -mtime +7 -delete 2>/dev/null
echo "  Removed old view cache files"

# 4. Warm up the cache
echo "→ Warming up caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Fix storage permissions as well
echo "→ Fixing storage permissions..."
chown -R www-data:www-data /var/www/api-gateway/storage
chmod -R 775 /var/www/api-gateway/storage
chown -R www-data:www-data /var/www/api-gateway/bootstrap/cache
chmod -R 775 /var/www/api-gateway/bootstrap/cache

# 6. Restart PHP-FPM to clear opcache
echo "→ Restarting PHP-FPM..."
service php8.3-fpm restart

echo "========================================="
echo "✓ View cache emergency fix completed!"
echo "========================================="

# Check if the issue is resolved
if [ -d "$VIEW_CACHE_DIR" ] && [ -w "$VIEW_CACHE_DIR" ]; then
    echo "✓ View cache directory is accessible and writable"
else
    echo "⚠ Warning: View cache directory may still have issues"
    exit 1
fi