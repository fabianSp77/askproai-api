#!/usr/bin/env bash
set -euo pipefail

################################################################################
# System-Audit v3 – FULL DUMP
#   • LINES=<n>   → begrenzt Ausgabe jedes großen Blocks (default = ALL)
################################################################################
MAXLINES="${LINES:-0}"        # 0 = alles

TS="$(date +%F_%H%M%S)"
REPORT="system_audit_${TS}.txt"
log() { printf '%s\n' "$*" | tee -a "$REPORT"; }
maybe_head() {                  # $1 = cmd…   (nutzt MAXLINES)
  if (( MAXLINES > 0 )); then eval "$1 | head -n \$MAXLINES"; else eval "$1"; fi
}

exec > >(tee -a "$REPORT") 2>&1
log "### System-Audit v3  –  $(date) ###"; echo

##############################################################################
log "## 1) Host & Software"; {
  hostname -f; whoami; echo
  lsb_release -d 2>/dev/null || grep PRETTY_NAME /etc/os-release
  php -v | head -n1; composer --version || true; echo
  for svc in nginx apache2 php-fpm; do
    command -v "$svc" >/dev/null 2>&1 && { "$svc" -v 2>&1 | head -n1; systemctl is-active "$svc"; }
  done
}; echo

##############################################################################
log "## 2) Git"; maybe_head "git --no-pager log --graph --decorate -n 20"; echo

log "## 3) Composer – top level"; maybe_head "composer show -N"; echo

##############################################################################
log "## 4) .env (sanitised – Schlüssel ausgeblendet)"
maybe_head "grep -E '^[A-Z0-9_]+=' .env | sed 's/=.*/=***SANITISED***/'"
echo

##############################################################################
log "## 5) Laravel – about"; php artisan about --ansi || php artisan --version; echo
log "###   Routes (json)"; maybe_head "php artisan route:list --json | jq -c '.'"; echo
log "###   Migration status"; php artisan migrate:status --ansi; echo
log "###   Horizon status"; php artisan horizon:status 2>/dev/null || echo 'Horizon not installed'; echo
log "###   Scheduler list"; php artisan schedule:list 2>/dev/null || echo 'Scheduler cmd not found'; echo
log "###   Queue failed (7 days)"; php artisan queue:failed --since='7 days ago' || true; echo

##############################################################################
log "## 6) File tree (maxdepth 3)"; maybe_head "find . -maxdepth 3 -type f -printf '%P\n' | sort"; echo
log "## 7) Disk usage"; du -sh . storage public 2>/dev/null; echo

##############################################################################
# DB-Schema  (MySQL / MariaDB   &   SQLite fallback)
if grep -q '^DB_CONNECTION=mysql' .env; then
  log "## 8) MySQL schema"
  export $(grep -E '^(DB_HOST|DB_PORT|DB_DATABASE|DB_USERNAME|DB_PASSWORD)=' .env)
  mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" \
    -e "SELECT TABLE_NAME,COLUMN_NAME,COLUMN_TYPE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA='$DB_DATABASE'
        ORDER BY TABLE_NAME,ORDINAL_POSITION;" \
  | maybe_head "cat"
elif grep -q '^DB_CONNECTION=sqlite' .env; then
  log "## 8) SQLite schema"
  DB_FILE=$(grep '^DB_DATABASE=' .env | cut -d= -f2)
  sqlite3 "$DB_FILE" '.schema' | maybe_head "cat"
fi
echo

##############################################################################
log "## 9) Last 200 lines application logs"; maybe_head "tail -n 200 storage/logs/laravel.log"; echo

##############################################################################
log "## 10) PHP extensions"; maybe_head "php -m"; echo

log "### Report saved to  \$PWD/$REPORT"
