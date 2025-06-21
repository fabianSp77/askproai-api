#!/bin/bash
# Monitor for Retell webhooks

echo "Monitoring for Retell webhooks..."
echo "Press Ctrl+C to stop"
echo ""

while true; do
    # Check nginx logs for recent webhook activity
    recent_webhooks=$(tail -100 /var/log/nginx/access.log | grep -i "retell\|webhook" | tail -5)
    
    if [ ! -z "$recent_webhooks" ]; then
        echo "=== WEBHOOK ACTIVITY DETECTED ==="
        echo "$recent_webhooks"
        echo "================================="
        echo ""
    fi
    
    # Check Laravel logs for webhook processing
    recent_processing=$(tail -100 /var/www/api-gateway/storage/logs/laravel.log | grep -i "retell\|webhook" | tail -5)
    
    if [ ! -z "$recent_processing" ]; then
        echo "=== WEBHOOK PROCESSING LOGS ==="
        echo "$recent_processing"
        echo "==============================="
        echo ""
    fi
    
    sleep 5
done