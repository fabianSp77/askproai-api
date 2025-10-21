<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 3: Resilience & Error Handling Infrastructure
     *
     * PURPOSE:
     * - Add resilience services for Cal.com API failures
     * - Track circuit breaker state transitions
     * - Record failure metrics for monitoring
     * - Enable graceful degradation during outages
     *
     * SERVICES IMPLEMENTED:
     * - CalcomCircuitBreaker: 3-state pattern (CLOSED/OPEN/HALF_OPEN)
     * - RetryPolicy: Exponential backoff with jitter (1s, 2s, 4s)
     * - FailureDetector: Proactive failure monitoring
     * - Exception hierarchy: Domain-specific exception classes
     *
     * @author Phase 3 Implementation
     * @date 2025-10-18
     */
    public function up(): void
    {
        // Create circuit breaker state transitions table (for historical tracking)
        if (!Schema::hasTable('circuit_breaker_events')) {
            Schema::create('circuit_breaker_events', function (Blueprint $table) {
                $table->id();
                $table->string('service'); // 'calcom', 'retell', etc
                $table->string('old_state'); // CLOSED, OPEN, HALF_OPEN
                $table->string('new_state');
                $table->string('reason')->nullable();
                $table->integer('failure_count')->default(0);
                $table->json('context')->nullable(); // Additional data
                $table->timestamps();

                $table->index('service');
                $table->index('created_at');
                $table->index(['service', 'created_at']);

                \Log::info('✅ Created circuit_breaker_events table');
            });
        }

        // Create failure metrics table (for monitoring dashboards)
        if (!Schema::hasTable('failure_metrics')) {
            Schema::create('failure_metrics', function (Blueprint $table) {
                $table->id();
                $table->string('service'); // 'calcom', 'retell', etc
                $table->string('endpoint')->nullable(); // Specific API endpoint
                $table->string('error_type'); // 'timeout', '429', 'connection_lost', etc
                $table->integer('count')->default(1);
                $table->integer('severity')->default(2); // 1=low, 2=medium, 3=critical
                $table->float('success_rate')->default(0); // 0.0 to 1.0
                $table->integer('avg_response_time_ms')->default(0);
                $table->integer('max_response_time_ms')->default(0);
                $table->timestamp('first_occurrence_at');
                $table->timestamp('last_occurrence_at');
                $table->json('last_error_context')->nullable();
                $table->timestamps();

                $table->index('service');
                $table->index('error_type');
                $table->index(['service', 'created_at']);
                $table->index('last_occurrence_at');

                \Log::info('✅ Created failure_metrics table');
            });
        }

        // Add resilience tracking columns to appointments table
        Schema::table('appointments', function (Blueprint $table) {
            // Track retry attempts
            if (!Schema::hasColumn('appointments', 'retry_count')) {
                $table->integer('retry_count')
                    ->default(0)
                    ->after('last_sync_attempted_at')
                    ->comment('Number of times booking was retried');

                \Log::info('✅ Added retry_count column to appointments');
            }

            // Track if circuit breaker was open when booking attempted
            if (!Schema::hasColumn('appointments', 'circuit_breaker_open_at_booking')) {
                $table->timestamp('circuit_breaker_open_at_booking')
                    ->nullable()
                    ->after('retry_count')
                    ->comment('Timestamp when circuit breaker was open during booking attempt');

                \Log::info('✅ Added circuit_breaker_open_at_booking column to appointments');
            }

            // Track resilience decisions made
            if (!Schema::hasColumn('appointments', 'resilience_strategy')) {
                $table->string('resilience_strategy')
                    ->nullable()
                    ->after('circuit_breaker_open_at_booking')
                    ->comment('Resilience strategy used: immediate_retry, exponential_backoff, circuit_breaker_open, graceful_degradation');

                \Log::info('✅ Added resilience_strategy column to appointments');
            }
        });

        \Log::info('📊 Phase 3 Migration: Resilience Infrastructure Setup Complete');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasColumn('appointments', 'retry_count')) {
                $table->dropColumn('retry_count');
            }
            if (Schema::hasColumn('appointments', 'circuit_breaker_open_at_booking')) {
                $table->dropColumn('circuit_breaker_open_at_booking');
            }
            if (Schema::hasColumn('appointments', 'resilience_strategy')) {
                $table->dropColumn('resilience_strategy');
            }
        });

        Schema::dropIfExists('circuit_breaker_events');
        Schema::dropIfExists('failure_metrics');
    }
};
