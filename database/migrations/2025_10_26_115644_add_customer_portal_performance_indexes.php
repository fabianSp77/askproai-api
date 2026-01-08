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

        if (Schema::hasTable('retell_call_sessions')) {
            $existingIndexes = collect(Schema::getIndexes('retell_call_sessions'))->pluck('name')->toArray();

            Schema::table('retell_call_sessions', function (Blueprint $table) use ($existingIndexes) {
                // Branch filtering (managers view their branches)
                if (!in_array('idx_retell_sessions_branch', $existingIndexes) && Schema::hasColumn('retell_call_sessions', 'branch_id')) {
                    $table->index('branch_id', 'idx_retell_sessions_branch');
                }

                // Customer call history (sorted by date, most recent first)
                // Query: WHERE customer_id = ? ORDER BY started_at DESC
                if (!in_array('idx_retell_sessions_customer_date', $existingIndexes) &&
                    Schema::hasColumn('retell_call_sessions', 'customer_id') &&
                    Schema::hasColumn('retell_call_sessions', 'started_at')) {
                    $table->index(['customer_id', 'started_at'], 'idx_retell_sessions_customer_date');
                }

                // Company dashboard with status filtering
                // Query: WHERE company_id = ? AND call_status = ? ORDER BY started_at DESC
                if (!in_array('idx_retell_sessions_company_status', $existingIndexes) &&
                    Schema::hasColumn('retell_call_sessions', 'company_id') &&
                    Schema::hasColumn('retell_call_sessions', 'started_at') &&
                    Schema::hasColumn('retell_call_sessions', 'call_status')) {
                    $table->index(['company_id', 'started_at', 'call_status'], 'idx_retell_sessions_company_status');
                }

                // Manager view: company + branch + date
                // Query: WHERE company_id = ? AND branch_id = ? ORDER BY started_at DESC
                if (!in_array('idx_retell_sessions_company_branch_date', $existingIndexes) &&
                    Schema::hasColumn('retell_call_sessions', 'company_id') &&
                    Schema::hasColumn('retell_call_sessions', 'branch_id') &&
                    Schema::hasColumn('retell_call_sessions', 'started_at')) {
                    $table->index(['company_id', 'branch_id', 'started_at'], 'idx_retell_sessions_company_branch_date');
                }
            });
        }

        // ================================================================
        // 2. APPOINTMENTS - Customer portal appointment views
        // ================================================================

        // MySQL/MariaDB doesn't support partial indexes (WHERE clause)
        // Use regular composite indexes instead
        if (Schema::hasTable('appointments')) {
            $existingIndexes = collect(Schema::getIndexes('appointments'))->pluck('name')->toArray();

            Schema::table('appointments', function (Blueprint $table) use ($existingIndexes) {
                // Customer appointments with date ordering
                // Query: WHERE customer_id = ? ORDER BY starts_at DESC
                if (!in_array('idx_appointments_customer_active', $existingIndexes) &&
                    Schema::hasColumn('appointments', 'customer_id') &&
                    Schema::hasColumn('appointments', 'starts_at')) {
                    $table->index(['customer_id', 'starts_at'], 'idx_appointments_customer_active');
                }

                // Company-wide appointment dashboard
                // Query: WHERE company_id = ? ORDER BY starts_at
                if (!in_array('idx_appointments_company_active', $existingIndexes) &&
                    Schema::hasColumn('appointments', 'company_id') &&
                    Schema::hasColumn('appointments', 'starts_at')) {
                    $table->index(['company_id', 'starts_at'], 'idx_appointments_company_active');
                }
            });
        }

        // ================================================================
        // 3. RETELL_TRANSCRIPT_SEGMENTS - Efficient transcript loading
        // ================================================================

        if (Schema::hasTable('retell_transcript_segments')) {
            $existingIndexes = collect(Schema::getIndexes('retell_transcript_segments'))->pluck('name')->toArray();

            Schema::table('retell_transcript_segments', function (Blueprint $table) use ($existingIndexes) {
                // Transcript loading by call session, ordered by sequence
                // Query: WHERE call_session_id = ? ORDER BY segment_sequence
                if (!in_array('idx_transcript_segments_session_seq', $existingIndexes) &&
                    Schema::hasColumn('retell_transcript_segments', 'call_session_id') &&
                    Schema::hasColumn('retell_transcript_segments', 'segment_sequence')) {
                    $table->index(['call_session_id', 'segment_sequence'], 'idx_transcript_segments_session_seq');
                }

                // Timeline view with timestamps
                // Query: WHERE call_session_id = ? ORDER BY occurred_at
                if (!in_array('idx_transcript_segments_session_time', $existingIndexes) &&
                    Schema::hasColumn('retell_transcript_segments', 'call_session_id') &&
                    Schema::hasColumn('retell_transcript_segments', 'occurred_at')) {
                    $table->index(['call_session_id', 'occurred_at'], 'idx_transcript_segments_session_time');
                }
            });
        }

        // ================================================================
        // 4. RETELL_FUNCTION_TRACES - Function call analysis
        // ================================================================

        if (Schema::hasTable('retell_function_traces')) {
            $existingIndexes = collect(Schema::getIndexes('retell_function_traces'))->pluck('name')->toArray();

            Schema::table('retell_function_traces', function (Blueprint $table) use ($existingIndexes) {
                // Function traces by call session
                // Query: WHERE call_session_id = ? ORDER BY executed_at
                if (!in_array('idx_function_traces_session_time', $existingIndexes) &&
                    Schema::hasColumn('retell_function_traces', 'call_session_id') &&
                    Schema::hasColumn('retell_function_traces', 'executed_at')) {
                    $table->index(['call_session_id', 'executed_at'], 'idx_function_traces_session_time');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ================================================================
        // Drop indexes in reverse order (with existence checks)
        // ================================================================

        // retell_function_traces
        if (Schema::hasTable('retell_function_traces')) {
            $existingIndexes = collect(Schema::getIndexes('retell_function_traces'))->pluck('name')->toArray();
            Schema::table('retell_function_traces', function (Blueprint $table) use ($existingIndexes) {
                if (in_array('idx_function_traces_session_time', $existingIndexes)) {
                    $table->dropIndex('idx_function_traces_session_time');
                }
            });
        }

        // retell_transcript_segments
        if (Schema::hasTable('retell_transcript_segments')) {
            $existingIndexes = collect(Schema::getIndexes('retell_transcript_segments'))->pluck('name')->toArray();
            Schema::table('retell_transcript_segments', function (Blueprint $table) use ($existingIndexes) {
                if (in_array('idx_transcript_segments_session_seq', $existingIndexes)) {
                    $table->dropIndex('idx_transcript_segments_session_seq');
                }
                if (in_array('idx_transcript_segments_session_time', $existingIndexes)) {
                    $table->dropIndex('idx_transcript_segments_session_time');
                }
            });
        }

        // appointments
        if (Schema::hasTable('appointments')) {
            $existingIndexes = collect(Schema::getIndexes('appointments'))->pluck('name')->toArray();
            Schema::table('appointments', function (Blueprint $table) use ($existingIndexes) {
                if (in_array('idx_appointments_customer_active', $existingIndexes)) {
                    $table->dropIndex('idx_appointments_customer_active');
                }
                if (in_array('idx_appointments_company_active', $existingIndexes)) {
                    $table->dropIndex('idx_appointments_company_active');
                }
            });
        }

        // retell_call_sessions
        if (Schema::hasTable('retell_call_sessions')) {
            $existingIndexes = collect(Schema::getIndexes('retell_call_sessions'))->pluck('name')->toArray();
            Schema::table('retell_call_sessions', function (Blueprint $table) use ($existingIndexes) {
                if (in_array('idx_retell_sessions_branch', $existingIndexes)) {
                    $table->dropIndex('idx_retell_sessions_branch');
                }
                if (in_array('idx_retell_sessions_customer_date', $existingIndexes)) {
                    $table->dropIndex('idx_retell_sessions_customer_date');
                }
                if (in_array('idx_retell_sessions_company_status', $existingIndexes)) {
                    $table->dropIndex('idx_retell_sessions_company_status');
                }
                if (in_array('idx_retell_sessions_company_branch_date', $existingIndexes)) {
                    $table->dropIndex('idx_retell_sessions_company_branch_date');
                }
            });
        }
    }
};
