# Golden Backup - 2025-12-26

**Status**: ERFOLGREICH
**Script**: golden-backup-v2-ultimate.sh (v2.0-ultimate)
**Dauer**: ~70 Sekunden

---

## Backup-Informationen

| Eigenschaft | Wert |
|-------------|------|
| **Datum** | 2025-12-26 12:22:59 |
| **Name** | ultimate-backup-20251226_122259 |
| **Speicherort** | /var/www/GOLDEN_BACKUPS_V2/ultimate-backup-20251226_122259 |
| **Archiv** | ultimate-backup-20251226_122259.tar.gz |
| **Gesamtgroesse** | 219 MB (214 MB komprimiert) |
| **Letztes Backup** | 2025-09-27 (91 Tage zuvor) |

---

## Gesicherte Komponenten (12 Tiers)

| Tier | Komponente | Groesse | Status |
|------|------------|---------|--------|
| 1 | Application Code | 136 MB | OK |
| 2 | claudedocs/ (787 Dateien) | 3.4 MB | OK |
| 3 | Database (askproai_db) | 18 MB | OK |
| 4 | Environment & Config | ~1 MB | OK |
| 5 | Storage/Uploads | 58 MB | OK |
| 6 | Retell.ai State | ~100 KB | OK |
| 7 | Cal.com State | ~100 KB | OK |
| 8 | System Config (nginx, php, supervisor) | ~5 MB | OK |
| 9 | Redis Snapshot | ~10 MB | OK |
| 10 | Git State + Uncommitted Patches | 175 KB | OK |
| 11 | Scripts | ~2 MB | OK |
| 12 | SSL Certificates | ~100 KB | OK |

---

## System-Snapshot

```
PHP:     8.3.23 (cli)
MySQL:   MariaDB 10.11.11
Nginx:   1.22.1
OS:      Linux 6.1.0-37-arm64
Host:    v2202507255565358960
```

---

## Kritische Aenderungen seit letztem Backup (27.09.2025)

### Neue Features
- Service Gateway 2-Phase Delivery-Gate Pattern
- Audio Streaming fuer Backup Emails
- Webhook Secret Hashing
- Thomas Categories Migration
- Intent Fields in Calls Table

### Security Fixes
- Critical multi-tenancy customer data isolation
- Cross-company customer assignments fix

### Neue Dateien (157 untracked)
- 6 neue Migrationen (Dezember 2025)
- Email Templates (ServiceCaseNotificationV2, ITSupportNotification)
- Customer Portal Views
- RCA Dokumentationen

### Modifizierte Dateien (24)
- Alle als Git-Patch gesichert: `system/git/pre-backup-uncommitted.patch`

---

## Wiederherstellung

### Quick Restore
```bash
cd /var/www/GOLDEN_BACKUPS_V2/ultimate-backup-20251226_122259
sudo bash quick-restore.sh
```

### Manuell
```bash
# 1. Archiv entpacken
tar -xzf ultimate-backup-20251226_122259.tar.gz

# 2. Application Code wiederherstellen
tar -xzf app/application.tar.gz -C /var/www/

# 3. Datenbank wiederherstellen
gunzip -c database/full_dump.sql.gz | mysql -u root askproai_db

# 4. Environment wiederherstellen
cp config/env.production /var/www/api-gateway/.env

# 5. Uncommitted Changes anwenden
cd /var/www/api-gateway
git apply system/git/pre-backup-uncommitted.patch
```

### Detaillierte Anleitung
Siehe: `/var/www/GOLDEN_BACKUPS_V2/ultimate-backup-20251226_122259/docs/ULTIMATE_RESTORE_GUIDE.md`

---

## Verifizierung

```
Checksums:        OK (alle Dateien verifiziert)
Archive Integrity: OK
Database Dump:    OK (18 MB)
Application:      OK (136 MB)
Git Patches:      OK (174 KB, 3921 Zeilen)
```

---

## Backup-Verzeichnisstruktur

```
/var/www/GOLDEN_BACKUPS_V2/ultimate-backup-20251226_122259/
  app/
    application.tar.gz (136M)
  claudedocs/
    documentation.tar.gz (3.4M)
  database/
    full_dump.sql.gz (18M)
    schema_only.sql.gz
    table_statistics.txt
  config/
    env.production
    composer.json
    package.json
  storage/
    app_storage.tar.gz (58M)
  external-services/
    retell_state.json
    calcom_state.json
  system/
    nginx/
    php/
    supervisor/
    redis/
    cron/
    git/
      pre-backup-uncommitted.patch (174K)
      pre-backup-git-status.txt
      pre-backup-recent-commits.txt
    info/
  ssl/
    certificates.tar.gz
  docs/
    ULTIMATE_RESTORE_GUIDE.md
  checksums.txt
  metadata.json
  backup.log
  quick-restore.sh
```

---

## Naechstes Backup

**Empfehlung**: Woechentliches Backup oder vor groesseren Deployments
**Script**: `sudo bash scripts/golden-backup-v2-ultimate.sh`
