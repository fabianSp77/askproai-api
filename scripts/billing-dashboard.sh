#!/bin/bash

# ============================================
# BILLING SYSTEM - LIVE DASHBOARD
# ============================================
# Real-time monitoring of billing metrics
# ============================================

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
MAGENTA='\033[0;35m'
NC='\033[0m'
BOLD='\033[1m'

# Database connection
DB="askproai"
DB_USER="root"

# Function to format currency
format_euro() {
    local cents=$1
    if [ -z "$cents" ] || [ "$cents" == "NULL" ]; then
        echo "0,00 €"
    else
        euros=$((cents / 100))
        remainder=$((cents % 100))
        printf "%d,%02d €" $euros $remainder
    fi
}

# Function to get metric from database
get_metric() {
    local query=$1
    mysql -u $DB_USER $DB -e "$query" -s -N 2>/dev/null || echo "0"
}

# Clear screen and show dashboard
while true; do
    clear
    
    # Header
    echo -e "${BOLD}${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${BOLD}${CYAN}                    BILLING SYSTEM DASHBOARD                    ${NC}"
    echo -e "${BOLD}${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "  📅 $(date +'%Y-%m-%d %H:%M:%S')"
    echo ""
    
    # Key Metrics
    echo -e "${BOLD}${BLUE}📊 KEY METRICS${NC}"
    echo -e "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    
    # Total balance across all tenants
    total_balance=$(get_metric "SELECT COALESCE(SUM(balance_cents), 0) FROM tenants")
    echo -e "💰 Gesamt-Guthaben:     ${GREEN}$(format_euro $total_balance)${NC}"
    
    # Number of tenants
    total_tenants=$(get_metric "SELECT COUNT(*) FROM tenants")
    active_tenants=$(get_metric "SELECT COUNT(*) FROM tenants WHERE balance_cents > 0")
    echo -e "👥 Tenants:             ${YELLOW}$active_tenants${NC} / $total_tenants aktiv"
    
    # Resellers
    resellers=$(get_metric "SELECT COUNT(*) FROM tenants WHERE tenant_type = 'reseller'")
    echo -e "🤝 Reseller:            ${MAGENTA}$resellers${NC}"
    
    # Today's transactions
    today_count=$(get_metric "SELECT COUNT(*) FROM transactions WHERE DATE(created_at) = CURDATE()")
    today_volume=$(get_metric "SELECT COALESCE(SUM(ABS(amount_cents)), 0) FROM transactions WHERE DATE(created_at) = CURDATE()")
    echo -e "📈 Transaktionen heute: ${CYAN}$today_count${NC} ($(format_euro $today_volume))"
    
    echo ""
    
    # Tenant Details
    echo -e "${BOLD}${BLUE}👥 TENANT ÜBERSICHT${NC}"
    echo -e "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    
    # Top 5 tenants by balance
    echo -e "${BOLD}Name                  Typ        Guthaben${NC}"
    mysql -u $DB_USER $DB -e "
        SELECT 
            LEFT(name, 20) as name,
            LEFT(tenant_type, 10) as type,
            balance_cents
        FROM tenants 
        ORDER BY balance_cents DESC 
        LIMIT 5
    " -s -N 2>/dev/null | while IFS=$'\t' read -r name type balance; do
        printf "%-20s %-10s " "$name" "$type"
        if [ "$balance" -lt 500 ]; then
            echo -e "${RED}$(format_euro $balance)${NC}"
        elif [ "$balance" -lt 2000 ]; then
            echo -e "${YELLOW}$(format_euro $balance)${NC}"
        else
            echo -e "${GREEN}$(format_euro $balance)${NC}"
        fi
    done
    
    echo ""
    
    # Recent Transactions
    echo -e "${BOLD}${BLUE}📜 LETZTE TRANSAKTIONEN${NC}"
    echo -e "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo -e "${BOLD}Zeit     Tenant              Typ      Betrag${NC}"
    
    mysql -u $DB_USER $DB -e "
        SELECT 
            DATE_FORMAT(t.created_at, '%H:%i') as time,
            LEFT(tn.name, 18) as tenant,
            LEFT(t.type, 8) as type,
            t.amount_cents
        FROM transactions t
        JOIN tenants tn ON t.tenant_id = tn.id
        ORDER BY t.created_at DESC
        LIMIT 5
    " -s -N 2>/dev/null | while IFS=$'\t' read -r time tenant type amount; do
        printf "%-8s %-18s %-8s " "$time" "$tenant" "$type"
        if [ "$amount" -lt 0 ]; then
            echo -e "${RED}$(format_euro ${amount#-})${NC}"
        else
            echo -e "${GREEN}+$(format_euro $amount)${NC}"
        fi
    done
    
    echo ""
    
    # System Health
    echo -e "${BOLD}${BLUE}🏥 SYSTEM HEALTH${NC}"
    echo -e "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    
    # Check Stripe config
    if grep -q "sk_live_" /var/www/api-gateway/.env 2>/dev/null; then
        echo -e "✅ Stripe:     ${GREEN}Konfiguriert${NC}"
    else
        echo -e "❌ Stripe:     ${RED}Nicht konfiguriert${NC}"
    fi
    
    # Check Cal.com config
    if grep -q "cal_live_" /var/www/api-gateway/.env 2>/dev/null; then
        echo -e "✅ Cal.com:    ${GREEN}Konfiguriert${NC}"
    else
        echo -e "⚠️  Cal.com:    ${YELLOW}Prüfen${NC}"
    fi
    
    # Database status
    if mysql -u $DB_USER $DB -e "SELECT 1" &>/dev/null; then
        echo -e "✅ Database:   ${GREEN}Online${NC}"
    else
        echo -e "❌ Database:   ${RED}Offline${NC}"
    fi
    
    # Check for negative balances
    negative=$(get_metric "SELECT COUNT(*) FROM tenants WHERE balance_cents < 0")
    if [ "$negative" -eq 0 ]; then
        echo -e "✅ Bilanzen:   ${GREEN}OK${NC}"
    else
        echo -e "⚠️  Bilanzen:   ${RED}$negative negative!${NC}"
    fi
    
    echo ""
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "  Aktualisierung in 5 Sekunden... [Strg+C zum Beenden]"
    
    sleep 5
done