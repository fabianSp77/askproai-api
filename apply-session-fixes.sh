#!/bin/bash

echo "=== Applying Session Fixes ==="

# 1. Clear all file-based sessions
echo "1. Clearing file-based sessions..."
rm -rf /var/www/api-gateway/storage/framework/sessions/*
rm -rf /var/www/api-gateway/storage/framework/sessions/admin/*

# 2. Clear Laravel caches
echo "2. Clearing all caches..."
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 3. Rebuild configuration cache
echo "3. Rebuilding configuration cache..."
php artisan config:cache

# 4. Restart PHP-FPM to ensure clean state
echo "4. Restarting PHP-FPM..."
sudo systemctl restart php8.3-fpm

# 5. Clear Redis session keys
echo "5. Clearing old Redis session keys..."
redis-cli --scan --pattern "askproai_cache_*" | xargs -L 1 redis-cli DEL

echo "=== Fixes Applied ==="
echo ""
echo "Next steps:"
echo "1. Visit https://api.askproai.de/admin in a fresh incognito window"
echo "2. Try to login with admin credentials"
echo "3. Check browser console for any errors"
echo ""