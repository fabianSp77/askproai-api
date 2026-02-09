#!/bin/bash
# ==============================================================================
# Atomic Deployment Script - Staging
# ==============================================================================
# Purpose: Deploy to staging using releases + symlink for zero-downtime
# Usage: ./deploy-staging-atomic.sh [git-branch]
# Example: ./deploy-staging-atomic.sh main
# ==============================================================================

set -euo pipefail

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[1;34m'
NC='\033[0m'

# Configuration
APP_NAME="askproai-production"
BASE_DIR="/var/www/api-gateway"
SHARED_DIR="$BASE_DIR/shared"
RELEASES_DIR="$BASE_DIR/releases"
CURRENT_LINK="$BASE_DIR/current"
REPO_DIR="$BASE_DIR/repo"

GIT_BRANCH="${1:-main}"
KEEP_RELEASES=5
KEEP_BACKUPS=5

# Staging specific
DB_NAME="askproai_db"
DOMAIN="api.askproai.de"

# Function: Log with color
log() {
    echo -e "${GREEN}[$(date +'%H:%M:%S')]${NC} $1"
}

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

log_step() {
    echo ""
    echo -e "${YELLOW}===================================================${NC}"
    echo -e "${YELLOW} $1${NC}"
    echo -e "${YELLOW}===================================================${NC}"
}

# Function: Check prerequisites
check_prerequisites() {
    log_step "Checking Prerequisites"

    # Check if running as correct user
    if [ "$EUID" -eq 0 ]; then
        log_error "Do not run as root! Run as www-data or deployment user."
        exit 1
    fi

    # Check required commands
    for cmd in git php composer npm rsync; do
        if ! command -v $cmd &> /dev/null; then
            log_error "$cmd is not installed"
            exit 1
        fi
    done

    log "✅ All prerequisites met"
}

# Function: Create directory structure
setup_directories() {
    log_step "Setting Up Directory Structure"

    # Create base directories
    mkdir -p "$BASE_DIR"
    mkdir -p "$RELEASES_DIR"
    mkdir -p "$SHARED_DIR"/{storage,public/uploads,.env}

    # Create shared storage structure
    mkdir -p "$SHARED_DIR/storage"/{app,framework,logs}
    mkdir -p "$SHARED_DIR/storage/framework"/{cache,sessions,views}
    mkdir -p "$SHARED_DIR/storage/app"/{public,private}

    # Set permissions
    chmod -R 775 "$SHARED_DIR/storage"
    chmod -R 775 "$SHARED_DIR/public"

    log "✅ Directory structure ready"
}

# Function: Clone or update repository
update_repository() {
    log_step "Updating Repository"

    if [ ! -d "$REPO_DIR/.git" ]; then
        log "Cloning repository..."
        git clone https://github.com/[org]/[repo].git "$REPO_DIR"
        cd "$REPO_DIR"
    else
        cd "$REPO_DIR"
        log "Fetching latest changes..."
        git fetch origin
    fi

    log "Checking out branch: $GIT_BRANCH"
    git checkout "$GIT_BRANCH"
    git pull origin "$GIT_BRANCH"

    CURRENT_COMMIT=$(git rev-parse --short HEAD)
    log "✅ Repository updated to: $CURRENT_COMMIT"
    echo "$CURRENT_COMMIT"
}

# Function: Create new release
create_release() {
    local commit_sha="$1"
    local timestamp=$(date +%Y%m%d_%H%M%S)
    local release_name="${timestamp}-${commit_sha}"
    local release_path="$RELEASES_DIR/$release_name"

    log_step "Creating New Release: $release_name"

    # Copy repository to new release
    log "Copying files to release directory..."
    rsync -a --exclude='.git' \
        --exclude='node_modules' \
        --exclude='vendor' \
        --exclude='storage' \
        --exclude='.env*' \
        "$REPO_DIR/" "$release_path/"

    log "✅ Release created: $release_path"
    echo "$release_path"
}

# Function: Install dependencies
install_dependencies() {
    local release_path="$1"

    log_step "Installing Dependencies"

    cd "$release_path"

    # Composer install
    log "Installing Composer dependencies..."
    composer install \
        --no-dev \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader \
        --no-progress

    # NPM install and build
    log "Installing NPM dependencies..."
    npm ci --prefer-offline

    log "Building frontend assets..."
    npm run build

    log "✅ Dependencies installed"
}

# Function: Link shared resources
link_shared_resources() {
    local release_path="$1"

    log_step "Linking Shared Resources"

    cd "$release_path"

    # Link storage
    rm -rf storage
    ln -s "$SHARED_DIR/storage" storage

    # Link uploads
    rm -rf public/uploads
    ln -s "$SHARED_DIR/public/uploads" public/uploads

    # Link .env
    if [ -f "$SHARED_DIR/.env/staging.env" ]; then
        ln -s "$SHARED_DIR/.env/staging.env" .env
    elif [ ! -f ".env" ]; then
        cp .env.example .env
        log "⚠️  Created .env from example - please configure!"
    fi

    log "✅ Shared resources linked"
}

# Function: Run migrations
run_migrations() {
    local release_path="$1"

    log_step "Running Database Migrations"

    cd "$release_path"

    # Backup database before migrations
    log "Creating pre-migration backup..."
    mysqldump -h 127.0.0.1 -u root "$DB_NAME" \
        --single-transaction \
        --quick | gzip > "$SHARED_DIR/storage/backups/pre-migration-$(date +%s).sql.gz" 2>/dev/null || true

    # Run migrations
    log "Running migrations..."
    php artisan migrate --force

    log "✅ Migrations completed"
}

# Function: Clear caches
clear_caches() {
    local release_path="$1"

    log_step "Clearing Caches"

    cd "$release_path"

    php artisan config:clear
    php artisan cache:clear
    php artisan route:clear
    php artisan view:clear
    php artisan optimize:clear

    # Rebuild caches
    php artisan config:cache
    php artisan route:cache

    log "✅ Caches cleared"
}

# Function: Switch to new release (ATOMIC)
switch_release() {
    local release_path="$1"

    log_step "Switching to New Release (Atomic)"

    # Create temp symlink
    local temp_link="$BASE_DIR/current_tmp_$$"
    ln -s "$release_path" "$temp_link"

    # Atomic switch
    mv -Tf "$temp_link" "$CURRENT_LINK"

    log "✅ Release switched: $CURRENT_LINK -> $release_path"
}

# Function: Reload PHP-FPM and Nginx
reload_services() {
    log_step "Reloading Services"

    # PHP-FPM
    if sudo systemctl reload php8.3-fpm; then
        log "✅ PHP-FPM reloaded"
    else
        log_error "Failed to reload PHP-FPM"
    fi

    # Nginx
    if sudo nginx -t && sudo systemctl reload nginx; then
        log "✅ Nginx reloaded"
    else
        log_error "Failed to reload Nginx"
    fi
}

# Function: Run health checks
run_health_checks() {
    log_step "Running Health Checks"

    # HTTP check
    if curl -sf "https://$DOMAIN/health" > /dev/null; then
        log "✅ HTTP health check passed"
    else
        log_error "HTTP health check failed!"
        return 1
    fi

    # Database check
    cd "$CURRENT_LINK"
    if php artisan migrate:status > /dev/null 2>&1; then
        log "✅ Database connection OK"
    else
        log_error "Database connection failed!"
        return 1
    fi

    log "✅ All health checks passed"
}

# Function: Cleanup old releases
cleanup_old_releases() {
    log_step "Cleaning Up Old Releases"

    cd "$RELEASES_DIR"
    local count=$(ls -1d */ 2>/dev/null | wc -l)

    if [ "$count" -gt "$KEEP_RELEASES" ]; then
        log "Found $count releases, keeping last $KEEP_RELEASES..."
        ls -1dt */ | tail -n +$((KEEP_RELEASES + 1)) | xargs rm -rf
        log "✅ Old releases cleaned"
    else
        log "Only $count releases, nothing to clean"
    fi
}

# Function: Cleanup old backups
cleanup_old_backups() {
    log_step "Cleaning Up Old Backups"

    local backup_dir="$SHARED_DIR/storage/backups"
    if [ ! -d "$backup_dir" ]; then
        log "No backup directory found"
        return
    fi

    cd "$backup_dir"
    local count=$(ls -1t pre-migration-*.sql.gz pre-deploy-*.sql.gz 2>/dev/null | wc -l)

    if [ "$count" -gt "$KEEP_BACKUPS" ]; then
        log "Found $count backups, keeping last $KEEP_BACKUPS..."
        ls -1t pre-migration-*.sql.gz pre-deploy-*.sql.gz 2>/dev/null | tail -n +$((KEEP_BACKUPS + 1)) | while read f; do
            rm -f "$f" "${f}.sha256"
            log "  Deleted: $f"
        done
        log "✅ Old backups cleaned"
    else
        log "Only $count backups, nothing to clean"
    fi
}

# Function: Rollback to previous release
rollback() {
    log_error "Deployment failed! Rolling back..."

    local previous_release=$(ls -1dt "$RELEASES_DIR"/*/ 2>/dev/null | sed -n '2p')

    if [ -n "$previous_release" ]; then
        log "Rolling back to: $previous_release"
        ln -sfn "$previous_release" "$CURRENT_LINK"
        reload_services
        log "✅ Rolled back to previous release"
    else
        log_error "No previous release found for rollback!"
    fi

    exit 1
}

# Main deployment flow
main() {
    echo ""
    echo -e "${GREEN}╔════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║  ATOMIC DEPLOYMENT - STAGING                           ║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════════════════════╝${NC}"
    echo ""

    local start_time=$(date +%s)

    # Set trap for rollback on error
    trap rollback ERR

    check_prerequisites
    setup_directories

    local commit_sha=$(update_repository)
    local release_path=$(create_release "$commit_sha")

    install_dependencies "$release_path"
    link_shared_resources "$release_path"
    run_migrations "$release_path"
    clear_caches "$release_path"
    switch_release "$release_path"
    reload_services

    # Check if deployment was successful
    if run_health_checks; then
        cleanup_old_releases
        cleanup_old_backups

        local end_time=$(date +%s)
        local duration=$((end_time - start_time))

        echo ""
        log_step "🎉 DEPLOYMENT SUCCESSFUL"
        log "Release: $(basename $release_path)"
        log "Commit: $commit_sha"
        log "Duration: ${duration}s"
        log "Domain: https://$DOMAIN"
        echo ""

        exit 0
    else
        rollback
    fi
}

# Run main function
main "$@"
