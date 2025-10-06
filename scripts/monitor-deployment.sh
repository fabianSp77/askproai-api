#!/bin/bash
################################################################################
# Deployment Monitoring Script
# Purpose: Continuous monitoring during 3-hour post-deployment window
# Usage: ./monitor-deployment.sh [duration_minutes]
# Exit Codes: 0=monitoring complete, 1=critical errors detected
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
MONITOR_LOG="${LOG_DIR}/monitor-$(date +%Y%m%d-%H%M%S).log"
ERROR_LOG="/var/www/api-gateway/storage/logs/laravel.log"
SLOW_QUERY_LOG="/var/log/mysql/slow-query.log"

# Default monitoring duration (3 hours = 180 minutes)
DURATION_MINUTES=${1:-180}
CHECK_INTERVAL_SECONDS=30

# Alert thresholds
ERROR_THRESHOLD_PER_MIN=5
SLOW_QUERY_THRESHOLD_PER_MIN=10
REDIS_MEMORY_THRESHOLD_MB=512
CRITICAL_ERROR_KEYWORDS=("SQLSTATE" "foreign key constraint" "Integrity constraint" "deadlock" "Connection refused" "out of memory")

# Counters
TOTAL_ERRORS=0
TOTAL_WARNINGS=0
CRITICAL_ALERTS=0
START_TIME=$(date +%s)
END_TIME=$((START_TIME + DURATION_MINUTES * 60))

################################################################################
# Logging Functions
################################################################################

setup_logging() {
    mkdir -p "$LOG_DIR"
    touch "$MONITOR_LOG"
    echo "==========================================" | tee -a "$MONITOR_LOG"
    echo "Deployment Monitoring Started - $(date)" | tee -a "$MONITOR_LOG"
    echo "Duration: $DURATION_MINUTES minutes" | tee -a "$MONITOR_LOG"
    echo "==========================================" | tee -a "$MONITOR_LOG"
}

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$MONITOR_LOG"
}

log_success() {
    echo -e "${GREEN}✅ [$(date '+%H:%M:%S')] $*${NC}" | tee -a "$MONITOR_LOG"
}

log_error() {
    echo -e "${RED}❌ [$(date '+%H:%M:%S')] $*${NC}" | tee -a "$MONITOR_LOG"
    ((TOTAL_ERRORS++))
}

log_warning() {
    echo -e "${YELLOW}⚠️  [$(date '+%H:%M:%S')] $*${NC}" | tee -a "$MONITOR_LOG"
    ((TOTAL_WARNINGS++))
}

log_critical() {
    echo -e "${RED}${BOLD}🚨 [$(date '+%H:%M:%S')] CRITICAL: $*${NC}" | tee -a "$MONITOR_LOG"
    ((CRITICAL_ALERTS++))
}

log_info() {
    echo -e "${BLUE}ℹ️  [$(date '+%H:%M:%S')] $*${NC}" | tee -a "$MONITOR_LOG"
}

log_metric() {
    echo -e "${CYAN}📊 [$(date '+%H:%M:%S')] $*${NC}" | tee -a "$MONITOR_LOG"
}

################################################################################
# Monitoring Functions
################################################################################

monitor_error_log() {
    if [[ ! -f "$ERROR_LOG" ]]; then
        log_warning "Laravel error log not found: $ERROR_LOG"
        return 0
    fi

    # Get errors from last minute
    local recent_errors=$(find "$ERROR_LOG" -type f -mmin -1 2>/dev/null || echo "")

    if [[ -n "$recent_errors" ]]; then
        local error_count=$(tail -n 100 "$ERROR_LOG" 2>/dev/null | grep -c "\[$(date +%Y-%m-%d)" || echo "0")

        if [[ $error_count -gt $ERROR_THRESHOLD_PER_MIN ]]; then
            log_error "High error rate detected: $error_count errors/min (threshold: $ERROR_THRESHOLD_PER_MIN)"

            # Check for critical errors
            for keyword in "${CRITICAL_ERROR_KEYWORDS[@]}"; do
                if tail -n 100 "$ERROR_LOG" | grep -q "$keyword"; then
                    log_critical "Critical error detected: $keyword"

                    # Log the actual error
                    local error_line=$(tail -n 100 "$ERROR_LOG" | grep "$keyword" | tail -n 1)
                    log "$error_line"
                fi
            done
        else
            log_metric "Error rate: $error_count errors/min (normal)"
        fi
    fi
}

monitor_slow_queries() {
    if [[ ! -f "$SLOW_QUERY_LOG" ]]; then
        log_info "MySQL slow query log not found (may be disabled)"
        return 0
    fi

    # Count slow queries in last minute
    local slow_count=$(grep -c "Query_time" "$SLOW_QUERY_LOG" 2>/dev/null | tail -n 100 || echo "0")

    if [[ $slow_count -gt $SLOW_QUERY_THRESHOLD_PER_MIN ]]; then
        log_warning "High slow query rate: $slow_count queries/min (threshold: $SLOW_QUERY_THRESHOLD_PER_MIN)"

        # Show top slow query
        local slow_query=$(tail -n 50 "$SLOW_QUERY_LOG" | grep -A 5 "Query_time" | tail -n 1 || echo "N/A")
        log "Recent slow query: $slow_query"
    else
        log_metric "Slow queries: $slow_count/min (acceptable)"
    fi
}

monitor_redis_memory() {
    local redis_memory=$(php -r "
        require '/var/www/api-gateway/vendor/autoload.php';
        \$app = require_once '/var/www/api-gateway/bootstrap/app.php';
        \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        try {
            \$info = Redis::connection()->info('memory');
            if (isset(\$info['used_memory'])) {
                echo round(\$info['used_memory'] / 1024 / 1024, 2);
            } else {
                echo '0';
            }
        } catch (Exception \$e) {
            echo 'ERROR';
        }
    " 2>/dev/null)

    if [[ "$redis_memory" == "ERROR" ]]; then
        log_error "Cannot connect to Redis"
        return 1
    fi

    if [[ "$redis_memory" != "0" ]]; then
        if (( $(echo "$redis_memory > $REDIS_MEMORY_THRESHOLD_MB" | bc -l) )); then
            log_warning "Redis memory usage high: ${redis_memory}MB (threshold: ${REDIS_MEMORY_THRESHOLD_MB}MB)"
        else
            log_metric "Redis memory: ${redis_memory}MB (normal)"
        fi
    fi
}

monitor_database_connections() {
    local db_connections=$(php -r "
        require '/var/www/api-gateway/vendor/autoload.php';
        \$app = require_once '/var/www/api-gateway/bootstrap/app.php';
        \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        try {
            \$result = DB::select('SHOW STATUS LIKE \"Threads_connected\"');
            echo \$result[0]->Value ?? 0;
        } catch (Exception \$e) {
            echo 'ERROR';
        }
    " 2>/dev/null)

    if [[ "$db_connections" == "ERROR" ]]; then
        log_critical "Cannot query database connection status"
        return 1
    fi

    if [[ $db_connections -gt 100 ]]; then
        log_warning "High database connections: $db_connections"
    else
        log_metric "Database connections: $db_connections"
    fi
}

monitor_queue_workers() {
    local worker_count=$(ps aux | grep -c "[q]ueue:work" || echo "0")

    if [[ $worker_count -lt 1 ]]; then
        log_warning "No queue workers running"
    else
        log_metric "Queue workers: $worker_count"
    fi
}

monitor_disk_space() {
    local disk_usage=$(df -h /var/www/api-gateway | awk 'NR==2 {print $5}' | sed 's/%//')

    if [[ $disk_usage -gt 90 ]]; then
        log_critical "Disk usage critical: ${disk_usage}%"
    elif [[ $disk_usage -gt 80 ]]; then
        log_warning "Disk usage high: ${disk_usage}%"
    else
        log_metric "Disk usage: ${disk_usage}%"
    fi
}

monitor_migration_tables() {
    local table_counts=$(php -r "
        require '/var/www/api-gateway/vendor/autoload.php';
        \$app = require_once '/var/www/api-gateway/bootstrap/app.php';
        \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        try {
            \$policy_count = DB::table('policy_configurations')->count();
            \$callback_count = DB::table('callback_requests')->count();
            echo \"policy_configurations:\$policy_count,callback_requests:\$callback_count\";
        } catch (Exception \$e) {
            echo 'ERROR: ' . \$e->getMessage();
        }
    " 2>/dev/null)

    if [[ "$table_counts" == ERROR* ]]; then
        log_critical "Cannot query migration tables: $table_counts"
    else
        log_metric "Table counts: $table_counts"
    fi
}

check_application_health() {
    local health_status=$(php -r "
        require '/var/www/api-gateway/vendor/autoload.php';
        \$app = require_once '/var/www/api-gateway/bootstrap/app.php';
        \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        try {
            // Test database
            DB::connection()->getPdo();

            // Test cache
            Cache::put('health_check', 'ok', 5);
            \$cached = Cache::get('health_check');
            if (\$cached !== 'ok') throw new Exception('Cache check failed');

            // Test migration tables
            DB::table('policy_configurations')->count();
            DB::table('callback_requests')->count();

            echo 'OK';
        } catch (Exception \$e) {
            echo 'FAILED: ' . \$e->getMessage();
        }
    " 2>&1)

    if [[ "$health_status" == "OK" ]]; then
        log_success "Application health check passed"
    else
        log_critical "Application health check failed: $health_status"
    fi
}

check_migration_integrity() {
    local integrity_check=$(php -r "
        require '/var/www/api-gateway/vendor/autoload.php';
        \$app = require_once '/var/www/api-gateway/bootstrap/app.php';
        \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        try {
            // Check tables exist
            if (!DB::getSchemaBuilder()->hasTable('policy_configurations')) {
                throw new Exception('policy_configurations table missing');
            }
            if (!DB::getSchemaBuilder()->hasTable('callback_requests')) {
                throw new Exception('callback_requests table missing');
            }

            // Check foreign keys exist
            \$fks = DB::select(\"
                SELECT COUNT(*) as count
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = 'askproai_db'
                AND TABLE_NAME IN ('policy_configurations', 'callback_requests')
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            \");

            if (\$fks[0]->count < 5) {
                throw new Exception('Missing foreign key constraints');
            }

            echo 'OK';
        } catch (Exception \$e) {
            echo 'FAILED: ' . \$e->getMessage();
        }
    " 2>&1)

    if [[ "$integrity_check" == "OK" ]]; then
        log_success "Migration integrity check passed"
    else
        log_critical "Migration integrity check failed: $integrity_check"
    fi
}

################################################################################
# Display Functions
################################################################################

display_header() {
    clear
    echo -e "${BOLD}${BLUE}"
    echo "╔══════════════════════════════════════════════════════╗"
    echo "║        PRODUCTION DEPLOYMENT MONITOR                 ║"
    echo "╚══════════════════════════════════════════════════════╝"
    echo -e "${NC}"

    local current_time=$(date +%s)
    local elapsed=$((current_time - START_TIME))
    local remaining=$((END_TIME - current_time))
    local elapsed_min=$((elapsed / 60))
    local remaining_min=$((remaining / 60))

    echo -e "${CYAN}Started:${NC} $(date -d @$START_TIME '+%Y-%m-%d %H:%M:%S')"
    echo -e "${CYAN}Elapsed:${NC} ${elapsed_min} minutes | ${CYAN}Remaining:${NC} ${remaining_min} minutes"
    echo -e "${CYAN}Status:${NC} Monitoring..."
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
}

display_summary() {
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo -e "${BOLD}Current Status:${NC}"
    echo -e "  ${GREEN}Checks Passed${NC}"
    echo -e "  ${YELLOW}Warnings: $TOTAL_WARNINGS${NC}"
    echo -e "  ${RED}Errors: $TOTAL_ERRORS${NC}"
    echo -e "  ${RED}${BOLD}Critical Alerts: $CRITICAL_ALERTS${NC}"
    echo ""
}

################################################################################
# Main Monitoring Loop
################################################################################

run_monitoring_cycle() {
    display_header

    # System health checks
    log_info "Running system health checks..."
    check_application_health
    check_migration_integrity

    # Resource monitoring
    log_info "Monitoring system resources..."
    monitor_disk_space
    monitor_database_connections
    monitor_redis_memory
    monitor_queue_workers

    # Error monitoring
    log_info "Monitoring for errors..."
    monitor_error_log
    monitor_slow_queries

    # Migration-specific monitoring
    log_info "Monitoring migration tables..."
    monitor_migration_tables

    display_summary

    # Check if we should exit due to critical errors
    if [[ $CRITICAL_ALERTS -gt 5 ]]; then
        log_critical "Too many critical alerts ($CRITICAL_ALERTS) - consider emergency rollback"
        log "Run: /var/www/api-gateway/scripts/emergency-rollback.sh"
        return 1
    fi

    return 0
}

################################################################################
# Main Execution
################################################################################

main() {
    setup_logging

    log_info "Starting deployment monitoring for $DURATION_MINUTES minutes..."
    log_info "Press Ctrl+C to stop monitoring early"
    echo ""

    # Run initial check
    if ! run_monitoring_cycle; then
        log_critical "Initial health check failed - monitoring stopped"
        exit 1
    fi

    # Monitoring loop
    while true; do
        local current_time=$(date +%s)

        # Check if monitoring period is over
        if [[ $current_time -ge $END_TIME ]]; then
            log_success "Monitoring period completed successfully"
            break
        fi

        # Wait for next check interval
        sleep $CHECK_INTERVAL_SECONDS

        # Run monitoring cycle
        if ! run_monitoring_cycle; then
            log_critical "Critical failures detected - monitoring stopped"
            exit 1
        fi
    done

    # Final summary
    echo ""
    echo "==========================================" | tee -a "$MONITOR_LOG"
    echo "Deployment Monitoring Complete - $(date)" | tee -a "$MONITOR_LOG"
    echo "==========================================" | tee -a "$MONITOR_LOG"
    echo "Total Warnings: $TOTAL_WARNINGS" | tee -a "$MONITOR_LOG"
    echo "Total Errors: $TOTAL_ERRORS" | tee -a "$MONITOR_LOG"
    echo "Critical Alerts: $CRITICAL_ALERTS" | tee -a "$MONITOR_LOG"
    echo "==========================================" | tee -a "$MONITOR_LOG"
    echo "Log file: $MONITOR_LOG" | tee -a "$MONITOR_LOG"
    echo ""

    if [[ $CRITICAL_ALERTS -eq 0 ]]; then
        log_success "Deployment monitoring completed successfully - no critical issues"
        exit 0
    else
        log_error "Deployment monitoring completed with $CRITICAL_ALERTS critical alerts"
        exit 1
    fi
}

# Trap Ctrl+C for graceful exit
trap 'echo -e "\n${YELLOW}Monitoring stopped by user${NC}"; exit 0' INT

main "$@"
