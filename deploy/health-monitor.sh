#!/bin/bash

# Health monitoring script
HEALTH_URL="http://localhost/api/health"
MAX_RETRIES=3
RETRY_DELAY=10

for i in $(seq 1 $MAX_RETRIES); do
    RESPONSE=$(curl -s -w "\n%{http_code}" "$HEALTH_URL")
    HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
    BODY=$(echo "$RESPONSE" | sed '$d')
    
    if [ "$HTTP_CODE" = "200" ]; then
        STATUS=$(echo "$BODY" | jq -r '.status' 2>/dev/null || echo "unknown")
        if [ "$STATUS" = "healthy" ]; then
            echo "[$(date)] Health check passed"
            exit 0
        fi
    fi
    
    echo "[$(date)] Health check failed (attempt $i/$MAX_RETRIES, HTTP $HTTP_CODE)"
    
    if [ $i -lt $MAX_RETRIES ]; then
        sleep $RETRY_DELAY
    fi
done

# Health check failed - send alert
echo "[$(date)] CRITICAL: Health check failed after $MAX_RETRIES attempts"

# Restart services if needed
cd /var/www/api-gateway
php artisan horizon:terminate
sleep 5
php artisan horizon &

# Send notification (implement your notification method)
# Example: Send to Slack, email, SMS, etc.
