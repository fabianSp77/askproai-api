# AskProAI - Production Deployment System

**Status**: ✅ Vollständig implementiert
**Datum**: 29. Oktober 2025
**Version**: v1.0 - Synology Edition

---

## Übersicht

Vollautomatisches CI/CD-System mit atomaren Deployments, täglichen Backups auf Synology NAS und monatlichen Restore-Tests.

### Kernkomponenten

- **Atomic Deployments**: Zero-Downtime via Releases + Symlink
- **Build in CI**: npm + composer builds in GitHub Actions
- **Backup-Strategie**: Täglich 02:00 CET → Synology NAS (SFTP/rsync)
- **Retention**: 7 Tage daily, 10 pre-deploy, 3 lokal
- **Restore-Tests**: Monatlich (erster Sonntag 03:00 CET)
- **Auto-Deploy**: develop→Staging, main→Production (nur wenn Staging green)

---

## Zeitplan (CET)

| Vorgang | Zeitpunkt | Cron (UTC) | Beschreibung |
|---------|-----------|------------|--------------|
| **Daily Backup** | **02:00 CET** | `0 1 * * *` | DB + App → Synology |
| **Restore Test** | **03:00 CET** | `0 2 1-7 * 0` | Monatlich (1. Sonntag) |
| **Deploy Staging** | Bei Push | - | develop branch → staging.askproai.de |
| **Deploy Production** | Bei Push | - | main branch → api.askproai.de |

---

## Deployment-Architektur

### Directory Structure (Production)
```
/var/www/api-gateway/
├── current -> releases/20251029_121500-abc123/  # Symlink (atomic)
├── releases/
│   ├── 20251029_121500-abc123/  # Latest
│   ├── 20251029_103000-def456/  # Previous (instant rollback)
│   └── ...
├── shared/  # Persistent data
│   ├── storage/logs/
│   ├── storage/backups/
│   ├── .env/production.env
│   └── public/uploads/
└── repo/  # Git checkout
```

### Atomic Deployment Flow

1. **Build** (GitHub Actions CI)
   - `npm ci` + `npm run build` (Vite)
   - `composer install --no-dev --optimize-autoloader`
   - Create deployment bundle + SHA256

2. **Deploy** (Atomic Switch)
   - Create new release dir: `releases/{timestamp}-{sha}/`
   - Extract bundle
   - Link shared resources (storage, .env, uploads)
   - Run migrations (mit pre-backup)
   - Clear all caches
   - **Atomic**: `mv -Tf temp_symlink current`
   - Reload PHP-FPM + Nginx

3. **Health Checks**
   - HTTP `/health` endpoint
   - Database connection test
   - Auto-rollback on failure

---

## Workflows

### 1. build-artifacts.yml
**Trigger**: Push to main/develop
**Dauer**: ~5-10 min
```yaml
Jobs:
  - build-frontend: npm ci + vite build
  - build-backend: composer install --no-dev
  - create-deployment-bundle: bundle + SHA256
  - run-tests: PHPUnit mit MariaDB
```

### 2. deploy-staging.yml
**Trigger**: Push to develop
**Server**: staging.askproai.de
```yaml
Jobs:
  - build: Call build-artifacts.yml
  - deploy-staging: Atomic deployment
  - smoke-tests: Homepage, Health, API checks
```

### 3. deploy-production.yml
**Trigger**: Push to main (nur wenn Staging green)
**Server**: api.askproai.de
```yaml
Jobs:
  - check-staging: Verify staging health
  - pre-deploy-backup: DB + App → Synology
  - build: Call build-artifacts.yml
  - deploy-production: Atomic deployment
  - smoke-tests: Production health checks
```

### 4. backup-daily.yml
**Schedule**: 02:00 CET täglich
**Storage**: Synology NAS `/volume1/homes/FSAdmin/Backup/Server AskProAI/daily/`
```yaml
Jobs:
  - create-backup:
      1. mysqldump (gzip) + SHA256
      2. tar -czf app snapshot + SHA256
      3. Upload via scripts/synology-upload.sh
      4. Apply retention (keep 7)
      5. Verify integrity
```

### 5. backup-restore-test.yml
**Schedule**: 1. Sonntag/Monat 03:00 CET
**Test-Umgebung**: Staging (separate DB)
```yaml
Jobs:
  - restore-test:
      1. Download latest backup (from production)
      2. Extract + verify checksums
      3. Create test DB on staging
      4. Restore database
      5. Verify integrity (tables, counts)
      6. Generate report + upload artifact
      7. Cleanup test DB
```

---

## Backup-Strategie

### Retention-Policy
```
daily/       → 7 Backups (letzte 7 Tage)
pre-deploy/  → 10 Backups (letzte 10 Deployments)
local/       → 3 Backups (auf Produktions-Server)
```

### Backup-Dateien
```
backup-db-{YYYYMMDD_HHMMSS}.sql.gz           # Database dump
backup-db-{YYYYMMDD_HHMMSS}.sql.gz.sha256    # Checksum
backup-app-{YYYYMMDD_HHMMSS}.tar.gz          # Application snapshot
backup-app-{YYYYMMDD_HHMMSS}.tar.gz.sha256   # Checksum
backup-manifest-{YYYYMMDD_HHMMSS}.txt        # Manifest
```

### Synology-Struktur
```
/volume1/homes/FSAdmin/Backup/Server AskProAI/
├── daily/
│   ├── backup-db-20251029_020000.sql.gz
│   ├── backup-app-20251029_020000.tar.gz
│   └── ...
└── pre-deploy/
    ├── backup-db-20251029_101500.sql.gz
    ├── backup-app-20251029_101500.tar.gz
    └── ...
```

---

## Restore-Anleitung

### Schnelles Rollback (ohne DB-Änderungen)
```bash
# Production
ssh www-data@api.askproai.de
cd /var/www/api-gateway
./scripts/rollback-production.sh

# Oder mit spezifischer Release
./scripts/rollback-production.sh 20251028_150000-abc123
```

### Vollständiges Restore (mit DB)

#### 1. Backup von Synology holen
```bash
# SSH zu Production-Server
ssh www-data@api.askproai.de

# Download latest backup from Synology (via synology-upload.sh)
# Oder: direkt von lokalem Backup auf Server
cd /var/www/api-gateway/shared/storage/backups
ls -lt backup-db-*.sql.gz | head -1  # Latest backup anzeigen
```

#### 2. Database Restore
```bash
# Extract backup
gunzip < backup-db-20251029_020000.sql.gz > /tmp/restore.sql

# Verify checksum BEFORE restore
sha256sum -c backup-db-20251029_020000.sql.gz.sha256

# Create test restore first (SAFE)
mysql -h 127.0.0.1 -u root \
  -e "CREATE DATABASE askproai_restore_test;"
mysql -h 127.0.0.1 -u root askproai_restore_test < /tmp/restore.sql

# Test queries
mysql -h 127.0.0.1 -u root askproai_restore_test \
  -e "SELECT COUNT(*) FROM users; SELECT COUNT(*) FROM appointments;"

# If OK, restore to production (CAREFUL!)
mysql -h 127.0.0.1 -u root askproai_db < /tmp/restore.sql

# Cleanup
rm /tmp/restore.sql
mysql -h 127.0.0.1 -u root -e "DROP DATABASE askproai_restore_test;"
```

#### 3. Application Restore (falls nötig)
```bash
cd /var/www/api-gateway

# Extract app backup
tar -xzf shared/storage/backups/backup-app-20251029_020000.tar.gz \
  -C /tmp/app-restore/

# Vergleichen und selektiv wiederherstellen
# VORSICHT: Nicht .env oder storage/ überschreiben!
rsync -av --exclude='.env' --exclude='storage/' \
  /tmp/app-restore/ current/

# Cleanup
rm -rf /tmp/app-restore/
```

#### 4. Nach Restore
```bash
cd current
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
sudo systemctl reload php8.3-fpm nginx

# Health check
curl -sf https://api.askproai.de/health
```

---

## Git-Workflow

### Branch-Strategie
```
main     → Production (api.askproai.de)
develop  → Staging (staging.askproai.de)
feature/* → Feature-Branches (PR zu develop)
```

### Branch-Protection (main)
- ✅ Require pull request reviews
- ✅ Require status checks (build-artifacts, tests)
- ✅ No direct push (nur via PR)
- ✅ Auto-deploy nur wenn Staging green

### Deployment-Flow
```bash
# 1. Feature entwickeln
git checkout -b feature/new-feature develop
# ... code changes ...
git commit -m "feat: add new feature"
git push origin feature/new-feature

# 2. PR erstellen zu develop
# → Auto-deploy zu Staging bei Merge

# 3. Testen auf Staging
curl https://staging.askproai.de/health

# 4. PR von develop zu main
# → Pre-deploy backup + Auto-deploy zu Production
```

---

## Wichtige Befehle

### Deployment
```bash
# Manueller Staging-Deploy (falls nötig)
ssh www-data@staging.askproai.de
cd /var/www/api-gateway-staging
./scripts/deploy-staging-atomic.sh develop

# Manueller Production-Deploy (falls nötig)
ssh www-data@api.askproai.de
cd /var/www/api-gateway
./scripts/deploy-production-atomic.sh main

# Rollback Production
./scripts/rollback-production.sh
```

### Backup
```bash
# Manuelles Backup triggern
gh workflow run backup-daily.yml

# Backup zu Synology uploaden
./scripts/synology-upload.sh /path/to/backup.tar.gz daily

# Restore-Test triggern
gh workflow run backup-restore-test.yml
```

### Monitoring
```bash
# Logs anzeigen
tail -f /var/www/api-gateway/current/storage/logs/laravel.log

# Service-Status
systemctl status php8.3-fpm nginx

# Aktive Release
readlink /var/www/api-gateway/current

# Verfügbare Releases
ls -lt /var/www/api-gateway/releases/

# Backup-Status
ls -lth /var/www/api-gateway/shared/storage/backups/ | head -10
```

### Health Checks
```bash
# Production
curl -sf https://api.askproai.de/health
curl -sf https://api.askproai.de/api/health

# Staging
curl -sf https://staging.askproai.de/health
curl -sf https://staging.askproai.de/api/health
```

---

## Troubleshooting

### Deployment fehlgeschlagen
```bash
# 1. Check GitHub Actions logs
gh run list --workflow=deploy-production.yml
gh run view {run-id}

# 2. Check server logs
ssh www-data@api.askproai.de
tail -f /var/www/api-gateway/current/storage/logs/laravel.log

# 3. Rollback wenn nötig
./scripts/rollback-production.sh
```

### Backup fehlgeschlagen
```bash
# 1. Check workflow logs
gh run list --workflow=backup-daily.yml
gh run view {run-id}

# 2. Check Synology connectivity
ssh www-data@api.askproai.de
./scripts/synology-upload.sh --test

# 3. Verify local backups exist
ls -lth /var/www/api-gateway/shared/storage/backups/
```

### Restore-Test fehlgeschlagen
```bash
# 1. Check workflow artifact
gh run download {run-id}

# 2. Manual restore test
# (siehe Restore-Anleitung oben)
```

---

## Secrets (GitHub Actions)

Folgende Secrets müssen in GitHub konfiguriert sein:

```yaml
PRODUCTION_SSH_KEY       # SSH private key für api.askproai.de
STAGING_SSH_KEY          # SSH private key für staging.askproai.de
```

Optional (falls Synology über GitHub Actions erreichbar):
```yaml
SYNOLOGY_HOST            # z.B. askpro.synology.me
SYNOLOGY_SSH_KEY         # SSH key für Synology
```

---

## Nächste Schritte

1. ✅ **Synology NAS konfigurieren**
   - SSH-Zugang einrichten (Port Forwarding oder DynDNS)
   - SSH-Key von Production-Server zu Synology kopieren
   - `scripts/synology-upload.sh` testen

2. ✅ **Branch-Protection aktivieren**
   - GitHub Settings → Branches → main
   - Require PR reviews + status checks

3. ✅ **Ersten Deployment testen**
   - develop branch → Staging deployment
   - main branch → Production deployment (mit Pre-Deploy-Backup)

4. ✅ **Monitoring einrichten**
   - Backup-Success-Benachrichtigungen
   - Deployment-Notifications
   - Restore-Test-Reports

---

## Support & Dokumentation

- **Scripts**: `/var/www/api-gateway/scripts/`
- **Workflows**: `.github/workflows/`
- **Logs**: `/var/www/api-gateway/current/storage/logs/`
- **Backups**: `/var/www/api-gateway/shared/storage/backups/`
- **GitHub Actions**: https://github.com/{org}/{repo}/actions

---

**System Ready** ✅

Alle Komponenten sind implementiert und einsatzbereit. Das System führt automatisch Backups durch, deployed Code-Änderungen atomic und testet monatlich die Restore-Fähigkeit.
