<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1.2: Add SLA response tracking to service_cases
 *
 * Bug Fix: EscalationRule.php:195 references $case->sla_response_met_at
 * but column didn't exist, causing errors in escalation processing.
 *
 * This field tracks when the first response was made to a case,
 * enabling accurate SLA compliance reporting.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_cases', function (Blueprint $table) {
            // Timestamp when first response was made
            // Used for SLA compliance calculation
            $table->timestamp('sla_response_met_at')->nullable()
                ->after('sla_resolution_due_at')
                ->comment('When first response was made to the case');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_cases', function (Blueprint $table) {
            $table->dropColumn('sla_response_met_at');
        });
    }
};
