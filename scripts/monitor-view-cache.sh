#!/bin/bash

# View Cache Monitoring Script
# Checks for view cache issues and auto-fixes them

LOG_FILE="/var/www/api-gateway/storage/logs/view-cache-monitor.log"
ERROR_COUNT=0
MAX_ERRORS=3

# Function to log messages
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
    echo "$1"
}

# Function to check cache health
check_cache_health() {
    # Test if admin page loads
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" https://api.askproai.de/admin)
    
    if [ "$HTTP_CODE" != "200" ]; then
        return 1
    fi
    return 0
}

# Function to fix cache
fix_cache() {
    log_message "WARNING: Cache issue detected, attempting fix..."
    /var/www/api-gateway/scripts/auto-fix-cache.sh >> "$LOG_FILE" 2>&1
    sleep 2
}

# Main monitoring loop
log_message "Starting view cache monitor..."

while true; do
    if ! check_cache_health; then
        ERROR_COUNT=$((ERROR_COUNT + 1))
        log_message "ERROR: Cache health check failed (attempt $ERROR_COUNT/$MAX_ERRORS)"
        
        if [ $ERROR_COUNT -ge $MAX_ERRORS ]; then
            log_message "CRITICAL: Maximum error count reached, running full fix..."
            fix_cache
            ERROR_COUNT=0
        fi
    else
        if [ $ERROR_COUNT -gt 0 ]; then
            log_message "SUCCESS: Cache health restored"
        fi
        ERROR_COUNT=0
    fi
    
    # Check every 5 minutes
    sleep 300
done