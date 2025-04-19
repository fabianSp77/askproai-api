#!/bin/bash

# ==================================================
# AskProAI - STAGING Restore Skript
# VERSION: 1.0 (Angepasst für Staging)
# ==================================================

# --- Konfiguration für STAGING ---
# Lese Credentials aus der Staging .env Datei (im Verzeichnis /var/www/api-gateway-staging)
ENV_FILE="/var/www/api-gateway-staging/.env"

# Sicherstellen, dass die .env Datei existiert
if [ ! -f "$ENV_FILE" ]; then
    echo "FEHLER: Staging .env Datei nicht gefunden unter $ENV_FILE"
    exit 1
fi

DB_USER=$(grep '^DB_USERNAME=' $ENV_FILE | cut -d '=' -f2)
DB_PASS=$(grep '^DB_PASSWORD=' $ENV_FILE | cut -d '=' -f2 | tr -d '"' | tr -d "'") # Entfernt beide Arten von Anführungszeichen
DB_NAME=$(grep '^DB_DATABASE=' $ENV_FILE | cut -d '=' -f2)

# Fallback (sollte nicht nötig sein, wenn .env korrekt ist)
DB_USER=${DB_USER:-"askproai_staging_user"}
DB_PASS=${DB_PASS:-"StagingPW123!"}
DB_NAME=${DB_NAME:-"askproai_staging_db"}

# Pfade für STAGING
APP_DIR="/var/www/api-gateway-staging"
BACKUP_DIR="/var/backups/askproai" # Backup-Verzeichnis bleibt gleich

# --- Hilfsfunktionen ---
log_error() {
  echo "[FEHLER] $1" >&2 # Schreibe Fehler nach stderr
}

# --- Eingabevalidierung ---
if [ -z "$1" ]; then
  echo "Verwendung: $0 <timestamp>"
  echo "Beispiel: $0 2025-03-31_03-00-01"
  echo "Verfügbare Backups:"
  ls -1t "$BACKUP_DIR/db/" | sed 's/backup_db_\(.*\)\.sql\.gz/\1/' | head -n 10 # Zeige die letzten 10
  exit 1
fi

TIMESTAMP=$1
DB_BACKUP_FILE="$BACKUP_DIR/db/backup_db_${TIMESTAMP}.sql.gz"
FILES_BACKUP_FILE="$BACKUP_DIR/files/backup_files_${TIMESTAMP}.tar.gz"
CONFIG_BACKUP_DIR="$BACKUP_DIR/config/config_backup_${TIMESTAMP}"

# Prüfen, ob die Backup-Dateien existieren
if [ ! -f "$DB_BACKUP_FILE" ]; then
  log_error "Datenbank-Backup-Datei nicht gefunden: $DB_BACKUP_FILE"
  exit 1
fi
if [ ! -f "$FILES_BACKUP_FILE" ]; then
  log_error "Datei-Backup nicht gefunden: $FILES_BACKUP_FILE"
  # Optional: Nur DB wiederherstellen? Oder abbrechen? Wir brechen hier ab.
  exit 1
fi

# --- Sicherheitsabfrage ---
echo "==========================================================================="
echo "ACHTUNG: WIEDERHERSTELLUNG EINES BACKUPS ÜBERSCHREIBT BESTEHENDE STAGING DATEN!"
echo "==========================================================================="
echo "Timestamp: $TIMESTAMP"
echo "Datenbank: $DB_NAME wird überschrieben"
echo "Dateien: $APP_DIR wird überschrieben"
echo "==========================================================================="
read -p "Sind Sie ABSOLUT sicher, dass Sie fortfahren möchten? (ja/nein) " CONFIRM
if [ "$CONFIRM" != "ja" ]; then
  echo "Abbruch durch Benutzer."
  exit 0
fi
read -p "Geben Sie zur Bestätigung den Timestamp erneut ein: " CONFIRM_TS
if [ "$CONFIRM_TS" != "$TIMESTAMP" ]; then
  echo "Timestamp stimmt nicht überein. Abbruch."
  exit 1
fi

# --- Wiederherstellung ---
echo "Starte Wiederherstellung von Backup $TIMESTAMP für STAGING..."

# 1. Temporäres Backup der aktuellen Staging-DB (Sicherheit)
TEMP_DB_BACKUP_FILE="/tmp/staging_db_before_restore_$(date +%F_%H%M).sql"
echo "Erstelle Sicherheits-Backup der aktuellen Staging-Datenbank nach $TEMP_DB_BACKUP_FILE..."
mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$TEMP_DB_BACKUP_FILE"
if [ $? -ne 0 ]; then
    log_error "Konnte kein temporäres DB-Backup erstellen. Zugriff verweigert oder DB nicht vorhanden?"
    # Nicht abbrechen, weitermachen, aber mit Warnung
fi

# 2. Stelle Datenbank wieder her
echo "Stelle Staging-Datenbank wieder her ($DB_NAME)..."
gunzip < "$DB_BACKUP_FILE" | mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME"
if [ $? -ne 0 ]; then
  log_error "Datenbank-Wiederherstellung fehlgeschlagen!"
  echo "Versuchen Sie, den Zustand vor dem Restore mit $TEMP_DB_BACKUP_FILE wiederherzustellen."
  exit 1
fi

# 3. Dienste stoppen (während Dateioperationen)
echo "Stoppe Webserver und PHP-FPM..."
sudo systemctl stop nginx
sudo systemctl stop php8.2-fpm

# 4. Stelle Dateien wieder her (ins Staging-Verzeichnis!)
echo "Stelle Dateien wieder her nach $APP_DIR..."
# --overwrite ist bei tar nicht nötig, -C wechselt ins Zielverzeichnis VOR dem Extrahieren
# --strip-components=1 ist oft nützlich, wenn das Backup einen übergeordneten Ordner enthält (hier anpassen, falls nötig!)
if ! sudo tar -xzf "$FILES_BACKUP_FILE" -C "$APP_DIR" --strip-components=1; then
  log_error "Dateien-Wiederherstellung fehlgeschlagen!"
  echo "Der Webserver wurde gestoppt. Bitte manuell überprüfen und neu starten."
  exit 1
fi

# 5. Berechtigungen für Staging setzen
echo "Setze Berechtigungen für $APP_DIR..."
sudo chown -R www-data:www-data "$APP_DIR"

# 6. Konfiguration wiederherstellen (falls vorhanden)
if [ -d "$CONFIG_BACKUP_DIR" ]; then
  echo "Stelle Konfiguration wieder her (nur .env und oauth keys)..."
  # Nur relevante Dateien kopieren, keine Verzeichnisse
  sudo cp "$CONFIG_BACKUP_DIR/.env" "$APP_DIR/" 2>/dev/null
  # Die OAuth-Keys sollten im storage-Verzeichnis landen
  sudo cp "$CONFIG_BACKUP_DIR/oauth-"* "$APP_DIR/storage/" 2>/dev/null
  # Rechte für kopierte Dateien setzen
  sudo chown www-data:www-data "$APP_DIR/.env" "$APP_DIR/storage/oauth-"*
else
    echo "Kein Konfigurations-Backup für diesen Timestamp gefunden ($CONFIG_BACKUP_DIR)."
fi

# 7. Cache leeren
echo "Leere Cache..."
cd "$APP_DIR" # Ins Staging-Verzeichnis wechseln!
sudo -u www-data php artisan optimize:clear # Umfassender Cache Clear

# 8. Dienste neu starten
echo "Starte Webserver und PHP-FPM..."
sudo systemctl start php8.2-fpm
sudo systemctl start nginx

# Überprüfen, ob die Dienste laufen
sleep 2 # Kurz warten
if systemctl is-active --quiet nginx; then
  echo "Nginx läuft wieder."
else
  echo "WARNUNG: Nginx konnte nicht gestartet werden. Bitte überprüfen!"
fi

if systemctl is-active --quiet php8.2-fpm; then
  echo "PHP-FPM läuft wieder."
else
  echo "WARNUNG: PHP-FPM konnte nicht gestartet werden. Bitte überprüfen!"
fi

echo "==========================================================================="
echo "STAGING Wiederherstellung abgeschlossen!"
echo "Timestamp des wiederhergestellten Backups: $TIMESTAMP"
echo "Temporäres DB-Backup vor Restore: $TEMP_DB_BACKUP_FILE (falls erstellt)"
echo "==========================================================================="

exit 0
