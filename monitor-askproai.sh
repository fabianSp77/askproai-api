#!/bin/bash

# AskProAI Monitoring Script
# Runs every 5 minutes via cron to check system health

LOG_FILE="/var/log/askproai-monitor.log"
ALERT_EMAIL="admin@askproai.de"
WEBHOOK_URL="" # Optional: Slack/Discord webhook

# Function to log messages
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

# Function to send alert
send_alert() {
    local message=$1
    local severity=$2
    
    # Log to file
    log_message "ALERT [$severity]: $message"
    
    # Send email (if configured)
    if [ -n "$ALERT_EMAIL" ]; then
        echo "$message" | mail -s "AskProAI Alert [$severity]" "$ALERT_EMAIL"
    fi
    
    # Send to webhook (if configured)
    if [ -n "$WEBHOOK_URL" ]; then
        curl -X POST "$WEBHOOK_URL" \
            -H "Content-Type: application/json" \
            -d "{\"text\":\"AskProAI Alert [$severity]: $message\"}" \
            2>/dev/null
    fi
}

# Check 1: PHP-FPM Status
check_php_fpm() {
    if ! systemctl is-active --quiet php8.3-fpm; then
        send_alert "PHP-FPM is not running!" "CRITICAL"
        systemctl start php8.3-fpm
        log_message "Attempted to restart PHP-FPM"
    fi
}

# Check 2: MySQL Status
check_mysql() {
    if ! mysqladmin ping -h localhost --silent; then
        send_alert "MySQL is not responding!" "CRITICAL"
    fi
}

# Check 3: Redis Status
check_redis() {
    if ! redis-cli ping > /dev/null 2>&1; then
        send_alert "Redis is not responding!" "CRITICAL"
        systemctl restart redis-server
        log_message "Attempted to restart Redis"
    fi
}

# Check 4: Horizon Status
check_horizon() {
    cd /var/www/api-gateway
    horizon_status=$(php artisan horizon:status 2>&1)
    
    if [[ ! "$horizon_status" =~ "Horizon is running" ]]; then
        send_alert "Laravel Horizon is not running!" "HIGH"
        php artisan horizon:terminate
        nohup php artisan horizon > /dev/null 2>&1 &
        log_message "Attempted to restart Horizon"
    fi
}

# Check 5: Disk Space
check_disk_space() {
    disk_usage=$(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')
    
    if [ "$disk_usage" -gt 90 ]; then
        send_alert "Disk space critical: ${disk_usage}% used!" "CRITICAL"
    elif [ "$disk_usage" -gt 80 ]; then
        send_alert "Disk space warning: ${disk_usage}% used!" "WARNING"
    fi
}

# Check 6: Memory Usage
check_memory() {
    memory_usage=$(free | grep Mem | awk '{print int($3/$2 * 100)}')
    
    if [ "$memory_usage" -gt 90 ]; then
        send_alert "Memory usage critical: ${memory_usage}%!" "CRITICAL"
    elif [ "$memory_usage" -gt 80 ]; then
        send_alert "Memory usage warning: ${memory_usage}%!" "WARNING"
    fi
}

# Check 7: API Response Time
check_api_response() {
    response_time=$(curl -o /dev/null -s -w '%{time_total}' https://api.askproai.de/api/health)
    response_code=$(curl -o /dev/null -s -w '%{http_code}' https://api.askproai.de/api/health)
    
    if [ "$response_code" != "200" ]; then
        send_alert "API health check failed! HTTP $response_code" "CRITICAL"
    elif (( $(echo "$response_time > 5" | bc -l) )); then
        send_alert "API response slow: ${response_time}s" "WARNING"
    fi
}

# Check 8: Failed Jobs
check_failed_jobs() {
    cd /var/www/api-gateway
    failed_jobs=$(php artisan queue:failed | grep -c "Failed")
    
    if [ "$failed_jobs" -gt 10 ]; then
        send_alert "High number of failed jobs: $failed_jobs" "WARNING"
    fi
}

# Check 9: Error Logs
check_error_logs() {
    # Check for recent errors (last 5 minutes)
    recent_errors=$(grep "ERROR" /var/www/api-gateway/storage/logs/laravel.log | \
                   grep "$(date '+%Y-%m-%d %H')" | \
                   tail -20 | wc -l)
    
    if [ "$recent_errors" -gt 50 ]; then
        send_alert "High error rate: $recent_errors errors in last hour" "WARNING"
    fi
}

# Main monitoring function
main() {
    log_message "Starting monitoring checks..."
    
    check_php_fpm
    check_mysql
    check_redis
    check_horizon
    check_disk_space
    check_memory
    check_api_response
    check_failed_jobs
    check_error_logs
    
    log_message "Monitoring checks completed"
}

# Run main function
main