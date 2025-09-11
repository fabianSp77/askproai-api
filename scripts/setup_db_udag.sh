#!/usr/bin/env bash
###############################################################################
#   setup_db_udag.sh   –  Datenbank-Creds in Laravel .env + config/database.php
###############################################################################
set -euo pipefail
set +H                            # «!» im PW soll nicht die Bash-History triggern

DB_HOST='127.0.0.1'
DB_PORT='3306'
DB_NAME='askproai_db'
DB_USER='askproai_user'
DB_PASS='Vb39!pLc#7Lqwp$X'

cd /var/www/api-gateway || { echo "❌  Projektpfad fehlt"; exit 1; }

echo "◇ .env aktualisieren"
sudo sed -i '/^DB_/d' .env
cat <<EOT | sudo tee -a .env >/dev/null

# --- Datenbank --------------------------------------------------------------
DB_CONNECTION=mysql
DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT}
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASS}
EOT
echo "   ✔ .env fertig"

echo "◇ config/database.php absichern (Default = mysql)"
sudo sed -i "s/'default'.*=>.*'.*'/'default' => env('DB_CONNECTION', 'mysql')/" \
    config/database.php
echo "   ✔ config/database.php geprüft"

echo "◇ Autoloader & Caches leeren"
composer dump-autoload -o
php artisan optimize:clear

echo "◇ PHP-FPM neu starten"
sudo systemctl restart php8.2-fpm

echo "◇ Verbindung testen"
php artisan tinker --execute="try{DB::select('SELECT 1');echo \"✅ DB-Connect ok\n\";}catch(Exception \$e){echo \"❌ \".$e->getMessage().\"\n\";}"
php artisan db:show | head -n 20 || true   # Laravel 11 Quick-Info

echo -e "\n✅  Fertig – 500-Fehler wegen DB sollten jetzt verschwunden sein."
###############################################################################
