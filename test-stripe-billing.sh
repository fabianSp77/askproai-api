#!/bin/bash

#########################################################
# Stripe Test-Script für AskProAI
# Sicheres Umschalten zwischen Test- und Live-Umgebung
#########################################################

# Farben für Output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Konfiguration
ENV_FILE="/var/www/api-gateway/.env"
ENV_BACKUP="/var/www/api-gateway/.env.backup"
ENV_TESTING="/var/www/api-gateway/.env.testing"
LOCK_FILE="/var/www/api-gateway/.stripe-test-mode.lock"
LOG_FILE="/var/www/api-gateway/storage/logs/stripe-test-mode.log"

# Funktion für Logging
log_action() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

# Funktion für Fehlerbehandlung
error_exit() {
    echo -e "${RED}❌ ERROR: $1${NC}" >&2
    log_action "ERROR: $1"
    exit 1
}

# Funktion für Erfolg
success() {
    echo -e "${GREEN}✅ $1${NC}"
    log_action "SUCCESS: $1"
}

# Funktion für Warnung
warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
    log_action "WARNING: $1"
}

# Funktion für Info
info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
    log_action "INFO: $1"
}

# Prüfe ob wir im richtigen Verzeichnis sind
if [[ ! -f "$ENV_FILE" ]]; then
    error_exit "Konnte .env Datei nicht finden. Bitte im API-Gateway Verzeichnis ausführen."
fi

# Prüfe ob Test-Umgebung konfiguriert ist
check_test_config() {
    if [[ ! -f "$ENV_TESTING" ]]; then
        warning "Test-Umgebung nicht konfiguriert. Erstelle Template..."
        
        # Erstelle Test-Template
        cp "$ENV_FILE" "$ENV_TESTING"
        
        # Ersetze Live-Keys mit Platzhaltern
        sed -i 's/STRIPE_KEY=pk_live_.*/STRIPE_KEY=pk_test_YOUR_TEST_KEY_HERE/g' "$ENV_TESTING"
        sed -i 's/STRIPE_SECRET=sk_live_.*/STRIPE_SECRET=sk_test_YOUR_TEST_SECRET_HERE/g' "$ENV_TESTING"
        sed -i 's/STRIPE_WEBHOOK_SECRET=.*/STRIPE_WEBHOOK_SECRET=whsec_test_YOUR_TEST_WEBHOOK_SECRET/g' "$ENV_TESTING"
        
        echo ""
        echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
        echo -e "${YELLOW}WICHTIG: Test-Umgebung wurde erstellt aber nicht konfiguriert!${NC}"
        echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
        echo ""
        echo "Bitte folgende Schritte ausführen:"
        echo ""
        echo "1. Öffne Stripe Dashboard: https://dashboard.stripe.com"
        echo "2. Aktiviere Test-Modus (Toggle oben rechts)"
        echo "3. Gehe zu: Developers → API keys"
        echo "4. Kopiere die Test-Keys und trage sie ein in: $ENV_TESTING"
        echo ""
        echo "   STRIPE_KEY=pk_test_..."
        echo "   STRIPE_SECRET=sk_test_..."
        echo ""
        echo "5. Führe dieses Script erneut aus"
        echo ""
        error_exit "Test-Umgebung muss erst konfiguriert werden"
    fi
    
    # Prüfe ob Test-Keys eingetragen sind
    if grep -q "YOUR_TEST_KEY_HERE" "$ENV_TESTING"; then
        error_exit "Test-Keys noch nicht in $ENV_TESTING eingetragen"
    fi
}

# Funktion um aktuellen Modus zu prüfen
check_current_mode() {
    if [[ -f "$LOCK_FILE" ]]; then
        echo -e "${YELLOW}🔒 TEST-MODUS IST AKTIV${NC}"
        echo -e "   Gestartet: $(cat $LOCK_FILE)"
        return 0
    else
        current_key=$(grep "^STRIPE_KEY=" "$ENV_FILE" | cut -d'=' -f2)
        if [[ $current_key == pk_test_* ]]; then
            echo -e "${YELLOW}⚠️  Test-Keys gefunden aber kein Lock-File!${NC}"
            return 0
        else
            echo -e "${GREEN}💳 LIVE-MODUS IST AKTIV${NC}"
            return 1
        fi
    fi
}

# Funktion für Test-Modus aktivieren
start_test_mode() {
    info "Aktiviere Test-Modus..."
    
    # Prüfe ob bereits im Test-Modus
    if [[ -f "$LOCK_FILE" ]]; then
        error_exit "Test-Modus ist bereits aktiv!"
    fi
    
    # Prüfe Test-Konfiguration
    check_test_config
    
    # Erstelle Backup
    cp "$ENV_FILE" "$ENV_BACKUP"
    success "Backup erstellt: $ENV_BACKUP"
    
    # Kopiere Test-Umgebung
    cp "$ENV_TESTING" "$ENV_FILE"
    success "Test-Umgebung aktiviert"
    
    # Erstelle Lock-File
    echo "$(date '+%Y-%m-%d %H:%M:%S') von $USER" > "$LOCK_FILE"
    
    # Cache leeren
    info "Leere Laravel Cache..."
    cd /var/www/api-gateway
    php artisan config:clear
    php artisan cache:clear
    
    # PHP-FPM neustarten
    info "Starte PHP-FPM neu..."
    sudo systemctl restart php8.3-fpm
    
    echo ""
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${GREEN}✅ TEST-MODUS ERFOLGREICH AKTIVIERT${NC}"
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    echo "🧪 Test-Kreditkarten:"
    echo "   ✅ 4242 4242 4242 4242  (Erfolgreiche Zahlung)"
    echo "   ❌ 4000 0000 0000 9995  (Zahlung abgelehnt)"
    echo ""
    echo "📍 Business Portal: https://api.askproai.de/business"
    echo "📍 Stripe Dashboard: https://dashboard.stripe.com/test/payments"
    echo ""
    warning "WICHTIG: Nach dem Test mit './test-stripe-billing.sh stop' beenden!"
}

# Funktion für Test-Modus beenden
stop_test_mode() {
    info "Beende Test-Modus..."
    
    # Prüfe ob Test-Modus aktiv ist
    if [[ ! -f "$LOCK_FILE" ]]; then
        error_exit "Test-Modus ist nicht aktiv!"
    fi
    
    # Prüfe ob Backup existiert
    if [[ ! -f "$ENV_BACKUP" ]]; then
        error_exit "Kein Backup gefunden! Manuelle Wiederherstellung erforderlich!"
    fi
    
    # Stelle Live-Umgebung wieder her
    cp "$ENV_BACKUP" "$ENV_FILE"
    success "Live-Umgebung wiederhergestellt"
    
    # Entferne Lock-File
    rm -f "$LOCK_FILE"
    
    # Cache leeren
    info "Leere Laravel Cache..."
    cd /var/www/api-gateway
    php artisan config:clear
    php artisan cache:clear
    
    # PHP-FPM neustarten
    info "Starte PHP-FPM neu..."
    sudo systemctl restart php8.3-fpm
    
    echo ""
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${GREEN}✅ LIVE-MODUS WIEDERHERGESTELLT${NC}"
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
}

# Funktion für Status anzeigen
show_status() {
    echo ""
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${BLUE}STRIPE KONFIGURATION STATUS${NC}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    
    check_current_mode
    echo ""
    
    # Zeige aktuelle Keys (gekürzt)
    current_key=$(grep "^STRIPE_KEY=" "$ENV_FILE" | cut -d'=' -f2)
    current_secret=$(grep "^STRIPE_SECRET=" "$ENV_FILE" | cut -d'=' -f2)
    
    if [[ -n "$current_key" ]]; then
        echo "Public Key:  ${current_key:0:20}..."
    fi
    if [[ -n "$current_secret" ]]; then
        echo "Secret Key:  ${current_secret:0:20}..."
    fi
    echo ""
    
    # Zeige letzte Transaktionen
    if command -v mysql &> /dev/null; then
        echo "Letzte Transaktionen:"
        mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db -e "
            SELECT 
                DATE_FORMAT(created_at, '%d.%m.%Y %H:%i') as Zeit,
                amount as Betrag,
                status as Status,
                LEFT(stripe_session_id, 30) as Session_ID
            FROM balance_topups 
            ORDER BY created_at DESC 
            LIMIT 5;
        " 2>/dev/null || echo "Konnte Transaktionen nicht abrufen"
    fi
}

# Funktion für Sicherheitscheck
safety_check() {
    echo ""
    echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${YELLOW}SICHERHEITS-CHECK${NC}"
    echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    
    # Prüfe auf Test-Keys in Live
    current_key=$(grep "^STRIPE_KEY=" "$ENV_FILE" | cut -d'=' -f2)
    if [[ -f "$LOCK_FILE" ]]; then
        if [[ $current_key != pk_test_* ]]; then
            error_exit "Lock-File existiert aber Live-Keys sind aktiv! KRITISCH!"
        fi
    else
        if [[ $current_key == pk_test_* ]]; then
            warning "Test-Keys sind aktiv aber kein Lock-File! Erstelle Lock-File..."
            echo "$(date '+%Y-%m-%d %H:%M:%S') - Nachträglich erstellt" > "$LOCK_FILE"
        fi
    fi
    
    success "Sicherheits-Check bestanden"
}

# Hauptprogramm
case "${1:-}" in
    start)
        start_test_mode
        ;;
    stop)
        stop_test_mode
        ;;
    status)
        show_status
        ;;
    check)
        safety_check
        ;;
    *)
        echo "Stripe Test-Modus Manager für AskProAI"
        echo ""
        echo "Verwendung: $0 {start|stop|status|check}"
        echo ""
        echo "  start   - Aktiviert Test-Modus (Test-Keys)"
        echo "  stop    - Beendet Test-Modus (Live-Keys)"
        echo "  status  - Zeigt aktuellen Status"
        echo "  check   - Führt Sicherheits-Check durch"
        echo ""
        show_status
        ;;
esac

exit 0