#!/usr/bin/env bash
set -euo pipefail

# 0) Report-Datei vorbereiten
TS="$(date +%F_%H%M%S)"
REPORT="system_audit_${TS}.txt"
exec > >(tee -a "$REPORT") 2>&1
echo "###  System-Audit  –  $(date) ###"
echo

# 1) Allg. System-Infos
echo "## Host & Software"
echo "\$ hostname -f && whoami";          hostname -f 2>/dev/null || true
whoami
echo; lsb_release -d 2>/dev/null || cat /etc/os-release
echo; php -v | head -n1
echo; composer --no-ansi --version
echo; nginx -v 2>&1 | head -n1 || true
echo "–––"; echo

# 2) Git / Composer / PHP-Pakete
echo "## Git HEAD"; git --no-pager log -1 --oneline || true
echo; echo "## Composer packages (top level)"; composer --no-ansi show -N | head
echo "–––"; echo

# 3) .env (sanitised – Werte werden ausgeblendet)
echo "## .env (sanitised)"
grep -E '^(APP_|CALCOM_|DB_|MAIL_|QUEUE_|REDIS_|STRIPE_|AWS_)' .env \
 | sed -E 's/=.*/=***SANITISED***/'
echo "–––"; echo

# 4) Laravel-Status
echo "## Laravel"
php artisan --version
echo; php artisan route:list --compact
echo; php artisan migrate:status
echo "–––"; echo

# 5) Verzeichnis-Struktur (2 Ebenen)
echo "## Tree (maxdepth 2)"
find . -maxdepth 2 -type f -printf '%P\n' | sort
echo "–––"; echo

# 6) Datenbank-Schema (MySQL/MariaDB)  – liest .env
if grep -q '^DB_CONNECTION=mysql' .env; then
  export $(grep -E '^(DB_HOST|DB_PORT|DB_DATABASE|DB_USERNAME|DB_PASSWORD)=' .env)
  echo "## MySQL – Tabellen & Spalten"
  mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" \
        -p"$DB_PASSWORD" -e "
    SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = '$DB_DATABASE'
    ORDER BY TABLE_NAME, ORDINAL_POSITION;"
  echo "–––"; echo
fi

# 7) End-Hinweis
echo "Audit gespeichert in  \$PWD/$REPORT"
