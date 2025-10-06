#!/bin/bash

# Automated Health Check & Alert System
# Runs every 5 minutes via cron

LOG_FILE="/var/www/api-gateway/storage/logs/health-check.log"
ALERT_FILE="/var/www/api-gateway/storage/logs/health-alerts.log"
BASE_URL="https://api.askproai.de"

# Colors for terminal output (if run manually)
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Function to log with timestamp
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
    if [ -t 1 ]; then
        echo -e "$1"
    fi
}

# Function to send alert
send_alert() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ALERT: $1" >> "$ALERT_FILE"
    # In production, this would send email/SMS/Slack notification
    # For now, just log it
}

# Initialize
HEALTH_SCORE=100
ISSUES=0

log_message "Starting automated health check..."

# Check 1: Web Server Response
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/business/login")
if [ "$HTTP_CODE" = "200" ]; then
    log_message "${GREEN}✓${NC} Web server: OK (HTTP $HTTP_CODE)"
else
    log_message "${RED}✗${NC} Web server: Failed (HTTP $HTTP_CODE)"
    HEALTH_SCORE=$((HEALTH_SCORE - 25))
    ISSUES=$((ISSUES + 1))
    send_alert "Web server returning HTTP $HTTP_CODE"
fi

# Check 2: Database Connection
DB_CHECK=$(php -r "
    require '/var/www/api-gateway/vendor/autoload.php';
    \$app = require '/var/www/api-gateway/bootstrap/app.php';
    \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    try {
        DB::select('SELECT 1');
        echo 'OK';
    } catch (Exception \$e) {
        echo 'FAILED';
    }
" 2>/dev/null)

if [ "$DB_CHECK" = "OK" ]; then
    log_message "${GREEN}✓${NC} Database: Connected"
else
    log_message "${RED}✗${NC} Database: Connection failed"
    HEALTH_SCORE=$((HEALTH_SCORE - 25))
    ISSUES=$((ISSUES + 1))
    send_alert "Database connection failed"
fi

# Check 3: Redis Connection
REDIS_CHECK=$(redis-cli ping 2>/dev/null)
if [ "$REDIS_CHECK" = "PONG" ]; then
    log_message "${GREEN}✓${NC} Redis: Connected"
else
    log_message "${RED}✗${NC} Redis: Connection failed"
    HEALTH_SCORE=$((HEALTH_SCORE - 15))
    ISSUES=$((ISSUES + 1))
    send_alert "Redis connection failed"
fi

# Check 4: Disk Space
DISK_USAGE=$(df / | awk 'NR==2 {print int($5)}')
if [ "$DISK_USAGE" -lt 80 ]; then
    log_message "${GREEN}✓${NC} Disk space: ${DISK_USAGE}% used"
elif [ "$DISK_USAGE" -lt 90 ]; then
    log_message "${YELLOW}⚠${NC} Disk space: ${DISK_USAGE}% used (warning)"
    HEALTH_SCORE=$((HEALTH_SCORE - 10))
    send_alert "Disk usage at ${DISK_USAGE}% (warning)"
else
    log_message "${RED}✗${NC} Disk space: ${DISK_USAGE}% used (critical)"
    HEALTH_SCORE=$((HEALTH_SCORE - 20))
    ISSUES=$((ISSUES + 1))
    send_alert "Disk usage at ${DISK_USAGE}% (CRITICAL)"
fi

# Check 5: Memory Usage
MEM_USAGE=$(free | grep Mem | awk '{print int($3/$2 * 100)}')
if [ "$MEM_USAGE" -lt 80 ]; then
    log_message "${GREEN}✓${NC} Memory: ${MEM_USAGE}% used"
elif [ "$MEM_USAGE" -lt 90 ]; then
    log_message "${YELLOW}⚠${NC} Memory: ${MEM_USAGE}% used (warning)"
    HEALTH_SCORE=$((HEALTH_SCORE - 5))
else
    log_message "${RED}✗${NC} Memory: ${MEM_USAGE}% used (critical)"
    HEALTH_SCORE=$((HEALTH_SCORE - 15))
    ISSUES=$((ISSUES + 1))
    send_alert "Memory usage at ${MEM_USAGE}% (CRITICAL)"
fi

# Check 6: PHP-FPM Status
PHP_FPM_STATUS=$(systemctl is-active php8.3-fpm)
if [ "$PHP_FPM_STATUS" = "active" ]; then
    log_message "${GREEN}✓${NC} PHP-FPM: Active"
else
    log_message "${RED}✗${NC} PHP-FPM: $PHP_FPM_STATUS"
    HEALTH_SCORE=$((HEALTH_SCORE - 20))
    ISSUES=$((ISSUES + 1))
    send_alert "PHP-FPM is $PHP_FPM_STATUS"

    # Attempt auto-recovery
    log_message "Attempting to restart PHP-FPM..."
    systemctl restart php8.3-fpm
    sleep 2
    if [ "$(systemctl is-active php8.3-fpm)" = "active" ]; then
        log_message "${GREEN}✓${NC} PHP-FPM: Recovered"
        send_alert "PHP-FPM automatically recovered"
    fi
fi

# Check 7: Error Log Size
ERROR_LOG="/var/www/api-gateway/storage/logs/laravel.log"
if [ -f "$ERROR_LOG" ]; then
    LOG_SIZE=$(du -m "$ERROR_LOG" | cut -f1)
    if [ "$LOG_SIZE" -gt 100 ]; then
        log_message "${YELLOW}⚠${NC} Error log size: ${LOG_SIZE}MB (consider rotation)"
        send_alert "Laravel log file is ${LOG_SIZE}MB"
    else
        log_message "${GREEN}✓${NC} Error log size: ${LOG_SIZE}MB"
    fi
fi

# Check 8: Recent Errors
if [ -f "$ERROR_LOG" ]; then
    RECENT_ERRORS=$(tail -100 "$ERROR_LOG" | grep -c "ERROR" || true)
    if [ "$RECENT_ERRORS" -gt 10 ]; then
        log_message "${YELLOW}⚠${NC} Recent errors: $RECENT_ERRORS in last 100 lines"
        HEALTH_SCORE=$((HEALTH_SCORE - 5))
        send_alert "High error rate: $RECENT_ERRORS errors in recent logs"
    else
        log_message "${GREEN}✓${NC} Error rate: Normal ($RECENT_ERRORS errors)"
    fi
fi

# Calculate final health score
if [ "$HEALTH_SCORE" -lt 0 ]; then
    HEALTH_SCORE=0
fi

# Summary
log_message "──────────────────────────────────"
log_message "Health Score: $HEALTH_SCORE/100"
log_message "Issues Found: $ISSUES"

# Determine overall status
if [ "$HEALTH_SCORE" -ge 90 ]; then
    STATUS="${GREEN}HEALTHY${NC}"
    STATUS_TEXT="HEALTHY"
elif [ "$HEALTH_SCORE" -ge 70 ]; then
    STATUS="${YELLOW}DEGRADED${NC}"
    STATUS_TEXT="DEGRADED"
else
    STATUS="${RED}CRITICAL${NC}"
    STATUS_TEXT="CRITICAL"
    send_alert "System health is CRITICAL (Score: $HEALTH_SCORE/100)"
fi

log_message "System Status: $STATUS_TEXT"

# Auto-recovery if critical
if [ "$HEALTH_SCORE" -lt 70 ] && [ "$1" = "--auto-recover" ]; then
    log_message "Initiating auto-recovery..."
    php /var/www/api-gateway/artisan system:recover --auto
fi

# Store health score for monitoring trends
echo "$HEALTH_SCORE" > /var/www/api-gateway/storage/logs/health-score.txt
echo "$(date '+%Y-%m-%d %H:%M:%S'),$HEALTH_SCORE,$STATUS_TEXT" >> /var/www/api-gateway/storage/logs/health-history.csv

# Exit with appropriate code
if [ "$HEALTH_SCORE" -lt 50 ]; then
    exit 2  # Critical
elif [ "$HEALTH_SCORE" -lt 90 ]; then
    exit 1  # Warning
else
    exit 0  # Healthy
fi