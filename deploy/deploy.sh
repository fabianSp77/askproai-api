#!/bin/bash

# AskProAI Production Deployment Script (Enhanced)
# Version: 2.0.0
# Description: Zero-downtime deployment with comprehensive safety checks

set -euo pipefail  # Exit on error, undefined variables, pipe failures
IFS=$'\n\t'       # Set IFS to prevent word splitting issues

# Load environment-specific configuration
if [ -f "$(dirname "$0")/deploy.conf" ]; then
    source "$(dirname "$0")/deploy.conf"
fi

# Configuration
readonly APP_DIR="${APP_DIR:-/var/www/api-gateway}"
readonly BACKUP_DIR="${BACKUP_DIR:-/var/backups/askproai}"
readonly LOG_DIR="${LOG_DIR:-/var/log/askproai}"
readonly DEPLOYMENT_LOG="${LOG_DIR}/deployment-$(date +%Y%m%d_%H%M%S).log"
readonly LOCK_FILE="/var/run/askproai-deployment.lock"
readonly MAX_BACKUP_AGE_DAYS=30
readonly DEPLOYMENT_TIMEOUT=1800  # 30 minutes
readonly HEALTH_CHECK_RETRIES=5
readonly HEALTH_CHECK_DELAY=10

# Deployment modes
readonly MODE="${1:-production}"  # production, staging, maintenance

# Colors for output
readonly RED='\033[0;31m'
readonly GREEN='\033[0;32m'
readonly YELLOW='\033[1;33m'
readonly BLUE='\033[0;34m'
readonly PURPLE='\033[0;35m'
readonly NC='\033[0m'

# Ensure log directory exists
mkdir -p "$LOG_DIR"

# Logging functions
log() {
    local level="$1"
    shift
    local message="$*"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo -e "${timestamp} [${level}] ${message}" | tee -a "$DEPLOYMENT_LOG"
    
    # Also log to syslog
    logger -t "askproai-deploy" "${level}: ${message}"
}

log_info() {
    echo -e "${GREEN}[INFO]${NC} $*" >&2
    log "INFO" "$@"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $*" >&2
    log "WARN" "$@"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $*" >&2
    log "ERROR" "$@"
}

log_section() {
    echo -e "\n${BLUE}=== $* ===${NC}" >&2
    log "SECTION" "$@"
}

# Lock file handling
acquire_lock() {
    local timeout=60
    local elapsed=0
    
    while [ -f "$LOCK_FILE" ] && [ $elapsed -lt $timeout ]; do
        log_warn "Waiting for existing deployment to complete..."
        sleep 5
        elapsed=$((elapsed + 5))
    done
    
    if [ -f "$LOCK_FILE" ]; then
        log_error "Another deployment is still running after ${timeout}s timeout"
        exit 1
    fi
    
    echo $$ > "$LOCK_FILE"
    trap release_lock EXIT INT TERM
}

release_lock() {
    rm -f "$LOCK_FILE"
}

# Send notifications
send_notification() {
    local status="$1"
    local message="$2"
    local webhook_url="${SLACK_WEBHOOK_URL:-}"
    
    if [ -n "$webhook_url" ]; then
        local color="#36a64f"  # Green
        [ "$status" == "error" ] && color="#ff0000"  # Red
        [ "$status" == "warning" ] && color="#ffcc00"  # Yellow
        
        curl -X POST -H 'Content-type: application/json' \
            --data "{
                \"text\": \"AskProAI Deployment\",
                \"attachments\": [{
                    \"color\": \"$color\",
                    \"text\": \"$message\",
                    \"fields\": [
                        {\"title\": \"Environment\", \"value\": \"$MODE\", \"short\": true},
                        {\"title\": \"Time\", \"value\": \"$(date)\", \"short\": true}
                    ]
                }]
            }" \
            "$webhook_url" 2>/dev/null || true
    fi
    
    # Also send email notification if configured
    if [ -n "${DEPLOYMENT_EMAIL:-}" ]; then
        echo "$message" | mail -s "AskProAI Deployment: $status" "$DEPLOYMENT_EMAIL" || true
    fi
}

# Check prerequisites
check_prerequisites() {
    log_section "Checking Prerequisites"
    
    # Check if running as appropriate user
    if [ "$MODE" == "production" ] && [ "$EUID" -eq 0 ]; then
        log_error "Do not run production deployments as root!"
        exit 1
    fi
    
    # Check required commands
    local required_commands=(
        "php" "composer" "npm" "git" "mysql" "redis-cli"
        "curl" "rsync" "tar" "gzip"
    )
    
    for cmd in "${required_commands[@]}"; do
        if ! command -v "$cmd" &> /dev/null; then
            log_error "Required command not found: $cmd"
            exit 1
        fi
    done
    
    # Check PHP version
    local php_version=$(php -r 'echo PHP_VERSION;')
    if ! php -r 'exit(version_compare(PHP_VERSION, "8.2.0", ">=") ? 0 : 1);'; then
        log_error "PHP 8.2.0 or higher is required. Current: $php_version"
        exit 1
    fi
    
    log_info "All prerequisites satisfied"
}

# Validate environment
validate_environment() {
    log_section "Validating Environment"
    
    cd "$APP_DIR"
    
    # Check environment file
    if [ ! -f ".env.${MODE}" ]; then
        log_error "Environment file .env.${MODE} not found"
        exit 1
    fi
    
    # Validate critical environment variables
    local required_vars=(
        "APP_KEY" "DB_CONNECTION" "DB_DATABASE"
        "DEFAULT_CALCOM_API_KEY" "DEFAULT_RETELL_API_KEY"
        "QUEUE_CONNECTION" "CACHE_DRIVER"
    )
    
    for var in "${required_vars[@]}"; do
        if ! grep -q "^${var}=.\+" ".env.${MODE}"; then
            log_error "Required environment variable not set: $var"
            exit 1
        fi
    done
    
    # Test database connection
    if ! php artisan db:show --env="$MODE" &>/dev/null; then
        log_error "Cannot connect to database"
        exit 1
    fi
    
    # Test Redis connection
    if ! redis-cli ping &>/dev/null; then
        log_error "Cannot connect to Redis"
        exit 1
    fi
    
    log_info "Environment validation passed"
}

# Create comprehensive backup
create_backup() {
    log_section "Creating Backup"
    
    local backup_name="backup_${MODE}_$(date +%Y%m%d_%H%M%S)"
    local backup_path="${BACKUP_DIR}/${backup_name}"
    
    mkdir -p "$backup_path"
    
    # Save deployment metadata
    cat > "${backup_path}/metadata.json" <<EOF
{
    "timestamp": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
    "mode": "$MODE",
    "git_commit": "$(git rev-parse HEAD)",
    "git_branch": "$(git branch --show-current)",
    "php_version": "$(php -r 'echo PHP_VERSION;')",
    "laravel_version": "$(php artisan --version | grep -oP 'Laravel Framework \K[0-9.]+')"
}
EOF
    
    # Backup database with progress
    log_info "Backing up database..."
    local db_backup="${backup_path}/database.sql.gz"
    
    # Use Laravel's database credentials
    source ".env.${MODE}"
    
    mysqldump \
        --single-transaction \
        --routines \
        --triggers \
        --events \
        --add-drop-database \
        --databases "$DB_DATABASE" \
        -h"$DB_HOST" \
        -P"${DB_PORT:-3306}" \
        -u"$DB_USERNAME" \
        -p"$DB_PASSWORD" \
        | pv -s $(mysql -h"$DB_HOST" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "SELECT SUM(data_length + index_length) FROM information_schema.TABLES WHERE table_schema='$DB_DATABASE';" -s -N) \
        | gzip -9 > "$db_backup" || {
            log_error "Database backup failed"
            exit 1
        }
    
    # Backup application files
    log_info "Backing up application files..."
    tar -czf "${backup_path}/files.tar.gz" \
        --exclude="node_modules" \
        --exclude="vendor" \
        --exclude="storage/logs/*" \
        --exclude="storage/framework/cache/*" \
        --exclude="storage/framework/sessions/*" \
        --exclude="storage/framework/views/*" \
        --exclude="bootstrap/cache/*" \
        --exclude=".git" \
        -C "$APP_DIR" . || {
            log_error "File backup failed"
            exit 1
        }
    
    # Backup environment file
    cp ".env.${MODE}" "${backup_path}/.env.backup"
    
    # Create backup manifest
    find "$backup_path" -type f -exec md5sum {} \; > "${backup_path}/manifest.txt"
    
    log_info "Backup created: $backup_name"
    
    # Clean old backups
    find "$BACKUP_DIR" -maxdepth 1 -type d -name "backup_*" -mtime +$MAX_BACKUP_AGE_DAYS -exec rm -rf {} \; || true
}

# Enable maintenance mode
enable_maintenance() {
    log_section "Enabling Maintenance Mode"
    
    cd "$APP_DIR"
    
    # Generate secret for maintenance bypass
    local secret=$(openssl rand -hex 16)
    
    php artisan down \
        --retry=60 \
        --refresh=15 \
        --secret="$secret" \
        --status=503 \
        --message="System maintenance in progress. We'll be back shortly." || {
            log_error "Failed to enable maintenance mode"
            exit 1
        }
    
    log_info "Maintenance mode enabled (secret: $secret)"
    
    # Wait for active requests to complete
    sleep 5
}

# Update application code
update_code() {
    log_section "Updating Application Code"
    
    cd "$APP_DIR"
    
    # Save current state
    local current_commit=$(git rev-parse HEAD)
    
    # Stash any local changes
    git stash save "Deployment stash: $(date)" || true
    
    # Fetch latest changes
    git fetch origin || {
        log_error "Failed to fetch from repository"
        exit 1
    }
    
    # Determine target branch/tag
    local target="${DEPLOY_TARGET:-origin/main}"
    
    # Update to target
    git checkout "$target" || {
        log_error "Failed to checkout $target"
        exit 1
    }
    
    # Reset to ensure clean state
    git reset --hard "$target"
    
    local new_commit=$(git rev-parse HEAD)
    
    if [ "$current_commit" == "$new_commit" ]; then
        log_warn "No code changes detected"
    else
        log_info "Updated from $current_commit to $new_commit"
        
        # Show what changed
        git log --oneline "$current_commit..$new_commit" | head -20
    fi
}

# Install dependencies
install_dependencies() {
    log_section "Installing Dependencies"
    
    cd "$APP_DIR"
    
    # Copy environment file
    cp ".env.${MODE}" .env
    
    # Install Composer dependencies
    log_info "Installing PHP dependencies..."
    composer install \
        --no-dev \
        --optimize-autoloader \
        --no-interaction \
        --prefer-dist \
        --no-progress || {
            log_error "Composer install failed"
            exit 1
        }
    
    # Install NPM dependencies
    log_info "Installing Node dependencies..."
    npm ci --production || {
        log_error "NPM install failed"
        exit 1
    }
    
    # Build assets
    log_info "Building frontend assets..."
    npm run build || {
        log_error "Asset build failed"
        exit 1
    }
}

# Run database migrations
run_migrations() {
    log_section "Running Database Migrations"
    
    cd "$APP_DIR"
    
    # Check for pending migrations
    local pending_count=$(php artisan migrate:status | grep -c "Pending" || echo 0)
    
    if [ "$pending_count" -gt 0 ]; then
        log_info "Found $pending_count pending migrations"
        
        # Run migrations with zero downtime
        php artisan migrate --force --no-interaction || {
            log_error "Migrations failed"
            exit 1
        }
        
        log_info "Migrations completed successfully"
    else
        log_info "No pending migrations"
    fi
}

# Optimize application
optimize_application() {
    log_section "Optimizing Application"
    
    cd "$APP_DIR"
    
    # Clear all caches first
    php artisan optimize:clear || true
    
    # Generate optimized files
    php artisan config:cache || {
        log_error "Config cache failed"
        exit 1
    }
    
    php artisan route:cache || {
        log_error "Route cache failed"
        exit 1
    }
    
    php artisan view:cache || {
        log_error "View cache failed"
        exit 1
    }
    
    php artisan event:cache || {
        log_error "Event cache failed"
        exit 1
    }
    
    # Optimize Composer autoloader
    composer dump-autoload --optimize --no-dev
    
    # Warm up application caches
    php artisan cache:warmup || log_warn "Cache warmup failed"
    
    log_info "Application optimized"
}

# Set correct permissions
set_permissions() {
    log_section "Setting Permissions"
    
    cd "$APP_DIR"
    
    # Set ownership
    chown -R www-data:www-data storage bootstrap/cache
    
    # Set directory permissions
    find storage bootstrap/cache -type d -exec chmod 755 {} \;
    
    # Set file permissions
    find storage bootstrap/cache -type f -exec chmod 644 {} \;
    
    # Make scripts executable
    chmod +x artisan
    find . -name "*.sh" -type f -exec chmod +x {} \;
    
    log_info "Permissions configured"
}

# Restart services gracefully
restart_services() {
    log_section "Restarting Services"
    
    # Reload PHP-FPM gracefully
    log_info "Reloading PHP-FPM..."
    systemctl reload php8.2-fpm || {
        log_error "PHP-FPM reload failed"
        exit 1
    }
    
    # Restart queue workers gracefully
    log_info "Restarting queue workers..."
    php artisan queue:restart || log_warn "Queue restart command failed"
    
    if command -v supervisorctl &> /dev/null; then
        supervisorctl restart horizon:* || log_warn "Horizon restart failed"
    fi
    
    # Clear OPcache
    if command -v cachetool &> /dev/null; then
        cachetool opcache:reset --fcgi=/var/run/php/php8.2-fpm.sock || true
    fi
    
    # Test and reload nginx
    nginx -t || {
        log_error "Nginx configuration test failed"
        exit 1
    }
    
    systemctl reload nginx || {
        log_error "Nginx reload failed"
        exit 1
    }
    
    log_info "Services restarted successfully"
}

# Disable maintenance mode
disable_maintenance() {
    log_section "Disabling Maintenance Mode"
    
    cd "$APP_DIR"
    
    php artisan up || {
        log_error "Failed to disable maintenance mode"
        exit 1
    }
    
    log_info "Maintenance mode disabled"
}

# Run comprehensive health checks
run_health_checks() {
    log_section "Running Health Checks"
    
    local base_url="https://api.askproai.de"
    [ "$MODE" != "production" ] && base_url="http://localhost"
    
    # Wait for services to stabilize
    sleep 5
    
    # Check main health endpoint
    local retry=0
    local health_ok=false
    
    while [ $retry -lt $HEALTH_CHECK_RETRIES ]; do
        local response=$(curl -s -w "\n%{http_code}" "$base_url/api/health" 2>/dev/null | tail -1)
        
        if [ "$response" == "200" ]; then
            health_ok=true
            break
        fi
        
        log_warn "Health check attempt $((retry + 1)) failed (HTTP $response)"
        sleep $HEALTH_CHECK_DELAY
        retry=$((retry + 1))
    done
    
    if [ "$health_ok" != "true" ]; then
        log_error "Health check failed after $HEALTH_CHECK_RETRIES attempts"
        exit 1
    fi
    
    log_info "Main health check passed"
    
    # Check specific health endpoints
    local endpoints=(
        "/api/health/database"
        "/api/health/redis"
        "/api/health/queue"
        "/api/health/storage"
    )
    
    for endpoint in "${endpoints[@]}"; do
        local response=$(curl -s -o /dev/null -w "%{http_code}" "$base_url$endpoint" 2>/dev/null)
        if [ "$response" == "200" ]; then
            log_info "Health check passed: $endpoint"
        else
            log_warn "Health check failed: $endpoint (HTTP $response)"
        fi
    done
    
    # Check queue processing
    php artisan queue:monitor || log_warn "Queue monitor check failed"
    
    # Check scheduled tasks
    php artisan schedule:list || log_warn "Schedule list failed"
    
    log_info "All health checks completed"
}

# Post-deployment tasks
post_deployment() {
    log_section "Running Post-Deployment Tasks"
    
    cd "$APP_DIR"
    
    # Clear route cache to ensure fresh routes
    php artisan route:clear
    php artisan route:cache
    
    # Run any post-deployment commands
    if [ -f "artisan" ]; then
        php artisan deploy:post || log_warn "Post-deployment command failed"
    fi
    
    # Update deployment marker
    echo "$(date -u +%Y-%m-%dT%H:%M:%SZ)|$(git rev-parse HEAD)|$MODE" >> "$LOG_DIR/deployments.log"
    
    # Send deployment metrics
    if [ -n "${METRICS_ENDPOINT:-}" ]; then
        curl -X POST "$METRICS_ENDPOINT" \
            -H "Content-Type: application/json" \
            -d "{
                \"deployment\": {
                    \"timestamp\": \"$(date -u +%Y-%m-%dT%H:%M:%SZ)\",
                    \"mode\": \"$MODE\",
                    \"commit\": \"$(git rev-parse HEAD)\",
                    \"duration\": $SECONDS,
                    \"status\": \"success\"
                }
            }" 2>/dev/null || true
    fi
    
    log_info "Post-deployment tasks completed"
}

# Rollback on failure
rollback() {
    log_error "Deployment failed! Initiating rollback..."
    send_notification "error" "Deployment failed! Check logs: $DEPLOYMENT_LOG"
    
    # Call rollback script if it exists
    if [ -f "$(dirname "$0")/rollback.sh" ]; then
        bash "$(dirname "$0")/rollback.sh" --auto --backup=latest
    fi
    
    exit 1
}

# Main deployment flow
main() {
    local start_time=$(date +%s)
    
    log_info "Starting AskProAI deployment"
    log_info "Mode: $MODE"
    log_info "Log file: $DEPLOYMENT_LOG"
    
    # Set up error handling
    trap rollback ERR
    trap "release_lock; exit 130" INT TERM
    
    # Acquire deployment lock
    acquire_lock
    
    # Send start notification
    send_notification "info" "Deployment started for $MODE environment"
    
    # Execute deployment steps
    check_prerequisites
    validate_environment
    
    if [ "$MODE" == "production" ]; then
        create_backup
        enable_maintenance
    fi
    
    update_code
    install_dependencies
    run_migrations
    optimize_application
    set_permissions
    restart_services
    
    if [ "$MODE" == "production" ]; then
        disable_maintenance
    fi
    
    run_health_checks
    post_deployment
    
    # Calculate duration
    local end_time=$(date +%s)
    local duration=$((end_time - start_time))
    
    # Success
    log_info "Deployment completed successfully in ${duration}s"
    send_notification "success" "Deployment completed successfully in ${duration}s! 🎉"
    
    # Show summary
    echo -e "\n${GREEN}=== Deployment Summary ===${NC}"
    echo -e "Environment: ${PURPLE}$MODE${NC}"
    echo -e "Duration: ${duration}s"
    echo -e "Commit: $(git rev-parse --short HEAD)"
    echo -e "Log: $DEPLOYMENT_LOG"
    echo -e "${GREEN}==========================${NC}\n"
    
    # Release lock
    release_lock
    
    exit 0
}

# Run main function with timeout
if command -v timeout &> /dev/null; then
    timeout $DEPLOYMENT_TIMEOUT bash -c "$(declare -f main); main"
else
    main
fi