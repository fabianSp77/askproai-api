<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Phase 2: Staff Assignment - Add Cal.com Host ID to Appointments
     * Stores the Cal.com host ID that was assigned to each appointment
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->integer('calcom_host_id')
                ->nullable()
                ->after('calcom_v2_booking_id')
                ->comment('Cal.com host ID from booking response');

            // Note: Index not added due to MySQL 64-index limit on appointments table
            // Query performance will rely on calcom_host_mappings.calcom_host_id index
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('calcom_host_id');
        });
    }
};
