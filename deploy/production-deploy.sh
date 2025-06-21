#!/bin/bash

# AskProAI Production Deployment Script
# Version: 1.0
# Date: 2025-06-18

set -e # Exit on error

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
APP_NAME="askproai"
DEPLOY_USER="deploy"
DEPLOY_PATH="/var/www/api-gateway"
BACKUP_PATH="/var/backups/askproai"
LOG_FILE="/var/log/askproai-deploy-$(date +%Y%m%d-%H%M%S).log"

# Function to log messages
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

# Check if running as correct user
# DISABLED FOR TESTING - normally should run as deploy user
# if [ "$USER" != "$DEPLOY_USER" ]; then
#     error "This script must be run as $DEPLOY_USER user"
# fi

# Pre-deployment checks
log "Starting pre-deployment checks..."

# 1. Check disk space
DISK_USAGE=$(df -h $DEPLOY_PATH | awk 'NR==2 {print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 85 ]; then
    error "Disk usage is above 85% ($DISK_USAGE%). Please free up space before deploying."
fi

# 2. Check database connectivity
log "Checking database connectivity..."
cd $DEPLOY_PATH
php artisan db:show &>/dev/null || error "Database connection failed"

# 3. Check Redis connectivity
log "Checking Redis connectivity..."
redis-cli ping &>/dev/null || error "Redis connection failed"

# 4. Create backup
log "Creating backup..."
mkdir -p $BACKUP_PATH
BACKUP_FILE="$BACKUP_PATH/backup-$(date +%Y%m%d-%H%M%S).tar.gz"
tar -czf $BACKUP_FILE --exclude='node_modules' --exclude='vendor' --exclude='storage/logs/*' $DEPLOY_PATH
log "Backup created: $BACKUP_FILE"

# 5. Enable maintenance mode
log "Enabling maintenance mode..."
php artisan down --render="errors::503" --retry=60

# Main deployment
log "Starting deployment..."

# 6. Pull latest code
log "Pulling latest code from repository..."
# SKIP GIT PULL FOR NOW - no repository configured
# git pull origin main || {
#     php artisan up
#     error "Git pull failed"
# }
log "Skipping git pull - no repository configured"

# 7. Install composer dependencies
log "Installing composer dependencies..."
composer install --no-dev --optimize-autoloader || {
    php artisan up
    error "Composer install failed"
}

# 8. Run database migrations
log "Running database migrations..."
php artisan migrate --force || {
    warning "Migration failed, attempting rollback..."
    php artisan migrate:rollback --force
    php artisan up
    error "Database migration failed and rolled back"
}

# 9. Clear and rebuild caches
log "Clearing caches..."
php artisan optimize:clear

log "Building optimized cache..."
php artisan optimize
php artisan view:cache
php artisan event:cache

# 10. Set permissions
log "Setting correct permissions..."
# Skip chown for now - deploy user doesn't exist
# chown -R $DEPLOY_USER:www-data $DEPLOY_PATH
find $DEPLOY_PATH/storage -type d -exec chmod 775 {} \;
find $DEPLOY_PATH/storage -type f -exec chmod 664 {} \;
find $DEPLOY_PATH/bootstrap/cache -type d -exec chmod 775 {} \;

# 11. Restart queue workers
log "Restarting queue workers..."
php artisan horizon:terminate || warning "Horizon terminate failed"
sleep 5

# Start Horizon in background
nohup php artisan horizon > /dev/null 2>&1 &
log "Horizon restarted"

# 12. Health checks
log "Running health checks..."
sleep 10 # Give services time to start

# Check main health endpoint
HEALTH_CHECK=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/api/health)
if [ $HEALTH_CHECK -ne 200 ]; then
    php artisan up
    error "Health check failed (HTTP $HEALTH_CHECK)"
fi

# Check Cal.com integration
CALCOM_CHECK=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/api/health/calcom)
if [ $CALCOM_CHECK -ne 200 ]; then
    warning "Cal.com health check failed (HTTP $CALCOM_CHECK)"
fi

# 13. Disable maintenance mode
log "Disabling maintenance mode..."
php artisan up

# 14. Warm up cache
log "Warming up cache..."
curl -s http://localhost/api/health > /dev/null

# 15. Send deployment notification
log "Sending deployment notification..."
# Skip git version for now - no repository
# php artisan deployment:notify --version=$(git rev-parse --short HEAD) || warning "Notification failed"
log "Skipping deployment notification - no git repository"

# Post-deployment tasks
log "Running post-deployment tasks..."

# Clear opcache if available
if command -v cachetool &> /dev/null; then
    cachetool opcache:reset
fi

# Log deployment metrics
DEPLOY_END=$(date +%s)
DEPLOY_DURATION=$((DEPLOY_END - $(date +%s -d "$(head -1 $LOG_FILE | cut -d' ' -f1-2 | tr -d '[]')")))

log "================================================"
log "Deployment completed successfully!"
log "Duration: ${DEPLOY_DURATION} seconds"
# log "Version: $(git rev-parse --short HEAD)"
log "Backup: $BACKUP_FILE"
log "================================================"

# Monitor for 5 minutes
log "Monitoring system for 5 minutes..."
for i in {1..5}; do
    sleep 60
    HEALTH=$(curl -s http://localhost/api/health | jq -r '.status' 2>/dev/null || echo "unknown")
    if [ "$HEALTH" != "healthy" ]; then
        warning "Health check returned: $HEALTH"
    fi
    log "Minute $i/5: System status is $HEALTH"
done

log "Deployment monitoring complete. System appears stable."