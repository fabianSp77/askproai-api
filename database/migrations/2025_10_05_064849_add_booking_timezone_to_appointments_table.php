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
     * Add booking_timezone column to preserve original timezone for appointments
     * created via Cal.com or other booking systems.
     */
    public function up(): void
    {
        // Add booking_timezone column
        if (!Schema::hasColumn('appointments', 'booking_timezone')) {
            Schema::table('appointments', function (Blueprint $table) {
                // Add after metadata if it exists, otherwise just add it
                if (Schema::hasColumn('appointments', 'metadata')) {
                    $table->string('booking_timezone', 50)->default('Europe/Berlin')->after('metadata');
                } else {
                    $table->string('booking_timezone', 50)->default('Europe/Berlin');
                }
            });
        }

        // Add calcom_booking_id column if it doesn't exist
        if (!Schema::hasColumn('appointments', 'calcom_booking_id')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->unsignedBigInteger('calcom_booking_id')->nullable()->after('booking_id');
            });
        }

        // Add index for calcom_booking_id (use try-catch in case it exists)
        try {
            Schema::table('appointments', function (Blueprint $table) {
                $table->index('calcom_booking_id');
            });
        } catch (\Exception $e) {
            // Index probably already exists, ignore
            \Log::debug('Index calcom_booking_id might already exist', ['error' => $e->getMessage()]);
        }

        // Backfill booking_timezone from metadata where available
        DB::statement("
            UPDATE appointments
            SET booking_timezone = JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.booking_timezone'))
            WHERE metadata IS NOT NULL
              AND JSON_EXTRACT(metadata, '$.booking_timezone') IS NOT NULL
        ");

        // Log migration completion
        \Log::info('✅ Migration completed: add_booking_timezone_to_appointments_table', [
            'updated_count' => DB::table('appointments')
                ->whereNotNull('metadata')
                ->whereRaw("JSON_EXTRACT(metadata, '$.booking_timezone') IS NOT NULL")
                ->count()
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Try to drop index (might not exist)
        try {
            Schema::table('appointments', function (Blueprint $table) {
                $table->dropIndex(['calcom_booking_id']);
            });
        } catch (\Exception $e) {
            \Log::debug('Index calcom_booking_id might not exist', ['error' => $e->getMessage()]);
        }

        // Drop booking_timezone column if it exists
        if (Schema::hasColumn('appointments', 'booking_timezone')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->dropColumn('booking_timezone');
            });
        }
    }
};
