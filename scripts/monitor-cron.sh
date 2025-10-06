#!/bin/bash

# AskPro AI Gateway Monitoring Cron Script
# This script is called by cron every 5 minutes

LARAVEL_PATH="/var/www/api-gateway"
LOG_PATH="$LARAVEL_PATH/storage/logs"
DATE=$(date '+%Y-%m-%d %H:%M:%S')

# Function to run Laravel command and log output
run_monitor() {
    COMMAND=$1
    LOG_FILE=$2
    
    echo "[$DATE] Running: php artisan $COMMAND" >> "$LOG_PATH/$LOG_FILE"
    cd $LARAVEL_PATH
    /usr/bin/php artisan $COMMAND >> "$LOG_PATH/$LOG_FILE" 2>&1
    EXIT_CODE=$?
    
    if [ $EXIT_CODE -ne 0 ]; then
        echo "[$DATE] ERROR: Command failed with exit code $EXIT_CODE" >> "$LOG_PATH/$LOG_FILE"
    fi
    
    return $EXIT_CODE
}

# 1. Health Check (every 5 minutes)
run_monitor "monitor:health" "health-check.log"
HEALTH_STATUS=$?

# 2. Error Monitoring (every 5 minutes) 
run_monitor "monitor:errors --minutes=5 --threshold=25" "error-monitor.log"
ERROR_STATUS=$?

# 3. Cache Monitoring (every 5 minutes)
run_monitor "monitor:cache" "cache-monitor.log"
CACHE_STATUS=$?

# 4. Cache Warming (every 5 minutes)
run_monitor "cache:warm" "cache-warm.log"
WARM_STATUS=$?

# 5. Check if any monitoring failed
if [ $HEALTH_STATUS -ne 0 ] || [ $ERROR_STATUS -ne 0 ] || [ $CACHE_STATUS -ne 0 ]; then
    echo "[$DATE] ALERT: Monitoring detected issues!" >> "$LOG_PATH/alerts.log"
    
    # Could trigger external alerting here
    # Example: send email, SMS, or webhook
    
    # Log to system journal for visibility
    logger -t "askproai-monitor" "Monitoring alert triggered - check $LOG_PATH/alerts.log"
fi

echo "[$DATE] Monitoring cycle completed" >> "$LOG_PATH/monitor.log"