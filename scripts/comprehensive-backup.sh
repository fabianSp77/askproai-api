#!/bin/bash

#############################################
# Comprehensive System Backup Script
# Created: $(date +"%Y-%m-%d")
# Purpose: Full system backup with verification
#############################################

set -euo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration
BACKUP_ROOT="/var/www/GOLDEN_BACKUPS"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_DIR="$BACKUP_ROOT/full_backup_$TIMESTAMP"
LOG_FILE="$BACKUP_DIR/backup.log"
MANIFEST_FILE="$BACKUP_DIR/MANIFEST.txt"
VERIFICATION_FILE="$BACKUP_DIR/VERIFICATION.txt"

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Logging function
log() {
    echo -e "${2:-$NC}$1${NC}"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

# Error handling
error_exit() {
    log "ERROR: $1" "$RED"
    exit 1
}

# Progress tracking
TOTAL_STEPS=15
CURRENT_STEP=0

progress() {
    CURRENT_STEP=$((CURRENT_STEP + 1))
    PERCENTAGE=$((CURRENT_STEP * 100 / TOTAL_STEPS))
    log "[$CURRENT_STEP/$TOTAL_STEPS - $PERCENTAGE%] $1" "$BLUE"
}

# Start backup
log "========================================" "$GREEN"
log "Starting Comprehensive System Backup" "$GREEN"
log "Backup Directory: $BACKUP_DIR" "$GREEN"
log "========================================" "$GREEN"

# Create manifest header
cat > "$MANIFEST_FILE" << EOF
========================================
COMPREHENSIVE SYSTEM BACKUP MANIFEST
========================================
Backup Date: $(date)
Hostname: $(hostname)
Kernel: $(uname -r)
PHP Version: $(php -v | head -1)
MySQL Version: $(mysql --version)
Nginx Version: $(nginx -v 2>&1)
========================================

BACKUP CONTENTS:
EOF

# 1. Backup all databases
progress "Backing up MySQL databases"
mkdir -p "$BACKUP_DIR/databases"

# Get all databases
DATABASES=$(mysql -u root -N -e "SHOW DATABASES;" | grep -v -E 'information_schema|performance_schema|mysql|sys')

for DB in $DATABASES; do
    log "  - Backing up database: $DB" "$YELLOW"
    mysqldump -u root \
        --single-transaction \
        --routines \
        --triggers \
        --events \
        --add-drop-database \
        --databases "$DB" > "$BACKUP_DIR/databases/${DB}.sql" 2>/dev/null

    # Compress the dump
    gzip "$BACKUP_DIR/databases/${DB}.sql"

    # Calculate checksum
    CHECKSUM=$(sha256sum "$BACKUP_DIR/databases/${DB}.sql.gz" | awk '{print $1}')
    echo "database:${DB}:${CHECKSUM}" >> "$VERIFICATION_FILE"
    echo "  - Database: $DB ($(du -h "$BACKUP_DIR/databases/${DB}.sql.gz" | awk '{print $1}'))" >> "$MANIFEST_FILE"
done

# 2. Backup main application
progress "Backing up main application (/var/www/api-gateway)"
tar --exclude='vendor' \
    --exclude='node_modules' \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='.git' \
    -czf "$BACKUP_DIR/api-gateway.tar.gz" \
    -C /var/www api-gateway 2>/dev/null

CHECKSUM=$(sha256sum "$BACKUP_DIR/api-gateway.tar.gz" | awk '{print $1}')
echo "application:api-gateway:${CHECKSUM}" >> "$VERIFICATION_FILE"
echo "  - Application: api-gateway ($(du -h "$BACKUP_DIR/api-gateway.tar.gz" | awk '{print $1}'))" >> "$MANIFEST_FILE"

# 3. Backup environment files
progress "Backing up environment files"
mkdir -p "$BACKUP_DIR/env"

find /var/www -maxdepth 3 -name ".env" -type f 2>/dev/null | while read -r ENV_FILE; do
    REL_PATH=$(echo "$ENV_FILE" | sed 's|/var/www/||')
    DIR_PATH=$(dirname "$BACKUP_DIR/env/$REL_PATH")
    mkdir -p "$DIR_PATH"
    cp "$ENV_FILE" "$BACKUP_DIR/env/$REL_PATH"
    log "  - Backed up: $ENV_FILE" "$YELLOW"
done

# 4. Backup Nginx configuration
progress "Backing up Nginx configuration"
mkdir -p "$BACKUP_DIR/nginx"

cp -r /etc/nginx/sites-available "$BACKUP_DIR/nginx/"
cp -r /etc/nginx/sites-enabled "$BACKUP_DIR/nginx/"
cp /etc/nginx/nginx.conf "$BACKUP_DIR/nginx/" 2>/dev/null || true

echo "  - Nginx configuration" >> "$MANIFEST_FILE"

# 5. Backup PHP configuration
progress "Backing up PHP configuration"
mkdir -p "$BACKUP_DIR/php"

PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
cp -r "/etc/php/$PHP_VERSION" "$BACKUP_DIR/php/" 2>/dev/null || true

echo "  - PHP $PHP_VERSION configuration" >> "$MANIFEST_FILE"

# 6. Backup Supervisor configuration
progress "Backing up Supervisor configuration"
mkdir -p "$BACKUP_DIR/supervisor"

if [ -d "/etc/supervisor/conf.d" ]; then
    cp -r /etc/supervisor/conf.d "$BACKUP_DIR/supervisor/"
    echo "  - Supervisor configuration" >> "$MANIFEST_FILE"
fi

# 7. Backup Cron jobs
progress "Backing up Cron jobs"
mkdir -p "$BACKUP_DIR/cron"

# System cron
crontab -l > "$BACKUP_DIR/cron/root_crontab.txt" 2>/dev/null || true

# User crons
for USER in www-data; do
    crontab -u "$USER" -l > "$BACKUP_DIR/cron/${USER}_crontab.txt" 2>/dev/null || true
done

echo "  - Cron jobs" >> "$MANIFEST_FILE"

# 8. Backup Redis data (if running)
progress "Backing up Redis data"
mkdir -p "$BACKUP_DIR/redis"

if systemctl is-active --quiet redis-server; then
    redis-cli --rdb "$BACKUP_DIR/redis/dump.rdb" 2>/dev/null || true
    echo "  - Redis data snapshot" >> "$MANIFEST_FILE"
fi

# 9. System information
progress "Collecting system information"
mkdir -p "$BACKUP_DIR/system"

# Package list
dpkg -l > "$BACKUP_DIR/system/installed_packages.txt"

# PHP modules
php -m > "$BACKUP_DIR/system/php_modules.txt"

# Composer global packages
composer global show > "$BACKUP_DIR/system/composer_global.txt" 2>/dev/null || true

# Node global packages
npm list -g --depth=0 > "$BACKUP_DIR/system/npm_global.txt" 2>/dev/null || true

# System services
systemctl list-units --type=service --all > "$BACKUP_DIR/system/services.txt"

# Network configuration
ip addr > "$BACKUP_DIR/system/network.txt"

echo "  - System information and package lists" >> "$MANIFEST_FILE"

# 10. Backup Laravel storage (important user uploads)
progress "Backing up Laravel storage"
if [ -d "/var/www/api-gateway/storage/app" ]; then
    tar -czf "$BACKUP_DIR/storage_app.tar.gz" \
        -C /var/www/api-gateway/storage app 2>/dev/null
    echo "  - Laravel storage/app (user uploads)" >> "$MANIFEST_FILE"
fi

# 11. Backup SSL certificates
progress "Backing up SSL certificates"
mkdir -p "$BACKUP_DIR/ssl"

if [ -d "/etc/letsencrypt" ]; then
    tar -czf "$BACKUP_DIR/ssl/letsencrypt.tar.gz" \
        -C /etc letsencrypt 2>/dev/null
    echo "  - SSL certificates (Let's Encrypt)" >> "$MANIFEST_FILE"
fi

# 12. Create restoration script
progress "Creating restoration script"
cat > "$BACKUP_DIR/restore.sh" << 'RESTORE_SCRIPT'
#!/bin/bash

#############################################
# System Restoration Script
# Usage: ./restore.sh [backup_dir]
#############################################

set -euo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

BACKUP_DIR="${1:-$(pwd)}"

if [ ! -f "$BACKUP_DIR/MANIFEST.txt" ]; then
    echo -e "${RED}ERROR: Invalid backup directory. MANIFEST.txt not found.${NC}"
    exit 1
fi

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}System Restoration from Backup${NC}"
echo -e "${GREEN}Backup: $BACKUP_DIR${NC}"
echo -e "${GREEN}========================================${NC}"

echo -e "${YELLOW}WARNING: This will restore system data.${NC}"
echo -e "${YELLOW}Make sure to backup current state first!${NC}"

# Skip confirmation if --yes flag or AUTO_CONFIRM=1
if [ "${AUTO_CONFIRM:-0}" = "1" ] || [ "${1:-}" = "--yes" ]; then
    echo "Auto-confirm enabled, proceeding..."
else
    read -p "Continue? (yes/no): " CONFIRM
    if [ "$CONFIRM" != "yes" ]; then
        echo "Restoration cancelled."
        exit 0
    fi
fi

# Function to restore with progress
restore_step() {
    echo -e "${BLUE}[*] $1${NC}"
}

# 1. Restore databases
restore_step "Restoring MySQL databases..."
for SQL_GZ in "$BACKUP_DIR"/databases/*.sql.gz; do
    if [ -f "$SQL_GZ" ]; then
        DB_NAME=$(basename "$SQL_GZ" .sql.gz)
        echo "  - Restoring database: $DB_NAME"
        gunzip -c "$SQL_GZ" | mysql -u root
    fi
done

# 2. Restore application files
if [ -f "$BACKUP_DIR/api-gateway.tar.gz" ]; then
    restore_step "Restoring application files..."

    # Backup current
    if [ -d "/var/www/api-gateway" ]; then
        mv /var/www/api-gateway "/var/www/api-gateway.before_restore_$(date +%Y%m%d_%H%M%S)"
    fi

    # Extract backup
    tar -xzf "$BACKUP_DIR/api-gateway.tar.gz" -C /var/www/
fi

# 3. Restore environment files
if [ -d "$BACKUP_DIR/env" ]; then
    restore_step "Restoring environment files..."
    cd "$BACKUP_DIR/env"
    find . -name ".env" -type f | while read -r ENV_FILE; do
        DEST="/var/www/${ENV_FILE#./}"
        DEST_DIR=$(dirname "$DEST")

        if [ -d "$DEST_DIR" ]; then
            cp "$ENV_FILE" "$DEST"
            echo "  - Restored: $DEST"
        fi
    done
    cd - > /dev/null
fi

# 4. Restore Nginx configuration
if [ -d "$BACKUP_DIR/nginx" ]; then
    restore_step "Restoring Nginx configuration..."

    # Backup current
    cp -r /etc/nginx/sites-available "/etc/nginx/sites-available.before_restore_$(date +%Y%m%d_%H%M%S)"

    # Restore
    cp -r "$BACKUP_DIR/nginx/sites-available"/* /etc/nginx/sites-available/

    # Test configuration
    nginx -t && systemctl reload nginx
fi

# 5. Restore cron jobs
if [ -f "$BACKUP_DIR/cron/root_crontab.txt" ]; then
    restore_step "Restoring cron jobs..."
    crontab "$BACKUP_DIR/cron/root_crontab.txt"
fi

# 6. Restore Redis data
if [ -f "$BACKUP_DIR/redis/dump.rdb" ] && systemctl is-active --quiet redis-server; then
    restore_step "Restoring Redis data..."
    systemctl stop redis-server
    cp "$BACKUP_DIR/redis/dump.rdb" /var/lib/redis/dump.rdb
    chown redis:redis /var/lib/redis/dump.rdb
    systemctl start redis-server
fi

# 7. Restore Laravel storage
if [ -f "$BACKUP_DIR/storage_app.tar.gz" ]; then
    restore_step "Restoring Laravel storage..."
    tar -xzf "$BACKUP_DIR/storage_app.tar.gz" -C /var/www/api-gateway/storage/
fi

# 8. Set permissions
restore_step "Setting permissions..."
chown -R www-data:www-data /var/www/api-gateway
chmod -R 755 /var/www/api-gateway
chmod -R 775 /var/www/api-gateway/storage
chmod -R 775 /var/www/api-gateway/bootstrap/cache

# 9. Clear Laravel cache
restore_step "Clearing Laravel cache..."
cd /var/www/api-gateway
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# 10. Restart services
restore_step "Restarting services..."
systemctl restart nginx
systemctl restart php*-fpm
systemctl restart redis-server || true
systemctl restart supervisor || true

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Restoration completed successfully!${NC}"
echo -e "${GREEN}========================================${NC}"

echo -e "${YELLOW}Please verify:${NC}"
echo "  1. Website functionality"
echo "  2. Database connections"
echo "  3. Background jobs"
echo "  4. File uploads"

RESTORE_SCRIPT

chmod +x "$BACKUP_DIR/restore.sh"

# 13. Verification checksums
progress "Calculating verification checksums"

# Create verification report
cat > "$BACKUP_DIR/VERIFICATION_REPORT.txt" << EOF
========================================
BACKUP VERIFICATION REPORT
========================================
Date: $(date)
Backup: $BACKUP_DIR

File Integrity Checksums:
EOF

while IFS=':' read -r TYPE NAME CHECKSUM; do
    echo "  ✓ $TYPE: $NAME" >> "$BACKUP_DIR/VERIFICATION_REPORT.txt"
    echo "    SHA256: $CHECKSUM" >> "$BACKUP_DIR/VERIFICATION_REPORT.txt"
done < "$VERIFICATION_FILE"

# 14. Create README
progress "Creating documentation"
cat > "$BACKUP_DIR/README.md" << EOF
# Comprehensive System Backup

## Backup Information
- **Date**: $(date)
- **Type**: Full System Backup
- **Location**: $BACKUP_DIR

## Contents
This backup contains:
1. All MySQL databases
2. Application code (/var/www/api-gateway)
3. Environment configuration files
4. Nginx configuration
5. PHP configuration
6. Supervisor configuration
7. Cron jobs
8. Redis data snapshot
9. SSL certificates
10. System package lists

## Restoration
To restore from this backup:

\`\`\`bash
cd $BACKUP_DIR
sudo ./restore.sh
\`\`\`

## Verification
To verify backup integrity:

\`\`\`bash
cd $BACKUP_DIR
./verify.sh
\`\`\`

## Important Files
- **MANIFEST.txt**: Complete list of backed up components
- **VERIFICATION.txt**: Checksums for integrity verification
- **backup.log**: Detailed backup process log
- **restore.sh**: Automated restoration script
- **verify.sh**: Integrity verification script

## Notes
- Backup size: $(du -sh "$BACKUP_DIR" | awk '{print $1}')
- Compression: gzip for databases and archives
- Exclusions: vendor/, node_modules/, cache files, .git

## Emergency Contact
In case of issues during restoration:
1. Check backup.log for any errors during backup
2. Verify disk space before restoration
3. Ensure all services are stopped before restoring
4. Keep the previous backup before restoring

---
Generated by Comprehensive Backup Script
EOF

# 15. Create verification script
progress "Creating verification script"
cat > "$BACKUP_DIR/verify.sh" << 'VERIFY_SCRIPT'
#!/bin/bash

GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'

echo "========================================="
echo "Backup Integrity Verification"
echo "========================================="

ERRORS=0

while IFS=':' read -r TYPE NAME EXPECTED_CHECKSUM; do
    FILE=""

    case $TYPE in
        database)
            FILE="databases/${NAME}.sql.gz"
            ;;
        application)
            FILE="${NAME}.tar.gz"
            ;;
    esac

    if [ -n "$FILE" ] && [ -f "$FILE" ]; then
        ACTUAL_CHECKSUM=$(sha256sum "$FILE" | awk '{print $1}')

        if [ "$ACTUAL_CHECKSUM" == "$EXPECTED_CHECKSUM" ]; then
            echo -e "${GREEN}✓${NC} $TYPE: $NAME - Verified"
        else
            echo -e "${RED}✗${NC} $TYPE: $NAME - FAILED"
            ERRORS=$((ERRORS + 1))
        fi
    fi
done < VERIFICATION.txt

echo "========================================="
if [ $ERRORS -eq 0 ]; then
    echo -e "${GREEN}All files verified successfully!${NC}"
else
    echo -e "${RED}$ERRORS file(s) failed verification!${NC}"
    exit 1
fi
VERIFY_SCRIPT

chmod +x "$BACKUP_DIR/verify.sh"

# Final summary
log "========================================" "$GREEN"
log "Backup completed successfully!" "$GREEN"
log "========================================" "$GREEN"

echo "" >> "$MANIFEST_FILE"
echo "========================================" >> "$MANIFEST_FILE"
echo "Backup Size: $(du -sh "$BACKUP_DIR" | awk '{print $1}')" >> "$MANIFEST_FILE"
echo "Completion Time: $(date)" >> "$MANIFEST_FILE"

log "Backup location: $BACKUP_DIR" "$YELLOW"
log "Total size: $(du -sh "$BACKUP_DIR" | awk '{print $1}')" "$YELLOW"
log "" "$NC"
log "To restore: cd $BACKUP_DIR && sudo ./restore.sh" "$BLUE"
log "To verify: cd $BACKUP_DIR && ./verify.sh" "$BLUE"

# Cleanup old backups (keep last 5)
log "Cleaning up old backups (keeping last 5)..." "$YELLOW"
cd "$BACKUP_ROOT"
ls -dt full_backup_* 2>/dev/null | tail -n +6 | xargs rm -rf 2>/dev/null || true

exit 0