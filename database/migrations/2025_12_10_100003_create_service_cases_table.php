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
        // Skip if table already exists
        if (Schema::hasTable('service_cases')) {
            return;
        }

        Schema::create('service_cases', function (Blueprint $table) {
            $table->id();

            // Multi-tenant isolation
            $table->unsignedBigInteger('company_id');

            // Optional relationships
            $table->unsignedBigInteger('call_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();

            // Case classification
            $table->unsignedBigInteger('category_id');
            $table->enum('case_type', ['incident', 'request', 'inquiry']);

            // Priority matrix (calculated from urgency + impact)
            $table->enum('priority', ['critical', 'high', 'normal', 'low'])->default('normal');
            $table->enum('urgency', ['critical', 'high', 'normal', 'low'])->default('normal');
            $table->enum('impact', ['critical', 'high', 'normal', 'low'])->default('normal');

            // Case content
            $table->string('subject', 255);
            $table->text('description');
            $table->json('structured_data')->nullable();
            $table->json('ai_metadata')->nullable();

            // Status management
            $table->enum('status', ['new', 'open', 'pending', 'resolved', 'closed'])->default('new');
            $table->string('external_reference', 100)->nullable();

            // Assignment (UUID to match staff.id type)
            $table->uuid('assigned_to')->nullable();

            // SLA tracking
            $table->timestamp('sla_response_due_at')->nullable();
            $table->timestamp('sla_resolution_due_at')->nullable();

            // Lifecycle timestamps
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            // Output delivery status
            $table->enum('output_status', ['pending', 'sent', 'failed'])->default('pending');
            $table->timestamp('output_sent_at')->nullable();
            $table->text('output_error')->nullable();

            // Standard timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'priority']);
            $table->index(['company_id', 'created_at']);
            $table->index(['assigned_to', 'status']);
            $table->index('output_status');

            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('call_id')->references('id')->on('calls')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('category_id')->references('id')->on('service_case_categories')->onDelete('restrict');
            // Note: assigned_to FK omitted - staff table uses different ID type
            // Will be enforced at application level
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_cases');
    }
};
