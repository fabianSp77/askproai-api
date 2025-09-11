<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to add Cal.com V2 extracted fields
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Check and add attendees field if not exists
            if (!Schema::hasColumn('appointments', 'attendees')) {
                $table->json('attendees')->nullable()->after('meeting_url')
                    ->comment('Array of attendee information from Cal.com');
            }
            
            // Check and add responses field if not exists
            if (!Schema::hasColumn('appointments', 'responses')) {
                $table->json('responses')->nullable()->after('attendees')
                    ->comment('Booking form responses from Cal.com');
            }
            
            // Check and add location fields if not exist
            if (!Schema::hasColumn('appointments', 'location_type')) {
                $table->string('location_type')->nullable()->after('responses')
                    ->comment('Type of location: video, phone, inPerson, email, integration');
            }
            
            if (!Schema::hasColumn('appointments', 'location_value')) {
                $table->string('location_value', 500)->nullable()->after('location_type')
                    ->comment('Location details or URL');
            }
            
            // Check and add booking_metadata if not exists
            if (!Schema::hasColumn('appointments', 'booking_metadata')) {
                $table->json('booking_metadata')->nullable()->after('location_value')
                    ->comment('Additional booking metadata including title, hosts, rating');
            }
            
            // Check and add recurring fields if not exist
            if (!Schema::hasColumn('appointments', 'is_recurring')) {
                $table->boolean('is_recurring')->default(false)->after('booking_metadata')
                    ->comment('Flag for recurring appointments');
            }
            
            if (!Schema::hasColumn('appointments', 'recurring_event_id')) {
                $table->string('recurring_event_id')->nullable()->after('is_recurring')
                    ->comment('ID for recurring event series');
            }
            
            // Check and add cancellation/rejection reasons if not exist
            if (!Schema::hasColumn('appointments', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable()->after('recurring_event_id')
                    ->comment('Reason for cancellation if cancelled');
            }
            
            if (!Schema::hasColumn('appointments', 'rejected_reason')) {
                $table->text('rejected_reason')->nullable()->after('cancellation_reason')
                    ->comment('Reason for rejection if rejected');
            }
            
            // Add indexes for better query performance
            $table->index('location_type', 'idx_appointments_location_type');
            $table->index('is_recurring', 'idx_appointments_is_recurring');
            $table->index(['calcom_v2_booking_id', 'source'], 'idx_appointments_calcom_v2_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('idx_appointments_location_type');
            $table->dropIndex('idx_appointments_is_recurring');
            $table->dropIndex('idx_appointments_calcom_v2_source');
            
            // Drop columns
            $columns = [
                'attendees',
                'responses',
                'location_type',
                'location_value',
                'booking_metadata',
                'is_recurring',
                'recurring_event_id',
                'cancellation_reason',
                'rejected_reason'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('appointments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};