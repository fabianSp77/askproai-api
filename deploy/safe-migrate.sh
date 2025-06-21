#!/bin/bash

# AskProAI Safe Database Migration Script
# Version: 1.0.0
# Description: Performs database migrations with safety checks and rollback capability

set -euo pipefail
IFS=$'\n\t'

# Configuration
readonly APP_DIR="${APP_DIR:-/var/www/api-gateway}"
readonly BACKUP_DIR="${BACKUP_DIR:-/var/backups/askproai/migrations}"
readonly LOG_FILE="/var/log/askproai/migration-$(date +%Y%m%d_%H%M%S).log"
readonly LOCK_FILE="/var/run/askproai-migration.lock"

# Parse arguments
DRY_RUN=false
FORCE=false
BATCH_SIZE=1000
TIMEOUT=600

while [[ $# -gt 0 ]]; do
    case $1 in
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        --force)
            FORCE=true
            shift
            ;;
        --batch-size=*)
            BATCH_SIZE="${1#*=}"
            shift
            ;;
        --timeout=*)
            TIMEOUT="${1#*=}"
            shift
            ;;
        *)
            echo "Unknown option: $1"
            echo "Usage: $0 [--dry-run] [--force] [--batch-size=N] [--timeout=N]"
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

# Ensure directories exist
mkdir -p "$(dirname "$LOG_FILE")"
mkdir -p "$BACKUP_DIR"

# Logging
log() {
    local message="[$(date '+%Y-%m-%d %H:%M:%S')] $*"
    echo "$message" | tee -a "$LOG_FILE"
}

log_info() {
    echo -e "${GREEN}[INFO]${NC} $*" >&2
    log "INFO: $*"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $*" >&2
    log "WARN: $*"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $*" >&2
    log "ERROR: $*"
}

log_section() {
    echo -e "\n${BLUE}=== $* ===${NC}" >&2
    log "SECTION: $*"
}

# Lock handling
acquire_lock() {
    if [ -f "$LOCK_FILE" ]; then
        log_error "Another migration is already in progress"
        exit 1
    fi
    
    echo $$ > "$LOCK_FILE"
    trap "rm -f $LOCK_FILE" EXIT INT TERM
}

# Check prerequisites
check_prerequisites() {
    log_section "Checking Prerequisites"
    
    cd "$APP_DIR"
    
    # Check if Laravel app
    if [ ! -f "artisan" ]; then
        log_error "Not a Laravel application directory"
        exit 1
    fi
    
    # Check database connection
    if ! php artisan db:show &>/dev/null; then
        log_error "Cannot connect to database"
        exit 1
    fi
    
    # Check for required tools
    if ! command -v pt-online-schema-change &>/dev/null; then
        log_warn "pt-online-schema-change not found - will use standard migrations"
    fi
    
    log_info "Prerequisites check passed"
}

# Analyze pending migrations
analyze_migrations() {
    log_section "Analyzing Pending Migrations"
    
    cd "$APP_DIR"
    
    # Get pending migrations
    local pending_migrations=$(php artisan migrate:status | grep "Pending" | awk '{print $4}' || true)
    
    if [ -z "$pending_migrations" ]; then
        log_info "No pending migrations found"
        exit 0
    fi
    
    echo -e "\n${YELLOW}Pending migrations:${NC}"
    echo "$pending_migrations" | nl
    
    # Count pending migrations
    local count=$(echo "$pending_migrations" | wc -l)
    log_info "Found $count pending migrations"
    
    # Analyze each migration
    echo -e "\n${YELLOW}Migration analysis:${NC}"
    
    while IFS= read -r migration; do
        local migration_file="database/migrations/${migration}.php"
        
        if [ -f "$migration_file" ]; then
            echo -e "\n${BLUE}$migration:${NC}"
            
            # Check for dangerous operations
            if grep -q "dropColumn\|drop\|truncate" "$migration_file"; then
                echo -e "  ${RED}⚠ Contains destructive operations${NC}"
            fi
            
            if grep -q "alter.*table\|modify.*column" "$migration_file"; then
                echo -e "  ${YELLOW}⚠ Contains schema changes${NC}"
            fi
            
            if grep -q "DB::statement\|DB::raw" "$migration_file"; then
                echo -e "  ${YELLOW}⚠ Contains raw SQL${NC}"
            fi
            
            # Estimate impact
            local tables=$(grep -oP "Schema::(create|table)\('\K[^']+'" "$migration_file" | sort -u || true)
            if [ -n "$tables" ]; then
                echo "  Affected tables: $tables"
                
                # Get row counts
                while IFS= read -r table; do
                    local count=$(mysql -NBe "SELECT COUNT(*) FROM $table" 2>/dev/null || echo "0")
                    echo "    - $table: $count rows"
                done <<< "$tables"
            fi
        fi
    done <<< "$pending_migrations"
    
    if [ "$DRY_RUN" == "true" ]; then
        log_info "Dry run mode - no changes will be made"
        exit 0
    fi
    
    if [ "$FORCE" != "true" ]; then
        echo -e "\n${YELLOW}Proceed with migrations? (yes/no):${NC} "
        read -r confirm
        if [ "$confirm" != "yes" ]; then
            log_info "Migration cancelled by user"
            exit 0
        fi
    fi
}

# Create pre-migration backup
create_backup() {
    log_section "Creating Pre-Migration Backup"
    
    local backup_name="pre_migration_$(date +%Y%m%d_%H%M%S)"
    local backup_path="$BACKUP_DIR/$backup_name"
    
    mkdir -p "$backup_path"
    
    # Get database credentials
    source "$APP_DIR/.env"
    
    # Get list of affected tables
    local tables=$(php artisan migrate:status | grep "Pending" | while read -r line; do
        migration=$(echo "$line" | awk '{print $4}')
        migration_file="database/migrations/${migration}.php"
        if [ -f "$migration_file" ]; then
            grep -oP "Schema::(create|table)\('\K[^']+'" "$migration_file" 2>/dev/null || true
        fi
    done | sort -u)
    
    if [ -n "$tables" ]; then
        log_info "Backing up affected tables..."
        
        # Backup each table
        while IFS= read -r table; do
            log_info "Backing up table: $table"
            
            mysqldump \
                --single-transaction \
                --routines \
                --triggers \
                -h"$DB_HOST" \
                -u"$DB_USERNAME" \
                -p"$DB_PASSWORD" \
                "$DB_DATABASE" "$table" \
                | gzip > "$backup_path/${table}.sql.gz" || {
                    log_error "Failed to backup table: $table"
                    exit 1
                }
        done <<< "$tables"
    else
        log_info "No specific tables to backup - creating full backup"
        
        mysqldump \
            --single-transaction \
            --routines \
            --triggers \
            -h"$DB_HOST" \
            -u"$DB_USERNAME" \
            -p"$DB_PASSWORD" \
            "$DB_DATABASE" \
            | gzip > "$backup_path/full_backup.sql.gz" || {
                log_error "Failed to create backup"
                exit 1
            }
    fi
    
    # Save migration state
    php artisan migrate:status > "$backup_path/migration_status.txt"
    
    log_info "Backup created: $backup_name"
}

# Execute migrations with monitoring
execute_migrations() {
    log_section "Executing Migrations"
    
    cd "$APP_DIR"
    
    # Create migration command with timeout
    local migration_cmd="timeout $TIMEOUT php artisan migrate --force"
    
    # Add batch size if supported
    if php artisan help migrate | grep -q "batch-size"; then
        migration_cmd="$migration_cmd --batch-size=$BATCH_SIZE"
    fi
    
    # Start migration with progress monitoring
    log_info "Starting migrations..."
    
    # Execute with real-time output
    if $migration_cmd 2>&1 | tee -a "$LOG_FILE"; then
        log_info "Migrations completed successfully"
    else
        log_error "Migration failed!"
        
        # Attempt to get more info about the failure
        php artisan migrate:status | tail -20 >> "$LOG_FILE"
        
        return 1
    fi
}

# Run post-migration checks
post_migration_checks() {
    log_section "Post-Migration Verification"
    
    cd "$APP_DIR"
    
    # Check migration status
    log_info "Checking migration status..."
    
    local pending=$(php artisan migrate:status | grep -c "Pending" || echo 0)
    
    if [ "$pending" -gt 0 ]; then
        log_error "Still have $pending pending migrations!"
        return 1
    fi
    
    # Test database connection
    if ! php artisan db:show &>/dev/null; then
        log_error "Database connection test failed!"
        return 1
    fi
    
    # Run basic application checks
    log_info "Running application checks..."
    
    # Check if models can be loaded
    if ! php artisan tinker --execute="App\Models\User::count();" &>/dev/null; then
        log_warn "Model check failed - this might be expected for new installations"
    fi
    
    # Clear caches
    php artisan cache:clear || true
    php artisan config:clear || true
    
    log_info "Post-migration checks completed"
}

# Rollback function
rollback_migrations() {
    log_error "Attempting to rollback migrations..."
    
    cd "$APP_DIR"
    
    # Try to rollback last batch
    if php artisan migrate:rollback --force; then
        log_info "Rollback completed"
    else
        log_error "Automatic rollback failed!"
        log_error "Manual intervention required - check backups in: $BACKUP_DIR"
    fi
}

# Performance optimization for large migrations
optimize_for_migration() {
    log_section "Optimizing Database for Migration"
    
    # Temporarily adjust MySQL settings for better migration performance
    mysql -h"$DB_HOST" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "
        SET GLOBAL innodb_flush_log_at_trx_commit = 2;
        SET GLOBAL sync_binlog = 0;
        SET GLOBAL innodb_buffer_pool_size = 2147483648;
    " 2>/dev/null || log_warn "Could not optimize MySQL settings"
    
    # Disable foreign key checks for migration (will be re-enabled after)
    export MIGRATION_DISABLE_FK_CHECKS=1
}

# Restore normal settings
restore_settings() {
    log_info "Restoring normal database settings..."
    
    mysql -h"$DB_HOST" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "
        SET GLOBAL innodb_flush_log_at_trx_commit = 1;
        SET GLOBAL sync_binlog = 1;
    " 2>/dev/null || true
    
    unset MIGRATION_DISABLE_FK_CHECKS
}

# Main execution
main() {
    log_info "Starting safe migration process"
    log_info "Dry run: $DRY_RUN"
    log_info "Force: $FORCE"
    log_info "Batch size: $BATCH_SIZE"
    log_info "Timeout: ${TIMEOUT}s"
    
    # Acquire lock
    acquire_lock
    
    # Set up error handling
    trap 'rollback_migrations; restore_settings; exit 1' ERR
    
    # Execute migration steps
    check_prerequisites
    analyze_migrations
    
    if [ "$DRY_RUN" != "true" ]; then
        create_backup
        optimize_for_migration
        
        if execute_migrations; then
            post_migration_checks
            restore_settings
            
            log_info "Migration completed successfully!"
            
            # Clean up old backups (keep last 10)
            cd "$BACKUP_DIR"
            ls -t | tail -n +11 | xargs -r rm -rf
        else
            log_error "Migration failed!"
            rollback_migrations
            restore_settings
            exit 1
        fi
    fi
    
    # Show summary
    echo -e "\n${GREEN}=== Migration Summary ===${NC}"
    echo "Status: Success"
    echo "Log file: $LOG_FILE"
    echo "Backup location: $BACKUP_DIR"
    echo -e "${GREEN}========================${NC}\n"
}

# Run main function
main "$@"