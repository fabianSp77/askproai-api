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
     * Cleanup migration: Remove the temporary backup column after verification.
     *
     * IMPORTANT: Only run this migration AFTER you have:
     * 1. Run the fix_calcom_v1_v2_booking_id_separation migration
     * 2. Verified the migration worked correctly using validation_queries.sql
     * 3. Confirmed you don't need to rollback
     *
     * This migration should be run 24-48 hours after the main migration
     * to allow time for verification and potential rollback.
     */
    public function up(): void
    {
        // Safety check: Verify the migration was successful before cleanup
        $validationCheck = DB::selectOne("
            SELECT
                COUNT(*) as total_backup,
                SUM(CASE WHEN calcom_v2_booking_id IS NOT NULL AND calcom_v2_booking_id REGEXP '^[0-9]+$' THEN 1 ELSE 0 END) as v1_still_in_v2
            FROM appointments
            WHERE _migration_backup_v2_id IS NOT NULL
        ");

        if ($validationCheck && $validationCheck->v1_still_in_v2 > 0) {
            throw new \Exception(
                "Cannot cleanup: Found {$validationCheck->v1_still_in_v2} V1 IDs still in V2 column. " .
                "Migration may have failed or been rolled back. Please investigate before running cleanup."
            );
        }

        \Log::info('Cleanup migration started: cleanup_calcom_booking_id_migration_backup', [
            'backup_records' => $validationCheck->total_backup ?? 0
        ]);

        // Drop the temporary backup column
        if (Schema::hasColumn('appointments', '_migration_backup_v2_id')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->dropColumn('_migration_backup_v2_id');
            });

            \Log::info('Cleanup migration completed: Dropped _migration_backup_v2_id column', [
                'message' => 'Backup column removed successfully. Migration is now permanent.'
            ]);
        } else {
            \Log::warning('Cleanup migration: Backup column does not exist', [
                'message' => 'Column may have already been dropped or migration never ran'
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * WARNING: Cannot recreate backup data once dropped.
     * This will recreate the column but it will be empty.
     */
    public function down(): void
    {
        \Log::warning('Cleanup migration rollback: Cannot restore backup data', [
            'message' => 'Recreating empty backup column. Original data is permanently lost.'
        ]);

        if (!Schema::hasColumn('appointments', '_migration_backup_v2_id')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->string('_migration_backup_v2_id', 255)->nullable()->after('calcom_v2_booking_id');
            });
        }
    }
};
