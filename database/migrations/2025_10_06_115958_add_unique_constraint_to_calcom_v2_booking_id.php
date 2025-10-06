<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds unique constraint to calcom_v2_booking_id column to prevent duplicate bookings
     * from Cal.com idempotency behavior.
     *
     * IMPORTANT: Cleans up existing duplicates before adding constraint
     */
    public function up(): void
    {
        // STEP 1: Find and clean up existing duplicate calcom_v2_booking_ids

        Log::info('🔧 Migration: Starting duplicate cleanup for calcom_v2_booking_id');

        // Find all duplicate booking IDs
        $duplicates = DB::table('appointments')
            ->select('calcom_v2_booking_id', DB::raw('COUNT(*) as count'))
            ->whereNotNull('calcom_v2_booking_id')
            ->groupBy('calcom_v2_booking_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isEmpty()) {
            Log::info('✅ Migration: No duplicates found');
        } else {
            Log::warning('⚠️ Migration: Found duplicate calcom_v2_booking_ids', [
                'duplicate_count' => $duplicates->count(),
                'duplicates' => $duplicates->pluck('calcom_v2_booking_id')->toArray()
            ]);

            // For each duplicate, keep oldest appointment, delete newer ones
            foreach ($duplicates as $duplicate) {
                $bookingId = $duplicate->calcom_v2_booking_id;

                $appointments = DB::table('appointments')
                    ->where('calcom_v2_booking_id', $bookingId)
                    ->orderBy('created_at', 'asc')
                    ->get();

                if ($appointments->isEmpty()) {
                    continue;
                }

                // Keep first (oldest) appointment
                $keepAppointment = $appointments->first();
                $deleteAppointments = $appointments->slice(1);

                if ($deleteAppointments->isEmpty()) {
                    continue;
                }

                $deletedIds = $deleteAppointments->pluck('id')->toArray();

                // Delete newer duplicate appointments
                DB::table('appointments')
                    ->whereIn('id', $deletedIds)
                    ->delete();

                Log::warning('🗑️ Migration: Deleted duplicate appointments', [
                    'calcom_booking_id' => $bookingId,
                    'kept_appointment_id' => $keepAppointment->id,
                    'kept_created_at' => $keepAppointment->created_at,
                    'kept_call_id' => $keepAppointment->call_id,
                    'kept_customer_id' => $keepAppointment->customer_id,
                    'deleted_appointment_ids' => $deletedIds,
                    'deleted_count' => count($deletedIds)
                ]);
            }

            Log::info('✅ Migration: Duplicate cleanup completed', [
                'duplicates_processed' => $duplicates->count()
            ]);
        }

        // STEP 2: Add unique constraint to prevent future duplicates

        Schema::table('appointments', function (Blueprint $table) {
            // Add unique constraint on calcom_v2_booking_id
            // Note: Index name explicitly specified for clarity in database
            $table->unique('calcom_v2_booking_id', 'unique_calcom_v2_booking_id');
        });

        Log::info('✅ Migration: Unique constraint added to calcom_v2_booking_id');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Drop unique constraint
            $table->dropUnique('unique_calcom_v2_booking_id');
        });

        Log::info('⏪ Migration: Unique constraint removed from calcom_v2_booking_id');
    }
};
