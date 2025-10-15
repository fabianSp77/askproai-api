<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove calcom_event_type_id from branches table
     *
     * This field was incorrectly added on 2025-10-14.
     * It conflicts with the architecture decision made in 2025_09_29_fix_calcom_event_ownership.php
     * which explicitly REMOVED this field as "redundant with services".
     *
     * CORRECT ARCHITECTURE:
     * - Company has calcom_team_id (ONE team)
     * - Services have calcom_event_type_id (EACH service = ONE event type)
     * - Branches link to Services via branch_service pivot (MANY-TO-MANY)
     * - Branches do NOT have their own event_type_id
     */
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'calcom_event_type_id')) {
                // Drop index first
                $table->dropIndex(['calcom_event_type_id']);

                // Drop column
                $table->dropColumn('calcom_event_type_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Do NOT recreate this field - it was architecturally wrong
        // If rollback is needed, the field will remain removed
    }
};
