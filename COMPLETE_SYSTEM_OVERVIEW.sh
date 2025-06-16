#!/bin/bash
echo "======================================"
echo "AskProAI COMPLETE SYSTEM CHECK"
echo "======================================"
echo "Datum: $(date)"
echo ""

echo "1. DATENBANKSTRUKTUR"
echo "--------------------"
mysql -u askproai_user -p'Vb39!pLc#7Lqwp$X' askproai_db -e "SHOW TABLES;" 2>/dev/null

echo -e "\n2. PROJEKTSTRUKTUR"
echo "--------------------"
find app -type f -name "*.php" | grep -E "(Retell|Calcom|Webhook|Call|Appointment)" | sort

echo -e "\n3. KONFIGURATION"
echo "--------------------"
php artisan config:show app.name app.env app.url 2>/dev/null || echo "Config konnte nicht geladen werden"

echo -e "\n4. MIGRATIONS STATUS"
echo "--------------------"
php artisan migrate:status | tail -20

echo -e "\n5. QUEUE STATUS"
echo "--------------------"
php artisan queue:work --stop-when-empty 2>&1 | head -5

echo -e "\n6. COMPOSER PACKAGES"
echo "--------------------"
composer show | grep -E "(guzzle|http|webhook|calendar)"
