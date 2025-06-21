# SQLite Migration Fix Report

## Problem
The test suite was failing with a 94% failure rate due to MySQL-specific syntax in migrations that was incompatible with SQLite used in testing.

## Solution Implemented

### 1. CompatibleMigration Base Class
Already existed at `app/Database/CompatibleMigration.php` with the following features:
- Database driver detection (`isSQLite()`, `isMySQL()`)
- JSON column compatibility (`addJsonColumn()`)
- Safe table/column operations
- Index management with database-specific syntax
- Foreign key constraint handling

### 2. Migration Updates
Updated 58 migrations to use `CompatibleMigration` instead of Laravel's base `Migration` class. Key changes:
- Replaced `$table->json()` with `$this->addJsonColumn()`
- Added database-specific handling for column changes
- Fixed foreign key data type mismatches

### 3. Missing Dependencies
- Installed PHP SQLite extension: `php8.3-sqlite3`
- SQLite PDO driver was already available

### 4. Fixed Issues
1. **JSON Column Handling**: SQLite doesn't support JSON columns, so they're created as TEXT
2. **Column Type Changes**: SQLite can't change column types, so those operations are skipped
3. **Foreign Key Mismatches**: Fixed data type mismatches (uuid vs bigint, int vs bigint)
4. **Missing Tables**: Created missing `invoices` and `invoice_items_flexible` tables

## Migrations That Required Special Handling

### Manual Updates
- `2025_05_01_091735_alter_calls_raw_to_json.php` - Added SQLite check to skip column type change
- `2025_05_26_111052_add_tenant_id_to_users_table.php` - Fixed UUID to bigint mismatch
- `2025_05_19_000000_create_dashboard_configurations_table.php` - Fixed user_id data type

### New Migrations Created
- `2025_06_16_170100_create_invoices_table.php` - Missing invoices table
- `2025_06_16_170110_create_invoice_items_flexible_table.php` - Missing invoice items table

## Testing Commands
```bash
# Run migrations
php artisan migrate:fresh --force

# Run specific test
php artisan test tests/Unit/CriticalOptimizationsTest.php

# Run all tests
php artisan test
```

## Remaining Issues
While migrations now run successfully with SQLite, there may still be test failures due to:
1. Application code that uses MySQL-specific queries
2. Tests that rely on MySQL-specific features
3. Model casts that expect JSON support

## Recommendations
1. Review all `DB::raw()` and `whereRaw()` usage for MySQL-specific syntax
2. Consider using database-specific test traits for features that can't be abstracted
3. Ensure all models handle JSON fields properly when using SQLite (cast to array)
4. Add CI/CD testing against both MySQL and SQLite to catch compatibility issues early

## Script for Future Migration Updates
Created `/var/www/api-gateway/fix-migrations-for-sqlite.php` to automatically update migrations that use JSON columns to be SQLite-compatible.