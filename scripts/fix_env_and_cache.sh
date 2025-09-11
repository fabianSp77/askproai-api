set -euo pipefail
echo -e "\n🌱  Schritt 1  –  .env aufräumen"
backup=".env.bak.$(date +%s)"
cp .env "$backup" && echo "  • Backup unter  $backup"

# ---- gewünschte Sollwerte (einfach anpassen) --------------------------
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=askproai_db
DB_USERNAME=askproai_user
DB_PASSWORD='Vb39!pLc#7Lqwp$X'   # Quotes erzwingen, damit '!' sicher ist
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
# -----------------------------------------------------------------------

# alle alten DB_/CACHE_/SESSION_/QUEUE_ Zeilen löschen
sed -i '/^DB_/d;/^CACHE_DRIVER=/d;/^SESSION_DRIVER=/d;/^QUEUE_CONNECTION=/d' .env

# neue Werte anhängen
cat >> .env <<EOF
DB_CONNECTION=$DB_CONNECTION
DB_HOST=$DB_HOST
DB_PORT=$DB_PORT
DB_DATABASE=$DB_DATABASE
DB_USERNAME=$DB_USERNAME
DB_PASSWORD=$DB_PASSWORD

CACHE_DRIVER=$CACHE_DRIVER
SESSION_DRIVER=$SESSION_DRIVER
QUEUE_CONNECTION=$QUEUE_CONNECTION
EOF
echo "  • .env neu geschrieben"

echo -e "\n🌱  Schritt 2  –  Laravel-Config-Cache löschen (ohne DB-Hit)"
# wir vermeiden optimize:clear → nutzt DB-Cache-Store
php artisan config:clear    # liest .env neu ein
php artisan route:clear
php artisan view:clear

echo -e "\n🌱  Schritt 3  –  Tinker-Smoke-Test"
php artisan tinker --execute="DB::connection()->getPdo(); echo \"DB-Verbindung OK ✅\n\";"

echo -e "\n🌱  Schritt 4  –  PHP-FPM reload"
sudo systemctl restart php8.2-fpm

echo -e "\n🚀  Fertig!  Jetzt erneut:"
echo "      php artisan optimize       # KEIN clear, nur generieren"
echo "      php artisan migrate --force"
echo "      curl -k https://api.askproai.de/admin/password-reset/request"
