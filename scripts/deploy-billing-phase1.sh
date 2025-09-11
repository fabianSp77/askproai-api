#!/bin/bash

# ============================================
# BILLING SYSTEM PHASE 1 - PRODUCTION DEPLOYMENT
# ============================================
# Autor: AskProAI DevOps Team
# Datum: 2025-09-10
# Version: 1.0.0
# ============================================

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROJECT_DIR="/var/www/api-gateway"
BACKUP_DIR="/var/www/backups/billing-deployment"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_PATH="${BACKUP_DIR}/backup_${TIMESTAMP}"
LOG_FILE="${BACKUP_DIR}/deployment_${TIMESTAMP}.log"

# Functions
log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1" | tee -a "$LOG_FILE"
    exit 1
}

success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1" | tee -a "$LOG_FILE"
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1" | tee -a "$LOG_FILE"
}

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Header
clear
echo "============================================"
echo "  BILLING SYSTEM - PHASE 1 DEPLOYMENT"
echo "============================================"
echo ""
log "Deployment gestartet..."

# Step 1: Pre-flight checks
log "Step 1: Pre-flight Checks..."

# Check if we're in the right directory
if [ ! -f "$PROJECT_DIR/artisan" ]; then
    error "Nicht im Laravel-Projektverzeichnis. Bitte nach $PROJECT_DIR wechseln."
fi

cd "$PROJECT_DIR"

# Check if .env.billing.production exists
if [ ! -f ".env.billing.production" ]; then
    error ".env.billing.production nicht gefunden! Bitte zuerst konfigurieren."
fi

# Check for Stripe keys in config
if ! grep -q "pk_live_" .env.billing.production || ! grep -q "sk_live_" .env.billing.production; then
    warning "Stripe Live-Keys nicht gefunden in .env.billing.production"
    echo ""
    echo "WICHTIG: Bitte fügen Sie Ihre Stripe-Keys ein:"
    echo "1. Öffnen Sie https://dashboard.stripe.com/apikeys"
    echo "2. Kopieren Sie Publishable Key (pk_live_...)"
    echo "3. Kopieren Sie Secret Key (sk_live_...)"
    echo "4. Fügen Sie diese in .env.billing.production ein"
    echo ""
    read -p "Haben Sie die Keys eingefügt? (j/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Jj]$ ]]; then
        error "Deployment abgebrochen. Bitte Keys einfügen und erneut versuchen."
    fi
fi

success "Pre-flight Checks abgeschlossen"

# Step 2: Create comprehensive backup
log "Step 2: Erstelle Backup..."
mkdir -p "$BACKUP_PATH"

# Backup database
log "Exportiere Datenbank..."
mysqldump -u root askproai > "$BACKUP_PATH/database_backup.sql" 2>/dev/null || {
    warning "Datenbank-Backup fehlgeschlagen. Fortfahren? (j/n)"
    read -p "" -n 1 -r
    echo
    [[ ! $REPLY =~ ^[Jj]$ ]] && error "Deployment abgebrochen"
}

# Backup current .env
if [ -f .env ]; then
    cp .env "$BACKUP_PATH/.env.backup"
    log "Aktuelle .env gesichert"
fi

# Backup current config
cp -r config "$BACKUP_PATH/config_backup"
log "Konfiguration gesichert"

# Create rollback script
cat > "$BACKUP_PATH/rollback.sh" << 'ROLLBACK'
#!/bin/bash
echo "Rolling back deployment..."
cd /var/www/api-gateway

# Restore .env
if [ -f "$1/.env.backup" ]; then
    cp "$1/.env.backup" .env
    echo "✓ .env restored"
fi

# Restore database
if [ -f "$1/database_backup.sql" ]; then
    mysql -u root askproai < "$1/database_backup.sql"
    echo "✓ Database restored"
fi

# Clear caches
php artisan config:clear
php artisan cache:clear
echo "✓ Caches cleared"

echo "Rollback completed!"
ROLLBACK

chmod +x "$BACKUP_PATH/rollback.sh"
success "Backup erstellt in: $BACKUP_PATH"

# Step 3: Merge billing configuration
log "Step 3: Konfiguriere Billing-System..."

# Backup and update .env
if [ -f .env ]; then
    # Remove old billing entries
    grep -v "^STRIPE_" .env | grep -v "^BILLING_" | grep -v "^CALCOM_API_KEY" > .env.tmp
    
    # Add new billing configuration
    echo "" >> .env.tmp
    echo "# ===== BILLING CONFIGURATION =====" >> .env.tmp
    grep "^STRIPE_" .env.billing.production >> .env.tmp
    grep "^BILLING_" .env.billing.production >> .env.tmp
    
    # Update Cal.com key if provided
    if grep -q "cal_live_" .env.billing.production; then
        grep "^CALCOM_API_KEY" .env.billing.production >> .env.tmp
    fi
    
    mv .env.tmp .env
    success "Billing-Konfiguration aktiviert"
else
    cp .env.billing.production .env
    warning "Neue .env erstellt - bitte andere Konfigurationen prüfen"
fi

# Step 4: Clear and rebuild caches
log "Step 4: Cache-Optimierung..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
success "Caches optimiert"

# Step 5: Run health check
log "Step 5: System-Health-Check..."
php artisan billing:health-check --verbose || {
    warning "Health-Check zeigt Warnungen. Details siehe oben."
}

# Step 6: Configure Stripe webhook
log "Step 6: Stripe Webhook-Konfiguration..."
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  WICHTIG: Stripe Webhook konfigurieren"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "1. Öffnen Sie: https://dashboard.stripe.com/webhooks"
echo "2. Klicken Sie auf 'Add endpoint'"
echo "3. Endpoint URL: https://api.askproai.de/billing/webhook"
echo "4. Wählen Sie Events:"
echo "   ✓ checkout.session.completed"
echo "   ✓ payment_intent.succeeded"
echo "   ✓ payment_intent.payment_failed"
echo "   ✓ charge.refunded"
echo "5. Kopieren Sie das 'Signing secret' (whsec_...)"
echo "6. Fügen Sie es in .env als STRIPE_WEBHOOK_SECRET ein"
echo ""
read -p "Webhook konfiguriert? (j/n): " -n 1 -r
echo

if [[ $REPLY =~ ^[Jj]$ ]]; then
    # Update webhook secret if needed
    read -p "Webhook Secret eingeben (oder Enter für später): " webhook_secret
    if [ ! -z "$webhook_secret" ]; then
        sed -i "s/^STRIPE_WEBHOOK_SECRET=.*/STRIPE_WEBHOOK_SECRET=\"$webhook_secret\"/" .env
        php artisan config:cache
        success "Webhook Secret aktualisiert"
    fi
fi

# Step 7: Setup monitoring
log "Step 7: Monitoring-Setup..."

# Create monitoring script
cat > /usr/local/bin/billing-monitor.sh << 'MONITOR'
#!/bin/bash
cd /var/www/api-gateway
php artisan billing:health-check --email

# Check if billing is working
BALANCE=$(mysql -u root askproai -e "SELECT SUM(balance_cents) FROM tenants;" -s -N)
if [ "$BALANCE" -lt 0 ]; then
    echo "ALERT: Negative balance detected!" | mail -s "Billing Alert" admin@askproai.de
fi
MONITOR

chmod +x /usr/local/bin/billing-monitor.sh

# Add to crontab (if not exists)
if ! crontab -l | grep -q "billing-monitor"; then
    (crontab -l 2>/dev/null; echo "*/5 * * * * /usr/local/bin/billing-monitor.sh") | crontab -
    success "Monitoring-Cronjob installiert"
fi

# Step 8: Test payment
log "Step 8: Test-Zahlung..."
echo ""
echo "Möchten Sie eine Test-Zahlung durchführen?"
read -p "Test-Zahlung starten? (j/n): " -n 1 -r
echo

if [[ $REPLY =~ ^[Jj]$ ]]; then
    echo "Öffne Test-Zahlung im Browser..."
    echo "URL: https://api.askproai.de/billing/topup?amount=1000&test=true"
    echo ""
    echo "Verwenden Sie Stripe Test-Karte: 4242 4242 4242 4242"
    echo "Ablauf: Beliebiges Datum in Zukunft"
    echo "CVC: Beliebige 3 Ziffern"
fi

# Step 9: Final report
log "Step 9: Deployment-Report..."
echo ""
echo "============================================"
echo "  DEPLOYMENT ABGESCHLOSSEN"
echo "============================================"
echo ""
success "✓ Billing-System aktiviert"
success "✓ Backup erstellt: $BACKUP_PATH"
success "✓ Monitoring eingerichtet"
echo ""
echo "📊 System-Status:"
php artisan billing:health-check --short
echo ""
echo "🚀 Nächste Schritte:"
echo "1. Test-Zahlung durchführen"
echo "2. Erste Reseller anlegen"
echo "3. Dashboard überwachen"
echo ""
echo "🔧 Bei Problemen:"
echo "Rollback: $BACKUP_PATH/rollback.sh $BACKUP_PATH"
echo "Logs: $LOG_FILE"
echo ""
echo "📧 Support: admin@askproai.de"
echo "============================================"

# Create success marker
touch "$BACKUP_DIR/phase1_deployed_${TIMESTAMP}.success"

log "Deployment erfolgreich abgeschlossen!"