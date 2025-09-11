<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'meeting_url')) {
                $table->string('meeting_url', 500)->nullable()->after('status');
            }
            if (!Schema::hasColumn('appointments', 'calcom_booking_uid')) {
                $table->string('calcom_booking_uid')->nullable()->after('calcom_v2_booking_id');
            }
            if (!Schema::hasColumn('appointments', 'reschedule_uid')) {
                $table->string('reschedule_uid')->nullable()->after('calcom_booking_uid');
            }
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['meeting_url', 'calcom_booking_uid', 'reschedule_uid']);
        });
    }
};