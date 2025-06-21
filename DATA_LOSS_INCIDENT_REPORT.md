# AskProAI Data Loss Incident Report
**Date**: June 18, 2025  
**Severity**: **CRITICAL**  
**Status**: Data Loss Confirmed

## Executive Summary

A critical data loss incident occurred on June 17, 2025, when the migration `2025_06_17_cleanup_redundant_tables.php` was executed. This migration dropped 119 tables, many of which contained production data. While the table structures were later recreated, the data was not restored.

## Impact Assessment

### Data Lost
Based on analysis of the June 17 backup (created at 03:05 AM, before the cleanup):

| Table | Records Lost | Business Impact |
|-------|--------------|-----------------|
| appointments | 20 | Customer bookings lost |
| customers | 31 | Customer contact information lost |
| calls | Multiple | Call history and transcripts lost |
| staff | 25 | Employee assignments lost |
| services | 17 | Service catalog lost |
| branches | 15 | Location configurations lost |
| working_hours | 120 | Business hours settings lost |
| phone_numbers | 11 | Phone routing configurations lost |
| calcom_event_types | 2 | Calendar integration settings lost |
| retell_webhooks | 1,383 | Webhook processing history lost |

### Root Cause

1. The migration `2025_06_17_cleanup_redundant_tables.php` was designed to remove unused tables
2. However, it included tables that were actually in use (e.g., `retell_webhooks`, `activity_log`, `sessions`)
3. The migration cannot be reversed (no down() implementation)
4. Subsequent migrations recreated table structures but not data

## Recovery Options

### Option 1: Full Database Restore (Recommended)
**Pros:**
- Complete data recovery
- Preserves all relationships
- Quickest path to operational status

**Cons:**
- Will lose any changes made after June 17, 03:05 AM
- Need to re-run migrations from June 17

**Steps:**
```bash
# 1. Backup current state
mysqldump -u root -p'V9LGz2tdR5gpDQz' askproai_db > /var/www/api-gateway/backups/post_incident_backup.sql

# 2. Drop and recreate database
mysql -u root -p'V9LGz2tdR5gpDQz' -e "DROP DATABASE askproai_db; CREATE DATABASE askproai_db;"

# 3. Restore from June 17 backup
zcat /var/backups/mysql/askproai_db_2025-06-17_03-05.sql.gz | mysql -u root -p'V9LGz2tdR5gpDQz' askproai_db

# 4. Run migrations EXCEPT the cleanup one
php artisan migrate --step
```

### Option 2: Selective Data Recovery
**Pros:**
- Preserves current schema changes
- More controlled approach

**Cons:**
- Complex due to schema changes
- Risk of missing data relationships
- Time-consuming

**Approach:**
- Extract data from backup
- Map old schema to new schema
- Insert data with foreign key checks disabled

### Option 3: Manual Data Re-entry
**Pros:**
- Clean start
- Opportunity to validate data

**Cons:**
- Significant manual effort
- Risk of errors
- Business disruption

## Prevention Measures

### Immediate Actions Required
1. **Disable Dangerous Migrations**
   ```bash
   mv database/migrations/2025_06_17_cleanup_redundant_tables.php \
      database/migrations/2025_06_17_cleanup_redundant_tables.php.disabled
   ```

2. **Implement Migration Safety Checks**
   - Always include reversible down() methods
   - Require backup before destructive operations
   - Add confirmation prompts for DROP operations

3. **Backup Policy Enhancement**
   - Pre-migration automatic backups
   - Backup verification before destructive operations
   - Retention of hourly backups for 7 days

### Code Changes Needed

Create a safe migration base class:
```php
abstract class SafeDestructiveMigration extends Migration
{
    protected function confirmDestruction($tables)
    {
        if (app()->environment('production')) {
            $count = count($tables);
            echo "WARNING: This migration will DROP $count tables!\n";
            echo "Tables: " . implode(', ', $tables) . "\n";
            
            if (!$this->confirm('Have you created a backup?')) {
                throw new \Exception('Migration aborted: No backup confirmed');
            }
            
            if (!$this->confirm('Are you SURE you want to proceed?')) {
                throw new \Exception('Migration aborted by user');
            }
        }
    }
}
```

## Recovery Decision

**RECOMMENDED ACTION**: Perform Option 1 (Full Database Restore) immediately to restore service.

**Rationale:**
- Customer data integrity is paramount
- 20 lost appointments represent real customer bookings
- Call history is critical for business continuity
- Schema changes can be re-applied after data recovery

## Lessons Learned

1. **Never trust migration names** - "cleanup_redundant_tables" included active tables
2. **Always verify before dropping** - Check row counts and recent activity
3. **Implement safeguards** - Require backups and confirmations
4. **Test in staging first** - This should have been caught in testing
5. **Monitor post-migration** - Set up alerts for empty critical tables

## Next Steps

1. **Immediate**: Execute recovery plan
2. **Today**: Implement migration safeguards
3. **This Week**: Review all pending migrations
4. **This Month**: Implement automated backup testing
5. **Ongoing**: Monthly disaster recovery drills

## Contact

For questions about this incident:
- **Technical Lead**: [Responsible Developer]
- **Database Admin**: root@v2202503255565320322.happysrv.de
- **Backup Location**: /var/backups/mysql/

---

**Document Status**: Created June 18, 2025  
**Last Updated**: June 18, 2025  
**Classification**: CRITICAL INCIDENT