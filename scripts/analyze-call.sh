#!/bin/bash
#
# Call Analysis Script
# Analyzes a completed call from logs
#

if [ -z "$1" ]; then
    echo "Usage: $0 <call_id>"
    echo ""
    echo "Example: $0 abc123xyz"
    exit 1
fi

CALL_ID=$1
LOG_DIR="/var/www/api-gateway/storage/logs"

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}╔════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║         CALL ANALYSIS - $CALL_ID${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════╝${NC}"
echo ""

# Extract all logs for this call
echo -e "${CYAN}Extrahiere Logs für Call-ID: $CALL_ID${NC}"
echo ""

TEMP_LOG="/tmp/call-analysis-${CALL_ID}.log"
grep "$CALL_ID" "$LOG_DIR/laravel.log" > "$TEMP_LOG" 2>/dev/null

if [ ! -s "$TEMP_LOG" ]; then
    echo -e "${RED}Keine Logs für Call-ID '$CALL_ID' gefunden!${NC}"
    echo ""
    echo "Letzte Anrufe:"
    grep -oP 'call_id["\s:]+\K[a-zA-Z0-9_-]+' "$LOG_DIR/laravel.log" | tail -10 | sort -u
    exit 1
fi

LINE_COUNT=$(wc -l < "$TEMP_LOG")
echo -e "${GREEN}✓ $LINE_COUNT Log-Einträge gefunden${NC}"
echo ""

# Timeline
echo -e "${BLUE}═══ TIMELINE ═══${NC}"
echo ""

# Start time
START_TIME=$(head -1 "$TEMP_LOG" | grep -oP '\[\d{4}-\d{2}-\d{2} \K\d{2}:\d{2}:\d{2}')
END_TIME=$(tail -1 "$TEMP_LOG" | grep -oP '\[\d{4}-\d{2}-\d{2} \K\d{2}:\d{2}:\d{2}')

echo -e "${CYAN}Start:${NC} $START_TIME"
echo -e "${CYAN}Ende:${NC} $END_TIME"
echo ""

# Extract key events
echo -e "${BLUE}═══ KEY EVENTS ═══${NC}"
echo ""

# Webhook received
if grep -q "WEBHOOK RECEIVED" "$TEMP_LOG"; then
    echo -e "${GREEN}✓${NC} Webhook empfangen"
fi

# Tenant context
if grep -q "Tenant context set" "$TEMP_LOG"; then
    COMPANY=$(grep "Tenant context set" "$TEMP_LOG" | grep -oP 'company_id["\s:]+\K\d+' | head -1)
    BRANCH=$(grep "Tenant context set" "$TEMP_LOG" | grep -oP 'branch_id["\s:]+\K\d+' | head -1)
    echo -e "${GREEN}✓${NC} Tenant Context: Company $COMPANY, Branch ${BRANCH:-0}"
fi

# Appointment data
if grep -q "Collect appointment data extracted" "$TEMP_LOG"; then
    echo -e "${GREEN}✓${NC} Termin-Daten extrahiert:"

    DATUM=$(grep "Collect appointment data extracted" "$TEMP_LOG" | grep -oP 'datum["\s:]+\K[^,"]+' | head -1)
    UHRZEIT=$(grep "Collect appointment data extracted" "$TEMP_LOG" | grep -oP 'uhrzeit["\s:]+\K[^,"]+' | head -1)
    NAME=$(grep "Collect appointment data extracted" "$TEMP_LOG" | grep -oP '"name"[:\s]+"[^"]*' | grep -oP '"\K[^"]+$' | head -1)
    SERVICE=$(grep "Collect appointment data extracted" "$TEMP_LOG" | grep -oP 'dienstleistung["\s:]+\K[^,"]+' | head -1)

    [ -n "$DATUM" ] && echo -e "  ${CYAN}•${NC} Datum: $DATUM"
    [ -n "$UHRZEIT" ] && echo -e "  ${CYAN}•${NC} Uhrzeit: $UHRZEIT"
    [ -n "$NAME" ] && echo -e "  ${CYAN}•${NC} Name: $NAME"
    [ -n "$SERVICE" ] && echo -e "  ${CYAN}•${NC} Service: $SERVICE"
fi

echo ""

# Validation
echo -e "${BLUE}═══ VALIDATION & SECURITY ═══${NC}"
echo ""

if grep -q "getAppointmentData" "$TEMP_LOG"; then
    echo -e "${GREEN}✓${NC} Input Validation (FormRequest) verwendet"
else
    echo -e "${YELLOW}⚠${NC} Keine FormRequest Validation erkannt"
fi

SANITIZE_COUNT=$(grep -c "REDACTED" "$TEMP_LOG" 2>/dev/null || echo "0")
if [ "$SANITIZE_COUNT" -gt 0 ]; then
    echo -e "${GREEN}✓${NC} Log Sanitization aktiv ($SANITIZE_COUNT Redaktionen)"
else
    echo -e "${YELLOW}⚠${NC} Keine Sanitization events (möglicherweise keine PII)"
fi

if grep -q "Rate limit" "$TEMP_LOG"; then
    if grep -q "Rate limit exceeded" "$TEMP_LOG"; then
        echo -e "${RED}✗${NC} Rate Limit ÜBERSCHRITTEN!"
    else
        echo -e "${GREEN}✓${NC} Rate Limiting check passed"
    fi
fi

echo ""

# Cal.com Integration
echo -e "${BLUE}═══ CAL.COM INTEGRATION ═══${NC}"
echo ""

CALCOM_REQUESTS=$(grep -c "Cal\.com.*Sende" "$TEMP_LOG" 2>/dev/null || echo "0")
CALCOM_RESPONSES=$(grep -c "Cal\.com.*Response" "$TEMP_LOG" 2>/dev/null || echo "0")

echo -e "${CYAN}API Requests:${NC} $CALCOM_REQUESTS"
echo -e "${CYAN}API Responses:${NC} $CALCOM_RESPONSES"

if grep -q "Cal\.com.*Response" "$TEMP_LOG"; then
    STATUS=$(grep "Cal\.com.*Response" "$TEMP_LOG" | grep -oP '"status"[:\s]+\K\d+' | head -1)
    if [ "$STATUS" = "200" ]; then
        echo -e "${GREEN}✓${NC} Cal.com Response: HTTP $STATUS (Success)"
    else
        echo -e "${RED}✗${NC} Cal.com Response: HTTP $STATUS (Error)"
    fi
fi

# Circuit Breaker
if grep -q "Circuit breaker" "$TEMP_LOG"; then
    CB_STATE=$(grep "Circuit breaker" "$TEMP_LOG" | tail -1)
    if echo "$CB_STATE" | grep -q "CLOSED"; then
        echo -e "${GREEN}✓${NC} Circuit Breaker: CLOSED (Normal)"
    elif echo "$CB_STATE" | grep -q "OPEN"; then
        echo -e "${RED}✗${NC} Circuit Breaker: OPEN (Service Down!)"
    fi
fi

# Slots found
if grep -q "Alternative slots found" "$TEMP_LOG"; then
    SLOT_COUNT=$(grep "Alternative slots found" "$TEMP_LOG" | grep -oP 'count["\s:]+\K\d+' | head -1)
    echo -e "${GREEN}✓${NC} Alternative Slots: $SLOT_COUNT gefunden"
fi

# Business hours adjustment
if grep -q "Auto-adjusted request time" "$TEMP_LOG"; then
    ORIG=$(grep "Auto-adjusted" "$TEMP_LOG" | grep -oP 'original["\s:]+\K[^,"]+' | head -1)
    ADJ=$(grep "Auto-adjusted" "$TEMP_LOG" | grep -oP 'adjusted["\s:]+\K[^,"]+' | head -1)
    echo -e "${YELLOW}⚡${NC} Business Hours Adjustment: $ORIG → $ADJ"
fi

echo ""

# Cache Performance
echo -e "${BLUE}═══ PERFORMANCE ═══${NC}"
echo ""

CACHE_HITS=$(grep -c "Cache hit" "$TEMP_LOG" 2>/dev/null || echo "0")
CACHE_MISSES=$(grep -c "Cache miss" "$TEMP_LOG" 2>/dev/null || echo "0")
TOTAL_CACHE=$((CACHE_HITS + CACHE_MISSES))

if [ "$TOTAL_CACHE" -gt 0 ]; then
    HIT_RATE=$((CACHE_HITS * 100 / TOTAL_CACHE))
    echo -e "${CYAN}Cache Hits:${NC} $CACHE_HITS / $TOTAL_CACHE (${HIT_RATE}%)"

    if [ "$HIT_RATE" -ge 80 ]; then
        echo -e "${GREEN}✓${NC} Excellent Cache Performance"
    elif [ "$HIT_RATE" -ge 50 ]; then
        echo -e "${YELLOW}⚠${NC} Moderate Cache Performance"
    else
        echo -e "${RED}✗${NC} Poor Cache Performance"
    fi
fi

echo ""

# Errors
echo -e "${BLUE}═══ ERRORS & WARNINGS ═══${NC}"
echo ""

ERROR_COUNT=$(grep -c "ERROR" "$TEMP_LOG" 2>/dev/null || echo "0")
CRITICAL_COUNT=$(grep -c "CRITICAL" "$TEMP_LOG" 2>/dev/null || echo "0")
WARNING_COUNT=$(grep -c "WARNING" "$TEMP_LOG" 2>/dev/null || echo "0")

if [ "$CRITICAL_COUNT" -gt 0 ]; then
    echo -e "${RED}CRITICAL: $CRITICAL_COUNT${NC}"
    grep "CRITICAL" "$TEMP_LOG" | head -3 | while IFS= read -r line; do
        MSG=$(echo "$line" | sed 's/.*CRITICAL/CRITICAL/' | cut -c1-100)
        echo "  $MSG"
    done
    echo ""
fi

if [ "$ERROR_COUNT" -gt 0 ]; then
    echo -e "${RED}ERRORS: $ERROR_COUNT${NC}"
    grep "ERROR" "$TEMP_LOG" | grep -v "horizon" | head -3 | while IFS= read -r line; do
        MSG=$(echo "$line" | sed 's/.*ERROR/ERROR/' | cut -c1-100)
        echo "  $MSG"
    done
    echo ""
fi

if [ "$WARNING_COUNT" -gt 0 ]; then
    echo -e "${YELLOW}WARNINGS: $WARNING_COUNT${NC}"
fi

if [ "$ERROR_COUNT" -eq 0 ] && [ "$CRITICAL_COUNT" -eq 0 ]; then
    echo -e "${GREEN}✓ Keine Fehler - Anruf erfolgreich verarbeitet!${NC}"
fi

echo ""

# Summary
echo -e "${BLUE}═══ SUMMARY ═══${NC}"
echo ""

echo -e "${CYAN}Vollständiges Log:${NC} $TEMP_LOG"
echo -e "${CYAN}Zeilen:${NC} $LINE_COUNT"
echo ""

if [ "$ERROR_COUNT" -eq 0 ] && [ "$CALCOM_RESPONSES" -gt 0 ]; then
    echo -e "${GREEN}✅ CALL SUCCESSFUL${NC}"
elif [ "$ERROR_COUNT" -gt 0 ]; then
    echo -e "${RED}❌ CALL HAD ERRORS${NC}"
else
    echo -e "${YELLOW}⚠️  CALL INCOMPLETE${NC}"
fi

echo ""
echo "Für vollständige Log-Datei:"
echo "  less $TEMP_LOG"
echo ""
