#!/bin/bash

# AskProAI Backup Monitor
# Überwacht den Status des Backup-Systems und sendet Warnungen

set -e

# Konfiguration
BACKUP_DIR="/var/backups/askproai"
WARNING_HOURS=26  # Warnung wenn Backup älter als 26 Stunden
CRITICAL_HOURS=50 # Kritisch wenn älter als 50 Stunden
LOG_FILE="$BACKUP_DIR/logs/monitor_$(date +%Y-%m-%d).log"
ADMIN_EMAIL="fabian@askproai.de"

# Farben für Terminal-Ausgabe
RED='\033[0;31m'
YELLOW='\033[1;33m'
GREEN='\033[0;32m'
NC='\033[0m' # No Color

# Logging
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Status-Funktion
check_status() {
    local dir="$1"
    local name="$2"
    local pattern="$3"
    
    # Finde neueste Datei
    latest_file=$(find "$dir" -name "$pattern" -type f -printf '%T@ %p\n' 2>/dev/null | sort -rn | head -1 | cut -d' ' -f2-)
    
    if [ -z "$latest_file" ]; then
        echo -e "${RED}✗${NC} $name: KEINE BACKUPS GEFUNDEN"
        return 2
    fi
    
    # Berechne Alter in Stunden
    file_age_seconds=$(($(date +%s) - $(stat -c %Y "$latest_file")))
    file_age_hours=$((file_age_seconds / 3600))
    file_size=$(du -h "$latest_file" | cut -f1)
    
    if [ $file_age_hours -gt $CRITICAL_HOURS ]; then
        echo -e "${RED}✗${NC} $name: KRITISCH - Letztes Backup vor ${file_age_hours}h ($(basename "$latest_file"), $file_size)"
        return 2
    elif [ $file_age_hours -gt $WARNING_HOURS ]; then
        echo -e "${YELLOW}⚠${NC} $name: WARNUNG - Letztes Backup vor ${file_age_hours}h ($(basename "$latest_file"), $file_size)"
        return 1
    else
        echo -e "${GREEN}✓${NC} $name: OK - Letztes Backup vor ${file_age_hours}h ($(basename "$latest_file"), $file_size)"
        return 0
    fi
}

# Header
echo "========================================="
echo "AskProAI Backup System Monitor"
echo "Zeit: $(date)"
echo "========================================="

# Status-Variablen
overall_status=0
status_messages=""

# Prüfe Datenbank-Backups
echo ""
echo "Datenbank-Backups:"
if check_status "$BACKUP_DIR/db" "Datenbank" "*.sql.gz"; then
    db_status=0
else
    db_status=$?
    overall_status=$((overall_status + db_status))
fi

# Prüfe Datei-Backups
echo ""
echo "Datei-Backups:"
if check_status "$BACKUP_DIR/files" "Dateien" "*.tar.gz"; then
    file_status=0
else
    file_status=$?
    overall_status=$((overall_status + file_status))
fi

# Prüfe Laravel Backups
echo ""
echo "Laravel Backups:"
laravel_backup_dir="/var/www/api-gateway/storage/app/backups"
if [ -d "$laravel_backup_dir" ]; then
    if check_status "$laravel_backup_dir" "Laravel" "*.zip"; then
        laravel_status=0
    else
        laravel_status=$?
        overall_status=$((overall_status + laravel_status))
    fi
else
    echo -e "${YELLOW}⚠${NC} Laravel: Backup-Verzeichnis nicht gefunden"
    laravel_status=1
fi

# Prüfe Speicherplatz
echo ""
echo "Speicherplatz:"
disk_usage=$(df -h /var/backups | awk 'NR==2 {print $5}' | sed 's/%//')
disk_available=$(df -h /var/backups | awk 'NR==2 {print $4}')

if [ "$disk_usage" -gt 90 ]; then
    echo -e "${RED}✗${NC} Speicherplatz: KRITISCH - ${disk_usage}% belegt, $disk_available verfügbar"
    overall_status=$((overall_status + 2))
elif [ "$disk_usage" -gt 80 ]; then
    echo -e "${YELLOW}⚠${NC} Speicherplatz: WARNUNG - ${disk_usage}% belegt, $disk_available verfügbar"
    overall_status=$((overall_status + 1))
else
    echo -e "${GREEN}✓${NC} Speicherplatz: OK - ${disk_usage}% belegt, $disk_available verfügbar"
fi

# Prüfe Cron-Jobs
echo ""
echo "Cron-Jobs:"
if crontab -l | grep -q "daily-backup.sh"; then
    echo -e "${GREEN}✓${NC} Daily-Backup Cron: Aktiv (3:00 Uhr)"
else
    echo -e "${RED}✗${NC} Daily-Backup Cron: NICHT GEFUNDEN"
    overall_status=$((overall_status + 2))
fi

if crontab -l | grep -q "artisan backup:run"; then
    echo -e "${GREEN}✓${NC} Laravel-Backup Cron: Aktiv (2:00 Uhr)"
else
    echo -e "${YELLOW}⚠${NC} Laravel-Backup Cron: Nicht gefunden"
    overall_status=$((overall_status + 1))
fi

# Statistiken
echo ""
echo "========================================="
echo "Backup-Statistiken:"
echo "========================================="

total_backups=$(find "$BACKUP_DIR" -type f \( -name "*.gz" -o -name "*.zip" \) | wc -l)
total_size=$(du -sh "$BACKUP_DIR" 2>/dev/null | cut -f1)
oldest_backup=$(find "$BACKUP_DIR" -type f \( -name "*.gz" -o -name "*.zip" \) -printf '%T+ %p\n' | sort | head -1 | cut -d' ' -f1)

echo "Gesamt-Backups: $total_backups Dateien"
echo "Gesamt-Größe: $total_size"
echo "Ältestes Backup: $oldest_backup"

# Zusammenfassung
echo ""
echo "========================================="
if [ $overall_status -eq 0 ]; then
    echo -e "${GREEN}GESAMTSTATUS: ALLE SYSTEME OPERATIONAL${NC}"
    log "Monitor: Alle Backup-Systeme operational"
elif [ $overall_status -lt 3 ]; then
    echo -e "${YELLOW}GESAMTSTATUS: WARNUNGEN VORHANDEN${NC}"
    log "Monitor: Backup-System mit Warnungen (Status: $overall_status)"
    
    # E-Mail bei Warnungen (nur einmal täglich)
    warning_file="/tmp/backup_warning_$(date +%Y-%m-%d)"
    if [ ! -f "$warning_file" ]; then
        echo "Backup-Monitoring hat Warnungen festgestellt. Bitte prüfen Sie das System." | \
            mail -s "[WARNING] AskProAI Backup Monitor" "$ADMIN_EMAIL" 2>/dev/null || true
        touch "$warning_file"
    fi
else
    echo -e "${RED}GESAMTSTATUS: KRITISCHE FEHLER${NC}"
    log "Monitor: KRITISCH - Backup-System hat Fehler (Status: $overall_status)"
    
    # Sofortige E-Mail bei kritischen Fehlern
    echo "KRITISCH: Das Backup-System hat schwerwiegende Probleme! Sofortiges Handeln erforderlich." | \
        mail -s "[CRITICAL] AskProAI Backup FEHLER" "$ADMIN_EMAIL" 2>/dev/null || true
fi
echo "========================================="

# Exit-Code basierend auf Status
if [ $overall_status -eq 0 ]; then
    exit 0
elif [ $overall_status -lt 3 ]; then
    exit 1
else
    exit 2
fi