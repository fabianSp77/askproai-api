#!/bin/bash
# ==============================================================================
# Backup System Health Check
# ==============================================================================
# Purpose: Continuous monitoring of backup system health
# Schedule: Every 30 minutes via cron
# Actions: Detect issues, log incidents, attempt auto-recovery, send alerts
# ==============================================================================

set -euo pipefail

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOG_FILE="/var/log/backup-health-check.log"
STATUS_FILE="/var/www/api-gateway/storage/docs/backup-system/status.json"
INCIDENT_LOGGER="$SCRIPT_DIR/log-incident.sh"
BACKUP_DIR="/var/backups/askproai"
MAX_BACKUP_AGE_HOURS=24  # Alert if last backup is older than 24h

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Logging
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Health check results
ISSUES_FOUND=0
CRITICAL_ISSUES=0

log "🏥 Starting backup system health check..."

# ==============================================================================
# CHECK 1: Cron jobs exist
# ==============================================================================
log "📋 Checking cron jobs..."
if ! sudo crontab -l 2>/dev/null | grep -q "backup-run.sh"; then
    log "${RED}❌ CRITICAL: Backup cron jobs not found!${NC}"
    bash "$INCIDENT_LOGGER" critical automation \
        "Backup cron jobs missing" \
        "Automated backup cron jobs are not configured in root crontab" \
        "" \
        "sudo crontab -l | grep backup-run.sh"
    CRITICAL_ISSUES=$((CRITICAL_ISSUES + 1))

    # AUTO-RECOVERY: Reinstall cron jobs
    log "🔧 Attempting auto-recovery: Reinstalling cron jobs..."
    cat > /tmp/backup-cron-recovery.txt <<'EOF'
# AskProAI Backup Schedule - 3x Daily (CET)
0 3,11,19 * * * /var/www/api-gateway/scripts/backup-run.sh >> /var/log/backup-run.log 2>&1
0 2 * * 0 find /var/backups/askproai -type f -name "backup-*.tar.gz" -mtime +14 -delete >> /var/log/backup-cleanup.log 2>&1
EOF

    sudo crontab -l 2>/dev/null > /tmp/current-cron-recovery.txt || echo "" > /tmp/current-cron-recovery.txt
    cat /tmp/backup-cron-recovery.txt >> /tmp/current-cron-recovery.txt
    sudo crontab /tmp/current-cron-recovery.txt

    if sudo crontab -l | grep -q "backup-run.sh"; then
        log "${GREEN}✅ Auto-recovery successful: Cron jobs reinstalled${NC}"
        bash "$INCIDENT_LOGGER" info automation \
            "Cron jobs auto-recovered" \
            "Backup cron jobs were missing and have been automatically reinstalled" \
            "Auto-recovery: Reinstalled cron jobs via health check" \
            "sudo crontab -l | grep backup-run.sh && echo 'Cron jobs present'"
    else
        log "${RED}❌ Auto-recovery failed${NC}"
    fi
else
    log "${GREEN}✅ Cron jobs configured${NC}"
fi

# ==============================================================================
# CHECK 2: Last backup age
# ==============================================================================
log "🕐 Checking last backup age..."
LAST_BACKUP=$(find "$BACKUP_DIR" -name "backup-*.tar.gz" -type f -printf '%T@ %p\n' 2>/dev/null | sort -rn | head -1 | cut -d' ' -f2-)

if [ -z "$LAST_BACKUP" ]; then
    log "${RED}❌ CRITICAL: No backups found!${NC}"
    bash "$INCIDENT_LOGGER" critical backup \
        "No backups found" \
        "No backup files exist in $BACKUP_DIR - backup system may have never run successfully" \
        "" \
        "ls -lh $BACKUP_DIR/backup-*.tar.gz | head -5"
    CRITICAL_ISSUES=$((CRITICAL_ISSUES + 1))
else
    LAST_BACKUP_TIME=$(stat -c %Y "$LAST_BACKUP")
    CURRENT_TIME=$(date +%s)
    BACKUP_AGE_HOURS=$(( (CURRENT_TIME - LAST_BACKUP_TIME) / 3600 ))

    log "Last backup: $(basename "$LAST_BACKUP") (${BACKUP_AGE_HOURS}h ago)"

    if [ $BACKUP_AGE_HOURS -gt $MAX_BACKUP_AGE_HOURS ]; then
        log "${YELLOW}⚠️ WARNING: Last backup is ${BACKUP_AGE_HOURS}h old (threshold: ${MAX_BACKUP_AGE_HOURS}h)${NC}"
        bash "$INCIDENT_LOGGER" high backup \
            "Backup overdue" \
            "Last backup was ${BACKUP_AGE_HOURS} hours ago (file: $(basename "$LAST_BACKUP")). Expected interval: 8 hours (3x daily)" \
            "" \
            "ls -lth $BACKUP_DIR/backup-*.tar.gz | head -3"
        ISSUES_FOUND=$((ISSUES_FOUND + 1))
    else
        log "${GREEN}✅ Last backup is recent (${BACKUP_AGE_HOURS}h ago)${NC}"
    fi
fi

# ==============================================================================
# CHECK 3: Backup script executable
# ==============================================================================
log "🔍 Checking backup script permissions..."
BACKUP_SCRIPT="/var/www/api-gateway/scripts/backup-run.sh"

if [ ! -x "$BACKUP_SCRIPT" ]; then
    log "${RED}❌ CRITICAL: Backup script not executable!${NC}"
    bash "$INCIDENT_LOGGER" critical automation \
        "Backup script not executable" \
        "Script $BACKUP_SCRIPT lacks execute permissions" \
        "" \
        "test -x $BACKUP_SCRIPT && echo 'Script is executable' || echo 'Script is NOT executable'"
    CRITICAL_ISSUES=$((CRITICAL_ISSUES + 1))

    # AUTO-RECOVERY: Fix permissions
    log "🔧 Attempting auto-recovery: Setting executable permission..."
    sudo chmod +x "$BACKUP_SCRIPT"

    if [ -x "$BACKUP_SCRIPT" ]; then
        log "${GREEN}✅ Auto-recovery successful: Made script executable${NC}"
        bash "$INCIDENT_LOGGER" info automation \
            "Script permissions auto-recovered" \
            "Backup script lacked execute permission" \
            "Auto-recovery: chmod +x applied via health check" \
            "test -x $BACKUP_SCRIPT && echo '✅ Script is executable'"
    fi
else
    log "${GREEN}✅ Backup script is executable${NC}"
fi

# ==============================================================================
# CHECK 4: Database connectivity
# ==============================================================================
log "🗄️ Checking database connectivity..."
DB_USER=$(grep DB_USERNAME /var/www/api-gateway/.env | cut -d= -f2)
DB_PASS=$(grep DB_PASSWORD /var/www/api-gateway/.env | cut -d= -f2 | tr -d '"')
DB_NAME=$(grep DB_DATABASE /var/www/api-gateway/.env | cut -d= -f2)

if ! mysql -u "$DB_USER" -p"$DB_PASS" -e "SELECT 1" "$DB_NAME" &>/dev/null; then
    log "${RED}❌ CRITICAL: Database connection failed!${NC}"
    bash "$INCIDENT_LOGGER" critical database \
        "Database connection failed" \
        "Cannot connect to database $DB_NAME with configured credentials" \
        "" \
        "mysql -u $DB_USER -p*** -e 'SELECT 1' $DB_NAME"
    CRITICAL_ISSUES=$((CRITICAL_ISSUES + 1))
else
    log "${GREEN}✅ Database connection successful${NC}"
fi

# ==============================================================================
# CHECK 5: Binlog enabled
# ==============================================================================
log "📊 Checking binlog status..."
if ! mysql -u "$DB_USER" -p"$DB_PASS" -e "SHOW BINARY LOGS" &>/dev/null; then
    log "${YELLOW}⚠️ WARNING: Binlog not accessible or disabled${NC}"
    bash "$INCIDENT_LOGGER" medium database \
        "Binary logs not accessible" \
        "PITR (Point-in-Time Recovery) may not be available - binlog access failed" \
        "" \
        "mysql -u $DB_USER -p*** -e 'SHOW BINARY LOGS' | tail -5"
    ISSUES_FOUND=$((ISSUES_FOUND + 1))
else
    log "${GREEN}✅ Binlog accessible${NC}"
fi

# ==============================================================================
# CHECK 6: Storage space
# ==============================================================================
log "💾 Checking storage space..."
BACKUP_PARTITION=$(df "$BACKUP_DIR" | tail -1 | awk '{print $6}')
USAGE=$(df "$BACKUP_DIR" | tail -1 | awk '{print $5}' | tr -d '%')

log "Backup partition ($BACKUP_PARTITION) usage: ${USAGE}%"

if [ $USAGE -gt 90 ]; then
    log "${RED}❌ CRITICAL: Storage almost full (${USAGE}%)${NC}"
    bash "$INCIDENT_LOGGER" critical storage \
        "Storage critically low" \
        "Backup partition $BACKUP_PARTITION is ${USAGE}% full - backups may fail soon" \
        "" \
        "df -h $BACKUP_PARTITION"
    CRITICAL_ISSUES=$((CRITICAL_ISSUES + 1))
elif [ $USAGE -gt 80 ]; then
    log "${YELLOW}⚠️ WARNING: Storage high (${USAGE}%)${NC}"
    bash "$INCIDENT_LOGGER" medium storage \
        "Storage usage high" \
        "Backup partition $BACKUP_PARTITION is ${USAGE}% full" \
        "" \
        "df -h $BACKUP_PARTITION"
    ISSUES_FOUND=$((ISSUES_FOUND + 1))
else
    log "${GREEN}✅ Storage usage normal (${USAGE}%)${NC}"
fi

# ==============================================================================
# CHECK 7: Email configuration
# ==============================================================================
log "📧 Checking email configuration..."
if ! grep -q "MAIL_HOST" /var/www/api-gateway/.env || ! grep -q "MAIL_FROM_ADDRESS" /var/www/api-gateway/.env; then
    log "${YELLOW}⚠️ WARNING: Email not fully configured${NC}"
    bash "$INCIDENT_LOGGER" medium email \
        "Email configuration incomplete" \
        "MAIL_HOST or MAIL_FROM_ADDRESS missing in .env - alerts may not be sent" \
        "" \
        "grep -E 'MAIL_HOST|MAIL_FROM_ADDRESS' /var/www/api-gateway/.env"
    ISSUES_FOUND=$((ISSUES_FOUND + 1))
else
    log "${GREEN}✅ Email configured${NC}"
fi

# ==============================================================================
# UPDATE STATUS JSON
# ==============================================================================
log "📝 Updating status.json..."

HEALTH_STATUS="healthy"
HEALTH_MESSAGE="All systems operational"

if [ $CRITICAL_ISSUES -gt 0 ]; then
    HEALTH_STATUS="critical"
    HEALTH_MESSAGE="$CRITICAL_ISSUES critical issues detected - immediate attention required"
elif [ $ISSUES_FOUND -gt 0 ]; then
    HEALTH_STATUS="warning"
    HEALTH_MESSAGE="$ISSUES_FOUND warnings detected - review recommended"
fi

# Update status.json with health check results
python3 <<PYTHON
import json
from datetime import datetime

try:
    with open('$STATUS_FILE', 'r') as f:
        data = json.load(f)

    data['status'] = '$HEALTH_STATUS'
    data['message'] = '$HEALTH_MESSAGE'
    data['health_check'] = {
        'last_check': datetime.now().isoformat(),
        'critical_issues': $CRITICAL_ISSUES,
        'warnings': $ISSUES_FOUND,
        'checks_passed': 7 - $CRITICAL_ISSUES - $ISSUES_FOUND,
        'total_checks': 7
    }

    with open('$STATUS_FILE', 'w') as f:
        json.dump(data, f, indent=2)

    print("✅ Status updated")
except Exception as e:
    print(f"❌ Failed to update status: {e}")
PYTHON

# ==============================================================================
# SUMMARY
# ==============================================================================
log "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
log "Health Check Summary:"
log "  Status: $HEALTH_STATUS"
log "  Critical Issues: $CRITICAL_ISSUES"
log "  Warnings: $ISSUES_FOUND"
log "  Checks Passed: $((7 - CRITICAL_ISSUES - ISSUES_FOUND))/7"
log "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Send summary email if issues found
if [ $CRITICAL_ISSUES -gt 0 ] || [ $ISSUES_FOUND -gt 0 ]; then
    log "📧 Sending health check alert email..."

    php /var/www/api-gateway/artisan tinker --execute="
        use Illuminate\Support\Facades\Mail;
        Mail::raw(
            '🏥 BACKUP SYSTEM HEALTH CHECK REPORT\n\n' .
            'Status: $HEALTH_STATUS\n' .
            'Critical Issues: $CRITICAL_ISSUES\n' .
            'Warnings: $ISSUES_FOUND\n' .
            'Checks Passed: $((7 - CRITICAL_ISSUES - ISSUES_FOUND))/7\n\n' .
            'Please review the backup system status at:\n' .
            'https://api.askproai.de/docs/backup-system\n\n' .
            'Log file: $LOG_FILE',
            function(\$message) {
                \$message->to(['fabian@askproai.de', 'fabianspitzer@icloud.com'])
                        ->subject('🏥 Backup Health Check - $HEALTH_STATUS');
            }
        );
        echo '✅ Email sent';
    " 2>&1 | grep -v "Warning:" || log "⚠️ Failed to send health check email"
fi

exit $([ $CRITICAL_ISSUES -eq 0 ] && echo 0 || echo 1)
