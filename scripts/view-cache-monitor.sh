#!/bin/bash
# View Cache Health Monitor
# Continuously monitors and fixes view cache issues

LOG_FILE="/var/www/api-gateway/storage/logs/view-cache-monitor.log"
VIEW_PATH="/tmp/laravel-views"
STORAGE_VIEW_PATH="/var/www/api-gateway/storage/framework/views"

# Function to log messages
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

# Function to check and fix view cache
check_and_fix() {
    # Ensure /tmp/laravel-views exists and has correct permissions
    if [ ! -d "$VIEW_PATH" ]; then
        log_message "Creating $VIEW_PATH directory"
        mkdir -p "$VIEW_PATH"
        chmod 777 "$VIEW_PATH"
        chown www-data:www-data "$VIEW_PATH"
    fi
    
    # Check if permissions are correct
    perms=$(stat -c "%a" "$VIEW_PATH" 2>/dev/null)
    if [ "$perms" != "777" ]; then
        log_message "Fixing permissions on $VIEW_PATH"
        chmod 777 "$VIEW_PATH"
        chown www-data:www-data "$VIEW_PATH"
    fi
    
    # Clean up old compiled views (older than 1 hour)
    find "$VIEW_PATH" -name "*.php" -type f -mmin +60 -delete 2>/dev/null
    
    # If storage views directory exists, clean it too
    if [ -d "$STORAGE_VIEW_PATH" ]; then
        # Remove any files causing issues
        find "$STORAGE_VIEW_PATH" -name "*.php" -type f ! -readable -delete 2>/dev/null
        find "$STORAGE_VIEW_PATH" -name "*.php" -type f -size 0 -delete 2>/dev/null
    fi
    
    # Check disk space
    disk_usage=$(df "$VIEW_PATH" | awk 'NR==2 {print $5}' | sed 's/%//')
    if [ "$disk_usage" -gt 90 ]; then
        log_message "WARNING: Disk usage at $disk_usage% - clearing all view cache"
        rm -f "$VIEW_PATH"/*.php
        rm -f "$STORAGE_VIEW_PATH"/*.php 2>/dev/null
    fi
    
    # Check inode usage
    inode_usage=$(df -i "$VIEW_PATH" | awk 'NR==2 {print $5}' | sed 's/%//')
    if [ "$inode_usage" -gt 90 ]; then
        log_message "WARNING: Inode usage at $inode_usage% - clearing all view cache"
        rm -f "$VIEW_PATH"/*.php
        rm -f "$STORAGE_VIEW_PATH"/*.php 2>/dev/null
    fi
}

# Main monitoring loop
log_message "View cache monitor started"

while true; do
    check_and_fix
    
    # Sleep for 30 seconds before next check
    sleep 30
done