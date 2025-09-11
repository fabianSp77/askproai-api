#!/bin/bash

#############################################################
# Laravel + Filament Deployment Script with Cache Management
# 
# This script ensures zero-downtime deployments with proper
# cache handling to prevent race conditions
#############################################################

set -e  # Exit on error

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="/var/www/api-gateway"
LOCK_FILE="/tmp/laravel-deploy.lock"
LOG_FILE="/var/log/laravel-deploy.log"

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
    exit 1
}

warning() {
    echo -e "${YELLOW}[$(date '+%Y-%m-%d %H:%M:%S')] WARNING:${NC} $1" | tee -a "$LOG_FILE"
}

# Acquire deployment lock
acquire_lock() {
    local timeout=60
    local elapsed=0
    
    while [ $elapsed -lt $timeout ]; do
        if mkdir "$LOCK_FILE" 2>/dev/null; then
            echo $$ > "$LOCK_FILE/pid"
            log "Deployment lock acquired"
            return 0
        fi
        
        warning "Waiting for previous deployment to complete..."
        sleep 2
        elapsed=$((elapsed + 2))
    done
    
    error "Could not acquire deployment lock after ${timeout} seconds"
}

# Release deployment lock
release_lock() {
    rm -rf "$LOCK_FILE"
    log "Deployment lock released"
}

# Cleanup on exit
cleanup() {
    release_lock
}
trap cleanup EXIT

#############################################################
# MAIN DEPLOYMENT PROCESS
#############################################################

log "========================================="
log "Starting Laravel + Filament Deployment"
log "========================================="

# Acquire lock to prevent concurrent deployments
acquire_lock

cd "$PROJECT_DIR" || error "Could not change to project directory"

# Step 1: Put application in maintenance mode (with bypass for admin)
log "→ Entering maintenance mode..."
php artisan down --retry=60 --refresh=5 --secret="deployment-in-progress-$(date +%s)"

# Step 2: Clear all caches BEFORE making changes
log "→ Clearing existing caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Clear Filament caches if they exist
if php artisan list | grep -q "filament:clear-cached-components"; then
    php artisan filament:clear-cached-components
fi

# Clear compiled files
log "→ Clearing compiled files..."
php artisan clear-compiled
php artisan optimize:clear

# Step 3: Update dependencies (if composer.lock changed)
if [ -f "composer.lock" ]; then
    log "→ Installing composer dependencies..."
    composer install --no-dev --optimize-autoloader --no-interaction
fi

# Step 4: Update NPM dependencies and build assets (if needed)
if [ -f "package.json" ]; then
    log "→ Building frontend assets..."
    npm ci --production
    npm run build
fi

# Step 5: Run database migrations
log "→ Running database migrations..."
php artisan migrate --force

# Step 6: Clear OPcache
log "→ Clearing OPcache..."
if php -r "exit(function_exists('opcache_reset') ? 0 : 1);"; then
    php -r "opcache_reset();"
fi

# Step 7: Warm up caches systematically
log "→ Warming up configuration cache..."
php artisan config:cache

log "→ Warming up route cache..."
php artisan route:cache

log "→ Warming up event cache..."
php artisan event:cache

# Step 8: Filament-specific optimizations
log "→ Running Filament optimizations..."

# Cache Filament components
if php artisan list | grep -q "filament:cache-components"; then
    php artisan filament:cache-components
fi

# Optimize Filament
if php artisan list | grep -q "filament:optimize"; then
    php artisan filament:optimize
fi

# Cache icons
if php artisan list | grep -q "icons:cache"; then
    php artisan icons:cache
fi

# Step 9: Pre-compile critical views
log "→ Pre-compiling views..."
php artisan view:cache

# Step 10: Ensure proper permissions
log "→ Setting permissions..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Ensure view cache directory exists with correct permissions
mkdir -p storage/framework/views
chown www-data:www-data storage/framework/views
chmod 775 storage/framework/views

# Also ensure /tmp/laravel-views exists if used
if [ -d "/tmp/laravel-views" ]; then
    chown www-data:www-data /tmp/laravel-views
    chmod 775 /tmp/laravel-views
fi

# Step 11: Restart services gracefully
log "→ Restarting PHP-FPM gracefully..."
service php8.3-fpm reload

# Step 12: Warm up critical paths
log "→ Warming up critical paths..."
# Pre-warm the enhanced call view and other critical views
urls=(
    "https://api.askproai.de/admin"
    "https://api.askproai.de/admin/calls"
    "https://api.askproai.de/admin/enhanced-calls"
)

for url in "${urls[@]}"; do
    curl -s -o /dev/null -w "  %{url_effective}: %{http_code}\n" "$url" || warning "Could not warm up: $url"
done

# Step 13: Exit maintenance mode
log "→ Exiting maintenance mode..."
php artisan up

# Step 14: Final health check
log "→ Running health check..."
php artisan about --only=cache || warning "Cache status check failed"

# Step 15: Clear any stale view cache files
log "→ Cleaning stale view cache files..."
find storage/framework/views -name "*.php" -mtime +7 -delete 2>/dev/null || true

log "========================================="
log "✅ Deployment completed successfully!"
log "========================================="

# Display cache status
echo ""
echo "Cache Status:"
php artisan cache:status 2>/dev/null || php artisan about --only=cache

exit 0