#!/bin/bash
TIMESTAMP=$(date +%Y%m%d-%H%M%S)
BACKUP_DIR="/var/backups/askproai/documentation"
mkdir -p $BACKUP_DIR
tar -czf "$BACKUP_DIR/docs-$TIMESTAMP.tar.gz" -C /var/www/api-gateway/public/admin documentation/
find $BACKUP_DIR -type f -name "docs-*.tar.gz" -mtime +30 -delete
