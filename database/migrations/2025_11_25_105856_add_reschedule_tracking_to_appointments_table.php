<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add reschedule tracking fields to appointments table
 *
 * Purpose: Track when appointments are rescheduled for:
 * - Admin Portal "Verschoben" badge display
 * - Reschedule history tracking
 * - Policy enforcement (max reschedules)
 *
 * Related: AppointmentModification table stores detailed history,
 * but these fields provide quick access for display/filtering.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Reschedule tracking fields
            $table->timestamp('rescheduled_at')->nullable()
                ->after('updated_at')
                ->comment('Last reschedule timestamp for quick filtering');

            $table->string('rescheduled_by', 50)->nullable()
                ->after('rescheduled_at')
                ->comment('Who rescheduled: customer, staff, system, retell_ai');

            $table->unsignedSmallInteger('rescheduled_count')->default(0)
                ->after('rescheduled_by')
                ->comment('Total number of reschedules for policy checks');

            $table->timestamp('previous_starts_at')->nullable()
                ->after('rescheduled_count')
                ->comment('Previous start time before last reschedule');

            // Cal.com reschedule tracking
            $table->string('calcom_previous_booking_uid', 100)->nullable()
                ->after('calcom_v2_booking_uid')
                ->comment('Previous Cal.com booking UID before reschedule');

            // Index for filtering rescheduled appointments
            $table->index('rescheduled_at', 'idx_appointments_rescheduled_at');
            $table->index(['rescheduled_count', 'customer_id'], 'idx_appointments_reschedule_policy');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('idx_appointments_rescheduled_at');
            $table->dropIndex('idx_appointments_reschedule_policy');

            $table->dropColumn([
                'rescheduled_at',
                'rescheduled_by',
                'rescheduled_count',
                'previous_starts_at',
                'calcom_previous_booking_uid',
            ]);
        });
    }
};
