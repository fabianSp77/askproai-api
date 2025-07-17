#!/bin/bash
# Emergency Retell.ai Integration Check
# Created: 2025-01-15

echo "🚨 EMERGENCY RETELL.AI CHECK"
echo "============================"
echo ""

# Check webhook endpoint
echo "1️⃣ Testing webhook endpoint..."
WEBHOOK_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" https://api.askproai.de/api/retell/webhook)
if [ "$WEBHOOK_RESPONSE" == "200" ] || [ "$WEBHOOK_RESPONSE" == "405" ]; then
    echo "✅ Webhook endpoint accessible (HTTP $WEBHOOK_RESPONSE)"
else
    echo "❌ WEBHOOK ENDPOINT ERROR! (HTTP $WEBHOOK_RESPONSE)"
fi

# Check recent calls
echo ""
echo "2️⃣ Recent call statistics..."
mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db -e "
SELECT 
    DATE(created_at) as date,
    COUNT(*) as calls,
    SUM(CASE WHEN appointment_id IS NOT NULL THEN 1 ELSE 0 END) as appointments,
    ROUND(AVG(duration_sec)) as avg_duration_sec
FROM calls 
WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(created_at)
ORDER BY date DESC;"

# Check webhook processing
echo ""
echo "3️⃣ Webhook processing status..."
mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db -e "
SELECT 
    COUNT(*) as total_webhooks,
    SUM(CASE WHEN status = 'processed' THEN 1 ELSE 0 END) as processed,
    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
    MAX(created_at) as last_webhook
FROM webhook_events 
WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR);"

# Check queue jobs
echo ""
echo "4️⃣ Queue job status..."
php artisan queue:work --stop-when-empty &
QUEUE_PID=$!
sleep 2
if ps -p $QUEUE_PID > /dev/null; then
    echo "✅ Queue worker running"
    kill $QUEUE_PID 2>/dev/null
else
    echo "❌ Queue worker NOT running!"
fi

# Check for failed jobs
FAILED_JOBS=$(mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db -e "SELECT COUNT(*) FROM failed_jobs WHERE failed_at > DATE_SUB(NOW(), INTERVAL 24 HOUR);" -s)
if [ "$FAILED_JOBS" -gt "0" ]; then
    echo "⚠️  $FAILED_JOBS failed jobs in last 24h!"
    mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db -e "SELECT id, queue, payload, exception FROM failed_jobs ORDER BY failed_at DESC LIMIT 5;"
fi

# Check Retell API connection
echo ""
echo "5️⃣ Testing Retell API..."
if [ -f ".env" ]; then
    RETELL_KEY=$(grep "DEFAULT_RETELL_API_KEY" .env | cut -d'=' -f2)
    if [ ! -z "$RETELL_KEY" ]; then
        API_TEST=$(curl -s -H "Authorization: Bearer $RETELL_KEY" https://api.retellai.com/v2/list-agents | head -c 50)
        if [[ $API_TEST == *"agent"* ]]; then
            echo "✅ Retell API connection working"
        else
            echo "❌ Retell API connection FAILED!"
            echo "Response: $API_TEST"
        fi
    else
        echo "❌ No Retell API key found in .env!"
    fi
fi

echo ""
echo "6️⃣ Recent errors in logs..."
grep -i "retell\|webhook\|appointment" storage/logs/laravel.log | tail -10

echo ""
echo "============================"
echo "🔍 DIAGNOSIS:"
echo ""

if [ "$FAILED_JOBS" -gt "0" ]; then
    echo "❌ CRITICAL: Failed jobs detected - webhooks may not be processing!"
    echo "   Fix: php artisan queue:retry all"
fi

RECENT_WEBHOOKS=$(mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db -e "SELECT COUNT(*) FROM webhook_events WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR);" -s)
if [ "$RECENT_WEBHOOKS" -eq "0" ]; then
    echo "❌ CRITICAL: No webhooks received in last hour!"
    echo "   Possible causes:"
    echo "   1. Retell webhook URL not configured"
    echo "   2. Webhook signature verification failing"
    echo "   3. Network/firewall blocking"
    echo ""
    echo "   CHECK NOW in Retell Dashboard:"
    echo "   https://retellai.com → Settings → Webhooks"
    echo "   URL should be: https://api.askproai.de/api/retell/webhook"
fi

echo ""
echo "📋 IMMEDIATE ACTIONS:"
echo "1. Check Retell.ai webhook configuration"
echo "2. Start queue worker: php artisan horizon"
echo "3. Process failed jobs: php artisan queue:retry all"
echo "4. Monitor: tail -f storage/logs/laravel.log"
echo ""