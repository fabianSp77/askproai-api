#!/bin/bash

echo "⏳ OAuth-Neuinstallation startet jetzt..."

cd /var/www/api-gateway

# OAuth Tabellen löschen
mysql askproai_db -e "
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS oauth_access_tokens;
DROP TABLE IF EXISTS oauth_auth_codes;
DROP TABLE IF EXISTS oauth_clients;
DROP TABLE IF EXISTS oauth_personal_access_clients;
DROP TABLE IF EXISTS oauth_refresh_tokens;
DELETE FROM migrations WHERE migration LIKE '%oauth%' OR migration LIKE '%passport%';
SET FOREIGN_KEY_CHECKS=1;
"

# Laravel Passport neu installieren
sudo -u www-data composer remove laravel/passport
sudo -u www-data composer require laravel/passport

# Migrationen neu veröffentlichen und durchführen
sudo -u www-data php artisan vendor:publish --tag=passport-migrations
sudo -u www-data php artisan migrate

# Passport-Schlüssel neu generieren
sudo -u www-data php artisan passport:install --force
sudo -u www-data php artisan passport:keys --force

# Rechte setzen
sudo chmod 600 storage/oauth-private.key
sudo chmod 644 storage/oauth-public.key
sudo chown www-data:www-data storage/oauth-*.key

# Caches leeren
sudo -u www-data php artisan optimize:clear

# Passport-Status prüfen
sudo -u www-data php artisan passport:status

echo "✅ OAuth-Neuinstallation abgeschlossen."
