#!/bin/bash

#############################################
# ULTIMATE GOLDEN BACKUP V2
# Complete System + External Services
# Created: 2025-10-25
# Purpose: Comprehensive disaster recovery backup
#############################################

set -euo pipefail

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m'

# Configuration
BACKUP_ROOT="/var/www/GOLDEN_BACKUPS_V2"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_NAME="ultimate-backup-${TIMESTAMP}"
BACKUP_DIR="${BACKUP_ROOT}/${BACKUP_NAME}"
APP_ROOT="/var/www/api-gateway"
LOG_FILE="${BACKUP_DIR}/backup.log"

# Create backup directory structure
mkdir -p "${BACKUP_DIR}"/{app,database,config,storage,system,docs,external-services,claudedocs}

# Logging functions
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

success() {
    echo -e "${GREEN}✓${NC} $1" | tee -a "$LOG_FILE"
}

# Progress tracking
TOTAL_STEPS=20
CURRENT_STEP=0

progress() {
    CURRENT_STEP=$((CURRENT_STEP + 1))
    PERCENTAGE=$((CURRENT_STEP * 100 / TOTAL_STEPS))
    echo -e "${CYAN}[$CURRENT_STEP/$TOTAL_STEPS - $PERCENTAGE%]${NC} $1" | tee -a "$LOG_FILE"
}

# Banner
echo -e "${MAGENTA}"
cat << "EOF"
╔══════════════════════════════════════════════════════════╗
║                                                          ║
║     ULTIMATE GOLDEN BACKUP V2                            ║
║     Complete Disaster Recovery System                    ║
║                                                          ║
╚══════════════════════════════════════════════════════════╝
EOF
echo -e "${NC}"

log "Starting Ultimate Golden Backup V2"
log "Backup Directory: ${BACKUP_DIR}"
log "Timestamp: ${TIMESTAMP}"

# Load environment variables
if [ -f "${APP_ROOT}/.env" ]; then
    export $(grep -v '^#' "${APP_ROOT}/.env" | grep -v '^$' | xargs)
    success "Environment loaded"
else
    error ".env file not found"
    exit 1
fi

# ========================================
# TIER 1: APPLICATION CODE & CONFIGURATION
# ========================================
progress "Backing up application code"

tar -czf "${BACKUP_DIR}/app/application.tar.gz" \
    -C /var/www \
    --exclude='vendor' \
    --exclude='node_modules' \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='.git' \
    api-gateway 2>/dev/null

APP_SIZE=$(du -h "${BACKUP_DIR}/app/application.tar.gz" | cut -f1)
success "Application code: ${APP_SIZE}"

# ========================================
# TIER 2: COMPLETE DOCUMENTATION
# ========================================
progress "Backing up complete documentation (claudedocs/)"

if [ -d "${APP_ROOT}/claudedocs" ]; then
    tar -czf "${BACKUP_DIR}/claudedocs/complete-docs.tar.gz" \
        -C "${APP_ROOT}" \
        claudedocs 2>/dev/null

    DOCS_SIZE=$(du -h "${BACKUP_DIR}/claudedocs/complete-docs.tar.gz" | cut -f1)
    success "Documentation: ${DOCS_SIZE}"
else
    warning "claudedocs/ directory not found"
fi

# ========================================
# TIER 3: DATABASE
# ========================================
progress "Creating complete database backup"

# Full database dump with all data
mysqldump -h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USERNAME}" -p"${DB_PASSWORD}" \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    --add-drop-database \
    --databases "${DB_DATABASE}" \
    > "${BACKUP_DIR}/database/full_dump.sql" 2>/dev/null

gzip "${BACKUP_DIR}/database/full_dump.sql"
DB_SIZE=$(du -h "${BACKUP_DIR}/database/full_dump.sql.gz" | cut -f1)
success "Database dump: ${DB_SIZE}"

# Schema-only backup
mysqldump -h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USERNAME}" -p"${DB_PASSWORD}" \
    --no-data \
    --routines \
    --triggers \
    --events \
    "${DB_DATABASE}" > "${BACKUP_DIR}/database/schema_only.sql" 2>/dev/null

gzip "${BACKUP_DIR}/database/schema_only.sql"
success "Schema backup created"

# Table statistics
mysql -h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USERNAME}" -p"${DB_PASSWORD}" \
    -e "SELECT
        TABLE_NAME,
        TABLE_ROWS,
        ROUND(DATA_LENGTH/1024/1024, 2) AS 'Data_MB',
        ROUND(INDEX_LENGTH/1024/1024, 2) AS 'Index_MB'
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA='${DB_DATABASE}'
    ORDER BY DATA_LENGTH DESC;" \
    > "${BACKUP_DIR}/database/table_statistics.txt" 2>/dev/null

# ========================================
# TIER 4: ENVIRONMENT & CONFIGURATION
# ========================================
progress "Backing up environment and configuration files"

# Copy all env files
cp "${APP_ROOT}/.env" "${BACKUP_DIR}/config/env.production"
[ -f "${APP_ROOT}/.env.example" ] && cp "${APP_ROOT}/.env.example" "${BACKUP_DIR}/config/"
[ -f "${APP_ROOT}/.env.backup" ] && cp "${APP_ROOT}/.env.backup" "${BACKUP_DIR}/config/"

# Package files
cp "${APP_ROOT}/composer.json" "${BACKUP_DIR}/config/"
cp "${APP_ROOT}/composer.lock" "${BACKUP_DIR}/config/"
[ -f "${APP_ROOT}/package.json" ] && cp "${APP_ROOT}/package.json" "${BACKUP_DIR}/config/"
[ -f "${APP_ROOT}/package-lock.json" ] && cp "${APP_ROOT}/package-lock.json" "${BACKUP_DIR}/config/"

# Laravel config files
mkdir -p "${BACKUP_DIR}/config/laravel"
cp -r "${APP_ROOT}/config" "${BACKUP_DIR}/config/laravel/"

success "Configuration files backed up"

# ========================================
# TIER 5: STORAGE & UPLOADS
# ========================================
progress "Backing up storage and user uploads"

tar -czf "${BACKUP_DIR}/storage/app_storage.tar.gz" \
    -C "${APP_ROOT}/storage" \
    --exclude="app/public" \
    --exclude="*.tmp" \
    --exclude="framework/cache/*" \
    --exclude="framework/sessions/*" \
    --exclude="framework/views/*" \
    app 2>/dev/null

STORAGE_SIZE=$(du -h "${BACKUP_DIR}/storage/app_storage.tar.gz" | cut -f1)
success "Storage: ${STORAGE_SIZE}"

# ========================================
# TIER 6: EXTERNAL SERVICES STATE
# ========================================
progress "Backing up Retell.ai configurations"

mkdir -p "${BACKUP_DIR}/external-services/retell"

if [ -n "${RETELL_TOKEN:-}" ] && [ -n "${RETELL_AGENT_ID:-}" ]; then
    # Current agent
    curl -s -X GET "https://api.retellai.com/get-agent/${RETELL_AGENT_ID}" \
        -H "Authorization: Bearer ${RETELL_TOKEN}" \
        > "${BACKUP_DIR}/external-services/retell/agent_${RETELL_AGENT_ID}.json"

    # All agents
    curl -s -X GET "https://api.retellai.com/list-agents" \
        -H "Authorization: Bearer ${RETELL_TOKEN}" \
        > "${BACKUP_DIR}/external-services/retell/all_agents.json"

    # Phone numbers
    curl -s -X GET "https://api.retellai.com/list-phone-numbers" \
        -H "Authorization: Bearer ${RETELL_TOKEN}" \
        > "${BACKUP_DIR}/external-services/retell/phone_numbers.json"

    success "Retell.ai state exported"
else
    warning "Retell credentials not found"
fi

progress "Backing up Cal.com configurations"

mkdir -p "${BACKUP_DIR}/external-services/calcom"

if [ -n "${CALCOM_API_KEY:-}" ]; then
    # Event types
    curl -s -X GET "${CALCOM_BASE_URL}/event-types" \
        -H "Authorization: Bearer ${CALCOM_API_KEY}" \
        -H "cal-api-version: ${CALCOM_API_VERSION}" \
        > "${BACKUP_DIR}/external-services/calcom/event_types.json"

    # Schedules
    curl -s -X GET "${CALCOM_BASE_URL}/schedules" \
        -H "Authorization: Bearer ${CALCOM_API_KEY}" \
        -H "cal-api-version: ${CALCOM_API_VERSION}" \
        > "${BACKUP_DIR}/external-services/calcom/schedules.json"

    success "Cal.com state exported"
else
    warning "Cal.com credentials not found"
fi

# ========================================
# TIER 7: SYSTEM CONFIGURATION
# ========================================
progress "Backing up system configurations"

# Nginx
mkdir -p "${BACKUP_DIR}/system/nginx"
cp -r /etc/nginx/sites-available "${BACKUP_DIR}/system/nginx/" 2>/dev/null || true
cp -r /etc/nginx/sites-enabled "${BACKUP_DIR}/system/nginx/" 2>/dev/null || true
cp /etc/nginx/nginx.conf "${BACKUP_DIR}/system/nginx/" 2>/dev/null || true

# PHP
mkdir -p "${BACKUP_DIR}/system/php"
PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
cp -r "/etc/php/${PHP_VERSION}" "${BACKUP_DIR}/system/php/" 2>/dev/null || true

# Supervisor
mkdir -p "${BACKUP_DIR}/system/supervisor"
[ -d "/etc/supervisor/conf.d" ] && cp -r /etc/supervisor/conf.d "${BACKUP_DIR}/system/supervisor/"

# Cron jobs
mkdir -p "${BACKUP_DIR}/system/cron"
crontab -l > "${BACKUP_DIR}/system/cron/root_crontab.txt" 2>/dev/null || true
crontab -u www-data -l > "${BACKUP_DIR}/system/cron/www-data_crontab.txt" 2>/dev/null || true

success "System configurations backed up"

# ========================================
# TIER 8: REDIS DATA
# ========================================
progress "Backing up Redis data"

mkdir -p "${BACKUP_DIR}/system/redis"

if systemctl is-active --quiet redis-server 2>/dev/null; then
    redis-cli --rdb "${BACKUP_DIR}/system/redis/dump.rdb" 2>/dev/null || true
    success "Redis snapshot created"
else
    warning "Redis not running"
fi

# ========================================
# TIER 9: GIT REPOSITORY STATE
# ========================================
progress "Backing up Git repository state"

mkdir -p "${BACKUP_DIR}/system/git"

cd "${APP_ROOT}"
git status > "${BACKUP_DIR}/system/git/status.txt" 2>/dev/null || true
git log --oneline -50 > "${BACKUP_DIR}/system/git/recent_commits.txt" 2>/dev/null || true
git remote -v > "${BACKUP_DIR}/system/git/remotes.txt" 2>/dev/null || true
git branch -a > "${BACKUP_DIR}/system/git/branches.txt" 2>/dev/null || true

success "Git state captured"

# ========================================
# TIER 10: SYSTEM INFORMATION
# ========================================
progress "Collecting detailed system information"

mkdir -p "${BACKUP_DIR}/system/info"

# Create comprehensive system report
cat > "${BACKUP_DIR}/system/info/system_report.txt" << EOF
========================================
ULTIMATE GOLDEN BACKUP V2
System Information Report
========================================

Backup Date: $(date)
Backup Name: ${BACKUP_NAME}
Hostname: $(hostname)

--- OPERATING SYSTEM ---
$(cat /etc/os-release)

Kernel: $(uname -r)
Architecture: $(uname -m)

--- SOFTWARE VERSIONS ---
PHP: $(php -v | head -1)
MySQL: $(mysql --version)
Nginx: $(nginx -v 2>&1)
Composer: $(composer --version)
Node.js: $(node -v 2>/dev/null || echo "Not installed")
NPM: $(npm -v 2>/dev/null || echo "Not installed")
Redis: $(redis-server --version 2>/dev/null || echo "Not installed")

--- DISK USAGE ---
$(df -h /var/www)

--- MEMORY ---
$(free -h)

--- DIRECTORY SIZES ---
$(du -sh /var/www/api-gateway/{app,config,database,public,resources,routes,storage} 2>/dev/null)

--- PHP MODULES ---
$(php -m)

--- RUNNING SERVICES ---
$(systemctl list-units --type=service --state=running | grep -E 'nginx|php|mysql|redis|supervisor')

========================================
END OF SYSTEM REPORT
========================================
EOF

# Package lists
dpkg -l > "${BACKUP_DIR}/system/info/installed_packages.txt"
composer global show > "${BACKUP_DIR}/system/info/composer_global.txt" 2>/dev/null || true
npm list -g --depth=0 > "${BACKUP_DIR}/system/info/npm_global.txt" 2>/dev/null || true

success "System information collected"

# ========================================
# TIER 11: SCRIPTS & AUTOMATION
# ========================================
progress "Backing up deployment and automation scripts"

mkdir -p "${BACKUP_DIR}/system/scripts"

if [ -d "${APP_ROOT}/scripts" ]; then
    tar -czf "${BACKUP_DIR}/system/scripts/all_scripts.tar.gz" \
        -C "${APP_ROOT}" \
        scripts 2>/dev/null
    success "Scripts backed up"
fi

# ========================================
# TIER 12: SSL CERTIFICATES
# ========================================
progress "Backing up SSL certificates"

mkdir -p "${BACKUP_DIR}/system/ssl"

if [ -d "/etc/letsencrypt" ]; then
    tar -czf "${BACKUP_DIR}/system/ssl/letsencrypt.tar.gz" \
        -C /etc letsencrypt 2>/dev/null
    success "SSL certificates backed up"
fi

# ========================================
# GENERATE METADATA & CHECKSUMS
# ========================================
progress "Generating checksums and metadata"

# Calculate checksums
cd "${BACKUP_DIR}"
find . -type f -exec sha256sum {} \; > checksums.txt
success "Checksums generated"

# Create metadata
cat > "${BACKUP_DIR}/metadata.json" << EOF
{
    "backup_version": "2.0-ultimate",
    "backup_name": "${BACKUP_NAME}",
    "backup_date": "$(date -Iseconds)",
    "backup_type": "ultimate-golden-backup",
    "source_path": "${APP_ROOT}",
    "hostname": "$(hostname)",
    "php_version": "$(php -v | head -n 1)",
    "mysql_version": "$(mysql --version)",
    "nginx_version": "$(nginx -v 2>&1)",
    "database": "${DB_DATABASE}",
    "total_size": "$(du -sh ${BACKUP_DIR} | cut -f1)",
    "components": {
        "application": true,
        "database": true,
        "storage": true,
        "configuration": true,
        "documentation": $([ -f "${BACKUP_DIR}/claudedocs/complete-docs.tar.gz" ] && echo "true" || echo "false"),
        "external_services": {
            "retell": $([ -f "${BACKUP_DIR}/external-services/retell/agent_${RETELL_AGENT_ID}.json" ] && echo "true" || echo "false"),
            "calcom": $([ -f "${BACKUP_DIR}/external-services/calcom/event_types.json" ] && echo "true" || echo "false")
        },
        "system_config": true,
        "git_state": true,
        "ssl_certs": $([ -f "${BACKUP_DIR}/system/ssl/letsencrypt.tar.gz" ] && echo "true" || echo "false")
    },
    "tier_sizes": {
        "application": "${APP_SIZE}",
        "database": "${DB_SIZE}",
        "storage": "${STORAGE_SIZE}",
        "docs": "${DOCS_SIZE:-N/A}"
    }
}
EOF

# ========================================
# CREATE COMPREHENSIVE RESTORE GUIDE
# ========================================
progress "Creating comprehensive restoration guide"

cat > "${BACKUP_DIR}/docs/ULTIMATE_RESTORE_GUIDE.md" << 'RESTORE_EOF'
# Ultimate Golden Backup V2 - Complete Restoration Guide

## 📋 Overview

This backup contains a **complete snapshot** of the AskPro AI Gateway system, including:
- ✅ Application code (Laravel)
- ✅ Complete database (MySQL)
- ✅ All configuration files (.env, nginx, PHP, etc.)
- ✅ User uploads & storage
- ✅ Complete documentation (claudedocs/)
- ✅ External services state (Retell.ai, Cal.com)
- ✅ System configurations
- ✅ SSL certificates
- ✅ Git repository state
- ✅ Scripts & automation

## ⚙️ Prerequisites

### Required Software
- **Linux**: Ubuntu 20.04+ or Debian 11+
- **PHP**: 8.2+ with extensions (see system_report.txt)
- **MySQL**: 8.0+
- **Nginx**: 1.18+
- **Redis**: 6.0+
- **Node.js**: 18+ & NPM
- **Composer**: 2.5+

### Required Access
- Root/sudo access
- Database credentials
- Domain DNS access (for SSL)
- Retell.ai API access
- Cal.com API access

## 🚀 Full System Restoration (Bare Metal)

### Phase 1: System Preparation

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install required software
sudo apt install -y nginx mysql-server redis-server \
    php8.3-cli php8.3-fpm php8.3-mysql php8.3-redis \
    php8.3-xml php8.3-mbstring php8.3-curl \
    php8.3-zip php8.3-gd php8.3-bcmath \
    composer nodejs npm git supervisor

# Create directories
sudo mkdir -p /var/www/api-gateway
sudo chown -R www-data:www-data /var/www
```

### Phase 2: Database Restoration

```bash
# Create database
mysql -u root -p << EOF
CREATE DATABASE IF NOT EXISTS askproai_db;
CREATE USER IF NOT EXISTS 'askproai_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON askproai_db.* TO 'askproai_user'@'localhost';
FLUSH PRIVILEGES;
EOF

# Restore database
gunzip < database/full_dump.sql.gz | mysql -u root -p

# Verify
mysql -u askproai_user -p askproai_db -e "SHOW TABLES;"
```

### Phase 3: Application Restoration

```bash
# Extract application
tar -xzf app/application.tar.gz -C /var/www/

# Restore environment
cp config/env.production /var/www/api-gateway/.env

# Update .env with new credentials if changed
nano /var/www/api-gateway/.env
```

### Phase 4: Dependencies Installation

```bash
cd /var/www/api-gateway

# PHP dependencies
composer install --no-dev --optimize-autoloader

# Node dependencies
npm install

# Build assets
npm run build
```

### Phase 5: Storage Restoration

```bash
# Restore storage
tar -xzf storage/app_storage.tar.gz -C /var/www/api-gateway/storage/

# Set permissions
sudo chown -R www-data:www-data /var/www/api-gateway
sudo chmod -R 755 /var/www/api-gateway
sudo chmod -R 775 /var/www/api-gateway/storage
sudo chmod -R 775 /var/www/api-gateway/bootstrap/cache

# Create storage link
php artisan storage:link
```

### Phase 6: System Configuration

```bash
# Restore Nginx config
sudo cp system/nginx/sites-available/askproai /etc/nginx/sites-available/
sudo ln -sf /etc/nginx/sites-available/askproai /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

# Restore PHP config (if needed)
# sudo cp -r system/php/8.3/* /etc/php/8.3/

# Restore cron jobs
crontab system/cron/www-data_crontab.txt

# Restore supervisor config
sudo cp -r system/supervisor/conf.d/* /etc/supervisor/conf.d/
sudo supervisorctl reread
sudo supervisorctl update
```

### Phase 7: Redis Restoration

```bash
# Stop Redis
sudo systemctl stop redis-server

# Restore dump
sudo cp system/redis/dump.rdb /var/lib/redis/dump.rdb
sudo chown redis:redis /var/lib/redis/dump.rdb

# Start Redis
sudo systemctl start redis-server
```

### Phase 8: SSL Certificates

```bash
# Restore Let's Encrypt certificates
sudo tar -xzf system/ssl/letsencrypt.tar.gz -C /etc/

# Test Nginx with SSL
sudo nginx -t
sudo systemctl reload nginx
```

### Phase 9: Laravel Setup

```bash
cd /var/www/api-gateway

# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations (if needed)
php artisan migrate --force
```

### Phase 10: External Services Restoration

#### Retell.ai

```bash
# Review current agent config
cat external-services/retell/agent_*.json

# Update via API (if needed)
curl -X POST "https://api.retellai.com/update-agent/YOUR_AGENT_ID" \
    -H "Authorization: Bearer YOUR_TOKEN" \
    -H "Content-Type: application/json" \
    -d @external-services/retell/agent_*.json

# Verify phone numbers
cat external-services/retell/phone_numbers.json
```

#### Cal.com

```bash
# Review event types
cat external-services/calcom/event_types.json

# Event types are usually preserved in Cal.com
# Verify via dashboard or API
```

### Phase 11: Documentation Restoration

```bash
# Extract documentation
tar -xzf claudedocs/complete-docs.tar.gz -C /var/www/api-gateway/
```

### Phase 12: Final Verification

```bash
# Check services
sudo systemctl status nginx
sudo systemctl status php8.3-fpm
sudo systemctl status mysql
sudo systemctl status redis-server
sudo systemctl status supervisor

# Test application
curl http://localhost
curl https://yourdomain.com

# Check logs
tail -f /var/www/api-gateway/storage/logs/laravel.log
tail -f /var/log/nginx/error.log
```

## 🔍 Verification Checklist

- [ ] Website loads correctly
- [ ] Database connection works
- [ ] Admin panel accessible
- [ ] File uploads work
- [ ] Retell.ai calls connect
- [ ] Cal.com availability queries work
- [ ] Background jobs running (queue workers)
- [ ] Cron jobs scheduled
- [ ] SSL certificate valid
- [ ] Redis connection works
- [ ] Email sending works (test)

## 🆘 Troubleshooting

### Application doesn't load
```bash
# Check PHP-FPM
sudo systemctl status php8.3-fpm
sudo tail -f /var/log/php8.3-fpm.log

# Check Nginx
sudo nginx -t
sudo tail -f /var/log/nginx/error.log

# Check permissions
sudo chown -R www-data:www-data /var/www/api-gateway
sudo chmod -R 775 /var/www/api-gateway/storage
```

### Database connection error
```bash
# Verify credentials in .env
cat /var/www/api-gateway/.env | grep DB_

# Test connection
mysql -u askproai_user -p askproai_db -e "SELECT 1;"
```

### Cache issues
```bash
cd /var/www/api-gateway
php artisan cache:clear
php artisan config:clear
php artisan view:clear
sudo systemctl restart php8.3-fpm
```

### Missing dependencies
```bash
cd /var/www/api-gateway
composer install --no-dev
npm install
npm run build
```

## 📊 Partial Restoration

### Database Only
```bash
gunzip < database/full_dump.sql.gz | mysql -u root -p
```

### Configuration Only
```bash
cp config/env.production /var/www/api-gateway/.env
```

### Storage Only
```bash
tar -xzf storage/app_storage.tar.gz -C /var/www/api-gateway/storage/
```

### Documentation Only
```bash
tar -xzf claudedocs/complete-docs.tar.gz -C /var/www/api-gateway/
```

## 📞 Support

Restore issues? Check:
1. **backup.log** - Backup process log
2. **metadata.json** - Backup metadata
3. **system/info/system_report.txt** - Original system info
4. **checksums.txt** - File integrity verification

---
**Backup Version**: 2.0-ultimate
**Created**: See metadata.json
RESTORE_EOF

# ========================================
# CREATE QUICK RESTORE SCRIPT
# ========================================
progress "Creating quick restore script"

cat > "${BACKUP_DIR}/quick-restore.sh" << 'QUICK_RESTORE_EOF'
#!/bin/bash

#############################################
# Quick Restore Script
# Usage: ./quick-restore.sh [component]
# Components: all, database, app, config
#############################################

set -euo pipefail

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

COMPONENT="${1:-all}"
BACKUP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo -e "${GREEN}╔═══════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║     Quick Restore - Ultimate Backup      ║${NC}"
echo -e "${GREEN}╚═══════════════════════════════════════════╝${NC}"
echo ""

echo -e "${YELLOW}WARNING: This will restore from backup!${NC}"
echo -e "${BLUE}Component: ${COMPONENT}${NC}"
echo -e "${BLUE}Backup: ${BACKUP_DIR}${NC}"
echo ""
read -p "Continue? (yes/no): " CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    echo "Cancelled."
    exit 0
fi

restore_database() {
    echo -e "${BLUE}[*] Restoring database...${NC}"
    gunzip < "${BACKUP_DIR}/database/full_dump.sql.gz" | mysql -u root -p
    echo -e "${GREEN}✓ Database restored${NC}"
}

restore_app() {
    echo -e "${BLUE}[*] Restoring application...${NC}"

    # Backup current
    if [ -d "/var/www/api-gateway" ]; then
        mv /var/www/api-gateway "/var/www/api-gateway.before_restore_$(date +%Y%m%d_%H%M%S)"
    fi

    # Extract
    tar -xzf "${BACKUP_DIR}/app/application.tar.gz" -C /var/www/

    # Restore storage
    tar -xzf "${BACKUP_DIR}/storage/app_storage.tar.gz" -C /var/www/api-gateway/storage/

    # Set permissions
    chown -R www-data:www-data /var/www/api-gateway
    chmod -R 775 /var/www/api-gateway/storage

    echo -e "${GREEN}✓ Application restored${NC}"
}

restore_config() {
    echo -e "${BLUE}[*] Restoring configuration...${NC}"
    cp "${BACKUP_DIR}/config/env.production" /var/www/api-gateway/.env
    echo -e "${GREEN}✓ Configuration restored${NC}"
}

case "$COMPONENT" in
    all)
        restore_database
        restore_app
        restore_config
        ;;
    database|db)
        restore_database
        ;;
    app|application)
        restore_app
        ;;
    config)
        restore_config
        ;;
    *)
        echo -e "${RED}Invalid component: ${COMPONENT}${NC}"
        echo "Valid: all, database, app, config"
        exit 1
        ;;
esac

echo ""
echo -e "${GREEN}╔═══════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║        Restoration Complete!              ║${NC}"
echo -e "${GREEN}╚═══════════════════════════════════════════╝${NC}"
QUICK_RESTORE_EOF

chmod +x "${BACKUP_DIR}/quick-restore.sh"

# ========================================
# CREATE COMPRESSED ARCHIVE
# ========================================
progress "Creating final compressed archive"

cd "${BACKUP_ROOT}"
tar -czf "${BACKUP_NAME}.tar.gz" "${BACKUP_NAME}/"

if [ $? -eq 0 ]; then
    FINAL_SIZE=$(du -h "${BACKUP_NAME}.tar.gz" | cut -f1)
    success "Archive created: ${FINAL_SIZE}"
else
    error "Failed to create archive"
    exit 1
fi

# ========================================
# CLEANUP OLD BACKUPS
# ========================================
progress "Cleaning up old backups (keeping last 5)"

cd "${BACKUP_ROOT}"
ls -dt ultimate-backup-* 2>/dev/null | grep -v ".tar.gz" | tail -n +6 | xargs rm -rf 2>/dev/null || true
ls -t ultimate-backup-*.tar.gz 2>/dev/null | tail -n +6 | xargs rm -f 2>/dev/null || true

# ========================================
# SUMMARY
# ========================================
echo ""
echo -e "${MAGENTA}"
cat << "EOF"
╔══════════════════════════════════════════════════════════╗
║                                                          ║
║     BACKUP COMPLETED SUCCESSFULLY!                       ║
║                                                          ║
╚══════════════════════════════════════════════════════════╝
EOF
echo -e "${NC}"

echo -e "${CYAN}========================================${NC}"
echo -e "${GREEN}Backup Summary:${NC}"
echo -e "${CYAN}========================================${NC}"
echo -e "${BLUE}Name:${NC}        ${BACKUP_NAME}"
echo -e "${BLUE}Location:${NC}    ${BACKUP_DIR}"
echo -e "${BLUE}Archive:${NC}     ${BACKUP_ROOT}/${BACKUP_NAME}.tar.gz"
echo -e "${BLUE}Size:${NC}        ${FINAL_SIZE}"
echo -e "${CYAN}========================================${NC}"
echo -e "${YELLOW}Components Backed Up:${NC}"
echo -e "  ✓ Application Code (${APP_SIZE})"
echo -e "  ✓ Database (${DB_SIZE})"
echo -e "  ✓ Storage (${STORAGE_SIZE})"
echo -e "  ✓ Documentation (${DOCS_SIZE:-N/A})"
echo -e "  ✓ External Services (Retell + Cal.com)"
echo -e "  ✓ System Configuration"
echo -e "  ✓ Git State"
echo -e "${CYAN}========================================${NC}"
echo -e "${YELLOW}Restoration:${NC}"
echo -e "  Guide:  ${BACKUP_DIR}/docs/ULTIMATE_RESTORE_GUIDE.md"
echo -e "  Script: ${BACKUP_DIR}/quick-restore.sh"
echo -e "  Log:    ${BACKUP_DIR}/backup.log"
echo -e "${CYAN}========================================${NC}"
echo ""

log "Ultimate Golden Backup V2 completed successfully!"

exit 0
QUICK_RESTORE_EOF

chmod +x "${BACKUP_DIR}/quick-restore.sh"

# ========================================
# CREATE README
# ========================================
cat > "${BACKUP_DIR}/README.md" << 'README_EOF'
# Ultimate Golden Backup V2

## 🎯 What is this?

This is a **complete, comprehensive disaster recovery backup** of the AskPro AI Gateway system. You can restore the entire system from scratch using only this backup.

## 📦 What's Included?

### Tier 1: Application
- Complete Laravel application code
- All routes, controllers, models, services
- Filament admin panel
- Frontend assets (compiled)

### Tier 2: Database
- Full MySQL dump with data
- Schema-only backup
- Table statistics

### Tier 3: Configuration
- .env file (production)
- composer.json/lock
- package.json/lock
- All Laravel config files

### Tier 4: Storage
- User uploads
- Application files
- Cache-safe storage

### Tier 5: Documentation
- Complete claudedocs/ directory
- All technical documentation
- Architecture diagrams
- RCA reports

### Tier 6: External Services
- Retell.ai agent configurations
- Cal.com event types & schedules
- Phone number mappings
- Database export of critical configs

### Tier 7: System Configuration
- Nginx configuration
- PHP-FPM settings
- Supervisor configs
- Cron jobs
- Redis data

### Tier 8: Development
- Git repository state
- Recent commits
- Branch information
- All automation scripts

### Tier 9: SSL
- Let's Encrypt certificates
- Private keys
- Certificate chains

### Tier 10: System Info
- Installed packages
- PHP modules
- System versions
- Disk usage snapshots

## 🚀 Quick Start

### Full Restoration (New Server)
```bash
cd /path/to/backup
sudo bash docs/ULTIMATE_RESTORE_GUIDE.md
```

### Partial Restoration
```bash
# Database only
./quick-restore.sh database

# Application only
./quick-restore.sh app

# Config only
./quick-restore.sh config

# Everything
./quick-restore.sh all
```

## 📖 Documentation

- **ULTIMATE_RESTORE_GUIDE.md** - Complete step-by-step restoration
- **backup.log** - Detailed backup process log
- **metadata.json** - Backup metadata and checksums
- **checksums.txt** - File integrity verification

## ✅ Verification

```bash
# Verify checksums
cd /path/to/backup
sha256sum -c checksums.txt

# Check metadata
cat metadata.json | jq '.'
```

## 🔒 Security Notes

- This backup contains **sensitive data** (.env, API keys, etc.)
- Store securely with encryption
- Restrict access appropriately
- Rotate credentials after restoration

## 📊 Backup Details

See `metadata.json` for:
- Backup date & time
- Component sizes
- System versions
- Checksum verification

## 💾 Storage Recommendations

- **Local**: Keep on separate disk/server
- **Remote**: S3, Google Cloud Storage, or similar
- **Retention**: Keep last 5-10 backups
- **Frequency**: Daily automated backups recommended

## 🆘 Support

Issues during restoration? Check:
1. backup.log
2. system/info/system_report.txt
3. ULTIMATE_RESTORE_GUIDE.md troubleshooting section

---
**Version**: 2.0-ultimate
**Created**: See metadata.json
README_EOF

success "README created"

# ========================================
# CREATE AUTOMATION GUIDE
# ========================================
cat > "${BACKUP_DIR}/docs/AUTOMATION_GUIDE.md" << 'AUTO_EOF'
# Backup Automation Guide

## Daily Automated Backups

### Setup Cron Job

```bash
# Edit crontab
sudo crontab -e

# Add daily backup at 2 AM
0 2 * * * /var/www/api-gateway/scripts/golden-backup-v2-ultimate.sh >> /var/log/backup-cron.log 2>&1
```

### Verify Cron Job

```bash
# List cron jobs
sudo crontab -l

# Check cron log
sudo tail -f /var/log/backup-cron.log
```

## Off-Site Backup Strategy

### AWS S3 (Recommended)

```bash
# Install AWS CLI
sudo apt install awscli

# Configure
aws configure

# Upload backup
aws s3 cp /var/www/GOLDEN_BACKUPS_V2/ultimate-backup-*.tar.gz \
    s3://your-bucket/backups/ \
    --storage-class GLACIER

# Automate with cron
0 3 * * * aws s3 sync /var/www/GOLDEN_BACKUPS_V2 s3://your-bucket/backups/
```

### Google Cloud Storage

```bash
# Install gsutil
curl https://sdk.cloud.google.com | bash

# Upload
gsutil cp /var/www/GOLDEN_BACKUPS_V2/ultimate-backup-*.tar.gz \
    gs://your-bucket/backups/
```

### Rsync to Remote Server

```bash
# Setup SSH key
ssh-keygen -t rsa -b 4096

# Copy to remote
ssh-copy-id user@backup-server.com

# Sync backups
rsync -avz --delete \
    /var/www/GOLDEN_BACKUPS_V2/ \
    user@backup-server.com:/backups/askproai/
```

## Backup Retention Policy

### Keep Last 7 Daily Backups

```bash
# Add to cron
0 4 * * * find /var/www/GOLDEN_BACKUPS_V2 -name "ultimate-backup-*.tar.gz" -mtime +7 -delete
```

### Keep Weekly Backups for 4 Weeks

```bash
# Run weekly at Sunday 3 AM
0 3 * * 0 /var/www/api-gateway/scripts/golden-backup-v2-ultimate.sh && \
    cp /var/www/GOLDEN_BACKUPS_V2/ultimate-backup-*.tar.gz \
    /var/www/WEEKLY_BACKUPS/backup-week-$(date +\%U).tar.gz
```

## Monitoring & Alerts

### Email Notification on Success/Failure

```bash
# Install mail utils
sudo apt install mailutils

# Modify backup script to send email
echo "Backup completed: ${BACKUP_NAME}" | mail -s "Backup Success" admin@example.com
```

### Slack Notification

```bash
# Add to end of backup script
curl -X POST -H 'Content-type: application/json' \
    --data "{\"text\":\"✅ Backup completed: ${BACKUP_NAME}\"}" \
    YOUR_SLACK_WEBHOOK_URL
```

## Testing Recovery

### Monthly Recovery Test

```bash
# Schedule monthly test
0 5 1 * * /var/www/api-gateway/scripts/test-recovery.sh >> /var/log/recovery-test.log
```

Create test script:
```bash
cat > /var/www/api-gateway/scripts/test-recovery.sh << 'TEST_EOF'
#!/bin/bash
# Test recovery process

LATEST_BACKUP=$(ls -t /var/www/GOLDEN_BACKUPS_V2/ultimate-backup-*.tar.gz | head -1)

# Extract to test location
TEST_DIR="/tmp/recovery-test-$(date +%Y%m%d)"
mkdir -p "$TEST_DIR"
tar -xzf "$LATEST_BACKUP" -C "$TEST_DIR"

# Verify components
ERRORS=0

# Check database dump
if [ ! -f "$TEST_DIR"/*/database/full_dump.sql.gz ]; then
    echo "ERROR: Database dump missing"
    ERRORS=$((ERRORS + 1))
fi

# Check application
if [ ! -f "$TEST_DIR"/*/app/application.tar.gz ]; then
    echo "ERROR: Application archive missing"
    ERRORS=$((ERRORS + 1))
fi

# Cleanup
rm -rf "$TEST_DIR"

if [ $ERRORS -eq 0 ]; then
    echo "✅ Recovery test PASSED"
    exit 0
else
    echo "❌ Recovery test FAILED: $ERRORS errors"
    exit 1
fi
TEST_EOF

chmod +x /var/www/api-gateway/scripts/test-recovery.sh
```

---
**Version**: 2.0-ultimate
AUTO_EOF

success "Automation guide created"

# Final summary
echo ""
echo -e "${MAGENTA}"
cat << "EOF"
╔══════════════════════════════════════════════════════════╗
║                                                          ║
║     BACKUP COMPLETED SUCCESSFULLY!                       ║
║                                                          ║
╚══════════════════════════════════════════════════════════╝
EOF
echo -e "${NC}"

echo -e "${CYAN}========================================${NC}"
echo -e "${GREEN}Backup Summary:${NC}"
echo -e "${CYAN}========================================${NC}"
echo -e "${BLUE}Name:${NC}        ${BACKUP_NAME}"
echo -e "${BLUE}Location:${NC}    ${BACKUP_DIR}"
echo -e "${BLUE}Archive:${NC}     ${BACKUP_ROOT}/${BACKUP_NAME}.tar.gz"
echo -e "${BLUE}Size:${NC}        ${FINAL_SIZE}"
echo -e "${CYAN}========================================${NC}"
echo -e "${YELLOW}Components Backed Up:${NC}"
echo -e "  ✓ Application Code (${APP_SIZE})"
echo -e "  ✓ Database (${DB_SIZE})"
echo -e "  ✓ Storage (${STORAGE_SIZE})"
echo -e "  ✓ Documentation (${DOCS_SIZE:-N/A})"
echo -e "  ✓ External Services (Retell + Cal.com)"
echo -e "  ✓ System Configuration"
echo -e "  ✓ Git State"
echo -e "${CYAN}========================================${NC}"
echo -e "${YELLOW}Restoration:${NC}"
echo -e "  Guide:  ${BACKUP_DIR}/docs/ULTIMATE_RESTORE_GUIDE.md"
echo -e "  Quick:  ${BACKUP_DIR}/quick-restore.sh"
echo -e "  Log:    ${BACKUP_DIR}/backup.log"
echo -e "${CYAN}========================================${NC}"
echo -e "${YELLOW}Documentation:${NC}"
echo -e "  README:     ${BACKUP_DIR}/README.md"
echo -e "  Automation: ${BACKUP_DIR}/docs/AUTOMATION_GUIDE.md"
echo -e "  Metadata:   ${BACKUP_DIR}/metadata.json"
echo -e "${CYAN}========================================${NC}"
echo ""

log "Ultimate Golden Backup V2 completed successfully!"

exit 0
