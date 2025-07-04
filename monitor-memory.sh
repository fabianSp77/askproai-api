#!/bin/bash

# Memory monitoring script for AskProAI

# Check memory usage
MEMORY_USAGE=$(LANG=C free | awk '/^Mem:/ {printf "%.0f", ($3/$2) * 100}')
MEMORY_USAGE_INT=${MEMORY_USAGE:-0}

# Check Horizon processes
HORIZON_COUNT=$(ps aux | grep horizon | grep -v grep | wc -l)

# Log directory
LOG_DIR="/var/www/api-gateway/storage/logs"
LOG_FILE="$LOG_DIR/memory-monitor.log"

# Current timestamp
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')

# Log current status
echo "[$TIMESTAMP] Memory: ${MEMORY_USAGE_INT}% | Horizon processes: $HORIZON_COUNT" >> $LOG_FILE

# Alert if memory usage is above 80%
if [ $MEMORY_USAGE_INT -gt 80 ]; then
    echo "[$TIMESTAMP] WARNING: High memory usage detected (${MEMORY_USAGE_INT}%)" >> $LOG_FILE
    
    # Restart Horizon if too many processes
    if [ $HORIZON_COUNT -gt 60 ]; then
        echo "[$TIMESTAMP] ALERT: Too many Horizon processes ($HORIZON_COUNT), restarting..." >> $LOG_FILE
        sudo supervisorctl restart horizon
    fi
fi

# Alert if too many Horizon processes
if [ $HORIZON_COUNT -gt 100 ]; then
    echo "[$TIMESTAMP] CRITICAL: Excessive Horizon processes ($HORIZON_COUNT), force restart!" >> $LOG_FILE
    sudo supervisorctl stop horizon
    pkill -f horizon
    sleep 5
    sudo supervisorctl start horizon
fi

# Clean up old logs (older than 7 days)
find $LOG_DIR -name "*.log" -mtime +7 -exec rm {} \; 2>/dev/null

# Report status
echo "Memory Usage: ${MEMORY_USAGE_INT}% | Horizon Processes: $HORIZON_COUNT"