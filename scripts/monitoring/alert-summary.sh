#!/bin/bash

ALERT_FILE="/var/www/api-gateway/storage/monitoring/alerts.log"

if [ ! -f "$ALERT_FILE" ]; then
    echo "No alerts found"
    exit 0
fi

# Count alerts by type
echo "Alert Summary (Last 24 hours):"
echo "=============================="

# Get alerts from last 24 hours
yesterday=$(date -d "24 hours ago" '+%Y-%m-%d %H:%M:%S')
recent_alerts=$(awk -v date="$yesterday" '$0 >= "["date' "$ALERT_FILE")

if [ -z "$recent_alerts" ]; then
    echo "No alerts in the last 24 hours"
else
    echo "$recent_alerts" | awk '{
        if (/High CPU/) cpu++
        else if (/High memory/) mem++
        else if (/Health check failed/) health++
        else if (/High error rate/) errors++
        else if (/orphaned/) orphaned++
        else other++
    }
    END {
        if (cpu > 0) printf "High CPU alerts: %d\n", cpu
        if (mem > 0) printf "High memory alerts: %d\n", mem
        if (health > 0) printf "Health check failures: %d\n", health
        if (errors > 0) printf "High error rate alerts: %d\n", errors
        if (orphaned > 0) printf "Orphaned records alerts: %d\n", orphaned
        if (other > 0) printf "Other alerts: %d\n", other
    }'
fi
