#!/usr/bin/env bash
set -euo pipefail

# ───── Settings
TS="$(date +%F_%H%M%S)"
REPORT="system_audit_${TS}.txt"
LOG () { printf '%s\n' "$*" | tee -a "$REPORT" ; }

exec > >(tee -a "$REPORT") 2>&1

LOG "### System-Audit v2  –  $(date) ###"; echo

# 1) Host & Software
LOG "## Host & Software"; {
  echo "\$ hostname -f / whoami:"; hostname -f 2>/dev/null || true; whoami
  echo; lsb_release -d 2>/dev/null || cat /etc/os-release | grep PRETTY_NAME
  echo; php -v | head -n1
  composer --version 2>/dev/null || true
  echo
  for svc in nginx apache2 php-fpm; do
    if command -v "$svc" >/dev/null 2>&1; then
      "$svc" -v 2>&1 | head -n1
      systemctl is-active "$svc" 2>/dev/null || true
    fi
  done
}; echo "–––"; echo

# 2) Git / Composer
LOG "## Git HEAD"; git --no-pager log -1 --oneline || true; echo
LOG "## Composer packages (top level)"; composer show -N | head; echo
if composer show --help | grep -q security; then
  LOG "## Composer security advisories"; composer security:check || true; echo
fi
echo "–––"; echo

# 3) .env (sanitised)
LOG "## .env (sanitised)"
grep -E '^(APP_|CALCOM_|DB_|MAIL_|QUEUE_|REDIS_|STRIPE_|AWS_|CACHE_|SESSION_)' .env \
 | sed 's/=.*/=***SANITISED***/'
echo "–––"; echo

# 4) Laravel
LOG "## Laravel  about / version"
if php artisan help about >/dev/null 2>&1; then php artisan about --ansi; else php artisan --version; fi
echo

if php artisan help route:list >/dev/null 2>&1; then
  if php artisan route:list --help | grep -q -- '--json'; then
    LOG "### Routes (json, 1-Zeile)"; php artisan route:list --json | jq -c '.[]' | head
  else
    LOG "### Routes"; php artisan route:list | head
  fi
else
  LOG "Laravel routes command not available"
fi
echo

if php artisan help migrate:status >/dev/null 2>&1; then
  LOG "### Migration status"; php artisan migrate:status --ansi
fi
echo "–––"; echo

# 5) Verzeichnis-Struktur + Größen
LOG "## Tree (maxdepth 2)"; find . -maxdepth 2 -type f -printf '%P\n' | sort | head -n 30; echo "…"; echo
LOG "## Disk usage (storage & public)"; du -sh storage 2>/dev/null; du -sh public 2>/dev/null
echo "–––"; echo

# 6) Datenbank-Schema
DB_CONN=$(grep '^DB_CONNECTION=' .env | cut -d= -f2)
if [[ "$DB_CONN" == "mysql" ]]; then
  export $(grep -E '^(DB_HOST|DB_PORT|DB_DATABASE|DB_USERNAME|DB_PASSWORD)=' .env)
  LOG "## MySQL schema"; mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "
    SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA='$DB_DATABASE'
    ORDER BY TABLE_NAME, ORDINAL_POSITION;" | head
elif [[ "$DB_CONN" == "sqlite" ]]; then
  DB_FILE=$(grep '^DB_DATABASE=' .env | cut -d= -f2)
  LOG "## SQLite schema ($DB_FILE)"; sqlite3 "$DB_FILE" ".tables"
  for T in $(sqlite3 "$DB_FILE" ".tables"); do
    echo "-- $T"; sqlite3 "$DB_FILE" "pragma table_info($T);"
  done | head
fi
echo "–––"; echo

LOG "Audit gespeichert: \$PWD/$REPORT"
