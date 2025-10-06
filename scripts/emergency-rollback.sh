#!/bin/bash
################################################################################
# Emergency Rollback Script
# Purpose: Automated rollback for failed production deployment
# Usage: ./emergency-rollback.sh [--auto] [--backup-file=/path/to/backup.sql]
# Exit Codes: 0=rollback successful, 1=rollback failed
################################################################################

set -euo pipefail

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
BOLD='\033[1m'
NC='\033[0m'

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOG_DIR="/var/www/api-gateway/storage/logs/deployment"
ROLLBACK_LOG="${LOG_DIR}/rollback-$(date +%Y%m%d-%H%M%S).log"
BACKUP_DIR="/var/www/api-gateway/storage/backups"
MAINTENANCE_FILE="/var/www/api-gateway/storage/framework/down"

# Parse arguments
AUTO_MODE=false
BACKUP_FILE=""

while [[ $# -gt 0 ]]; do
    case $1 in
        --auto)
            AUTO_MODE=true
            shift
            ;;
        --backup-file=*)
            BACKUP_FILE="${1#*=}"
            shift
            ;;
        *)
            echo "Unknown option: $1"
            exit 1
            ;;
    esac
done

# Steps tracking
STEPS_COMPLETED=0
STEPS_FAILED=0

################################################################################
# Logging Functions
################################################################################

setup_logging() {
    mkdir -p "$LOG_DIR"
    touch "$ROLLBACK_LOG"
    echo "==========================================" | tee -a "$ROLLBACK_LOG"
    echo "EMERGENCY ROLLBACK - $(date)" | tee -a "$ROLLBACK_LOG"
    echo "==========================================" | tee -a "$ROLLBACK_LOG"
}

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$ROLLBACK_LOG"
}

log_success() {
    echo -e "${GREEN}✅ [$(date '+%H:%M:%S')] $*${NC}" | tee -a "$ROLLBACK_LOG"
    ((STEPS_COMPLETED++))
}

log_error() {
    echo -e "${RED}❌ [$(date '+%H:%M:%S')] $*${NC}" | tee -a "$ROLLBACK_LOG"
    ((STEPS_FAILED++))
}

log_warning() {
    echo -e "${YELLOW}⚠️  [$(date '+%H:%M:%S')] $*${NC}" | tee -a "$ROLLBACK_LOG"
}

log_info() {
    echo -e "${BLUE}ℹ️  [$(date '+%H:%M:%S')] $*${NC}" | tee -a "$ROLLBACK_LOG"
}

log_critical() {
    echo -e "${RED}${BOLD}🚨 [$(date '+%H:%M:%S')] CRITICAL: $*${NC}" | tee -a "$ROLLBACK_LOG"
}

################################################################################
# Confirmation Functions
################################################################################

confirm_rollback() {
    if [[ "$AUTO_MODE" == true ]]; then
        log_warning "Auto mode enabled - proceeding without confirmation"
        return 0
    fi

    echo ""
    echo -e "${RED}${BOLD}╔════════════════════════════════════════════════╗${NC}"
    echo -e "${RED}${BOLD}║     ⚠️  EMERGENCY ROLLBACK INITIATED  ⚠️       ║${NC}"
    echo -e "${RED}${BOLD}╚════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "${YELLOW}This will:${NC}"
    echo "  1. Put site in maintenance mode"
    echo "  2. Rollback last 7 migrations"
    echo "  3. Restore database from backup"
    echo "  4. Clear all caches"
    echo "  5. Restart services"
    echo ""
    echo -e "${RED}${BOLD}This action CANNOT be undone!${NC}"
    echo ""

    read -p "Are you sure you want to continue? (type 'ROLLBACK' to confirm): " confirmation

    if [[ "$confirmation" != "ROLLBACK" ]]; then
        log "Rollback cancelled by user"
        exit 0
    fi

    log "Rollback confirmed by user"
}

################################################################################
# Rollback Steps
################################################################################

step_enable_maintenance_mode() {
    log_info "Step 1: Enabling maintenance mode..."

    if cd /var/www/api-gateway && php artisan down --retry=60 --secret="emergency-rollback-$(date +%s)" 2>&1 | tee -a "$ROLLBACK_LOG"; then
        log_success "Maintenance mode enabled"

        # Verify maintenance mode
        if [[ -f "$MAINTENANCE_FILE" ]]; then
            log_success "Maintenance file created: $MAINTENANCE_FILE"
        else
            log_warning "Maintenance file not found, but command succeeded"
        fi

        return 0
    else
        log_error "Failed to enable maintenance mode"
        return 1
    fi
}

step_create_emergency_backup() {
    log_info "Step 2: Creating emergency pre-rollback backup..."

    local emergency_backup="${BACKUP_DIR}/emergency-pre-rollback-$(date +%Y%m%d-%H%M%S).sql"

    if mysqldump -u root askproai_db \
        --single-transaction \
        --quick \
        --lock-tables=false \
        --routines \
        --triggers \
        --events \
        > "$emergency_backup" 2>&1 | tee -a "$ROLLBACK_LOG"; then

        local backup_size=$(du -h "$emergency_backup" | cut -f1)
        log_success "Emergency backup created: $emergency_backup ($backup_size)"
        return 0
    else
        log_error "Failed to create emergency backup"
        return 1
    fi
}

step_rollback_migrations() {
    log_info "Step 3: Rolling back migrations (last 7 steps)..."

    if cd /var/www/api-gateway && php artisan migrate:rollback --step=7 2>&1 | tee -a "$ROLLBACK_LOG"; then
        log_success "Migrations rolled back successfully"

        # Verify tables removed
        local policy_exists=$(php -r "
            require '/var/www/api-gateway/vendor/autoload.php';
            \$app = require_once '/var/www/api-gateway/bootstrap/app.php';
            \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
            echo DB::getSchemaBuilder()->hasTable('policy_configurations') ? 'yes' : 'no';
        " 2>/dev/null)

        if [[ "$policy_exists" == "no" ]]; then
            log_success "Verified: Migration tables removed"
        else
            log_warning "Migration tables still exist (may be expected)"
        fi

        return 0
    else
        log_error "Migration rollback failed"
        return 1
    fi
}

step_restore_from_backup() {
    log_info "Step 4: Restoring database from backup..."

    # Determine which backup to use
    local backup_to_restore=""

    if [[ -n "$BACKUP_FILE" ]]; then
        if [[ -f "$BACKUP_FILE" ]]; then
            backup_to_restore="$BACKUP_FILE"
            log_info "Using specified backup: $BACKUP_FILE"
        else
            log_error "Specified backup file not found: $BACKUP_FILE"
            return 1
        fi
    else
        # Find latest backup before deployment
        backup_to_restore=$(find "$BACKUP_DIR" -name "*.sql" -type f ! -name "emergency-*" -printf '%T@ %p\n' | sort -n | tail -1 | cut -d' ' -f2-)

        if [[ -z "$backup_to_restore" ]]; then
            log_error "No backup file found in $BACKUP_DIR"
            return 1
        fi

        log_info "Using latest backup: $backup_to_restore"
    fi

    # Restore backup
    if mysql -u root askproai_db < "$backup_to_restore" 2>&1 | tee -a "$ROLLBACK_LOG"; then
        log_success "Database restored from backup"
        return 0
    else
        log_error "Failed to restore database from backup"
        return 1
    fi
}

step_clear_all_caches() {
    log_info "Step 5: Clearing all caches..."

    local cache_commands=(
        "cache:clear"
        "config:clear"
        "route:clear"
        "view:clear"
        "event:clear"
    )

    local all_cleared=true

    for cmd in "${cache_commands[@]}"; do
        if cd /var/www/api-gateway && php artisan "$cmd" 2>&1 | tee -a "$ROLLBACK_LOG" >/dev/null; then
            log_success "Cleared: $cmd"
        else
            log_warning "Failed to clear: $cmd"
            all_cleared=false
        fi
    done

    # Clear Redis cache
    if php -r "
        require '/var/www/api-gateway/vendor/autoload.php';
        \$app = require_once '/var/www/api-gateway/bootstrap/app.php';
        \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        try {
            Redis::connection()->flushall();
            echo 'OK';
        } catch (Exception \$e) {
            echo 'FAILED';
            exit(1);
        }
    " 2>&1 | tee -a "$ROLLBACK_LOG" | grep -q "OK"; then
        log_success "Redis cache cleared"
    else
        log_warning "Failed to clear Redis cache (non-critical)"
    fi

    # Clear PHP-FPM cache
    if systemctl restart php8.3-fpm 2>&1 | tee -a "$ROLLBACK_LOG"; then
        log_success "PHP-FPM restarted"
    else
        log_warning "Failed to restart PHP-FPM"
    fi

    if $all_cleared; then
        return 0
    else
        return 1
    fi
}

step_verify_rollback() {
    log_info "Step 6: Verifying rollback success..."

    # Check database connection
    if ! php -r "
        require '/var/www/api-gateway/vendor/autoload.php';
        \$app = require_once '/var/www/api-gateway/bootstrap/app.php';
        \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        DB::connection()->getPdo();
    " 2>/dev/null; then
        log_error "Database connection failed"
        return 1
    fi
    log_success "Database connection verified"

    # Check migration tables removed
    local tables_removed=$(php -r "
        require '/var/www/api-gateway/vendor/autoload.php';
        \$app = require_once '/var/www/api-gateway/bootstrap/app.php';
        \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        \$policy = DB::getSchemaBuilder()->hasTable('policy_configurations') ? 'exists' : 'removed';
        \$callback = DB::getSchemaBuilder()->hasTable('callback_requests') ? 'exists' : 'removed';
        echo \"policy:\$policy,callback:\$callback\";
    " 2>/dev/null)

    log_info "Table status: $tables_removed"

    # Check required tables exist
    local required_tables=("companies" "branches" "services" "staff" "appointments" "customers")
    local all_exist=true

    for table in "${required_tables[@]}"; do
        local exists=$(php -r "
            require '/var/www/api-gateway/vendor/autoload.php';
            \$app = require_once '/var/www/api-gateway/bootstrap/app.php';
            \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
            echo DB::getSchemaBuilder()->hasTable('$table') ? 'yes' : 'no';
        " 2>/dev/null)

        if [[ "$exists" == "yes" ]]; then
            log_success "Required table exists: $table"
        else
            log_error "Required table missing: $table"
            all_exist=false
        fi
    done

    if $all_exist; then
        log_success "All required tables verified"
        return 0
    else
        log_error "Some required tables missing"
        return 1
    fi
}

step_disable_maintenance_mode() {
    log_info "Step 7: Disabling maintenance mode..."

    if cd /var/www/api-gateway && php artisan up 2>&1 | tee -a "$ROLLBACK_LOG"; then
        log_success "Maintenance mode disabled"

        # Verify site is up
        sleep 2
        if [[ ! -f "$MAINTENANCE_FILE" ]]; then
            log_success "Site is now live"
        else
            log_warning "Maintenance file still exists"
        fi

        return 0
    else
        log_error "Failed to disable maintenance mode"
        return 1
    fi
}

step_run_post_rollback_checks() {
    log_info "Step 8: Running post-rollback health checks..."

    local health_status=$(php -r "
        require '/var/www/api-gateway/vendor/autoload.php';
        \$app = require_once '/var/www/api-gateway/bootstrap/app.php';
        \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        try {
            // Test database
            DB::connection()->getPdo();

            // Test basic query
            \$companies = DB::table('companies')->count();

            // Test cache
            Cache::put('rollback_check', 'ok', 5);
            \$cached = Cache::get('rollback_check');
            if (\$cached !== 'ok') throw new Exception('Cache check failed');

            echo 'OK';
        } catch (Exception \$e) {
            echo 'FAILED: ' . \$e->getMessage();
            exit(1);
        }
    " 2>&1)

    if [[ "$health_status" == "OK" ]]; then
        log_success "Post-rollback health checks passed"
        return 0
    else
        log_error "Post-rollback health checks failed: $health_status"
        return 1
    fi
}

################################################################################
# Summary Report
################################################################################

print_summary() {
    echo ""
    echo "==========================================" | tee -a "$ROLLBACK_LOG"
    echo "ROLLBACK SUMMARY" | tee -a "$ROLLBACK_LOG"
    echo "==========================================" | tee -a "$ROLLBACK_LOG"
    log_success "Steps completed: $STEPS_COMPLETED"
    log_error "Steps failed: $STEPS_FAILED"
    echo "==========================================" | tee -a "$ROLLBACK_LOG"
    echo "Log file: $ROLLBACK_LOG" | tee -a "$ROLLBACK_LOG"
    echo ""

    if [[ $STEPS_FAILED -eq 0 ]]; then
        echo -e "${GREEN}${BOLD}✅ ROLLBACK COMPLETED SUCCESSFULLY${NC}" | tee -a "$ROLLBACK_LOG"
        echo "" | tee -a "$ROLLBACK_LOG"
        echo "Next steps:" | tee -a "$ROLLBACK_LOG"
        echo "  1. Verify application functionality" | tee -a "$ROLLBACK_LOG"
        echo "  2. Review error logs to determine root cause" | tee -a "$ROLLBACK_LOG"
        echo "  3. Plan remediation before next deployment attempt" | tee -a "$ROLLBACK_LOG"
        return 0
    else
        echo -e "${RED}${BOLD}❌ ROLLBACK COMPLETED WITH ERRORS${NC}" | tee -a "$ROLLBACK_LOG"
        echo "" | tee -a "$ROLLBACK_LOG"
        echo "URGENT ACTIONS REQUIRED:" | tee -a "$ROLLBACK_LOG"
        echo "  1. Site may be in inconsistent state" | tee -a "$ROLLBACK_LOG"
        echo "  2. Review rollback log: $ROLLBACK_LOG" | tee -a "$ROLLBACK_LOG"
        echo "  3. Consider manual database restore if needed" | tee -a "$ROLLBACK_LOG"
        echo "  4. Contact database administrator immediately" | tee -a "$ROLLBACK_LOG"
        return 1
    fi
}

################################################################################
# Main Execution
################################################################################

main() {
    setup_logging

    # Display warning and get confirmation
    confirm_rollback

    log_info "Starting emergency rollback procedure..."
    echo ""

    # Execute rollback steps
    step_enable_maintenance_mode || log_critical "Failed to enable maintenance mode (continuing anyway)"
    step_create_emergency_backup || log_critical "Failed to create emergency backup (continuing anyway)"

    # Critical steps - stop if these fail
    if ! step_rollback_migrations; then
        log_critical "Migration rollback failed - attempting database restore"
    fi

    if ! step_restore_from_backup; then
        log_critical "Database restore failed - MANUAL INTERVENTION REQUIRED"
        print_summary
        exit 1
    fi

    step_clear_all_caches || log_warning "Cache clear incomplete"
    step_verify_rollback || log_error "Rollback verification failed"
    step_disable_maintenance_mode || log_critical "Failed to disable maintenance mode"
    step_run_post_rollback_checks || log_warning "Post-rollback checks failed"

    # Final summary
    print_summary

    if [[ $STEPS_FAILED -eq 0 ]]; then
        exit 0
    else
        exit 1
    fi
}

main "$@"
