#!/bin/bash

# Performance Monitoring Script for AskPro AI Gateway
# Runs every 5 minutes via cron

LOGFILE="/var/www/api-gateway/storage/logs/performance.log"
DATE=$(date '+%Y-%m-%d %H:%M:%S')

# Function to log metrics
log_metric() {
    echo "[$DATE] $1" >> $LOGFILE
}

# 1. System Load
LOAD=$(uptime | awk -F'load average:' '{print $2}')
log_metric "LOAD_AVERAGE: $LOAD"

# 2. Memory Usage
MEM_TOTAL=$(free -m | awk 'NR==2{print $2}')
MEM_USED=$(free -m | awk 'NR==2{print $3}')
MEM_PERCENT=$((MEM_USED * 100 / MEM_TOTAL))
log_metric "MEMORY: ${MEM_USED}MB / ${MEM_TOTAL}MB (${MEM_PERCENT}%)"

# 3. Disk Usage
DISK_USAGE=$(df -h /var/www | awk 'NR==2{print $5}' | sed 's/%//')
log_metric "DISK_USAGE: ${DISK_USAGE}%"

# 4. PHP-FPM Processes
PHP_PROCESSES=$(ps aux | grep php-fpm | grep -v grep | wc -l)
log_metric "PHP_FPM_PROCESSES: $PHP_PROCESSES"

# 5. MySQL Connections
MYSQL_CONN=$(mysql -u askproai_user -pAskPro2025Secure -e "SHOW STATUS LIKE 'Threads_connected';" 2>/dev/null | awk 'NR==2{print $2}')
log_metric "MYSQL_CONNECTIONS: $MYSQL_CONN"

# 6. Redis Memory
REDIS_MEM=$(redis-cli info memory 2>/dev/null | grep used_memory_human | cut -d: -f2 | tr -d '\r')
log_metric "REDIS_MEMORY: $REDIS_MEM"

# 7. Laravel Queue Size
QUEUE_SIZE=$(mysql -u askproai_user -pAskPro2025Secure askproai_db -e "SELECT COUNT(*) FROM jobs;" 2>/dev/null | awk 'NR==2{print $1}')
log_metric "QUEUE_SIZE: $QUEUE_SIZE"

# 8. Response Time Test
RESPONSE_TIME=$(curl -o /dev/null -s -w '%{time_total}' https://api.askproai.de/api/health)
log_metric "HEALTH_CHECK_TIME: ${RESPONSE_TIME}s"

# 9. Error Count (last 5 minutes)
ERROR_COUNT=$(tail -1000 /var/www/api-gateway/storage/logs/laravel.log 2>/dev/null | grep -c "ERROR" || echo 0)
log_metric "ERROR_COUNT_5MIN: $ERROR_COUNT"

# Alert if critical thresholds exceeded
if [ $MEM_PERCENT -gt 90 ]; then
    echo "ALERT: Memory usage critical at ${MEM_PERCENT}%" | tee -a $LOGFILE
fi

if [ $DISK_USAGE -gt 85 ]; then
    echo "ALERT: Disk usage critical at ${DISK_USAGE}%" | tee -a $LOGFILE
fi

if [ "$ERROR_COUNT" -gt 50 ]; then
    echo "ALERT: High error rate detected: ${ERROR_COUNT} errors" | tee -a $LOGFILE
fi

echo "[$DATE] Performance check completed" >> $LOGFILE
echo "---" >> $LOGFILE