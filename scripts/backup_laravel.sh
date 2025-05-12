#!/usr/bin/env bash
set -euo pipefail
###############################################################################
# backup_laravel.sh – tägliche Sicherung für AskProAI
#
#  ► sichert Datenbank, Storage-Uploads & .env
#  ► legt alles sauber datiert unter /var/backups/askproai ab
#  ► rotiert/entfernt Alt-Backups automatisch (14 Tage)
###############################################################################

# -------- Ablage-Pfad (anpassen, wenn gewünscht) -----------------------------
BACKUP_DIR="/var/backups/askproai"

# -------- DB-Zugang (wird aus .env gelesen) ----------------------------------
source /var/www/api-gateway/.env        # liest DB_HOST/DB_USERNAME/…
DB_HOST=${DB_HOST:-localhost}
DB_USER=${DB_USERNAME:-root}
DB_PASS=${DB_PASSWORD}
DB_NAME=${DB_DATABASE:-askproai}

# -------- weitere Parameter ---------------------------------------------------
TIMESTAMP=$(date +"%Y-%m-%d_%H-%M")
RETENTION_DAYS=14                       # Aufbewahrungstage
LOG_FILE="$BACKUP_DIR/backup_$TIMESTAMP.log"

# -------- Ordnerstruktur anlegen ---------------------------------------------
mkdir -p "$BACKUP_DIR"/{db,files,logs,tmp}

# -------- Logging-Funktion ----------------------------------------------------
log() { echo "[$(date +'%F %T')] $*" | tee -a "$LOG_FILE"; }

log "🔥 Starte Backup-Job"

# -------- 1) Datenbank sichern ------------------------------------------------
DB_DUMP="$BACKUP_DIR/db/${DB_NAME}_$TIMESTAMP.sql.gz"
log "→ Datenbankdump nach $DB_DUMP"
mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" \
          --single-transaction --quick --skip-lock-tables "$DB_NAME" \
          | gzip -9 > "$DB_DUMP" \
          || { log "❌ mysqldump fehlgeschlagen"; exit 1; }

# -------- 2) Storage + wichtige Dateien --------------------------------------
FILES_TAR="$BACKUP_DIR/files/files_$TIMESTAMP.tar.zst"
log "→ Komprimiere Storage + .env nach $FILES_TAR"
/usr/bin/tar \
  --exclude 'vendor' \
  --exclude 'node_modules' \
  -I 'zstd -19 -T0' \
  -cpf "$FILES_TAR" \
    /var/www/api-gateway/storage \
    /var/www/api-gateway/public \
    /var/www/api-gateway/.env

# -------- 3) Prüfsumme erzeugen ----------------------------------------------
sha256sum "$DB_DUMP" "$FILES_TAR" > "$BACKUP_DIR/tmp/sha_$TIMESTAMP.txt"

# -------- 4) Alte Backups löschen --------------------------------------------
log "→ Bereinige Backups älter als $RETENTION_DAYS Tage"
find "$BACKUP_DIR/db"    -type f -mtime +$RETENTION_DAYS -delete
find "$BACKUP_DIR/files" -type f -mtime +$RETENTION_DAYS -delete
find "$BACKUP_DIR/logs"  -type f -mtime +$RETENTION_DAYS -delete

# -------- 5) Log verschieben & fertig ----------------------------------------
mv "$LOG_FILE" "$BACKUP_DIR/logs/"
log "✅ Backup abgeschlossen"
exit 0
