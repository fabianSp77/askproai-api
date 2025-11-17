<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 🔧 FIX 2025-11-17: Add calcom_v2_booking_uid column
     *
     * Cal.com V2 API returns TWO identifiers:
     * - id: 12848667 (numeric ID) - stored in calcom_v2_booking_id
     * - uid: "cvaRt5SvAUZG9W8up7eKz1" (string UID) - needed for cancellation
     *
     * The cancel endpoint REQUIRES the UID, not the ID.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('calcom_v2_booking_uid', 50)->nullable()->after('calcom_v2_booking_id');
            $table->index('calcom_v2_booking_uid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['calcom_v2_booking_uid']);
            $table->dropColumn('calcom_v2_booking_uid');
        });
    }
};
