#!/usr/bin/env bash
set -euo pipefail
cd /var/www/api-gateway

echo "======================================================"
echo "POST GO-LIVE MONITORING"
echo "======================================================"
echo ""

# Route Statistik
echo "📊 Route Statistik:"
TOTAL_ROUTES=$(php artisan route:list 2>/dev/null | grep -c '^' || echo "0")
API_ROUTES=$(php artisan route:list 2>/dev/null | grep '^.*api/' | wc -l || echo "0")
V2_ROUTES=$(php artisan route:list 2>/dev/null | grep '^.*api/v2/' | wc -l || echo "0")
echo "• Total Routes: $TOTAL_ROUTES"
echo "• API Routes: $API_ROUTES"
echo "• V2 Routes: $V2_ROUTES"

echo ""
echo "🔄 Queue Status:"
FAILED_COUNT=$(php artisan queue:failed 2>/dev/null | grep -c "^|" || echo "0")
echo "• Failed jobs: $FAILED_COUNT"

if [ "$FAILED_COUNT" -gt 0 ]; then
    echo "• Letzte Failed Jobs:"
    php artisan queue:failed 2>/dev/null | head -5
fi

echo ""
echo "💾 Redis Status:"
REDIS_STATUS=$(redis-cli ping 2>/dev/null || echo "OFFLINE")
if [ "$REDIS_STATUS" = "PONG" ]; then
    echo "• Redis: ✅ Online"
    REDIS_OPS=$(redis-cli info stats 2>/dev/null | grep "instantaneous_ops_per_sec:" | cut -d: -f2 | tr -d '\r')
    echo "• Operations/sec: ${REDIS_OPS:-0}"
else
    echo "• Redis: ❌ Offline"
fi

echo ""
echo "🔍 Live Request Monitoring:"
echo "• Aktuelle Requests/min: $(grep "$(date '+%Y-%m-%d %H:%M')" storage/logs/laravel.log 2>/dev/null | wc -l || echo "0")"
echo "• Fehler letzte 5 Min: $(grep ERROR storage/logs/laravel.log 2>/dev/null | tail -100 | grep "$(date '+%Y-%m-%d %H:')" | wc -l || echo "0")"

echo ""
echo "📁 Log-Größen:"
ls -lh storage/logs/*.log 2>/dev/null | tail -5 | awk '{print "• "$9": "$5}'

echo ""
echo "======================================================"
echo "📡 LIVE LOG STREAM (sanitized)"
echo "======================================================"
echo "STRG+C zum Beenden"
echo ""

# Tail logs mit PII-Maskierung
tail -n 200 -f storage/logs/laravel.log 2>/dev/null | \
    sed -E 's/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/[email]/g' | \
    sed -E 's/\+49[0-9]{9,}/[phone]/g' | \
    sed -E 's/"email":"[^"]+"/\"email\":\"[email]\"/g' | \
    sed -E 's/"phone":"[^"]+"/\"phone\":\"[phone]\"/g' | \
    sed -E 's/Bearer [A-Za-z0-9._-]+/Bearer [token]/g' | \
    sed -E 's/"password":"[^"]+"/\"password\":\"[hidden]\"/g' | \
    sed -E 's/cal_live_[a-z0-9]+/cal_live_[key]/g' | \
    grep -E --color=auto "(ERROR|WARNING|INFO|DEBUG|api/v2|calcom|composite|booking|drift)"