#!/bin/bash

# SuperClaude Backup Orchestrator
# Version: 2.0.0
# Enhanced backup system with self-healing and intelligent monitoring
# Integrates SuperClaude framework features for robust operation

set -euo pipefail

# =============================================================================
# CONFIGURATION & INITIALIZATION
# =============================================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TIMESTAMP=$(date +%Y-%m-%d_%H-%M-%S)
START_TIME=$(date +%s)

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Load secure credentials
BACKUP_CONFIG="${SCRIPT_DIR}/../.env.backup"
if [ -f "$BACKUP_CONFIG" ]; then
    export $(grep -v '^#' "$BACKUP_CONFIG" | xargs)
else
    echo -e "${RED}✗ Critical: .env.backup not found!${NC}"
    exit 1
fi

# Backup directories
BACKUP_DIR="${BACKUP_BASE_DIR:-/var/backups/askproai}"
DB_BACKUP_DIR="$BACKUP_DIR/db"
FILES_BACKUP_DIR="$BACKUP_DIR/files"
CONFIG_BACKUP_DIR="$BACKUP_DIR/config"
LOGS_DIR="$BACKUP_DIR/logs"
STATE_FILE="$BACKUP_DIR/.orchestrator_state"
LOG_FILE="$LOGS_DIR/orchestrator_$TIMESTAMP.log"

# Create necessary directories
mkdir -p "$DB_BACKUP_DIR" "$FILES_BACKUP_DIR" "$CONFIG_BACKUP_DIR" "$LOGS_DIR"

# =============================================================================
# TASK MANAGEMENT (SuperClaude TodoWrite-style)
# =============================================================================

declare -A TASKS
declare -A TASK_STATUS
declare -A TASK_START_TIME
declare -A TASK_END_TIME
declare -A TASK_ERRORS

# Task states: pending, in_progress, completed, failed
init_tasks() {
    TASKS=(
        ["1_preflight"]="Pre-flight checks and validation"
        ["2_database"]="Database backup and compression"
        ["3_files"]="Application files backup"
        ["4_config"]="Configuration backup"
        ["5_validate"]="Backup validation and integrity check"
        ["6_cleanup"]="Old backup cleanup"
        ["7_monitor"]="Health monitoring and alerts"
        ["8_report"]="Generate backup report"
    )
    
    for task in "${!TASKS[@]}"; do
        TASK_STATUS[$task]="pending"
        TASK_ERRORS[$task]=""
    done
}

# =============================================================================
# LOGGING & OUTPUT
# =============================================================================

log() {
    local level="$1"
    shift
    local message="$@"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    
    echo "[$timestamp] [$level] $message" >> "$LOG_FILE"
    
    case "$level" in
        ERROR)   echo -e "${RED}✗${NC} $message" ;;
        WARNING) echo -e "${YELLOW}⚠${NC} $message" ;;
        SUCCESS) echo -e "${GREEN}✓${NC} $message" ;;
        INFO)    echo -e "${BLUE}ℹ${NC} $message" ;;
        TASK)    echo -e "${CYAN}▶${NC} $message" ;;
        *)       echo "$message" ;;
    esac
}

show_progress() {
    local completed=0
    local total=${#TASKS[@]}
    
    for task in "${!TASK_STATUS[@]}"; do
        if [[ "${TASK_STATUS[$task]}" == "completed" ]]; then
            ((completed++))
        fi
    done
    
    local percentage=$((completed * 100 / total))
    echo -e "${MAGENTA}Progress: [$completed/$total] ${percentage}%${NC}"
}

# =============================================================================
# SELF-HEALING FUNCTIONS
# =============================================================================

check_and_fix_disk_space() {
    local usage=$(df "$BACKUP_DIR" | awk 'NR==2 {print int($5)}')
    
    if [ $usage -gt ${BACKUP_DISK_CRITICAL_PERCENT:-90} ]; then
        log WARNING "Disk usage critical: ${usage}%. Attempting auto-cleanup..."
        
        # Remove oldest backups beyond retention
        find "$DB_BACKUP_DIR" -name "*.sql.gz" -mtime +30 -delete 2>/dev/null || true
        find "$FILES_BACKUP_DIR" -name "*.tar.gz" -mtime +30 -delete 2>/dev/null || true
        
        # Re-check
        usage=$(df "$BACKUP_DIR" | awk 'NR==2 {print int($5)}')
        if [ $usage -lt ${BACKUP_DISK_WARNING_PERCENT:-80} ]; then
            log SUCCESS "Disk space recovered. Now at ${usage}%"
            return 0
        else
            log ERROR "Failed to recover sufficient disk space"
            return 1
        fi
    fi
    return 0
}

test_database_connection() {
    if mysql -h"${DB_BACKUP_HOST}" -u"${DB_BACKUP_USERNAME}" \
           -p"${DB_BACKUP_PASSWORD}" "${DB_BACKUP_DATABASE}" \
           -e "SELECT 1" &>/dev/null; then
        return 0
    else
        log ERROR "Database connection failed"
        return 1
    fi
}

# =============================================================================
# BACKUP FUNCTIONS
# =============================================================================

execute_task() {
    local task_id="$1"
    local task_name="${TASKS[$task_id]}"
    
    TASK_STATUS[$task_id]="in_progress"
    TASK_START_TIME[$task_id]=$(date +%s)
    
    log TASK "Starting: $task_name"
    
    case "$task_id" in
        "1_preflight")
            preflight_checks
            ;;
        "2_database")
            backup_database
            ;;
        "3_files")
            backup_files
            ;;
        "4_config")
            backup_config
            ;;
        "5_validate")
            validate_backups
            ;;
        "6_cleanup")
            cleanup_old_backups
            ;;
        "7_monitor")
            monitor_health
            ;;
        "8_report")
            generate_report
            ;;
    esac
    
    local exit_code=$?
    TASK_END_TIME[$task_id]=$(date +%s)
    
    if [ $exit_code -eq 0 ]; then
        TASK_STATUS[$task_id]="completed"
        log SUCCESS "Completed: $task_name"
    else
        TASK_STATUS[$task_id]="failed"
        TASK_ERRORS[$task_id]="Exit code: $exit_code"
        log ERROR "Failed: $task_name"
        
        # Attempt self-healing
        if [ "${BACKUP_AUTO_HEAL_ENABLED:-true}" == "true" ]; then
            attempt_self_heal "$task_id"
        fi
    fi
    
    show_progress
    return $exit_code
}

preflight_checks() {
    log INFO "Running pre-flight checks..."
    
    # Check disk space
    check_and_fix_disk_space || return 1
    
    # Test database connection
    test_database_connection || return 1
    
    # Check required tools
    for tool in mysql mysqldump gzip tar; do
        if ! command -v $tool &>/dev/null; then
            log ERROR "Required tool not found: $tool"
            return 1
        fi
    done
    
    log SUCCESS "Pre-flight checks passed"
    return 0
}

backup_database() {
    log INFO "Starting database backup..."
    
    local db_file="$DB_BACKUP_DIR/db_backup_$TIMESTAMP.sql.gz"
    
    # Execute backup with retry logic
    local attempt=1
    local max_attempts=${BACKUP_MAX_RETRY_ATTEMPTS:-3}
    
    while [ $attempt -le $max_attempts ]; do
        if mysqldump -h"${DB_BACKUP_HOST}" \
                    -u"${DB_BACKUP_USERNAME}" \
                    -p"${DB_BACKUP_PASSWORD}" \
                    --single-transaction \
                    --routines \
                    --triggers \
                    --events \
                    --quick \
                    --lock-tables=false \
                    "${DB_BACKUP_DATABASE}" 2>/dev/null | \
           gzip -${BACKUP_COMPRESSION_LEVEL:-9} > "$db_file"; then
            
            local size=$(du -h "$db_file" | cut -f1)
            log SUCCESS "Database backup completed: $size"
            
            # Store backup metadata
            echo "$db_file|$size|$(date +%s)" >> "$STATE_FILE"
            return 0
        else
            log WARNING "Database backup attempt $attempt failed"
            ((attempt++))
            [ $attempt -le $max_attempts ] && sleep ${BACKUP_RETRY_DELAY_SECONDS:-300}
        fi
    done
    
    log ERROR "Database backup failed after $max_attempts attempts"
    return 1
}

backup_files() {
    log INFO "Starting file backup..."
    
    local files_file="$FILES_BACKUP_DIR/files_backup_$TIMESTAMP.tar.gz"
    local laravel_dir="/var/www/api-gateway"
    
    # Create file backup with exclusions
    if tar czf "$files_file" \
           --exclude="$laravel_dir/vendor" \
           --exclude="$laravel_dir/node_modules" \
           --exclude="$laravel_dir/storage/logs" \
           --exclude="$laravel_dir/storage/framework/cache" \
           --exclude="$laravel_dir/.git" \
           "$laravel_dir" 2>/dev/null; then
        
        local size=$(du -h "$files_file" | cut -f1)
        log SUCCESS "File backup completed: $size"
        return 0
    else
        log ERROR "File backup failed"
        return 1
    fi
}

backup_config() {
    log INFO "Backing up configurations..."
    
    # Copy important configs
    cp "$BACKUP_CONFIG" "$CONFIG_BACKUP_DIR/.env.backup.$TIMESTAMP" 2>/dev/null || true
    cp /etc/nginx/sites-available/* "$CONFIG_BACKUP_DIR/" 2>/dev/null || true
    crontab -l > "$CONFIG_BACKUP_DIR/crontab.$TIMESTAMP" 2>/dev/null || true
    
    log SUCCESS "Configuration backup completed"
    return 0
}

validate_backups() {
    log INFO "Validating backups..."
    
    local validation_errors=0
    
    # Validate database backup
    local latest_db=$(ls -t "$DB_BACKUP_DIR"/*.sql.gz 2>/dev/null | head -1)
    if [ -z "$latest_db" ] || [ ! -f "$latest_db" ]; then
        log ERROR "No database backup found"
        ((validation_errors++))
    else
        # Test integrity
        if ! gzip -t "$latest_db" 2>/dev/null; then
            log ERROR "Database backup corrupted: $latest_db"
            ((validation_errors++))
        else
            log SUCCESS "Database backup valid: $(basename "$latest_db")"
        fi
    fi
    
    # Validate file backup
    local latest_files=$(ls -t "$FILES_BACKUP_DIR"/*.tar.gz 2>/dev/null | head -1)
    if [ -z "$latest_files" ] || [ ! -f "$latest_files" ]; then
        log ERROR "No file backup found"
        ((validation_errors++))
    else
        if ! tar tzf "$latest_files" &>/dev/null; then
            log ERROR "File backup corrupted: $latest_files"
            ((validation_errors++))
        else
            log SUCCESS "File backup valid: $(basename "$latest_files")"
        fi
    fi
    
    [ $validation_errors -eq 0 ] && return 0 || return 1
}

cleanup_old_backups() {
    log INFO "Cleaning up old backups..."
    
    local count=0
    
    # Remove daily backups older than retention period
    count=$(find "$DB_BACKUP_DIR" -name "*.sql.gz" -mtime +${BACKUP_RETENTION_DAYS:-14} -delete -print 2>/dev/null | wc -l)
    [ $count -gt 0 ] && log INFO "Removed $count old database backups"
    
    count=$(find "$FILES_BACKUP_DIR" -name "*.tar.gz" -mtime +${BACKUP_RETENTION_DAYS:-14} -delete -print 2>/dev/null | wc -l)
    [ $count -gt 0 ] && log INFO "Removed $count old file backups"
    
    log SUCCESS "Cleanup completed"
    return 0
}

monitor_health() {
    log INFO "Running health monitoring..."
    
    # Check backup age
    local latest_backup=$(find "$DB_BACKUP_DIR" -name "*.sql.gz" -type f -printf '%T@ %p\n' 2>/dev/null | sort -rn | head -1 | cut -d' ' -f2-)
    
    if [ -n "$latest_backup" ]; then
        local age_hours=$(( ($(date +%s) - $(stat -c %Y "$latest_backup")) / 3600 ))
        
        if [ $age_hours -gt ${BACKUP_CRITICAL_HOURS:-50} ]; then
            log ERROR "Latest backup is critically old: ${age_hours}h"
            return 1
        elif [ $age_hours -gt ${BACKUP_WARNING_HOURS:-26} ]; then
            log WARNING "Latest backup is getting old: ${age_hours}h"
        else
            log SUCCESS "Latest backup age: ${age_hours}h"
        fi
    fi
    
    return 0
}

generate_report() {
    log INFO "Generating backup report..."
    
    local report_file="$LOGS_DIR/report_$TIMESTAMP.txt"
    
    {
        echo "================================================"
        echo "SuperClaude Backup Report - $TIMESTAMP"
        echo "================================================"
        echo ""
        echo "Task Summary:"
        for task in $(echo "${!TASKS[@]}" | tr ' ' '\n' | sort); do
            printf "  %-20s: %s\n" "${TASKS[$task]}" "${TASK_STATUS[$task]}"
        done
        echo ""
        echo "Statistics:"
        echo "  Total tasks: ${#TASKS[@]}"
        echo "  Completed: $(grep -c "completed" <<< "${TASK_STATUS[@]}")"
        echo "  Failed: $(grep -c "failed" <<< "${TASK_STATUS[@]}")"
        echo "  Duration: $(($(date +%s) - START_TIME))s"
        echo ""
        echo "Backup Files:"
        ls -lah "$DB_BACKUP_DIR"/*.sql.gz 2>/dev/null | tail -5 || echo "  No database backups"
        ls -lah "$FILES_BACKUP_DIR"/*.tar.gz 2>/dev/null | tail -5 || echo "  No file backups"
        echo ""
        echo "Disk Usage:"
        df -h "$BACKUP_DIR"
        echo ""
    } > "$report_file"
    
    log SUCCESS "Report generated: $report_file"
    return 0
}

attempt_self_heal() {
    local task_id="$1"
    
    log INFO "Attempting self-healing for: ${TASKS[$task_id]}"
    
    case "$task_id" in
        "2_database")
            # Restart MySQL and retry
            systemctl restart mysql 2>/dev/null || true
            sleep 5
            execute_task "$task_id"
            ;;
        "1_preflight")
            # Try to fix common issues
            check_and_fix_disk_space
            execute_task "$task_id"
            ;;
        *)
            log WARNING "No self-healing available for: ${TASKS[$task_id]}"
            ;;
    esac
}

# =============================================================================
# MAIN EXECUTION
# =============================================================================

main() {
    log INFO "=== SuperClaude Backup Orchestrator v2.0 ==="
    log INFO "Starting backup process at $TIMESTAMP"
    
    # Initialize task system
    init_tasks
    
    # Execute tasks in order
    for task in $(echo "${!TASKS[@]}" | tr ' ' '\n' | sort); do
        execute_task "$task"
        
        # Stop on critical failure
        if [[ "$task" =~ ^[12]_ ]] && [ "${TASK_STATUS[$task]}" == "failed" ]; then
            log ERROR "Critical task failed. Stopping execution."
            break
        fi
    done
    
    # Final summary
    echo ""
    log INFO "=== Backup Process Complete ==="
    show_progress
    
    # Exit code based on failures
    local failed_count=$(grep -c "failed" <<< "${TASK_STATUS[@]}")
    [ $failed_count -eq 0 ] && exit 0 || exit 1
}

# Run main function
main "$@"