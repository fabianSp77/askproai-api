#!/bin/bash
#
# EMERGENCY DATA RECOVERY SCRIPT
# Incident: 2025-10-27 Database Wipe
# Recovery: Restore from 2025-10-04 Backup
#
# READ FULL RCA: /var/www/api-gateway/RCA_ADMIN_PANEL_DATA_LOSS_2025-10-27.md
#

set -e  # Exit on error
set -u  # Exit on undefined variable

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
APP_DIR="/var/www/api-gateway"
BACKUP_DIR="/var/www/backups"
BACKUP_FILE="$BACKUP_DIR/P4_pre_next_steps_20251004_112339/askproai_db_backup.sql"
DB_NAME="askproai_db"
DB_USER="askproai_user"
DB_PASS="askproai_secure_pass_2024"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

echo -e "${YELLOW}"
echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║                  EMERGENCY DATABASE RECOVERY                   ║"
echo "║                                                                ║"
echo "║  WARNING: This will restore data from 2025-10-04              ║"
echo "║  Data Loss Window: October 4-27 (23 days)                     ║"
echo "║                                                                ║"
echo "║  - New appointments in this period: LOST                      ║"
echo "║  - New customers in this period: LOST                         ║"
echo "║  - Configuration changes in this period: LOST                 ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

# Verify backup exists
if [ ! -f "$BACKUP_FILE" ]; then
    echo -e "${RED}❌ ERROR: Backup file not found at:${NC}"
    echo "   $BACKUP_FILE"
    exit 1
fi

echo -e "${GREEN}✅ Backup file found:${NC} $(ls -lh $BACKUP_FILE | awk '{print $5}')"
echo ""

# Confirmation
read -p "Have you read the full RCA report? (yes/no): " confirm_read
if [ "$confirm_read" != "yes" ]; then
    echo -e "${RED}Please read: /var/www/api-gateway/RCA_ADMIN_PANEL_DATA_LOSS_2025-10-27.md${NC}"
    exit 1
fi

read -p "Do you understand you will lose 23 days of data? (yes/no): " confirm_loss
if [ "$confirm_loss" != "yes" ]; then
    echo "Recovery aborted by user"
    exit 1
fi

read -p "Proceed with database restore? (yes/no): " confirm_restore
if [ "$confirm_restore" != "yes" ]; then
    echo "Recovery aborted by user"
    exit 1
fi

echo ""
echo -e "${YELLOW}Starting recovery process...${NC}"
echo ""

# Step 1: Put application in maintenance mode
echo "📝 Step 1/8: Putting application in maintenance mode..."
cd "$APP_DIR"
php artisan down --render="errors::503" --secret="recovery-token-${TIMESTAMP}" || true
echo -e "${GREEN}✅ Application is now in maintenance mode${NC}"
echo ""

# Step 2: Backup current empty state (for reference)
echo "📝 Step 2/8: Backing up current (empty) state..."
EMPTY_BACKUP="$BACKUP_DIR/empty_state_${TIMESTAMP}.sql"
mysqldump -u "$DB_USER" -p"$DB_PASS" \
    --single-transaction \
    --routines \
    --triggers \
    "$DB_NAME" > "$EMPTY_BACKUP"
echo -e "${GREEN}✅ Empty state backed up to: $EMPTY_BACKUP${NC}"
echo ""

# Step 3: Drop current database
echo "📝 Step 3/8: Dropping current database..."
mysql -u "$DB_USER" -p"$DB_PASS" -e "DROP DATABASE IF EXISTS $DB_NAME;"
echo -e "${GREEN}✅ Current database dropped${NC}"
echo ""

# Step 4: Recreate database
echo "📝 Step 4/8: Recreating database..."
mysql -u "$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
echo -e "${GREEN}✅ Database recreated${NC}"
echo ""

# Step 5: Restore from backup
echo "📝 Step 5/8: Restoring data from October 4 backup..."
echo "   This may take several minutes..."
mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$BACKUP_FILE"
echo -e "${GREEN}✅ Data restored from backup${NC}"
echo ""

# Step 6: Run pending migrations
echo "📝 Step 6/8: Running pending migrations..."
echo "   ⚠️  Checking migration status first..."
cd "$APP_DIR"
php artisan migrate:status
echo ""
read -p "Run pending migrations? (yes/no): " confirm_migrate
if [ "$confirm_migrate" = "yes" ]; then
    php artisan migrate --force
    echo -e "${GREEN}✅ Migrations executed${NC}"
else
    echo -e "${YELLOW}⚠️  Migrations skipped - you must run them manually${NC}"
fi
echo ""

# Step 7: Clear all caches
echo "📝 Step 7/8: Clearing all caches..."
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
echo -e "${GREEN}✅ Laravel caches cleared${NC}"

# Clear Redis
if command -v redis-cli &> /dev/null; then
    echo "Clearing Redis cache..."
    redis-cli FLUSHDB
    echo -e "${GREEN}✅ Redis cache cleared${NC}"
else
    echo -e "${YELLOW}⚠️  Redis not found - skipping Redis cache clear${NC}"
fi
echo ""

# Step 8: Bring application back up
echo "📝 Step 8/8: Bringing application back online..."
php artisan up
echo -e "${GREEN}✅ Application is now ONLINE${NC}"
echo ""

# Verification
echo -e "${YELLOW}═══════════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}✅ RECOVERY COMPLETE${NC}"
echo ""
echo "📊 Verifying data restoration..."
echo ""

php artisan tinker --execute="
    echo 'Companies: ' . \App\Models\Company::count() . PHP_EOL;
    echo 'Services: ' . \App\Models\Service::withoutGlobalScopes()->count() . PHP_EOL;
    echo 'Staff: ' . \App\Models\Staff::withoutGlobalScopes()->count() . PHP_EOL;
    echo 'Customers: ' . \App\Models\Customer::withoutGlobalScopes()->count() . PHP_EOL;
    echo 'Phone Numbers: ' . \App\Models\PhoneNumber::withoutGlobalScopes()->count() . PHP_EOL;
"

echo ""
echo -e "${GREEN}✅ Database recovery completed successfully${NC}"
echo ""
echo "🔍 NEXT STEPS:"
echo "   1. Login to admin panel: https://api.askproai.de/admin"
echo "   2. Verify company data: Should see 5 companies"
echo "   3. Check menu items: All 36 resources should be visible"
echo "   4. Run Phase 2 fix: Re-enable resource discovery"
echo "   5. Run Phase 3 fix: Fix authentication guards"
echo "   6. Run Phase 4 setup: Implement preventive measures"
echo ""
echo "📖 Full RCA Report: /var/www/api-gateway/RCA_ADMIN_PANEL_DATA_LOSS_2025-10-27.md"
echo ""
echo -e "${YELLOW}═══════════════════════════════════════════════════════════════${NC}"
