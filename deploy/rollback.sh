#!/bin/bash

# AskProAI Emergency Rollback Script
# Version: 1.0
# Date: 2025-06-18

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Configuration
DEPLOY_PATH="/var/www/api-gateway"
BACKUP_PATH="/var/backups/askproai"
LOG_FILE="/var/log/askproai-rollback-$(date +%Y%m%d-%H%M%S).log"

log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1" | tee -a $LOG_FILE
}

error() {
    echo -e "${RED}[ERROR]${NC} $1" | tee -a $LOG_FILE
    exit 1
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1" | tee -a $LOG_FILE
}

# Get latest backup or use provided backup
if [ -z "$1" ]; then
    BACKUP_FILE=$(ls -t $BACKUP_PATH/backup-*.tar.gz 2>/dev/null | head -1)
    if [ -z "$BACKUP_FILE" ]; then
        error "No backup found. Please provide backup file as argument."
    fi
    log "Using latest backup: $BACKUP_FILE"
else
    BACKUP_FILE="$1"
    if [ ! -f "$BACKUP_FILE" ]; then
        error "Backup file not found: $BACKUP_FILE"
    fi
fi

# Confirmation
echo -e "${RED}WARNING: This will rollback to: $BACKUP_FILE${NC}"
echo -e "${RED}All current data will be LOST!${NC}"
read -p "Are you sure? Type 'ROLLBACK' to continue: " CONFIRM

if [ "$CONFIRM" != "ROLLBACK" ]; then
    log "Rollback cancelled by user"
    exit 0
fi

# Start rollback
log "Starting emergency rollback..."

# 1. Enable maintenance mode
log "Enabling maintenance mode..."
cd $DEPLOY_PATH
php artisan down --message="Emergency maintenance. We'll be back soon!" || warning "Failed to enable maintenance mode"

# 2. Stop services
log "Stopping services..."
php artisan horizon:terminate || warning "Failed to stop Horizon"
sleep 5

# 3. Backup current state (just in case)
log "Creating backup of current state..."
CURRENT_BACKUP="$BACKUP_PATH/pre-rollback-$(date +%Y%m%d-%H%M%S).tar.gz"
tar -czf $CURRENT_BACKUP --exclude='node_modules' --exclude='vendor' --exclude='storage/logs/*' $DEPLOY_PATH
log "Current state backed up to: $CURRENT_BACKUP"

# 4. Extract backup
log "Extracting backup..."
cd /
tar -xzf $BACKUP_FILE || error "Failed to extract backup"

# 5. Restore permissions
log "Restoring permissions..."
chown -R deploy:www-data $DEPLOY_PATH
find $DEPLOY_PATH/storage -type d -exec chmod 775 {} \;
find $DEPLOY_PATH/storage -type f -exec chmod 664 {} \;

# 6. Clear caches
log "Clearing all caches..."
cd $DEPLOY_PATH
php artisan optimize:clear

# 7. Reinstall dependencies (in case of version mismatch)
log "Reinstalling dependencies..."
composer install --no-dev --optimize-autoloader
npm ci --production

# 8. Run migrations (if needed)
log "Checking database state..."
php artisan migrate:status || warning "Could not check migration status"

# 9. Restart services
log "Restarting services..."
nohup php artisan horizon > /dev/null 2>&1 &

# 10. Health check
log "Running health checks..."
sleep 10

HEALTH_CHECK=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/api/health)
if [ $HEALTH_CHECK -ne 200 ]; then
    error "Health check failed after rollback (HTTP $HEALTH_CHECK)"
fi

# 11. Disable maintenance mode
log "Disabling maintenance mode..."
php artisan up

log "================================================"
log "Rollback completed successfully!"
log "Restored from: $BACKUP_FILE"
log "Pre-rollback backup: $CURRENT_BACKUP"
log "================================================"

# Monitor
log "Monitoring system..."
for i in {1..3}; do
    sleep 20
    HEALTH=$(curl -s http://localhost/api/health | jq -r '.status' 2>/dev/null || echo "unknown")
    log "Health check $i/3: $HEALTH"
done

log "Rollback complete. Please verify system functionality."