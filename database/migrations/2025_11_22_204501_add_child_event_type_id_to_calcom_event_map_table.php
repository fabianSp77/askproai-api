<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds child_event_type_id to resolve Cal.com MANAGED event type bookings.
     *
     * CONTEXT:
     * Cal.com creates MANAGED event types as parent templates that cannot be booked directly.
     * Each staff member assigned to a MANAGED event type gets a CHILD event type.
     * We must use the child event type ID for bookings, not the parent ID.
     *
     * @see CalcomChildEventTypeResolver for resolution logic
     */
    public function up(): void
    {
        Schema::table('calcom_event_map', function (Blueprint $table) {
            // Cal.com child event type ID (bookable staff-specific event type)
            $table->integer('child_event_type_id')->nullable()->after('event_type_id')
                ->comment('Cal.com child event type ID for MANAGED event types (bookable)');

            // Track when child ID was last resolved
            $table->timestamp('child_resolved_at')->nullable()->after('child_event_type_id')
                ->comment('Last time child event type ID was resolved from Cal.com');

            // Index for performance
            $table->index('child_event_type_id', 'idx_child_event_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calcom_event_map', function (Blueprint $table) {
            $table->dropIndex('idx_child_event_type_id');
            $table->dropColumn(['child_event_type_id', 'child_resolved_at']);
        });
    }
};
