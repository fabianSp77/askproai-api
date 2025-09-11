#!/bin/bash

# AskProAI Daily Backup Script
# Automatisiertes Backup für Datenbank und kritische Dateien
# Wird täglich via Cron ausgeführt

set -e  # Exit on error

# Basiskonfiguration
TIMESTAMP=$(date +%Y-%m-%d_%H-%M-%S)
DATE=$(date +%Y-%m-%d)
BACKUP_DIR="/var/backups/askproai"
DB_BACKUP_DIR="$BACKUP_DIR/db"
FILES_BACKUP_DIR="$BACKUP_DIR/files"
CONFIG_BACKUP_DIR="$BACKUP_DIR/config"
LOGS_DIR="$BACKUP_DIR/logs"
LOG_FILE="$LOGS_DIR/daily_backup_$DATE.log"

# Laravel Umgebung
LARAVEL_DIR="/var/www/api-gateway"
ENV_FILE="$LARAVEL_DIR/.env"
BACKUP_ENV_FILE="$LARAVEL_DIR/.env.backup"

# Datenbank-Zugangsdaten aus sicherer .env.backup lesen
if [ -f "$BACKUP_ENV_FILE" ]; then
    export $(grep -E "^DB_BACKUP_|^BACKUP_" "$BACKUP_ENV_FILE" | xargs)
fi

# Verwende sichere Credentials aus .env.backup
DB_NAME="${DB_BACKUP_DATABASE:-askproai_db}"
DB_USER="${DB_BACKUP_USERNAME:-askproai_user}"
DB_PASS="${DB_BACKUP_PASSWORD}"
DB_HOST="${DB_BACKUP_HOST:-localhost}"

# Email-Benachrichtigung (aus .env.backup)
ADMIN_EMAIL="${BACKUP_ADMIN_EMAIL:-fabian@askproai.de}"

# Aufbewahrungsdauer (aus .env.backup)
RETAIN_DAYS="${BACKUP_RETENTION_DAYS:-14}"
RETAIN_WEEKLY="${BACKUP_RETENTION_WEEKLY:-4}"  # Wochen
RETAIN_MONTHLY="${BACKUP_RETENTION_MONTHLY:-3}" # Monate

# Erstelle alle notwendigen Verzeichnisse
mkdir -p "$DB_BACKUP_DIR"
mkdir -p "$FILES_BACKUP_DIR"
mkdir -p "$CONFIG_BACKUP_DIR"
mkdir -p "$LOGS_DIR"

# Logging-Funktion
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# E-Mail-Benachrichtigung Funktion
send_alert() {
    local subject="$1"
    local message="$2"
    local priority="${3:-normal}"
    
    # Prüfen ob Mail-Tool verfügbar ist
    if command -v mail &>/dev/null; then
        echo -e "$message\n\nServer: $(hostname)\nTimestamp: $(date)" | mail -s "[$priority] AskProAI Backup: $subject" "$ADMIN_EMAIL"
    fi
    
    # Immer ins Log schreiben
    log "ALERT [$priority]: $subject - $message"
}

# Funktion zur Größenberechnung
get_size_human() {
    du -h "$1" 2>/dev/null | cut -f1
}

log "====== AskProAI Daily Backup gestartet ======"
log "Backup-Verzeichnis: $BACKUP_DIR"
log "Timestamp: $TIMESTAMP"

# 1. Datenbank-Backup
log "Phase 1: Datenbank-Backup"
DB_BACKUP_FILE="$DB_BACKUP_DIR/db_backup_$TIMESTAMP.sql.gz"

if mysqldump -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    --quick \
    --lock-tables=false \
    "$DB_NAME" 2>/dev/null | gzip -9 > "$DB_BACKUP_FILE"; then
    
    DB_SIZE=$(get_size_human "$DB_BACKUP_FILE")
    log "✓ Datenbank-Backup erfolgreich: $DB_SIZE"
else
    log "✗ FEHLER: Datenbank-Backup fehlgeschlagen!"
    send_alert "FEHLER - Datenbank-Backup fehlgeschlagen" \
        "Das Datenbank-Backup vom $TIMESTAMP ist fehlgeschlagen.\nBitte dringend überprüfen!" \
        "high"
    exit 1
fi

# 2. Laravel Artisan Backup (falls konfiguriert)
log "Phase 2: Laravel Backup"
cd "$LARAVEL_DIR"

if php artisan backup:run --only-db --disable-notifications 2>&1 | tee -a "$LOG_FILE"; then
    log "✓ Laravel Backup erfolgreich"
else
    log "⚠ Laravel Backup übersprungen oder fehlgeschlagen (nicht kritisch)"
fi

# 3. Kritische Dateien sichern
log "Phase 3: Dateien-Backup"
FILES_BACKUP_FILE="$FILES_BACKUP_DIR/files_backup_$TIMESTAMP.tar.gz"

# Liste kritischer Verzeichnisse
CRITICAL_DIRS=(
    "$LARAVEL_DIR/app"
    "$LARAVEL_DIR/config"
    "$LARAVEL_DIR/database/migrations"
    "$LARAVEL_DIR/resources/views"
    "$LARAVEL_DIR/routes"
    "$LARAVEL_DIR/storage/app"
)

# Erstelle tar mit kritischen Dateien
tar_command="tar -czf $FILES_BACKUP_FILE"
tar_command="$tar_command --exclude=vendor --exclude=node_modules --exclude=.git"
tar_command="$tar_command --exclude=storage/logs --exclude=storage/framework/cache"

for dir in "${CRITICAL_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        tar_command="$tar_command -C / ${dir#/}"
    fi
done

if eval $tar_command 2>/dev/null; then
    FILES_SIZE=$(get_size_human "$FILES_BACKUP_FILE")
    log "✓ Dateien-Backup erfolgreich: $FILES_SIZE"
else
    log "⚠ Dateien-Backup teilweise fehlgeschlagen"
fi

# 4. Konfiguration sichern
log "Phase 4: Konfiguration-Backup"
CONFIG_BACKUP_TODAY="$CONFIG_BACKUP_DIR/config_$DATE"
mkdir -p "$CONFIG_BACKUP_TODAY"

# Sichere wichtige Konfigurationsdateien
cp "$LARAVEL_DIR/.env" "$CONFIG_BACKUP_TODAY/" 2>/dev/null && log "✓ .env gesichert"
cp -r /etc/nginx/sites-available/*.conf "$CONFIG_BACKUP_TODAY/" 2>/dev/null && log "✓ Nginx Config gesichert"
cp /etc/cron.d/* "$CONFIG_BACKUP_TODAY/" 2>/dev/null && log "✓ Cron Jobs gesichert"

# 5. Aufräumen alter Backups
log "Phase 5: Cleanup alter Backups"

# Tägliche Backups älter als RETAIN_DAYS löschen
find "$DB_BACKUP_DIR" -name "db_backup_*.sql.gz" -type f -mtime +$RETAIN_DAYS -delete
find "$FILES_BACKUP_DIR" -name "files_backup_*.tar.gz" -type f -mtime +$RETAIN_DAYS -delete
find "$CONFIG_BACKUP_DIR" -type d -mtime +$RETAIN_DAYS -exec rm -rf {} + 2>/dev/null

CLEANUP_COUNT=$(find "$BACKUP_DIR" -type f -mtime +$RETAIN_DAYS | wc -l)
if [ "$CLEANUP_COUNT" -gt 0 ]; then
    log "✓ $CLEANUP_COUNT alte Backup-Dateien entfernt"
fi

# 6. Backup-Statistik
log "Phase 6: Backup-Statistik"

TOTAL_SIZE=$(du -sh "$BACKUP_DIR" 2>/dev/null | cut -f1)
BACKUP_COUNT=$(find "$BACKUP_DIR" -type f -name "*.gz" | wc -l)
OLDEST_BACKUP=$(find "$BACKUP_DIR" -type f -name "*.gz" -printf '%T+ %p\n' | sort | head -1 | cut -d' ' -f1)

log "====== Backup-Zusammenfassung ======"
log "Datenbank-Backup: $DB_SIZE"
log "Dateien-Backup: $FILES_SIZE"
log "Gesamt-Backups: $BACKUP_COUNT Dateien"
log "Backup-Verzeichnis Größe: $TOTAL_SIZE"
log "Ältestes Backup: $OLDEST_BACKUP"
log "===================================="

# 7. Erfolgreiche Abschluss-Benachrichtigung
if [ -z "$BACKUP_ERRORS" ]; then
    log "✓ Daily Backup erfolgreich abgeschlossen"
    
    # Sende nur bei wichtigen Events eine E-Mail (z.B. Sonntags)
    if [ "$(date +%u)" == "7" ]; then
        send_alert "Wöchentlicher Backup-Report" \
            "Das wöchentliche Backup wurde erfolgreich durchgeführt.\n\nStatistik:\n- DB: $DB_SIZE\n- Files: $FILES_SIZE\n- Gesamt: $TOTAL_SIZE\n- Anzahl Backups: $BACKUP_COUNT" \
            "low"
    fi
else
    log "⚠ Daily Backup mit Warnungen abgeschlossen"
fi

exit 0