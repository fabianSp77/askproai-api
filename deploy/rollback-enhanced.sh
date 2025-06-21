#!/bin/bash

# AskProAI Emergency Rollback Script (Enhanced)
# Version: 2.0.0
# Description: Comprehensive rollback with verification and safety checks

set -euo pipefail
IFS=$'\n\t'

# Load environment-specific configuration
if [ -f "$(dirname "$0")/deploy.conf" ]; then
    source "$(dirname "$0")/deploy.conf"
fi

# Configuration
readonly APP_DIR="${APP_DIR:-/var/www/api-gateway}"
readonly BACKUP_DIR="${BACKUP_DIR:-/var/backups/askproai}"
readonly LOG_DIR="${LOG_DIR:-/var/log/askproai}"
readonly ROLLBACK_LOG="${LOG_DIR}/rollback-$(date +%Y%m%d_%H%M%S).log"
readonly LOCK_FILE="/var/run/askproai-rollback.lock"

# Parse command line arguments
AUTO_MODE=false
BACKUP_NAME=""
SKIP_CONFIRM=false
PARTIAL_ROLLBACK=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --auto)
            AUTO_MODE=true
            shift
            ;;
        --backup=*)
            BACKUP_NAME="${1#*=}"
            shift
            ;;
        --skip-confirm)
            SKIP_CONFIRM=true
            shift
            ;;
        --partial)
            PARTIAL_ROLLBACK=true
            shift
            ;;
        *)
            echo "Unknown option: $1"
            exit 1
            ;;
    esac
done

# Colors
readonly RED='\033[0;31m'
readonly GREEN='\033[0;32m'
readonly YELLOW='\033[1;33m'
readonly BLUE='\033[0;34m'
readonly NC='\033[0m'

# Ensure log directory exists
mkdir -p "$LOG_DIR"

# Logging functions
log() {
    local level="$1"
    shift
    local message="$*"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo -e "${timestamp} [${level}] ${message}" | tee -a "$ROLLBACK_LOG"
    logger -t "askproai-rollback" "${level}: ${message}"
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

# Lock handling
acquire_lock() {
    if [ -f "$LOCK_FILE" ]; then
        log_error "Another rollback is already in progress"
        exit 1
    fi
    
    echo $$ > "$LOCK_FILE"
    trap "rm -f $LOCK_FILE" EXIT INT TERM
}

# Send notifications
send_notification() {
    local message="$1"
    local webhook_url="${SLACK_WEBHOOK_URL:-}"
    
    if [ -n "$webhook_url" ]; then
        curl -X POST -H 'Content-type: application/json' \
            --data "{
                \"text\": \"AskProAI Rollback\",
                \"attachments\": [{
                    \"color\": \"#ff0000\",
                    \"text\": \"$message\",
                    \"fields\": [
                        {\"title\": \"Time\", \"value\": \"$(date)\", \"short\": true}
                    ]
                }]
            }" \
            "$webhook_url" 2>/dev/null || true
    fi
    
    if [ -n "${DEPLOYMENT_EMAIL:-}" ]; then
        echo "$message" | mail -s "AskProAI Rollback Alert" "$DEPLOYMENT_EMAIL" || true
    fi
}

# List available backups
list_backups() {
    log_section "Available Backups"
    
    if [ ! -d "$BACKUP_DIR" ]; then
        log_error "Backup directory not found: $BACKUP_DIR"
        exit 1
    fi
    
    local backups=($(ls -1dt "$BACKUP_DIR"/backup_* 2>/dev/null | head -20))
    
    if [ ${#backups[@]} -eq 0 ]; then
        log_error "No backups found"
        exit 1
    fi
    
    echo -e "\n${YELLOW}Available backups:${NC}"
    for i in "${!backups[@]}"; do
        local backup="${backups[$i]}"
        local backup_name=$(basename "$backup")
        local metadata_file="$backup/metadata.json"
        
        if [ -f "$metadata_file" ]; then
            local timestamp=$(jq -r '.timestamp' "$metadata_file" 2>/dev/null || echo "Unknown")
            local commit=$(jq -r '.git_commit' "$metadata_file" 2>/dev/null || echo "Unknown")
            local mode=$(jq -r '.mode' "$metadata_file" 2>/dev/null || echo "Unknown")
            
            printf "%2d) %-30s [%s] %s (%s)\n" \
                $((i+1)) "$backup_name" "$mode" "$timestamp" "${commit:0:8}"
        else
            printf "%2d) %-30s\n" $((i+1)) "$backup_name"
        fi
    done
    
    echo ""
}

# Select backup
select_backup() {
    if [ -n "$BACKUP_NAME" ]; then
        if [ "$BACKUP_NAME" == "latest" ]; then
            BACKUP_NAME=$(ls -1dt "$BACKUP_DIR"/backup_* 2>/dev/null | head -1 | xargs basename)
        fi
        
        BACKUP_PATH="$BACKUP_DIR/$BACKUP_NAME"
        
        if [ ! -d "$BACKUP_PATH" ]; then
            log_error "Backup not found: $BACKUP_PATH"
            exit 1
        fi
        
        log_info "Selected backup: $BACKUP_NAME"
        return
    fi
    
    list_backups
    
    echo -n "Select backup number (or 'q' to quit): "
    read -r selection
    
    if [ "$selection" == "q" ]; then
        log_info "Rollback cancelled by user"
        exit 0
    fi
    
    local backups=($(ls -1dt "$BACKUP_DIR"/backup_* 2>/dev/null | head -20))
    
    if [[ ! "$selection" =~ ^[0-9]+$ ]] || [ "$selection" -lt 1 ] || [ "$selection" -gt ${#backups[@]} ]; then
        log_error "Invalid selection"
        exit 1
    fi
    
    BACKUP_PATH="${backups[$((selection-1))]}"
    BACKUP_NAME=$(basename "$BACKUP_PATH")
    
    log_info "Selected backup: $BACKUP_NAME"
}

# Verify backup integrity
verify_backup() {
    log_section "Verifying Backup Integrity"
    
    # Check required files
    local required_files=(
        "metadata.json"
        "database.sql.gz"
        ".env.backup"
    )
    
    for file in "${required_files[@]}"; do
        if [ ! -f "$BACKUP_PATH/$file" ]; then
            log_error "Required backup file missing: $file"
            exit 1
        fi
    done
    
    # Verify manifest if exists
    if [ -f "$BACKUP_PATH/manifest.txt" ]; then
        log_info "Verifying backup manifest..."
        cd "$BACKUP_PATH"
        if ! md5sum -c manifest.txt --quiet 2>/dev/null; then
            log_warn "Backup manifest verification failed - backup may be corrupted"
            
            if [ "$SKIP_CONFIRM" != "true" ]; then
                echo -n "Continue anyway? (yes/no): "
                read -r confirm
                if [ "$confirm" != "yes" ]; then
                    log_info "Rollback cancelled"
                    exit 0
                fi
            fi
        else
            log_info "Backup integrity verified"
        fi
        cd - > /dev/null
    fi
}

# Show rollback plan
show_rollback_plan() {
    log_section "Rollback Plan"
    
    if [ -f "$BACKUP_PATH/metadata.json" ]; then
        echo -e "\n${YELLOW}Backup Information:${NC}"
        jq . "$BACKUP_PATH/metadata.json" 2>/dev/null || cat "$BACKUP_PATH/metadata.json"
    fi
    
    echo -e "\n${YELLOW}Rollback will perform the following actions:${NC}"
    echo "1. Enable maintenance mode"
    echo "2. Create safety backup of current state"
    echo "3. Stop application services"
    
    if [ "$PARTIAL_ROLLBACK" == "true" ]; then
        echo "4. Restore database only (partial rollback)"
    else
        echo "4. Restore database"
        echo "5. Restore application files"
        echo "6. Restore environment configuration"
    fi
    
    echo "7. Clear all caches"
    echo "8. Restart services"
    echo "9. Run health checks"
    echo "10. Disable maintenance mode"
    
    if [ "$SKIP_CONFIRM" != "true" ] && [ "$AUTO_MODE" != "true" ]; then
        echo -e "\n${RED}WARNING: This will overwrite the current application state!${NC}"
        echo -n "Proceed with rollback? (yes/no): "
        read -r confirm
        
        if [ "$confirm" != "yes" ]; then
            log_info "Rollback cancelled by user"
            exit 0
        fi
    fi
}

# Create safety backup
create_safety_backup() {
    log_section "Creating Safety Backup"
    
    local safety_backup="$BACKUP_DIR/safety_backup_$(date +%Y%m%d_%H%M%S)"
    mkdir -p "$safety_backup"
    
    # Quick database backup
    log_info "Backing up current database state..."
    source "$APP_DIR/.env"
    
    mysqldump \
        --single-transaction \
        --routines \
        --triggers \
        --add-drop-database \
        "$DB_DATABASE" \
        -h"$DB_HOST" \
        -u"$DB_USERNAME" \
        -p"$DB_PASSWORD" \
        | gzip > "$safety_backup/database.sql.gz" || {
            log_warn "Safety database backup failed"
        }
    
    # Save current git state
    cd "$APP_DIR"
    git rev-parse HEAD > "$safety_backup/git_commit.txt"
    
    log_info "Safety backup created: $(basename "$safety_backup")"
}

# Enable maintenance mode
enable_maintenance() {
    log_section "Enabling Maintenance Mode"
    
    cd "$APP_DIR"
    
    php artisan down \
        --retry=60 \
        --secret="rollback-$(date +%s)" \
        --message="Emergency maintenance in progress. We'll be back soon." || {
            log_warn "Failed to enable maintenance mode"
        }
    
    # Stop queue workers
    php artisan queue:stop || true
    
    # Wait for active requests
    sleep 10
}

# Stop services
stop_services() {
    log_section "Stopping Services"
    
    # Stop Horizon gracefully
    if pgrep -f "horizon" > /dev/null; then
        log_info "Stopping Horizon workers..."
        cd "$APP_DIR"
        php artisan horizon:terminate || true
        sleep 5
    fi
    
    # Stop any running queue workers
    if command -v supervisorctl &> /dev/null; then
        supervisorctl stop all || true
    fi
    
    log_info "Services stopped"
}

# Restore database
restore_database() {
    log_section "Restoring Database"
    
    local db_backup="$BACKUP_PATH/database.sql.gz"
    
    if [ ! -f "$db_backup" ]; then
        log_error "Database backup not found: $db_backup"
        exit 1
    fi
    
    # Load database credentials
    source "$APP_DIR/.env"
    
    log_info "Dropping existing database..."
    mysql -h"$DB_HOST" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "DROP DATABASE IF EXISTS $DB_DATABASE; CREATE DATABASE $DB_DATABASE;" || {
        log_error "Failed to recreate database"
        exit 1
    }
    
    log_info "Restoring database from backup..."
    pv "$db_backup" | gunzip | mysql -h"$DB_HOST" -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" || {
        log_error "Database restore failed"
        exit 1
    }
    
    log_info "Database restored successfully"
}

# Restore application files
restore_files() {
    log_section "Restoring Application Files"
    
    if [ "$PARTIAL_ROLLBACK" == "true" ]; then
        log_info "Skipping file restoration (partial rollback)"
        return
    fi
    
    local files_backup="$BACKUP_PATH/files.tar.gz"
    
    if [ ! -f "$files_backup" ]; then
        log_warn "Application files backup not found, skipping file restoration"
        return
    fi
    
    # Create temporary directory for extraction
    local temp_dir="/tmp/askproai_rollback_$$"
    mkdir -p "$temp_dir"
    
    log_info "Extracting backup files..."
    tar -xzf "$files_backup" -C "$temp_dir" || {
        log_error "Failed to extract backup files"
        rm -rf "$temp_dir"
        exit 1
    }
    
    # Preserve current storage uploads
    if [ -d "$APP_DIR/storage/app/public" ]; then
        cp -a "$APP_DIR/storage/app/public" "$temp_dir/storage/app/" || true
    fi
    
    # Restore files
    log_info "Restoring application files..."
    rsync -av --delete \
        --exclude="storage/logs/*" \
        --exclude="storage/app/public/*" \
        --exclude="bootstrap/cache/*" \
        --exclude="node_modules" \
        --exclude="vendor" \
        "$temp_dir/" "$APP_DIR/" || {
            log_error "File restoration failed"
            rm -rf "$temp_dir"
            exit 1
        }
    
    rm -rf "$temp_dir"
    
    # Restore environment file
    if [ -f "$BACKUP_PATH/.env.backup" ]; then
        cp "$BACKUP_PATH/.env.backup" "$APP_DIR/.env"
        log_info "Environment file restored"
    fi
    
    log_info "Application files restored"
}

# Reinstall dependencies
reinstall_dependencies() {
    log_section "Reinstalling Dependencies"
    
    cd "$APP_DIR"
    
    # Install Composer dependencies
    log_info "Installing PHP dependencies..."
    composer install --no-dev --optimize-autoloader || {
        log_error "Composer install failed"
        exit 1
    }
    
    # Install NPM dependencies
    log_info "Installing Node dependencies..."
    npm ci || {
        log_error "NPM install failed"
        exit 1
    }
    
    # Rebuild assets
    npm run build || {
        log_warn "Asset build failed"
    }
}

# Clear all caches
clear_caches() {
    log_section "Clearing Caches"
    
    cd "$APP_DIR"
    
    # Clear Laravel caches
    php artisan cache:clear || true
    php artisan config:clear || true
    php artisan route:clear || true
    php artisan view:clear || true
    php artisan optimize:clear || true
    
    # Clear OPcache
    if command -v cachetool &> /dev/null; then
        cachetool opcache:reset --fcgi=/var/run/php/php8.2-fpm.sock || true
    fi
    
    # Clear Redis
    redis-cli FLUSHDB || true
    
    log_info "All caches cleared"
}

# Restore permissions
restore_permissions() {
    log_section "Restoring Permissions"
    
    cd "$APP_DIR"
    
    # Set ownership
    chown -R www-data:www-data storage bootstrap/cache
    
    # Set permissions
    find storage bootstrap/cache -type d -exec chmod 755 {} \;
    find storage bootstrap/cache -type f -exec chmod 644 {} \;
    
    # Make scripts executable
    chmod +x artisan
    find . -name "*.sh" -type f -exec chmod +x {} \;
    
    log_info "Permissions restored"
}

# Restart services
restart_services() {
    log_section "Restarting Services"
    
    # Restart PHP-FPM
    systemctl restart php8.2-fpm || {
        log_error "PHP-FPM restart failed"
        exit 1
    }
    
    # Restart Nginx
    systemctl restart nginx || {
        log_error "Nginx restart failed"
        exit 1
    }
    
    # Start queue workers
    if command -v supervisorctl &> /dev/null; then
        supervisorctl start all || true
    fi
    
    cd "$APP_DIR"
    php artisan horizon:terminate || true
    
    log_info "Services restarted"
}

# Run health checks
run_health_checks() {
    log_section "Running Health Checks"
    
    # Wait for services to stabilize
    sleep 10
    
    # Check application health
    local health_response=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/api/health 2>/dev/null)
    
    if [ "$health_response" != "200" ]; then
        log_warn "Health check returned status: $health_response"
    else
        log_info "Application health check passed"
    fi
    
    # Check database
    cd "$APP_DIR"
    if php artisan db:show &>/dev/null; then
        log_info "Database connection verified"
    else
        log_error "Database connection failed"
    fi
    
    # Check Redis
    if redis-cli ping &>/dev/null; then
        log_info "Redis connection verified"
    else
        log_warn "Redis connection failed"
    fi
}

# Disable maintenance mode
disable_maintenance() {
    log_section "Disabling Maintenance Mode"
    
    cd "$APP_DIR"
    
    php artisan up || {
        log_error "Failed to disable maintenance mode"
    }
    
    log_info "Maintenance mode disabled"
}

# Post-rollback tasks
post_rollback() {
    log_section "Post-Rollback Tasks"
    
    cd "$APP_DIR"
    
    # Recache configuration
    php artisan config:cache || true
    php artisan route:cache || true
    
    # Log rollback event
    echo "$(date -u +%Y-%m-%dT%H:%M:%SZ)|ROLLBACK|$BACKUP_NAME" >> "$LOG_DIR/deployments.log"
    
    # Send metrics
    if [ -n "${METRICS_ENDPOINT:-}" ]; then
        curl -X POST "$METRICS_ENDPOINT" \
            -H "Content-Type: application/json" \
            -d "{
                \"rollback\": {
                    \"timestamp\": \"$(date -u +%Y-%m-%dT%H:%M:%SZ)\",
                    \"backup\": \"$BACKUP_NAME\",
                    \"duration\": $SECONDS,
                    \"status\": \"success\"
                }
            }" 2>/dev/null || true
    fi
}

# Main rollback flow
main() {
    local start_time=$(date +%s)
    
    log_info "Starting AskProAI emergency rollback"
    log_info "Log file: $ROLLBACK_LOG"
    
    # Acquire lock
    acquire_lock
    
    # Send notification
    send_notification "Emergency rollback initiated!"
    
    # Select and verify backup
    select_backup
    verify_backup
    show_rollback_plan
    
    # Perform rollback
    create_safety_backup
    enable_maintenance
    stop_services
    restore_database
    restore_files
    
    if [ "$PARTIAL_ROLLBACK" != "true" ]; then
        reinstall_dependencies
    fi
    
    clear_caches
    restore_permissions
    restart_services
    run_health_checks
    disable_maintenance
    post_rollback
    
    # Calculate duration
    local end_time=$(date +%s)
    local duration=$((end_time - start_time))
    
    # Success
    log_info "Rollback completed successfully in ${duration}s"
    send_notification "Rollback completed successfully to backup: $BACKUP_NAME"
    
    # Show summary
    echo -e "\n${GREEN}=== Rollback Summary ===${NC}"
    echo -e "Restored from: $BACKUP_NAME"
    echo -e "Duration: ${duration}s"
    echo -e "Log file: $ROLLBACK_LOG"
    echo -e "${GREEN}======================${NC}\n"
    
    echo -e "${YELLOW}IMPORTANT:${NC} Please verify the application is working correctly!"
    echo -e "${YELLOW}Consider investigating what caused the need for rollback.${NC}\n"
    
    exit 0
}

# Check if running with appropriate privileges
if [ "$EUID" -eq 0 ] && [ "$AUTO_MODE" != "true" ]; then
    log_warn "Running as root - be careful!"
fi

# Run main function
main "$@"