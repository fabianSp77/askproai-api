#!/bin/bash

# MCP Zero-Downtime Deployment Script
# Version: 1.0.0
# Last Updated: 2025-06-21

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Configuration
APP_DIR="/var/www/api-gateway"
BACKUP_DIR="/var/backups/askproai"
LOG_FILE="/var/log/askproai-deploy.log"
HEALTH_CHECK_URL="http://localhost/api/health"
MAX_HEALTH_ATTEMPTS=30
DEPLOYMENT_TIMEOUT=600

# Functions
log() {
    echo -e "${GREEN}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1" | tee -a "$LOG_FILE"
    exit 1
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1" | tee -a "$LOG_FILE"
}

check_requirements() {
    log "Checking requirements..."
    
    command -v php >/dev/null 2>&1 || error "PHP is required"
    command -v composer >/dev/null 2>&1 || error "Composer is required"
    command -v npm >/dev/null 2>&1 || error "NPM is required"
    command -v redis-cli >/dev/null 2>&1 || error "Redis is required"
    
    # Check PHP version
    PHP_VERSION=$(php -r "echo PHP_VERSION;")
    if [[ ! "$PHP_VERSION" =~ ^8\.[12] ]]; then
        error "PHP 8.1 or 8.2 is required (found: $PHP_VERSION)"
    fi
    
    log "All requirements met ✓"
}

create_backup() {
    log "Creating backup..."
    
    cd "$APP_DIR"
    
    # Database backup
    php artisan askproai:backup --type=critical --encrypt || error "Database backup failed"
    
    # Code backup
    BACKUP_NAME="deploy_$(date +%Y%m%d_%H%M%S)"
    tar -czf "$BACKUP_DIR/code_$BACKUP_NAME.tar.gz" \
        --exclude=node_modules \
        --exclude=vendor \
        --exclude=storage/logs \
        --exclude=storage/framework/cache \
        . || warning "Code backup failed"
    
    log "Backup created ✓"
}

enable_maintenance() {
    log "Enabling maintenance mode..."
    
    cd "$APP_DIR"
    php artisan down \
        --message="System upgrade in progress. We'll be back shortly!" \
        --retry=60 \
        --allow=127.0.0.1 \
        --allow=::1
    
    log "Maintenance mode enabled ✓"
}

stop_services() {
    log "Stopping services..."
    
    # Stop Horizon gracefully
    cd "$APP_DIR"
    php artisan horizon:pause
    sleep 5
    php artisan horizon:terminate
    
    # Wait for running jobs
    JOBS_RUNNING=true
    WAIT_COUNT=0
    while $JOBS_RUNNING && [ $WAIT_COUNT -lt 30 ]; do
        RUNNING=$(redis-cli llen horizon:pending_jobs)
        if [ "$RUNNING" -eq "0" ]; then
            JOBS_RUNNING=false
        else
            log "Waiting for $RUNNING jobs to complete..."
            sleep 2
            ((WAIT_COUNT++))
        fi
    done
    
    log "Services stopped ✓"
}

deploy_code() {
    log "Deploying code..."
    
    cd "$APP_DIR"
    
    # Pull latest code
    git fetch origin main
    git reset --hard origin/main
    
    # Install dependencies
    export COMPOSER_ALLOW_SUPERUSER=1
    composer install --no-dev --optimize-autoloader --no-interaction
    
    # Build assets
    npm ci --production
    npm run build
    
    log "Code deployed ✓"
}

run_migrations() {
    log "Running migrations..."
    
    cd "$APP_DIR"
    
    # Analyze migrations first
    php artisan migrate:smart --analyze > /tmp/migration_analysis.txt
    
    if grep -q "UNSAFE" /tmp/migration_analysis.txt; then
        error "Unsafe migrations detected. Manual intervention required."
    fi
    
    # Run migrations with online mode
    php artisan migrate:smart --online --force
    
    log "Migrations completed ✓"
}

optimize_application() {
    log "Optimizing application..."
    
    cd "$APP_DIR"
    
    # Clear old caches
    php artisan optimize:clear
    
    # Generate new caches
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
    php artisan filament:cache-components
    
    # Warm critical caches
    php artisan cache:warm
    
    # Generate IDE helper files
    php artisan ide-helper:generate || true
    php artisan ide-helper:meta || true
    
    log "Application optimized ✓"
}

start_services() {
    log "Starting services..."
    
    cd "$APP_DIR"
    
    # Start Horizon
    nohup php artisan horizon > /dev/null 2>&1 &
    
    # Start schedule runner if not using cron
    # nohup php artisan schedule:work > /dev/null 2>&1 &
    
    log "Services started ✓"
}

health_check() {
    log "Running health checks..."
    
    ATTEMPTS=0
    while [ $ATTEMPTS -lt $MAX_HEALTH_ATTEMPTS ]; do
        HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$HEALTH_CHECK_URL")
        
        if [ "$HTTP_CODE" = "200" ]; then
            log "Health check passed ✓"
            return 0
        fi
        
        ((ATTEMPTS++))
        log "Health check attempt $ATTEMPTS/$MAX_HEALTH_ATTEMPTS (HTTP $HTTP_CODE)"
        sleep 2
    done
    
    error "Health checks failed after $MAX_HEALTH_ATTEMPTS attempts"
}

disable_maintenance() {
    log "Disabling maintenance mode..."
    
    cd "$APP_DIR"
    php artisan up
    
    log "Maintenance mode disabled ✓"
}

warm_caches() {
    log "Warming caches..."
    
    cd "$APP_DIR"
    
    # Warm API endpoints
    ENDPOINTS=(
        "/api/health"
        "/api/metrics"
        "/api/dashboard/stats"
    )
    
    for endpoint in "${ENDPOINTS[@]}"; do
        curl -s -H "X-Cache-Warm: true" "http://localhost$endpoint" > /dev/null
        log "Warmed cache for $endpoint"
    done
    
    log "Caches warmed ✓"
}

verify_deployment() {
    log "Verifying deployment..."
    
    cd "$APP_DIR"
    
    # Check application version
    VERSION=$(php artisan --version)
    log "Application version: $VERSION"
    
    # Check critical services
    php artisan health:check || warning "Some health checks failed"
    
    # Check queue processing
    php artisan horizon:status || warning "Horizon status check failed"
    
    # Check external services
    php artisan mcp:health || warning "MCP services degraded"
    
    log "Deployment verified ✓"
}

rollback() {
    error "Rollback initiated!"
    
    cd "$APP_DIR"
    
    # Enable maintenance mode
    php artisan down
    
    # Restore previous code
    if [ -f "$BACKUP_DIR/code_$BACKUP_NAME.tar.gz" ]; then
        tar -xzf "$BACKUP_DIR/code_$BACKUP_NAME.tar.gz" -C "$APP_DIR"
    fi
    
    # Rollback migrations
    php artisan migrate:rollback --step=5 --force
    
    # Clear caches
    php artisan optimize:clear
    
    # Restart services
    php artisan horizon:terminate
    php artisan horizon
    
    # Disable maintenance mode
    php artisan up
    
    error "Rollback completed. Please investigate the issue."
}

# Main deployment flow
main() {
    log "Starting MCP deployment..."
    
    # Set error handler
    trap rollback ERR
    
    # Deployment steps
    check_requirements
    create_backup
    enable_maintenance
    stop_services
    deploy_code
    run_migrations
    optimize_application
    start_services
    health_check
    disable_maintenance
    warm_caches
    verify_deployment
    
    # Remove error handler
    trap - ERR
    
    log "Deployment completed successfully! 🎉"
    
    # Send notification
    # curl -X POST https://hooks.slack.com/services/xxx -d '{"text":"AskProAI MCP deployment completed successfully!"}'
}

# Handle arguments
case "${1:-deploy}" in
    deploy)
        main
        ;;
    rollback)
        rollback
        ;;
    health)
        health_check
        ;;
    *)
        echo "Usage: $0 {deploy|rollback|health}"
        exit 1
        ;;
esac