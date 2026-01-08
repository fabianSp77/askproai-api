<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip in testing environment (SQLite doesn't support FULLTEXT indexes and CREATE VIEW)
        if (app()->environment('testing')) {
            return;
        }

        // 1. RETELL_CALL_SESSIONS - Aggregate root for each call
        Schema::create('retell_call_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('call_id', 255)->unique()->index();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('agent_id', 255)->nullable();
            $table->integer('agent_version')->nullable();

            // Call lifecycle
            $table->timestamp('started_at', 6); // Microsecond precision
            $table->timestamp('ended_at', 6)->nullable();
            $table->string('call_status', 50)->default('in_progress')->index();
            $table->string('disconnection_reason', 100)->nullable();
            $table->integer('duration_ms')->nullable();

            // Conversation flow tracking
            $table->string('conversation_flow_id', 255)->nullable();
            $table->string('current_flow_node', 255)->nullable();
            $table->json('flow_state')->nullable();

            // Aggregate counters
            $table->integer('total_events')->default(0);
            $table->integer('function_call_count')->default(0);
            $table->integer('transcript_segment_count')->default(0);
            $table->integer('error_count')->default(0);

            // Performance metrics
            $table->integer('avg_response_time_ms')->nullable();
            $table->integer('max_response_time_ms')->nullable();
            $table->integer('min_response_time_ms')->nullable();

            // Metadata
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
        });

        // 2. RETELL_CALL_EVENTS - Event stream (immutable log)
        Schema::create('retell_call_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('call_session_id')->index();
            $table->uuid('correlation_id')->nullable()->index();

            // Event classification
            $table->string('event_type', 100)->index(); // 'function_call', 'transcript', 'flow_transition', 'error'
            $table->timestamp('occurred_at', 6)->index(); // Microsecond precision
            $table->integer('call_offset_ms')->nullable(); // Milliseconds from call start

            // Function call specific
            $table->string('function_name', 255)->nullable()->index();
            $table->json('function_arguments')->nullable();
            $table->json('function_response')->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->string('function_status', 50)->nullable(); // 'pending', 'success', 'error'

            // Transcript specific
            $table->text('transcript_text')->nullable();
            $table->string('transcript_role', 50)->nullable(); // 'agent', 'user', 'system'

            // Flow transition specific
            $table->string('from_node', 255)->nullable();
            $table->string('to_node', 255)->nullable();
            $table->string('transition_trigger', 255)->nullable();

            // Error specific
            $table->string('error_code', 100)->nullable()->index();
            $table->text('error_message')->nullable();
            $table->json('error_context')->nullable();

            // Metadata
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Foreign key
            $table->foreign('call_session_id')->references('id')->on('retell_call_sessions')->onDelete('cascade');

            // Composite indexes for common queries (MySQL-compatible)
            $table->index(['call_session_id', 'occurred_at'], 'idx_events_session_time');
            $table->index(['event_type', 'occurred_at'], 'idx_events_type_time');
        });

        // 3. RETELL_FUNCTION_TRACES - Optimized view for function debugging (USER'S #1 PRIORITY)
        Schema::create('retell_function_traces', function (Blueprint $table) {
            $table->id();
            $table->uuid('call_session_id')->index();
            $table->unsignedBigInteger('event_id')->nullable(); // Link to event
            $table->uuid('correlation_id')->nullable()->index();

            // Function identification
            $table->string('function_name', 255)->index();
            $table->integer('execution_sequence')->default(0); // Order within call

            // Timing (microsecond precision)
            $table->timestamp('started_at', 6)->index();
            $table->timestamp('completed_at', 6)->nullable();
            $table->integer('duration_ms')->nullable()->index();

            // Input/Output capture
            $table->json('input_params')->nullable();
            $table->json('output_result')->nullable();

            // Status tracking
            $table->string('status', 50)->default('pending')->index(); // 'pending', 'success', 'error', 'timeout'
            $table->json('error_details')->nullable();

            // Performance metrics
            $table->integer('db_query_count')->nullable();
            $table->integer('db_query_time_ms')->nullable();
            $table->integer('external_api_calls')->nullable();
            $table->integer('external_api_time_ms')->nullable();

            // Metadata
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('call_session_id')->references('id')->on('retell_call_sessions')->onDelete('cascade');
            $table->foreign('event_id')->references('id')->on('retell_call_events')->onDelete('set null');

            // Composite indexes for common queries (MySQL-compatible)
            $table->index(['call_session_id', 'execution_sequence'], 'idx_traces_session_seq');
            $table->index(['function_name', 'started_at'], 'idx_traces_function_time');
            $table->index(['status', 'started_at'], 'idx_traces_status');
        });

        // 4. RETELL_TRANSCRIPT_SEGMENTS - Timeline correlation
        Schema::create('retell_transcript_segments', function (Blueprint $table) {
            $table->id();
            $table->uuid('call_session_id')->index();
            $table->unsignedBigInteger('event_id')->nullable();

            // Timing
            $table->timestamp('occurred_at', 6)->index();
            $table->integer('call_offset_ms')->nullable();
            $table->integer('segment_sequence')->default(0);

            // Content
            $table->string('role', 50)->index(); // 'agent', 'user', 'system'
            $table->text('text');
            $table->integer('word_count')->nullable();
            $table->integer('duration_ms')->nullable();

            // Correlation
            $table->uuid('related_function_trace_id')->nullable(); // Link to function that was called near this segment
            $table->string('sentiment', 50)->nullable(); // 'positive', 'negative', 'neutral'

            // Metadata
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('call_session_id')->references('id')->on('retell_call_sessions')->onDelete('cascade');
            $table->foreign('event_id')->references('id')->on('retell_call_events')->onDelete('set null');

            // Composite indexes for common queries (MySQL-compatible)
            $table->index(['call_session_id', 'segment_sequence'], 'idx_transcript_session_seq');
            $table->index(['role', 'occurred_at'], 'idx_transcript_role');
        });

        // Add full-text index for transcript search (MySQL FULLTEXT)
        DB::statement('CREATE FULLTEXT INDEX idx_transcript_text_search ON retell_transcript_segments(text)');

        // 5. RETELL_ERROR_LOG - Fast error lookup and aggregation
        Schema::create('retell_error_log', function (Blueprint $table) {
            $table->id();
            $table->uuid('call_session_id')->index();
            $table->unsignedBigInteger('event_id')->nullable();
            $table->unsignedBigInteger('function_trace_id')->nullable();

            // Error classification
            $table->string('error_code', 100)->index();
            $table->string('error_type', 100)->index(); // 'function_error', 'api_error', 'validation_error', 'system_error'
            $table->string('severity', 50)->default('medium')->index(); // 'low', 'medium', 'high', 'critical'

            // Timing
            $table->timestamp('occurred_at', 6)->index();
            $table->integer('call_offset_ms')->nullable();

            // Details
            $table->text('error_message');
            $table->text('stack_trace')->nullable();
            $table->json('error_context')->nullable();

            // Impact
            $table->boolean('call_terminated')->default(false);
            $table->boolean('booking_failed')->default(false);
            $table->string('affected_function', 255)->nullable();

            // Resolution
            $table->boolean('resolved')->default(false);
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();

            // Metadata
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('call_session_id')->references('id')->on('retell_call_sessions')->onDelete('cascade');
            $table->foreign('event_id')->references('id')->on('retell_call_events')->onDelete('set null');
            $table->foreign('function_trace_id')->references('id')->on('retell_function_traces')->onDelete('set null');

            // Composite indexes for common queries (MySQL-compatible)
            $table->index(['error_code', 'occurred_at'], 'idx_errors_code_time');
            $table->index(['error_type', 'severity', 'occurred_at'], 'idx_errors_type_severity');
            $table->index(['resolved', 'occurred_at'], 'idx_errors_unresolved');
            $table->index(['severity', 'occurred_at'], 'idx_errors_critical');
        });

        // Create view for quick call debugging (MySQL-compatible, idempotent)
        DB::statement("
            CREATE OR REPLACE VIEW retell_call_debug_view AS
            SELECT
                cs.id as session_id,
                cs.call_id,
                cs.started_at,
                cs.call_status,
                cs.function_call_count,
                cs.error_count,
                cs.avg_response_time_ms,
                COUNT(DISTINCT ft.id) as traced_functions,
                COUNT(DISTINCT el.id) as logged_errors,
                MAX(ft.duration_ms) as slowest_function_ms,
                MAX(el.severity) as highest_error_severity
            FROM retell_call_sessions cs
            LEFT JOIN retell_function_traces ft ON cs.id = ft.call_session_id
            LEFT JOIN retell_error_log el ON cs.id = el.call_session_id
            GROUP BY cs.id, cs.call_id, cs.started_at, cs.call_status,
                     cs.function_call_count, cs.error_count, cs.avg_response_time_ms
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS retell_call_debug_view');

        Schema::dropIfExists('retell_error_log');
        Schema::dropIfExists('retell_transcript_segments');
        Schema::dropIfExists('retell_function_traces');
        Schema::dropIfExists('retell_call_events');
        Schema::dropIfExists('retell_call_sessions');
    }
};
