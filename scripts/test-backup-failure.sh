#!/bin/bash

# Simuliert einen Backup-Fehler für E-Mail-Test

source /var/www/api-gateway/scripts/daily-backup.sh

# Override some variables for testing
LOG_FILE="/var/www/api-gateway/storage/logs/backup-test-failure.log"
BACKUP_BASE_DIR="/var/www/api-gateway/backups/test"

log "========== SIMULATING BACKUP FAILURE FOR TESTING =========="

# Create test directory
mkdir -p "$BACKUP_BASE_DIR"

# Simulate database backup failure
ERROR_MSG="Das Datenbank-Backup vom $(date +'%Y-%m-%d_%H-%M-%S') ist fehlgeschlagen. Bitte überprüfen Sie den Server.

Fehlerdetails:
- Server: $HOSTNAME
- Zeitpunkt: $(date +'%d.%m.%Y %H:%M:%S')
- Fehler: mysqldump: Got error: 1045: Access denied for user 'askproai_user'@'localhost' (using password: YES)
- Backup-Verzeichnis: $BACKUP_BASE_DIR

Dies ist eine TEST-Nachricht zur Überprüfung des E-Mail-Systems.
Das echte Backup läuft täglich um 03:00 Uhr."

log "Sending failure notification email..."
send_email "AskProAI Backup FEHLER - $HOSTNAME" "$ERROR_MSG"

log "Test completed. Check your email at: $ADMIN_EMAIL"