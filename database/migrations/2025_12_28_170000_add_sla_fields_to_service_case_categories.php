<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1.1: Add SLA configuration fields to service_case_categories
 *
 * Bug Fix: Form fields existed in ServiceCaseCategoryResource but columns
 * were missing from database table, causing data loss on save.
 *
 * These fields allow per-category SLA configuration:
 * - sla_response_hours: Target time for first response (in hours)
 * - sla_resolution_hours: Target time for case resolution (in hours)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_case_categories', function (Blueprint $table) {
            // SLA configuration per category
            // NULL = inherit from parent or use system default
            $table->unsignedInteger('sla_response_hours')->nullable()
                ->after('default_priority')
                ->comment('Target hours for first response');

            $table->unsignedInteger('sla_resolution_hours')->nullable()
                ->after('sla_response_hours')
                ->comment('Target hours for case resolution');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_case_categories', function (Blueprint $table) {
            $table->dropColumn(['sla_response_hours', 'sla_resolution_hours']);
        });
    }
};
