#!/bin/bash
#
# Live Call Monitor - Real-time Retell Call Tracking
# Created: 2025-10-01
# Purpose: Monitor all data flow during test calls
#

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
LOG_DIR="$PROJECT_ROOT/storage/logs"
MONITOR_LOG="/tmp/call-monitor-$(date +%Y%m%d-%H%M%S).log"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
MAGENTA='\033[0;35m'
NC='\033[0m'

# Print with timestamp
log_event() {
    local color=$1
    local label=$2
    local message=$3
    local timestamp=$(date '+%H:%M:%S.%3N')
    echo -e "${color}[$timestamp] $label:${NC} $message" | tee -a "$MONITOR_LOG"
}

# Clear screen and show header
clear
cat << 'EOF'
╔════════════════════════════════════════════════════════════╗
║         🔴 LIVE CALL MONITOR - ECHTZEIT TRACKING          ║
║                                                            ║
║  Überwacht:                                               ║
║  • Eingehende Retell Webhooks                             ║
║  • Datenvalidierung & Sanitization                        ║
║  • Cal.com API Calls                                      ║
║  • Circuit Breaker Status                                 ║
║  • Rate Limiting                                          ║
║  • Cache Operations                                       ║
║  • Booking Flow                                           ║
╚════════════════════════════════════════════════════════════╝
EOF

echo ""
log_event "$CYAN" "INFO" "Monitor gestartet - Warte auf Anruf..."
log_event "$CYAN" "INFO" "Log wird gespeichert: $MONITOR_LOG"
echo ""
echo -e "${YELLOW}Drücke CTRL+C zum Beenden${NC}"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Track call state
CALL_ID=""
CALL_START_TIME=""

# Function to parse and display webhook data
display_webhook() {
    local line=$1

    # Extract call_id if present
    if echo "$line" | grep -q "call_id"; then
        local new_call_id=$(echo "$line" | grep -oP 'call_id["\s:]+\K[a-zA-Z0-9_-]+' | head -1)
        if [ -n "$new_call_id" ] && [ "$new_call_id" != "$CALL_ID" ]; then
            CALL_ID="$new_call_id"
            CALL_START_TIME=$(date '+%H:%M:%S')
            echo ""
            log_event "$MAGENTA" "🎯 NEUER ANRUF" "Call-ID: $CALL_ID"
            echo ""
        fi
    fi

    # Detect different event types
    if echo "$line" | grep -q "RETELL WEBHOOK RECEIVED"; then
        log_event "$BLUE" "📞 WEBHOOK IN" "Retell sendet Daten..."

    elif echo "$line" | grep -q "call_analyzed"; then
        log_event "$GREEN" "✅ ANALYZE" "Anruf analysiert"

    elif echo "$line" | grep -q "COLLECT APPOINTMENT"; then
        log_event "$CYAN" "📅 COLLECT" "Sammle Termin-Daten..."

    elif echo "$line" | grep -q "Collect appointment data extracted"; then
        # Extract appointment data
        local datum=$(echo "$line" | grep -oP 'datum["\s:]+\K[^,"]+' | head -1)
        local uhrzeit=$(echo "$line" | grep -oP 'uhrzeit["\s:]+\K[^,"]+' | head -1)
        local name=$(echo "$line" | grep -oP '"name"[:\s]+"[^"]*' | grep -oP '"\K[^"]+$' | head -1)

        if [ -n "$datum" ]; then
            log_event "$CYAN" "   └─ Datum" "$datum"
        fi
        if [ -n "$uhrzeit" ]; then
            log_event "$CYAN" "   └─ Uhrzeit" "$uhrzeit"
        fi
        if [ -n "$name" ]; then
            log_event "$CYAN" "   └─ Name" "$name"
        fi

    elif echo "$line" | grep -q "CHECK AVAILABILITY"; then
        log_event "$YELLOW" "🔍 VERFÜGBAR" "Prüfe Cal.com Verfügbarkeit..."

    elif echo "$line" | grep -q "Tenant context set"; then
        local company=$(echo "$line" | grep -oP 'company_id["\s:]+\K\d+' | head -1)
        local branch=$(echo "$line" | grep -oP 'branch_id["\s:]+\K\d+' | head -1)
        log_event "$GREEN" "🏢 TENANT" "Company: $company, Branch: $branch"

    elif echo "$line" | grep -q "Cal\.com.*Sende"; then
        log_event "$BLUE" "📡 CAL.COM →" "API Request gesendet"

    elif echo "$line" | grep -q "Cal\.com.*Response"; then
        local status=$(echo "$line" | grep -oP '"status"[:\s]+\K\d+' | head -1)
        if [ "$status" = "200" ]; then
            log_event "$GREEN" "📡 CAL.COM ←" "HTTP $status (Success)"
        else
            log_event "$RED" "📡 CAL.COM ←" "HTTP $status (Error)"
        fi

    elif echo "$line" | grep -q "Alternative slots found"; then
        local count=$(echo "$line" | grep -oP 'count["\s:]+\K\d+' | head -1)
        log_event "$GREEN" "✅ SLOTS" "$count alternative Termine gefunden"

    elif echo "$line" | grep -q "Auto-adjusted request time"; then
        local original=$(echo "$line" | grep -oP 'original["\s:]+\K[^,"]+' | head -1)
        local adjusted=$(echo "$line" | grep -oP 'adjusted["\s:]+\K[^,"]+' | head -1)
        log_event "$YELLOW" "⏰ ADJUST" "$original → $adjusted (Business Hours)"

    elif echo "$line" | grep -q "Circuit breaker.*CLOSED"; then
        log_event "$GREEN" "🔄 CIRCUIT" "Circuit Breaker: CLOSED (Normal)"

    elif echo "$line" | grep -q "Circuit breaker.*OPEN"; then
        log_event "$RED" "🔄 CIRCUIT" "Circuit Breaker: OPEN (Service Down!)"

    elif echo "$line" | grep -q "Rate limit exceeded"; then
        log_event "$RED" "⚠️  LIMIT" "Rate Limit überschritten!"

    elif echo "$line" | grep -q "EMAIL_REDACTED\|PHONE_REDACTED\|REDACTED"; then
        log_event "$GREEN" "🔒 SANITIZE" "PII/Tokens redacted (GDPR)"

    elif echo "$line" | grep -q "validation.*failed\|Validation"; then
        log_event "$RED" "❌ VALIDATE" "Validation fehlgeschlagen!"

    elif echo "$line" | grep -q "Cache hit"; then
        log_event "$GREEN" "💾 CACHE" "Cache Hit (Fast!)"

    elif echo "$line" | grep -q "Cache miss"; then
        log_event "$YELLOW" "💾 CACHE" "Cache Miss (DB Query)"

    elif echo "$line" | grep -q "ERROR"; then
        local error_msg=$(echo "$line" | grep -oP 'ERROR.*' | head -c 100)
        log_event "$RED" "❌ ERROR" "$error_msg"

    elif echo "$line" | grep -q "CRITICAL"; then
        local critical_msg=$(echo "$line" | grep -oP 'CRITICAL.*' | head -c 100)
        log_event "$RED" "🔴 CRITICAL" "$critical_msg"
    fi
}

# Monitor laravel.log in real-time
tail -f -n 0 "$LOG_DIR/laravel.log" 2>/dev/null | while IFS= read -r line; do
    # Filter for Retell-related logs only
    if echo "$line" | grep -qiE "retell|cal\.?com|appointment|webhook|circuit|rate.*limit|validation|sanitize"; then
        display_webhook "$line"
    fi
done &

TAIL_PID=$!

# Monitor for Ctrl+C
trap ctrl_c INT

ctrl_c() {
    echo ""
    echo ""
    log_event "$YELLOW" "INFO" "Monitor gestoppt - Generiere Zusammenfassung..."
    echo ""

    # Kill tail process
    kill $TAIL_PID 2>/dev/null

    # Generate summary
    generate_summary

    exit 0
}

generate_summary() {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
    echo -e "${MAGENTA}╔════════════════════════════════════════════════════════╗${NC}"
    echo -e "${MAGENTA}║              📊 CALL SUMMARY                          ║${NC}"
    echo -e "${MAGENTA}╚════════════════════════════════════════════════════════╝${NC}"
    echo ""

    if [ -z "$CALL_ID" ]; then
        echo -e "${YELLOW}Kein Anruf während Monitoring-Session erkannt.${NC}"
        echo ""
        echo -e "${CYAN}Vollständiges Log: $MONITOR_LOG${NC}"
        return
    fi

    echo -e "${CYAN}Call-ID:${NC} $CALL_ID"
    echo -e "${CYAN}Start:${NC} $CALL_START_TIME"
    echo -e "${CYAN}Ende:${NC} $(date '+%H:%M:%S')"
    echo ""

    # Count events
    local webhooks=$(grep -c "WEBHOOK IN" "$MONITOR_LOG" 2>/dev/null || echo "0")
    local calcom_calls=$(grep -c "CAL.COM →" "$MONITOR_LOG" 2>/dev/null || echo "0")
    local cache_hits=$(grep -c "Cache Hit" "$MONITOR_LOG" 2>/dev/null || echo "0")
    local errors=$(grep -c "ERROR" "$MONITOR_LOG" 2>/dev/null || echo "0")
    local validations=$(grep -c "VALIDATE" "$MONITOR_LOG" 2>/dev/null || echo "0")
    local sanitizations=$(grep -c "SANITIZE" "$MONITOR_LOG" 2>/dev/null || echo "0")

    echo -e "${BLUE}Event Statistik:${NC}"
    echo "  • Webhooks empfangen: $webhooks"
    echo "  • Cal.com API Calls: $calcom_calls"
    echo "  • Cache Hits: $cache_hits"
    echo "  • Sanitizations: $sanitizations"
    echo "  • Validation Events: $validations"
    echo "  • Errors: $errors"
    echo ""

    # Check for specific issues
    if grep -q "Circuit breaker.*OPEN" "$MONITOR_LOG" 2>/dev/null; then
        echo -e "${RED}⚠️  Circuit Breaker wurde OPEN (Cal.com Down!)${NC}"
    fi

    if grep -q "Rate limit exceeded" "$MONITOR_LOG" 2>/dev/null; then
        echo -e "${RED}⚠️  Rate Limit wurde überschritten!${NC}"
    fi

    if [ "$errors" -gt 0 ]; then
        echo -e "${RED}⚠️  $errors Fehler aufgetreten - Details im Log${NC}"
    fi

    if [ "$errors" -eq 0 ] && [ "$webhooks" -gt 0 ]; then
        echo -e "${GREEN}✅ Anruf erfolgreich verarbeitet - Keine Fehler!${NC}"
    fi

    echo ""
    echo -e "${CYAN}Vollständiges Log: $MONITOR_LOG${NC}"
    echo ""
    echo -e "${YELLOW}Für detaillierte Analyse:${NC}"
    echo "  less $MONITOR_LOG"
    echo ""
}

# Keep script running
wait $TAIL_PID
