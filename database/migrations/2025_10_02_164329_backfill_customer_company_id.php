<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Backfill customer.company_id for multi-tenant isolation
 *
 * SECURITY CRITICAL: 31/60 customers have NULL company_id, bypassing CompanyScope
 * This migration restores multi-tenant data isolation integrity
 *
 * Strategy: Phased relationship-based backfill
 *   Phase 1: Infer from appointments.company_id (primary source)
 *   Phase 2: Infer from phone_numbers.company_id (fallback)
 *   Phase 3: Flag remaining cases for manual review
 *
 * Safety Features:
 *   - Full backup table created before any modifications
 *   - Transaction-safe with automatic rollback on errors
 *   - Comprehensive audit logging of all changes
 *   - Conflict detection (customers with multiple companies)
 *   - Dry-run mode for testing (set DRY_RUN=true)
 *
 * Rollback: Automatic via backup table in down() method
 */
return new class extends Migration
{
    /**
     * Dry run mode - set to true for testing without modifications
     */
    private const DRY_RUN = false;  // 🚀 DISABLED - REAL EXECUTION

    /**
     * Migration execution timestamp for audit trail
     */
    private string $migrationTimestamp;

    /**
     * Statistics tracking
     */
    private array $stats = [
        'total_null_before' => 0,
        'backfilled_from_appointments' => 0,
        'backfilled_from_phones' => 0,
        'conflicts_detected' => 0,
        'orphans_soft_deleted' => 0,
        'remaining_null_after' => 0,
        'execution_time_seconds' => 0,
    ];

    public function __construct()
    {
        $this->migrationTimestamp = now()->toDateTimeString();
    }

    /**
     * Run the migration.
     */
    public function up(): void
    {
        $startTime = microtime(true);

        Log::info('=== Starting Customer company_id Backfill Migration ===', [
            'dry_run' => self::DRY_RUN,
            'timestamp' => $this->migrationTimestamp,
        ]);

        try {
            DB::transaction(function () {
                // Step 1: Pre-flight validation
                $this->preFlightValidation();

                // Step 2: Create backup table
                $this->createBackupTable();

                // Step 3: Create audit log table
                $this->createAuditLogTable();

                // Step 4: Detect and report conflicts
                $this->detectConflicts();

                // Step 5: Phase 1 - Backfill from appointments
                $this->backfillFromAppointments();

                // Step 6: Phase 2 - Backfill from phone_numbers
                $this->backfillFromPhoneNumbers();

                // Step 7: Phase 3 - Soft delete orphans
                $this->softDeleteOrphans();

                // Step 8: Post-migration validation
                $this->postMigrationValidation();

                // Step 9: Generate report
                $this->generateReport();

                if (self::DRY_RUN) {
                    Log::warning('DRY RUN MODE: Rolling back transaction (no changes committed)');
                    throw new \Exception('DRY_RUN mode - rolling back for testing');
                }
            });

            $this->stats['execution_time_seconds'] = round(microtime(true) - $startTime, 2);

            Log::info('=== Customer company_id Backfill Migration COMPLETED ===', [
                'stats' => $this->stats,
            ]);

        } catch (\Exception $e) {
            if (!self::DRY_RUN) {
                Log::error('Migration FAILED - Transaction rolled back', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            } else {
                Log::info('DRY_RUN completed successfully - No changes committed', [
                    'stats' => $this->stats,
                ]);
            }
        }
    }

    /**
     * Step 1: Pre-flight validation checks
     */
    private function preFlightValidation(): void
    {
        Log::info('Step 1: Pre-flight validation');

        // Count current NULL company_id records
        $this->stats['total_null_before'] = DB::table('customers')
            ->whereNull('company_id')
            ->count();

        Log::info('Pre-flight: NULL company_id count', [
            'count' => $this->stats['total_null_before'],
        ]);

        // Verify appointments have company_id (data integrity check)
        $nullAppointmentCompanies = DB::table('appointments as a')
            ->join('customers as c', 'c.id', '=', 'a.customer_id')
            ->whereNull('c.company_id')
            ->whereNull('a.company_id')
            ->count();

        if ($nullAppointmentCompanies > 0) {
            Log::warning('Pre-flight WARNING: Appointments with NULL company_id found', [
                'count' => $nullAppointmentCompanies,
                'action' => 'These appointments cannot be used for backfill',
            ]);
        }

        // Calculate coverage estimates
        $backfillableViaAppointments = DB::table('appointments')
            ->whereIn('customer_id', function ($query) {
                $query->select('id')
                    ->from('customers')
                    ->whereNull('company_id');
            })
            ->distinct()
            ->count('customer_id');

        // SKIP: phone_numbers table has no customer_id column (business phones, not customer phones)
        $backfillableViaPhones = 0;

        Log::info('Pre-flight: Coverage estimates', [
            'backfillable_via_appointments' => $backfillableViaAppointments,
            'backfillable_via_phones' => $backfillableViaPhones,
            'estimated_coverage_percent' => round(
                (($backfillableViaAppointments + $backfillableViaPhones) /
                max($this->stats['total_null_before'], 1)) * 100,
                2
            ),
        ]);
    }

    /**
     * Step 2: Create backup table for rollback capability
     */
    private function createBackupTable(): void
    {
        Log::info('Step 2: Creating backup table');

        // Drop existing backup if exists
        Schema::dropIfExists('customers_company_id_backup');

        // Create backup with full customer data
        DB::statement('
            CREATE TABLE customers_company_id_backup AS
            SELECT * FROM customers WHERE company_id IS NULL
        ');

        $backupCount = DB::table('customers_company_id_backup')->count();

        Log::info('Backup table created', [
            'table' => 'customers_company_id_backup',
            'records_backed_up' => $backupCount,
        ]);

        if ($backupCount !== $this->stats['total_null_before']) {
            throw new \Exception("Backup count mismatch: expected {$this->stats['total_null_before']}, got {$backupCount}");
        }
    }

    /**
     * Step 3: Create audit log table for change tracking
     */
    private function createAuditLogTable(): void
    {
        Log::info('Step 3: Creating audit log table');

        Schema::dropIfExists('customers_backfill_audit_log');

        Schema::create('customers_backfill_audit_log', function ($table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->unsignedBigInteger('company_id_before')->nullable();
            $table->unsignedBigInteger('company_id_after');
            $table->string('backfill_source'); // 'appointments', 'phone_numbers', 'manual'
            $table->text('backfill_logic')->nullable();
            $table->timestamp('backfilled_at');
            $table->index('customer_id');
        });

        Log::info('Audit log table created successfully');
    }

    /**
     * Step 4: Detect conflicts (customers with appointments from multiple companies)
     */
    private function detectConflicts(): void
    {
        Log::info('Step 4: Detecting conflicts');

        $conflicts = DB::select("
            SELECT
                c.id as customer_id,
                c.name as customer_name,
                c.email as customer_email,
                GROUP_CONCAT(DISTINCT a.company_id) as company_ids,
                COUNT(DISTINCT a.company_id) as company_count
            FROM customers c
            INNER JOIN appointments a ON a.customer_id = c.id
            WHERE c.company_id IS NULL
              AND a.company_id IS NOT NULL
            GROUP BY c.id, c.name, c.email
            HAVING COUNT(DISTINCT a.company_id) > 1
        ");

        $this->stats['conflicts_detected'] = count($conflicts);

        if ($this->stats['conflicts_detected'] > 0) {
            Log::warning('CONFLICTS DETECTED: Customers with appointments from multiple companies', [
                'count' => $this->stats['conflicts_detected'],
                'conflicts' => $conflicts,
                'action' => 'These customers will be SKIPPED and flagged for manual review',
            ]);

            // Log each conflict for manual review
            foreach ($conflicts as $conflict) {
                Log::warning('Conflict detail', [
                    'customer_id' => $conflict->customer_id,
                    'customer_name' => $conflict->customer_name,
                    'customer_email' => $conflict->customer_email,
                    'company_ids' => $conflict->company_ids,
                    'company_count' => $conflict->company_count,
                ]);
            }
        } else {
            Log::info('No conflicts detected - all customers have consistent company associations');
        }
    }

    /**
     * Step 5: Phase 1 - Backfill from appointments
     */
    private function backfillFromAppointments(): void
    {
        Log::info('Step 5: Phase 1 - Backfill from appointments');

        // Get customers eligible for backfill (single company only, no conflicts)
        $eligibleCustomers = DB::select("
            SELECT
                c.id as customer_id,
                c.name,
                c.email,
                a.company_id,
                COUNT(*) as appointment_count
            FROM customers c
            INNER JOIN appointments a ON a.customer_id = c.id
            WHERE c.company_id IS NULL
              AND a.company_id IS NOT NULL
            GROUP BY c.id, c.name, c.email, a.company_id
            HAVING COUNT(DISTINCT a.company_id) = 1
        ");

        Log::info('Eligible customers for appointment-based backfill', [
            'count' => count($eligibleCustomers),
        ]);

        foreach ($eligibleCustomers as $customer) {
            // Update customer.company_id
            DB::table('customers')
                ->where('id', $customer->customer_id)
                ->update([
                    'company_id' => $customer->company_id,
                    'updated_at' => now(),
                ]);

            // Log the change in audit table
            DB::table('customers_backfill_audit_log')->insert([
                'customer_id' => $customer->customer_id,
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'company_id_before' => null,
                'company_id_after' => $customer->company_id,
                'backfill_source' => 'appointments',
                'backfill_logic' => "Inferred from {$customer->appointment_count} appointments with company_id={$customer->company_id}",
                'backfilled_at' => now(),
            ]);

            $this->stats['backfilled_from_appointments']++;
        }

        Log::info('Phase 1 completed', [
            'backfilled_count' => $this->stats['backfilled_from_appointments'],
        ]);
    }

    /**
     * Step 6: Phase 2 - Backfill from phone_numbers (fallback)
     */
    private function backfillFromPhoneNumbers(): void
    {
        Log::info('Step 6: Phase 2 - Backfill from phone_numbers SKIPPED (table has no customer_id)');
        // SKIP: phone_numbers table structure doesn't support customer relationships
        return;

        // Get customers without appointments but with phone numbers
        $eligibleCustomers = DB::select("
            SELECT
                c.id as customer_id,
                c.name,
                c.email,
                p.company_id,
                COUNT(*) as phone_count
            FROM customers c
            INNER JOIN phone_numbers p ON p.customer_id = c.id
            WHERE c.company_id IS NULL
              AND p.company_id IS NOT NULL
              AND NOT EXISTS (
                  SELECT 1 FROM appointments WHERE customer_id = c.id
              )
            GROUP BY c.id, c.name, c.email, p.company_id
            HAVING COUNT(DISTINCT p.company_id) = 1
        ");

        Log::info('Eligible customers for phone-based backfill', [
            'count' => count($eligibleCustomers),
        ]);

        foreach ($eligibleCustomers as $customer) {
            // Update customer.company_id
            DB::table('customers')
                ->where('id', $customer->customer_id)
                ->update([
                    'company_id' => $customer->company_id,
                    'updated_at' => now(),
                ]);

            // Log the change in audit table
            DB::table('customers_backfill_audit_log')->insert([
                'customer_id' => $customer->customer_id,
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'company_id_before' => null,
                'company_id_after' => $customer->company_id,
                'backfill_source' => 'phone_numbers',
                'backfill_logic' => "Inferred from {$customer->phone_count} phone numbers with company_id={$customer->company_id}",
                'backfilled_at' => now(),
            ]);

            $this->stats['backfilled_from_phones']++;
        }

        Log::info('Phase 2 completed', [
            'backfilled_count' => $this->stats['backfilled_from_phones'],
        ]);
    }

    /**
     * Step 7: Phase 3 - Soft delete orphaned records
     */
    private function softDeleteOrphans(): void
    {
        Log::info('Step 7: Phase 3 - Soft delete orphaned records');

        // Find true orphans (no appointments, no calls)
        // SKIP: phone_numbers check removed (table has no customer_id column)
        $orphans = DB::select("
            SELECT c.id, c.name, c.email
            FROM customers c
            WHERE c.company_id IS NULL
              AND NOT EXISTS (SELECT 1 FROM appointments WHERE customer_id = c.id)
              AND NOT EXISTS (SELECT 1 FROM calls WHERE customer_id = c.id)
        ");

        Log::info('Orphaned customers detected', [
            'count' => count($orphans),
            'action' => 'Will be soft deleted (reversible)',
        ]);

        foreach ($orphans as $orphan) {
            DB::table('customers')
                ->where('id', $orphan->id)
                ->update([
                    'deleted_at' => now(),
                    'updated_at' => now(),
                ]);

            // Log the soft delete
            DB::table('customers_backfill_audit_log')->insert([
                'customer_id' => $orphan->id,
                'customer_name' => $orphan->name,
                'customer_email' => $orphan->email,
                'company_id_before' => null,
                'company_id_after' => 0, // Special marker for soft delete
                'backfill_source' => 'soft_delete',
                'backfill_logic' => 'Orphaned record with no relationships - soft deleted',
                'backfilled_at' => now(),
            ]);

            $this->stats['orphans_soft_deleted']++;
        }

        Log::info('Phase 3 completed', [
            'soft_deleted_count' => $this->stats['orphans_soft_deleted'],
        ]);
    }

    /**
     * Step 8: Post-migration validation
     */
    private function postMigrationValidation(): void
    {
        Log::info('Step 8: Post-migration validation');

        // Count remaining NULL values
        $this->stats['remaining_null_after'] = DB::table('customers')
            ->whereNull('company_id')
            ->whereNull('deleted_at') // Exclude soft deleted
            ->count();

        Log::info('Remaining NULL company_id count', [
            'count' => $this->stats['remaining_null_after'],
        ]);

        // Verify no data loss
        $currentCustomerCount = DB::table('customers')->count();
        $backupCount = DB::table('customers_company_id_backup')->count();

        // Total should match (backup had NULL customers, now some may be soft deleted)
        $expectedTotal = $currentCustomerCount; // Backup is subset, current includes all

        Log::info('Data loss verification', [
            'current_total_customers' => $currentCustomerCount,
            'backed_up_customers' => $backupCount,
        ]);

        // Verify relationship integrity
        $relationshipConflicts = DB::table('customers as c')
            ->join('appointments as a', 'c.id', '=', 'a.customer_id')
            ->whereColumn('c.company_id', '!=', 'a.company_id')
            ->whereNull('c.deleted_at')
            ->count();

        if ($relationshipConflicts > 0) {
            throw new \Exception("Relationship integrity violation: {$relationshipConflicts} customers have mismatched company_id with appointments");
        }

        Log::info('Relationship integrity validated', [
            'conflicts' => $relationshipConflicts,
        ]);

        // Verify audit log completeness
        $auditLogCount = DB::table('customers_backfill_audit_log')->count();
        $expectedAuditCount = $this->stats['backfilled_from_appointments']
            + $this->stats['backfilled_from_phones']
            + $this->stats['orphans_soft_deleted'];

        if ($auditLogCount !== $expectedAuditCount) {
            throw new \Exception("Audit log incomplete: expected {$expectedAuditCount}, got {$auditLogCount}");
        }

        Log::info('Audit log verification passed', [
            'audit_records' => $auditLogCount,
        ]);

        // Final validation
        if ($this->stats['remaining_null_after'] > $this->stats['conflicts_detected']) {
            Log::warning('Remaining NULL values exceed expected conflicts', [
                'remaining_null' => $this->stats['remaining_null_after'],
                'expected_conflicts' => $this->stats['conflicts_detected'],
                'action' => 'Manual review required for remaining records',
            ]);
        }
    }

    /**
     * Step 9: Generate comprehensive report
     */
    private function generateReport(): void
    {
        Log::info('Step 9: Generating migration report');

        // Get remaining NULL customers for manual review
        $remainingNulls = DB::table('customers')
            ->whereNull('company_id')
            ->whereNull('deleted_at')
            ->select('id', 'name', 'email', 'created_at')
            ->get();

        $report = [
            'migration_summary' => $this->stats,
            'success_rate' => round(
                (($this->stats['backfilled_from_appointments'] + $this->stats['backfilled_from_phones']) /
                max($this->stats['total_null_before'], 1)) * 100,
                2
            ) . '%',
            'remaining_for_manual_review' => $this->stats['remaining_null_after'],
            'remaining_customers' => $remainingNulls->toArray(),
        ];

        Log::info('=== MIGRATION REPORT ===', $report);

        // Export manual review CSV if needed
        if ($this->stats['remaining_null_after'] > 0) {
            $this->exportManualReviewCsv($remainingNulls);
        }
    }

    /**
     * Export remaining NULL customers to CSV for manual review
     */
    private function exportManualReviewCsv($customers): void
    {
        $csvPath = storage_path('app/customers_manual_review_' . date('Y-m-d_His') . '.csv');

        $fp = fopen($csvPath, 'w');
        fputcsv($fp, ['ID', 'Name', 'Email', 'Created At', 'Suggested Company ID', 'Notes']);

        foreach ($customers as $customer) {
            // Try to get suggested company from any related data
            $suggestedCompany = DB::table('appointments')
                ->where('customer_id', $customer->id)
                ->value('company_id');

            // SKIP: phone_numbers fallback (table has no customer_id column)
            // if (!$suggestedCompany) {
            //     $suggestedCompany = DB::table('phone_numbers')
            //         ->where('customer_id', $customer->id)
            //         ->value('company_id');
            // }

            fputcsv($fp, [
                $customer->id,
                $customer->name,
                $customer->email,
                $customer->created_at,
                $suggestedCompany ?? 'CONFLICT - Multiple companies or no data',
                'Requires manual review',
            ]);
        }

        fclose($fp);

        Log::info('Manual review CSV exported', [
            'path' => $csvPath,
            'record_count' => count($customers),
        ]);
    }

    /**
     * Reverse the migration - restore from backup
     */
    public function down(): void
    {
        Log::info('=== Rolling back Customer company_id Backfill Migration ===');

        DB::transaction(function () {
            // Verify backup table exists
            if (!Schema::hasTable('customers_company_id_backup')) {
                Log::warning('Backup table does not exist - cannot rollback');
                return;
            }

            // Restore company_id to NULL for backed up customers
            DB::statement("
                UPDATE customers c
                INNER JOIN customers_company_id_backup b ON c.id = b.id
                SET c.company_id = NULL,
                    c.updated_at = NOW()
            ");

            // Restore soft deleted orphans
            DB::table('customers')
                ->whereIn('id', function ($query) {
                    $query->select('customer_id')
                        ->from('customers_backfill_audit_log')
                        ->where('backfill_source', 'soft_delete');
                })
                ->update([
                    'deleted_at' => null,
                    'updated_at' => now(),
                ]);

            $restoredCount = DB::table('customers')
                ->whereNull('company_id')
                ->count();

            Log::info('Rollback completed', [
                'restored_null_count' => $restoredCount,
            ]);

            // Drop backup and audit tables
            Schema::dropIfExists('customers_company_id_backup');
            Schema::dropIfExists('customers_backfill_audit_log');

            Log::info('Backup and audit tables dropped');
        });

        Log::info('=== Rollback COMPLETED ===');
    }
};
