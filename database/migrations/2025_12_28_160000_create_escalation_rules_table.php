<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Escalation Rules for Service Gateway
 *
 * ServiceNow-style automated escalation based on SLA breaches,
 * idle time, or priority changes. Opt-in per company.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Escalation Rules table
        Schema::create('escalation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();

            // Trigger configuration
            $table->enum('trigger_type', [
                'sla_response_breach',   // Response SLA exceeded
                'sla_resolution_breach', // Resolution SLA exceeded
                'sla_response_warning',  // Response SLA approaching (X minutes before)
                'sla_resolution_warning',// Resolution SLA approaching (X minutes before)
                'idle_time',             // No activity for X minutes
                'priority_change',       // Priority increased
            ]);
            $table->integer('trigger_minutes')->nullable()
                ->comment('Minutes before/after for warning/idle triggers');

            // Condition filters (JSON)
            $table->json('conditions')->nullable()
                ->comment('Filter by priority, category, case_type, assigned_group');

            // Action configuration
            $table->enum('action_type', [
                'notify_email',      // Send email notification
                'reassign_group',    // Reassign to another group
                'escalate_priority', // Increase priority
                'notify_webhook',    // Call external webhook
            ]);
            $table->json('action_config')
                ->comment('Action-specific configuration');

            // Control
            $table->boolean('is_active')->default(true);
            $table->integer('execution_order')->default(0)
                ->comment('Lower = executes first');
            $table->timestamp('last_executed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['company_id', 'is_active', 'trigger_type']);
            $table->index('execution_order');
        });

        // Escalation execution log
        Schema::create('escalation_rule_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('escalation_rule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_case_id')->constrained()->cascadeOnDelete();
            $table->string('action_type');
            $table->json('action_result')->nullable();
            $table->boolean('success')->default(true);
            $table->text('error_message')->nullable();
            $table->timestamp('executed_at');

            $table->index(['escalation_rule_id', 'executed_at']);
            $table->index(['service_case_id', 'executed_at']);
        });

        // Add opt-in setting to companies
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('escalation_rules_enabled')->default(false)
                ->after('is_active')
                ->comment('Opt-in for automated escalation rules');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('escalation_rules_enabled');
        });

        Schema::dropIfExists('escalation_rule_executions');
        Schema::dropIfExists('escalation_rules');
    }
};
