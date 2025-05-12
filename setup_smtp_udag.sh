#!/usr/bin/env bash
################################################################################
#   setup_smtp_udag.sh   –  UD|AG-SMTP für Laravel konfigurieren
################################################################################
set -euo pipefail
set +H                        # ⬅️  History-Expansion ( ! ) ausschalten

MAIL_USER='askproai-de-0001'
MAIL_PASS='Qwe421as1!1'
MAIL_FROM='fabian@askproai.de'
MAIL_FROM_NAME='AskProAI'

cd /var/www/api-gateway || { echo "❌  Projektpfad fehlt"; exit 1; }

echo "◇ .env anpassen / ergänzen"
sudo sed -i '/^MAIL_/d' .env          # alte MAIL_* Zeilen entfernen
cat <<EOT | sudo tee -a .env >/dev/null

# --- SMTP (UD|AG) -----------------------------------------------------------
MAIL_MAILER=smtp
MAIL_HOST=smtps.udag.de
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
MAIL_USERNAME=${MAIL_USER}
MAIL_PASSWORD=${MAIL_PASS}
MAIL_FROM_ADDRESS=${MAIL_FROM}
MAIL_FROM_NAME="${MAIL_FROM_NAME}"
EOT
echo "   ✔ .env aktualisiert"

echo "◇ config/mail.php schreiben"
sudo tee config/mail.php >/dev/null <<'PHP'
<?php

return [
    'default' => env('MAIL_MAILER', 'smtp'),

    'mailers' => [
        'smtp' => [
            'transport'  => 'smtp',
            'host'       => env('MAIL_HOST', 'smtps.udag.de'),
            'port'       => env('MAIL_PORT', 465),
            'encryption' => env('MAIL_ENCRYPTION', 'ssl'),
            'username'   => env('MAIL_USERNAME'),
            'password'   => env('MAIL_PASSWORD'),
            'timeout'    => null,
            'local_domain' => null,
        ],
    ],

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
        'name'    => env('MAIL_FROM_NAME', 'Laravel'),
    ],
];
PHP
sudo chmod 644 config/mail.php
echo "   ✔ config/mail.php geschrieben"

echo "◇ Autoloader + Caches erneuern"
composer dump-autoload -o
php artisan optimize:clear

echo "◇ PHP-FPM neu laden"
sudo systemctl restart php8.2-fpm            # ggf. Dienstnamen anpassen

echo "◇ Test-Mail schicken (Tinker-One-Liner)"
php artisan tinker --execute="\Mail::raw('SMTP ok 🚀', fn($m)=>$m->to('${MAIL_FROM}')->subject('UDAG SMTP funktioniert'));"

echo -e "\n✅ Fertig – Postfach ${MAIL_FROM} prüfen!"
################################################################################
