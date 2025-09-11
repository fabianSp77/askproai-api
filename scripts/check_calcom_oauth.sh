#!/usr/bin/env bash
set -euo pipefail
echo -e "\n🗂  Arbeits­verzeichnis  $(pwd)"

# 1) .env – sieht man die Client-Creds?
echo -e "\n### .env  – Cal.com Secrets"
grep -E '^CALCOM_(CLIENT_ID|CLIENT_SECRET)=' .env || echo "⚠️  keine CALCOM_* Variablen"

# 2) Composer – sind Packages installiert?
echo -e "\n### Composer-Packages (sollten existieren)"
composer show league/oauth2-client guzzlehttp/guzzle 2>/dev/null \
 | awk '/^name|^versions|^description/'

# 3) Konfig- & Klassen-Dateien vorhanden?
echo -e "\n### Datei-Check"
for f in config/calcom.php \
         app/Services/CalcomOAuthService.php \
         app/Http/Controllers/CalcomOAuthController.php
do [[ -f $f ]] && echo "✅ $f" || echo "❌ $f fehlt"; done

# 4) Route-Registrierung
echo -e "\n### Route list (grep calcom)"
php artisan route:list --compact | grep -E 'calcom' || echo "⚠️  keine Routes"

# 5) Feld-Auszug aus config()
echo -e "\n### Laravel-Config calcom.php"
php artisan tinker --execute "print_r(config('calcom'))" 2>/dev/null || true

# 6) Token-Status im Cache
echo -e "\n### Token-Status (Cache key: calcom_token)"
php artisan tinker --execute "\$t=app('\\\App\\\Services\\\CalcomOAuthService')->token(); echo \$t?:'⛔ kein Token';" 2>/dev/null || true
