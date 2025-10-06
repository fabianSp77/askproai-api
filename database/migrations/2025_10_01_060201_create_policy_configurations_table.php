<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates hierarchical policy configuration system for appointment management.
     * Supports 4-level hierarchy: Company → Branch → Service → Staff
     * Policy types: cancellation, reschedule, recurring
     */
    public function up(): void
    {
        if (Schema::hasTable('policy_configurations')) {
            return;
        }

        Schema::create('policy_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete()
                ->comment('Company owning this policy configuration');

            // Polymorphic relationship - can attach to Company, Branch, Service, or Staff
            $table->string('configurable_type')->comment('Morph type: Company, Branch, Service, Staff');
            $table->string('configurable_id')->comment('ID of parent entity (supports UUID and BIGINT)');

            // Policy type categorization
            $table->enum('policy_type', ['cancellation', 'reschedule', 'recurring'])
                ->comment('Type of policy being configured');

            // Flexible JSON configuration storage
            // Examples:
            // cancellation: {"hours_before": 24, "fee_percentage": 50, "max_cancellations_per_month": 3}
            // reschedule: {"hours_before": 12, "max_reschedules_per_appointment": 2, "fee_percentage": 25}
            // recurring: {"allow_partial_cancel": true, "require_full_series_notice": false}
            $table->json('config')
                ->comment('Flexible policy settings as JSON');

            // Inheritance control
            $table->boolean('is_override')->default(false)
                ->comment('True if this overrides parent policy, false if inheriting');

            // Self-reference to parent policy being overridden
            $table->foreignId('overrides_id')->nullable()
                ->constrained('policy_configurations')->onDelete('set null')
                ->comment('Parent policy being overridden, null if no override');

            // Metadata
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('company_id', 'idx_company');
            $table->index(['company_id', 'configurable_type', 'configurable_id'], 'idx_polymorphic_config');
            $table->index(['company_id', 'policy_type'], 'idx_policy_type');
            $table->index(['is_override', 'overrides_id'], 'idx_override_chain');

            // Unique constraint: one policy type per company-entity combination
            // This prevents multiple cancellation policies for same service, etc.
            $table->unique(['company_id', 'configurable_type', 'configurable_id', 'policy_type', 'deleted_at'], 'unique_policy_per_entity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policy_configurations');
    }
};
