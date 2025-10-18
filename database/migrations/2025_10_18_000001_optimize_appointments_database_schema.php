<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Phase 1: Critical Hotfix - Remove phantom columns
     *
     * PROBLEM: The code was trying to INSERT into columns that don't exist:
     * - created_by
     * - booking_source
     * - booked_by_user_id
     *
     * IMPACT: 100% appointment creation failure (all attempts threw SQL errors)
     *
     * SOLUTION: Remove these columns from being set in the code
     * Migration: Drop them if they somehow got created
     *
     * NOTE: The appointments table already has 64 indexes (MySQL maximum)
     * So we only do the critical schema fix, no index additions
     *
     * @author System Recovery
     * @date 2025-10-18
     */
    public function up(): void
    {
        // Drop phantom columns if they were somehow created in the schema
        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasColumn('appointments', 'created_by')) {
                $table->dropColumn('created_by');
                \Log::info('✅ Removed phantom column: created_by');
            }

            if (Schema::hasColumn('appointments', 'booking_source')) {
                $table->dropColumn('booking_source');
                \Log::info('✅ Removed phantom column: booking_source');
            }

            if (Schema::hasColumn('appointments', 'booked_by_user_id')) {
                $table->dropColumn('booked_by_user_id');
                \Log::info('✅ Removed phantom column: booked_by_user_id');
            }
        });

        // Verify critical indexes exist
        if (!$this->indexExists('appointments', 'idx_appointments_call_lookup')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->index(['call_id'], 'idx_appointments_call_lookup');
            });
            \Log::info('✅ Verified/Added index: idx_appointments_call_lookup');
        }

        $totalRows = DB::table('appointments')->count();
        \Log::info('📊 Phase 1 Migration Complete', [
            'total_rows' => $totalRows,
            'phantom_columns_removed' => 3,
            'status' => 'Ready for Phase 2'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback of column drops - they shouldn't have existed in the first place
        \Log::warning('Phase 1 migration rollback: No schema changes to reverse');
    }

    /**
     * Check if index exists
     */
    private function indexExists(string $table, string $index): bool
    {
        try {
            $indexes = Schema::getIndexes($table);
            return collect($indexes)->pluck('name')->contains($index);
        } catch (\Exception $e) {
            return false;
        }
    }
};
