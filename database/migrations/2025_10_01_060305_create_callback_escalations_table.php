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
        if (Schema::hasTable('callback_escalations')) {
            return;
        }

        Schema::create('callback_escalations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete()
                ->comment('Company owning this escalation record');

            // Foreign key to callback request
            $table->foreignId('callback_request_id')
                ->constrained('callback_requests')
                ->cascadeOnDelete()
                ->comment('Reference to escalated callback request');

            // Escalation details
            $table->enum('escalation_reason', ['sla_breach', 'manual_escalation', 'multiple_attempts_failed'])
                ->comment('Reason for escalation');

            // Staff tracking
            $table->uuid('escalated_from')->nullable();
            $table->foreign('escalated_from')
                ->references('id')
                ->on('staff')
                ->nullOnDelete()
                ->comment('Original staff member, null if system escalation');

            $table->uuid('escalated_to')->nullable();
            $table->foreign('escalated_to')
                ->references('id')
                ->on('staff')
                ->nullOnDelete()
                ->comment('Staff member escalated to, null if unassigned');

            // Escalation timeline
            $table->timestamp('escalated_at')
                ->comment('When escalation occurred');

            $table->timestamp('resolved_at')
                ->nullable()
                ->comment('When escalation was resolved');

            // Resolution details
            $table->text('resolution_notes')
                ->nullable()
                ->comment('Notes on escalation resolution');

            $table->json('metadata')
                ->nullable()
                ->comment('Additional escalation context and data');

            // Standard timestamps
            $table->timestamps();

            // Indexes for performance
            $table->index('company_id', 'idx_company');
            $table->index(['company_id', 'callback_request_id'], 'idx_company_callback');
            $table->index(['company_id', 'escalated_to', 'resolved_at'], 'idx_escalated_to_resolved');
            $table->index(['company_id', 'escalation_reason'], 'idx_company_reason');
            $table->index(['company_id', 'escalated_at'], 'idx_company_escalated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('callback_escalations');
    }
};
