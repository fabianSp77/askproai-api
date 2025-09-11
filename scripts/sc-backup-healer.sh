#!/bin/bash

# SuperClaude Backup Self-Healer
# Version: 2.0.0
# Automatic problem detection and resolution for backup system
# Implements intelligent self-healing mechanisms

set -euo pipefail

# =============================================================================
# CONFIGURATION
# =============================================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TIMESTAMP=$(date +%Y-%m-%d_%H-%M-%S)

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m'

# Load configuration
BACKUP_CONFIG="${SCRIPT_DIR}/../.env.backup"
if [ -f "$BACKUP_CONFIG" ]; then
    export $(grep -v '^#' "$BACKUP_CONFIG" | xargs)
else
    echo -e "${RED}✗ Critical: .env.backup not found!${NC}"
    exit 1
fi

# Paths
BACKUP_DIR="${BACKUP_BASE_DIR:-/var/backups/askproai}"
HEALING_LOG="$BACKUP_DIR/logs/healing_$TIMESTAMP.log"
STATE_FILE="$BACKUP_DIR/.healer_state"
INCIDENT_LOG="$BACKUP_DIR/logs/incidents.log"

# Healing metrics
declare -A ISSUES_DETECTED
declare -A ISSUES_RESOLVED
declare -A HEALING_ACTIONS
TOTAL_ISSUES=0
TOTAL_RESOLVED=0

# =============================================================================
# LOGGING
# =============================================================================

log() {
    local level="$1"
    shift
    local message="$@"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    
    echo "[$timestamp] [$level] $message" >> "$HEALING_LOG"
    
    case "$level" in
        ERROR)   echo -e "${RED}✗${NC} $message" ;;
        WARNING) echo -e "${YELLOW}⚠${NC} $message" ;;
        SUCCESS) echo -e "${GREEN}✓${NC} $message" ;;
        INFO)    echo -e "${BLUE}ℹ${NC} $message" ;;
        HEAL)    echo -e "${MAGENTA}⚕${NC} $message" ;;
        DETECT)  echo -e "${CYAN}🔍${NC} $message" ;;
        *)       echo "$message" ;;
    esac
}

record_incident() {
    local issue="$1"
    local action="$2"
    local result="$3"
    
    echo "[$TIMESTAMP] Issue: $issue | Action: $action | Result: $result" >> "$INCIDENT_LOG"
}

# =============================================================================
# DETECTION FUNCTIONS
# =============================================================================

detect_disk_space_issue() {
    log DETECT "Checking disk space..."
    
    local usage=$(df "$BACKUP_DIR" | awk 'NR==2 {print int($5)}')
    local available=$(df "$BACKUP_DIR" | awk 'NR==2 {print $4}')
    
    if [ $usage -gt ${BACKUP_DISK_CRITICAL_PERCENT:-90} ]; then
        ISSUES_DETECTED["disk_space"]="critical"
        log ERROR "Disk space critical: ${usage}% used, $available available"
        return 1
    elif [ $usage -gt ${BACKUP_DISK_WARNING_PERCENT:-80} ]; then
        ISSUES_DETECTED["disk_space"]="warning"
        log WARNING "Disk space warning: ${usage}% used, $available available"
        return 2
    else
        log SUCCESS "Disk space healthy: ${usage}% used"
        return 0
    fi
}

detect_database_issue() {
    log DETECT "Checking database connectivity..."
    
    if ! mysql -h"${DB_BACKUP_HOST}" -u"${DB_BACKUP_USERNAME}" \
              -p"${DB_BACKUP_PASSWORD}" "${DB_BACKUP_DATABASE}" \
              -e "SELECT 1" &>/dev/null; then
        ISSUES_DETECTED["database"]="connection_failed"
        log ERROR "Database connection failed"
        return 1
    else
        log SUCCESS "Database connection healthy"
        return 0
    fi
}

detect_permission_issue() {
    log DETECT "Checking file permissions..."
    
    local issues=0
    
    # Check backup directory permissions
    if [ ! -w "$BACKUP_DIR" ]; then
        ISSUES_DETECTED["permissions"]="backup_dir_not_writable"
        log ERROR "Backup directory not writable: $BACKUP_DIR"
        ((issues++))
    fi
    
    # Check script permissions
    for script in "$SCRIPT_DIR"/*.sh; do
        if [ ! -x "$script" ]; then
            ISSUES_DETECTED["permissions"]="script_not_executable"
            log WARNING "Script not executable: $(basename "$script")"
            ((issues++))
        fi
    done
    
    if [ $issues -eq 0 ]; then
        log SUCCESS "All permissions correct"
    fi
    
    return $issues
}

detect_service_issue() {
    log DETECT "Checking required services..."
    
    local services=("mysql" "nginx" "php8.3-fpm")
    local issues=0
    
    for service in "${services[@]}"; do
        if ! systemctl is-active --quiet "$service"; then
            ISSUES_DETECTED["service_$service"]="not_running"
            log ERROR "Service not running: $service"
            ((issues++))
        fi
    done
    
    if [ $issues -eq 0 ]; then
        log SUCCESS "All services running"
    fi
    
    return $issues
}

detect_backup_age_issue() {
    log DETECT "Checking backup age..."
    
    local latest_backup=$(find "$BACKUP_DIR" -name "*.gz" -type f -printf '%T@ %p\n' 2>/dev/null | sort -rn | head -1 | cut -d' ' -f2-)
    
    if [ -z "$latest_backup" ]; then
        ISSUES_DETECTED["backup_age"]="no_backups"
        log ERROR "No backups found"
        return 1
    fi
    
    local age_hours=$(( ($(date +%s) - $(stat -c %Y "$latest_backup")) / 3600 ))
    
    if [ $age_hours -gt ${BACKUP_CRITICAL_HOURS:-50} ]; then
        ISSUES_DETECTED["backup_age"]="critical_$age_hours"
        log ERROR "Backup critically old: ${age_hours}h"
        return 1
    elif [ $age_hours -gt ${BACKUP_WARNING_HOURS:-26} ]; then
        ISSUES_DETECTED["backup_age"]="warning_$age_hours"
        log WARNING "Backup getting old: ${age_hours}h"
        return 2
    else
        log SUCCESS "Backup age healthy: ${age_hours}h"
        return 0
    fi
}

detect_corruption() {
    log DETECT "Checking for backup corruption..."
    
    local corrupted=0
    
    # Check recent database backups
    for backup in $(find "$BACKUP_DIR/db" -name "*.sql.gz" -mtime -1 2>/dev/null); do
        if ! gzip -t "$backup" 2>/dev/null; then
            ISSUES_DETECTED["corruption_$(basename "$backup")"]="gzip_failed"
            log ERROR "Corrupted backup: $(basename "$backup")"
            ((corrupted++))
        fi
    done
    
    # Check recent file backups
    for backup in $(find "$BACKUP_DIR/files" -name "*.tar.gz" -mtime -1 2>/dev/null); do
        if ! tar tzf "$backup" &>/dev/null; then
            ISSUES_DETECTED["corruption_$(basename "$backup")"]="tar_failed"
            log ERROR "Corrupted backup: $(basename "$backup")"
            ((corrupted++))
        fi
    done
    
    if [ $corrupted -eq 0 ]; then
        log SUCCESS "No corruption detected"
    fi
    
    return $corrupted
}

# =============================================================================
# HEALING FUNCTIONS
# =============================================================================

heal_disk_space() {
    log HEAL "Attempting to free disk space..."
    
    local initial_usage=$(df "$BACKUP_DIR" | awk 'NR==2 {print int($5)}')
    
    # Step 1: Remove old backups beyond retention
    local removed=0
    removed=$(find "$BACKUP_DIR" -name "*.gz" -mtime +${BACKUP_RETENTION_DAYS:-14} -delete -print 2>/dev/null | wc -l)
    log INFO "Removed $removed old backups"
    
    # Step 2: Clear old logs
    find "$BACKUP_DIR/logs" -name "*.log" -mtime +30 -delete 2>/dev/null
    
    # Step 3: Remove orphaned temp files
    find /tmp -name "backup_*" -mtime +1 -delete 2>/dev/null
    
    # Step 4: If still critical, remove oldest backups keeping minimum set
    local current_usage=$(df "$BACKUP_DIR" | awk 'NR==2 {print int($5)}')
    if [ $current_usage -gt ${BACKUP_DISK_CRITICAL_PERCENT:-90} ]; then
        log WARNING "Still critical, removing oldest backups keeping last 3..."
        
        # Keep only last 3 of each type
        ls -t "$BACKUP_DIR/db"/*.sql.gz 2>/dev/null | tail -n +4 | xargs rm -f 2>/dev/null
        ls -t "$BACKUP_DIR/files"/*.tar.gz 2>/dev/null | tail -n +4 | xargs rm -f 2>/dev/null
    fi
    
    # Check final usage
    local final_usage=$(df "$BACKUP_DIR" | awk 'NR==2 {print int($5)}')
    local freed=$((initial_usage - final_usage))
    
    if [ $final_usage -lt ${BACKUP_DISK_WARNING_PERCENT:-80} ]; then
        log SUCCESS "Disk space recovered: ${freed}% freed, now at ${final_usage}%"
        ISSUES_RESOLVED["disk_space"]="freed_${freed}_percent"
        HEALING_ACTIONS["disk_space"]="cleanup_successful"
        record_incident "disk_space_critical" "cleanup" "success"
        return 0
    else
        log ERROR "Failed to free sufficient space. Current: ${final_usage}%"
        HEALING_ACTIONS["disk_space"]="cleanup_insufficient"
        record_incident "disk_space_critical" "cleanup" "partial"
        return 1
    fi
}

heal_database_connection() {
    log HEAL "Attempting to restore database connection..."
    
    # Step 1: Check if MySQL service is running
    if ! systemctl is-active --quiet mysql; then
        log INFO "MySQL service not running. Attempting restart..."
        
        if systemctl restart mysql 2>/dev/null; then
            sleep 5
            log SUCCESS "MySQL service restarted"
        else
            log ERROR "Failed to restart MySQL service"
            return 1
        fi
    fi
    
    # Step 2: Test connection again
    if mysql -h"${DB_BACKUP_HOST}" -u"${DB_BACKUP_USERNAME}" \
            -p"${DB_BACKUP_PASSWORD}" "${DB_BACKUP_DATABASE}" \
            -e "SELECT 1" &>/dev/null; then
        log SUCCESS "Database connection restored"
        ISSUES_RESOLVED["database"]="connection_restored"
        HEALING_ACTIONS["database"]="service_restart"
        record_incident "database_connection" "restart_mysql" "success"
        return 0
    fi
    
    # Step 3: Check for lock files
    if [ -f /var/lib/mysql/*.lock ]; then
        log INFO "Removing MySQL lock files..."
        rm -f /var/lib/mysql/*.lock 2>/dev/null
        systemctl restart mysql 2>/dev/null
        sleep 5
    fi
    
    # Final test
    if mysql -h"${DB_BACKUP_HOST}" -u"${DB_BACKUP_USERNAME}" \
            -p"${DB_BACKUP_PASSWORD}" "${DB_BACKUP_DATABASE}" \
            -e "SELECT 1" &>/dev/null; then
        log SUCCESS "Database connection restored after lock cleanup"
        ISSUES_RESOLVED["database"]="connection_restored"
        HEALING_ACTIONS["database"]="lock_cleanup"
        record_incident "database_connection" "lock_cleanup" "success"
        return 0
    else
        log ERROR "Failed to restore database connection"
        HEALING_ACTIONS["database"]="failed"
        record_incident "database_connection" "multiple_attempts" "failed"
        return 1
    fi
}

heal_permissions() {
    log HEAL "Fixing permission issues..."
    
    local fixed=0
    
    # Fix backup directory permissions
    if [ ! -w "$BACKUP_DIR" ]; then
        chmod 755 "$BACKUP_DIR" 2>/dev/null && ((fixed++))
        chown www-data:www-data "$BACKUP_DIR" 2>/dev/null
    fi
    
    # Fix script permissions
    for script in "$SCRIPT_DIR"/*.sh; do
        if [ ! -x "$script" ]; then
            chmod +x "$script" 2>/dev/null && ((fixed++))
            log INFO "Made executable: $(basename "$script")"
        fi
    done
    
    # Fix subdirectory permissions
    find "$BACKUP_DIR" -type d -exec chmod 755 {} \; 2>/dev/null
    find "$BACKUP_DIR" -type f -name "*.sh" -exec chmod +x {} \; 2>/dev/null
    
    if [ $fixed -gt 0 ]; then
        log SUCCESS "Fixed $fixed permission issues"
        ISSUES_RESOLVED["permissions"]="fixed_$fixed"
        HEALING_ACTIONS["permissions"]="chmod_successful"
        record_incident "permissions" "chmod_fix" "success"
        return 0
    else
        log INFO "No permission issues to fix"
        return 0
    fi
}

heal_services() {
    log HEAL "Attempting to restore services..."
    
    local services=("mysql" "nginx" "php8.3-fpm")
    local restored=0
    
    for service in "${services[@]}"; do
        if ! systemctl is-active --quiet "$service"; then
            log INFO "Starting $service..."
            
            if systemctl start "$service" 2>/dev/null; then
                sleep 2
                if systemctl is-active --quiet "$service"; then
                    log SUCCESS "Started $service"
                    ((restored++))
                    ISSUES_RESOLVED["service_$service"]="started"
                    record_incident "service_$service" "start" "success"
                else
                    log ERROR "Failed to start $service"
                    record_incident "service_$service" "start" "failed"
                fi
            fi
        fi
    done
    
    if [ $restored -gt 0 ]; then
        log SUCCESS "Restored $restored services"
        HEALING_ACTIONS["services"]="restored_$restored"
        return 0
    else
        return 0
    fi
}

heal_old_backup() {
    log HEAL "Attempting to create fresh backup..."
    
    # Trigger immediate backup
    if [ -x "$SCRIPT_DIR/sc-backup-orchestrator.sh" ]; then
        log INFO "Running backup orchestrator..."
        
        if "$SCRIPT_DIR/sc-backup-orchestrator.sh"; then
            log SUCCESS "Fresh backup created successfully"
            ISSUES_RESOLVED["backup_age"]="new_backup_created"
            HEALING_ACTIONS["backup_age"]="backup_successful"
            record_incident "old_backup" "trigger_backup" "success"
            return 0
        else
            log ERROR "Backup orchestrator failed"
            HEALING_ACTIONS["backup_age"]="backup_failed"
            record_incident "old_backup" "trigger_backup" "failed"
            return 1
        fi
    else
        log WARNING "Backup orchestrator not found"
        return 1
    fi
}

heal_corruption() {
    log HEAL "Handling corrupted backups..."
    
    local removed=0
    
    for issue_key in "${!ISSUES_DETECTED[@]}"; do
        if [[ "$issue_key" =~ ^corruption_ ]]; then
            local file_name="${issue_key#corruption_}"
            local corrupted_file=$(find "$BACKUP_DIR" -name "$file_name" 2>/dev/null | head -1)
            
            if [ -n "$corrupted_file" ] && [ -f "$corrupted_file" ]; then
                log INFO "Quarantining corrupted file: $file_name"
                
                # Move to quarantine directory
                local quarantine_dir="$BACKUP_DIR/quarantine"
                mkdir -p "$quarantine_dir"
                mv "$corrupted_file" "$quarantine_dir/" 2>/dev/null
                ((removed++))
                
                record_incident "corruption_$file_name" "quarantine" "success"
            fi
        fi
    done
    
    if [ $removed -gt 0 ]; then
        log SUCCESS "Quarantined $removed corrupted files"
        ISSUES_RESOLVED["corruption"]="quarantined_$removed"
        HEALING_ACTIONS["corruption"]="quarantine_successful"
        
        # Trigger new backup to replace corrupted ones
        heal_old_backup
        return 0
    else
        return 0
    fi
}

# =============================================================================
# MAIN HEALING PROCESS
# =============================================================================

run_diagnostics() {
    log INFO "=== Running System Diagnostics ==="
    
    TOTAL_ISSUES=0
    
    # Run all detection functions
    detect_disk_space_issue || ((TOTAL_ISSUES++))
    detect_database_issue || ((TOTAL_ISSUES++))
    detect_permission_issue || ((TOTAL_ISSUES++))
    detect_service_issue || ((TOTAL_ISSUES++))
    detect_backup_age_issue || ((TOTAL_ISSUES++))
    detect_corruption || ((TOTAL_ISSUES++))
    
    log INFO "Total issues detected: $TOTAL_ISSUES"
    
    return $TOTAL_ISSUES
}

apply_healing() {
    log INFO "=== Applying Self-Healing ==="
    
    TOTAL_RESOLVED=0
    
    # Apply healing based on detected issues
    for issue in "${!ISSUES_DETECTED[@]}"; do
        log HEAL "Addressing issue: $issue = ${ISSUES_DETECTED[$issue]}"
        
        case "$issue" in
            disk_space)
                heal_disk_space && ((TOTAL_RESOLVED++))
                ;;
            database)
                heal_database_connection && ((TOTAL_RESOLVED++))
                ;;
            permissions)
                heal_permissions && ((TOTAL_RESOLVED++))
                ;;
            service_*)
                heal_services && ((TOTAL_RESOLVED++))
                ;;
            backup_age)
                heal_old_backup && ((TOTAL_RESOLVED++))
                ;;
            corruption_*)
                heal_corruption && ((TOTAL_RESOLVED++))
                ;;
            *)
                log WARNING "No healing available for: $issue"
                ;;
        esac
    done
    
    log INFO "Total issues resolved: $TOTAL_RESOLVED"
}

generate_healing_report() {
    log INFO "Generating healing report..."
    
    {
        echo "================================================"
        echo "Self-Healing Report - $TIMESTAMP"
        echo "================================================"
        echo ""
        echo "Issues Detected: $TOTAL_ISSUES"
        for issue in "${!ISSUES_DETECTED[@]}"; do
            echo "  - $issue: ${ISSUES_DETECTED[$issue]}"
        done
        echo ""
        echo "Issues Resolved: $TOTAL_RESOLVED"
        for issue in "${!ISSUES_RESOLVED[@]}"; do
            echo "  - $issue: ${ISSUES_RESOLVED[$issue]}"
        done
        echo ""
        echo "Healing Actions Taken:"
        for action in "${!HEALING_ACTIONS[@]}"; do
            echo "  - $action: ${HEALING_ACTIONS[$action]}"
        done
        echo ""
        echo "System Status: $([ $TOTAL_ISSUES -eq $TOTAL_RESOLVED ] && echo "HEALTHY" || echo "NEEDS ATTENTION")"
        echo ""
    } | tee -a "$HEALING_LOG"
}

# =============================================================================
# MAIN
# =============================================================================

main() {
    # Create log directory
    mkdir -p "$(dirname "$HEALING_LOG")"
    
    log INFO "=== SuperClaude Backup Self-Healer v2.0 ==="
    log INFO "Starting self-healing process..."
    
    # Run diagnostics
    run_diagnostics
    
    # Apply healing if issues found
    if [ $TOTAL_ISSUES -gt 0 ]; then
        apply_healing
        
        # Re-run diagnostics to verify
        log INFO "=== Verifying Healing Results ==="
        run_diagnostics
    else
        log SUCCESS "No issues detected. System healthy!"
    fi
    
    # Generate report
    generate_healing_report
    
    # Exit based on unresolved issues
    local unresolved=$((TOTAL_ISSUES - TOTAL_RESOLVED))
    if [ $unresolved -eq 0 ]; then
        log SUCCESS "=== All Issues Resolved Successfully ==="
        exit 0
    else
        log WARNING "=== $unresolved Issues Remain Unresolved ==="
        exit 1
    fi
}

# Run main
main "$@"