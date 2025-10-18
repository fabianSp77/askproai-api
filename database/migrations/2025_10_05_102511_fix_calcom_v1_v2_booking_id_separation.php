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
     * Fix Cal.com V1 (numeric) IDs incorrectly stored in calcom_v2_booking_id (varchar) column.
     *
     * Issue: Cal.com V1 uses numeric IDs (bigint), V2 uses alphanumeric UIDs (varchar).
     * Many appointments have V1 numeric IDs stored in the V2 column, which should be separated.
     *
     * Strategy:
     * 1. Create backup column for rollback capability
     * 2. Move numeric IDs from calcom_v2_booking_id → calcom_booking_id
     * 3. Clear V2 column for records that had V1 IDs
     * 4. Preserve records that have both V1 and V2 IDs (shouldn't happen but handle safely)
     * 5. Validate all changes
     */
    public function up(): void
    {
        try {
            // Step 1: Add temporary backup column for rollback capability
            if (!Schema::hasColumn('appointments', '_migration_backup_v2_id')) {
                Schema::table('appointments', function (Blueprint $table) {
                    $table->string('_migration_backup_v2_id', 255)->nullable()->after('calcom_v2_booking_id');
                });
            }

            // Step 2: Backup current calcom_v2_booking_id values
            DB::statement("
                UPDATE appointments
                SET _migration_backup_v2_id = calcom_v2_booking_id
                WHERE calcom_v2_booking_id IS NOT NULL
            ");

            // Step 3: Count records that will be migrated
            $stats = DB::selectOne("
                SELECT
                    COUNT(*) as total_records,
                    SUM(CASE WHEN calcom_v2_booking_id IS NOT NULL AND calcom_v2_booking_id REGEXP '^[0-9]+$' THEN 1 ELSE 0 END) as v1_in_v2_column,
                    SUM(CASE WHEN calcom_v2_booking_id IS NOT NULL AND calcom_v2_booking_id NOT REGEXP '^[0-9]+$' THEN 1 ELSE 0 END) as proper_v2_ids,
                    SUM(CASE WHEN calcom_booking_id IS NOT NULL AND calcom_v2_booking_id IS NOT NULL THEN 1 ELSE 0 END) as has_both_ids,
                    SUM(CASE WHEN calcom_booking_id IS NULL AND calcom_v2_booking_id IS NULL THEN 1 ELSE 0 END) as no_calcom_ids
                FROM appointments
                WHERE deleted_at IS NULL
            ");

            \Log::info('Migration started: fix_calcom_v1_v2_booking_id_separation', [
                'pre_migration_stats' => $stats
            ]);

            // Step 4: Move V1 numeric IDs from calcom_v2_booking_id to calcom_booking_id
            // Only migrate if calcom_booking_id is NULL to avoid overwriting existing V1 IDs
            $migratedCount = DB::statement("
                UPDATE appointments
                SET
                    calcom_booking_id = CAST(calcom_v2_booking_id AS UNSIGNED),
                    calcom_v2_booking_id = NULL
                WHERE calcom_booking_id IS NULL
                  AND calcom_v2_booking_id IS NOT NULL
                  AND calcom_v2_booking_id REGEXP '^[0-9]+$'
                  AND deleted_at IS NULL
            ");

            // Step 5: Handle edge case - records with both V1 and V2 IDs
            // For these, check if V2 column contains a numeric ID that differs from V1 column
            $conflictingRecords = DB::select("
                SELECT
                    id,
                    calcom_booking_id,
                    calcom_v2_booking_id,
                    _migration_backup_v2_id
                FROM appointments
                WHERE calcom_booking_id IS NOT NULL
                  AND calcom_v2_booking_id IS NOT NULL
                  AND calcom_v2_booking_id REGEXP '^[0-9]+$'
                  AND CAST(calcom_v2_booking_id AS UNSIGNED) != calcom_booking_id
                  AND deleted_at IS NULL
            ");

            if (!empty($conflictingRecords)) {
                \Log::warning('Found appointments with conflicting V1 IDs in both columns', [
                    'count' => count($conflictingRecords),
                    'records' => $conflictingRecords,
                    'action' => 'Preserving calcom_booking_id, clearing calcom_v2_booking_id'
                ]);

                // Resolve conflicts by clearing the V2 column (keep the V1 column as source of truth)
                DB::statement("
                    UPDATE appointments
                    SET calcom_v2_booking_id = NULL
                    WHERE calcom_booking_id IS NOT NULL
                      AND calcom_v2_booking_id IS NOT NULL
                      AND calcom_v2_booking_id REGEXP '^[0-9]+$'
                      AND CAST(calcom_v2_booking_id AS UNSIGNED) != calcom_booking_id
                      AND deleted_at IS NULL
                ");
            }

            // Step 6: Post-migration validation
            $postStats = DB::selectOne("
                SELECT
                    COUNT(*) as total_records,
                    SUM(CASE WHEN calcom_booking_id IS NOT NULL THEN 1 ELSE 0 END) as has_v1_id,
                    SUM(CASE WHEN calcom_v2_booking_id IS NOT NULL THEN 1 ELSE 0 END) as has_v2_id,
                    SUM(CASE WHEN calcom_v2_booking_id IS NOT NULL AND calcom_v2_booking_id REGEXP '^[0-9]+$' THEN 1 ELSE 0 END) as v1_still_in_v2,
                    SUM(CASE WHEN calcom_booking_id IS NOT NULL AND calcom_v2_booking_id IS NOT NULL THEN 1 ELSE 0 END) as has_both_ids
                FROM appointments
                WHERE deleted_at IS NULL
            ");

            // Step 7: Verify migration success
            if ($postStats->v1_still_in_v2 > 0) {
                throw new \Exception("Migration validation failed: Still found {$postStats->v1_still_in_v2} V1 IDs in V2 column");
            }

            \Log::info('Migration completed successfully: fix_calcom_v1_v2_booking_id_separation', [
                'pre_migration_stats' => $stats,
                'post_migration_stats' => $postStats,
                'migrated_records' => $stats->v1_in_v2_column,
                'conflicting_records' => count($conflictingRecords)
            ]);

        } catch (\Exception $e) {
            \Log::error('Migration failed: fix_calcom_v1_v2_booking_id_separation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     *
     * Restore original values from backup column.
     * This is a safe rollback that restores the exact pre-migration state.
     */
    public function down(): void
    {
        DB::beginTransaction();

        try {
            // Restore original calcom_v2_booking_id values from backup
            DB::statement("
                UPDATE appointments
                SET calcom_v2_booking_id = _migration_backup_v2_id
                WHERE _migration_backup_v2_id IS NOT NULL
            ");

            // Optional: Clear calcom_booking_id values that were migrated
            // This is conservative - only clear if the backup shows they came from V2 column
            DB::statement("
                UPDATE appointments
                SET calcom_booking_id = NULL
                WHERE _migration_backup_v2_id IS NOT NULL
                  AND _migration_backup_v2_id REGEXP '^[0-9]+$'
                  AND calcom_booking_id = CAST(_migration_backup_v2_id AS UNSIGNED)
            ");

            \Log::info('Migration rolled back: fix_calcom_v1_v2_booking_id_separation', [
                'action' => 'Restored original values from _migration_backup_v2_id'
            ]);

            // Drop backup column
            if (Schema::hasColumn('appointments', '_migration_backup_v2_id')) {
                Schema::table('appointments', function (Blueprint $table) {
                    $table->dropColumn('_migration_backup_v2_id');
                });
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Migration rollback failed: fix_calcom_v1_v2_booking_id_separation', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
};
