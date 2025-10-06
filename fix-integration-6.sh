#!/bin/bash
echo "🔧 FIXING INTEGRATION 6 COMPREHENSIVE"
echo "======================================"
echo ""

echo "1. Clearing ALL caches..."
cd /var/www/api-gateway
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
rm -rf storage/framework/views/*
rm -rf bootstrap/cache/*.php

echo ""
echo "2. Checking Integration 6 in database..."
php artisan tinker --execute="
\$integration = App\Models\Integration::find(6);
if (\$integration) {
    echo 'Found Integration 6: ' . \$integration->name . PHP_EOL;
    echo 'Type: ' . \$integration->integrable_type . PHP_EOL;
    echo 'ID: ' . \$integration->integrable_id . PHP_EOL;
    
    // Check if the morphed relationship exists
    if (\$integration->integrable_type && \$integration->integrable_id) {
        \$morphed = \$integration->integrable;
        if (\$morphed) {
            echo 'Morphed to: ' . get_class(\$morphed) . ' ID: ' . \$morphed->id . PHP_EOL;
        } else {
            echo 'WARNING: Morphed relationship broken!' . PHP_EOL;
            // Fix it by setting to null
            \$integration->integrable_type = null;
            \$integration->integrable_id = null;
            \$integration->save();
            echo 'Fixed: Set morphed relationship to null' . PHP_EOL;
        }
    }
} else {
    echo 'Integration 6 not found!' . PHP_EOL;
}
"

echo ""
echo "3. Optimizing..."
php artisan optimize:clear
COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload -o

echo ""
echo "4. Restarting services..."
supervisorctl restart all
systemctl restart php8.3-fpm
systemctl restart nginx

echo ""
echo "5. Testing Integration 6..."
sleep 3
status=$(curl -sI https://api.askproai.de/admin/integrations/6 2>/dev/null | head -1)
echo "Status: $status"

if [[ "$status" == *"302"* ]]; then
    echo "✅ Integration 6 is now working!"
else
    echo "⚠️ Still showing unexpected status"
fi
