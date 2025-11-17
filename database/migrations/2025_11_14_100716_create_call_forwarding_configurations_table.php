<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates call_forwarding_configurations table for branch-level call routing.
     *
     * Features:
     * - Per-branch forwarding rules with trigger conditions
     * - Priority-based rule evaluation (lowest priority number = highest priority)
     * - Active hours scheduling (when forwarding is enabled)
     * - Multi-tenant isolation via company_id
     * - Soft deletes for audit trail
     *
     * Example forwarding_rules JSON:
     * [
     *   {
     *     "trigger": "no_availability",
     *     "target_number": "+4915112345678",
     *     "priority": 1,
     *     "conditions": {"time_window": "all"}
     *   },
     *   {
     *     "trigger": "after_hours",
     *     "target_number": "+4915187654321",
     *     "priority": 2,
     *     "conditions": {"outside_business_hours": true}
     *   }
     * ]
     */
    public function up(): void
    {
        Schema::create('call_forwarding_configurations', function (Blueprint $table) {
            $table->id();

            // Multi-tenant relationships
            $table->unsignedBigInteger('company_id');
            $table->string('branch_id', 36); // UUID

            // Forwarding rules configuration
            $table->json('forwarding_rules')->comment('Array of trigger conditions and target numbers');

            // Default/fallback numbers
            $table->string('default_forwarding_number', 50)->nullable()->comment('Fallback forwarding number');
            $table->string('emergency_forwarding_number', 50)->nullable()->comment('Emergency escalation number');

            // Scheduling
            $table->json('active_hours')->nullable()->comment('When forwarding is active (JSON time ranges)');
            $table->string('timezone', 50)->default('Europe/Berlin');

            // Status
            $table->boolean('is_active')->default(true)->index();

            // Timestamps & soft deletes
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('company_id')
                  ->references('id')
                  ->on('companies')
                  ->onDelete('cascade');

            $table->foreign('branch_id')
                  ->references('id')
                  ->on('branches')
                  ->onDelete('cascade');

            // Indexes
            $table->index(['branch_id', 'is_active'], 'idx_branch_active');
            $table->unique(['branch_id', 'deleted_at'], 'unique_branch_forwarding');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_forwarding_configurations');
    }
};
