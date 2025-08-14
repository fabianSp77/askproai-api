# OPERATIONS RUNBOOK

## Release
- Tagging: semver (vMAJOR.MINOR.PATCH), Changelog aktualisieren.
- GitHub Release: Notes aus PR_BODY.md.

## Monitoring
- Smoke: cron */5 via /usr/local/bin/askproai_smoke.sh, Log /var/log/askproai_smoke.log.
- Final Gate: täglich 06:00 via /usr/local/bin/askproai_final_gate.sh, Log /var/log/askproai_final_gate.log.

## Security
- Secrets nur in .env, nie im Repo.
- Rotationsrhythmus: 90 Tage (Cal.com, Retell, interne API Keys).
- Nginx-Deny: .env, .git, composer.*, package.json, vite.*.

## Backups
- Restore-Doku: `gunzip -c dump.sql.gz | mysql DBNAME`
- Monatlicher Test-Restore in Staging-DB.

## Admin
- Netzwerk-Restriktion: Nginx + Middleware.
- Filament: bei Widget-Änderungen vorher `php artisan optimize:clear`.

## PR-Gates (must pass)
- Keine v1-URLs, Bearer + cal-api-version in Code.
- /docs/API.md deckt alle Routen ab.
- Unit/Feature-Tests grün oder begründet.
