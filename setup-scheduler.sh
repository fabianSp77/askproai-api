#!/bin/bash

echo "🕐 Einrichten des Laravel Schedulers..."

# Erstelle Log-Verzeichnis falls nicht vorhanden
mkdir -p /var/www/api-gateway/storage/logs

# Füge Crontab-Eintrag hinzu
(crontab -l 2>/dev/null; echo "* * * * * cd /var/www/api-gateway && php artisan schedule:run >> /dev/null 2>&1") | crontab -

echo "✅ Scheduler eingerichtet!"
echo ""
echo "Aktuelle Crontab:"
crontab -l

echo ""
echo "📋 Geplante Tasks:"
cd /var/www/api-gateway && php artisan schedule:list