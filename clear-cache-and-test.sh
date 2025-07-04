#!/bin/bash

echo "Clearing all caches..."
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

echo "Cache cleared. Test the wizard at:"
echo "https://api.askproai.de/admin/quick-setup-wizard-v2?company=1"