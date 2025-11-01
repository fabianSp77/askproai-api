<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add sync orchestration fields for bidirectional Cal.com sync
     * This enables loop prevention and sync monitoring for appointments
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // ═══════════════════════════════════════════════════════════
            // SYNC ORIGIN TRACKING (Loop Prevention)
            // ═══════════════════════════════════════════════════════════

            // Origin system that initiated this appointment
            // Used to prevent infinite webhook loops:
            // - 'calcom': Created/modified via Cal.com webhook → DON'T sync back
            // - 'retell': Created/modified via Retell AI phone → SYNC to Cal.com
            // - 'admin': Created/modified via Admin UI → SYNC to Cal.com
            // - 'api': Created/modified via API → SYNC to Cal.com
            if (!Schema::hasColumn('appointments', 'sync_origin')) {
                $table->enum('sync_origin', ['calcom', 'retell', 'admin', 'api', 'system'])
                      ->nullable()
                      ->after('calcom_sync_status')
                      ->comment('System that initiated this appointment (for loop prevention)');
            }

            // When the sync was initiated (for tracking/debugging)
            if (!Schema::hasColumn('appointments', 'sync_initiated_at')) {
                $table->timestamp('sync_initiated_at')
                      ->nullable()
                      ->after('sync_origin')
                      ->comment('When sync to Cal.com was initiated');
            }

            // User who initiated the sync (for audit trail)
            if (!Schema::hasColumn('appointments', 'sync_initiated_by_user_id')) {
                $table->foreignId('sync_initiated_by_user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->after('sync_initiated_at')
                  ->comment('User who initiated sync (NULL for system/phone)');
            }

            // ═══════════════════════════════════════════════════════════
            // SYNC QUEUE TRACKING (Monitoring & Debugging)
            // ═══════════════════════════════════════════════════════════

            // Laravel job ID for tracking sync job status
            if (!Schema::hasColumn('appointments', 'sync_job_id')) {
                $table->string('sync_job_id', 100)
                      ->nullable()
                      ->after('sync_initiated_by_user_id')
                      ->comment('Laravel job ID for tracking sync progress');
            }

            // ═══════════════════════════════════════════════════════════
            // SYNC RETRY TRACKING
            // ═══════════════════════════════════════════════════════════

            // Number of sync attempts (for exponential backoff)
            if (!Schema::hasColumn('appointments', 'sync_attempt_count')) {
                $table->unsignedTinyInteger('sync_attempt_count')
                      ->default(0)
                      ->after('sync_job_id')
                      ->comment('Number of sync attempts (for retry logic)');
            }

            // ═══════════════════════════════════════════════════════════
            // MANUAL REVIEW FLAGS
            // ═══════════════════════════════════════════════════════════

            // Flag for appointments requiring manual review (after max retries)
            if (!Schema::hasColumn('appointments', 'requires_manual_review')) {
                $table->boolean('requires_manual_review')
                      ->default(false)
                      ->after('sync_attempt_count')
                      ->comment('True if sync failed after max retries');
            }

            // When the appointment was flagged for manual review
            if (!Schema::hasColumn('appointments', 'manual_review_flagged_at')) {
                $table->timestamp('manual_review_flagged_at')
                      ->nullable()
                      ->after('requires_manual_review')
                      ->comment('When appointment was flagged for manual review');
            }

            // ═══════════════════════════════════════════════════════════
            // INDEXES (Performance Optimization)
            // ═══════════════════════════════════════════════════════════

            // Index for sync origin queries (e.g., "find all retell appointments to sync")
            $table->index(['sync_origin', 'company_id'], 'idx_sync_origin_company');

            // Index for manual review dashboard queries
            $table->index(['requires_manual_review', 'manual_review_flagged_at'], 'idx_manual_review');

            // Index for pending sync jobs
            $table->index(['calcom_sync_status', 'sync_job_id'], 'idx_sync_status_job');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('idx_sync_origin_company');
            $table->dropIndex('idx_manual_review');
            $table->dropIndex('idx_sync_status_job');

            // Drop foreign key
            $table->dropForeign(['sync_initiated_by_user_id']);

            // Drop columns in reverse order
            $table->dropColumn([
                'manual_review_flagged_at',
                'requires_manual_review',
                'sync_attempt_count',
                'sync_job_id',
                'sync_initiated_by_user_id',
                'sync_initiated_at',
                'sync_origin',
            ]);
        });
    }
};
