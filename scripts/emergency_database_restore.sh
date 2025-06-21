#!/bin/bash

# Emergency Database Restore Script
# This script restores the database from the June 17, 2025 backup
# Created in response to data loss incident

set -e  # Exit on error

echo "=== AskProAI Emergency Database Restore ==="
echo "This will restore the database to June 17, 2025 03:05 AM state"
echo ""

# Configuration
DB_USER="root"
DB_PASS="V9LGz2tdR5gpDQz"
DB_NAME="askproai_db"
BACKUP_FILE="/var/backups/mysql/askproai_db_2025-06-17_03-05.sql.gz"
BACKUP_DIR="/var/www/api-gateway/backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Safety check
read -p "This will DESTROY all current data. Are you sure? (type 'yes' to continue): " confirm
if [ "$confirm" != "yes" ]; then
    echo "Aborted."
    exit 1
fi

echo ""
echo "Step 1: Creating backup of current state..."
mysqldump -u $DB_USER -p"$DB_PASS" $DB_NAME > "$BACKUP_DIR/pre_restore_backup_$TIMESTAMP.sql"
echo "✓ Current state backed up to: $BACKUP_DIR/pre_restore_backup_$TIMESTAMP.sql"

echo ""
echo "Step 2: Checking backup file..."
if [ ! -f "$BACKUP_FILE" ]; then
    echo "❌ ERROR: Backup file not found: $BACKUP_FILE"
    exit 1
fi
echo "✓ Backup file found: $BACKUP_FILE"

echo ""
echo "Step 3: Dropping current database..."
mysql -u $DB_USER -p"$DB_PASS" -e "DROP DATABASE IF EXISTS $DB_NAME;"
echo "✓ Database dropped"

echo ""
echo "Step 4: Creating fresh database..."
mysql -u $DB_USER -p"$DB_PASS" -e "CREATE DATABASE $DB_NAME DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
echo "✓ Database created"

echo ""
echo "Step 5: Restoring from backup..."
echo "This may take a few minutes..."
zcat "$BACKUP_FILE" | mysql -u $DB_USER -p"$DB_PASS" $DB_NAME
echo "✓ Data restored"

echo ""
echo "Step 6: Verifying restoration..."
echo "Key table record counts:"
mysql -u $DB_USER -p"$DB_PASS" $DB_NAME -e "
SELECT 'appointments' as table_name, COUNT(*) as count FROM appointments
UNION SELECT 'customers', COUNT(*) FROM customers
UNION SELECT 'calls', COUNT(*) FROM calls
UNION SELECT 'staff', COUNT(*) FROM staff
UNION SELECT 'services', COUNT(*) FROM services
UNION SELECT 'branches', COUNT(*) FROM branches
UNION SELECT 'companies', COUNT(*) FROM companies;"

echo ""
echo "Step 7: Checking migrations status..."
echo "Current migration batch:"
mysql -u $DB_USER -p"$DB_PASS" $DB_NAME -e "SELECT MAX(batch) as max_batch FROM migrations;"

echo ""
echo "=== RESTORATION COMPLETE ==="
echo ""
echo "IMPORTANT NEXT STEPS:"
echo "1. Disable the problematic migration:"
echo "   mv database/migrations/2025_06_17_cleanup_redundant_tables.php database/migrations/2025_06_17_cleanup_redundant_tables.php.disabled"
echo ""
echo "2. Run migrations from June 17 onwards (EXCEPT the cleanup):"
echo "   php artisan migrate"
echo ""
echo "3. Clear all caches:"
echo "   php artisan optimize:clear"
echo ""
echo "4. Test the application thoroughly"
echo ""
echo "5. Create a new backup after verification:"
echo "   mysqldump -u $DB_USER -p'$DB_PASS' $DB_NAME | gzip > $BACKUP_DIR/post_recovery_backup_$TIMESTAMP.sql.gz"
echo ""

# Create a recovery log
cat > "$BACKUP_DIR/recovery_log_$TIMESTAMP.txt" << EOF
Recovery performed at: $(date)
Restored from: $BACKUP_FILE
Pre-restore backup: $BACKUP_DIR/pre_restore_backup_$TIMESTAMP.sql
Performed by: $(whoami)
Reason: Data loss due to cleanup_redundant_tables migration
EOF

echo "Recovery log saved to: $BACKUP_DIR/recovery_log_$TIMESTAMP.txt"