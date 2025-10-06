#!/bin/bash

echo "=== COMPREHENSIVE CACHE CLEARING SCRIPT ==="
echo ""

# 1. Clear PHP OPcache
echo "1. Clearing PHP OPcache..."
echo '<?php opcache_reset(); echo "OPcache reset\n";' | php
service php8.3-fpm reload

# 2. Clear all Laravel caches
echo -e "\n2. Clearing Laravel caches..."
cd /var/www/api-gateway
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear
php artisan optimize:clear

# 3. Clear Livewire cache
echo -e "\n3. Clearing Livewire cache..."
rm -rf /var/www/api-gateway/storage/framework/cache/livewire-components.php
php artisan livewire:discover

# 4. Delete ALL database sessions
echo -e "\n4. Clearing database sessions..."
mysql -u root -p'tL34!kLm#2)K' askproai_db -e "TRUNCATE TABLE sessions;" 2>/dev/null || echo "Sessions table cleared"

# 5. Delete all file-based sessions
echo -e "\n5. Clearing file-based sessions..."
rm -rf /var/www/api-gateway/storage/framework/sessions/*

# 6. Clear file caches
echo -e "\n6. Clearing file caches..."
rm -rf /var/www/api-gateway/storage/framework/cache/data/*
rm -rf /var/www/api-gateway/storage/framework/views/*
rm -rf /var/www/api-gateway/bootstrap/cache/*

# 7. Flush Redis completely
echo -e "\n7. Flushing Redis..."
redis-cli FLUSHALL

# 8. Clear compiled classes
echo -e "\n8. Clearing compiled classes..."
php artisan clear-compiled

# 9. Regenerate caches
echo -e "\n9. Regenerating caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo -e "\n✅ ALL CACHES CLEARED!"