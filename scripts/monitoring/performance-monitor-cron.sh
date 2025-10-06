#!/bin/bash
LOG_FILE="/var/www/api-gateway/storage/monitoring/performance.log"
ALERT_FILE="/var/www/api-gateway/storage/monitoring/alerts.log"

echo "====== Performance Check: $(date) ======" >> "$LOG_FILE"

# Check CPU usage
cpu_usage=$(top -bn1 | grep "Cpu(s)" | sed "s/.*, *\([0-9.]*\)%* id.*/\1/" | awk '{print int(100 - $1)}')
echo "CPU Usage: ${cpu_usage}%" >> "$LOG_FILE"

if [ "$cpu_usage" -gt 80 ]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] High CPU usage: ${cpu_usage}%" >> "$ALERT_FILE"
fi

# Check memory
mem_percent=$(free -m | grep "^Mem" | awk '{printf "%d", $3*100/$2}')
echo "Memory Usage: ${mem_percent}%" >> "$LOG_FILE"

if [ "$mem_percent" -gt 85 ]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] High memory usage: ${mem_percent}%" >> "$ALERT_FILE"
fi

# Check response time
response_time=$(curl -s -o /dev/null -w "%{time_total}" http://localhost/admin 2>/dev/null)
echo "Dashboard Response: ${response_time}s" >> "$LOG_FILE"

# Check database connections
connections=$(mysql -u root askproai_db -sN -e "SHOW STATUS LIKE 'Threads_connected'" 2>/dev/null | awk '{print $2}')
echo "DB Connections: $connections" >> "$LOG_FILE"

echo "" >> "$LOG_FILE"
