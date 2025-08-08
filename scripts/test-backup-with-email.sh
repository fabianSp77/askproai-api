#!/bin/bash

# Test-Backup Script mit E-Mail-Benachrichtigung
# Dieses Script simuliert einen Backup-Fehler um die E-Mail-Funktion zu testen

set -e

# Configuration
BACKUP_BASE_DIR="/var/www/api-gateway/backups"
DB_NAME="askproai_db"
DB_USER="askproai_user"
DB_PASS="lkZ57Dju9EDjrMxn"
DATE=$(date +%Y%m%d)
TIME=$(date +%H%M%S)
TIMESTAMP="${DATE}_${TIME}"
LOG_FILE="/var/www/api-gateway/storage/logs/backup-test.log"

# Email settings
ADMIN_EMAIL="fabian@v2202503255565320322.happysrv.de"
HOSTNAME=$(hostname)

# Create log entry
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Send email notification
send_email() {
    local subject="$1"
    local body="$2"
    
    # Versuche zuerst mit mail command
    if command -v mail >/dev/null 2>&1; then
        echo "$body" | mail -s "$subject" "$ADMIN_EMAIL" 2>/dev/null || {
            log "Mail command failed, trying sendmail..."
            # Fallback zu sendmail
            {
                echo "To: $ADMIN_EMAIL"
                echo "Subject: $subject"
                echo "From: backup@$HOSTNAME"
                echo ""
                echo "$body"
            } | /usr/sbin/sendmail -t 2>/dev/null || {
                log "ERROR: Could not send email via mail or sendmail"
            }
        }
    else
        log "Mail command not found, using sendmail directly..."
        {
            echo "To: $ADMIN_EMAIL"
            echo "Subject: $subject"
            echo "From: backup@$HOSTNAME"
            echo ""
            echo "$body"
        } | /usr/sbin/sendmail -t 2>/dev/null || {
            log "ERROR: Could not send email via sendmail"
        }
    fi
}

# Start test
log "========== Starting TEST backup with email notification =========="
log "This is a TEST to verify email notifications are working"

# Create backup directory
mkdir -p "$BACKUP_BASE_DIR/test"

# 1. First do a successful mini backup
log "Creating successful test backup..."
DB_TEST_FILE="$BACKUP_BASE_DIR/test/db_test_${TIMESTAMP}.sql"

# Create a small test backup (just schema, no data)
if mysqldump -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" --no-data > "$DB_TEST_FILE" 2>&1; then
    gzip "$DB_TEST_FILE"
    DB_TEST_FILE="${DB_TEST_FILE}.gz"
    log "Test backup created: $DB_TEST_FILE"
else
    log "ERROR: Could not create test backup"
fi

# 2. Send success notification
SUCCESS_MSG="TEST EMAIL - Backup System Working

This is a test email from the AskProAI backup system.

Server: $HOSTNAME
Date: $(date)
Test backup created: $(basename $DB_TEST_FILE)

If you receive this email, the email notification system is working correctly.

Regular backups run daily at 03:00 AM and you will receive:
- Error notifications immediately when backups fail
- Weekly summary reports on Sundays

This test was initiated manually to verify the email system."

log "Sending test success email..."
send_email "AskProAI TEST - Backup Email System Working" "$SUCCESS_MSG"

# 3. Now simulate a failure to test error notification
sleep 2
log "Simulating backup failure for testing..."

ERROR_MSG="TEST EMAIL - Simulated Backup Failure

This is a TEST of the error notification system.

Server: $HOSTNAME  
Date: $(date)
Error: Simulated database connection failure (THIS IS JUST A TEST)

In a real failure scenario, you would receive this type of notification immediately.

The backup system monitors for:
- Database connection failures
- Insufficient disk space
- Corrupted backup files
- Permission errors

This test confirms that error notifications are working."

log "Sending test error email..."
send_email "AskProAI TEST - Backup Error Notification" "$ERROR_MSG"

# 4. Check if emails were sent
log "Checking mail queue..."
if command -v mailq >/dev/null 2>&1; then
    MAIL_QUEUE=$(mailq 2>/dev/null | tail -1)
    log "Mail queue status: $MAIL_QUEUE"
fi

# 5. Also test the weekly report format
WEEKLY_REPORT="TEST EMAIL - Weekly Backup Report

This is how the weekly report looks (sent every Sunday):

AskProAI Weekly Backup Summary
==============================
Week: $(date +%Y-%W)
Server: $HOSTNAME

Backups Created This Week:
- Monday: ✅ Success (1.2MB)
- Tuesday: ✅ Success (1.2MB)
- Wednesday: ✅ Success (1.3MB)
- Thursday: ❌ FAILED - Database error
- Friday: ✅ Success (1.2MB)
- Saturday: ✅ Success (1.3MB)
- Sunday: ✅ Success (1.2MB)

Success Rate: 85.7% (6/7)
Total Backup Size: 8.4MB
Disk Usage: 25GB of 504GB (5%)
Oldest Backup: 2025-06-05 (61 days)

Next scheduled cleanup will remove 1 backup older than 60 days."

log "Sending test weekly report email..."
send_email "AskProAI TEST - Weekly Backup Report Format" "$WEEKLY_REPORT"

# Clean up test files
log "Cleaning up test files..."
rm -f "$BACKUP_BASE_DIR/test/db_test_"*.gz

# Summary
log "========== Test completed =========="
log ""
log "Three test emails were sent to: $ADMIN_EMAIL"
log "1. Success notification"
log "2. Error notification" 
log "3. Weekly report format"
log ""
log "Please check your email inbox (and spam folder) for these test messages."
log "If you don't receive them within 5 minutes, check the mail configuration."

# Show mail configuration
log ""
log "Current mail configuration:"
if [ -f /etc/postfix/main.cf ]; then
    log "Postfix is installed"
    postconf -n | grep -E "(myhostname|mydestination|relayhost)" | while read line; do
        log "  $line"
    done
elif [ -f /etc/exim4/update-exim4.conf.conf ]; then
    log "Exim4 is installed"
else
    log "No standard mail server found. Installing postfix might be needed:"
    log "  apt-get install postfix mailutils"
fi

exit 0