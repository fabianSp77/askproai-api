#!/bin/bash

# Retell API Key Rotation Script
# WICHTIG: Neuen Key zuerst im Retell Dashboard generieren!

echo "=== Retell API Key Rotation ==="
echo ""
echo "SCHRITT 1: Generiere neuen API Key im Retell Dashboard"
echo "  1. Gehe zu https://dashboard.retell.ai/settings/api-keys"
echo "  2. Klicke auf 'Create API Key'"
echo "  3. Kopiere den neuen Key"
echo ""
read -p "Neuer Retell API Key (key_...): " NEW_KEY

if [[ ! "$NEW_KEY" =~ ^key_ ]]; then
    echo "ERROR: API Key muss mit 'key_' beginnen"
    exit 1
fi

echo ""
echo "SCHRITT 2: Backup aktueller .env"
cp .env .env.backup-$(date +%Y%m%d-%H%M%S)

echo ""
echo "SCHRITT 3: Update .env"
sed -i "s/^RETELL_API_KEY=.*/RETELL_API_KEY=$NEW_KEY/" .env
sed -i "s/^DEFAULT_RETELL_API_KEY=.*/DEFAULT_RETELL_API_KEY=$NEW_KEY/" .env

echo ""
echo "SCHRITT 4: Cache leeren"
php artisan config:clear
php artisan cache:clear

echo ""
echo "SCHRITT 5: Test neue Konfiguration"
php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();
echo 'Neuer Key in ENV: ' . substr(env('RETELL_API_KEY'), 0, 10) . '...' . PHP_EOL;
"

echo ""
echo "SCHRITT 6: Horizon neustarten"
php artisan horizon:terminate
sleep 2
php artisan horizon &

echo ""
echo "✅ Rotation abgeschlossen!"
echo ""
echo "WICHTIG: Teste jetzt einen Anruf auf +493033081738"
echo "Falls Probleme: cp .env.backup-* .env"