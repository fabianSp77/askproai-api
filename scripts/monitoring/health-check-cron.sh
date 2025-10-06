#!/bin/bash
LOG_FILE="/var/www/api-gateway/storage/monitoring/health-check.log"
ALERT_FILE="/var/www/api-gateway/storage/monitoring/alerts.log"

# Run health check
/var/www/api-gateway/tests/quick-health-check.sh >> "$LOG_FILE" 2>&1
EXIT_CODE=$?

# If issues found, log alert
if [ $EXIT_CODE -ne 0 ]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Health check failed with $EXIT_CODE issues" >> "$ALERT_FILE"

    # Optional: Send alert (customize as needed)
    # echo "Health check failed" | mail -s "Alert: AskPro AI Health Check Failed" admin@example.com
fi

# Keep only last 7 days of logs
find /var/www/api-gateway/storage/monitoring -name "*.log" -mtime +7 -delete
