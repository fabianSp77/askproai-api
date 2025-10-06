#!/bin/bash

# ========================================
# SETUP CONTINUOUS MONITORING
# Configure cron jobs for automated monitoring
# ========================================

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}SETTING UP CONTINUOUS MONITORING${NC}"
echo "======================================"

# Create log directory
LOG_DIR="/var/www/api-gateway/storage/monitoring"
mkdir -p "$LOG_DIR"
chown -R www-data:www-data "$LOG_DIR"

# Create monitoring scripts directory
SCRIPTS_DIR="/var/www/api-gateway/scripts/monitoring"
mkdir -p "$SCRIPTS_DIR"

# 1. Create health check wrapper
cat > "$SCRIPTS_DIR/health-check-cron.sh" << 'EOF'
#!/bin/bash
LOG_FILE="/var/www/api-gateway/storage/monitoring/health-check.log"
ALERT_FILE="/var/www/api-gateway/storage/monitoring/alerts.log"

# Run health check
/var/www/api-gateway/tests/quick-health-check.sh >> "$LOG_FILE" 2>&1
EXIT_CODE=$?

# If issues found, log alert
if [ $EXIT_CODE -ne 0 ]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Health check failed with $EXIT_CODE issues" >> "$ALERT_FILE"

    # Optional: Send alert (customize as needed)
    # echo "Health check failed" | mail -s "Alert: AskPro AI Health Check Failed" admin@example.com
fi

# Keep only last 7 days of logs
find /var/www/api-gateway/storage/monitoring -name "*.log" -mtime +7 -delete
EOF

# 2. Create performance monitor wrapper
cat > "$SCRIPTS_DIR/performance-monitor-cron.sh" << 'EOF'
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
EOF

# 3. Create database integrity check wrapper
cat > "$SCRIPTS_DIR/database-check-cron.sh" << 'EOF'
#!/bin/bash
LOG_FILE="/var/www/api-gateway/storage/monitoring/database-integrity.log"
ALERT_FILE="/var/www/api-gateway/storage/monitoring/alerts.log"

echo "====== Database Check: $(date) ======" >> "$LOG_FILE"

# Check for orphaned records
orphaned_calls=$(mysql -u root askproai_db -sN -e "SELECT COUNT(*) FROM calls WHERE staff_id IS NOT NULL AND staff_id NOT IN (SELECT id FROM staff)" 2>/dev/null)

if [ "$orphaned_calls" -gt 0 ]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Found $orphaned_calls orphaned call records" >> "$ALERT_FILE"
    echo "Orphaned calls: $orphaned_calls" >> "$LOG_FILE"
fi

# Check for duplicate emails
duplicates=$(mysql -u root askproai_db -sN -e "SELECT COUNT(*) FROM (SELECT email, COUNT(*) as cnt FROM customers WHERE email IS NOT NULL GROUP BY email HAVING cnt > 1) as t" 2>/dev/null)

if [ "$duplicates" -gt 0 ]; then
    echo "Duplicate customer emails: $duplicates" >> "$LOG_FILE"
fi

# Check slow queries
slow_queries=$(mysql -u root askproai_db -sN -e "SHOW STATUS LIKE 'Slow_queries'" 2>/dev/null | awk '{print $2}')
echo "Slow queries: $slow_queries" >> "$LOG_FILE"

echo "" >> "$LOG_FILE"
EOF

# 4. Create error monitor wrapper
cat > "$SCRIPTS_DIR/error-monitor-cron.sh" << 'EOF'
#!/bin/bash
LOG_FILE="/var/www/api-gateway/storage/monitoring/error-monitor.log"
ALERT_FILE="/var/www/api-gateway/storage/monitoring/alerts.log"
LARAVEL_LOG="/var/www/api-gateway/storage/logs/laravel.log"

echo "====== Error Monitor: $(date) ======" >> "$LOG_FILE"

# Count errors in last 15 minutes
if [ -f "$LARAVEL_LOG" ]; then
    errors=$(find /var/www/api-gateway/storage/logs -name "*.log" -mmin -15 -exec grep -c "ERROR" {} \; 2>/dev/null | paste -sd+ | bc 2>/dev/null || echo "0")
    warnings=$(find /var/www/api-gateway/storage/logs -name "*.log" -mmin -15 -exec grep -c "WARNING" {} \; 2>/dev/null | paste -sd+ | bc 2>/dev/null || echo "0")

    echo "Errors (15 min): $errors" >> "$LOG_FILE"
    echo "Warnings (15 min): $warnings" >> "$LOG_FILE"

    if [ "$errors" -gt 50 ]; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] High error rate: $errors errors in 15 minutes" >> "$ALERT_FILE"
    fi

    # Log last error
    last_error=$(grep "ERROR" "$LARAVEL_LOG" | tail -1 | cut -c1-200)
    if [ -n "$last_error" ]; then
        echo "Last error: $last_error" >> "$LOG_FILE"
    fi
fi

echo "" >> "$LOG_FILE"
EOF

# Make all scripts executable
chmod +x "$SCRIPTS_DIR"/*.sh

# 5. Setup cron jobs
echo -e "\n${YELLOW}Adding cron jobs...${NC}"

# Backup existing crontab
crontab -l > /tmp/current_crontab.bak 2>/dev/null || true

# Create new crontab entries
cat > /tmp/monitoring_cron << EOF
# AskPro AI Platform Monitoring
# Quick health check every 5 minutes
*/5 * * * * $SCRIPTS_DIR/health-check-cron.sh

# Performance monitoring every 10 minutes
*/10 * * * * $SCRIPTS_DIR/performance-monitor-cron.sh

# Error monitoring every 15 minutes
*/15 * * * * $SCRIPTS_DIR/error-monitor-cron.sh

# Database integrity check every hour
0 * * * * $SCRIPTS_DIR/database-check-cron.sh

# Daily comprehensive test at 3 AM
0 3 * * * /var/www/api-gateway/tests/master-test.sh > /var/www/api-gateway/storage/monitoring/daily-test.log 2>&1

# Weekly performance analysis on Sunday at 2 AM
0 2 * * 0 /var/www/api-gateway/tests/performance-check.sh > /var/www/api-gateway/storage/monitoring/weekly-performance.log 2>&1

# Log rotation - keep only last 30 days
0 4 * * * find /var/www/api-gateway/storage/monitoring -name "*.log" -mtime +30 -delete
EOF

# Check if monitoring jobs already exist
if crontab -l 2>/dev/null | grep -q "AskPro AI Platform Monitoring"; then
    echo -e "${YELLOW}Monitoring cron jobs already exist. Updating...${NC}"
    # Remove old monitoring jobs
    crontab -l | grep -v "AskPro AI Platform Monitoring" | grep -v "$SCRIPTS_DIR" | grep -v "/var/www/api-gateway/tests/" > /tmp/clean_crontab
    cat /tmp/clean_crontab /tmp/monitoring_cron | crontab -
else
    # Add new monitoring jobs
    (crontab -l 2>/dev/null; echo ""; cat /tmp/monitoring_cron) | crontab -
fi

# 6. Create monitoring dashboard script
cat > "$SCRIPTS_DIR/view-monitoring.sh" << 'EOF'
#!/bin/bash

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

clear
echo -e "${BLUE}MONITORING DASHBOARD${NC}"
echo "======================================"
echo "Time: $(date)"
echo ""

# Recent alerts
echo -e "${RED}Recent Alerts:${NC}"
if [ -f /var/www/api-gateway/storage/monitoring/alerts.log ]; then
    tail -5 /var/www/api-gateway/storage/monitoring/alerts.log
else
    echo "  No alerts"
fi
echo ""

# Last health check
echo -e "${BLUE}Last Health Check:${NC}"
if [ -f /var/www/api-gateway/storage/monitoring/health-check.log ]; then
    tail -20 /var/www/api-gateway/storage/monitoring/health-check.log | head -15
fi
echo ""

# Recent performance
echo -e "${BLUE}Recent Performance:${NC}"
if [ -f /var/www/api-gateway/storage/monitoring/performance.log ]; then
    tail -10 /var/www/api-gateway/storage/monitoring/performance.log
fi
echo ""

# Recent errors
echo -e "${YELLOW}Recent Errors:${NC}"
if [ -f /var/www/api-gateway/storage/monitoring/error-monitor.log ]; then
    tail -5 /var/www/api-gateway/storage/monitoring/error-monitor.log
fi
EOF

chmod +x "$SCRIPTS_DIR/view-monitoring.sh"

# 7. Create alert summary script
cat > "$SCRIPTS_DIR/alert-summary.sh" << 'EOF'
#!/bin/bash

ALERT_FILE="/var/www/api-gateway/storage/monitoring/alerts.log"

if [ ! -f "$ALERT_FILE" ]; then
    echo "No alerts found"
    exit 0
fi

# Count alerts by type
echo "Alert Summary (Last 24 hours):"
echo "=============================="

# Get alerts from last 24 hours
yesterday=$(date -d "24 hours ago" '+%Y-%m-%d %H:%M:%S')
recent_alerts=$(awk -v date="$yesterday" '$0 >= "["date' "$ALERT_FILE")

if [ -z "$recent_alerts" ]; then
    echo "No alerts in the last 24 hours"
else
    echo "$recent_alerts" | awk '{
        if (/High CPU/) cpu++
        else if (/High memory/) mem++
        else if (/Health check failed/) health++
        else if (/High error rate/) errors++
        else if (/orphaned/) orphaned++
        else other++
    }
    END {
        if (cpu > 0) printf "High CPU alerts: %d\n", cpu
        if (mem > 0) printf "High memory alerts: %d\n", mem
        if (health > 0) printf "Health check failures: %d\n", health
        if (errors > 0) printf "High error rate alerts: %d\n", errors
        if (orphaned > 0) printf "Orphaned records alerts: %d\n", orphaned
        if (other > 0) printf "Other alerts: %d\n", other
    }'
fi
EOF

chmod +x "$SCRIPTS_DIR/alert-summary.sh"

# Summary
echo ""
echo -e "${GREEN}✓ Monitoring setup complete!${NC}"
echo ""
echo "Cron jobs installed:"
echo "  • Health check: Every 5 minutes"
echo "  • Performance: Every 10 minutes"
echo "  • Error monitor: Every 15 minutes"
echo "  • Database check: Every hour"
echo "  • Full test: Daily at 3 AM"
echo "  • Performance analysis: Weekly on Sunday"
echo ""
echo "Monitoring commands:"
echo "  • View dashboard: $SCRIPTS_DIR/view-monitoring.sh"
echo "  • Alert summary: $SCRIPTS_DIR/alert-summary.sh"
echo "  • Live monitor: /var/www/api-gateway/tests/monitor.sh"
echo ""
echo "Log locations:"
echo "  • /var/www/api-gateway/storage/monitoring/"
echo ""
echo -e "${YELLOW}Note: Monitoring will start within the next 5 minutes${NC}"