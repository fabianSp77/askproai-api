#!/bin/bash
# Improved Uptime Monitor for AskProAI with debugging
# Logs PID to identify overlapping processes

set -e

LOG_FILE="/var/www/api-gateway/storage/logs/uptime-monitor.log"
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')
ALERT_EMAIL="admin@askproai.de"
PID=$$

# Function to log messages with PID
log_message() {
    echo "[$TIMESTAMP] [PID:$PID] $1" >> "$LOG_FILE"
}

# Check if service is running
check_service() {
    local service=$1
    local url=$2
    local expected=$3
    
    if curl -s -o /dev/null -w "%{http_code}" "$url" | grep -q "$expected"; then
        log_message "✓ $service is UP"
        return 0
    else
        log_message "✗ $service is DOWN"
        return 1
    fi
}

# Start monitoring
log_message "=== Starting uptime check ==="

# Initialize status
all_good=true

# 1. Check main website (redirect to admin is OK)
http_code=$(curl -s -o /dev/null -w "%{http_code}" "https://api.askproai.de/")
if [[ "$http_code" =~ ^(200|301|302)$ ]]; then
    log_message "✓ Main Website is UP"
else
    log_message "✗ Main Website is DOWN (HTTP $http_code)"
    all_good=false
fi

# 2. Check Admin Panel (200, 301, 302 redirects sind OK)
http_code=$(curl -s -o /dev/null -w "%{http_code}" "https://api.askproai.de/admin")
if [[ "$http_code" =~ ^(200|301|302)$ ]]; then
    log_message "✓ Admin Panel is UP (HTTP $http_code)"
else
    log_message "✗ Admin Panel is DOWN (HTTP $http_code)"
    all_good=false
fi

# 3. Check API endpoint (use Laravel health endpoint)
if ! check_service "API Endpoint" "https://api.askproai.de/api/health" "200"; then
    # Fallback: Try alternative endpoint
    if ! check_service "API Endpoint (alt)" "https://api.askproai.de/api/status" "200"; then
        all_good=false
    fi
fi

# 4. Check MySQL
if mysqladmin -u askproai_user -p'lkZ57Dju9EDjrMxn' ping &>/dev/null; then
    log_message "✓ MySQL is UP"
else
    log_message "✗ MySQL is DOWN"
    all_good=false
fi

# 5. Check Redis
if redis-cli ping &>/dev/null; then
    log_message "✓ Redis is UP"
else
    log_message "✗ Redis is DOWN"
    all_good=false
fi

# 6. Check Horizon
if php /var/www/api-gateway/artisan horizon:status | grep -q "running"; then
    log_message "✓ Horizon is UP"
else
    log_message "✗ Horizon is DOWN"
    all_good=false
fi

# 7. Check disk space
DISK_USAGE=$(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')
if [ "$DISK_USAGE" -lt 90 ]; then
    log_message "✓ Disk space OK ($DISK_USAGE% used)"
else
    log_message "✗ Disk space WARNING ($DISK_USAGE% used)"
    all_good=false
fi

# Summary
if [ "$all_good" = true ]; then
    log_message "=== All systems operational ==="
else
    log_message "=== ALERT: Some services are down ==="
fi

# Keep only last 1000 lines in log
tail -n 1000 "$LOG_FILE" > "$LOG_FILE.tmp" && mv "$LOG_FILE.tmp" "$LOG_FILE"
