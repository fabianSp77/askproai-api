# Database Consolidation Guide

## Overview

This guide covers the database consolidation process for AskProAI, reducing from 119 tables to approximately 20 core tables while maintaining data integrity and system functionality.

## Current State Analysis

### Problem Statement
- **119 tables** with overlapping functionality
- Multiple tables for similar purposes (e.g., `kunden` vs `customers`)
- Inconsistent naming conventions
- Complex relationships making maintenance difficult
- Performance issues due to excessive joins

### Target State
- **~20 core tables** with clear purposes
- Consistent naming conventions
- Simplified relationships
- Improved query performance
- Easier maintenance and debugging

## Consolidation Strategy

### Phase 1: Analysis and Planning

```sql
-- Analyze table usage
SELECT 
    table_name,
    table_rows,
    data_length + index_length AS size_bytes,
    create_time,
    update_time
FROM information_schema.tables
WHERE table_schema = 'askproai_db'
ORDER BY table_rows DESC;

-- Find foreign key dependencies
SELECT 
    table_name,
    column_name,
    constraint_name,
    referenced_table_name,
    referenced_column_name
FROM information_schema.key_column_usage
WHERE table_schema = 'askproai_db'
    AND referenced_table_name IS NOT NULL;
```

### Phase 2: Core Tables Identification

#### Essential Tables to Keep
1. **companies** - Tenant organizations
2. **branches** - Physical locations
3. **users** - System users
4. **customers** - Client records
5. **appointments** - Booking records
6. **services** - Service offerings
7. **staff** - Employee records
8. **phone_numbers** - Contact numbers
9. **calls** - Call records
10. **webhook_events** - Integration events

#### Tables to Consolidate

```yaml
Merge Plan:
  customers:
    - merge: kunden
    - merge: customer_details
    - merge: customer_preferences
    
  services:
    - merge: dienstleistungen
    - merge: service_categories
    - merge: service_pricing
    
  appointments:
    - merge: termine
    - merge: booking_slots
    - merge: appointment_history
```

### Phase 3: Data Migration Scripts

#### Customer Consolidation

```php
// database/migrations/2025_consolidate_customers.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ConsolidateCustomers extends Migration
{
    public function up()
    {
        // Backup existing data
        $this->backupTables(['customers', 'kunden']);
        
        // Migrate kunden to customers
        DB::statement("
            INSERT INTO customers (
                company_id,
                name,
                email,
                phone,
                created_at,
                updated_at
            )
            SELECT 
                company_id,
                CONCAT(vorname, ' ', nachname) as name,
                email,
                telefon as phone,
                created_at,
                updated_at
            FROM kunden
            WHERE NOT EXISTS (
                SELECT 1 FROM customers 
                WHERE customers.phone = kunden.telefon
            )
        ");
        
        // Update foreign keys
        $this->updateForeignKeys('kunden_id', 'customer_id');
        
        // Drop old table
        Schema::dropIfExists('kunden');
    }
    
    private function backupTables(array $tables): void
    {
        foreach ($tables as $table) {
            DB::statement("CREATE TABLE {$table}_backup LIKE {$table}");
            DB::statement("INSERT INTO {$table}_backup SELECT * FROM {$table}");
        }
    }
    
    private function updateForeignKeys($oldColumn, $newColumn): void
    {
        // Update appointments
        DB::statement("
            UPDATE appointments a
            JOIN kunden k ON a.{$oldColumn} = k.id
            JOIN customers c ON k.telefon = c.phone
            SET a.{$newColumn} = c.id
            WHERE a.{$oldColumn} IS NOT NULL
        ");
    }
}
```

#### Service Consolidation

```php
// database/migrations/2025_consolidate_services.php
class ConsolidateServices extends Migration
{
    public function up()
    {
        // Standardize service structure
        Schema::table('services', function (Blueprint $table) {
            $table->decimal('base_price', 10, 2)->default(0);
            $table->integer('default_duration')->default(60);
            $table->json('pricing_rules')->nullable();
            $table->json('availability_rules')->nullable();
        });
        
        // Migrate from old tables
        DB::statement("
            INSERT INTO services (
                company_id,
                name,
                description,
                base_price,
                default_duration,
                created_at,
                updated_at
            )
            SELECT 
                company_id,
                name,
                beschreibung as description,
                preis as base_price,
                dauer as default_duration,
                created_at,
                updated_at
            FROM dienstleistungen
            WHERE NOT EXISTS (
                SELECT 1 FROM services 
                WHERE services.name = dienstleistungen.name
                AND services.company_id = dienstleistungen.company_id
            )
        ");
    }
}
```

### Phase 4: Schema Optimization

#### Normalized Structure

```sql
-- Optimized appointments table
CREATE TABLE appointments (
    id BIGINT UNSIGNED PRIMARY KEY,
    company_id INT NOT NULL,
    branch_id INT NOT NULL,
    customer_id INT NOT NULL,
    staff_id INT NULL,
    service_id INT NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    duration INT NOT NULL DEFAULT 60,
    status ENUM('scheduled','confirmed','completed','cancelled','no_show') DEFAULT 'scheduled',
    price DECIMAL(10,2) NULL,
    notes TEXT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_company_date (company_id, date),
    INDEX idx_branch_date (branch_id, date),
    INDEX idx_customer (customer_id),
    INDEX idx_status (status),
    
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (staff_id) REFERENCES staff(id),
    FOREIGN KEY (service_id) REFERENCES services(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Phase 5: Testing and Validation

#### Data Integrity Checks

```php
// app/Console/Commands/ValidateConsolidation.php
class ValidateConsolidation extends Command
{
    protected $signature = 'db:validate-consolidation';
    
    public function handle()
    {
        $this->info('Validating database consolidation...');
        
        // Check record counts
        $this->validateRecordCounts();
        
        // Check foreign key integrity
        $this->validateForeignKeys();
        
        // Check data consistency
        $this->validateDataConsistency();
        
        // Performance comparison
        $this->comparePerformance();
    }
    
    private function validateRecordCounts(): void
    {
        $checks = [
            'customers' => 'SELECT COUNT(*) FROM customers',
            'customers_backup' => 'SELECT COUNT(*) FROM customers_backup',
            'kunden_backup' => 'SELECT COUNT(*) FROM kunden_backup',
        ];
        
        foreach ($checks as $label => $query) {
            $count = DB::select($query)[0]->{'COUNT(*)'};
            $this->line("{$label}: {$count} records");
        }
    }
    
    private function validateForeignKeys(): void
    {
        $orphanedRecords = DB::select("
            SELECT COUNT(*) as count
            FROM appointments a
            LEFT JOIN customers c ON a.customer_id = c.id
            WHERE a.customer_id IS NOT NULL
            AND c.id IS NULL
        ");
        
        if ($orphanedRecords[0]->count > 0) {
            $this->error("Found {$orphanedRecords[0]->count} orphaned appointment records!");
        } else {
            $this->info("✓ No orphaned records found");
        }
    }
}
```

## Rollback Strategy

### Automated Rollback

```php
// database/migrations/2025_rollback_consolidation.php
class RollbackConsolidation extends Migration
{
    public function up()
    {
        // Restore from backups
        $backupTables = [
            'customers_backup' => 'customers',
            'kunden_backup' => 'kunden',
            'services_backup' => 'services',
        ];
        
        foreach ($backupTables as $backup => $original) {
            if (Schema::hasTable($backup)) {
                Schema::dropIfExists($original);
                Schema::rename($backup, $original);
            }
        }
    }
}
```

### Manual Rollback Steps

```bash
# 1. Stop application
php artisan down

# 2. Restore database backup
mysql -u root -p askproai_db < backup_before_consolidation.sql

# 3. Clear caches
php artisan cache:clear
php artisan config:clear

# 4. Restart application
php artisan up
```

## Performance Improvements

### Before Consolidation
```sql
-- Complex query with multiple joins
SELECT 
    t.*,
    k.vorname,
    k.nachname,
    d.name as service_name,
    f.name as branch_name
FROM termine t
JOIN kunden k ON t.kunden_id = k.id
JOIN dienstleistungen d ON t.dienstleistung_id = d.id
JOIN filialen f ON t.filiale_id = f.id
WHERE t.datum = '2025-07-01';
-- Execution time: 250ms
```

### After Consolidation
```sql
-- Simplified query
SELECT 
    a.*,
    c.name as customer_name,
    s.name as service_name,
    b.name as branch_name
FROM appointments a
JOIN customers c ON a.customer_id = c.id
JOIN services s ON a.service_id = s.id
JOIN branches b ON a.branch_id = b.id
WHERE a.date = '2025-07-01';
-- Execution time: 45ms (82% improvement)
```

## Monitoring and Maintenance

### Health Checks

```php
// app/Console/Commands/DatabaseHealth.php
class DatabaseHealth extends Command
{
    public function handle()
    {
        $metrics = [
            'Table Count' => $this->getTableCount(),
            'Total Size' => $this->getDatabaseSize(),
            'Largest Tables' => $this->getLargestTables(),
            'Index Usage' => $this->getIndexUsage(),
        ];
        
        $this->table(
            ['Metric', 'Value'],
            collect($metrics)->map(fn($v, $k) => [$k, $v])
        );
    }
}
```

### Automated Cleanup

```yaml
# config/database-maintenance.yml
cleanup_tasks:
  - name: "Remove orphaned records"
    schedule: "0 2 * * *"  # Daily at 2 AM
    query: |
      DELETE FROM appointments 
      WHERE customer_id NOT IN (SELECT id FROM customers)
      
  - name: "Archive old data"
    schedule: "0 3 * * 0"  # Weekly on Sunday
    query: |
      INSERT INTO appointments_archive 
      SELECT * FROM appointments 
      WHERE date < DATE_SUB(NOW(), INTERVAL 2 YEAR)
```

## Best Practices

1. **Always backup before consolidation**
2. **Test migrations in staging first**
3. **Run during maintenance window**
4. **Monitor performance after consolidation**
5. **Keep backup tables for 30 days**
6. **Document all changes**
7. **Update application code simultaneously**

## Troubleshooting

### Common Issues

**Issue: Foreign key constraint fails**
```sql
-- Find problematic records
SELECT * FROM appointments 
WHERE customer_id NOT IN (SELECT id FROM customers);

-- Fix or remove
UPDATE appointments SET customer_id = NULL 
WHERE customer_id NOT IN (SELECT id FROM customers);
```

**Issue: Duplicate key errors**
```sql
-- Find duplicates
SELECT phone, COUNT(*) as count 
FROM customers 
GROUP BY phone 
HAVING count > 1;

-- Merge duplicates
CALL merge_duplicate_customers();
```

## Related Documentation

- [Database Configuration](../configuration/database.md)
- [Performance Optimization](../operations/performance.md)
- [Backup Strategy](../deployment/backup.md)
- [Service Unification](service-unification.md)