#!/bin/bash
LOG_FILE="/var/www/api-gateway/storage/monitoring/error-monitor.log"
ALERT_FILE="/var/www/api-gateway/storage/monitoring/alerts.log"
LARAVEL_LOG="/var/www/api-gateway/storage/logs/laravel.log"

echo "====== Error Monitor: $(date) ======" >> "$LOG_FILE"

# Count errors in last 15 minutes
if [ -f "$LARAVEL_LOG" ]; then
    errors=$(find /var/www/api-gateway/storage/logs -name "*.log" -mmin -15 -exec grep -c "ERROR" {} \; 2>/dev/null | paste -sd+ | bc 2>/dev/null || echo "0")
    warnings=$(find /var/www/api-gateway/storage/logs -name "*.log" -mmin -15 -exec grep -c "WARNING" {} \; 2>/dev/null | paste -sd+ | bc 2>/dev/null || echo "0")

    echo "Errors (15 min): $errors" >> "$LOG_FILE"
    echo "Warnings (15 min): $warnings" >> "$LOG_FILE"

    if [ "$errors" -gt 50 ]; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] High error rate: $errors errors in 15 minutes" >> "$ALERT_FILE"
    fi

    # Log last error
    last_error=$(grep "ERROR" "$LARAVEL_LOG" | tail -1 | cut -c1-200)
    if [ -n "$last_error" ]; then
        echo "Last error: $last_error" >> "$LOG_FILE"
    fi
fi

echo "" >> "$LOG_FILE"
