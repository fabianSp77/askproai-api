<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add enrichment tracking fields to service_cases
 *
 * Part of 2-Phase Delivery-Gate Pattern:
 * Phase 1: Case created during call (enrichment_status = 'pending')
 * Phase 2: Case enriched after call (enrichment_status = 'enriched')
 *
 * @see /root/.claude/plans/zippy-skipping-lobster.md
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip if retell_call_sessions doesn't exist (SQLite testing without monitoring tables)
        $hasRetellTable = Schema::hasTable('retell_call_sessions');

        Schema::table('service_cases', function (Blueprint $table) use ($hasRetellTable) {
            // Enrichment status tracking
            $table->enum('enrichment_status', ['pending', 'enriched', 'timeout', 'skipped'])
                ->default('pending')
                ->after('output_status')
                ->comment('2-phase delivery: pending→enriched after call ends');

            // When enrichment completed
            $table->timestamp('enriched_at')
                ->nullable()
                ->after('enrichment_status');

            // Link to RetellCallSession for transcript/stats
            $table->uuid('retell_call_session_id')
                ->nullable()
                ->after('call_id');

            // Transcript statistics (populated from RetellCallSession)
            $table->unsignedInteger('transcript_segment_count')
                ->nullable()
                ->after('retell_call_session_id')
                ->comment('Number of transcript segments from call');

            $table->unsignedInteger('transcript_char_count')
                ->nullable()
                ->after('transcript_segment_count')
                ->comment('Total characters in transcript');

            // Index for finding pending enrichments
            $table->index(['enrichment_status', 'created_at'], 'idx_enrichment_status_created');

            // Foreign key to retell_call_sessions (only if table exists - skipped for SQLite)
            if ($hasRetellTable) {
                $table->foreign('retell_call_session_id')
                    ->references('id')
                    ->on('retell_call_sessions')
                    ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_cases', function (Blueprint $table) {
            // Drop foreign key first (only if it exists - might not exist for SQLite)
            if (Schema::hasTable('retell_call_sessions')) {
                $table->dropForeign(['retell_call_session_id']);
            }

            // Drop index
            $table->dropIndex('idx_enrichment_status_created');

            // Drop columns
            $table->dropColumn([
                'enrichment_status',
                'enriched_at',
                'retell_call_session_id',
                'transcript_segment_count',
                'transcript_char_count',
            ]);
        });
    }
};
