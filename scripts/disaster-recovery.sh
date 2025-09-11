#!/bin/bash

# =====================================================
# DISASTER RECOVERY & ROLLBACK PROCEDURES
# AskPro AI Billing System v2.0
# =====================================================
# 
# This script provides comprehensive disaster recovery
# capabilities including automated rollback, data
# restoration, and system verification.
#
# Usage:
#   ./disaster-recovery.sh [command] [options]
#
# Commands:
#   backup        Create full system backup
#   rollback      Rollback to previous version
#   restore       Restore from specific backup
#   verify        Verify system integrity
#   emergency     Emergency shutdown and restore
# =====================================================

set -euo pipefail

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
BACKUP_DIR="/var/www/backups"
LOG_DIR="/var/log/askpro"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
LOG_FILE="${LOG_DIR}/disaster-recovery-${TIMESTAMP}.log"

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Ensure log directory exists
mkdir -p "$LOG_DIR"

# Logging function
log() {
    echo -e "${1}" | tee -a "$LOG_FILE"
}

log_error() {
    echo -e "${RED}[ERROR] ${1}${NC}" | tee -a "$LOG_FILE"
}

log_success() {
    echo -e "${GREEN}[SUCCESS] ${1}${NC}" | tee -a "$LOG_FILE"
}

log_warning() {
    echo -e "${YELLOW}[WARNING] ${1}${NC}" | tee -a "$LOG_FILE"
}

log_info() {
    echo -e "${BLUE}[INFO] ${1}${NC}" | tee -a "$LOG_FILE"
}

# =====================================================
# BACKUP FUNCTIONS
# =====================================================

create_full_backup() {
    log_info "Starting full system backup..."
    
    local backup_name="backup-${TIMESTAMP}"
    local backup_path="${BACKUP_DIR}/${backup_name}"
    
    # Create backup directory
    mkdir -p "$backup_path"
    
    # 1. Backup database
    log_info "Backing up database..."
    mysqldump \
        --single-transaction \
        --routines \
        --triggers \
        --events \
        --add-drop-database \
        --databases askpro_production \
        > "${backup_path}/database.sql" 2>> "$LOG_FILE"
    
    if [ $? -eq 0 ]; then
        log_success "Database backup completed"
    else
        log_error "Database backup failed"
        return 1
    fi
    
    # 2. Backup application files
    log_info "Backing up application files..."
    tar -czf "${backup_path}/application.tar.gz" \
        -C "$PROJECT_ROOT" \
        --exclude=node_modules \
        --exclude=vendor \
        --exclude=storage/logs \
        --exclude=storage/framework/cache \
        --exclude=storage/framework/sessions \
        --exclude=storage/framework/views \
        --exclude=.git \
        . 2>> "$LOG_FILE"
    
    if [ $? -eq 0 ]; then
        log_success "Application files backup completed"
    else
        log_error "Application files backup failed"
        return 1
    fi
    
    # 3. Backup environment configuration
    log_info "Backing up environment configuration..."
    cp "${PROJECT_ROOT}/.env" "${backup_path}/.env.backup" 2>> "$LOG_FILE"
    
    # 4. Backup Redis data
    log_info "Backing up Redis data..."
    redis-cli --rdb "${backup_path}/redis.rdb" 2>> "$LOG_FILE"
    
    # 5. Create backup manifest
    cat > "${backup_path}/manifest.json" << EOF
{
    "timestamp": "${TIMESTAMP}",
    "version": "$(cd $PROJECT_ROOT && git describe --tags --always)",
    "branch": "$(cd $PROJECT_ROOT && git rev-parse --abbrev-ref HEAD)",
    "commit": "$(cd $PROJECT_ROOT && git rev-parse HEAD)",
    "php_version": "$(php -v | head -n 1)",
    "mysql_version": "$(mysql --version)",
    "redis_version": "$(redis-server --version)",
    "files": {
        "database": "database.sql",
        "application": "application.tar.gz",
        "environment": ".env.backup",
        "redis": "redis.rdb"
    }
}
EOF
    
    # 6. Create integrity checksum
    log_info "Creating integrity checksums..."
    cd "$backup_path"
    sha256sum database.sql application.tar.gz .env.backup redis.rdb > checksums.sha256
    
    # 7. Compress entire backup
    log_info "Compressing backup..."
    cd "$BACKUP_DIR"
    tar -czf "${backup_name}.tar.gz" "$backup_name/"
    
    # 8. Encrypt backup (optional)
    if [ ! -z "${BACKUP_ENCRYPTION_KEY:-}" ]; then
        log_info "Encrypting backup..."
        openssl enc -aes-256-cbc \
            -salt \
            -in "${backup_name}.tar.gz" \
            -out "${backup_name}.tar.gz.enc" \
            -pass pass:"$BACKUP_ENCRYPTION_KEY"
        rm "${backup_name}.tar.gz"
    fi
    
    # Clean up uncompressed backup
    rm -rf "$backup_path"
    
    log_success "Full backup completed: ${backup_name}"
    echo "$backup_name"
}

# =====================================================
# ROLLBACK FUNCTIONS
# =====================================================

rollback_to_previous() {
    log_info "Starting rollback to previous version..."
    
    # 1. Find the most recent backup
    local latest_backup=$(ls -t "${BACKUP_DIR}"/backup-*.tar.gz* 2>/dev/null | head -1)
    
    if [ -z "$latest_backup" ]; then
        log_error "No backup found to rollback to"
        return 1
    fi
    
    log_info "Found backup: $(basename $latest_backup)"
    
    # 2. Create safety backup of current state
    log_info "Creating safety backup of current state..."
    local safety_backup="safety-backup-${TIMESTAMP}"
    create_full_backup > /dev/null
    mv "${BACKUP_DIR}/backup-${TIMESTAMP}.tar.gz" "${BACKUP_DIR}/${safety_backup}.tar.gz"
    
    # 3. Perform rollback
    restore_from_backup "$latest_backup"
}

restore_from_backup() {
    local backup_file="$1"
    
    if [ ! -f "$backup_file" ]; then
        log_error "Backup file not found: $backup_file"
        return 1
    fi
    
    log_info "Restoring from backup: $(basename $backup_file)"
    
    # 1. Create temporary restore directory
    local restore_dir="${BACKUP_DIR}/restore-${TIMESTAMP}"
    mkdir -p "$restore_dir"
    
    # 2. Decrypt if necessary
    if [[ "$backup_file" == *.enc ]]; then
        log_info "Decrypting backup..."
        openssl enc -aes-256-cbc \
            -d \
            -in "$backup_file" \
            -out "${restore_dir}/backup.tar.gz" \
            -pass pass:"${BACKUP_ENCRYPTION_KEY:-}"
        backup_file="${restore_dir}/backup.tar.gz"
    fi
    
    # 3. Extract backup
    log_info "Extracting backup..."
    tar -xzf "$backup_file" -C "$restore_dir"
    
    # Find the backup directory
    local backup_content_dir=$(find "$restore_dir" -maxdepth 1 -type d -name "backup-*" | head -1)
    
    if [ -z "$backup_content_dir" ]; then
        log_error "Invalid backup structure"
        rm -rf "$restore_dir"
        return 1
    fi
    
    # 4. Verify checksums
    log_info "Verifying backup integrity..."
    cd "$backup_content_dir"
    if [ -f "checksums.sha256" ]; then
        sha256sum -c checksums.sha256 > /dev/null 2>&1
        if [ $? -ne 0 ]; then
            log_error "Backup integrity check failed"
            rm -rf "$restore_dir"
            return 1
        fi
    fi
    
    # 5. Put application in maintenance mode
    log_info "Enabling maintenance mode..."
    cd "$PROJECT_ROOT"
    php artisan down --message="System maintenance in progress" --retry=60
    
    # 6. Stop services
    log_info "Stopping services..."
    systemctl stop php8.3-fpm || true
    systemctl stop horizon || true
    systemctl stop queue-worker || true
    
    # 7. Restore database
    log_info "Restoring database..."
    mysql askpro_production < "${backup_content_dir}/database.sql"
    
    if [ $? -ne 0 ]; then
        log_error "Database restoration failed"
        # Attempt to restore from safety backup
        restore_safety_backup
        return 1
    fi
    
    # 8. Restore application files
    log_info "Restoring application files..."
    # Backup current vendor and node_modules
    mv "${PROJECT_ROOT}/vendor" "${PROJECT_ROOT}/vendor.backup" 2>/dev/null || true
    mv "${PROJECT_ROOT}/node_modules" "${PROJECT_ROOT}/node_modules.backup" 2>/dev/null || true
    
    # Extract application files
    tar -xzf "${backup_content_dir}/application.tar.gz" -C "$PROJECT_ROOT"
    
    # Restore vendor and node_modules
    mv "${PROJECT_ROOT}/vendor.backup" "${PROJECT_ROOT}/vendor" 2>/dev/null || true
    mv "${PROJECT_ROOT}/node_modules.backup" "${PROJECT_ROOT}/node_modules" 2>/dev/null || true
    
    # 9. Restore environment configuration
    log_info "Restoring environment configuration..."
    cp "${backup_content_dir}/.env.backup" "${PROJECT_ROOT}/.env"
    
    # 10. Restore Redis data
    log_info "Restoring Redis data..."
    systemctl stop redis
    cp "${backup_content_dir}/redis.rdb" /var/lib/redis/dump.rdb
    chown redis:redis /var/lib/redis/dump.rdb
    systemctl start redis
    
    # 11. Run post-restore tasks
    log_info "Running post-restore tasks..."
    cd "$PROJECT_ROOT"
    
    # Clear caches
    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    
    # Rebuild caches
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    # Run migrations (if any)
    php artisan migrate --force
    
    # 12. Start services
    log_info "Starting services..."
    systemctl start php8.3-fpm
    systemctl start horizon
    systemctl start queue-worker
    
    # 13. Verify system health
    verify_system_health
    
    if [ $? -eq 0 ]; then
        # 14. Disable maintenance mode
        log_info "Disabling maintenance mode..."
        php artisan up
        
        log_success "Restoration completed successfully"
        
        # Clean up
        rm -rf "$restore_dir"
    else
        log_error "System health check failed after restoration"
        return 1
    fi
}

# =====================================================
# VERIFICATION FUNCTIONS
# =====================================================

verify_system_health() {
    log_info "Verifying system health..."
    
    local errors=0
    
    # 1. Check database connectivity
    log_info "Checking database connectivity..."
    mysql -e "SELECT 1" askpro_production > /dev/null 2>&1
    if [ $? -eq 0 ]; then
        log_success "Database connectivity OK"
    else
        log_error "Database connectivity FAILED"
        ((errors++))
    fi
    
    # 2. Check Redis connectivity
    log_info "Checking Redis connectivity..."
    redis-cli ping > /dev/null 2>&1
    if [ $? -eq 0 ]; then
        log_success "Redis connectivity OK"
    else
        log_error "Redis connectivity FAILED"
        ((errors++))
    fi
    
    # 3. Check critical tables
    log_info "Checking critical database tables..."
    local tables=("tenants" "users" "transactions" "balance_topups")
    for table in "${tables[@]}"; do
        mysql -e "SELECT COUNT(*) FROM $table" askpro_production > /dev/null 2>&1
        if [ $? -eq 0 ]; then
            log_success "Table $table OK"
        else
            log_error "Table $table FAILED"
            ((errors++))
        fi
    done
    
    # 4. Check application health endpoint
    log_info "Checking application health endpoint..."
    response=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/health)
    if [ "$response" = "200" ]; then
        log_success "Application health endpoint OK"
    else
        log_error "Application health endpoint FAILED (HTTP $response)"
        ((errors++))
    fi
    
    # 5. Check Stripe connectivity
    log_info "Checking Stripe API connectivity..."
    cd "$PROJECT_ROOT"
    php -r "
        require 'vendor/autoload.php';
        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
        try {
            \Stripe\Account::retrieve();
            echo 'OK';
        } catch (\Exception \$e) {
            echo 'FAILED';
        }
    " > /tmp/stripe_check.txt 2>&1
    
    if grep -q "OK" /tmp/stripe_check.txt; then
        log_success "Stripe API connectivity OK"
    else
        log_error "Stripe API connectivity FAILED"
        ((errors++))
    fi
    
    # 6. Check file permissions
    log_info "Checking file permissions..."
    if [ -w "${PROJECT_ROOT}/storage" ] && [ -w "${PROJECT_ROOT}/bootstrap/cache" ]; then
        log_success "File permissions OK"
    else
        log_error "File permissions FAILED"
        ((errors++))
    fi
    
    # 7. Check services status
    log_info "Checking services status..."
    services=("nginx" "php8.3-fpm" "mysql" "redis")
    for service in "${services[@]}"; do
        if systemctl is-active --quiet "$service"; then
            log_success "Service $service OK"
        else
            log_error "Service $service FAILED"
            ((errors++))
        fi
    done
    
    # 8. Run billing system health check
    log_info "Running billing system health check..."
    cd "$PROJECT_ROOT"
    php artisan billing:health-check > /tmp/billing_health.txt 2>&1
    if grep -q "PASSED" /tmp/billing_health.txt; then
        log_success "Billing system health check OK"
    else
        log_error "Billing system health check FAILED"
        cat /tmp/billing_health.txt >> "$LOG_FILE"
        ((errors++))
    fi
    
    # Summary
    if [ $errors -eq 0 ]; then
        log_success "System health verification completed: ALL CHECKS PASSED"
        return 0
    else
        log_error "System health verification completed: $errors CHECKS FAILED"
        return 1
    fi
}

# =====================================================
# EMERGENCY FUNCTIONS
# =====================================================

emergency_shutdown() {
    log_warning "EMERGENCY SHUTDOWN INITIATED"
    
    # 1. Immediately stop accepting new requests
    log_info "Blocking new requests..."
    iptables -I INPUT -p tcp --dport 443 -j REJECT
    iptables -I INPUT -p tcp --dport 80 -j REJECT
    
    # 2. Put application in maintenance mode
    cd "$PROJECT_ROOT"
    php artisan down --message="Emergency maintenance" --retry=300
    
    # 3. Stop all services
    log_info "Stopping all services..."
    systemctl stop nginx
    systemctl stop php8.3-fpm
    systemctl stop horizon
    systemctl stop queue-worker
    
    # 4. Create emergency backup
    log_info "Creating emergency backup..."
    create_full_backup
    
    # 5. Flush Redis to prevent data corruption
    log_info "Flushing Redis cache..."
    redis-cli FLUSHALL
    
    log_warning "Emergency shutdown complete"
}

emergency_recovery() {
    log_warning "EMERGENCY RECOVERY INITIATED"
    
    # 1. Find the last known good backup
    log_info "Finding last known good backup..."
    local good_backup=$(ls -t "${BACKUP_DIR}"/backup-*.tar.gz* | head -1)
    
    if [ -z "$good_backup" ]; then
        log_error "No backup available for recovery"
        return 1
    fi
    
    # 2. Restore from backup
    restore_from_backup "$good_backup"
    
    # 3. Remove firewall blocks
    log_info "Removing firewall blocks..."
    iptables -D INPUT -p tcp --dport 443 -j REJECT 2>/dev/null || true
    iptables -D INPUT -p tcp --dport 80 -j REJECT 2>/dev/null || true
    
    # 4. Verify system
    verify_system_health
    
    if [ $? -eq 0 ]; then
        log_success "Emergency recovery completed successfully"
    else
        log_error "Emergency recovery completed with errors - manual intervention required"
        return 1
    fi
}

# =====================================================
# MONITORING & ALERTING
# =====================================================

send_alert() {
    local message="$1"
    local severity="${2:-INFO}"
    
    # Send email alert
    if [ ! -z "${ALERT_EMAIL:-}" ]; then
        echo "$message" | mail -s "[DISASTER RECOVERY] $severity: AskPro AI System" "$ALERT_EMAIL"
    fi
    
    # Send Slack notification
    if [ ! -z "${SLACK_WEBHOOK:-}" ]; then
        curl -X POST -H 'Content-type: application/json' \
            --data "{\"text\":\":warning: *Disaster Recovery Alert*\n*Severity:* $severity\n*Message:* $message\"}" \
            "$SLACK_WEBHOOK" 2>/dev/null
    fi
    
    # Log to syslog
    logger -t "askpro-disaster-recovery" -p "user.$severity" "$message"
}

# =====================================================
# AUTOMATED RECOVERY PROCEDURES
# =====================================================

auto_recover() {
    log_info "Starting automated recovery procedure..."
    
    # 1. Detect failure type
    local failure_type=""
    
    # Check database
    mysql -e "SELECT 1" askpro_production > /dev/null 2>&1
    if [ $? -ne 0 ]; then
        failure_type="database"
    fi
    
    # Check application
    response=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/health)
    if [ "$response" != "200" ]; then
        failure_type="${failure_type:+$failure_type,}application"
    fi
    
    # Check Redis
    redis-cli ping > /dev/null 2>&1
    if [ $? -ne 0 ]; then
        failure_type="${failure_type:+$failure_type,}redis"
    fi
    
    log_info "Detected failure type: ${failure_type:-none}"
    
    # 2. Attempt automatic recovery based on failure type
    case "$failure_type" in
        *database*)
            log_info "Attempting database recovery..."
            systemctl restart mysql
            sleep 5
            mysql -e "SELECT 1" askpro_production > /dev/null 2>&1
            if [ $? -ne 0 ]; then
                log_error "Database recovery failed - initiating rollback"
                rollback_to_previous
            else
                log_success "Database recovered"
            fi
            ;;
            
        *application*)
            log_info "Attempting application recovery..."
            cd "$PROJECT_ROOT"
            php artisan config:cache
            php artisan route:cache
            systemctl restart php8.3-fpm
            systemctl restart nginx
            sleep 5
            response=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/health)
            if [ "$response" != "200" ]; then
                log_error "Application recovery failed - initiating rollback"
                rollback_to_previous
            else
                log_success "Application recovered"
            fi
            ;;
            
        *redis*)
            log_info "Attempting Redis recovery..."
            systemctl restart redis
            sleep 5
            redis-cli ping > /dev/null 2>&1
            if [ $? -ne 0 ]; then
                log_error "Redis recovery failed"
                # Redis failure is not critical enough for rollback
            else
                log_success "Redis recovered"
            fi
            ;;
            
        *)
            log_info "No failures detected"
            ;;
    esac
    
    # 3. Final health check
    verify_system_health
}

# =====================================================
# MAIN SCRIPT LOGIC
# =====================================================

print_usage() {
    cat << EOF
Usage: $0 [command] [options]

Commands:
    backup              Create full system backup
    rollback            Rollback to previous version
    restore <file>      Restore from specific backup file
    verify              Verify system integrity
    emergency-shutdown  Emergency system shutdown
    emergency-recovery  Emergency system recovery
    auto-recover        Attempt automatic recovery
    list-backups        List available backups
    
Options:
    -h, --help         Show this help message
    -v, --verbose      Enable verbose output
    -e, --email        Email address for alerts
    
Examples:
    $0 backup
    $0 rollback
    $0 restore /var/www/backups/backup-20250910_120000.tar.gz
    $0 verify
    $0 emergency-shutdown
    
EOF
}

# Parse command line arguments
COMMAND="${1:-}"
shift || true

case "$COMMAND" in
    backup)
        create_full_backup
        ;;
        
    rollback)
        rollback_to_previous
        ;;
        
    restore)
        if [ -z "${1:-}" ]; then
            log_error "Backup file required for restore"
            exit 1
        fi
        restore_from_backup "$1"
        ;;
        
    verify)
        verify_system_health
        ;;
        
    emergency-shutdown)
        emergency_shutdown
        ;;
        
    emergency-recovery)
        emergency_recovery
        ;;
        
    auto-recover)
        auto_recover
        ;;
        
    list-backups)
        log_info "Available backups:"
        ls -lht "${BACKUP_DIR}"/backup-*.tar.gz* 2>/dev/null | head -20
        ;;
        
    -h|--help|help)
        print_usage
        exit 0
        ;;
        
    *)
        log_error "Unknown command: $COMMAND"
        print_usage
        exit 1
        ;;
esac

# Send completion alert
if [ ! -z "${ALERT_EMAIL:-}" ]; then
    send_alert "Disaster recovery operation completed: $COMMAND" "INFO"
fi

log_info "Operation completed. Log file: $LOG_FILE"