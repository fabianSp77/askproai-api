#!/bin/bash

# AskProAI Health Monitoring Script
# Version: 1.0.0
# Description: Automated health checks and monitoring for AskProAI system

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
LOG_FILE="/var/www/api-gateway/storage/logs/health-monitor.log"
ERROR_THRESHOLD=10
PERFORMANCE_THRESHOLD=500 # milliseconds

# Load secure credentials
BACKUP_CONFIG="/var/www/api-gateway/.env.backup"
if [ -f "$BACKUP_CONFIG" ]; then
    export $(grep -E "^DB_BACKUP_" "$BACKUP_CONFIG" | xargs)
fi

# Set database credentials from secure storage
DB_USER="${DB_BACKUP_USERNAME:-askproai_user}"
DB_PASS="${DB_BACKUP_PASSWORD}"
DB_NAME="${DB_BACKUP_DATABASE:-askproai_db}"
DB_HOST="${DB_BACKUP_HOST:-127.0.0.1}"

# Function to log messages
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> $LOG_FILE
    echo -e "$1"
}

# Function to check HTTP endpoints
check_http_endpoints() {
    log_message "${GREEN}=== HTTP Endpoint Tests ===${NC}"
    
    local endpoints=(
        "https://api.askproai.de/admin/login|200|Admin Login"
        "https://api.askproai.de/api/health|200|API Health"
    )
    
    local failed=0
    
    for endpoint_data in "${endpoints[@]}"; do
        IFS='|' read -r url expected_code name <<< "$endpoint_data"
        http_code=$(curl -s -o /dev/null -w "%{http_code}" "$url")
        
        if [ "$http_code" == "$expected_code" ]; then
            log_message "${GREEN}✓${NC} $name: $http_code"
        else
            log_message "${RED}✗${NC} $name: $http_code (expected $expected_code)"
            ((failed++))
        fi
    done
    
    return $failed
}

# Function to check database connectivity
check_database() {
    log_message "${GREEN}=== Database Tests ===${NC}"
    
    result=$(mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT COUNT(*) as count FROM customers;" 2>&1)
    
    if [ $? -eq 0 ]; then
        log_message "${GREEN}✓${NC} Database connection: OK"
        
        # Check table counts
        mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
        SELECT 'customers' as tbl, COUNT(*) as cnt FROM customers
        UNION SELECT 'calls', COUNT(*) FROM calls
        UNION SELECT 'appointments', COUNT(*) FROM appointments;" 2>&1 | tail -n +2 | while read line; do
            log_message "  $line"
        done
        return 0
    else
        log_message "${RED}✗${NC} Database connection: FAILED"
        return 1
    fi
}

# Function to check error logs
check_error_logs() {
    log_message "${GREEN}=== Error Log Analysis ===${NC}"
    
    # Count errors in last hour
    error_count=$(grep "ERROR" /var/www/api-gateway/storage/logs/laravel.log | grep "$(date '+%Y-%m-%d %H')" | wc -l)
    
    if [ $error_count -lt $ERROR_THRESHOLD ]; then
        log_message "${GREEN}✓${NC} Errors in last hour: $error_count (threshold: $ERROR_THRESHOLD)"
        return 0
    else
        log_message "${RED}✗${NC} Errors in last hour: $error_count (threshold: $ERROR_THRESHOLD)"
        
        # Show top error types
        log_message "${YELLOW}Top error types:${NC}"
        grep "ERROR" /var/www/api-gateway/storage/logs/laravel.log | grep "$(date '+%Y-%m-%d %H')" | \
            awk -F'ERROR:' '{print $2}' | cut -d' ' -f1-5 | sort | uniq -c | sort -rn | head -5 | while read line; do
            log_message "  $line"
        done
        return 1
    fi
}

# Function to check performance
check_performance() {
    log_message "${GREEN}=== Performance Tests ===${NC}"
    
    # Test database query performance
    start_time=$(date +%s%3N)
    mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
    SELECT c.*, COUNT(DISTINCT calls.id) as call_count, COUNT(DISTINCT a.id) as appointment_count
    FROM customers c
    LEFT JOIN calls ON calls.customer_id = c.id
    LEFT JOIN appointments a ON a.customer_id = c.id
    GROUP BY c.id
    LIMIT 10;" > /dev/null 2>&1
    end_time=$(date +%s%3N)
    
    query_time=$((end_time - start_time))
    
    if [ $query_time -lt $PERFORMANCE_THRESHOLD ]; then
        log_message "${GREEN}✓${NC} Complex query performance: ${query_time}ms (threshold: ${PERFORMANCE_THRESHOLD}ms)"
        return 0
    else
        log_message "${RED}✗${NC} Complex query performance: ${query_time}ms (threshold: ${PERFORMANCE_THRESHOLD}ms)"
        return 1
    fi
}

# Function to check services
check_services() {
    log_message "${GREEN}=== Service Status ===${NC}"
    
    services=("nginx" "php8.3-fpm" "mysql" "redis-server")
    failed=0
    
    for service in "${services[@]}"; do
        if systemctl is-active --quiet $service; then
            log_message "${GREEN}✓${NC} $service: Running"
        else
            log_message "${RED}✗${NC} $service: Not running"
            ((failed++))
        fi
    done
    
    return $failed
}

# Function to check disk space
check_disk_space() {
    log_message "${GREEN}=== Disk Space ===${NC}"
    
    usage=$(df -h /var/www/api-gateway | awk 'NR==2 {print $5}' | sed 's/%//')
    
    if [ $usage -lt 80 ]; then
        log_message "${GREEN}✓${NC} Disk usage: ${usage}%"
        return 0
    elif [ $usage -lt 90 ]; then
        log_message "${YELLOW}⚠${NC} Disk usage: ${usage}% (warning)"
        return 1
    else
        log_message "${RED}✗${NC} Disk usage: ${usage}% (critical)"
        return 2
    fi
}

# Main execution
main() {
    log_message "${GREEN}========================================${NC}"
    log_message "${GREEN}AskProAI Health Check - $(date)${NC}"
    log_message "${GREEN}========================================${NC}"
    
    total_issues=0
    
    check_http_endpoints
    total_issues=$((total_issues + $?))
    
    check_database
    total_issues=$((total_issues + $?))
    
    check_error_logs
    total_issues=$((total_issues + $?))
    
    check_performance
    total_issues=$((total_issues + $?))
    
    check_services
    total_issues=$((total_issues + $?))
    
    check_disk_space
    total_issues=$((total_issues + $?))
    
    log_message "${GREEN}========================================${NC}"
    
    if [ $total_issues -eq 0 ]; then
        log_message "${GREEN}✓ System Health: EXCELLENT${NC}"
        exit 0
    elif [ $total_issues -lt 3 ]; then
        log_message "${YELLOW}⚠ System Health: WARNING ($total_issues issues)${NC}"
        exit 1
    else
        log_message "${RED}✗ System Health: CRITICAL ($total_issues issues)${NC}"
        exit 2
    fi
}

# Run main function
main