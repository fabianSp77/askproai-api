<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tables for data consistency monitoring and circuit breaker state.
     */
    public function up(): void
    {
        // Table 1: Circuit Breaker States
        Schema::create('circuit_breaker_states', function (Blueprint $table) {
            $table->id();
            $table->string('circuit_key')->unique()->comment('Unique circuit identifier (e.g., appointment_booking:service:123)');
            $table->enum('state', ['closed', 'open', 'half_open'])->default('closed')->comment('Current circuit state');
            $table->integer('failure_count')->default(0)->comment('Consecutive failure count');
            $table->integer('success_count')->default(0)->comment('Success count in half_open state');
            $table->timestamp('last_failure_at')->nullable()->comment('Timestamp of last failure');
            $table->timestamp('opened_at')->nullable()->comment('When circuit was opened');
            $table->timestamp('closed_at')->nullable()->comment('When circuit was closed');
            $table->json('metadata')->nullable()->comment('Additional context (service_id, error types, etc.)');
            $table->timestamps();

            // Indexes
            $table->index('circuit_key');
            $table->index('state');
            $table->index('last_failure_at');
            $table->index(['state', 'opened_at']); // For finding circuits to reset
        });

        // Table 2: Data Consistency Alerts
        Schema::create('data_consistency_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('alert_type')->comment('Type of inconsistency (session_outcome_mismatch, missing_appointment, etc.)');
            $table->enum('severity', ['critical', 'warning', 'info'])->default('warning')->comment('Alert severity level');
            $table->string('entity_type')->comment('Entity type (call, appointment, etc.)');
            $table->unsignedBigInteger('entity_id')->nullable()->comment('ID of affected entity');
            $table->text('description')->comment('Human-readable description');
            $table->json('metadata')->nullable()->comment('Additional context and details');
            $table->timestamp('detected_at')->comment('When inconsistency was detected');
            $table->boolean('auto_corrected')->default(false)->comment('Whether automatically fixed');
            $table->timestamp('corrected_at')->nullable()->comment('When auto-correction was applied');
            $table->text('resolution_notes')->nullable()->comment('Notes on how it was resolved');
            $table->timestamps();

            // Indexes
            $table->index('alert_type');
            $table->index('severity');
            $table->index('entity_type');
            $table->index('entity_id');
            $table->index('detected_at');
            $table->index('auto_corrected');
            $table->index(['alert_type', 'detected_at']); // For reporting
            $table->index(['severity', 'detected_at']); // For filtering by severity
        });

        // Table 3: Manual Review Queue
        Schema::create('manual_review_queue', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('call_id')->comment('Call requiring manual review');
            $table->string('retell_call_id')->nullable()->comment('Retell call ID for reference');
            $table->string('reason')->comment('Why manual review is needed');
            $table->integer('priority')->default(5)->comment('Priority level (1=highest, 5=lowest)');
            $table->enum('status', ['pending', 'in_progress', 'resolved', 'dismissed'])->default('pending');
            $table->json('context')->nullable()->comment('Additional context for review');
            $table->unsignedBigInteger('assigned_to')->nullable()->comment('User ID assigned to review');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('call_id')->references('id')->on('calls')->onDelete('cascade');

            // Indexes
            $table->index('call_id');
            $table->index('status');
            $table->index('priority');
            $table->index(['status', 'priority']); // For queue management
            $table->index('assigned_to');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manual_review_queue');
        Schema::dropIfExists('data_consistency_alerts');
        Schema::dropIfExists('circuit_breaker_states');
    }
};
