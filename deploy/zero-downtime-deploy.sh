#!/bin/bash

# AskProAI Zero-Downtime Deployment Script
# Version: 3.0.0
# Description: Blue-Green deployment with automated rollback and comprehensive monitoring

set -euo pipefail
IFS=$'\n\t'

# ===================================================================
# Configuration
# ===================================================================

# Deployment configuration
readonly SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly APP_NAME="askproai"
readonly APP_DIR="/var/www/api-gateway"
readonly BLUE_DIR="${APP_DIR}-blue"
readonly GREEN_DIR="${APP_DIR}-green"
readonly BACKUP_DIR="/var/backups/askproai"
readonly LOG_DIR="/var/log/askproai"
readonly DEPLOYMENT_LOG="${LOG_DIR}/zero-downtime-deploy-$(date +%Y%m%d_%H%M%S).log"
readonly LOCK_FILE="/var/run/askproai-zero-downtime-deploy.lock"
readonly METRICS_FILE="${LOG_DIR}/deployment-metrics.json"

# Deployment settings
readonly MAX_BACKUP_AGE_DAYS=30
readonly DEPLOYMENT_TIMEOUT=1800  # 30 minutes
readonly HEALTH_CHECK_RETRIES=10
readonly HEALTH_CHECK_DELAY=5
readonly MIGRATION_CHECK_RETRIES=3
readonly LOAD_BALANCER_DRAIN_TIME=30

# Environment
readonly ENVIRONMENT="${1:-production}"
readonly DRY_RUN="${2:-false}"

# Colors for output
readonly RED='\033[0;31m'
readonly GREEN='\033[0;32m'
readonly YELLOW='\033[1;33m'
readonly BLUE='\033[0;34m'
readonly PURPLE='\033[0;35m'
readonly CYAN='\033[0;36m'
readonly NC='\033[0m'

# Ensure directories exist
mkdir -p "$LOG_DIR" "$BACKUP_DIR"

# ===================================================================
# Logging Functions
# ===================================================================

log() {
    local level="$1"
    shift
    local message="$*"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo -e "${timestamp} [${level}] ${message}" | tee -a "$DEPLOYMENT_LOG"
    
    # Send to monitoring system
    if [ -f "$APP_DIR/artisan" ]; then
        php "$APP_DIR/artisan" monitoring:log-event \
            --type="deployment" \
            --level="$level" \
            --message="$message" 2>/dev/null || true
    fi
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
    echo -e "\n${BLUE}═══ $* ═══${NC}" >&2
    log "SECTION" "$@"
}

log_success() {
    echo -e "${GREEN}✓${NC} $*" >&2
    log "SUCCESS" "$@"
}

# ===================================================================
# Lock Management
# ===================================================================

acquire_lock() {
    local timeout=300  # 5 minutes
    local elapsed=0
    
    while [ -f "$LOCK_FILE" ] && [ $elapsed -lt $timeout ]; do
        local pid=$(cat "$LOCK_FILE" 2>/dev/null || echo "unknown")
        log_warn "Waiting for deployment lock (PID: $pid)..."
        sleep 10
        elapsed=$((elapsed + 10))
    done
    
    if [ -f "$LOCK_FILE" ]; then
        log_error "Cannot acquire deployment lock after ${timeout}s"
        exit 1
    fi
    
    echo $$ > "$LOCK_FILE"
    trap cleanup EXIT INT TERM
}

release_lock() {
    rm -f "$LOCK_FILE"
}

# ===================================================================
# Cleanup and Error Handling
# ===================================================================

cleanup() {
    local exit_code=$?
    
    if [ $exit_code -ne 0 ]; then
        log_error "Deployment failed with exit code: $exit_code"
        send_alert "critical" "Zero-downtime deployment failed" \
            "Environment: $ENVIRONMENT, Exit Code: $exit_code"
    fi
    
    release_lock
    
    # Save deployment metrics
    save_deployment_metrics "$exit_code"
}

rollback_on_error() {
    log_error "Critical error detected, initiating rollback..."
    
    # Send immediate alert
    send_alert "critical" "Deployment failed - rollback initiated" \
        "Environment: $ENVIRONMENT, Error: $1"
    
    # Perform rollback
    if [ -d "$BLUE_DIR" ] && [ -L "$APP_DIR" ]; then
        log_info "Switching symlink back to blue environment"
        ln -sfn "$BLUE_DIR" "$APP_DIR"
        reload_services
    fi
    
    exit 1
}

# ===================================================================
# Alerting Integration
# ===================================================================

send_alert() {
    local severity="$1"
    local title="$2"
    local message="$3"
    
    if [ -f "$APP_DIR/artisan" ]; then
        php "$APP_DIR/artisan" alerts:send \
            --severity="$severity" \
            --title="$title" \
            --message="$message" \
            --channel=all 2>/dev/null || true
    fi
    
    # Also send Slack notification if configured
    if [ -n "${SLACK_WEBHOOK_URL:-}" ]; then
        send_slack_notification "$severity" "$title" "$message"
    fi
}

send_slack_notification() {
    local severity="$1"
    local title="$2"
    local message="$3"
    local color="#36a64f"  # Green
    
    case "$severity" in
        "critical") color="#ff0000" ;;  # Red
        "warning") color="#ffcc00" ;;   # Yellow
        "info") color="#3aa3e3" ;;      # Blue
    esac
    
    curl -X POST -H 'Content-type: application/json' \
        --data "{
            \"attachments\": [{
                \"color\": \"$color\",
                \"title\": \"🚀 Deployment: $title\",
                \"text\": \"$message\",
                \"fields\": [
                    {\"title\": \"Environment\", \"value\": \"$ENVIRONMENT\", \"short\": true},
                    {\"title\": \"Time\", \"value\": \"$(date '+%Y-%m-%d %H:%M:%S')\", \"short\": true}
                ],
                \"footer\": \"AskProAI Zero-Downtime Deploy\",
                \"ts\": $(date +%s)
            }]
        }" \
        "$SLACK_WEBHOOK_URL" 2>/dev/null || true
}

# ===================================================================
# Pre-flight Checks
# ===================================================================

check_prerequisites() {
    log_section "Pre-flight Checks"
    
    # Check required commands
    local required_commands=(
        "php" "composer" "npm" "git" "mysql" "redis-cli"
        "nginx" "rsync" "jq" "curl" "pv"
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
        log_error "PHP 8.2.0 or higher required. Found: $php_version"
        exit 1
    fi
    
    # Check disk space
    local available_space=$(df -BG "$APP_DIR" | awk 'NR==2 {print $4}' | sed 's/G//')
    if [ "$available_space" -lt 5 ]; then
        log_error "Insufficient disk space: ${available_space}GB available, 5GB required"
        exit 1
    fi
    
    # Check if blue/green directories exist
    if [ ! -d "$BLUE_DIR" ] && [ ! -d "$GREEN_DIR" ]; then
        log_warn "Blue/Green directories not found. Initializing..."
        initialize_blue_green
    fi
    
    log_success "All prerequisites satisfied"
}

# ===================================================================
# Blue-Green Setup
# ===================================================================

initialize_blue_green() {
    log_section "Initializing Blue-Green Deployment"
    
    # Create blue directory from current
    if [ -d "$APP_DIR" ] && [ ! -L "$APP_DIR" ]; then
        log_info "Converting current deployment to blue environment"
        mv "$APP_DIR" "$BLUE_DIR"
        ln -sfn "$BLUE_DIR" "$APP_DIR"
    fi
    
    # Create green directory
    if [ ! -d "$GREEN_DIR" ]; then
        log_info "Creating green environment"
        cp -a "$BLUE_DIR" "$GREEN_DIR"
    fi
    
    log_success "Blue-Green environment initialized"
}

determine_target_environment() {
    # Determine which environment is currently active
    if [ -L "$APP_DIR" ]; then
        local current=$(readlink "$APP_DIR")
        if [[ "$current" == *"blue"* ]]; then
            echo "green"
        else
            echo "blue"
        fi
    else
        echo "green"
    fi
}

get_environment_dir() {
    local env="$1"
    if [ "$env" == "blue" ]; then
        echo "$BLUE_DIR"
    else
        echo "$GREEN_DIR"
    fi
}

# ===================================================================
# Database Migration Safety
# ===================================================================

check_migration_safety() {
    log_section "Migration Safety Check"
    
    local target_dir="$1"
    cd "$target_dir"
    
    # Check for destructive migrations
    local destructive_patterns=(
        "dropColumn"
        "dropTable"
        "dropIndex"
        "dropForeign"
        "truncate"
        "Schema::drop"
    )
    
    local pending_migrations=$(php artisan migrate:status --pending --json 2>/dev/null || echo '[]')
    local has_destructive=false
    
    if [ "$pending_migrations" != "[]" ]; then
        for pattern in "${destructive_patterns[@]}"; do
            if echo "$pending_migrations" | grep -q "$pattern"; then
                has_destructive=true
                log_warn "Potentially destructive migration detected: $pattern"
            fi
        done
    fi
    
    if [ "$has_destructive" == "true" ] && [ "$DRY_RUN" != "true" ]; then
        log_error "Destructive migrations detected. Manual intervention required."
        log_info "Run with DRY_RUN=true to preview migrations"
        exit 1
    fi
    
    log_success "Migration safety check passed"
}

run_migrations_safely() {
    log_section "Running Database Migrations"
    
    local target_dir="$1"
    cd "$target_dir"
    
    # Create migration backup point
    local backup_name="pre_migration_$(date +%Y%m%d_%H%M%S)"
    log_info "Creating database backup: $backup_name"
    
    php artisan backup:run --only-db --filename="$backup_name" || {
        log_error "Failed to create pre-migration backup"
        exit 1
    }
    
    # Dry run first
    log_info "Running migration dry run..."
    php artisan migrate --pretend || {
        log_error "Migration dry run failed"
        exit 1
    }
    
    if [ "$DRY_RUN" == "true" ]; then
        log_info "DRY RUN: Skipping actual migration"
        return 0
    fi
    
    # Run migrations with retry logic
    local retry=0
    while [ $retry -lt $MIGRATION_CHECK_RETRIES ]; do
        if php artisan migrate --force --no-interaction; then
            log_success "Migrations completed successfully"
            return 0
        fi
        
        retry=$((retry + 1))
        log_warn "Migration attempt $retry failed, retrying..."
        sleep 5
    done
    
    log_error "Migrations failed after $MIGRATION_CHECK_RETRIES attempts"
    exit 1
}

# ===================================================================
# Deployment Process
# ===================================================================

prepare_target_environment() {
    local target_env="$1"
    local target_dir=$(get_environment_dir "$target_env")
    
    log_section "Preparing $target_env Environment"
    
    cd "$target_dir"
    
    # Update code
    log_info "Updating code..."
    if [ -d ".git" ]; then
        git fetch origin
        git reset --hard origin/main
    else
        log_warn "Not a git repository, skipping code update"
    fi
    
    # Copy environment file
    cp "$APP_DIR/.env" "$target_dir/.env"
    
    # Install dependencies
    log_info "Installing dependencies..."
    composer install --no-dev --optimize-autoloader --no-interaction
    npm ci --production
    
    # Build assets
    log_info "Building assets..."
    npm run build
    
    # Clear and optimize
    log_info "Optimizing application..."
    php artisan optimize:clear
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
    
    # Set permissions
    chown -R www-data:www-data storage bootstrap/cache
    find storage bootstrap/cache -type d -exec chmod 755 {} \;
    find storage bootstrap/cache -type f -exec chmod 644 {} \;
    
    log_success "$target_env environment prepared"
}

# ===================================================================
# Health Checks
# ===================================================================

run_health_check() {
    local target_dir="$1"
    local check_type="${2:-basic}"
    
    log_info "Running $check_type health check on $target_dir"
    
    cd "$target_dir"
    
    # Basic health check
    local health_endpoint="http://localhost/api/health"
    local retry=0
    
    while [ $retry -lt $HEALTH_CHECK_RETRIES ]; do
        local response=$(curl -s -w "\n%{http_code}" "$health_endpoint" 2>/dev/null || echo "000")
        local http_code=$(echo "$response" | tail -1)
        
        if [ "$http_code" == "200" ]; then
            log_success "Health check passed"
            
            # Extended health checks
            if [ "$check_type" == "extended" ]; then
                php artisan health:check --json || {
                    log_error "Extended health check failed"
                    return 1
                }
            fi
            
            return 0
        fi
        
        retry=$((retry + 1))
        log_warn "Health check attempt $retry failed (HTTP $http_code)"
        sleep $HEALTH_CHECK_DELAY
    done
    
    log_error "Health check failed after $HEALTH_CHECK_RETRIES attempts"
    return 1
}

# ===================================================================
# Load Balancer Integration
# ===================================================================

update_load_balancer() {
    local action="$1"
    local environment="$2"
    
    log_info "Updating load balancer: $action $environment"
    
    # If using nginx as load balancer
    local nginx_config="/etc/nginx/sites-available/askproai"
    local nginx_upstream="askproai_backend"
    
    case "$action" in
        "add")
            # Add new backend to pool
            if [ "$environment" == "green" ]; then
                sed -i "s/# server 127.0.0.1:8001/server 127.0.0.1:8001/" "$nginx_config"
            else
                sed -i "s/# server 127.0.0.1:8000/server 127.0.0.1:8000/" "$nginx_config"
            fi
            ;;
        "remove")
            # Remove old backend from pool
            if [ "$environment" == "blue" ]; then
                sed -i "s/server 127.0.0.1:8000/# server 127.0.0.1:8000/" "$nginx_config"
            else
                sed -i "s/server 127.0.0.1:8001/# server 127.0.0.1:8001/" "$nginx_config"
            fi
            ;;
        "drain")
            # Mark backend for graceful drain
            if [ "$environment" == "blue" ]; then
                sed -i "s/server 127.0.0.1:8000/server 127.0.0.1:8000 down/" "$nginx_config"
            else
                sed -i "s/server 127.0.0.1:8001/server 127.0.0.1:8001 down/" "$nginx_config"
            fi
            ;;
    esac
    
    # Test and reload nginx
    nginx -t || {
        log_error "Nginx configuration test failed"
        return 1
    }
    
    systemctl reload nginx
    log_success "Load balancer updated"
}

# ===================================================================
# Service Management
# ===================================================================

reload_services() {
    log_section "Reloading Services"
    
    # Reload PHP-FPM gracefully
    systemctl reload php8.3-fpm || systemctl reload php8.2-fpm
    
    # Restart queue workers
    php "$APP_DIR/artisan" queue:restart
    
    # Restart Horizon if running
    if pgrep -f "horizon" > /dev/null; then
        php "$APP_DIR/artisan" horizon:terminate
        sleep 5
        nohup php "$APP_DIR/artisan" horizon > /dev/null 2>&1 &
    fi
    
    # Clear OPcache
    if command -v cachetool &> /dev/null; then
        cachetool opcache:reset --fcgi=/var/run/php/php8.3-fpm.sock || true
    fi
    
    log_success "Services reloaded"
}

# ===================================================================
# Deployment Metrics
# ===================================================================

collect_deployment_metrics() {
    local start_time="$1"
    local end_time=$(date +%s)
    local duration=$((end_time - start_time))
    
    local metrics=$(cat <<EOF
{
    "deployment_id": "$(uuidgen)",
    "timestamp": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
    "environment": "$ENVIRONMENT",
    "duration_seconds": $duration,
    "git_commit": "$(cd $APP_DIR && git rev-parse HEAD 2>/dev/null || echo 'unknown')",
    "php_version": "$(php -r 'echo PHP_VERSION;')",
    "laravel_version": "$(cd $APP_DIR && php artisan --version | grep -oP 'Laravel Framework \K[0-9.]+')",
    "deployment_type": "zero_downtime_blue_green"
}
EOF
)
    
    echo "$metrics" > "$METRICS_FILE"
    
    # Send to monitoring system
    if [ -f "$APP_DIR/artisan" ]; then
        php "$APP_DIR/artisan" monitoring:record-deployment --json="$metrics" 2>/dev/null || true
    fi
}

save_deployment_metrics() {
    local exit_code="$1"
    local metrics_file="${METRICS_FILE}.final"
    
    if [ -f "$METRICS_FILE" ]; then
        jq ". + {\"exit_code\": $exit_code, \"status\": \"$([ $exit_code -eq 0 ] && echo 'success' || echo 'failed')\"}" \
            "$METRICS_FILE" > "$metrics_file"
    fi
}

# ===================================================================
# Main Deployment Flow
# ===================================================================

main() {
    local start_time=$(date +%s)
    
    log_info "Starting Zero-Downtime Deployment"
    log_info "Environment: $ENVIRONMENT"
    log_info "Dry Run: $DRY_RUN"
    log_info "Log File: $DEPLOYMENT_LOG"
    
    # Send start notification
    send_alert "info" "Deployment Started" \
        "Zero-downtime deployment initiated for $ENVIRONMENT environment"
    
    # Acquire lock
    acquire_lock
    
    # Pre-flight checks
    check_prerequisites
    
    # Determine target environment
    local current_env=$([ -L "$APP_DIR" ] && basename "$(readlink "$APP_DIR")" | sed 's/.*-//' || echo "blue")
    local target_env=$([ "$current_env" == "blue" ] && echo "green" || echo "blue")
    local target_dir=$(get_environment_dir "$target_env")
    
    log_info "Current environment: $current_env"
    log_info "Target environment: $target_env"
    
    # Prepare target environment
    prepare_target_environment "$target_env"
    
    # Check migration safety
    check_migration_safety "$target_dir"
    
    # Run migrations
    run_migrations_safely "$target_dir"
    
    # Health check on target
    if ! run_health_check "$target_dir" "basic"; then
        log_error "Target environment health check failed"
        exit 1
    fi
    
    # Start zero-downtime switch
    log_section "Starting Zero-Downtime Switch"
    
    if [ "$DRY_RUN" != "true" ]; then
        # Add target to load balancer
        update_load_balancer "add" "$target_env"
        
        # Wait for connections to establish
        log_info "Waiting for new connections to establish..."
        sleep 10
        
        # Extended health check
        if ! run_health_check "$target_dir" "extended"; then
            log_error "Extended health check failed, rolling back"
            update_load_balancer "remove" "$target_env"
            exit 1
        fi
        
        # Drain current environment
        log_info "Draining connections from $current_env environment..."
        update_load_balancer "drain" "$current_env"
        
        # Wait for drain
        log_info "Waiting $LOAD_BALANCER_DRAIN_TIME seconds for connection drain..."
        sleep $LOAD_BALANCER_DRAIN_TIME
        
        # Switch symlink
        log_info "Switching application symlink..."
        ln -sfn "$target_dir" "${APP_DIR}.tmp"
        mv -Tf "${APP_DIR}.tmp" "$APP_DIR"
        
        # Remove old environment from load balancer
        update_load_balancer "remove" "$current_env"
        
        # Reload services
        reload_services
        
        # Final health check
        if ! run_health_check "$APP_DIR" "extended"; then
            log_error "Post-deployment health check failed"
            rollback_on_error "Post-deployment health check failed"
        fi
    else
        log_info "DRY RUN: Skipping actual deployment switch"
    fi
    
    # Collect metrics
    collect_deployment_metrics "$start_time"
    
    # Success
    local duration=$(($(date +%s) - start_time))
    log_success "Zero-downtime deployment completed in ${duration}s"
    
    # Send success notification
    send_alert "success" "Deployment Completed" \
        "Zero-downtime deployment successful! Duration: ${duration}s, Active: $target_env"
    
    # Show summary
    echo -e "\n${GREEN}═══ Deployment Summary ═══${NC}"
    echo -e "Environment: ${PURPLE}$ENVIRONMENT${NC}"
    echo -e "Active: ${CYAN}$target_env${NC}"
    echo -e "Duration: ${duration}s"
    echo -e "Status: ${GREEN}SUCCESS${NC}"
    echo -e "${GREEN}═════════════════════════${NC}\n"
    
    # Cleanup old backups
    find "$BACKUP_DIR" -name "*.tar.gz" -mtime +$MAX_BACKUP_AGE_DAYS -delete || true
    
    exit 0
}

# ===================================================================
# Script Execution
# ===================================================================

# Handle script arguments
case "${1:-}" in
    "help"|"--help"|"-h")
        cat <<EOF
AskProAI Zero-Downtime Deployment Script

Usage: $0 [environment] [dry-run]

Arguments:
  environment   Deployment environment (production, staging, development)
                Default: production
  
  dry-run       Run without making actual changes (true/false)
                Default: false

Examples:
  $0                    # Deploy to production
  $0 staging           # Deploy to staging
  $0 production true   # Dry run for production

Environment Variables:
  SLACK_WEBHOOK_URL    Slack webhook for notifications
  DEPLOY_TARGET        Git branch/tag to deploy (default: origin/main)

EOF
        exit 0
        ;;
esac

# Set error handling
set -E
trap 'rollback_on_error "Unexpected error on line $LINENO"' ERR

# Run main deployment
if [ "${BASH_SOURCE[0]}" == "${0}" ]; then
    main "$@"
fi