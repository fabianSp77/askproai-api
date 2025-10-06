#!/bin/bash
# Fix PHP-FPM cache issues when environment variables change
# Created: 2025-09-25

echo "🔧 PHP-FPM Cache Fix Script"
echo "=========================="
echo "This script fixes issues when PHP-FPM caches old environment variables"

# Clear all Laravel caches
echo "1. Clearing Laravel caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Remove bootstrap cache files
echo "2. Removing bootstrap cache..."
rm -f bootstrap/cache/*.php

# Clear opcache if available
echo "3. Clearing PHP opcache..."
php -r "if(function_exists('opcache_reset')) { opcache_reset(); echo 'Opcache cleared'; }"

# Restart PHP-FPM to reload environment
echo "4. Restarting PHP-FPM..."
systemctl restart php8.3-fpm

# Test database connection
echo "5. Testing database connection..."
php artisan tinker --execute="DB::select('SELECT 1'); echo 'Database: OK';" 2>&1 | grep -q "OK" && echo "✅ Database connection successful" || echo "❌ Database connection failed"

echo ""
echo "✅ Fix complete! Test your application now."