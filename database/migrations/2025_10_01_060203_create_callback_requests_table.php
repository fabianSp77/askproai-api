<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('callback_requests')) {
            return;
        }

        Schema::create('callback_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete()
                ->comment('Company owning this callback request');

            // Foreign key relationships
            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete()
                ->comment('Existing customer, null for new customers');

            $table->uuid('branch_id');
            $table->foreign('branch_id')
                ->references('id')
                ->on('branches')
                ->cascadeOnDelete()
                ->comment('Branch requested for callback');

            $table->foreignId('service_id')
                ->nullable()
                ->constrained('services')
                ->nullOnDelete()
                ->comment('Service requested, null if not specified');

            $table->uuid('staff_id')->nullable();
            $table->foreign('staff_id')
                ->references('id')
                ->on('staff')
                ->nullOnDelete()
                ->comment('Preferred staff member, null if no preference');

            // Customer contact information
            $table->string('phone_number', 50)
                ->comment('Customer phone number in E.164 format');

            $table->string('customer_name', 255)
                ->comment('Customer name for callback');

            // Scheduling and priority
            $table->json('preferred_time_window')
                ->nullable()
                ->comment('JSON: {"start": "2025-10-02 09:00", "end": "2025-10-02 17:00"}');

            $table->enum('priority', ['normal', 'high', 'urgent'])
                ->default('normal')
                ->comment('Callback priority level');

            $table->enum('status', ['pending', 'assigned', 'contacted', 'completed', 'expired', 'cancelled'])
                ->default('pending')
                ->comment('Current status of callback request');

            // Assignment tracking
            $table->uuid('assigned_to')->nullable();
            $table->foreign('assigned_to')
                ->references('id')
                ->on('staff')
                ->nullOnDelete()
                ->comment('Staff member assigned to handle callback');

            $table->timestamp('assigned_at')->nullable()->comment('When callback was assigned');
            $table->timestamp('contacted_at')->nullable()->comment('When customer was contacted');
            $table->timestamp('completed_at')->nullable()->comment('When callback was completed');
            $table->timestamp('expires_at')->comment('SLA deadline for callback');

            // Additional information
            $table->text('notes')->nullable()->comment('Customer notes or special requests');
            $table->json('metadata')->nullable()->comment('Retell call data and context');

            // Standard timestamps and soft deletes
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('company_id', 'idx_company');
            $table->index(['company_id', 'status', 'priority', 'expires_at'], 'idx_status_priority_expires');
            $table->index(['company_id', 'assigned_to', 'status'], 'idx_assigned_status');
            $table->index(['company_id', 'customer_id'], 'idx_company_customer');
            $table->index('branch_id');
            $table->index(['company_id', 'created_at'], 'idx_company_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('callback_requests');
    }
};
