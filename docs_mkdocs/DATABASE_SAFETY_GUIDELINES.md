# Database Safety Guidelines

## Overview

Following the data loss incident of June 17, 2025, these guidelines establish mandatory practices for database operations to prevent future data loss.

## Critical Rules

### 1. **NEVER Drop Tables Without Verification**
- Count records before dropping
- Check last modification dates
- Verify table is truly unused
- Document why table is being dropped

### 2. **ALWAYS Create Backups Before Destructive Operations**
```bash
# Before ANY migration that drops tables or columns
mysqldump -u root -p askproai_db | gzip > /var/backups/mysql/askproai_db_$(date +%Y-%m-%d_%H-%M-%S)_pre_migration.sql.gz
```

### 3. **Use SafeDestructiveMigration Base Class**
```php
use App\Database\Migrations\SafeDestructiveMigration;

class DropUnusedTables extends SafeDestructiveMigration
{
    protected function getTablesToDrop(): array
    {
        return ['old_table1', 'old_table2'];
    }
    
    protected function executeDestructiveOperation(): void
    {
        Schema::dropIfExists('old_table1');
        Schema::dropIfExists('old_table2');
    }
}
```

## Migration Best Practices

### Before Creating a Migration

1. **Analyze Impact**
   ```sql
   -- Check table usage
   SELECT COUNT(*) FROM table_name;
   SELECT MAX(updated_at) FROM table_name;
   
   -- Check foreign key dependencies
   SELECT 
       TABLE_NAME,
       COLUMN_NAME,
       CONSTRAINT_NAME,
       REFERENCED_TABLE_NAME,
       REFERENCED_COLUMN_NAME
   FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
   WHERE REFERENCED_TABLE_NAME = 'table_name';
   ```

2. **Document Decision**
   - Why is this change needed?
   - What data will be affected?
   - Is there a rollback plan?

### Safe Migration Patterns

#### Adding Columns (Safe)
```php
Schema::table('users', function (Blueprint $table) {
    $table->string('new_column')->nullable()->after('existing_column');
});
```

#### Dropping Columns (Caution Required)
```php
// First migration: deprecate the column
Schema::table('users', function (Blueprint $table) {
    $table->string('old_column')->nullable()->comment('DEPRECATED: Will be removed in 30 days');
});

// After 30 days: drop the column
Schema::table('users', function (Blueprint $table) {
    $table->dropColumn('old_column');
});
```

#### Renaming Tables (Safe with Backward Compatibility)
```php
// Create view for backward compatibility
Schema::rename('old_table', 'new_table');
DB::statement('CREATE VIEW old_table AS SELECT * FROM new_table');
```

## Emergency Procedures

### If You Accidentally Drop Data

1. **STOP** - Don't run any more migrations
2. **Check Backups**
   ```bash
   ls -lah /var/backups/mysql/askproai_db_*.sql.gz
   ```
3. **Restore Immediately**
   ```bash
   ./scripts/emergency_database_restore.sh
   ```

### Daily Backup Verification

Add to crontab:
```bash
# Daily backup verification at 4 AM
0 4 * * * /var/www/api-gateway/scripts/verify_daily_backup.sh
```

## Testing Requirements

### Local Testing
1. Run migration on local database first
2. Verify data integrity
3. Test rollback procedure

### Staging Testing
1. Copy production data to staging (sanitized)
2. Run migration
3. Full application test
4. Document results

### Production Deployment
1. Schedule during low-traffic window
2. Create fresh backup
3. Run migration with monitoring
4. Verify application functionality
5. Keep backup for 30 days

## Monitoring

### Post-Migration Checks
```php
// Add to health check endpoint
public function checkCriticalTables()
{
    $criticalTables = [
        'appointments' => 1,  // Minimum expected records
        'customers' => 1,
        'companies' => 1,
        'branches' => 1,
        'staff' => 1,
    ];
    
    foreach ($criticalTables as $table => $minCount) {
        $count = DB::table($table)->count();
        if ($count < $minCount) {
            alert("CRITICAL: Table {$table} has only {$count} records!");
        }
    }
}
```

### Automated Alerts

Set up monitoring for:
- Tables with 0 records that shouldn't be empty
- Sudden drop in record counts
- Failed migrations
- Backup age > 24 hours

## Recovery Time Objectives

- **Detection**: < 5 minutes (automated monitoring)
- **Decision**: < 15 minutes (assess impact)
- **Recovery**: < 30 minutes (restore from backup)
- **Verification**: < 1 hour (full system check)

## Approval Process

For any migration that:
- Drops tables
- Drops columns
- Truncates data
- Modifies primary keys

Requires:
1. Code review by senior developer
2. Backup verification
3. Rollback plan documentation
4. Staging environment test results

## Tools and Scripts

### Check Table Usage
```bash
php artisan db:table-usage {table_name}
```

### Safe Migration Generator
```bash
php artisan make:migration:safe {migration_name}
```

### Backup Before Migration
```bash
php artisan migrate --backup
```

## Contact for Database Emergencies

- **Primary DBA**: root@server
- **Backup Location**: /var/backups/mysql/
- **Recovery Scripts**: /var/www/api-gateway/scripts/
- **On-Call**: [Phone Number]

---

**Remember**: It's better to delay a migration than to lose data. When in doubt, don't drop out!