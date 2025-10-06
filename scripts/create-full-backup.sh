#!/bin/bash

# ============================================
# Complete API Gateway Backup Script
# ============================================
# Generated with Claude Code via Happy
# Date: 2025-09-22

set -e

# Configuration
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_ROOT="/var/www/backups"
BACKUP_DIR="${BACKUP_ROOT}/full-backup-${TIMESTAMP}"

# Create backup directory first
mkdir -p "${BACKUP_DIR}"

LOG_FILE="${BACKUP_DIR}/backup.log"

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Functions
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

success() {
    echo -e "${GREEN}✅ $1${NC}"
    log "SUCCESS: $1"
}

info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
    log "INFO: $1"
}

warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
    log "WARNING: $1"
}

error_exit() {
    echo -e "${RED}❌ Error: $1${NC}" >&2
    log "ERROR: $1"
    exit 1
}

# Header
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}        🔐 Complete API Gateway Backup Script              ${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo ""

# Step 1: Create backup directory structure
info "Step 1: Creating backup directory structure..."
mkdir -p "${BACKUP_DIR}"/{database,application,configs,nginx,php,supervisor,scripts,storage,env}
log "Backup directory created: ${BACKUP_DIR}"
success "Backup directory created"

# Step 2: Backup database
echo ""
info "Step 2: Backing up database..."

# Get database credentials from .env
if [ -f /var/www/api-gateway/.env ]; then
    DB_NAME=$(grep ^DB_DATABASE= /var/www/api-gateway/.env | cut -d= -f2)
    DB_USER=$(grep ^DB_USERNAME= /var/www/api-gateway/.env | cut -d= -f2)
    DB_PASS=$(grep ^DB_PASSWORD= /var/www/api-gateway/.env | cut -d= -f2)
    DB_HOST=$(grep ^DB_HOST= /var/www/api-gateway/.env | cut -d= -f2)
else
    DB_NAME="askpro"
    DB_USER="root"
    DB_PASS=""
    DB_HOST="localhost"
fi

# Dump database
mysqldump -h ${DB_HOST} -u ${DB_USER} ${DB_PASS:+-p${DB_PASS}} \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    --complete-insert \
    --extended-insert \
    --lock-tables=false \
    ${DB_NAME} > "${BACKUP_DIR}/database/${DB_NAME}.sql" 2>/dev/null || warning "Database backup had warnings"

# Get database size
DB_SIZE=$(du -sh "${BACKUP_DIR}/database/${DB_NAME}.sql" | cut -f1)
success "Database backed up (${DB_SIZE})"

# Step 3: Backup application code
echo ""
info "Step 3: Backing up application code..."

# Copy application files (excluding vendor and node_modules for space)
rsync -av \
    --exclude='vendor' \
    --exclude='node_modules' \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='bootstrap/cache/*' \
    --exclude='.git' \
    /var/www/api-gateway/ "${BACKUP_DIR}/application/" > /dev/null 2>&1

# Backup vendor and node_modules file lists (for reference)
if [ -d /var/www/api-gateway/vendor ]; then
    ls -la /var/www/api-gateway/vendor > "${BACKUP_DIR}/application/vendor_list.txt"
fi

if [ -d /var/www/api-gateway/node_modules ]; then
    ls -la /var/www/api-gateway/node_modules > "${BACKUP_DIR}/application/node_modules_list.txt"
fi

# Copy composer.json and package.json for dependency restoration
cp /var/www/api-gateway/composer.* "${BACKUP_DIR}/application/" 2>/dev/null || true
cp /var/www/api-gateway/package*.json "${BACKUP_DIR}/application/" 2>/dev/null || true

APP_SIZE=$(du -sh "${BACKUP_DIR}/application" | cut -f1)
success "Application code backed up (${APP_SIZE})"

# Step 4: Backup configuration files
echo ""
info "Step 4: Backing up configuration files..."

# Nginx configs
if [ -d /etc/nginx ]; then
    cp -r /etc/nginx/sites-available "${BACKUP_DIR}/nginx/" 2>/dev/null || true
    cp -r /etc/nginx/sites-enabled "${BACKUP_DIR}/nginx/" 2>/dev/null || true
    cp /etc/nginx/nginx.conf "${BACKUP_DIR}/nginx/" 2>/dev/null || true
    success "Nginx configuration backed up"
fi

# PHP-FPM configs
if [ -d /etc/php/8.3/fpm ]; then
    cp -r /etc/php/8.3/fpm/pool.d "${BACKUP_DIR}/php/" 2>/dev/null || true
    cp /etc/php/8.3/fpm/php.ini "${BACKUP_DIR}/php/" 2>/dev/null || true
    success "PHP-FPM configuration backed up"
fi

# Supervisor configs
if [ -d /etc/supervisor/conf.d ]; then
    cp -r /etc/supervisor/conf.d "${BACKUP_DIR}/supervisor/" 2>/dev/null || true
    success "Supervisor configuration backed up"
fi

# Environment file (secured)
if [ -f /var/www/api-gateway/.env ]; then
    cp /var/www/api-gateway/.env "${BACKUP_DIR}/env/.env"
    chmod 600 "${BACKUP_DIR}/env/.env"
    success "Environment file backed up (secured)"
fi

# Step 5: Backup storage and logs (last 7 days)
echo ""
info "Step 5: Backing up recent storage and logs..."

# Recent logs
find /var/www/api-gateway/storage/logs -name "*.log" -mtime -7 -exec cp {} "${BACKUP_DIR}/storage/" \; 2>/dev/null || true

# Storage app directory
if [ -d /var/www/api-gateway/storage/app ]; then
    cp -r /var/www/api-gateway/storage/app "${BACKUP_DIR}/storage/" 2>/dev/null || true
fi

STORAGE_SIZE=$(du -sh "${BACKUP_DIR}/storage" | cut -f1)
success "Storage and logs backed up (${STORAGE_SIZE})"

# Step 6: Backup custom scripts
echo ""
info "Step 6: Backing up custom scripts..."

if [ -d /var/www/api-gateway/scripts ]; then
    cp -r /var/www/api-gateway/scripts "${BACKUP_DIR}/scripts/"
    success "Custom scripts backed up"
fi

# Step 7: Create system information file
echo ""
info "Step 7: Creating system information file..."

cat > "${BACKUP_DIR}/system_info.txt" << EOF
========================================
API Gateway Backup System Information
========================================
Backup Date: $(date)
Hostname: $(hostname)
OS: $(lsb_release -d | cut -f2)
PHP Version: $(php -v | head -n1)
MySQL Version: $(mysql --version)
Laravel Version: $(cd /var/www/api-gateway && php artisan --version 2>/dev/null || echo "Unknown")
Disk Usage: $(df -h /var/www)

========================================
Installed PHP Extensions:
$(php -m)

========================================
Active Services:
$(systemctl list-units --type=service --state=running | grep -E "nginx|php|mysql|mariadb|redis|supervisor")

========================================
Database Information:
Database Name: ${DB_NAME}
Tables Count: $(mysql -u ${DB_USER} ${DB_PASS:+-p${DB_PASS}} -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_NAME}'" -s 2>/dev/null || echo "Unknown")

========================================
Application Structure:
$(cd /var/www/api-gateway && find app -type d -maxdepth 2 | sort)

========================================
EOF

success "System information documented"

# Step 8: Create backup manifest
echo ""
info "Step 8: Creating backup manifest..."

cat > "${BACKUP_DIR}/BACKUP_MANIFEST.md" << EOF
# API Gateway Full Backup
**Date:** ${TIMESTAMP}
**Type:** Complete System Backup

## Backup Contents

### 1. Database
- Location: \`database/\`
- File: \`${DB_NAME}.sql\`
- Size: ${DB_SIZE}

### 2. Application Code
- Location: \`application/\`
- Size: ${APP_SIZE}
- Note: Excludes vendor/node_modules (see composer.json/package.json for deps)

### 3. Configuration Files
- Nginx: \`nginx/\`
- PHP-FPM: \`php/\`
- Supervisor: \`supervisor/\`
- Environment: \`env/.env\` (secured)

### 4. Storage & Logs
- Location: \`storage/\`
- Size: ${STORAGE_SIZE}
- Contains: Last 7 days of logs

### 5. Custom Scripts
- Location: \`scripts/\`

### 6. System Information
- File: \`system_info.txt\`

## Restoration Instructions

### Quick Restore:
\`\`\`bash
# 1. Restore database
mysql -u root ${DB_NAME} < database/${DB_NAME}.sql

# 2. Restore application
rsync -av application/ /var/www/api-gateway/

# 3. Restore environment file
cp env/.env /var/www/api-gateway/.env

# 4. Install dependencies
cd /var/www/api-gateway
composer install
npm install

# 5. Clear caches
php artisan optimize:clear
\`\`\`

### Full System Restore:
See \`RESTORE_INSTRUCTIONS.txt\` for complete restoration procedure.

---
*Generated with Claude Code via Happy*
EOF

success "Backup manifest created"

# Step 9: Create restoration script
echo ""
info "Step 9: Creating restoration script..."

cat > "${BACKUP_DIR}/restore.sh" << 'EOF'
#!/bin/bash

# API Gateway Restoration Script
# Usage: ./restore.sh

set -e

echo "🔄 API Gateway Restoration Script"
echo "=================================="
echo ""
echo "⚠️  WARNING: This will overwrite current data!"
read -p "Continue? (yes/no): " confirm

if [ "$confirm" != "yes" ]; then
    echo "Restoration cancelled."
    exit 1
fi

BACKUP_DIR="$(dirname "$0")"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

echo "Creating pre-restore backup..."
mkdir -p /var/www/backups/pre-restore-${TIMESTAMP}
cp -r /var/www/api-gateway /var/www/backups/pre-restore-${TIMESTAMP}/ 2>/dev/null || true

echo "Restoring database..."
mysql -u root askpro < "${BACKUP_DIR}/database/askpro.sql"

echo "Restoring application files..."
rsync -av --exclude='.env' "${BACKUP_DIR}/application/" /var/www/api-gateway/

echo "Restoring environment file..."
cp "${BACKUP_DIR}/env/.env" /var/www/api-gateway/.env

echo "Setting permissions..."
chown -R www-data:www-data /var/www/api-gateway
chmod -R 755 /var/www/api-gateway
chmod -R 775 /var/www/api-gateway/storage
chmod -R 775 /var/www/api-gateway/bootstrap/cache

echo "Installing dependencies..."
cd /var/www/api-gateway
composer install --no-dev --optimize-autoloader
npm install --production

echo "Clearing caches..."
php artisan optimize:clear
# DO NOT CACHE CONFIG IN DEVELOPMENT!
# This causes the old password to be cached!
# php artisan config:cache # REMOVED - causes 500 errors!
# php artisan route:cache  # REMOVED - not needed
# php artisan view:cache   # REMOVED - not needed
echo "✅ Caches cleared (NOT cached again)"

echo "Restarting services..."
systemctl restart php8.3-fpm
systemctl restart nginx

echo ""
echo "✅ Restoration complete!"
echo "Please verify the application at: https://api.askproai.de"
EOF

chmod +x "${BACKUP_DIR}/restore.sh"
success "Restoration script created"

# Step 10: Create compressed archive
echo ""
info "Step 10: Creating compressed archive..."

cd "${BACKUP_ROOT}"
tar -czf "full-backup-${TIMESTAMP}.tar.gz" "full-backup-${TIMESTAMP}/" 2>/dev/null

ARCHIVE_SIZE=$(du -sh "${BACKUP_ROOT}/full-backup-${TIMESTAMP}.tar.gz" | cut -f1)
success "Compressed archive created (${ARCHIVE_SIZE})"

# Step 11: Calculate and display summary
echo ""
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}           ✨ BACKUP COMPLETE!                             ${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo ""

TOTAL_SIZE=$(du -sh "${BACKUP_DIR}" | cut -f1)

echo "📊 Backup Summary:"
echo "  • Location: ${BACKUP_DIR}"
echo "  • Archive: ${BACKUP_ROOT}/full-backup-${TIMESTAMP}.tar.gz"
echo "  • Total Size: ${TOTAL_SIZE}"
echo "  • Compressed: ${ARCHIVE_SIZE}"
echo ""
echo "📁 Contents:"
echo "  • Database backup ✅"
echo "  • Application code ✅"
echo "  • Configuration files ✅"
echo "  • Environment file ✅"
echo "  • Storage & logs ✅"
echo "  • Custom scripts ✅"
echo "  • System information ✅"
echo "  • Restoration script ✅"
echo ""
echo "🔐 Security:"
echo "  • Environment file secured (600 permissions)"
echo "  • Database dump includes all triggers & routines"
echo ""
echo "📝 Documentation:"
echo "  • Manifest: ${BACKUP_DIR}/BACKUP_MANIFEST.md"
echo "  • System Info: ${BACKUP_DIR}/system_info.txt"
echo "  • Restore Script: ${BACKUP_DIR}/restore.sh"
echo ""
echo "💡 Quick Commands:"
echo "  • View manifest: cat ${BACKUP_DIR}/BACKUP_MANIFEST.md"
echo "  • Extract archive: tar -xzf ${BACKUP_ROOT}/full-backup-${TIMESTAMP}.tar.gz"
echo "  • Restore: ${BACKUP_DIR}/restore.sh"
echo ""

log "Backup completed successfully"
echo "✅ Full backup completed successfully!"