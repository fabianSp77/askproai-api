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
     * Backfill calcom_booking_id from metadata JSON where it exists.
     * This fixes appointments where booking ID was stored in metadata but not in the column.
     */
    public function up(): void
    {
        // Skip in testing environment (SQLite doesn't support JSON functions)
        if (app()->environment('testing')) {
            return;
        }

        // Count records that will be updated
        $countToUpdate = DB::table('appointments')
            ->whereNull('calcom_booking_id')
            ->whereNotNull('metadata')
            ->whereRaw("JSON_EXTRACT(metadata, '$.calcom_booking_id') IS NOT NULL")
            ->whereRaw("JSON_EXTRACT(metadata, '$.calcom_booking_id') != 'null'")
            ->count();

        // Backfill calcom_booking_id from metadata where:
        // 1. calcom_booking_id column is NULL
        // 2. metadata contains calcom_booking_id
        DB::statement("
            UPDATE appointments
            SET calcom_booking_id = CAST(JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.calcom_booking_id')) AS UNSIGNED)
            WHERE calcom_booking_id IS NULL
              AND metadata IS NOT NULL
              AND JSON_EXTRACT(metadata, '$.calcom_booking_id') IS NOT NULL
              AND JSON_EXTRACT(metadata, '$.calcom_booking_id') != 'null'
        ");

        // Log specific appointment 632 fix (the one from root cause analysis)
        $appointment632 = DB::table('appointments')->where('id', 632)->first();

        \Log::info('✅ Migration completed: backfill_calcom_booking_id_from_metadata', [
            'total_updated' => $countToUpdate,
            'appointment_632_fixed' => $appointment632 ? [
                'id' => $appointment632->id,
                'calcom_booking_id' => $appointment632->calcom_booking_id,
                'metadata_has_id' => !is_null($appointment632->metadata) &&
                                     str_contains($appointment632->metadata, 'calcom_booking_id')
            ] : 'not_found'
        ]);

        // Verify critical appointment 632 was fixed
        if ($appointment632 && is_null($appointment632->calcom_booking_id)) {
            \Log::warning('⚠️ Appointment 632 NOT fixed by migration - manual intervention needed', [
                'id' => $appointment632->id,
                'metadata' => $appointment632->metadata
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * WARNING: This will set calcom_booking_id back to NULL for backfilled records.
     * Only use this if you need to rollback the migration.
     */
    public function down(): void
    {
        // We cannot safely rollback this migration since we don't know which records
        // had NULL before and which had values. Log a warning instead.
        \Log::warning('⚠️ Cannot safely rollback backfill_calcom_booking_id_from_metadata migration', [
            'reason' => 'No way to distinguish original NULLs from backfilled values',
            'recommendation' => 'If rollback is needed, restore from database backup'
        ]);

        // Don't actually rollback - data loss risk is too high
    }
};
