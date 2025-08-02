#!/bin/bash
# =============================================================================
# Security Monitoring Script for AskProAI
# =============================================================================
# CRITICAL: Run this regularly via cron for continuous monitoring
# Usage: chmod +x security-monitoring.sh && ./security-monitoring.sh

set -e

# Configuration
ALERT_EMAIL="admin@askproai.de"
LOG_DIR="/var/log/askproai-security"
ALERT_THRESHOLD_CPU=80
ALERT_THRESHOLD_MEMORY=85
ALERT_THRESHOLD_DISK=90

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Create log directory if it doesn't exist
mkdir -p $LOG_DIR

echo -e "${BLUE}🔐 AskProAI Security Monitoring - $(date)${NC}"

# -----------------------------------------------------------------------------
# System Resource Monitoring
# -----------------------------------------------------------------------------

echo -e "\n${BLUE}📊 System Resource Check${NC}"

# CPU Usage
CPU_USAGE=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | cut -d'%' -f1)
CPU_USAGE_INT=${CPU_USAGE%.*}

if [ "$CPU_USAGE_INT" -gt "$ALERT_THRESHOLD_CPU" ]; then
    echo -e "${RED}⚠️  HIGH CPU USAGE: ${CPU_USAGE}%${NC}"
    echo "$(date): HIGH CPU USAGE: ${CPU_USAGE}%" >> $LOG_DIR/alerts.log
else
    echo -e "${GREEN}✅ CPU Usage: ${CPU_USAGE}%${NC}"
fi

# Memory Usage
MEMORY_USAGE=$(free | grep Mem | awk '{printf("%.0f", $3/$2 * 100.0)}')

if [ "$MEMORY_USAGE" -gt "$ALERT_THRESHOLD_MEMORY" ]; then
    echo -e "${RED}⚠️  HIGH MEMORY USAGE: ${MEMORY_USAGE}%${NC}"
    echo "$(date): HIGH MEMORY USAGE: ${MEMORY_USAGE}%" >> $LOG_DIR/alerts.log
else
    echo -e "${GREEN}✅ Memory Usage: ${MEMORY_USAGE}%${NC}"
fi

# Disk Usage
DISK_USAGE=$(df / | tail -1 | awk '{print $5}' | cut -d'%' -f1)

if [ "$DISK_USAGE" -gt "$ALERT_THRESHOLD_DISK" ]; then
    echo -e "${RED}⚠️  HIGH DISK USAGE: ${DISK_USAGE}%${NC}"
    echo "$(date): HIGH DISK USAGE: ${DISK_USAGE}%" >> $LOG_DIR/alerts.log
else
    echo -e "${GREEN}✅ Disk Usage: ${DISK_USAGE}%${NC}"
fi

# -----------------------------------------------------------------------------
# Service Status Monitoring
# -----------------------------------------------------------------------------

echo -e "\n${BLUE}🔧 Service Status Check${NC}"

# Check critical services
SERVICES=("nginx" "php8.3-fpm" "mysql" "redis-server" "fail2ban")

for service in "${SERVICES[@]}"; do
    if systemctl is-active --quiet $service; then
        echo -e "${GREEN}✅ $service is running${NC}"
    else
        echo -e "${RED}❌ $service is not running${NC}"
        echo "$(date): SERVICE DOWN: $service" >> $LOG_DIR/alerts.log
        
        # Try to restart the service
        echo "Attempting to restart $service..."
        systemctl restart $service || echo "Failed to restart $service"
    fi
done

# -----------------------------------------------------------------------------
# Security Log Analysis
# -----------------------------------------------------------------------------

echo -e "\n${BLUE}🔍 Security Log Analysis${NC}"

# Check for failed login attempts (last hour)
FAILED_LOGINS=$(grep "$(date --date='1 hour ago' '+%b %d %H')" /var/log/auth.log 2>/dev/null | grep -c "Failed password" || echo "0")

if [ "$FAILED_LOGINS" -gt "10" ]; then
    echo -e "${RED}⚠️  High number of failed logins: $FAILED_LOGINS in last hour${NC}"
    echo "$(date): HIGH FAILED LOGINS: $FAILED_LOGINS" >> $LOG_DIR/alerts.log
else
    echo -e "${GREEN}✅ Failed logins (last hour): $FAILED_LOGINS${NC}"
fi

# Check nginx error logs for security issues
NGINX_ERRORS=$(grep -c "$(date '+%Y/%m/%d')" /var/log/nginx/error.log 2>/dev/null || echo "0")

if [ "$NGINX_ERRORS" -gt "100" ]; then
    echo -e "${YELLOW}⚠️  High nginx error count today: $NGINX_ERRORS${NC}"
fi

# Check for suspicious requests in nginx access logs
SUSPICIOUS_REQUESTS=$(grep "$(date '+%d/%b/%Y')" /var/log/nginx/access.log 2>/dev/null | grep -E "(sql|union|select|script|javascript|eval|base64)" | wc -l || echo "0")

if [ "$SUSPICIOUS_REQUESTS" -gt "0" ]; then
    echo -e "${RED}⚠️  Suspicious requests detected today: $SUSPICIOUS_REQUESTS${NC}"
    echo "$(date): SUSPICIOUS REQUESTS: $SUSPICIOUS_REQUESTS" >> $LOG_DIR/alerts.log
fi

# -----------------------------------------------------------------------------
# Fail2ban Status
# -----------------------------------------------------------------------------

echo -e "\n${BLUE}🚫 Fail2ban Status${NC}"

if systemctl is-active --quiet fail2ban; then
    # Get banned IPs
    BANNED_IPS=$(fail2ban-client status | grep "Jail list" | cut -d: -f2 | xargs -n1 fail2ban-client status 2>/dev/null | grep "Currently banned" | awk '{sum += $4} END {print sum+0}')
    echo -e "${GREEN}✅ Fail2ban active - Currently banned IPs: $BANNED_IPS${NC}"
    
    # Log active jails
    fail2ban-client status | grep "Jail list" | cut -d: -f2 | tr ',' '\n' | while read jail; do
        jail=$(echo $jail | xargs)
        if [ ! -z "$jail" ]; then
            status=$(fail2ban-client status $jail 2>/dev/null | grep "Currently banned" | awk '{print $4}')
            if [ "$status" -gt "0" ]; then
                echo -e "${YELLOW}📋 Jail '$jail': $status banned IPs${NC}"
            fi
        fi
    done
else
    echo -e "${RED}❌ Fail2ban is not running${NC}"
    echo "$(date): FAIL2BAN DOWN" >> $LOG_DIR/alerts.log
fi

# -----------------------------------------------------------------------------
# File Integrity Monitoring
# -----------------------------------------------------------------------------

echo -e "\n${BLUE}📁 File Integrity Check${NC}"

# Check for modifications to critical files
CRITICAL_FILES=(
    "/etc/nginx/sites-available/api.askproai.de"
    "/etc/php/8.3/fpm/php.ini"
    "/etc/redis/redis.conf"
    "/var/www/api-gateway/.env"
    "/etc/fail2ban/jail.local"
)

for file in "${CRITICAL_FILES[@]}"; do
    if [ -f "$file" ]; then
        # Check if file was modified in last 24 hours
        if [ "$(find "$file" -mtime -1 -print)" ]; then
            echo -e "${YELLOW}⚠️  Critical file modified (last 24h): $file${NC}"
            echo "$(date): CRITICAL FILE MODIFIED: $file" >> $LOG_DIR/alerts.log
        fi
    else
        echo -e "${RED}❌ Critical file missing: $file${NC}"
        echo "$(date): CRITICAL FILE MISSING: $file" >> $LOG_DIR/alerts.log
    fi
done

# -----------------------------------------------------------------------------
# Network Connection Monitoring
# -----------------------------------------------------------------------------

echo -e "\n${BLUE}🌐 Network Security Check${NC}"

# Check for unusual network connections
ESTABLISHED_CONNECTIONS=$(netstat -an | grep ESTABLISHED | wc -l)
echo -e "${GREEN}📊 Established connections: $ESTABLISHED_CONNECTIONS${NC}"

# Check for suspicious listening ports
LISTENING_PORTS=$(netstat -tlnp | grep LISTEN | awk '{print $4}' | cut -d: -f2 | sort -n | uniq)
EXPECTED_PORTS=(22 80 443 3306 6379)

for port in $LISTENING_PORTS; do
    if [[ ! " ${EXPECTED_PORTS[@]} " =~ " ${port} " ]]; then
        echo -e "${YELLOW}⚠️  Unexpected listening port: $port${NC}"
        echo "$(date): UNEXPECTED LISTENING PORT: $port" >> $LOG_DIR/alerts.log
    fi
done

# -----------------------------------------------------------------------------
# Database Security Check
# -----------------------------------------------------------------------------

echo -e "\n${BLUE}🗄️  Database Security Check${NC}"

# Check MySQL process list for long-running queries
LONG_QUERIES=$(mysql -u root -e "SHOW PROCESSLIST;" 2>/dev/null | awk '$6 > 30 {print $6}' | wc -l || echo "0")

if [ "$LONG_QUERIES" -gt "5" ]; then
    echo -e "${YELLOW}⚠️  Long-running database queries detected: $LONG_QUERIES${NC}"
fi

# Check for failed MySQL connections
MYSQL_ERRORS=$(grep "$(date '+%Y-%m-%d')" /var/log/mysql/error.log 2>/dev/null | grep -i "access denied" | wc -l || echo "0")

if [ "$MYSQL_ERRORS" -gt "10" ]; then
    echo -e "${RED}⚠️  High MySQL authentication failures: $MYSQL_ERRORS${NC}"
    echo "$(date): HIGH MYSQL AUTH FAILURES: $MYSQL_ERRORS" >> $LOG_DIR/alerts.log
fi

# -----------------------------------------------------------------------------
# Application-Level Security
# -----------------------------------------------------------------------------

echo -e "\n${BLUE}🚀 Application Security Check${NC}"

# Check Laravel logs for security events
LARAVEL_ERRORS=$(grep "$(date '+Y-m-d')" /var/www/api-gateway/storage/logs/laravel.log 2>/dev/null | grep -E "(CRITICAL|EMERGENCY|ERROR)" | wc -l || echo "0")

if [ "$LARAVEL_ERRORS" -gt "50" ]; then
    echo -e "${YELLOW}⚠️  High Laravel error count: $LARAVEL_ERRORS${NC}"
fi

# Check for suspicious webhook attempts
WEBHOOK_FAILS=$(grep "$(date '+%d/%b/%Y')" /var/log/nginx/access.log 2>/dev/null | grep -E "/api/(retell|calcom|stripe)/webhook" | grep -E " (403|401|400) " | wc -l || echo "0")

if [ "$WEBHOOK_FAILS" -gt "20" ]; then
    echo -e "${RED}⚠️  High webhook authentication failures: $WEBHOOK_FAILS${NC}"
    echo "$(date): HIGH WEBHOOK AUTH FAILURES: $WEBHOOK_FAILS" >> $LOG_DIR/alerts.log
fi

# -----------------------------------------------------------------------------
# Disk Space for Logs
# -----------------------------------------------------------------------------

echo -e "\n${BLUE}📝 Log Management${NC}"

# Check log directory sizes
LOG_SIZE=$(du -sh /var/log | awk '{print $1}')
echo -e "${GREEN}📊 Total log size: $LOG_SIZE${NC}"

# Clean old logs if needed (older than 30 days)
find /var/log -name "*.log" -type f -mtime +30 -exec rm -f {} \; 2>/dev/null || true
find $LOG_DIR -name "*.log" -type f -mtime +30 -exec rm -f {} \; 2>/dev/null || true

# -----------------------------------------------------------------------------
# Generate Summary Report
# -----------------------------------------------------------------------------

REPORT_FILE="$LOG_DIR/security-report-$(date +%Y%m%d-%H%M%S).log"

cat > $REPORT_FILE << EOF
AskProAI Security Monitoring Report
Generated: $(date)

SYSTEM RESOURCES:
- CPU Usage: ${CPU_USAGE}%
- Memory Usage: ${MEMORY_USAGE}%
- Disk Usage: ${DISK_USAGE}%

SECURITY METRICS:
- Failed Logins (1h): $FAILED_LOGINS
- Nginx Errors Today: $NGINX_ERRORS
- Suspicious Requests: $SUSPICIOUS_REQUESTS
- Banned IPs: $BANNED_IPS
- Webhook Failures: $WEBHOOK_FAILS

SERVICES STATUS:
$(for service in "${SERVICES[@]}"; do
    if systemctl is-active --quiet $service; then
        echo "- $service: RUNNING"
    else
        echo "- $service: STOPPED"
    fi
done)

EOF

echo -e "\n${GREEN}📋 Security report saved: $REPORT_FILE${NC}"

# -----------------------------------------------------------------------------
# Send Alerts if Critical Issues Found
# -----------------------------------------------------------------------------

if [ -f "$LOG_DIR/alerts.log" ] && [ $(wc -l < "$LOG_DIR/alerts.log") -gt 0 ]; then
    echo -e "\n${RED}🚨 CRITICAL ALERTS DETECTED!${NC}"
    echo -e "${RED}Check: $LOG_DIR/alerts.log${NC}"
    
    # Send email alert (if mail is configured)
    if command -v mail >/dev/null 2>&1; then
        tail -20 "$LOG_DIR/alerts.log" | mail -s "AskProAI Security Alert - $(hostname)" "$ALERT_EMAIL" 2>/dev/null || true
    fi
fi

echo -e "\n${GREEN}✅ Security monitoring completed!${NC}"
echo -e "${BLUE}💡 Add to cron: */15 * * * * /var/www/api-gateway/security/security-monitoring.sh${NC}"