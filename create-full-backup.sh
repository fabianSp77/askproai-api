#!/bin/bash

# Full Backup Script for AskProAI
# Created: 2025-08-05
# This script creates a comprehensive backup before major changes

BACKUP_DATE="20250805-230451"
BACKUP_BASE="/var/www/backups"
PROJECT_DIR="/var/www/api-gateway"
BACKUP_DIR="${BACKUP_BASE}/askproai-full-backup-${BACKUP_DATE}"

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${GREEN}=== AskProAI Full System Backup ===${NC}"
echo -e "Backup Date: ${BACKUP_DATE}"
echo -e "Backup Location: ${BACKUP_DIR}"
echo ""

# Create backup directory structure
echo -e "${YELLOW}Creating backup directories...${NC}"
mkdir -p "${BACKUP_DIR}"
mkdir -p "${BACKUP_DIR}/database"
mkdir -p "${BACKUP_DIR}/application"
mkdir -p "${BACKUP_DIR}/config"
mkdir -p "${BACKUP_DIR}/logs"

# 1. Database Backup
echo -e "\n${YELLOW}1. Backing up database...${NC}"
mysqldump -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db --single-transaction --routines --triggers --add-drop-table > "${BACKUP_DIR}/database/askproai_db_${BACKUP_DATE}.sql"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Database backup completed${NC}"
    # Compress database backup
    gzip "${BACKUP_DIR}/database/askproai_db_${BACKUP_DATE}.sql"
    echo -e "${GREEN}✓ Database backup compressed${NC}"
else
    echo -e "${RED}✗ Database backup failed${NC}"
    exit 1
fi

# 2. Application Code Backup
echo -e "\n${YELLOW}2. Backing up application code...${NC}"
cd "${PROJECT_DIR}"

# Create list of files to backup (excluding vendor, node_modules, etc.)
tar -czf "${BACKUP_DIR}/application/app-code-${BACKUP_DATE}.tar.gz" \
    --exclude='vendor' \
    --exclude='node_modules' \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='bootstrap/cache/*' \
    --exclude='.git' \
    --exclude='*.log' \
    app/ \
    config/ \
    database/ \
    public/ \
    resources/ \
    routes/ \
    composer.json \
    composer.lock \
    package.json \
    package-lock.json \
    .env \
    .env.example \
    vite.config.js \
    tailwind.config.js \
    CLAUDE.md

echo -e "${GREEN}✓ Application code backup completed${NC}"

# 3. Storage Files Backup (uploaded files, etc.)
echo -e "\n${YELLOW}3. Backing up storage files...${NC}"
if [ -d "storage/app" ]; then
    tar -czf "${BACKUP_DIR}/application/storage-files-${BACKUP_DATE}.tar.gz" \
        storage/app/
    echo -e "${GREEN}✓ Storage files backup completed${NC}"
else
    echo -e "${YELLOW}⚠ No storage files to backup${NC}"
fi

# 4. Environment and Config Backup
echo -e "\n${YELLOW}4. Backing up configuration...${NC}"
cp .env "${BACKUP_DIR}/config/.env.backup"
cp -r config/ "${BACKUP_DIR}/config/"

# Also backup nginx config if accessible
if [ -f "/etc/nginx/sites-available/api.askproai.de" ]; then
    cp "/etc/nginx/sites-available/api.askproai.de" "${BACKUP_DIR}/config/nginx-site.conf"
fi

# Backup supervisor config if exists
if [ -f "/etc/supervisor/conf.d/horizon.conf" ]; then
    cp "/etc/supervisor/conf.d/horizon.conf" "${BACKUP_DIR}/config/horizon-supervisor.conf"
fi

echo -e "${GREEN}✓ Configuration backup completed${NC}"

# 5. Create backup manifest
echo -e "\n${YELLOW}5. Creating backup manifest...${NC}"
cat > "${BACKUP_DIR}/BACKUP_MANIFEST.txt" << EOF
AskProAI Full System Backup
===========================
Backup Date: ${BACKUP_DATE}
Created At: $(date)
Laravel Version: $(php artisan --version 2>/dev/null || echo "Unknown")
PHP Version: $(php -v | head -n 1)
MySQL Version: $(mysql --version)

Backup Contents:
----------------
1. Database: askproai_db (compressed)
2. Application Code: All project files except vendor/node_modules
3. Storage Files: User uploads and app storage
4. Configuration: .env, config files, nginx, supervisor

Current Git Branch: $(git branch --show-current 2>/dev/null || echo "Unknown")
Last Commit: $(git log -1 --oneline 2>/dev/null || echo "Unknown")

Backup Size:
EOF

# Calculate backup size
du -sh "${BACKUP_DIR}"/* >> "${BACKUP_DIR}/BACKUP_MANIFEST.txt"

# 6. Create restore script
echo -e "\n${YELLOW}6. Creating restore script...${NC}"
cat > "${BACKUP_DIR}/restore-backup.sh" << 'EOF'
#!/bin/bash

# Restore Script for AskProAI Backup
# WARNING: This will overwrite existing data!

BACKUP_DIR="$(dirname "$0")"
RESTORE_TARGET="/var/www/api-gateway"

echo "=== AskProAI Backup Restore Script ==="
echo "Backup Location: ${BACKUP_DIR}"
echo "Restore Target: ${RESTORE_TARGET}"
echo ""
echo "WARNING: This will overwrite existing data!"
read -p "Are you sure you want to continue? (yes/no): " confirm

if [ "$confirm" != "yes" ]; then
    echo "Restore cancelled."
    exit 0
fi

# 1. Restore Database
echo "Restoring database..."
gunzip -c "${BACKUP_DIR}/database/"*.sql.gz | mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db

# 2. Restore Application Code
echo "Restoring application code..."
cd "${RESTORE_TARGET}"
tar -xzf "${BACKUP_DIR}/application/app-code-"*.tar.gz

# 3. Restore Storage Files
if [ -f "${BACKUP_DIR}/application/storage-files-"*.tar.gz ]; then
    echo "Restoring storage files..."
    tar -xzf "${BACKUP_DIR}/application/storage-files-"*.tar.gz
fi

# 4. Set permissions
echo "Setting permissions..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 5. Clear caches
echo "Clearing caches..."
php artisan optimize:clear

echo "Restore completed!"
echo "Don't forget to:"
echo "- Check .env configuration"
echo "- Run: composer install"
echo "- Run: npm install && npm run build"
echo "- Restart services: php-fpm, nginx, horizon"
EOF

chmod +x "${BACKUP_DIR}/restore-backup.sh"
echo -e "${GREEN}✓ Restore script created${NC}"

# 7. Create final archive
echo -e "\n${YELLOW}7. Creating final compressed archive...${NC}"
cd "${BACKUP_BASE}"
tar -czf "askproai-full-backup-${BACKUP_DATE}.tar.gz" "askproai-full-backup-${BACKUP_DATE}/"

# Calculate final size
FINAL_SIZE=$(du -h "askproai-full-backup-${BACKUP_DATE}.tar.gz" | cut -f1)

echo -e "\n${GREEN}=== Backup Complete ===${NC}"
echo -e "Backup Location: ${BACKUP_BASE}/askproai-full-backup-${BACKUP_DATE}.tar.gz"
echo -e "Backup Size: ${FINAL_SIZE}"
echo -e "\n${YELLOW}Important:${NC}"
echo -e "- Store this backup in a safe location"
echo -e "- Consider copying to external storage"
echo -e "- Test the restore script in a development environment first"

# Create a quick backup info file
cat > "${PROJECT_DIR}/LAST_BACKUP_INFO.txt" << EOF
Last Full Backup
================
Date: ${BACKUP_DATE}
Location: ${BACKUP_BASE}/askproai-full-backup-${BACKUP_DATE}.tar.gz
Size: ${FINAL_SIZE}
Created: $(date)

To restore, extract the archive and run:
./restore-backup.sh

To verify backup:
tar -tzf ${BACKUP_BASE}/askproai-full-backup-${BACKUP_DATE}.tar.gz | head -20
EOF

echo -e "\n${GREEN}Backup info saved to: ${PROJECT_DIR}/LAST_BACKUP_INFO.txt${NC}"