#!/bin/bash
# Daily Backup Script for AskProAI
# Created: 2025-07-15

set -e

# Configuration
BACKUP_DIR="/var/www/api-gateway/backups"
PROJECT_DIR="/var/www/api-gateway"
DAYS_TO_KEEP=7
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Create backup directory if not exists
mkdir -p "$BACKUP_DIR"

echo "Starting backup at $(date)"

# 1. Database backup
echo "Backing up database..."
mysqldump -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db | gzip > "$BACKUP_DIR/db_backup_$TIMESTAMP.sql.gz"

# 2. Important files backup (config, .env, storage)
echo "Backing up important files..."
tar -czf "$BACKUP_DIR/files_backup_$TIMESTAMP.tar.gz" \
    -C "$PROJECT_DIR" \
    .env \
    config/ \
    storage/app/ \
    storage/logs/ \
    --exclude='storage/logs/*.log' \
    --exclude='storage/logs/*.gz' \
    2>/dev/null || true

# 3. Clean old backups
echo "Cleaning old backups..."
find "$BACKUP_DIR" -name "*.gz" -type f -mtime +$DAYS_TO_KEEP -delete

# 4. Show backup sizes
echo "Backup completed. Current backups:"
ls -lh "$BACKUP_DIR" | grep -E "(db_backup|files_backup)" | tail -5

echo "Backup finished at $(date)"