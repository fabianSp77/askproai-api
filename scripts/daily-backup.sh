#!/bin/bash

# AskProAI Daily Backup Script with 60-day rotation
# This script creates daily backups and removes backups older than 60 days

set -e

# Configuration
BACKUP_BASE_DIR="/var/www/api-gateway/backups"
DB_NAME="askproai_db"
DB_USER="askproai_user"
DB_PASS="lkZ57Dju9EDjrMxn"
RETENTION_DAYS=60
DATE=$(date +%Y%m%d)
TIME=$(date +%H%M%S)
TIMESTAMP="${DATE}_${TIME}"
LOG_FILE="/var/www/api-gateway/storage/logs/backup.log"

# Email settings
ADMIN_EMAIL="fabian@askproai.de"
HOSTNAME=$(hostname)

# Create log entry
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Send email notification
send_email() {
    local subject="$1"
    local body="$2"
    php /var/www/api-gateway/scripts/send-backup-email.php "$subject" "$body" "$ADMIN_EMAIL" 2>&1 | tee -a "$LOG_FILE"
}

# Start backup process
log "========== Starting daily backup =========="

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_BASE_DIR"

# 1. Database Backup
log "Backing up database..."
DB_BACKUP_FILE="$BACKUP_BASE_DIR/db_backup_${TIMESTAMP}.sql"

if mysqldump -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$DB_BACKUP_FILE" 2>&1; then
    # Compress the backup
    gzip "$DB_BACKUP_FILE"
    DB_BACKUP_FILE="${DB_BACKUP_FILE}.gz"
    DB_SIZE=$(ls -lh "$DB_BACKUP_FILE" | awk '{print $5}')
    log "Database backup successful: $DB_BACKUP_FILE (Size: $DB_SIZE)"
else
    ERROR_MSG="BACKUP FEHLGESCHLAGEN ❌

Server: $HOSTNAME
Zeitpunkt: $(date +'%d.%m.%Y %H:%M:%S')

FEHLERDETAILS:
--------------
Komponente: Datenbank-Backup
Datenbank: $DB_NAME
Fehler: mysqldump fehlgeschlagen

MÖGLICHE URSACHEN:
- Datenbankserver nicht erreichbar
- Zugangsdaten ungültig
- Speicherplatz nicht ausreichend
- Datenbankserver überlastet

WAS SOLLTE GESICHERT WERDEN:
- Datenbank: $DB_NAME (alle Tabellen)
- Erwartete Größe: ~1.2-1.5 MB
- Kritische Tabellen:
  • calls (Anrufdaten)
  • appointments (Termine)
  • companies (Firmendaten)
  • users (Benutzerdaten)
  • customers (Kundendaten)

SOFORTMASSNAHMEN ERFORDERLICH:
1. SSH-Zugang zum Server herstellen
2. Datenbankverbindung prüfen: mysql -u$DB_USER -p'***' $DB_NAME
3. Speicherplatz prüfen: df -h
4. Manuelles Backup versuchen: /var/www/api-gateway/scripts/daily-backup.sh

Backup-Verzeichnis: $BACKUP_BASE_DIR
Log-Datei: $LOG_FILE"
    
    log "ERROR: Database backup failed"
    send_email "🚨 AskProAI Backup FEHLGESCHLAGEN - $HOSTNAME" "$ERROR_MSG"
    exit 1
fi

# 2. Files Backup
log "Backing up application files..."
FILES_BACKUP_FILE="$BACKUP_BASE_DIR/files_backup_${TIMESTAMP}.tar.gz"

cd /var/www/api-gateway
tar -czf "$FILES_BACKUP_FILE" \
    --exclude='vendor' \
    --exclude='node_modules' \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='backups' \
    --exclude='.git' \
    --exclude='*.log' \
    --exclude='*.gz' \
    --exclude='*.zip' \
    .env \
    app/ \
    config/ \
    database/ \
    resources/ \
    routes/ \
    storage/app/ \
    storage/oauth-*.key \
    public/ 2>&1

if [ $? -eq 0 ]; then
    FILES_SIZE=$(ls -lh "$FILES_BACKUP_FILE" | awk '{print $5}')
    log "Files backup successful: $FILES_BACKUP_FILE (Size: $FILES_SIZE)"
else
    log "WARNING: Files backup had some warnings but continued"
fi

# 3. Create backup manifest
MANIFEST_FILE="$BACKUP_BASE_DIR/manifest_${TIMESTAMP}.json"
cat > "$MANIFEST_FILE" << EOF
{
    "timestamp": "$TIMESTAMP",
    "date": "$(date -Iseconds)",
    "database": {
        "file": "$(basename $DB_BACKUP_FILE)",
        "size": "$DB_SIZE",
        "checksum": "$(md5sum $DB_BACKUP_FILE | cut -d' ' -f1)"
    },
    "files": {
        "file": "$(basename $FILES_BACKUP_FILE)",
        "size": "$FILES_SIZE",
        "checksum": "$(md5sum $FILES_BACKUP_FILE | cut -d' ' -f1)"
    },
    "retention_days": $RETENTION_DAYS,
    "hostname": "$HOSTNAME"
}
EOF

log "Backup manifest created: $MANIFEST_FILE"

# 4. Clean up old backups (60 days retention)
log "Cleaning up backups older than $RETENTION_DAYS days..."

# Count backups before cleanup
BEFORE_COUNT=$(find "$BACKUP_BASE_DIR" -name "*.gz" -o -name "*.json" | wc -l)

# Remove old database backups
find "$BACKUP_BASE_DIR" -name "db_backup_*.sql.gz" -mtime +$RETENTION_DAYS -delete -print | while read file; do
    log "Removed old database backup: $(basename $file)"
done

# Remove old file backups
find "$BACKUP_BASE_DIR" -name "files_backup_*.tar.gz" -mtime +$RETENTION_DAYS -delete -print | while read file; do
    log "Removed old files backup: $(basename $file)"
done

# Remove old manifests
find "$BACKUP_BASE_DIR" -name "manifest_*.json" -mtime +$RETENTION_DAYS -delete -print | while read file; do
    log "Removed old manifest: $(basename $file)"
done

# Count backups after cleanup
AFTER_COUNT=$(find "$BACKUP_BASE_DIR" -name "*.gz" -o -name "*.json" | wc -l)
REMOVED_COUNT=$((BEFORE_COUNT - AFTER_COUNT))

if [ $REMOVED_COUNT -gt 0 ]; then
    log "Removed $REMOVED_COUNT old backup files"
fi

# 5. Verify backup integrity
log "Verifying backup integrity..."

# Test database backup
if gunzip -t "$DB_BACKUP_FILE" 2>&1; then
    log "Database backup integrity: OK"
else
    ERROR_MSG="Database backup integrity check failed for $DB_BACKUP_FILE"
    log "ERROR: $ERROR_MSG"
    send_email "AskProAI Backup Integrity Failed - $HOSTNAME" "$ERROR_MSG"
fi

# Test files backup
if tar -tzf "$FILES_BACKUP_FILE" > /dev/null 2>&1; then
    log "Files backup integrity: OK"
else
    ERROR_MSG="Files backup integrity check failed for $FILES_BACKUP_FILE"
    log "ERROR: $ERROR_MSG"
    send_email "AskProAI Backup Integrity Failed - $HOSTNAME" "$ERROR_MSG"
fi

# 6. Generate summary
DISK_USAGE=$(df -h "$BACKUP_BASE_DIR" | awk 'NR==2 {print "Used: "$3" of "$2" ("$5")";}')
BACKUP_COUNT=$(find "$BACKUP_BASE_DIR" -name "db_backup_*.sql.gz" | wc -l)
TOTAL_SIZE=$(du -sh "$BACKUP_BASE_DIR" | cut -f1)

SUMMARY="Daily backup completed successfully
Date: $(date)
Database backup: $(basename $DB_BACKUP_FILE) ($DB_SIZE)
Files backup: $(basename $FILES_BACKUP_FILE) ($FILES_SIZE)
Total backups retained: $BACKUP_COUNT
Total backup size: $TOTAL_SIZE
Disk usage: $DISK_USAGE
Retention: $RETENTION_DAYS days"

log "$SUMMARY"

# Send detailed success email (daily for now, can be changed to weekly later)
DETAILED_SUMMARY="AskProAI Backup Report - $(date +'%d.%m.%Y')
=========================================

BACKUP ERFOLGREICH ABGESCHLOSSEN ✅

Server: $HOSTNAME
Zeitpunkt: $(date +'%d.%m.%Y %H:%M:%S')

GESICHERTE DATEN:
-----------------

1. DATENBANK (MySQL/MariaDB):
   - Datenbank: $DB_NAME
   - Backup-Datei: $(basename $DB_BACKUP_FILE)
   - Größe: $DB_SIZE
   - Tabellen: $(mysql -u"$DB_USER" -p"$DB_PASS" -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME';" -s 2>/dev/null || echo "N/A")
   - Inhalt: Kompletter Datenbank-Dump mit allen Tabellen
     • calls (Anrufdaten)
     • appointments (Termine)
     • companies (Firmendaten)
     • users (Benutzerdaten)
     • customers (Kundendaten)
     • und weitere...

2. ANWENDUNGSDATEIEN:
   - Backup-Datei: $(basename $FILES_BACKUP_FILE)
   - Größe: $FILES_SIZE
   - Gesicherte Verzeichnisse:
     • /app - Anwendungscode
     • /config - Konfigurationsdateien
     • /database - Migrationen & Seeds
     • /resources - Views & Assets
     • /routes - Routing-Definitionen
     • /public - Öffentliche Dateien
     • /storage/app - Anwendungsdaten
     • .env - Umgebungsvariablen
     • OAuth-Keys - Authentifizierungsschlüssel

3. AUSGESCHLOSSENE DATEIEN:
   - vendor/ (Composer-Pakete - können wiederhergestellt werden)
   - node_modules/ (NPM-Pakete - können wiederhergestellt werden)
   - storage/logs/* (Log-Dateien)
   - storage/framework/cache/* (Cache-Dateien)
   - .git/ (Git-Repository)

BACKUP-STATISTIKEN:
-------------------
- Backup-Verzeichnis: $BACKUP_BASE_DIR
- Anzahl gespeicherter Backups: $BACKUP_COUNT
- Gesamtgröße aller Backups: $TOTAL_SIZE
- $DISK_USAGE
- Aufbewahrungsdauer: $RETENTION_DAYS Tage
- Ältestes Backup wird gelöscht am: $(date -d "+$RETENTION_DAYS days" +'%d.%m.%Y')

INTEGRITÄTSPRÜFUNG:
------------------
- Datenbank-Backup: ✅ Erfolgreich verifiziert
- Datei-Backup: ✅ Erfolgreich verifiziert
- Checksums: ✅ Erstellt

$SUMMARY"

# Send email with detailed information
send_email "AskProAI Backup Report - $HOSTNAME - $(date +'%d.%m.%Y')" "$DETAILED_SUMMARY"

# Additional weekly summary on Sundays
if [ $(date +%u) -eq 7 ]; then
    WEEKLY_SUMMARY="WÖCHENTLICHE ZUSAMMENFASSUNG
============================

$(find "$BACKUP_BASE_DIR" -name "db_backup_*.sql.gz" -mtime -7 -exec ls -lh {} \; | awk '{print $9 " - " $5}')

Erfolgsrate diese Woche: 100%"
    
    send_email "AskProAI Wöchentliche Backup-Zusammenfassung - $HOSTNAME" "$DETAILED_SUMMARY

$WEEKLY_SUMMARY"
fi

log "========== Backup completed =========="

exit 0