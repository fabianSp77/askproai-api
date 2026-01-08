<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the service_case_activity_logs table for immutable audit trail
 *
 * PURPOSE: Track all changes to ServiceCase records for:
 * - Compliance (GDPR, SOC2)
 * - Debugging and support
 * - Customer communication history
 * - SLA tracking
 *
 * DESIGN DECISIONS:
 * - No updated_at: Records are immutable (audit trail)
 * - company_id: Multi-tenancy isolation
 * - JSON columns: Flexible old/new value storage
 * - Composite indexes: Optimized for common queries
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_case_activity_logs', function (Blueprint $table) {
            $table->id();

            // Foreign keys with multi-tenancy
            $table->foreignId('service_case_id')
                ->constrained('service_cases')
                ->cascadeOnDelete();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Action tracking
            $table->string('action', 50);  // created, status_changed, assigned, etc.

            // Change tracking (JSON for flexibility)
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            // Request context (for debugging/security)
            $table->string('ip_address', 45)->nullable();  // IPv6 compatible
            $table->string('user_agent', 255)->nullable();

            // Optional reason (for cancellations, reassignments, etc.)
            $table->text('reason')->nullable();

            // Immutable: only created_at, no updated_at
            $table->timestamp('created_at')->useCurrent();

            // Composite indexes for common queries
            $table->index(['service_case_id', 'created_at'], 'idx_case_timeline');
            $table->index(['company_id', 'created_at'], 'idx_company_activity');
            $table->index('action', 'idx_action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_case_activity_logs');
    }
};
