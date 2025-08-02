#!/bin/bash
# monitor-deployment.sh
# Post-deployment monitoring script for Business Portal

set -e

DEPLOYMENT_ID="${1:-unknown}"
MONITORING_DURATION="${2:-3600}"  # Default 1 hour
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
LOG_FILE="$PROJECT_ROOT/storage/deployment/monitoring-${DEPLOYMENT_ID}.log"
START_TIME=$(date +%s)

# Monitoring thresholds
RESPONSE_TIME_THRESHOLD=2000    # ms
ERROR_RATE_THRESHOLD=5.0        # percentage
MEMORY_THRESHOLD=85.0           # percentage
CPU_THRESHOLD=80.0              # percentage
QUEUE_SIZE_THRESHOLD=1000       # jobs

# Alert counters
RESPONSE_TIME_ALERTS=0
ERROR_RATE_ALERTS=0
HEALTH_CHECK_FAILURES=0

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1" | tee -a "$LOG_FILE"
}

success() {
    echo -e "${GREEN}✅ $1${NC}" | tee -a "$LOG_FILE"
}

warning() {
    echo -e "${YELLOW}⚠️ $1${NC}" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}❌ $1${NC}" | tee -a "$LOG_FILE"
}

# Send alert notification
send_alert() {
    local severity="$1"
    local message="$2"
    
    log "ALERT [$severity]: $message"
    
    # Send notification
    if [ -f "$SCRIPT_DIR/send-deployment-notification.sh" ]; then
        if [ "$severity" = "CRITICAL" ]; then
            "$SCRIPT_DIR/send-deployment-notification.sh" "emergency" \
                "Deployment Monitoring Alert" "$message" "$DEPLOYMENT_ID" &
        else
            # Log to monitoring alerts
            echo "$(date -Iseconds): [$severity] $message" >> \
                "$PROJECT_ROOT/storage/deployment/monitoring-alerts.log"
        fi
    fi
}

# Check response time
check_response_time() {
    local url="https://api.askproai.de/health"
    local response_time
    
    response_time=$(curl -w "%{time_total}" -s -o /dev/null "$url" 2>/dev/null || echo "999")
    response_time_ms=$(echo "$response_time * 1000" | bc -l | cut -d. -f1)
    
    if [ "$response_time_ms" -gt "$RESPONSE_TIME_THRESHOLD" ]; then
        RESPONSE_TIME_ALERTS=$((RESPONSE_TIME_ALERTS + 1))
        warning "Slow response time: ${response_time_ms}ms (threshold: ${RESPONSE_TIME_THRESHOLD}ms)"
        
        if [ $RESPONSE_TIME_ALERTS -ge 3 ]; then
            send_alert "HIGH" "Response time consistently slow: ${response_time_ms}ms (3+ consecutive alerts)"
        fi
    else
        RESPONSE_TIME_ALERTS=0  # Reset counter on good response
        log "Response time: ${response_time_ms}ms ✓"
    fi
    
    echo "$response_time_ms"
}

# Check error rate
check_error_rate() {
    local log_file="$PROJECT_ROOT/storage/logs/laravel.log"
    local error_rate=0
    
    if [ -f "$log_file" ]; then
        local recent_lines=100
        local error_count=$(tail -$recent_lines "$log_file" | grep -c "ERROR\|CRITICAL" || echo "0")
        
        if [ $recent_lines -gt 0 ]; then
            error_rate=$(echo "scale=2; $error_count * 100 / $recent_lines" | bc)
        fi
        
        if [ "$(echo "$error_rate > $ERROR_RATE_THRESHOLD" | bc)" -eq 1 ]; then
            ERROR_RATE_ALERTS=$((ERROR_RATE_ALERTS + 1))
            warning "High error rate: ${error_rate}% (threshold: ${ERROR_RATE_THRESHOLD}%)"
            
            if [ $ERROR_RATE_ALERTS -ge 2 ]; then
                send_alert "HIGH" "Error rate consistently high: ${error_rate}% (2+ consecutive alerts)"
            fi
        else
            ERROR_RATE_ALERTS=0  # Reset counter
            log "Error rate: ${error_rate}% ✓"
        fi
    else
        warning "Log file not found: $log_file"
    fi
    
    echo "$error_rate"
}

# Check system health
check_system_health() {
    local health_status="unknown"
    
    # Try to get health status from application
    if php artisan health:check --quiet 2>/dev/null; then
        health_status="healthy"
        HEALTH_CHECK_FAILURES=0
        log "System health: healthy ✓"
    else
        health_status="unhealthy"
        HEALTH_CHECK_FAILURES=$((HEALTH_CHECK_FAILURES + 1))
        warning "System health check failed (failure #${HEALTH_CHECK_FAILURES})"
        
        if [ $HEALTH_CHECK_FAILURES -ge 3 ]; then
            send_alert "CRITICAL" "System health checks failing (${HEALTH_CHECK_FAILURES} consecutive failures)"
        fi
    fi
    
    echo "$health_status"
}

# Check memory usage
check_memory_usage() {
    local memory_info
    memory_info=$(free | grep Mem)
    local total=$(echo "$memory_info" | awk '{print $2}')
    local used=$(echo "$memory_info" | awk '{print $3}')
    local percentage=$(echo "scale=1; $used * 100 / $total" | bc)
    
    if [ "$(echo "$percentage > $MEMORY_THRESHOLD" | bc)" -eq 1 ]; then
        warning "High memory usage: ${percentage}% (threshold: ${MEMORY_THRESHOLD}%)"
        
        if [ "$(echo "$percentage > 95" | bc)" -eq 1 ]; then
            send_alert "HIGH" "Critical memory usage: ${percentage}%"
        fi
    else
        log "Memory usage: ${percentage}% ✓"
    fi
    
    echo "$percentage"
}

# Check CPU usage
check_cpu_usage() {
    local cpu_usage
    cpu_usage=$(top -bn1 | grep "Cpu(s)" | sed "s/.*, *\([0-9.]*\)%* id.*/\1/" | awk '{print 100 - $1}')
    
    if [ "$(echo "$cpu_usage > $CPU_THRESHOLD" | bc)" -eq 1 ]; then
        warning "High CPU usage: ${cpu_usage}% (threshold: ${CPU_THRESHOLD}%)"
        
        if [ "$(echo "$cpu_usage > 95" | bc)" -eq 1 ]; then
            send_alert "HIGH" "Critical CPU usage: ${cpu_usage}%"
        fi
    else
        log "CPU usage: ${cpu_usage}% ✓"
    fi
    
    echo "$cpu_usage"
}

# Check queue size
check_queue_size() {
    local queue_size=0
    
    if queue_size=$(php artisan tinker --execute="echo DB::table('jobs')->count();" 2>/dev/null | tail -1); then
        if [ "$queue_size" -gt "$QUEUE_SIZE_THRESHOLD" ]; then
            warning "Large queue backlog: $queue_size jobs (threshold: $QUEUE_SIZE_THRESHOLD)"
            
            if [ "$queue_size" -gt $((QUEUE_SIZE_THRESHOLD * 2)) ]; then
                send_alert "HIGH" "Critical queue backlog: $queue_size jobs"
            fi
        else
            log "Queue size: $queue_size jobs ✓"
        fi
    else
        warning "Failed to check queue size"
        queue_size=0
    fi
    
    echo "$queue_size"
}

# Check database connectivity
check_database() {
    if php artisan tinker --execute="DB::select('SELECT 1');" >/dev/null 2>&1; then
        log "Database connectivity: OK ✓"
        echo "ok"
    else
        error "Database connectivity failed"
        send_alert "CRITICAL" "Database connectivity lost"
        echo "failed"
    fi
}

# Check Redis connectivity
check_redis() {
    if php artisan tinker --execute="Redis::ping();" 2>/dev/null | grep -q "PONG"; then
        log "Redis connectivity: OK ✓"
        echo "ok"
    else
        warning "Redis connectivity failed"
        echo "failed"
    fi
}

# Generate monitoring report
generate_monitoring_report() {
    local timestamp="$1"
    local response_time="$2"
    local error_rate="$3"
    local health_status="$4"
    local memory_usage="$5"
    local cpu_usage="$6"
    local queue_size="$7"
    local db_status="$8"
    local redis_status="$9"
    
    cat > "$PROJECT_ROOT/storage/deployment/monitoring-snapshot-${DEPLOYMENT_ID}-$(date +%s).json" << EOF
{
    "deployment_id": "$DEPLOYMENT_ID",
    "timestamp": "$timestamp",
    "metrics": {
        "response_time_ms": $response_time,
        "error_rate_percent": $error_rate,
        "system_health": "$health_status",
        "memory_usage_percent": $memory_usage,
        "cpu_usage_percent": $cpu_usage,
        "queue_size": $queue_size,
        "database_status": "$db_status",
        "redis_status": "$redis_status"
    },
    "thresholds": {
        "response_time_ms": $RESPONSE_TIME_THRESHOLD,
        "error_rate_percent": $ERROR_RATE_THRESHOLD,
        "memory_usage_percent": $MEMORY_THRESHOLD,
        "cpu_usage_percent": $CPU_THRESHOLD,
        "queue_size": $QUEUE_SIZE_THRESHOLD
    },
    "alert_counts": {
        "response_time_alerts": $RESPONSE_TIME_ALERTS,
        "error_rate_alerts": $ERROR_RATE_ALERTS,
        "health_check_failures": $HEALTH_CHECK_FAILURES
    }
}
EOF
}

# Feature flag health check
check_feature_flags() {
    local flags_healthy=true
    
    if php artisan feature health --quiet 2>/dev/null; then
        log "Feature flags: Healthy ✓"
    else
        warning "Feature flag system issues detected"
        flags_healthy=false
    fi
    
    echo "$flags_healthy"
}

# Main monitoring loop
main_monitoring_loop() {
    local end_time=$((START_TIME + MONITORING_DURATION))
    local check_interval=60  # Check every minute
    local iteration=0
    
    log "🔍 Starting post-deployment monitoring for deployment: $DEPLOYMENT_ID"
    log "⏱️ Monitoring duration: $MONITORING_DURATION seconds"
    log "📊 Check interval: $check_interval seconds"
    
    while [ $(date +%s) -lt $end_time ]; do
        iteration=$((iteration + 1))
        local timestamp=$(date -Iseconds)
        
        log "\n📊 Monitoring Check #$iteration ($(date))"
        
        # Run all checks
        local response_time=$(check_response_time)
        local error_rate=$(check_error_rate)
        local health_status=$(check_system_health)
        local memory_usage=$(check_memory_usage)
        local cpu_usage=$(check_cpu_usage)
        local queue_size=$(check_queue_size)
        local db_status=$(check_database)
        local redis_status=$(check_redis)
        local flags_healthy=$(check_feature_flags)
        
        # Generate monitoring snapshot
        generate_monitoring_report "$timestamp" "$response_time" "$error_rate" \
            "$health_status" "$memory_usage" "$cpu_usage" "$queue_size" \
            "$db_status" "$redis_status"
        
        # Overall health assessment
        local overall_status="healthy"
        if [ "$health_status" != "healthy" ] || [ "$db_status" != "ok" ] || \
           [ "$HEALTH_CHECK_FAILURES" -ge 3 ] || [ "$ERROR_RATE_ALERTS" -ge 3 ]; then
            overall_status="unhealthy"
        elif [ "$RESPONSE_TIME_ALERTS" -gt 0 ] || [ "$ERROR_RATE_ALERTS" -gt 0 ] || \
             [ "$redis_status" != "ok" ]; then
            overall_status="warning"
        fi
        
        log "Overall Status: $overall_status"
        
        # Critical failure check
        if [ "$overall_status" = "unhealthy" ]; then
            error "System in unhealthy state - consider rollback"
            send_alert "CRITICAL" "System monitoring detected unhealthy state after deployment"
            
            # Check if we should trigger automatic rollback
            if [ "$HEALTH_CHECK_FAILURES" -ge 5 ]; then
                error "Multiple consecutive health check failures - triggering emergency rollback"
                "$SCRIPT_DIR/emergency-rollback.sh" "$DEPLOYMENT_ID" "Automatic rollback due to monitoring failures"
                exit 1
            fi
        fi
        
        # Sleep until next check
        sleep $check_interval
    done
}

# Generate final monitoring report
generate_final_report() {
    local monitoring_end=$(date +%s)
    local total_duration=$((monitoring_end - START_TIME))
    
    log "\n📋 FINAL MONITORING REPORT"
    log "================================"
    log "Deployment ID: $DEPLOYMENT_ID"
    log "Monitoring Duration: $total_duration seconds"
    log "Total Response Time Alerts: $RESPONSE_TIME_ALERTS"
    log "Total Error Rate Alerts: $ERROR_RATE_ALERTS"
    log "Total Health Check Failures: $HEALTH_CHECK_FAILURES"
    
    # Create final report
    cat > "$PROJECT_ROOT/storage/deployment/final-monitoring-report-${DEPLOYMENT_ID}.json" << EOF
{
    "deployment_id": "$DEPLOYMENT_ID",
    "monitoring_period": {
        "start": "$(date -d @$START_TIME -Iseconds)",
        "end": "$(date -Iseconds)",
        "duration_seconds": $total_duration
    },
    "summary": {
        "response_time_alerts": $RESPONSE_TIME_ALERTS,
        "error_rate_alerts": $ERROR_RATE_ALERTS,
        "health_check_failures": $HEALTH_CHECK_FAILURES,
        "overall_status": "$([ $HEALTH_CHECK_FAILURES -lt 3 ] && echo 'stable' || echo 'unstable')"
    },
    "thresholds": {
        "response_time_ms": $RESPONSE_TIME_THRESHOLD,
        "error_rate_percent": $ERROR_RATE_THRESHOLD,
        "memory_usage_percent": $MEMORY_THRESHOLD,
        "cpu_usage_percent": $CPU_THRESHOLD,
        "queue_size": $QUEUE_SIZE_THRESHOLD
    },
    "recommendations": [
        $([ $RESPONSE_TIME_ALERTS -gt 0 ] && echo "\"Investigate response time performance\"," || echo "")
        $([ $ERROR_RATE_ALERTS -gt 0 ] && echo "\"Review error logs for recurring issues\"," || echo "")
        $([ $HEALTH_CHECK_FAILURES -gt 0 ] && echo "\"Monitor system health closely\"," || echo "")
        "\"Continue standard monitoring\""
    ]
}
EOF
    
    success "Final monitoring report generated"
    
    # Cleanup old snapshots (keep last 50)
    find "$PROJECT_ROOT/storage/deployment" -name "monitoring-snapshot-*.json" -type f | \
        sort -r | tail -n +51 | xargs rm -f 2>/dev/null || true
}

# Cleanup and exit
cleanup_monitoring() {
    local exit_code=$?
    
    generate_final_report
    
    if [ $exit_code -eq 0 ]; then
        success "✅ Post-deployment monitoring completed successfully"
        success "🕐 Switching to standard monitoring intervals"
    else
        error "❌ Post-deployment monitoring ended with issues"
        warning "🔍 Review monitoring logs and reports"
    fi
    
    log "Monitoring session ended at $(date)"
}
trap cleanup_monitoring EXIT

# Main execution
log "🚀 Post-deployment monitoring started for deployment: $DEPLOYMENT_ID"

# Change to project root
cd "$PROJECT_ROOT"

# Run main monitoring loop
main_monitoring_loop

success "🎉 Post-deployment monitoring completed successfully"