#!/bin/bash

# Monitoring Script für Retell Test Call Verification
# Usage: ./monitor_test_call.sh

echo "═══════════════════════════════════════════════════════"
echo "🔍 RETELL WEBHOOK MONITORING GESTARTET"
echo "═══════════════════════════════════════════════════════"
echo ""
echo "Drücke CTRL+C zum Beenden"
echo ""
echo "Anweisungen:"
echo "1. Dieses Script laufen lassen"
echo "2. Testanruf machen"
echo "3. Beobachte die Ausgabe hier"
echo "4. Nach dem Anruf: CTRL+C drücken"
echo ""
echo "═══════════════════════════════════════════════════════"
echo ""

# Timestamp für Start
START_TIME=$(date +%s)
echo "⏰ Start: $(date '+%Y-%m-%d %H:%M:%S')"
echo ""

# Counter für Webhooks
WEBHOOK_COUNT=0

# Monitor middleware log
echo "📡 Überwache Webhook-Eingang..."
echo ""

# Tail beide logs gleichzeitig
tail -f /tmp/retell_middleware_test.log 2>/dev/null &
MIDDLEWARE_PID=$!

# Monitor nginx access log for Retell webhooks
tail -f /var/log/nginx/access.log 2>/dev/null | grep --line-buffered "POST /api/webhooks/retell" | while read line; do
    ((WEBHOOK_COUNT++))
    ELAPSED=$(($(date +%s) - START_TIME))
    echo "[${ELAPSED}s] 🌐 NGINX: $line"
done &
NGINX_PID=$!

# Monitor Laravel log for webhook processing
tail -f /var/www/api-gateway/storage/logs/laravel.log 2>/dev/null | grep --line-buffered -i "retell\|webhook" | while read line; do
    ELAPSED=$(($(date +%s) - START_TIME))
    echo "[${ELAPSED}s] 📋 LARAVEL: $line"
done &
LARAVEL_PID=$!

# Cleanup function
cleanup() {
    echo ""
    echo "═══════════════════════════════════════════════════════"
    echo "🛑 MONITORING BEENDET"
    echo "═══════════════════════════════════════════════════════"

    TOTAL_TIME=$(($(date +%s) - START_TIME))
    echo "⏱️  Gesamtdauer: ${TOTAL_TIME} Sekunden"
    echo ""

    # Count webhooks received
    MIDDLEWARE_HITS=$(wc -l < /tmp/retell_middleware_test.log 2>/dev/null || echo "0")
    echo "📊 Webhook Statistik:"
    echo "   - Middleware Executions: $MIDDLEWARE_HITS"
    echo ""

    # Show latest calls
    echo "🔍 Überprüfe neueste Calls in Database..."
    php /var/www/api-gateway/debug_latest_calls.php 2>/dev/null | head -50

    # Kill background processes
    kill $MIDDLEWARE_PID 2>/dev/null
    kill $NGINX_PID 2>/dev/null
    kill $LARAVEL_PID 2>/dev/null

    echo ""
    echo "✅ Monitoring abgeschlossen"
    echo ""
    echo "Next Steps:"
    echo "1. Prüfe die Ausgabe oben für Webhook-Aktivität"
    echo "2. Wenn Webhooks empfangen: php analyze_test_call.php"
    echo "3. Wenn KEINE Webhooks: Weitere Debugging notwendig"
    exit 0
}

# Trap CTRL+C
trap cleanup SIGINT SIGTERM

# Keep script running
wait
