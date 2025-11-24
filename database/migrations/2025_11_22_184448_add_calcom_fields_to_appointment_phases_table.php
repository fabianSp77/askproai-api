<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds Cal.com booking fields to appointment_phases table for composite sync support.
     * Each phase (segment) of a composite appointment gets its own Cal.com booking.
     *
     * @see Phase 3.1 of Composite Sync Implementation Plan
     */
    public function up(): void
    {
        Schema::table('appointment_phases', function (Blueprint $table) {
            // Cal.com booking identifiers
            $table->integer('calcom_booking_id')->nullable()->after('end_time')
                ->comment('Cal.com booking ID for this phase/segment');
            $table->string('calcom_booking_uid', 255)->nullable()->after('calcom_booking_id')
                ->comment('Cal.com booking UID for this phase/segment');

            // Sync status tracking
            $table->string('calcom_sync_status', 20)->default('pending')->after('calcom_booking_uid')
                ->comment('Sync status: pending, synced, failed');
            $table->text('sync_error_message')->nullable()->after('calcom_sync_status')
                ->comment('Error message if sync failed');

            // Index for sync status queries
            $table->index(['appointment_id', 'calcom_sync_status'], 'idx_phases_sync_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointment_phases', function (Blueprint $table) {
            $table->dropIndex('idx_phases_sync_status');
            $table->dropColumn([
                'calcom_booking_id',
                'calcom_booking_uid',
                'calcom_sync_status',
                'sync_error_message'
            ]);
        });
    }
};
