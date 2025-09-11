#!/bin/bash

#############################################################################
# Abrechnungssystem Backup & Deployment Script
# 
# Dieses Script erstellt ein vollständiges Backup vor der Produktiv-
# schaltung des mehrstufigen Abrechnungssystems.
#############################################################################

set -e  # Beende bei Fehlern
set -u  # Beende bei undefinierten Variablen

# Konfiguration
BACKUP_DIR="/var/www/backups/billing-deployment"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_NAME="billing_backup_${TIMESTAMP}"
DB_NAME="askproai_production"
DB_USER="root"
DB_HOST="localhost"
APP_DIR="/var/www/api-gateway"
MAX_BACKUPS=10  # Behalte nur die letzten 10 Backups

# Farben für Ausgabe
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Logging-Funktion
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1" >&2
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Header
echo "═══════════════════════════════════════════════════════════════════════"
echo " Abrechnungssystem Backup & Deployment"
echo " Timestamp: ${TIMESTAMP}"
echo "═══════════════════════════════════════════════════════════════════════"
echo

# 1. Erstelle Backup-Verzeichnis
log "Erstelle Backup-Verzeichnis..."
mkdir -p "${BACKUP_DIR}/${BACKUP_NAME}"

# 2. Prüfe aktuelle Migration Status
log "Prüfe Migrations-Status..."
cd "${APP_DIR}"
php artisan migrate:status > "${BACKUP_DIR}/${BACKUP_NAME}/migration_status.txt"

# 3. Datenbank-Backup
log "Erstelle Datenbank-Backup..."

# Kritische Billing-Tabellen
BILLING_TABLES=(
    "tenants"
    "transactions"
    "balance_topups"
    "pricing_plans"
    "commission_ledger"
    "reseller_payouts"
    "billing_periods"
    "invoices"
    "invoice_items"
    "billing_alerts"
    "billing_settings"
    "payment_methods"
)

# Vollständiges Backup
mysqldump -h "${DB_HOST}" -u "${DB_USER}" "${DB_NAME}" \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    > "${BACKUP_DIR}/${BACKUP_NAME}/full_database.sql"

if [ $? -eq 0 ]; then
    log "Vollständiges Datenbank-Backup erfolgreich erstellt"
else
    error "Datenbank-Backup fehlgeschlagen!"
    exit 1
fi

# Separates Backup der Billing-Tabellen
log "Erstelle separates Backup der Billing-Tabellen..."
mysqldump -h "${DB_HOST}" -u "${DB_USER}" "${DB_NAME}" \
    --single-transaction \
    --no-create-db \
    ${BILLING_TABLES[@]} \
    > "${BACKUP_DIR}/${BACKUP_NAME}/billing_tables.sql" 2>/dev/null || true

# 4. Konfigurationsdateien sichern
log "Sichere Konfigurationsdateien..."
cp "${APP_DIR}/.env" "${BACKUP_DIR}/${BACKUP_NAME}/.env.backup"
cp -r "${APP_DIR}/config" "${BACKUP_DIR}/${BACKUP_NAME}/config_backup"

# 5. Aktuelle Code-Version dokumentieren
log "Dokumentiere Code-Version..."
cd "${APP_DIR}"
git status > "${BACKUP_DIR}/${BACKUP_NAME}/git_status.txt"
git log --oneline -n 20 > "${BACKUP_DIR}/${BACKUP_NAME}/git_log.txt"
git diff > "${BACKUP_DIR}/${BACKUP_NAME}/git_diff.txt"

# 6. Erstelle Systemzustand-Snapshot
log "Erstelle Systemzustand-Snapshot..."
php artisan billing:health-check --verbose > "${BACKUP_DIR}/${BACKUP_NAME}/health_check.txt" 2>&1 || true

# Sammle Statistiken
echo "=== BILLING SYSTEM STATISTICS ===" > "${BACKUP_DIR}/${BACKUP_NAME}/statistics.txt"
echo "Timestamp: ${TIMESTAMP}" >> "${BACKUP_DIR}/${BACKUP_NAME}/statistics.txt"
echo "" >> "${BACKUP_DIR}/${BACKUP_NAME}/statistics.txt"

mysql -h "${DB_HOST}" -u "${DB_USER}" "${DB_NAME}" -e "
    SELECT 
        'Tenants' as Tabelle, COUNT(*) as Anzahl FROM tenants
    UNION ALL
    SELECT 'Transactions', COUNT(*) FROM transactions
    UNION ALL
    SELECT 'Balance Topups', COUNT(*) FROM balance_topups
    UNION ALL
    SELECT 'Pricing Plans', COUNT(*) FROM pricing_plans;
" >> "${BACKUP_DIR}/${BACKUP_NAME}/statistics.txt" 2>/dev/null || true

# 7. Komprimiere Backup
log "Komprimiere Backup..."
cd "${BACKUP_DIR}"
tar -czf "${BACKUP_NAME}.tar.gz" "${BACKUP_NAME}/"

if [ $? -eq 0 ]; then
    # Entferne unkomprimiertes Verzeichnis
    rm -rf "${BACKUP_NAME}/"
    log "Backup komprimiert: ${BACKUP_NAME}.tar.gz"
else
    warning "Komprimierung fehlgeschlagen, behalte unkomprimiertes Backup"
fi

# 8. Alte Backups aufräumen
log "Räume alte Backups auf (behalte letzte ${MAX_BACKUPS})..."
cd "${BACKUP_DIR}"
ls -t billing_backup_*.tar.gz 2>/dev/null | tail -n +$((MAX_BACKUPS + 1)) | xargs -r rm -f

# 9. Deployment-Vorbereitung
echo
echo "═══════════════════════════════════════════════════════════════════════"
echo " DEPLOYMENT-CHECKLISTE"
echo "═══════════════════════════════════════════════════════════════════════"
echo

# Prüfe Umgebungsvariablen
log "Prüfe kritische Umgebungsvariablen..."

REQUIRED_ENV_VARS=(
    "STRIPE_KEY"
    "STRIPE_SECRET"
    "STRIPE_WEBHOOK_SECRET"
    "BILLING_ENABLED"
)

MISSING_VARS=()
for var in "${REQUIRED_ENV_VARS[@]}"; do
    if ! grep -q "^${var}=" "${APP_DIR}/.env"; then
        MISSING_VARS+=("$var")
    fi
done

if [ ${#MISSING_VARS[@]} -gt 0 ]; then
    warning "Fehlende Umgebungsvariablen:"
    for var in "${MISSING_VARS[@]}"; do
        echo "  - $var"
    done
    echo
    echo "Bitte fügen Sie diese zu .env hinzu bevor Sie fortfahren!"
else
    log "Alle kritischen Umgebungsvariablen vorhanden ✓"
fi

# 10. Erstelle Rollback-Script
log "Erstelle Rollback-Script..."

cat > "${BACKUP_DIR}/rollback_${TIMESTAMP}.sh" << 'ROLLBACK'
#!/bin/bash
# Automatisch generiertes Rollback-Script
# Erstellt: TIMESTAMP_PLACEHOLDER

set -e

echo "╔═══════════════════════════════════════════════════════════════════╗"
echo "║                    BILLING SYSTEM ROLLBACK                       ║"
echo "╚═══════════════════════════════════════════════════════════════════╝"
echo

read -p "WARNUNG: Dies wird die Datenbank zurücksetzen! Fortfahren? (yes/no): " confirm
if [ "$confirm" != "yes" ]; then
    echo "Rollback abgebrochen."
    exit 0
fi

echo "Starte Rollback..."

# Stoppe Anwendung
echo "Stoppe Anwendung..."
sudo supervisorctl stop all

# Restore Datenbank
echo "Stelle Datenbank wieder her..."
mysql -h localhost -u root askproai_production < BACKUP_PATH_PLACEHOLDER/full_database.sql

# Restore Konfiguration
echo "Stelle Konfiguration wieder her..."
cp BACKUP_PATH_PLACEHOLDER/.env.backup /var/www/api-gateway/.env

# Cache löschen
echo "Lösche Cache..."
cd /var/www/api-gateway
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Starte Anwendung
echo "Starte Anwendung..."
sudo supervisorctl start all

echo "Rollback abgeschlossen!"
ROLLBACK

sed -i "s|TIMESTAMP_PLACEHOLDER|${TIMESTAMP}|g" "${BACKUP_DIR}/rollback_${TIMESTAMP}.sh"
sed -i "s|BACKUP_PATH_PLACEHOLDER|${BACKUP_DIR}/${BACKUP_NAME}|g" "${BACKUP_DIR}/rollback_${TIMESTAMP}.sh"
chmod +x "${BACKUP_DIR}/rollback_${TIMESTAMP}.sh"

# 11. Zusammenfassung
echo
echo "═══════════════════════════════════════════════════════════════════════"
echo " BACKUP ERFOLGREICH ABGESCHLOSSEN"
echo "═══════════════════════════════════════════════════════════════════════"
echo
echo "📁 Backup-Verzeichnis: ${BACKUP_DIR}"
echo "📦 Backup-Datei: ${BACKUP_NAME}.tar.gz"
echo "🔄 Rollback-Script: rollback_${TIMESTAMP}.sh"
echo
echo "Backup-Größe:"
du -sh "${BACKUP_DIR}/${BACKUP_NAME}.tar.gz" 2>/dev/null || echo "N/A"
echo

# 12. Nächste Schritte
echo "📋 NÄCHSTE SCHRITTE:"
echo "────────────────────────────────────────────────────────────────────"
echo "1. Prüfen Sie die fehlenden Umgebungsvariablen (falls vorhanden)"
echo "2. Führen Sie die Migrationen aus:"
echo "   php artisan migrate --force"
echo "3. Aktivieren Sie das Billing-System:"
echo "   php artisan config:cache"
echo "4. Testen Sie die Webhook-URL:"
echo "   curl -X POST https://your-domain.de/billing/webhook"
echo "5. Überwachen Sie das System:"
echo "   php artisan billing:health-check"
echo
echo "Bei Problemen verwenden Sie das Rollback-Script:"
echo "   ${BACKUP_DIR}/rollback_${TIMESTAMP}.sh"
echo

log "Script beendet."
