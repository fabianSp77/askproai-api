#!/bin/bash

#########################################
# GOLDEN BACKUP - Complete System Backup
# Created: $(date +"%Y-%m-%d %H:%M:%S")
# Purpose: Complete system backup for restoration
#########################################

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
BACKUP_ROOT="/var/www/backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_NAME="golden-backup-${TIMESTAMP}"
BACKUP_DIR="${BACKUP_ROOT}/${BACKUP_NAME}"
APP_ROOT="/var/www/api-gateway"
LOG_FILE="${BACKUP_DIR}/backup.log"

# Create log function
log() {
    echo -e "${GREEN}[$(date +"%H:%M:%S")]${NC} $1" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1" | tee -a "$LOG_FILE"
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1" | tee -a "$LOG_FILE"
}

info() {
    echo -e "${BLUE}[INFO]${NC} $1" | tee -a "$LOG_FILE"
}

# Start backup
echo -e "${GREEN}╔═══════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║     GOLDEN BACKUP - System Backup        ║${NC}"
echo -e "${GREEN}║          ${TIMESTAMP}           ║${NC}"
echo -e "${GREEN}╚═══════════════════════════════════════════╝${NC}"
echo ""

# 1. Create backup directory structure
log "Creating backup directory structure..."
mkdir -p "${BACKUP_DIR}"/{app,database,config,storage,system,docs}
mkdir -p "$BACKUP_ROOT"

# 2. Backup application code
log "Backing up application code..."
cd "$APP_ROOT" || exit 1

# Create exclude file for tar
cat > "${BACKUP_DIR}/exclude.txt" << EOF
vendor
node_modules
.git
storage/app/public/*
storage/framework/cache/*
storage/framework/sessions/*
storage/framework/views/*
storage/logs/*
*.log
.env
EOF

# Archive application code
tar -czf "${BACKUP_DIR}/app/application.tar.gz" \
    --exclude-from="${BACKUP_DIR}/exclude.txt" \
    . 2>/dev/null

if [ $? -eq 0 ]; then
    log "Application code backed up successfully"
    APP_SIZE=$(du -h "${BACKUP_DIR}/app/application.tar.gz" | cut -f1)
    info "Application backup size: $APP_SIZE"
else
    error "Failed to backup application code"
fi

# 3. Database backup
log "Creating database dumps..."

# Read database credentials from .env (strip comments, whitespace, quotes)
if [ -f "${APP_ROOT}/.env" ]; then
    DB_CONNECTION=$(grep DB_CONNECTION "${APP_ROOT}/.env" | cut -d '=' -f2- | sed 's/#.*//' | sed 's/^[[:space:]]*//;s/[[:space:]]*$//' | tr -d '"' | tr -d "'")
    DB_HOST=$(grep DB_HOST "${APP_ROOT}/.env" | cut -d '=' -f2- | sed 's/#.*//' | sed 's/^[[:space:]]*//;s/[[:space:]]*$//' | tr -d '"' | tr -d "'")
    DB_PORT=$(grep DB_PORT "${APP_ROOT}/.env" | cut -d '=' -f2- | sed 's/#.*//' | sed 's/^[[:space:]]*//;s/[[:space:]]*$//' | tr -d '"' | tr -d "'")
    DB_DATABASE=$(grep DB_DATABASE "${APP_ROOT}/.env" | cut -d '=' -f2- | sed 's/#.*//' | sed 's/^[[:space:]]*//;s/[[:space:]]*$//' | tr -d '"' | tr -d "'")
    DB_USERNAME=$(grep DB_USERNAME "${APP_ROOT}/.env" | cut -d '=' -f2- | sed 's/#.*//' | sed 's/^[[:space:]]*//;s/[[:space:]]*$//' | tr -d '"' | tr -d "'")
    DB_PASSWORD=$(grep DB_PASSWORD "${APP_ROOT}/.env" | cut -d '=' -f2- | sed 's/#.*//' | sed 's/^[[:space:]]*//;s/[[:space:]]*$//' | tr -d '"' | tr -d "'")

    # Full database dump
    log "Creating full database dump..."
    mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" \
        --single-transaction --routines --triggers --events \
        "$DB_DATABASE" > "${BACKUP_DIR}/database/full_dump.sql" 2>/dev/null

    if [ $? -eq 0 ]; then
        gzip "${BACKUP_DIR}/database/full_dump.sql"
        log "Full database dump created"
        DB_SIZE=$(du -h "${BACKUP_DIR}/database/full_dump.sql.gz" | cut -f1)
        info "Database backup size: $DB_SIZE"
    else
        error "Failed to create database dump"
    fi

    # Schema-only dump
    log "Creating schema-only dump..."
    mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" \
        --no-data --routines --triggers --events \
        "$DB_DATABASE" > "${BACKUP_DIR}/database/schema_only.sql" 2>/dev/null

    if [ $? -eq 0 ]; then
        gzip "${BACKUP_DIR}/database/schema_only.sql"
        log "Schema-only dump created"
    fi

    # Table information
    mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" \
        -e "SELECT TABLE_NAME, TABLE_ROWS, ROUND(DATA_LENGTH/1024/1024, 2) AS 'Data_MB' \
        FROM information_schema.TABLES WHERE TABLE_SCHEMA='$DB_DATABASE' \
        ORDER BY DATA_LENGTH DESC;" > "${BACKUP_DIR}/database/table_info.txt" 2>/dev/null
else
    error ".env file not found, skipping database backup"
fi

# 4. Backup environment files and configurations
log "Backing up environment files and configurations..."

# Copy .env files
if [ -f "${APP_ROOT}/.env" ]; then
    cp "${APP_ROOT}/.env" "${BACKUP_DIR}/config/env.production"
    log ".env file backed up"
fi

if [ -f "${APP_ROOT}/.env.example" ]; then
    cp "${APP_ROOT}/.env.example" "${BACKUP_DIR}/config/env.example"
fi

# Copy package files
cp "${APP_ROOT}/composer.json" "${BACKUP_DIR}/config/" 2>/dev/null
cp "${APP_ROOT}/composer.lock" "${BACKUP_DIR}/config/" 2>/dev/null
cp "${APP_ROOT}/package.json" "${BACKUP_DIR}/config/" 2>/dev/null
cp "${APP_ROOT}/package-lock.json" "${BACKUP_DIR}/config/" 2>/dev/null
log "Package files backed up"

# 5. Backup storage (only important data)
log "Backing up storage data..."
if [ -d "${APP_ROOT}/storage/app" ]; then
    # Exclude public symlink and temp files
    tar -czf "${BACKUP_DIR}/storage/app_storage.tar.gz" \
        -C "${APP_ROOT}/storage" \
        --exclude="app/public" \
        --exclude="*.tmp" \
        app 2>/dev/null

    if [ $? -eq 0 ]; then
        log "Storage data backed up"
        STORAGE_SIZE=$(du -h "${BACKUP_DIR}/storage/app_storage.tar.gz" | cut -f1)
        info "Storage backup size: $STORAGE_SIZE"
    fi
fi

# 6. System information
log "Collecting system information..."
{
    echo "=== SYSTEM INFORMATION ==="
    echo "Backup Date: $(date)"
    echo "Hostname: $(hostname)"
    echo "OS: $(cat /etc/os-release | grep PRETTY_NAME | cut -d '=' -f2)"
    echo "Kernel: $(uname -r)"
    echo "PHP Version: $(php -v | head -n 1)"
    echo "MySQL Version: $(mysql --version)"
    echo "Node Version: $(node -v 2>/dev/null || echo 'Not installed')"
    echo "NPM Version: $(npm -v 2>/dev/null || echo 'Not installed')"
    echo ""
    echo "=== DISK USAGE ==="
    df -h "$APP_ROOT"
    echo ""
    echo "=== DIRECTORY SIZES ==="
    du -sh "${APP_ROOT}"/* 2>/dev/null | sort -h
} > "${BACKUP_DIR}/system/system_info.txt"

# 7. Generate checksums
log "Generating checksums..."
cd "$BACKUP_DIR" || exit 1
find . -type f -exec sha256sum {} \; > checksums.txt
log "Checksums generated"

# 8. Create restoration script
log "Creating restoration documentation..."
cat > "${BACKUP_DIR}/docs/RESTORE_GUIDE.md" << 'EOF'
# Golden Backup - Restoration Guide

## Backup Information
- **Created:** TIMESTAMP_PLACEHOLDER
- **Location:** BACKUP_DIR_PLACEHOLDER

## Prerequisites
- MySQL/MariaDB server
- PHP 8.1+
- Composer
- Node.js & NPM
- Web server (Nginx/Apache)

## Restoration Steps

### 1. Prepare Environment
```bash
# Create application directory
sudo mkdir -p /var/www/api-gateway
cd /var/www/api-gateway

# Extract application code
tar -xzf /path/to/backup/app/application.tar.gz
```

### 2. Restore Database
```bash
# Create database if not exists
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS your_database;"

# Restore database dump
gunzip < /path/to/backup/database/full_dump.sql.gz | mysql -u root -p your_database
```

### 3. Configure Environment
```bash
# Copy environment file
cp /path/to/backup/config/env.production .env

# Update .env with new server details if needed
nano .env
```

### 4. Install Dependencies
```bash
# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node dependencies
npm install

# Build assets
npm run build
```

### 5. Restore Storage
```bash
# Extract storage data
tar -xzf /path/to/backup/storage/app_storage.tar.gz -C storage/

# Set permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 6. Final Steps
```bash
# Generate application key
php artisan key:generate

# Clear and rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations (if needed)
php artisan migrate

# Create storage symlink
php artisan storage:link
```

### 7. Web Server Configuration
Configure your web server to point to `/var/www/api-gateway/public`

### 8. Verify Restoration
- Check application loads correctly
- Verify database connectivity
- Test key functionality
- Review logs for errors

## Verification Checklist
- [ ] Application accessible
- [ ] Database connected
- [ ] Storage accessible
- [ ] APIs functional
- [ ] Background jobs running
- [ ] Logs writing correctly

## Troubleshooting
- **Permission Issues:** Ensure www-data owns application files
- **Database Errors:** Check credentials in .env
- **Missing Dependencies:** Run composer install and npm install
- **Cache Issues:** Clear all caches with php artisan cache:clear

## Important Files in Backup
- `app/application.tar.gz` - Application code
- `database/full_dump.sql.gz` - Complete database
- `config/env.production` - Environment configuration
- `storage/app_storage.tar.gz` - User uploads and data
- `checksums.txt` - File integrity verification
EOF

# Replace placeholders
sed -i "s/TIMESTAMP_PLACEHOLDER/$(date)/g" "${BACKUP_DIR}/docs/RESTORE_GUIDE.md"
sed -i "s|BACKUP_DIR_PLACEHOLDER|${BACKUP_DIR}|g" "${BACKUP_DIR}/docs/RESTORE_GUIDE.md"

# 9. Create backup metadata
log "Creating backup metadata..."
cat > "${BACKUP_DIR}/metadata.json" << EOF
{
    "backup_name": "${BACKUP_NAME}",
    "backup_date": "$(date -Iseconds)",
    "backup_type": "golden-backup",
    "source_path": "${APP_ROOT}",
    "php_version": "$(php -v | head -n 1)",
    "database": "${DB_DATABASE}",
    "total_size": "$(du -sh ${BACKUP_DIR} | cut -f1)",
    "components": {
        "application": true,
        "database": true,
        "storage": true,
        "configuration": true,
        "documentation": true
    }
}
EOF

# 10. Create final compressed archive
log "Creating final compressed archive..."
cd "$BACKUP_ROOT" || exit 1
tar -czf "${BACKUP_NAME}.tar.gz" "${BACKUP_NAME}/"

if [ $? -eq 0 ]; then
    FINAL_SIZE=$(du -h "${BACKUP_NAME}.tar.gz" | cut -f1)
    log "Final backup archive created: ${BACKUP_NAME}.tar.gz"
    info "Total backup size: $FINAL_SIZE"

    # Create quick restore script
    cat > "${BACKUP_ROOT}/quick-restore-${TIMESTAMP}.sh" << EOF
#!/bin/bash
# Quick restore script for Golden Backup
# Created: $(date)

BACKUP_FILE="${BACKUP_ROOT}/${BACKUP_NAME}.tar.gz"

echo "This will restore the Golden Backup from \${BACKUP_FILE}"
echo "WARNING: This will overwrite existing data!"
read -p "Continue? (y/n) " -n 1 -r
echo
if [[ \$REPLY =~ ^[Yy]$ ]]; then
    tar -xzf "\${BACKUP_FILE}" -C /tmp/
    echo "Backup extracted to /tmp/${BACKUP_NAME}"
    echo "Follow the restoration guide at /tmp/${BACKUP_NAME}/docs/RESTORE_GUIDE.md"
fi
EOF
    chmod +x "${BACKUP_ROOT}/quick-restore-${TIMESTAMP}.sh"
else
    error "Failed to create final archive"
fi

# Summary
echo ""
echo -e "${GREEN}╔═══════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║        BACKUP COMPLETED SUCCESSFULLY      ║${NC}"
echo -e "${GREEN}╚═══════════════════════════════════════════╝${NC}"
echo ""
echo -e "${BLUE}Backup Location:${NC} ${BACKUP_DIR}"
echo -e "${BLUE}Archive:${NC} ${BACKUP_ROOT}/${BACKUP_NAME}.tar.gz"
echo -e "${BLUE}Size:${NC} ${FINAL_SIZE}"
echo -e "${BLUE}Restore Guide:${NC} ${BACKUP_DIR}/docs/RESTORE_GUIDE.md"
echo -e "${BLUE}Quick Restore:${NC} ${BACKUP_ROOT}/quick-restore-${TIMESTAMP}.sh"
echo ""
log "Golden Backup completed successfully!"