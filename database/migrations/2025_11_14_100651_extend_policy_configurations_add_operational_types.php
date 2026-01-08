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
        // Skip if table doesn't exist
        if (!Schema::hasTable('policy_configurations')) {
            return;
        }

        // Skip if column doesn't exist
        if (!Schema::hasColumn('policy_configurations', 'policy_type')) {
            return;
        }

        // Try to update the CHECK constraint (PostgreSQL syntax)
        // MySQL/MariaDB may not support this syntax - wrap in try-catch
        try {
            DB::statement("
                ALTER TABLE policy_configurations
                DROP CONSTRAINT IF EXISTS policy_configurations_policy_type_check
            ");
        } catch (\Exception $e) {
            // MySQL doesn't support DROP CONSTRAINT IF EXISTS for CHECK constraints - ignore
        }

        try {
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
        } catch (\Exception $e) {
            // MySQL/MariaDB CHECK constraint error - ignore (enum validation happens in application layer)
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Skip if table doesn't exist
        if (!Schema::hasTable('policy_configurations')) {
            return;
        }

        // Skip if column doesn't exist
        if (!Schema::hasColumn('policy_configurations', 'policy_type')) {
            return;
        }

        // Revert to original enum values
        try {
            DB::statement("
                ALTER TABLE policy_configurations
                DROP CONSTRAINT IF EXISTS policy_configurations_policy_type_check
            ");
        } catch (\Exception $e) {
            // Ignore
        }

        try {
            DB::statement("
                ALTER TABLE policy_configurations
                ADD CONSTRAINT policy_configurations_policy_type_check
                CHECK (policy_type IN (
                    'cancellation',
                    'reschedule',
                    'recurring'
                ))
            ");
        } catch (\Exception $e) {
            // Ignore
        }
    }
};
