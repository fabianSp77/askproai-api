#!/bin/bash
################################################################################
# Production Deployment Orchestration Script
# Purpose: Automated, safe deployment with validation and rollback
# Usage: ./deploy-production.sh [--skip-backup] [--skip-monitoring]
# Exit Codes: 0=success, 1=pre-check failed, 2=deployment failed, 3=rollback executed
################################################################################

set -euo pipefail

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOG_DIR="/var/www/api-gateway/storage/logs/deployment"
DEPLOY_LOG="${LOG_DIR}/deploy-$(date +%Y%m%d-%H%M%S).log"
BACKUP_DIR="/var/www/api-gateway/storage/backups"

# Migration tables to validate
MIGRATION_TABLES=(
    "policy_configurations"
    "callback_requests"
)

# Parse arguments
SKIP_BACKUP=false
SKIP_MONITORING=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --skip-backup)
            SKIP_BACKUP=true
            shift
            ;;
        --skip-monitoring)
            SKIP_MONITORING=true
            shift
            ;;
        *)
            echo "Unknown option: $1"
            exit 1
            ;;
    esac
done

# State tracking
DEPLOYMENT_STATE="NOT_STARTED"
BACKUP_FILE=""
MIGRATIONS_RUN=false

################################################################################
# Logging Functions
################################################################################

setup_logging() {
    mkdir -p "$LOG_DIR"
    touch "$DEPLOY_LOG"
    echo "╔══════════════════════════════════════════════════════╗" | tee -a "$DEPLOY_LOG"
    echo "║     PRODUCTION DEPLOYMENT - $(date '+%Y-%m-%d')              ║" | tee -a "$DEPLOY_LOG"
    echo "╚══════════════════════════════════════════════════════╝" | tee -a "$DEPLOY_LOG"
    echo "" | tee -a "$DEPLOY_LOG"
}

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$DEPLOY_LOG"
}

log_success() {
    echo -e "${GREEN}✅ [$(date '+%H:%M:%S')] $*${NC}" | tee -a "$DEPLOY_LOG"
}

log_error() {
    echo -e "${RED}❌ [$(date '+%H:%M:%S')] $*${NC}" | tee -a "$DEPLOY_LOG"
}

log_warning() {
    echo -e "${YELLOW}⚠️  [$(date '+%H:%M:%S')] $*${NC}" | tee -a "$DEPLOY_LOG"
}

log_info() {
    echo -e "${BLUE}ℹ️  [$(date '+%H:%M:%S')] $*${NC}" | tee -a "$DEPLOY_LOG"
}

log_step() {
    echo "" | tee -a "$DEPLOY_LOG"
    echo -e "${BOLD}${CYAN}▶ $*${NC}" | tee -a "$DEPLOY_LOG"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" | tee -a "$DEPLOY_LOG"
}

################################################################################
# Deployment Steps
################################################################################

step_pre_deployment_check() {
    log_step "STEP 1: Pre-Deployment Validation"

    if bash "$SCRIPT_DIR/deploy-pre-check.sh" 2>&1 | tee -a "$DEPLOY_LOG"; then
        log_success "Pre-deployment checks passed"
        DEPLOYMENT_STATE="PRE_CHECK_PASSED"
        return 0
    else
        log_error "Pre-deployment checks failed"
        DEPLOYMENT_STATE="PRE_CHECK_FAILED"
        return 1
    fi
}

step_create_backup() {
    log_step "STEP 2: Creating Database Backup"

    if [[ "$SKIP_BACKUP" == true ]]; then
        log_warning "Backup skipped (--skip-backup flag)"
        return 0
    fi

    local backup_timestamp=$(date +%Y%m%d-%H%M%S)
    BACKUP_FILE="${BACKUP_DIR}/pre-deploy-${backup_timestamp}.sql"

    log_info "Creating backup: $BACKUP_FILE"

    if mysqldump -u root askproai_db \
        --single-transaction \
        --quick \
        --lock-tables=false \
        --routines \
        --triggers \
        --events \
        --hex-blob \
        > "$BACKUP_FILE" 2>&1 | tee -a "$DEPLOY_LOG"; then

        # Compress backup
        gzip "$BACKUP_FILE"
        BACKUP_FILE="${BACKUP_FILE}.gz"

        local backup_size=$(du -h "$BACKUP_FILE" | cut -f1)
        log_success "Backup created: $BACKUP_FILE ($backup_size)"
        DEPLOYMENT_STATE="BACKUP_CREATED"
        return 0
    else
        log_error "Backup creation failed"
        return 1
    fi
}

step_enable_maintenance_mode() {
    log_step "STEP 3: Enabling Maintenance Mode"

    if cd /var/www/api-gateway && php artisan down --retry=60 --secret="deploy-$(date +%s)" 2>&1 | tee -a "$DEPLOY_LOG"; then
        log_success "Maintenance mode enabled"
        DEPLOYMENT_STATE="MAINTENANCE_ENABLED"

        # Give users time to finish requests
        log_info "Waiting 10 seconds for active requests to complete..."
        sleep 10

        return 0
    else
        log_error "Failed to enable maintenance mode"
        return 1
    fi
}

step_run_migrations() {
    log_step "STEP 4: Running Database Migrations"

    log_info "Running migrations one by one with validation..."

    # Get list of pending migrations
    local pending_migrations=$(cd /var/www/api-gateway && php artisan migrate:status --pending 2>/dev/null | grep -c "Pending" || echo "0")

    log_info "Pending migrations: $pending_migrations"

    # Run migrations one at a time
    if cd /var/www/api-gateway && php artisan migrate --force --step=7 2>&1 | tee -a "$DEPLOY_LOG"; then
        log_success "Migrations executed successfully"
        MIGRATIONS_RUN=true
        DEPLOYMENT_STATE="MIGRATIONS_RUN"
    else
        log_error "Migration execution failed"
        MIGRATIONS_RUN=true
        return 1
    fi

    # Validate each migration table
    log_info "Validating migration tables..."

    for table in "${MIGRATION_TABLES[@]}"; do
        log_info "Validating table: $table"

        if bash "$SCRIPT_DIR/validate-migration.sh" "$table" 2>&1 | tee -a "$DEPLOY_LOG"; then
            log_success "Table validation passed: $table"
        else
            log_error "Table validation failed: $table"
            return 1
        fi
    done

    return 0
}

step_clear_caches() {
    log_step "STEP 5: Clearing Application Caches"

    local cache_commands=(
        "cache:clear"
        "config:clear"
        "route:clear"
        "view:clear"
        "event:clear"
    )

    for cmd in "${cache_commands[@]}"; do
        if cd /var/www/api-gateway && php artisan "$cmd" 2>&1 | tee -a "$DEPLOY_LOG" >/dev/null; then
            log_success "Cache cleared: $cmd"
        else
            log_warning "Failed to clear: $cmd"
        fi
    done

    # Clear Redis
    if php -r "
        require '/var/www/api-gateway/vendor/autoload.php';
        \$app = require_once '/var/www/api-gateway/bootstrap/app.php';
        \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        Redis::connection()->flushall();
    " 2>&1 | tee -a "$DEPLOY_LOG" >/dev/null; then
        log_success "Redis cache cleared"
    else
        log_warning "Failed to clear Redis cache"
    fi

    # Restart PHP-FPM
    if systemctl restart php8.3-fpm 2>&1 | tee -a "$DEPLOY_LOG"; then
        log_success "PHP-FPM restarted"
    else
        log_warning "Failed to restart PHP-FPM"
    fi

    DEPLOYMENT_STATE="CACHES_CLEARED"
    return 0
}

step_run_smoke_tests() {
    log_step "STEP 6: Running Smoke Tests"

    if bash "$SCRIPT_DIR/smoke-test.sh" 2>&1 | tee -a "$DEPLOY_LOG"; then
        log_success "Smoke tests passed - deployment verified"
        DEPLOYMENT_STATE="SMOKE_TESTS_PASSED"
        return 0
    else
        local exit_code=$?

        if [[ $exit_code -eq 1 ]]; then
            log_warning "Smoke tests passed with warnings (YELLOW status)"
            DEPLOYMENT_STATE="SMOKE_TESTS_WARNING"
            return 0
        else
            log_error "Smoke tests failed (RED status)"
            DEPLOYMENT_STATE="SMOKE_TESTS_FAILED"
            return 1
        fi
    fi
}

step_disable_maintenance_mode() {
    log_step "STEP 7: Disabling Maintenance Mode"

    if cd /var/www/api-gateway && php artisan up 2>&1 | tee -a "$DEPLOY_LOG"; then
        log_success "Maintenance mode disabled - site is LIVE"
        DEPLOYMENT_STATE="DEPLOYMENT_COMPLETE"
        return 0
    else
        log_error "Failed to disable maintenance mode"
        return 1
    fi
}

step_start_monitoring() {
    log_step "STEP 8: Starting Post-Deployment Monitoring"

    if [[ "$SKIP_MONITORING" == true ]]; then
        log_warning "Monitoring skipped (--skip-monitoring flag)"
        return 0
    fi

    log_info "Starting 3-hour monitoring window in background..."

    # Start monitoring in background
    nohup bash "$SCRIPT_DIR/monitor-deployment.sh" 180 > "${LOG_DIR}/monitor-background-$(date +%Y%m%d-%H%M%S).log" 2>&1 &
    local monitor_pid=$!

    log_success "Monitoring started (PID: $monitor_pid)"
    log_info "Monitor logs: ${LOG_DIR}/monitor-background-*.log"
    log_info "To stop monitoring: kill $monitor_pid"

    return 0
}

################################################################################
# Rollback Handling
################################################################################

trigger_rollback() {
    log_error "Deployment failed at state: $DEPLOYMENT_STATE"
    log_warning "Initiating automatic rollback..."

    if [[ "$MIGRATIONS_RUN" == true ]]; then
        log_info "Migrations were executed - running full rollback"

        if [[ -n "$BACKUP_FILE" ]] && [[ -f "$BACKUP_FILE" ]]; then
            bash "$SCRIPT_DIR/emergency-rollback.sh" --auto --backup-file="$BACKUP_FILE" 2>&1 | tee -a "$DEPLOY_LOG"
        else
            bash "$SCRIPT_DIR/emergency-rollback.sh" --auto 2>&1 | tee -a "$DEPLOY_LOG"
        fi
    else
        log_info "Migrations were not run - disabling maintenance mode only"
        cd /var/www/api-gateway && php artisan up 2>&1 | tee -a "$DEPLOY_LOG" || true
    fi

    DEPLOYMENT_STATE="ROLLED_BACK"
}

################################################################################
# Summary Report
################################################################################

print_summary() {
    echo "" | tee -a "$DEPLOY_LOG"
    echo "╔══════════════════════════════════════════════════════╗" | tee -a "$DEPLOY_LOG"
    echo "║           DEPLOYMENT SUMMARY                         ║" | tee -a "$DEPLOY_LOG"
    echo "╚══════════════════════════════════════════════════════╝" | tee -a "$DEPLOY_LOG"
    echo "" | tee -a "$DEPLOY_LOG"
    echo "Final State: $DEPLOYMENT_STATE" | tee -a "$DEPLOY_LOG"
    echo "Timestamp: $(date)" | tee -a "$DEPLOY_LOG"

    if [[ -n "$BACKUP_FILE" ]]; then
        echo "Backup: $BACKUP_FILE" | tee -a "$DEPLOY_LOG"
    fi

    echo "Log File: $DEPLOY_LOG" | tee -a "$DEPLOY_LOG"
    echo "" | tee -a "$DEPLOY_LOG"

    case $DEPLOYMENT_STATE in
        "DEPLOYMENT_COMPLETE"|"SMOKE_TESTS_WARNING")
            echo -e "${GREEN}${BOLD}✅ DEPLOYMENT SUCCESSFUL${NC}" | tee -a "$DEPLOY_LOG"
            echo "" | tee -a "$DEPLOY_LOG"
            echo "Next Steps:" | tee -a "$DEPLOY_LOG"
            echo "  1. Monitor application for 3 hours" | tee -a "$DEPLOY_LOG"
            echo "  2. Watch monitoring logs in: ${LOG_DIR}/" | tee -a "$DEPLOY_LOG"
            echo "  3. Check error logs: tail -f /var/www/api-gateway/storage/logs/laravel.log" | tee -a "$DEPLOY_LOG"
            echo "  4. Keep backup for 7 days: $BACKUP_FILE" | tee -a "$DEPLOY_LOG"
            ;;

        "ROLLED_BACK")
            echo -e "${RED}${BOLD}❌ DEPLOYMENT FAILED - ROLLED BACK${NC}" | tee -a "$DEPLOY_LOG"
            echo "" | tee -a "$DEPLOY_LOG"
            echo "Urgent Actions:" | tee -a "$DEPLOY_LOG"
            echo "  1. Review deployment log: $DEPLOY_LOG" | tee -a "$DEPLOY_LOG"
            echo "  2. Review rollback log: ${LOG_DIR}/rollback-*.log" | tee -a "$DEPLOY_LOG"
            echo "  3. Verify site functionality" | tee -a "$DEPLOY_LOG"
            echo "  4. Investigate root cause before retry" | tee -a "$DEPLOY_LOG"
            ;;

        "PRE_CHECK_FAILED")
            echo -e "${YELLOW}${BOLD}⚠️  DEPLOYMENT ABORTED - PRE-CHECK FAILED${NC}" | tee -a "$DEPLOY_LOG"
            echo "" | tee -a "$DEPLOY_LOG"
            echo "Actions Required:" | tee -a "$DEPLOY_LOG"
            echo "  1. Review pre-check log: ${LOG_DIR}/pre-check-*.log" | tee -a "$DEPLOY_LOG"
            echo "  2. Fix identified issues" | tee -a "$DEPLOY_LOG"
            echo "  3. Re-run deployment when ready" | tee -a "$DEPLOY_LOG"
            ;;

        *)
            echo -e "${RED}${BOLD}❌ DEPLOYMENT FAILED${NC}" | tee -a "$DEPLOY_LOG"
            echo "" | tee -a "$DEPLOY_LOG"
            echo "Manual intervention may be required" | tee -a "$DEPLOY_LOG"
            ;;
    esac

    echo "" | tee -a "$DEPLOY_LOG"
}

################################################################################
# Main Execution
################################################################################

main() {
    setup_logging

    log_info "Starting production deployment..."
    log_info "Options: SKIP_BACKUP=$SKIP_BACKUP, SKIP_MONITORING=$SKIP_MONITORING"
    echo ""

    # Pre-deployment check
    if ! step_pre_deployment_check; then
        print_summary
        exit 1
    fi

    # Create backup
    if ! step_create_backup; then
        log_error "Backup creation failed - aborting deployment"
        print_summary
        exit 1
    fi

    # Enable maintenance mode
    if ! step_enable_maintenance_mode; then
        log_error "Cannot enable maintenance mode - aborting"
        print_summary
        exit 1
    fi

    # Run migrations with validation
    if ! step_run_migrations; then
        log_error "Migration or validation failed - triggering rollback"
        trigger_rollback
        print_summary
        exit 2
    fi

    # Clear caches
    step_clear_caches

    # Run smoke tests
    if ! step_run_smoke_tests; then
        log_error "Smoke tests failed - triggering rollback"
        trigger_rollback
        print_summary
        exit 2
    fi

    # Disable maintenance mode
    if ! step_disable_maintenance_mode; then
        log_error "Failed to disable maintenance mode - manual intervention required"
        print_summary
        exit 1
    fi

    # Start monitoring
    step_start_monitoring

    # Success!
    print_summary

    echo ""
    log_success "Deployment completed successfully!"
    echo ""

    exit 0
}

# Trap errors for automatic rollback
trap 'trigger_rollback; print_summary; exit 3' ERR

main "$@"
