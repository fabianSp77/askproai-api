#!/bin/bash

# ==================================================
# AskProAI - Rollback Test Skript (Datenbank)
# VERSION: 1.1
# WARNUNG: NUR FÜR STAGING-UMGEBUNG GEDACHT!
# ==================================================

# --- Konfiguration ---
# Diese Werte MÜSSEN an die Staging-Umgebung angepasst werden!
# Es wird versucht, die Credentials aus der .env Datei im aktuellen Verzeichnis zu lesen
# (Sollte im Staging-Webverzeichnis ausgeführt werden)
ENV_FILE=".env"
DB_USER_ST=$(grep '^DB_USERNAME=' $ENV_FILE | cut -d '=' -f2)
DB_PASS_ST=$(grep '^DB_PASSWORD=' $ENV_FILE | cut -d '=' -f2 | tr -d '"') # Entfernt Anführungszeichen
DB_NAME_ST=$(grep '^DB_DATABASE=' $ENV_FILE | cut -d '=' -f2)

# Fallback, falls .env nicht lesbar oder Variablen nicht gesetzt sind
DB_USER_ST=${DB_USER_ST:-"askproai_staging_user"}
DB_PASS_ST=${DB_PASS_ST:-"StagingPW123!"} # Standard-Passwort, falls nicht in .env
DB_NAME_ST=${DB_NAME_ST:-"askproai_staging_db"}

BACKUP_SCRIPT_PATH="/var/www/api-gateway/scripts/backup.sh" # Pfad zum Backup-Skript (Produktion)
RESTORE_SCRIPT_PATH="/var/www/api-gateway/scripts/restore.sh" # Pfad zum Restore-Skript (Produktion)
BACKUP_DIR="/var/backups/askproai/db" # Pfad zum DB-Backup-Verzeichnis
LOG_FILE="/tmp/rollback_test_$(date +%Y-%m-%d_%H%M).log" # Log-Datei in /tmp

# --- Hilfsfunktionen ---
log() {
  echo "[$(date +"%Y-%m-%d %H:%M:%S")] $1" | tee -a "$LOG_FILE"
}

# --- Start ---
log "=== ROLLBACK-TEST (DATENBANK) GESTARTET ==="
echo "Protokoll wird nach $LOG_FILE geschrieben."
echo "Verwendete DB Credentials: User=$DB_USER_ST, DB=$DB_NAME_ST"
if [ -z "$DB_USER_ST" ] || [ -z "$DB_NAME_ST" ]; then
  log "❌ FEHLER: Konnte DB-Credentials nicht aus $ENV_FILE lesen oder sie sind leer. Bitte manuell prüfen/setzen."
  exit 1
fi


# --- Sicherheitsprüfung ---
log "Prüfe aktuelle Umgebung..."
# Prüfen, ob wir uns im Staging-Verzeichnis befinden
if [[ ! $(pwd) == *"/var/www/api-gateway-staging"* ]]; then
    log "WARNUNG: Dieses Skript sollte aus dem Staging-Verzeichnis (/var/www/api-gateway-staging) ausgeführt werden."
    log "Aktuelles Verzeichnis: $(pwd)"
    read -p "Trotzdem fortfahren? (ja/nein) " CONFIRM
    if [[ "$CONFIRM" != "ja" ]]; then
        log "Abbruch durch Benutzer."
        exit 1
    fi
     log "WARNUNG: Fortfahren im Verzeichnis $(pwd) auf eigenes Risiko!"
fi


# --- Vorbereitung ---
log "1. Erstelle temporäres Backup der aktuellen Staging-DB..."
TEMP_BACKUP_FILE="/tmp/staging_db_before_rollback_test_$(date +%F_%H%M).sql"
mysqldump -u "$DB_USER_ST" -p"$DB_PASS_ST" "$DB_NAME_ST" > "$TEMP_BACKUP_FILE"
if [ $? -ne 0 ]; then
    log "❌ FEHLER: Konnte kein temporäres Backup erstellen. Stimmen die DB-Credentials ($DB_USER_ST, $DB_NAME_ST)?"
    exit 1
fi
log "✅ Temporäres Backup erstellt: $TEMP_BACKUP_FILE"

log "2. Füge Testdaten ein..."
TEST_CALL_ID="rollback_test_db_$(date +%s)"
# Füge Timestamps hinzu, falls Spalten NOT NULL sind
mysql -u "$DB_USER_ST" -p"$DB_PASS_ST" "$DB_NAME_ST" -e "INSERT INTO calls (call_id, call_status, successful, created_at, updated_at) VALUES ('$TEST_CALL_ID', 'test_before_restore', 1, NOW(), NOW());"
if [ $? -ne 0 ]; then
    log "❌ FEHLER: Einfügen der Testdaten fehlgeschlagen. Stelle manuell wieder her: mysql -u $DB_USER_ST -p'$DB_PASS_ST' $DB_NAME_ST < $TEMP_BACKUP_FILE"
    exit 1
fi

log "3. Verifiziere Testdaten..."
# Verwende COUNT(*) und erwarte 1
COUNT_BEFORE=$(mysql -N -s -u "$DB_USER_ST" -p"$DB_PASS_ST" "$DB_NAME_ST" -e "SELECT COUNT(*) FROM calls WHERE call_id = '$TEST_CALL_ID';")
if [ "$COUNT_BEFORE" -ne 1 ]; then
  log "❌ FEHLER: Testdaten nicht wie erwartet in der DB gefunden (Count: $COUNT_BEFORE). Stelle manuell wieder her: mysql -u $DB_USER_ST -p'$DB_PASS_ST' $DB_NAME_ST < $TEMP_BACKUP_FILE"
  exit 1
fi
log "✅ Testdaten erfolgreich eingefügt und verifiziert."

# --- Durchführung ---
log "4. Wähle letztes verfügbares Backup..."
LATEST_BACKUP_FILE=$(ls -t "$BACKUP_DIR"/backup_db_*.sql.gz | head -n 1)
if [ -z "$LATEST_BACKUP_FILE" ]; then
    log "❌ FEHLER: Kein Backup im Verzeichnis $BACKUP_DIR gefunden. Abbruch."
    rm "$TEMP_BACKUP_FILE" # Temporäres Backup löschen
    exit 1
fi
TIMESTAMP=$(basename "$LATEST_BACKUP_FILE" | sed 's/backup_db_\(.*\)\.sql\.gz/\1/')
log "Verwende Backup mit Zeitstempel: $TIMESTAMP aus Datei: $LATEST_BACKUP_FILE"

log "5. Führe Restore-Skript aus..."
# Hier sudo verwenden, da das Skript wahrscheinlich root-Rechte braucht und ggf. DB neu erstellt
sudo "$RESTORE_SCRIPT_PATH" "$TIMESTAMP"
RESTORE_EXIT_CODE=$?

if [ $RESTORE_EXIT_CODE -ne 0 ]; then
    log "❌ FEHLER: Restore-Skript wurde mit Fehlercode $RESTORE_EXIT_CODE beendet. Prüfe Skript-Logs."
    log "VERSUCHE, ursprünglichen Zustand mit temporärem Backup wiederherzustellen..."
    mysql -u "$DB_USER_ST" -p"$DB_PASS_ST" "$DB_NAME_ST" < "$TEMP_BACKUP_FILE"
    if [ $? -eq 0 ]; then
        log "✅ Wiederherstellung mit temporärem Backup erfolgreich."
    else
        log "❌ FEHLER bei Wiederherstellung mit temporärem Backup. Manuelle Prüfung erforderlich! Backup liegt in $TEMP_BACKUP_FILE"
    fi
    # rm "$TEMP_BACKUP_FILE" # Nicht löschen bei Fehler
    exit 1
fi
log "✅ Restore-Skript erfolgreich ausgeführt."

# --- Überprüfung ---
log "6. Verifiziere Rollback (Testdaten sollten weg sein)..."
COUNT_AFTER=$(mysql -N -s -u "$DB_USER_ST" -p"$DB_PASS_ST" "$DB_NAME_ST" -e "SELECT COUNT(*) FROM calls WHERE call_id = '$TEST_CALL_ID';")
# Konvertiere das Ergebnis zu einer Zahl, falls es leer ist -> 0
COUNT_AFTER=${COUNT_AFTER:-0}

if [ "$COUNT_AFTER" -eq 0 ]; then
  log "✅ Rollback war erfolgreich, Testdaten wurden entfernt."
  RESULT="ERFOLGREICH"
  EXIT_CODE=0
else
  log "❌ Rollback fehlgeschlagen, Testdaten sind noch vorhanden (Count: $COUNT_AFTER)."
  log "VERSUCHE, ursprünglichen Zustand mit temporärem Backup wiederherzustellen..."
  mysql -u "$DB_USER_ST" -p"$DB_PASS_ST" "$DB_NAME_ST" < "$TEMP_BACKUP_FILE"
   if [ $? -eq 0 ]; then
        log "✅ Wiederherstellung mit temporärem Backup erfolgreich."
    else
        log "❌ FEHLER bei Wiederherstellung mit temporärem Backup. Manuelle Prüfung erforderlich! Backup liegt in $TEMP_BACKUP_FILE"
    fi
  RESULT="FEHLGESCHLAGEN"
  EXIT_CODE=1
fi

# --- Aufräumen ---
log "7. Entferne temporäres Backup..."
rm "$TEMP_BACKUP_FILE"

# --- Abschluss ---
log "=== ROLLBACK-TEST (DATENBANK) ABGESCHLOSSEN ==="
log "Ergebnis: $RESULT"
echo ""
echo "===== ROLLBACK-TEST ERGEBNISSE ====="
echo "Der Test wurde abgeschlossen mit Status: $RESULT"
echo "Details im Protokoll: $LOG_FILE"
echo ""

exit $EXIT_CODE
