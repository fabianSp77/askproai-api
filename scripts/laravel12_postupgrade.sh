#!/usr/bin/env bash
set -euo pipefail

ROOT="/var/www/api-gateway"
cd "$ROOT"

TS="$(date +%Y%m%d_%H%M%S)"
OUT="/tmp/laravel12_postupgrade_${TS}.txt"
mkdir -p docs/postupgrade

log(){ echo "[$(date +%F_%T)] $*"; }
sec(){ echo; echo "=== $* ==="; }

exec > >(tee -a "$OUT") 2>&1

sec "0) CONTEXT"
pwd; php -v || true; php artisan --version || true
composer --version || true
git branch --show-current || true

sec "1) PRECHECKS"
echo "- Laravel detection:"
php -r "echo (file_exists('artisan')?'artisan OK':'artisan MISSING'),PHP_EOL;" || true
echo "- Routes count:"
php artisan route:list 2>/dev/null | wc -l || true

sec "2) STATIC ANALYSIS – LARASTAN oder PHPSTAN"
HAS_LARASTAN="$(env COMPOSER_ALLOW_SUPERUSER=1 composer show -D nunomaduro/larastan 2>/dev/null | wc -l || true)"
if [ "$HAS_LARASTAN" -eq 0 ]; then
  echo "Larastan fehlt. Versuche Installation für L12 ..."
  set +e
  env COMPOSER_ALLOW_SUPERUSER=1 composer require --dev nunomaduro/larastan:^3 --no-interaction
  RC=$?
  set -e
  if [ $RC -ne 0 ]; then
    echo "Larastan Install fehlgeschlagen. Fallback: reiner PHPStan."
  fi
else
  echo "Larastan bereits vorhanden."
fi

if [ ! -f phpstan.neon.dist ]; then
  sec "2a) phpstan.neon.dist anlegen"
  cat > phpstan.neon.dist <<'NEON'
includes:
  - vendor/nunomaduro/larastan/extension.neon
parameters:
  paths: [ app ]
  level: 5
  checkMissingIterableValueType: true
NEON
  git add phpstan.neon.dist || true
fi

sec "2b) PHPStan/Larastan Lauf"
set +e
vendor/bin/phpstan analyse --no-progress --memory-limit=1G
STAN_RC=$?
set -e
if [ $STAN_RC -ne 0 ]; then
  echo "WARN: Static Analysis hat Findings. Weiter mit Plan; Details im Log."
fi

sec "3) TEST‑SUITE – SQLITE PROFIL"
# Eigenständige phpunit.sqlite.xml erstellen, wenn nicht vorhanden
if [ ! -f phpunit.sqlite.xml ]; then
  cat > phpunit.sqlite.xml <<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="vendor/autoload.php" colors="true">
  <testsuites>
    <testsuite name="Application">
      <directory>tests</directory>
    </testsuite>
  </testsuites>
  <php>
    <env name="APP_ENV" value="testing"/>
    <env name="CACHE_DRIVER" value="array"/>
    <env name="QUEUE_CONNECTION" value="sync"/>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
  </php>
</phpunit>
XML
  git add phpunit.sqlite.xml || true
fi

set +e
echo "Starte Tests (SQLite, parallel):"
php artisan test --parallel --configuration=phpunit.sqlite.xml
TEST_RC=$?
set -e
if [ $TEST_RC -ne 0 ]; then
  echo "WARN: Tests nicht grün. Legacy‑Probleme möglich. Weiter mit Audit."
fi

sec "4) SECURITY AUDIT"
set +e
env COMPOSER_ALLOW_SUPERUSER=1 composer audit --locked
AUDIT_RC=$?
set -e
if [ $AUDIT_RC -ne 0 ]; then
  echo "WARN: composer audit meldet Findings. Details oben."
fi

# Node audit nur wenn package.json existiert
if [ -f package.json ]; then
  sec "4a) npm audit (optional)"
  set +e
  npm audit --audit-level=high || true
  set -e
fi

sec "5) APP_DEBUG Prüfung"
echo "Fundstellen APP_DEBUG=true:"
git grep -n "APP_DEBUG=true" -- . ':(exclude).env*' || echo "OK: keine im Code"
if [ -f .env ]; then
  echo "Auszug .env (APP_DEBUG):"
  grep -n "^APP_DEBUG" .env || true
fi

sec "6) TODO/FIXME Inventar"
git grep -nE "TODO|FIXME|HACK|XXX|BUG|OPTIMIZE" -- '*.php' || echo "OK: keine Treffer"

sec "7) HORIZON/QUEUES Kurzer Check"
php artisan queue:failed 2>/dev/null || true
php artisan horizon:status 2>/dev/null || true

sec "8) BEWEISE SCHREIBEN"
cp "$OUT" "docs/postupgrade/report_${TS}.txt"
git add docs/postupgrade/report_${TS}.txt || true

sec "9) ZUSAMMENFASSUNG/GATES"
echo "GATE Laravel 12: $(php artisan --version 2>/dev/null || echo 'unbekannt')"
echo "GATE Static Analysis: RC=$STAN_RC (0=OK)"
echo "GATE Tests(SQLite): RC=$TEST_RC (0=OK)"
echo "GATE composer audit: RC=$AUDIT_RC (0=OK, !=0=Hinweis)"

echo "Fertig. Bericht: $OUT und docs/postupgrade/report_${TS}.txt"
