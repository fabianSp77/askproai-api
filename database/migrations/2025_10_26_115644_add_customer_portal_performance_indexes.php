<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Customer Portal Performance Indexes
 *
 * Purpose: Optimize query performance for customer portal views
 *
 * Performance Impact:
 * - Call history: 10x-100x faster (removes table scans)
 * - Appointment lists: 5x-20x faster (indexed filtering)
 * - Dashboard widgets: 3x-10x faster (composite indexes)
 *
 * Query Patterns Optimized:
 * - Filter by company_id + branch_id + date range
 * - Customer call history sorted by date
 * - Active appointments for customer
 * - Transcript loading by call session
 *
 * @see /var/www/api-gateway/PERFORMANCE_ANALYSIS_AGENT_REPORT.md
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ================================================================
        // 1. RETELL_CALL_SESSIONS - Critical for call history performance
        // ================================================================

        Schema::table('retell_call_sessions', function (Blueprint $table) {
            // Branch filtering (managers view their branches)
            $table->index('branch_id', 'idx_retell_sessions_branch');

            // Customer call history (sorted by date, most recent first)
            // Query: WHERE customer_id = ? ORDER BY started_at DESC
            $table->index(['customer_id', 'started_at'], 'idx_retell_sessions_customer_date');

            // Company dashboard with status filtering
            // Query: WHERE company_id = ? AND call_status = ? ORDER BY started_at DESC
            $table->index(['company_id', 'started_at', 'call_status'], 'idx_retell_sessions_company_status');

            // Manager view: company + branch + date
            // Query: WHERE company_id = ? AND branch_id = ? ORDER BY started_at DESC
            $table->index(['company_id', 'branch_id', 'started_at'], 'idx_retell_sessions_company_branch_date');
        });

        // ================================================================
        // 2. APPOINTMENTS - Customer portal appointment views
        // ================================================================

        // Partial index for active appointments by customer
        // Query: WHERE customer_id = ? AND deleted_at IS NULL ORDER BY starts_at DESC
        // Note: PostgreSQL supports partial indexes for better performance
        DB::statement(
            'CREATE INDEX IF NOT EXISTS idx_appointments_customer_active
             ON appointments(customer_id, starts_at DESC)
             WHERE deleted_at IS NULL'
        );

        // Company-wide appointment dashboard
        // Query: WHERE company_id = ? AND deleted_at IS NULL ORDER BY starts_at
        DB::statement(
            'CREATE INDEX IF NOT EXISTS idx_appointments_company_active
             ON appointments(company_id, starts_at DESC)
             WHERE deleted_at IS NULL'
        );

        // ================================================================
        // 3. RETELL_TRANSCRIPT_SEGMENTS - Efficient transcript loading
        // ================================================================

        Schema::table('retell_transcript_segments', function (Blueprint $table) {
            // Transcript loading by call session, ordered by sequence
            // Query: WHERE call_session_id = ? ORDER BY segment_sequence
            $table->index(['call_session_id', 'segment_sequence'], 'idx_transcript_segments_session_seq');

            // Timeline view with timestamps
            // Query: WHERE call_session_id = ? ORDER BY occurred_at
            $table->index(['call_session_id', 'occurred_at'], 'idx_transcript_segments_session_time');
        });

        // ================================================================
        // 4. RETELL_FUNCTION_TRACES - Function call analysis
        // ================================================================

        Schema::table('retell_function_traces', function (Blueprint $table) {
            // Function traces by call session
            // Query: WHERE call_session_id = ? ORDER BY executed_at
            $table->index(['call_session_id', 'executed_at'], 'idx_function_traces_session_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ================================================================
        // Drop indexes in reverse order
        // ================================================================

        // retell_function_traces
        Schema::table('retell_function_traces', function (Blueprint $table) {
            $table->dropIndex('idx_function_traces_session_time');
        });

        // retell_transcript_segments
        Schema::table('retell_transcript_segments', function (Blueprint $table) {
            $table->dropIndex('idx_transcript_segments_session_seq');
            $table->dropIndex('idx_transcript_segments_session_time');
        });

        // appointments (partial indexes via raw SQL)
        DB::statement('DROP INDEX IF EXISTS idx_appointments_customer_active');
        DB::statement('DROP INDEX IF EXISTS idx_appointments_company_active');

        // retell_call_sessions
        Schema::table('retell_call_sessions', function (Blueprint $table) {
            $table->dropIndex('idx_retell_sessions_branch');
            $table->dropIndex('idx_retell_sessions_customer_date');
            $table->dropIndex('idx_retell_sessions_company_status');
            $table->dropIndex('idx_retell_sessions_company_branch_date');
        });
    }
};
