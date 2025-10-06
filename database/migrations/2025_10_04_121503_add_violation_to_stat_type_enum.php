<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix for PolicyChartsWidget 500 errors
     *
     * Root Cause: PolicyChartsWidget queries for stat_type = 'violation' but the enum
     * only contains: 'cancel_30d', 'reschedule_30d', 'cancel_90d', 'reschedule_90d'
     *
     * This adds 'violation' to the stat_type enum.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE appointment_modification_stats
            MODIFY COLUMN stat_type ENUM(
                'cancel_30d',
                'reschedule_30d',
                'cancel_90d',
                'reschedule_90d',
                'violation'
            ) NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First, remove any 'violation' records to avoid constraint violations
        DB::table('appointment_modification_stats')
            ->where('stat_type', 'violation')
            ->delete();

        // Restore original enum without 'violation'
        DB::statement("
            ALTER TABLE appointment_modification_stats
            MODIFY COLUMN stat_type ENUM(
                'cancel_30d',
                'reschedule_30d',
                'cancel_90d',
                'reschedule_90d'
            ) NOT NULL
        ");
    }
};
