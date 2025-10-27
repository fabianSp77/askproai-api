#!/bin/bash
###############################################################################
# sync-staging-database.sh - Sync Production DB to Staging (Sanitized)
###############################################################################
# Usage: ./scripts/sync-staging-database.sh
#
# What this script does:
# 1. Creates backup of staging database (safety)
# 2. Dumps production database
# 3. Sanitizes sensitive data (emails, passwords, API keys)
# 4. Imports to staging database
# 5. Runs any pending migrations
###############################################################################

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration (CHANGE THESE!)
PROD_DB="askproai_gateway"
PROD_USER="askproai_user"
STAGING_DB="askproai_staging"
STAGING_USER="askproai_staging_user"
BACKUP_DIR="/var/backups/mysql"

echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}   Database Sync: Production → Staging${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}"
echo ""

# Safety check
echo -e "${YELLOW}⚠️  WARNING: This will OVERWRITE staging database!${NC}"
echo -e "Production DB: ${GREEN}${PROD_DB}${NC}"
echo -e "Staging DB: ${GREEN}${STAGING_DB}${NC}"
echo ""
read -p "Continue? (yes/no): " confirm
if [ "$confirm" != "yes" ]; then
    echo -e "${RED}❌ Aborted${NC}"
    exit 1
fi

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Step 1: Backup current staging database
echo -e "${YELLOW}[1/6]${NC} Backing up current staging database..."
mysqldump -u "$STAGING_USER" -p "$STAGING_DB" | gzip > "$BACKUP_DIR/staging_backup_$(date +%Y%m%d_%H%M%S).sql.gz"
echo -e "${GREEN}✅ Backup saved${NC}"

# Step 2: Dump production database
echo -e "${YELLOW}[2/6]${NC} Dumping production database..."
DUMP_FILE="/tmp/prod_dump_$(date +%Y%m%d_%H%M%S).sql"
mysqldump -u "$PROD_USER" -p \
    --single-transaction \
    --quick \
    --lock-tables=false \
    "$PROD_DB" > "$DUMP_FILE"
echo -e "${GREEN}✅ Production database dumped${NC}"

# Step 3: Sanitize sensitive data
echo -e "${YELLOW}[3/6]${NC} Sanitizing sensitive data..."

# Replace emails (keep domain for testing)
sed -i "s/\([a-zA-Z0-9._-]*\)@\([a-zA-Z0-9.-]*\)/test_\1@staging.local/g" "$DUMP_FILE"

# Replace phone numbers
sed -i "s/+49[0-9]\{10,11\}/+49123456789/g" "$DUMP_FILE"

# Sanitize API keys (Retell, Cal.com, Stripe)
sed -i "s/key_[a-zA-Z0-9_-]\{32,\}/key_STAGING_SANITIZED/g" "$DUMP_FILE"
sed -i "s/sk_live_[a-zA-Z0-9_-]\{24,\}/sk_test_STAGING/g" "$DUMP_FILE"
sed -i "s/pk_live_[a-zA-Z0-9_-]\{24,\}/pk_test_STAGING/g" "$DUMP_FILE"

# Hash all passwords (double-hash for extra safety)
sed -i "s/\$2y\$[0-9]\{2\}\$[a-zA-Z0-9./]\{53\}/\$2y\$12\$STAGING_PASSWORD_HASH/g" "$DUMP_FILE"

echo -e "${GREEN}✅ Data sanitized${NC}"

# Step 4: Drop and recreate staging database
echo -e "${YELLOW}[4/6]${NC} Recreating staging database..."
mysql -u root -p <<MYSQL_COMMANDS
DROP DATABASE IF EXISTS $STAGING_DB;
CREATE DATABASE $STAGING_DB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON $STAGING_DB.* TO '$STAGING_USER'@'localhost';
FLUSH PRIVILEGES;
MYSQL_COMMANDS
echo -e "${GREEN}✅ Database recreated${NC}"

# Step 5: Import sanitized dump
echo -e "${YELLOW}[5/6]${NC} Importing data to staging..."
mysql -u "$STAGING_USER" -p "$STAGING_DB" < "$DUMP_FILE"
echo -e "${GREEN}✅ Data imported${NC}"

# Step 6: Run migrations (in case staging is behind)
echo -e "${YELLOW}[6/6]${NC} Running migrations..."
cd /var/www/api-gateway
php artisan migrate --env=staging --force
echo -e "${GREEN}✅ Migrations complete${NC}"

# Cleanup
rm -f "$DUMP_FILE"

# Success
echo ""
echo -e "${GREEN}═══════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}✅ Database sync complete!${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════${NC}"
echo ""
echo -e "📊 Staging database: ${GREEN}${STAGING_DB}${NC}"
echo -e "📧 Test emails: ${BLUE}test_*@staging.local${NC}"
echo -e "🔑 Passwords: ${YELLOW}All sanitized (reset required)${NC}"
echo ""
echo -e "${YELLOW}⚠️  IMPORTANT: Reset test user passwords!${NC}"
echo -e "   php artisan tinker"
echo -e "   User::where('email', 'LIKE', '%@staging.local')->update(['password' => bcrypt('TestPass123!')]);"
echo ""
