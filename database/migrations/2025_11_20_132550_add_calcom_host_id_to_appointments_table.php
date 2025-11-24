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
            if (!Schema::hasColumn('appointments', 'calcom_host_id')) {
                $table->integer('calcom_host_id')
                    ->nullable()
                    ->after('calcom_v2_booking_id')
                    ->comment('Cal.com host/organizer ID for staff mapping audit trail');

                $table->index('calcom_host_id', 'idx_appointments_calcom_host_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasColumn('appointments', 'calcom_host_id')) {
                $table->dropIndex('idx_appointments_calcom_host_id');
                $table->dropColumn('calcom_host_id');
            }
        });
    }
};
