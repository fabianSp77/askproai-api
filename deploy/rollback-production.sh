#!/bin/bash

# AskProAI Production Rollback Script
# Version: 1.0.0
# Description: Emergency rollback script for failed deployments

set -e
set -o pipefail

# Configuration
APP_DIR="/var/www/api-gateway"
BACKUP_DIR="/var/backups/askproai"
LOG_FILE="/var/log/askproai/rollback.log"
SLACK_WEBHOOK_URL="${SLACK_WEBHOOK_URL:-}"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Logging
log() {
    echo -e "${GREEN}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[$(date '+%Y-%m-%d %H:%M:%S')] ERROR:${NC} $1" | tee -a "$LOG_FILE"
    exit 1
}

warning() {
    echo -e "${YELLOW}[$(date '+%Y-%m-%d %H:%M:%S')] WARNING:${NC} $1" | tee -a "$LOG_FILE"
}

# Send Slack notification
send_notification() {
    local message="$1"
    if [ -n "$SLACK_WEBHOOK_URL" ]; then
        curl -X POST -H 'Content-type: application/json' \
            --data "{\"text\":\"AskProAI Rollback: $message\",\"color\":\"#ff0000\"}" \
            "$SLACK_WEBHOOK_URL" 2>/dev/null || true
    fi
}

# List available backups
list_backups() {
    echo -e "\n${YELLOW}Available backups:${NC}"
    ls -1t "$BACKUP_DIR" | head -10 | nl
}

# Get backup selection
get_backup_selection() {
    list_backups
    echo -n "Select backup number to restore (or 'latest' for most recent): "
    read selection
    
    if [ "$selection" == "latest" ]; then
        BACKUP_NAME=$(ls -1t "$BACKUP_DIR" | head -1)
    else
        BACKUP_NAME=$(ls -1t "$BACKUP_DIR" | sed -n "${selection}p")
    fi
    
    if [ -z "$BACKUP_NAME" ]; then
        error "Invalid backup selection"
    fi
    
    BACKUP_PATH="$BACKUP_DIR/$BACKUP_NAME"
    
    if [ ! -d "$BACKUP_PATH" ]; then
        error "Backup directory not found: $BACKUP_PATH"
    fi
    
    echo -e "\n${YELLOW}Selected backup:${NC} $BACKUP_NAME"
    echo -n "Confirm rollback to this backup? (yes/no): "
    read confirm
    
    if [ "$confirm" != "yes" ]; then
        log "Rollback cancelled by user"
        exit 0
    fi
}

# Enable maintenance mode
enable_maintenance() {
    log "Enabling maintenance mode..."
    cd "$APP_DIR"
    php artisan down --retry=60 --secret="${MAINTENANCE_MODE_SECRET:-secret}"
}

# Restore database
restore_database() {
    log "Restoring database..."
    
    # Find database backup file
    db_backup=$(find "$BACKUP_PATH" -name "*.sql" -o -name "*.sql.gz" | head -1)
    
    if [ -z "$db_backup" ]; then
        error "No database backup found in $BACKUP_PATH"
    fi
    
    # Load database credentials from .env
    source "$APP_DIR/.env.production"
    
    # Restore database
    if [[ "$db_backup" == *.gz ]]; then
        gunzip -c "$db_backup" | mysql -h"$DB_HOST" -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE"
    else
        mysql -h"$DB_HOST" -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" < "$db_backup"
    fi
    
    log "Database restored from $db_backup"
}

# Restore application files
restore_files() {
    log "Restoring application files..."
    
    if [ ! -d "$BACKUP_PATH/app" ]; then
        warning "No application file backup found, skipping file restoration"
        return
    fi
    
    # Restore files (excluding runtime directories)
    rsync -avz --delete \
        --exclude='storage/logs/*' \
        --exclude='storage/app/public/*' \
        --exclude='storage/framework/cache/*' \
        --exclude='storage/framework/sessions/*' \
        --exclude='storage/framework/views/*' \
        --exclude='bootstrap/cache/*' \
        "$BACKUP_PATH/app/" "$APP_DIR/"
    
    # Restore .env if backed up
    if [ -f "$BACKUP_PATH/.env.production.backup" ]; then
        cp "$BACKUP_PATH/.env.production.backup" "$APP_DIR/.env.production"
        log "Environment file restored"
    fi
    
    log "Application files restored"
}

# Clear caches
clear_caches() {
    log "Clearing all caches..."
    
    cd "$APP_DIR"
    
    php artisan optimize:clear
    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    
    # Clear OPcache
    if [ -f /usr/local/bin/cachetool ]; then
        cachetool opcache:reset --fcgi=/var/run/php/php8.2-fpm.sock
    fi
    
    log "Caches cleared"
}

# Restart services
restart_services() {
    log "Restarting services..."
    
    systemctl restart php8.2-fpm
    systemctl restart nginx
    
    # Restart queue workers
    php artisan horizon:terminate
    sleep 5
    supervisorctl restart horizon
    
    log "Services restarted"
}

# Disable maintenance mode
disable_maintenance() {
    log "Disabling maintenance mode..."
    cd "$APP_DIR"
    php artisan up
}

# Run health checks
run_health_checks() {
    log "Running health checks..."
    
    sleep 10
    
    health_response=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/api/health)
    
    if [ "$health_response" != "200" ]; then
        warning "Health check returned status: $health_response"
    else
        log "Health check passed"
    fi
}

# Main rollback flow
main() {
    log "Starting AskProAI emergency rollback..."
    send_notification "Emergency rollback initiated!"
    
    get_backup_selection
    enable_maintenance
    restore_database
    restore_files
    clear_caches
    restart_services
    disable_maintenance
    run_health_checks
    
    log "Rollback completed successfully!"
    send_notification "Rollback completed to backup: $BACKUP_NAME"
    
    echo -e "\n${GREEN}=== Rollback Summary ===${NC}"
    echo "Restored from: $BACKUP_NAME"
    echo "Rollback time: $(date)"
    echo "Log file: $LOG_FILE"
    echo -e "${GREEN}========================${NC}\n"
    
    echo -e "${YELLOW}IMPORTANT:${NC} Please verify the application is working correctly!"
}

# Check if running as root or with sudo
if [ "$EUID" -ne 0 ]; then
    error "This script must be run as root or with sudo"
fi

# Run main function
main "$@"