#!/bin/bash

# Erweitertes Wiederherstellungsskript für AskProAI

# Basis-Konfiguration
BACKUP_DIR="/var/backups/askproai"
DB_USER="askproai_user"
DB_PASS="Vb39!pLc#7Lqwp\$X"
DB_NAME="askproai_db"

# Prüfen der Aufrufparameter
if [ "$#" -ne 1 ]; then
  echo "Verwendung: $0 <timestamp>"
  echo "Beispiel: $0 2025-03-16_03-00-00"
  echo "Verfügbare Backups:"
  ls -lt "$BACKUP_DIR/db" | grep -o 'backup_db_[0-9]\{4\}-[0-9]\{2\}-[0-9]\{2\}_[0-9]\{2\}-[0-9]\{2\}-[0-9]\{2\}.sql.gz' | sed 's/backup_db_//' | sed 's/.sql.gz//' | head -n 10
  exit 1
fi

TIMESTAMP=$1

# Prüfen, ob Backup-Dateien existieren
if [ ! -f "$BACKUP_DIR/db/backup_db_$TIMESTAMP.sql.gz" ]; then
  echo "Datenbank-Backup nicht gefunden: $BACKUP_DIR/db/backup_db_$TIMESTAMP.sql.gz"
  exit 1
fi

if [ ! -f "$BACKUP_DIR/files/backup_files_$TIMESTAMP.tar.gz" ]; then
  echo "Dateien-Backup nicht gefunden: $BACKUP_DIR/files/backup_files_$TIMESTAMP.tar.gz"
  exit 1
fi

# Warnung und Bestätigung
echo "==========================================================================="
echo "ACHTUNG: WIEDERHERSTELLUNG EINES BACKUPS ÜBERSCHREIBT BESTEHENDE DATEN!"
echo "==========================================================================="
echo "Timestamp: $TIMESTAMP"
echo "Datenbank: $DB_NAME wird überschrieben"
echo "Dateien: /var/www/api-gateway wird überschrieben"
echo "==========================================================================="
read -p "Sind Sie ABSOLUT sicher, dass Sie fortfahren möchten? (ja/nein) " -r
echo

if [[ ! $REPLY =~ ^[Jj][Aa]$ ]]; then
  echo "Wiederherstellung abgebrochen."
  exit 1
fi

# Zusätzliche Sicherheitsabfrage
read -p "Geben Sie zur Bestätigung den Timestamp erneut ein: " -r CONFIRM
if [[ "$CONFIRM" != "$TIMESTAMP" ]]; then
  echo "Timestamp stimmt nicht überein. Wiederherstellung abgebrochen."
  exit 1
fi

echo "Starte Wiederherstellung von Backup $TIMESTAMP..."

# Aktuelles Datum für temporäre Sicherung
CURRENT_DATE=$(date +%Y-%m-%d_%H-%M-%S)
TEMP_BACKUP_DIR="$BACKUP_DIR/pre_restore_$CURRENT_DATE"
mkdir -p "$TEMP_BACKUP_DIR"

# Schnelles Sicherheits-Backup der aktuellen Datenbank vor Wiederherstellung
echo "Erstelle Sicherheits-Backup der aktuellen Datenbank..."
mysqldump -u"$DB_USER" -p"$DB_PASS" --single-transaction --quick "$DB_NAME" | gzip > "$TEMP_BACKUP_DIR/pre_restore_db.sql.gz"

# Datenbank wiederherstellen
echo "Stelle Datenbank wieder her..."
if ! zcat "$BACKUP_DIR/db/backup_db_$TIMESTAMP.sql.gz" | mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME"; then
  echo "FEHLER: Datenbank-Wiederherstellung fehlgeschlagen!"
  echo "Das vor-Wiederherstellungs-Backup finden Sie unter: $TEMP_BACKUP_DIR"
  exit 1
fi

# Dienste stoppen
echo "Stoppe Webserver und PHP-FPM..."
sudo systemctl stop nginx
sudo systemctl stop php8.2-fpm

# Dateien wiederherstellen
echo "Stelle Dateien wieder her..."
if ! sudo tar -xzf "$BACKUP_DIR/files/backup_files_$TIMESTAMP.tar.gz" -C /var/www/api-gateway --overwrite; then
  echo "FEHLER: Dateien-Wiederherstellung fehlgeschlagen!"
  echo "Der Webserver wurde gestoppt. Bitte manuell überprüfen und neu starten."
  exit 1
fi

# Berechtigungen setzen
sudo chown -R www-data:www-data /var/www/api-gateway

# Konfiguration wiederherstellen (falls vorhanden)
if [ -d "$BACKUP_DIR/config/config_backup_$TIMESTAMP" ]; then
  echo "Stelle Konfiguration wieder her..."
  sudo cp "$BACKUP_DIR/config/config_backup_$TIMESTAMP/.env" /var/www/api-gateway/ 2>/dev/null
  sudo cp "$BACKUP_DIR/config/config_backup_$TIMESTAMP/oauth-"* /var/www/api-gateway/storage/ 2>/dev/null
fi

# Cache leeren
echo "Leere Cache..."
cd /var/www/api-gateway
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan view:clear
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan optimize

# Webserver neu starten
echo "Starte Webserver und PHP-FPM..."
sudo systemctl start php8.2-fpm
sudo systemctl start nginx

# Überprüfen, ob der Webserver läuft
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
echo "Wiederherstellung abgeschlossen!"
echo "Timestamp des wiederhergestellten Backups: $TIMESTAMP"
echo "Vor-Wiederherstellungs-Backup: $TEMP_BACKUP_DIR"
echo "==========================================================================="#
