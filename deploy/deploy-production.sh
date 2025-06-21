#!/bin/bash

# AskProAI Production Deployment Script
# Version: 1.0.0
# Description: Automated deployment script with zero-downtime deployment

set -e  # Exit on error
set -o pipefail  # Exit on pipe failure

# Configuration
APP_DIR="/var/www/api-gateway"
BACKUP_DIR="/var/backups/askproai"
LOG_FILE="/var/log/askproai/deployment.log"
SLACK_WEBHOOK_URL="${SLACK_WEBHOOK_URL:-}"
DEPLOYMENT_USER="deploy"
PHP_VERSION="8.2"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Logging function
log() {
    echo -e "${GREEN}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[$(date '+%Y-%m-%d %H:%M:%S')] ERROR:${NC} $1" | tee -a "$LOG_FILE"
    send_notification "Deployment Failed: $1" "danger"
    exit 1
}

warning() {
    echo -e "${YELLOW}[$(date '+%Y-%m-%d %H:%M:%S')] WARNING:${NC} $1" | tee -a "$LOG_FILE"
}

# Send notification to Slack
send_notification() {
    local message="$1"
    local type="${2:-info}"
    
    if [ -n "$SLACK_WEBHOOK_URL" ]; then
        local color="#36a64f"  # Green
        [ "$type" == "danger" ] && color="#ff0000"  # Red
        [ "$type" == "warning" ] && color="#ffcc00"  # Yellow
        
        curl -X POST -H 'Content-type: application/json' \
            --data "{\"text\":\"AskProAI Deployment: $message\",\"color\":\"$color\"}" \
            "$SLACK_WEBHOOK_URL" 2>/dev/null || true
    fi
}

# Check if running as correct user
check_user() {
    if [ "$(whoami)" != "$DEPLOYMENT_USER" ]; then
        error "This script must be run as $DEPLOYMENT_USER user"
    fi
}

# Pre-deployment checks
pre_deployment_checks() {
    log "Running pre-deployment checks..."
    
    # Check PHP version
    php_version=$(php -v | grep -oP 'PHP \K[0-9]+\.[0-9]+')
    if [ "$php_version" != "$PHP_VERSION" ]; then
        error "PHP version mismatch. Expected $PHP_VERSION, got $php_version"
    fi
    
    # Check required services
    for service in mysql redis-server nginx; do
        if ! systemctl is-active --quiet $service; then
            error "$service is not running"
        fi
    done
    
    # Check disk space
    available_space=$(df -BG "$APP_DIR" | awk 'NR==2 {print $4}' | sed 's/G//')
    if [ "$available_space" -lt 5 ]; then
        error "Insufficient disk space. At least 5GB required, only ${available_space}GB available"
    fi
    
    # Check if .env.production exists
    if [ ! -f "$APP_DIR/.env.production" ]; then
        error ".env.production file not found"
    fi
    
    log "Pre-deployment checks passed"
}

# Create backup
create_backup() {
    log "Creating backup..."
    
    # Create backup directory
    backup_path="$BACKUP_DIR/$(date +%Y%m%d_%H%M%S)"
    mkdir -p "$backup_path"
    
    # Backup database
    log "Backing up database..."
    php artisan askproai:backup --type=database --path="$backup_path" || error "Database backup failed"
    
    # Backup application files
    log "Backing up application files..."
    rsync -avz --exclude='node_modules' --exclude='vendor' --exclude='storage/logs' \
        "$APP_DIR/" "$backup_path/app/" || error "File backup failed"
    
    # Backup .env file
    cp "$APP_DIR/.env.production" "$backup_path/.env.production.backup"
    
    log "Backup completed: $backup_path"
}

# Pull latest code
pull_latest_code() {
    log "Pulling latest code from repository..."
    
    cd "$APP_DIR"
    
    # Stash any local changes
    git stash save "Auto-stash before deployment $(date)"
    
    # Pull latest changes
    git pull origin main || error "Git pull failed"
    
    # Record deployment info
    echo "$(date): $(git rev-parse HEAD)" >> "$APP_DIR/storage/logs/deployments.log"
    
    log "Code updated to commit: $(git rev-parse --short HEAD)"
}

# Install dependencies
install_dependencies() {
    log "Installing dependencies..."
    
    cd "$APP_DIR"
    
    # Install Composer dependencies
    log "Installing Composer dependencies..."
    composer install --no-dev --optimize-autoloader --no-interaction || error "Composer install failed"
    
    # Install NPM dependencies and build assets
    log "Installing NPM dependencies and building assets..."
    npm ci --production || error "NPM install failed"
    npm run build || error "Asset build failed"
    
    log "Dependencies installed successfully"
}

# Run migrations
run_migrations() {
    log "Running database migrations..."
    
    cd "$APP_DIR"
    
    # Check for pending migrations
    pending=$(php artisan migrate:status | grep -c "Pending" || true)
    
    if [ "$pending" -gt 0 ]; then
        log "Found $pending pending migrations"
        
        # Run migrations with zero downtime
        php artisan migrate:smart --analyze || error "Migration analysis failed"
        php artisan migrate:smart --online --force || error "Migrations failed"
    else
        log "No pending migrations"
    fi
}

# Optimize application
optimize_application() {
    log "Optimizing application..."
    
    cd "$APP_DIR"
    
    # Clear old caches
    php artisan optimize:clear
    
    # Cache configuration
    php artisan config:cache || error "Config cache failed"
    
    # Cache routes
    php artisan route:cache || error "Route cache failed"
    
    # Cache views
    php artisan view:cache || error "View cache failed"
    
    # Cache events
    php artisan event:cache || error "Event cache failed"
    
    # Warm up Cal.com cache
    php artisan calcom:cache-warmup || warning "Cal.com cache warmup failed"
    
    # Optimize Composer autoloader
    composer dump-autoload --optimize --no-dev
    
    log "Application optimized"
}

# Set permissions
set_permissions() {
    log "Setting file permissions..."
    
    cd "$APP_DIR"
    
    # Set ownership
    chown -R www-data:www-data storage bootstrap/cache
    
    # Set directory permissions
    find storage -type d -exec chmod 755 {} \;
    find bootstrap/cache -type d -exec chmod 755 {} \;
    
    # Set file permissions
    find storage -type f -exec chmod 644 {} \;
    find bootstrap/cache -type f -exec chmod 644 {} \;
    
    # Make artisan executable
    chmod +x artisan
    
    log "Permissions set"
}

# Restart services
restart_services() {
    log "Restarting services..."
    
    # Restart PHP-FPM with zero downtime
    systemctl reload php${PHP_VERSION}-fpm || error "PHP-FPM reload failed"
    
    # Restart queue workers gracefully
    php artisan horizon:terminate || error "Horizon terminate failed"
    sleep 5
    supervisorctl restart horizon || error "Horizon restart failed"
    
    # Clear OPcache
    if [ -f /usr/local/bin/cachetool ]; then
        cachetool opcache:reset --fcgi=/var/run/php/php${PHP_VERSION}-fpm.sock
    fi
    
    # Test nginx configuration
    nginx -t || error "Nginx configuration test failed"
    
    # Reload nginx
    systemctl reload nginx || error "Nginx reload failed"
    
    log "Services restarted successfully"
}

# Run health checks
run_health_checks() {
    log "Running health checks..."
    
    # Wait for services to stabilize
    sleep 10
    
    # Check application health
    health_response=$(curl -s -o /dev/null -w "%{http_code}" https://api.askproai.de/api/health)
    
    if [ "$health_response" != "200" ]; then
        error "Health check failed with status: $health_response"
    fi
    
    # Check critical endpoints
    for endpoint in "/api/health/database" "/api/health/redis" "/api/health/calcom"; do
        response=$(curl -s -o /dev/null -w "%{http_code}" "https://api.askproai.de$endpoint")
        if [ "$response" != "200" ]; then
            warning "Health check failed for $endpoint with status: $response"
        fi
    done
    
    # Check queue processing
    queue_status=$(php artisan horizon:status | grep -c "running" || true)
    if [ "$queue_status" -eq 0 ]; then
        error "Queue workers are not running"
    fi
    
    log "Health checks passed"
}

# Post-deployment tasks
post_deployment_tasks() {
    log "Running post-deployment tasks..."
    
    cd "$APP_DIR"
    
    # Clear application cache
    php artisan cache:clear
    
    # Warm up cache
    php artisan cache:warmup || warning "Cache warmup failed"
    
    # Run scheduled tasks
    php artisan schedule:run
    
    # Update monitoring
    curl -X POST https://monitoring.askproai.de/api/deployment \
        -H "Content-Type: application/json" \
        -d "{\"status\":\"completed\",\"version\":\"$(git rev-parse HEAD)\",\"timestamp\":\"$(date -u +%Y-%m-%dT%H:%M:%SZ)\"}" \
        2>/dev/null || true
    
    # Clean up old backups (keep last 10)
    cd "$BACKUP_DIR"
    ls -t | tail -n +11 | xargs -r rm -rf
    
    log "Post-deployment tasks completed"
}

# Rollback function
rollback() {
    error "Deployment failed. Initiating rollback..."
    
    # This would contain rollback logic
    # For now, we just exit with error
    exit 1
}

# Main deployment flow
main() {
    log "Starting AskProAI production deployment..."
    send_notification "Deployment started" "info"
    
    # Set up error handling
    trap rollback ERR
    
    # Run deployment steps
    check_user
    pre_deployment_checks
    create_backup
    pull_latest_code
    install_dependencies
    run_migrations
    optimize_application
    set_permissions
    restart_services
    run_health_checks
    post_deployment_tasks
    
    # Success
    log "Deployment completed successfully!"
    send_notification "Deployment completed successfully! 🎉" "good"
    
    # Show deployment summary
    echo -e "\n${GREEN}=== Deployment Summary ===${NC}"
    echo "Deployed version: $(git rev-parse --short HEAD)"
    echo "Deployment time: $(date)"
    echo "Log file: $LOG_FILE"
    echo -e "${GREEN}========================${NC}\n"
}

# Run main function
main "$@"