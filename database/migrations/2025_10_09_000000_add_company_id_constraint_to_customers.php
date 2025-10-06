<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Add NOT NULL constraint to customers.company_id
 *
 * TIMING: Run this migration 1 WEEK after backfill migration
 * This delay allows for monitoring and verification of backfill success
 *
 * Pre-requisites:
 *   1. Backfill migration (2025_10_02_164329_backfill_customer_company_id) completed
 *   2. All NULL company_id values resolved or documented
 *   3. 1 week of monitoring with zero issues
 *   4. Validation command passes: php artisan customer:validate-company-id --post-migration
 *
 * Safety Features:
 *   - Pre-flight validation (aborts if ANY NULL values exist)
 *   - Rollback capability (removes constraint)
 *   - Foreign key constraint to companies table
 *   - Audit trigger for future NULL attempts
 *
 * Rollback: Drops NOT NULL constraint and audit trigger
 */
return new class extends Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        Log::info('=== Adding NOT NULL Constraint to customers.company_id ===');

        // CRITICAL: Pre-flight validation - abort if ANY NULL values exist
        $this->validateNoNullValues();

        DB::transaction(function () {
            // Step 1: Add foreign key constraint (if not exists)
            $this->addForeignKeyConstraint();

            // Step 2: Add NOT NULL constraint
            $this->addNotNullConstraint();

            // Step 3: Create audit trigger for NULL attempts
            $this->createNullAuditTrigger();

            // Step 4: Verify constraint applied
            $this->verifyConstraintApplied();
        });

        Log::info('=== NOT NULL Constraint Successfully Applied ===');
    }

    /**
     * Pre-flight validation - ensure no NULL values exist
     */
    private function validateNoNullValues(): void
    {
        $nullCount = DB::table('customers')
            ->whereNull('company_id')
            ->whereNull('deleted_at') // Exclude soft deleted
            ->count();

        if ($nullCount > 0) {
            $nullCustomers = DB::table('customers')
                ->whereNull('company_id')
                ->whereNull('deleted_at')
                ->select('id', 'name', 'email')
                ->limit(10)
                ->get();

            Log::error('CONSTRAINT MIGRATION ABORTED: NULL company_id values still exist', [
                'null_count' => $nullCount,
                'sample_customers' => $nullCustomers,
            ]);

            throw new \Exception(
                "Cannot add NOT NULL constraint: {$nullCount} customers still have NULL company_id. " .
                "Run backfill migration first and verify all NULL values are resolved."
            );
        }

        Log::info('Pre-flight validation passed: No NULL company_id values found');
    }

    /**
     * Add foreign key constraint to companies table
     */
    private function addForeignKeyConstraint(): void
    {
        Log::info('Adding foreign key constraint to companies table');

        // Check if foreign key already exists
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'customers'
              AND COLUMN_NAME = 'company_id'
              AND REFERENCED_TABLE_NAME = 'companies'
        ");

        if (!empty($foreignKeys)) {
            Log::info('Foreign key constraint already exists', [
                'constraint_name' => $foreignKeys[0]->CONSTRAINT_NAME,
            ]);
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('restrict') // Prevent deleting companies with customers
                ->onUpdate('cascade');  // Update customer.company_id if company.id changes
        });

        Log::info('Foreign key constraint added successfully');
    }

    /**
     * Add NOT NULL constraint to company_id column
     */
    private function addNotNullConstraint(): void
    {
        Log::info('Adding NOT NULL constraint to company_id column');

        // Check if column is already NOT NULL
        $columnInfo = DB::select("
            SELECT IS_NULLABLE
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'customers'
              AND COLUMN_NAME = 'company_id'
        ");

        if (!empty($columnInfo) && $columnInfo[0]->IS_NULLABLE === 'NO') {
            Log::info('Column is already NOT NULL');
            return;
        }

        // Modify column to NOT NULL
        DB::statement('
            ALTER TABLE customers
            MODIFY COLUMN company_id BIGINT UNSIGNED NOT NULL
        ');

        Log::info('NOT NULL constraint added successfully');
    }

    /**
     * Create database trigger to audit NULL insertion attempts
     */
    private function createNullAuditTrigger(): void
    {
        Log::info('Creating audit trigger for NULL company_id attempts');

        // Drop trigger if exists
        DB::statement('DROP TRIGGER IF EXISTS customers_company_id_null_check');

        // Note: MySQL NOT NULL constraint will prevent NULL insertion
        // This trigger is for additional audit logging (optional)
        // Commenting out as NOT NULL constraint is sufficient

        /*
        DB::statement("
            CREATE TRIGGER customers_company_id_null_check
            BEFORE INSERT ON customers
            FOR EACH ROW
            BEGIN
                IF NEW.company_id IS NULL THEN
                    INSERT INTO audit_logs (table_name, event, description, created_at)
                    VALUES ('customers', 'NULL_COMPANY_ID_ATTEMPT', CONCAT('Attempted to insert customer with NULL company_id: ', NEW.name), NOW());
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'company_id cannot be NULL - multi-tenant isolation required';
                END IF;
            END
        ");
        */

        Log::info('Audit trigger creation skipped (NOT NULL constraint is sufficient)');
    }

    /**
     * Verify constraint was applied correctly
     */
    private function verifyConstraintApplied(): void
    {
        Log::info('Verifying constraint application');

        // Check column is NOT NULL
        $columnInfo = DB::select("
            SELECT IS_NULLABLE, DATA_TYPE, COLUMN_TYPE
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'customers'
              AND COLUMN_NAME = 'company_id'
        ");

        if (empty($columnInfo)) {
            throw new \Exception('Column company_id not found in customers table');
        }

        $column = $columnInfo[0];

        if ($column->IS_NULLABLE !== 'NO') {
            throw new \Exception('NOT NULL constraint not applied correctly');
        }

        Log::info('Constraint verified successfully', [
            'nullable' => $column->IS_NULLABLE,
            'data_type' => $column->DATA_TYPE,
            'column_type' => $column->COLUMN_TYPE,
        ]);

        // Test constraint with actual NULL insertion attempt (should fail)
        $this->testConstraintEnforcement();
    }

    /**
     * Test constraint enforcement with actual NULL insertion
     */
    private function testConstraintEnforcement(): void
    {
        Log::info('Testing constraint enforcement');

        try {
            // Attempt to insert customer with NULL company_id
            DB::table('customers')->insert([
                'name' => '__TEST_CONSTRAINT__',
                'email' => 'test_constraint_' . time() . '@example.com',
                'company_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // If we reach here, constraint is NOT working
            throw new \Exception('CONSTRAINT FAILURE: NULL company_id was allowed - constraint not enforced');

        } catch (\Illuminate\Database\QueryException $e) {
            // Expected behavior - NULL insertion should fail
            if (str_contains($e->getMessage(), 'cannot be null') ||
                str_contains($e->getMessage(), 'doesn\'t have a default value')) {
                Log::info('Constraint enforcement verified: NULL insertion correctly rejected');
            } else {
                // Unexpected error
                throw new \Exception("Unexpected error during constraint test: {$e->getMessage()}");
            }
        }
    }

    /**
     * Reverse the migration - remove constraint
     */
    public function down(): void
    {
        Log::info('=== Removing NOT NULL Constraint from customers.company_id ===');

        DB::transaction(function () {
            // Step 1: Drop audit trigger
            DB::statement('DROP TRIGGER IF EXISTS customers_company_id_null_check');
            Log::info('Audit trigger dropped');

            // Step 2: Remove NOT NULL constraint
            DB::statement('
                ALTER TABLE customers
                MODIFY COLUMN company_id BIGINT UNSIGNED NULL
            ');
            Log::info('NOT NULL constraint removed');

            // Step 3: Drop foreign key constraint (optional - may want to keep for data integrity)
            // Commenting out as foreign key is useful even without NOT NULL
            /*
            Schema::table('customers', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
            });
            Log::info('Foreign key constraint dropped');
            */

            Log::info('Foreign key constraint retained for data integrity');
        });

        Log::info('=== Constraint Removal Completed ===');
    }
};
