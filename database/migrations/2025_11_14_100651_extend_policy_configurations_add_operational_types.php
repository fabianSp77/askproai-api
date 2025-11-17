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
     * Extends policy_configurations.policy_type enum with operational policy types:
     * - booking: Allow/deny appointment booking
     * - appointment_inquiry: Allow/deny appointment information requests
     * - availability_inquiry: Allow/deny availability checks
     * - callback_service: Allow/deny callback requests
     * - service_information: Allow/deny service info requests
     * - opening_hours: Allow/deny opening hours requests
     * - anonymous_caller_restrictions: Hard-coded security rules for anonymous callers
     * - appointment_info_disclosure: Configure what appointment details to reveal
     */
    public function up(): void
    {
        // PostgreSQL: Add new enum values to existing policy_type enum
        DB::statement("
            ALTER TABLE policy_configurations
            DROP CONSTRAINT IF EXISTS policy_configurations_policy_type_check
        ");

        DB::statement("
            ALTER TABLE policy_configurations
            ADD CONSTRAINT policy_configurations_policy_type_check
            CHECK (policy_type IN (
                'cancellation',
                'reschedule',
                'recurring',
                'booking',
                'appointment_inquiry',
                'availability_inquiry',
                'callback_service',
                'service_information',
                'opening_hours',
                'anonymous_caller_restrictions',
                'appointment_info_disclosure'
            ))
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        DB::statement("
            ALTER TABLE policy_configurations
            DROP CONSTRAINT IF EXISTS policy_configurations_policy_type_check
        ");

        DB::statement("
            ALTER TABLE policy_configurations
            ADD CONSTRAINT policy_configurations_policy_type_check
            CHECK (policy_type IN (
                'cancellation',
                'reschedule',
                'recurring'
            ))
        ");
    }
};
