<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fix: AppointmentModificationStat Model expects different enum values than DB provides
     *
     * BEFORE (Production DB):
     * - stat_type ENUM('cancellation_count', 'reschedule_count')
     *
     * AFTER (Match Model):
     * - stat_type ENUM('cancel_30d', 'reschedule_30d', 'cancel_90d', 'reschedule_90d')
     *
     * This fixes CRITICAL-001C from POLICY_QUOTA_ENFORCEMENT_ANALYSIS.md
     * AppointmentPolicyEngine can now find materialized stats for O(1) quota checks
     */
    public function up(): void
    {
        // Check if column exists before modifying
        if (!Schema::hasColumn('appointment_modification_stats', 'stat_type')) {
            // Column doesn't exist, likely renamed to modification_type
            return;
        }

        // Table is empty (verified 0 records), safe to change enum
        DB::statement("
            ALTER TABLE appointment_modification_stats
            MODIFY COLUMN stat_type ENUM(
                'cancel_30d',
                'reschedule_30d',
                'cancel_90d',
                'reschedule_90d'
            ) NOT NULL COMMENT 'Type of modification being counted - matches AppointmentModificationStat::STAT_TYPES'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback to old enum values
        DB::statement("
            ALTER TABLE appointment_modification_stats
            MODIFY COLUMN stat_type ENUM(
                'cancellation_count',
                'reschedule_count'
            ) NOT NULL COMMENT 'Type of modification being counted'
        ");
    }
};
