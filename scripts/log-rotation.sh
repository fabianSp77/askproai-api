#!/bin/bash

# AskProAI Log Rotation Script
# Runs daily to clean up old log files

LOG_DIR="/var/www/api-gateway/storage/logs"
DAYS_TO_KEEP=7
LOG_FILE="/var/www/api-gateway/storage/logs/rotation.log"

echo "[$(date)] Starting log rotation..." >> "$LOG_FILE"

# Find and delete log files older than 7 days
find "$LOG_DIR" -name "*.log" -type f -mtime +$DAYS_TO_KEEP -exec rm -f {} \; 2>> "$LOG_FILE"

# Compress log files older than 1 day but newer than 7 days
find "$LOG_DIR" -name "*.log" -type f -mtime +1 -mtime -$DAYS_TO_KEEP ! -name "*.gz" -exec gzip {} \; 2>> "$LOG_FILE"

# Calculate disk usage after cleanup
USAGE=$(du -sh "$LOG_DIR" | cut -f1)
echo "[$(date)] Log rotation completed. Current log directory size: $USAGE" >> "$LOG_FILE"

# Keep rotation log small (max 1MB)
if [ -f "$LOG_FILE" ] && [ $(stat -c%s "$LOG_FILE") -gt 1048576 ]; then
    tail -n 1000 "$LOG_FILE" > "$LOG_FILE.tmp"
    mv "$LOG_FILE.tmp" "$LOG_FILE"
fi