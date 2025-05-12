#!/bin/bash

# Umfassendes Backup-Skript für AskProAI
# Sicherere Variante mit verbesserter Struktur und Funktionalität

# Basiskonfiguration
TIMESTAMP=$(date +%Y-%m-%d_%H-%M-%S)
BACKUP_DIR="/var/backups/askproai"
DB_BACKUP_DIR="$BACKUP_DIR/db"
FILES_BACKUP_DIR="$BACKUP_DIR/files"
CONFIG_BACKUP_DIR="$BACKUP_DIR/config/config_backup_$TIMESTAMP"
LOGS_DIR="$BACKUP_DIR/logs"
LOG_FILE="$LOGS_DIR/backup_$TIMESTAMP.log"
OFFSITE_ARCHIVE="$BACKUP_DIR/offsite_backup_$TIMESTAMP.tar.gz"

# Datenbank-Zugangsdaten
DB_USER="askproai_user"
DB_PASS="Vb39!pLc#7Lqwp\$X"
DB_NAME="askproai_db"

# Email-Benachrichtigung
ADMIN_EMAIL="fabian@askproai.de"

# Aufbewahrungsdauer
RETAIN_DAYS=14

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
  
  # Prüfen ob Mail-Tool verfügbar ist
  if command -v mail &>/dev/null; then
    echo "$message" | mail -s "AskProAI Backup: $subject" "$ADMIN_EMAIL"
  else
    # Mindestens ins Log schreiben, wenn kein Mail-Tool verfügbar
    log "ALERT: $subject - $message"
  fi
}

log "Starte Backup-Prozess"

# Datenbank-Backup
log "Erstelle Datenbank-Backup"
if mysqldump -u"$DB_USER" -p"$DB_PASS" --single-transaction --quick "$DB_NAME" | gzip > "$DB_BACKUP_DIR/backup_db_$TIMESTAMP.sql.gz"; then
  log "Datenbank-Backup erfolgreich erstellt"
else
  log "FEHLER: Datenbank-Backup fehlgeschlagen"
  send_alert "FEHLER - Datenbank-Backup fehlgeschlagen" "Das Datenbank-Backup vom $TIMESTAMP ist fehlgeschlagen. Bitte überprüfen Sie den Server."
  exit 1
fi

# Dateien-Backup mit Ausschlüssen
log "Erstelle Dateien-Backup"
if tar --exclude="vendor" --exclude="node_modules" --exclude=".git" -czf "$FILES_BACKUP_DIR/backup_files_$TIMESTAMP.tar.gz" /var/www/api-gateway; then
  log "Dateien-Backup erfolgreich"
else
  log "FEHLER: Dateien-Backup fehlgeschlagen"
  send_alert "FEHLER - Dateien-Backup fehlgeschlagen" "Das Dateien-Backup vom $TIMESTAMP ist fehlgeschlagen. Bitte überprüfen Sie den Server."
  exit 1
fi

# Konfigurationsbackup erstellen
log "Erstelle Konfigurationsbackup"
cp /var/www/api-gateway/.env "$CONFIG_BACKUP_DIR/" 2>/dev/null
cp /var/www/api-gateway/storage/oauth-*.key "$CONFIG_BACKUP_DIR/" 2>/dev/null
cp /etc/nginx/sites-available/*.conf "$CONFIG_BACKUP_DIR/" 2>/dev/null

# Erstelle ein Gesamtarchiv für mögliches Offsite-Backup
log "Erstelle Gesamtarchiv für Backup-Übersicht"
tar -czf "$OFFSITE_ARCHIVE" -C "$BACKUP_DIR" "db/backup_db_$TIMESTAMP.sql.gz" "config/config_backup_$TIMESTAMP" "files/backup_files_$TIMESTAMP.tar.gz"

# Alte Backups entfernen
log "Entferne alte Backups älter als $RETAIN_DAYS Tage"
find "$DB_BACKUP_DIR" -type f -mtime +$RETAIN_DAYS -delete
find "$FILES_BACKUP_DIR" -type f -mtime +$RETAIN_DAYS -delete
find "$BACKUP_DIR/config" -type d -mtime +$RETAIN_DAYS -exec rm -rf {} \; 2>/dev/null
find "$BACKUP_DIR" -name "offsite_backup_*.tar.gz" -type f -mtime +$RETAIN_DAYS -delete

# Backup-Statistik
DB_SIZE=$(du -h "$DB_BACKUP_DIR/backup_db_$TIMESTAMP.sql.gz" | cut -f1)
FILES_SIZE=$(du -h "$FILES_BACKUP_DIR/backup_files_$TIMESTAMP.tar.gz" | cut -f1)
TOTAL_SIZE=$(du -sh "$BACKUP_DIR" | cut -f1)

log "Backup-Statistik:"
log "- Datenbank-Backup: $DB_SIZE"
log "- Dateien-Backup: $FILES_SIZE"
log "- Gesamtgröße aller Backups: $TOTAL_SIZE"

# Erfolgsmeldung
log "Backup-Prozess erfolgreich abgeschlossen"
send_alert "Erfolg - Backup abgeschlossen" "Das Backup vom $TIMESTAMP wurde erfolgreich abgeschlossen. Backup-Größen: DB: $DB_SIZE, Dateien: $FILES_SIZE"
