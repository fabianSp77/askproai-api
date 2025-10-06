<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Audit trail for appointment cancellations and reschedules.
     * Tracks policy compliance, fees charged, and modification history.
     * Enables 30-day rolling window analysis for quota enforcement.
     */
    public function up(): void
    {
        if (Schema::hasTable('appointment_modifications')) {
            return;
        }

        Schema::create('appointment_modifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete()
                ->comment('Company owning this modification record');

            // Core relationships
            $table->foreignId('appointment_id')
                ->constrained('appointments')
                ->onDelete('cascade')
                ->comment('Appointment being modified');

            $table->foreignId('customer_id')
                ->constrained('customers')
                ->onDelete('cascade')
                ->comment('Customer who owns the appointment');

            // Modification details
            $table->enum('modification_type', ['cancel', 'reschedule'])
                ->comment('Type of modification performed');

            // Policy compliance tracking
            $table->boolean('within_policy')->default(true)
                ->comment('True if modification followed policy rules');

            // Financial tracking
            $table->decimal('fee_charged', 10, 2)->default(0)
                ->comment('Cancellation/reschedule fee charged in EUR');

            // Contextual information
            $table->text('reason')->nullable()
                ->comment('Optional reason provided by customer/staff');

            // Actor tracking (who made the modification)
            $table->string('modified_by_type')->nullable()
                ->comment('User, System, Customer, Staff');
            $table->unsignedBigInteger('modified_by_id')->nullable()
                ->comment('ID of actor who made modification');

            // Additional context storage
            // Examples:
            // {"hours_notice": 48, "policy_required": 24, "original_time": "2025-10-15 14:00", "new_time": "2025-10-15 16:00"}
            $table->json('metadata')->nullable()
                ->comment('Additional context like hours notice, policy details');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Performance indexes
            $table->index('company_id', 'idx_company');
            // Critical for 30-day rolling window queries: "get customer's cancellations in last 30 days"
            $table->index(['company_id', 'customer_id', 'modification_type', 'created_at'], 'idx_customer_mods_rolling');

            // For appointment history lookup
            $table->index(['company_id', 'appointment_id', 'created_at'], 'idx_appointment_history');

            // For policy compliance reports
            $table->index(['company_id', 'within_policy', 'modification_type'], 'idx_policy_compliance');

            // For fee analysis
            $table->index(['company_id', 'fee_charged', 'created_at'], 'idx_fee_analysis');

            // For actor-based queries
            $table->index(['modified_by_type', 'modified_by_id'], 'idx_modified_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_modifications');
    }
};
