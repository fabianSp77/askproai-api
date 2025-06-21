#!/bin/bash

# AskProAI Automated Backup Script
# Version: 1.0
# Date: 2025-06-18
# Schedule this with cron for automated backups

set -e

# Configuration
BACKUP_DIR="/var/backups/askproai"
APP_DIR="/var/www/api-gateway"
DB_NAME="askproai"
DB_USER="root"
DB_PASS="${DB_PASSWORD:-}"
RETENTION_DAYS=30
S3_BUCKET="${S3_BACKUP_BUCKET:-}"
SLACK_WEBHOOK="${SLACK_WEBHOOK_URL:-}"

# Backup types
BACKUP_TYPE="${1:-daily}" # daily, weekly, monthly

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Timestamp
TIMESTAMP=$(date +%Y%m%d-%H%M%S)
DATE=$(date +%Y-%m-%d)

# Log function
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1"
}

# Send notification
notify() {
    local status="$1"
    local message="$2"
    
    if [ -n "$SLACK_WEBHOOK" ]; then
        curl -X POST -H 'Content-type: application/json' \
            --data "{\"text\":\"Backup $status: $message\"}" \
            "$SLACK_WEBHOOK" 2>/dev/null || true
    fi
}

# Start backup
log "Starting $BACKUP_TYPE backup..."

# 1. Database backup
log "Backing up database..."
DB_BACKUP="$BACKUP_DIR/db-$BACKUP_TYPE-$TIMESTAMP.sql.gz"

if [ -n "$DB_PASS" ]; then
    mysqldump -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" | gzip > "$DB_BACKUP"
else
    mysqldump -u"$DB_USER" "$DB_NAME" | gzip > "$DB_BACKUP"
fi

log "Database backed up to: $DB_BACKUP"

# 2. Application files backup
log "Backing up application files..."
APP_BACKUP="$BACKUP_DIR/app-$BACKUP_TYPE-$TIMESTAMP.tar.gz"

tar -czf "$APP_BACKUP" \
    --exclude="$APP_DIR/vendor" \
    --exclude="$APP_DIR/node_modules" \
    --exclude="$APP_DIR/storage/logs/*" \
    --exclude="$APP_DIR/storage/app/public/*" \
    --exclude="$APP_DIR/storage/framework/cache/*" \
    --exclude="$APP_DIR/storage/framework/sessions/*" \
    --exclude="$APP_DIR/storage/framework/views/*" \
    --exclude="$APP_DIR/.git" \
    "$APP_DIR"

log "Application backed up to: $APP_BACKUP"

# 3. Environment and config backup
log "Backing up configuration..."
CONFIG_BACKUP="$BACKUP_DIR/config-$BACKUP_TYPE-$TIMESTAMP.tar.gz"

cd "$APP_DIR"
tar -czf "$CONFIG_BACKUP" \
    .env \
    .env.production \
    config/ \
    2>/dev/null || true

log "Configuration backed up to: $CONFIG_BACKUP"

# 4. Create backup manifest
MANIFEST="$BACKUP_DIR/manifest-$BACKUP_TYPE-$TIMESTAMP.json"
cat > "$MANIFEST" << EOF
{
    "timestamp": "$TIMESTAMP",
    "date": "$DATE",
    "type": "$BACKUP_TYPE",
    "files": {
        "database": "$(basename $DB_BACKUP)",
        "application": "$(basename $APP_BACKUP)",
        "configuration": "$(basename $CONFIG_BACKUP)"
    },
    "sizes": {
        "database": "$(du -h $DB_BACKUP | cut -f1)",
        "application": "$(du -h $APP_BACKUP | cut -f1)",
        "configuration": "$(du -h $CONFIG_BACKUP | cut -f1)"
    },
    "checksums": {
        "database": "$(sha256sum $DB_BACKUP | cut -d' ' -f1)",
        "application": "$(sha256sum $APP_BACKUP | cut -d' ' -f1)",
        "configuration": "$(sha256sum $CONFIG_BACKUP | cut -d' ' -f1)"
    }
}
EOF

# 5. Upload to S3 (if configured)
if [ -n "$S3_BUCKET" ]; then
    log "Uploading to S3..."
    
    for file in "$DB_BACKUP" "$APP_BACKUP" "$CONFIG_BACKUP" "$MANIFEST"; do
        aws s3 cp "$file" "s3://$S3_BUCKET/askproai/$BACKUP_TYPE/" || {
            log "Failed to upload $(basename $file) to S3"
            notify "warning" "Failed to upload $(basename $file) to S3"
        }
    done
    
    log "Backup uploaded to S3"
fi

# 6. Clean up old backups
log "Cleaning up old backups..."

# Local cleanup
find "$BACKUP_DIR" -name "*-$BACKUP_TYPE-*" -type f -mtime +$RETENTION_DAYS -delete

# S3 cleanup (if configured)
if [ -n "$S3_BUCKET" ]; then
    aws s3 ls "s3://$S3_BUCKET/askproai/$BACKUP_TYPE/" | \
    while read -r line; do
        createDate=$(echo $line | awk '{print $1" "$2}')
        createDate=$(date -d "$createDate" +%s)
        olderThan=$(date -d "$RETENTION_DAYS days ago" +%s)
        if [[ $createDate -lt $olderThan ]]; then
            fileName=$(echo $line | awk '{print $4}')
            aws s3 rm "s3://$S3_BUCKET/askproai/$BACKUP_TYPE/$fileName"
        fi
    done
fi

# 7. Verify backup integrity
log "Verifying backup integrity..."

# Test database backup
gunzip -t "$DB_BACKUP" || {
    log "Database backup verification failed!"
    notify "error" "Database backup verification failed for $BACKUP_TYPE backup"
    exit 1
}

# Test application backup
tar -tzf "$APP_BACKUP" > /dev/null || {
    log "Application backup verification failed!"
    notify "error" "Application backup verification failed for $BACKUP_TYPE backup"
    exit 1
}

# 8. Generate backup report
REPORT="$BACKUP_DIR/report-$DATE.txt"
{
    echo "AskProAI Backup Report - $DATE"
    echo "================================="
    echo ""
    echo "Backup Type: $BACKUP_TYPE"
    echo "Timestamp: $TIMESTAMP"
    echo ""
    echo "Files Created:"
    echo "- Database: $(basename $DB_BACKUP) ($(du -h $DB_BACKUP | cut -f1))"
    echo "- Application: $(basename $APP_BACKUP) ($(du -h $APP_BACKUP | cut -f1))"
    echo "- Configuration: $(basename $CONFIG_BACKUP) ($(du -h $CONFIG_BACKUP | cut -f1))"
    echo ""
    echo "Total Backup Size: $(du -sh $BACKUP_DIR | cut -f1)"
    echo "Available Disk Space: $(df -h $BACKUP_DIR | awk 'NR==2 {print $4}')"
    echo ""
    echo "Backups Retained:"
    echo "- Daily: $(find $BACKUP_DIR -name '*-daily-*' -type f | wc -l)"
    echo "- Weekly: $(find $BACKUP_DIR -name '*-weekly-*' -type f | wc -l)"
    echo "- Monthly: $(find $BACKUP_DIR -name '*-monthly-*' -type f | wc -l)"
} > "$REPORT"

# Success notification
log "Backup completed successfully!"
notify "success" "$BACKUP_TYPE backup completed. Size: $(du -sh $DB_BACKUP $APP_BACKUP $CONFIG_BACKUP | awk '{sum+=$1} END {print sum}')MB"

# Exit successfully
exit 0