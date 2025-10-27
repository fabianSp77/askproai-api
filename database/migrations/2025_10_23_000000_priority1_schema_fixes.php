<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Priority 1 Schema Fixes - Database Validation Report 2025-10-23
 *
 * CRITICAL FIXES:
 * 1. Add NOT NULL constraints on company_id (multi-tenant security)
 * 2. Add missing foreign key constraints (data integrity)
 * 3. Add critical performance indexes
 *
 * ESTIMATED TIME: 2 hours
 * RISK: LOW (with proper backfill testing)
 * ROLLBACK: Revert constraints, no data loss
 *
 * DEPLOYMENT:
 * - Run during low-traffic window
 * - Backup database first: mysqldump -u user -p askproai_db > backup_$(date +%Y%m%d).sql
 * - Verify no orphaned records before running
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════════
        // STEP 1: Verify no orphaned records exist
        // ═══════════════════════════════════════════════════════════

        $this->verifyNoOrphanedRecords();

        // ═══════════════════════════════════════════════════════════
        // STEP 2: Backfill NULL company_id values
        // ═══════════════════════════════════════════════════════════

        $this->backfillCompanyIds();

        // ═══════════════════════════════════════════════════════════
        // STEP 3: Add NOT NULL constraints
        // ═══════════════════════════════════════════════════════════

        $this->addNotNullConstraints();

        // ═══════════════════════════════════════════════════════════
        // STEP 4: Add missing foreign key constraints
        // ═══════════════════════════════════════════════════════════

        $this->addForeignKeyConstraints();

        // ═══════════════════════════════════════════════════════════
        // STEP 5: Add critical performance indexes
        // ═══════════════════════════════════════════════════════════

        $this->addPerformanceIndexes();

        // ═══════════════════════════════════════════════════════════
        // STEP 6: Log completion
        // ═══════════════════════════════════════════════════════════

        \Log::info('Priority 1 schema fixes applied successfully', [
            'migration' => '2025_10_23_000000_priority1_schema_fixes',
            'timestamp' => now()->toIso8601String()
        ]);
    }

    /**
     * Verify no orphaned records exist (safety check)
     */
    protected function verifyNoOrphanedRecords(): void
    {
        $orphanedServices = DB::table('services')
            ->leftJoin('companies', 'services.company_id', '=', 'companies.id')
            ->whereNotNull('services.company_id')
            ->whereNull('companies.id')
            ->count();

        $orphanedCalls = DB::table('calls')
            ->leftJoin('companies', 'calls.company_id', '=', 'companies.id')
            ->whereNotNull('calls.company_id')
            ->whereNull('companies.id')
            ->count();

        if ($orphanedServices > 0) {
            throw new \Exception("Found {$orphanedServices} orphaned services. Cannot proceed with FK constraint.");
        }

        if ($orphanedCalls > 0) {
            throw new \Exception("Found {$orphanedCalls} orphaned calls. Cannot proceed with FK constraint.");
        }

        \Log::info('Orphaned record check passed', [
            'services' => 0,
            'calls' => 0
        ]);
    }

    /**
     * Backfill NULL company_id values from related tables
     */
    protected function backfillCompanyIds(): void
    {
        // Backfill services.company_id from branches
        $servicesUpdated = DB::table('services')
            ->whereNull('company_id')
            ->whereNotNull('branch_id')
            ->update([
                'company_id' => DB::raw('(SELECT company_id FROM branches WHERE branches.id = services.branch_id)')
            ]);

        // Backfill remaining services.company_id with default company (ID=1)
        $servicesDefaulted = DB::table('services')
            ->whereNull('company_id')
            ->update(['company_id' => 1]);

        // Backfill staff.company_id from branches
        $staffUpdated = DB::table('staff')
            ->whereNull('company_id')
            ->whereNotNull('branch_id')
            ->update([
                'company_id' => DB::raw('(SELECT company_id FROM branches WHERE branches.id = staff.branch_id)')
            ]);

        // Backfill remaining staff.company_id with default company (ID=1)
        $staffDefaulted = DB::table('staff')
            ->whereNull('company_id')
            ->update(['company_id' => 1]);

        // Backfill calls.company_id from customers
        $callsUpdated = DB::table('calls')
            ->whereNull('company_id')
            ->whereNotNull('customer_id')
            ->update([
                'company_id' => DB::raw('(SELECT company_id FROM customers WHERE customers.id = calls.customer_id)')
            ]);

        // Backfill remaining calls.company_id with default company (ID=1)
        $callsDefaulted = DB::table('calls')
            ->whereNull('company_id')
            ->update(['company_id' => 1]);

        // Backfill branches.company_id (should not be NULL, but safety check)
        $branchesDefaulted = DB::table('branches')
            ->whereNull('company_id')
            ->update(['company_id' => 1]);

        \Log::info('company_id backfill completed', [
            'services_from_branch' => $servicesUpdated,
            'services_defaulted' => $servicesDefaulted,
            'staff_from_branch' => $staffUpdated,
            'staff_defaulted' => $staffDefaulted,
            'calls_from_customer' => $callsUpdated,
            'calls_defaulted' => $callsDefaulted,
            'branches_defaulted' => $branchesDefaulted
        ]);
    }

    /**
     * Add NOT NULL constraints on company_id
     */
    protected function addNotNullConstraints(): void
    {
        // services.company_id
        if (Schema::hasColumn('services', 'company_id')) {
            DB::statement('ALTER TABLE services MODIFY company_id BIGINT UNSIGNED NOT NULL');
            \Log::info('Added NOT NULL constraint: services.company_id');
        }

        // staff.company_id
        if (Schema::hasColumn('staff', 'company_id')) {
            DB::statement('ALTER TABLE staff MODIFY company_id BIGINT UNSIGNED NOT NULL');
            \Log::info('Added NOT NULL constraint: staff.company_id');
        }

        // calls.company_id
        if (Schema::hasColumn('calls', 'company_id')) {
            DB::statement('ALTER TABLE calls MODIFY company_id BIGINT UNSIGNED NOT NULL');
            \Log::info('Added NOT NULL constraint: calls.company_id');
        }

        // branches.company_id
        if (Schema::hasColumn('branches', 'company_id')) {
            DB::statement('ALTER TABLE branches MODIFY company_id BIGINT UNSIGNED NOT NULL');
            \Log::info('Added NOT NULL constraint: branches.company_id');
        }
    }

    /**
     * Add missing foreign key constraints
     */
    protected function addForeignKeyConstraints(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Check if FK already exists (safe for re-runs)
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'services'
                  AND CONSTRAINT_NAME = 'fk_services_company'
            ");

            if (empty($foreignKeys)) {
                $table->foreign('company_id', 'fk_services_company')
                    ->references('id')
                    ->on('companies')
                    ->onDelete('cascade');

                \Log::info('Added foreign key: services.company_id → companies.id');
            }
        });

        Schema::table('calls', function (Blueprint $table) {
            // Check if FK already exists
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'calls'
                  AND CONSTRAINT_NAME = 'fk_calls_company'
            ");

            if (empty($foreignKeys)) {
                $table->foreign('company_id', 'fk_calls_company')
                    ->references('id')
                    ->on('companies')
                    ->onDelete('cascade');

                \Log::info('Added foreign key: calls.company_id → companies.id');
            }
        });
    }

    /**
     * Add critical performance indexes
     */
    protected function addPerformanceIndexes(): void
    {
        // 1. staff.calcom_user_id (Cal.com sync performance)
        Schema::table('staff', function (Blueprint $table) {
            // Check if index already exists
            $indexes = DB::select("
                SHOW INDEX FROM staff WHERE Key_name = 'idx_staff_calcom_user'
            ");

            if (empty($indexes)) {
                $table->index('calcom_user_id', 'idx_staff_calcom_user');
                \Log::info('Added index: staff.calcom_user_id');
            }
        });

        // 2. service_staff reverse lookup (staff_id, can_book, is_active)
        Schema::table('service_staff', function (Blueprint $table) {
            $indexes = DB::select("
                SHOW INDEX FROM service_staff WHERE Key_name = 'idx_service_staff_reverse'
            ");

            if (empty($indexes)) {
                $table->index(['staff_id', 'can_book', 'is_active'], 'idx_service_staff_reverse');
                \Log::info('Added index: service_staff(staff_id, can_book, is_active)');
            }
        });

        // 3. services branch filtering (company_id, branch_id, is_active)
        Schema::table('services', function (Blueprint $table) {
            $indexes = DB::select("
                SHOW INDEX FROM services WHERE Key_name = 'idx_services_branch_active'
            ");

            if (empty($indexes)) {
                $table->index(['company_id', 'branch_id', 'is_active'], 'idx_services_branch_active');
                \Log::info('Added index: services(company_id, branch_id, is_active)');
            }
        });

        // 4. calls customer-company lookup (company_id, customer_id, created_at)
        Schema::table('calls', function (Blueprint $table) {
            $indexes = DB::select("
                SHOW INDEX FROM calls WHERE Key_name = 'idx_calls_customer_company'
            ");

            if (empty($indexes)) {
                $table->index(['company_id', 'customer_id', 'created_at'], 'idx_calls_customer_company');
                \Log::info('Added index: calls(company_id, customer_id, created_at)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ═══════════════════════════════════════════════════════════
        // ROLLBACK: Remove constraints and indexes
        // ═══════════════════════════════════════════════════════════

        // Drop foreign keys
        Schema::table('services', function (Blueprint $table) {
            $table->dropForeign('fk_services_company');
        });

        Schema::table('calls', function (Blueprint $table) {
            $table->dropForeign('fk_calls_company');
        });

        // Drop indexes
        Schema::table('staff', function (Blueprint $table) {
            $table->dropIndex('idx_staff_calcom_user');
        });

        Schema::table('service_staff', function (Blueprint $table) {
            $table->dropIndex('idx_service_staff_reverse');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex('idx_services_branch_active');
        });

        Schema::table('calls', function (Blueprint $table) {
            $table->dropIndex('idx_calls_customer_company');
        });

        // Revert NOT NULL constraints (make nullable again)
        DB::statement('ALTER TABLE services MODIFY company_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE staff MODIFY company_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE calls MODIFY company_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE branches MODIFY company_id BIGINT UNSIGNED NULL');

        \Log::warning('Priority 1 schema fixes rolled back', [
            'migration' => '2025_10_23_000000_priority1_schema_fixes',
            'timestamp' => now()->toIso8601String()
        ]);
    }
};
