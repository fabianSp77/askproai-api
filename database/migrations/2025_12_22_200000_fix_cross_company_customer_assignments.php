<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CRITICAL MULTI-TENANCY FIX
 *
 * This migration fixes cross-company customer data leakage that occurred due to
 * unscoped phone number lookups in webhook handlers.
 *
 * Problem: Calls were being linked to customers from different companies because
 * the DeterministicCustomerMatcher had a "cross-company fallback" that would return
 * customers from ANY company if not found in the target company.
 *
 * Solution: This migration identifies and fixes calls that are linked to customers
 * from different companies, setting their customer_id to NULL.
 *
 * @see app/Services/DeterministicCustomerMatcher.php - Cross-company fallback removed
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Identify calls with cross-company customer assignments
        $invalidAssignments = DB::table('calls')
            ->join('customers', 'calls.customer_id', '=', 'customers.id')
            ->whereRaw('calls.company_id != customers.company_id')
            ->whereNotNull('calls.customer_id')
            ->select(
                'calls.id as call_id',
                'calls.company_id as call_company_id',
                'calls.customer_id',
                'customers.company_id as customer_company_id',
                'customers.name as customer_name',
                'calls.from_number'
            )
            ->get();

        if ($invalidAssignments->isEmpty()) {
            Log::info('[Migration] No cross-company customer assignments found - database is clean');
            return;
        }

        // Step 2: Log the invalid assignments for audit trail
        Log::warning('[Migration] Found cross-company customer assignments', [
            'count' => $invalidAssignments->count(),
            'details' => $invalidAssignments->map(function ($assignment) {
                return [
                    'call_id' => $assignment->call_id,
                    'call_company' => $assignment->call_company_id,
                    'customer_company' => $assignment->customer_company_id,
                    'customer_name' => $assignment->customer_name,
                    'from_number' => $assignment->from_number,
                ];
            })->toArray()
        ]);

        // Step 3: Create backup table for recovery if needed
        Schema::create('calls_customer_backup_20251222', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('call_id');
            $table->unsignedBigInteger('original_customer_id');
            $table->unsignedBigInteger('call_company_id');
            $table->unsignedBigInteger('customer_company_id');
            $table->timestamp('backed_up_at');
        });

        // Step 4: Backup the invalid assignments
        foreach ($invalidAssignments as $assignment) {
            DB::table('calls_customer_backup_20251222')->insert([
                'call_id' => $assignment->call_id,
                'original_customer_id' => $assignment->customer_id,
                'call_company_id' => $assignment->call_company_id,
                'customer_company_id' => $assignment->customer_company_id,
                'backed_up_at' => now(),
            ]);
        }

        // Step 5: Fix the invalid assignments by setting customer_id to NULL
        $affectedCount = DB::table('calls')
            ->whereIn('id', $invalidAssignments->pluck('call_id'))
            ->update(['customer_id' => null]);

        Log::info('[Migration] Fixed cross-company customer assignments', [
            'affected_calls' => $affectedCount,
            'backup_table' => 'calls_customer_backup_20251222'
        ]);

        // Step 6: Also check service_cases table if it exists
        if (Schema::hasTable('service_cases') && Schema::hasColumn('service_cases', 'customer_id')) {
            $invalidServiceCases = DB::table('service_cases')
                ->join('customers', 'service_cases.customer_id', '=', 'customers.id')
                ->whereRaw('service_cases.company_id != customers.company_id')
                ->whereNotNull('service_cases.customer_id')
                ->select('service_cases.id')
                ->pluck('id');

            if ($invalidServiceCases->isNotEmpty()) {
                Log::warning('[Migration] Found cross-company service_case assignments', [
                    'count' => $invalidServiceCases->count()
                ]);

                DB::table('service_cases')
                    ->whereIn('id', $invalidServiceCases)
                    ->update(['customer_id' => null]);

                Log::info('[Migration] Fixed cross-company service_case assignments', [
                    'affected_service_cases' => $invalidServiceCases->count()
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore from backup table
        if (Schema::hasTable('calls_customer_backup_20251222')) {
            $backups = DB::table('calls_customer_backup_20251222')->get();

            foreach ($backups as $backup) {
                DB::table('calls')
                    ->where('id', $backup->call_id)
                    ->update(['customer_id' => $backup->original_customer_id]);
            }

            Schema::dropIfExists('calls_customer_backup_20251222');

            Log::info('[Migration Rollback] Restored cross-company customer assignments from backup');
        }
    }
};
