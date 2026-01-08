<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add SLA Tracking toggle to companies
 *
 * PURPOSE: Allow pass-through companies (like IT Systemhaus) to disable SLA tracking
 * when they only capture and forward tickets without being responsible for SLA compliance.
 *
 * BEHAVIOR:
 * - TRUE (default): SLA due dates are calculated, SLA reports include this company
 * - FALSE: No SLA dates calculated, excluded from SLA compliance reports
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('sla_tracking_enabled')->default(true)
                ->after('escalation_rules_enabled')
                ->comment('Enable SLA tracking for this company. Disable for pass-through/forwarding scenarios.');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('sla_tracking_enabled');
        });
    }
};
