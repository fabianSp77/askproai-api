<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if calls table exists first
        if (!Schema::hasTable('calls')) {
            return;
        }

        // Step 1: Convert duration_ms to duration_sec where needed
        DB::statement('UPDATE calls SET duration_sec = ROUND(duration_ms / 1000) WHERE duration_sec IS NULL AND duration_ms IS NOT NULL');

        // Step 2: Consolidate call_status into status
        DB::statement("UPDATE calls SET status =
            CASE
                WHEN call_status = 'ended' AND status = 'ongoing' THEN 'completed'
                WHEN call_status = 'ended' AND status IS NULL THEN 'completed'
                WHEN call_status = 'failed' THEN 'failed'
                WHEN call_status = 'completed' THEN 'completed'
                WHEN call_status = 'analyzed' THEN 'analyzed'
                WHEN call_status = 'call_analyzed' THEN 'analyzed'
                WHEN call_status = 'ongoing' AND status != 'ongoing' THEN 'ongoing'
                ELSE COALESCE(status, 'completed')
            END
            WHERE call_status IS NOT NULL"
        );

        // Step 3: Add indices for performance (only if they don't exist)
        $existingIndices = DB::select("SHOW INDEX FROM calls");
        $indexNames = array_column($existingIndices, 'Key_name');

        Schema::table('calls', function (Blueprint $table) use ($indexNames) {
            // Add indices only if they don't exist
            if (!in_array('idx_calls_duration', $indexNames)) {
                $table->index('duration_sec', 'idx_calls_duration');
            }
            if (!in_array('idx_calls_cost', $indexNames)) {
                $table->index('cost', 'idx_calls_cost');
            }
            if (!in_array('idx_calls_from_number', $indexNames)) {
                $table->index('from_number', 'idx_calls_from_number');
            }
            if (!in_array('idx_calls_to_number', $indexNames)) {
                $table->index('to_number', 'idx_calls_to_number');
            }
            if (!in_array('idx_calls_status_combo', $indexNames)) {
                $table->index(['status', 'call_status'], 'idx_calls_status_combo');
            }
        });

        // Step 4: Add new columns for cost hierarchy (only if they don't exist)
        if (!Schema::hasColumn('calls', 'timezone')) {
            
        if (!Schema::hasTable('calls')) {
            return;
        }

        Schema::table('calls', function (Blueprint $table) {
                // Add timezone info for proper handling
                $table->string('timezone', 50)->default('Europe/Berlin');
            });
        }

        // Step 5: Update null status values
        DB::statement("UPDATE calls SET status = 'completed' WHERE status IS NULL OR status = ''");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $existingIndices = DB::select("SHOW INDEX FROM calls");
        $indexNames = array_column($existingIndices, 'Key_name');

        Schema::table('calls', function (Blueprint $table) use ($indexNames) {
            // Drop indices if they exist
            if (in_array('idx_calls_duration', $indexNames)) {
                $table->dropIndex('idx_calls_duration');
            }
            if (in_array('idx_calls_cost', $indexNames)) {
                $table->dropIndex('idx_calls_cost');
            }
            if (in_array('idx_calls_from_number', $indexNames)) {
                $table->dropIndex('idx_calls_from_number');
            }
            if (in_array('idx_calls_to_number', $indexNames)) {
                $table->dropIndex('idx_calls_to_number');
            }
            if (in_array('idx_calls_status_combo', $indexNames)) {
                $table->dropIndex('idx_calls_status_combo');
            }
        });

        // Check if columns exist before dropping
        if (Schema::hasColumn('calls', 'timezone')) {
            Schema::table('calls', function (Blueprint $table) {
                $table->dropColumn('timezone');
            });
        }
    }
};