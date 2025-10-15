<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Cal.com Sync Verification System
     * Tracks synchronization status between local DB and Cal.com
     * Flags discrepancies for manual review
     */
    public function up(): void
    {
        if (!Schema::hasTable('appointments')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table) {
            // Sync status tracking
            if (!Schema::hasColumn('appointments', 'calcom_sync_status')) {
                $table->enum('calcom_sync_status', [
                    'synced',               // Verified present in both systems
                    'pending',              // Created locally, awaiting Cal.com confirmation
                    'failed',               // Sync attempt failed
                    'orphaned_local',       // Exists in DB but not in Cal.com
                    'orphaned_calcom',      // Exists in Cal.com but not in DB
                    'verification_pending'  // Scheduled for verification
                ])->default('pending')->after('calcom_v2_booking_id')
                  ->comment('Cal.com synchronization status');
            }

            // Sync attempt tracking
            if (!Schema::hasColumn('appointments', 'last_sync_attempt_at')) {
                $table->timestamp('last_sync_attempt_at')->nullable()
                    ->after('calcom_sync_status')
                    ->comment('Last verification attempt timestamp');
            }

            if (!Schema::hasColumn('appointments', 'sync_attempt_count')) {
                $table->unsignedTinyInteger('sync_attempt_count')->default(0)
                    ->after('last_sync_attempt_at')
                    ->comment('Number of sync verification attempts');
            }

            // Error details
            if (!Schema::hasColumn('appointments', 'sync_error_message')) {
                $table->text('sync_error_message')->nullable()
                    ->after('sync_attempt_count')
                    ->comment('Last sync error details');
            }

            if (!Schema::hasColumn('appointments', 'sync_error_code')) {
                $table->string('sync_error_code', 50)->nullable()
                    ->after('sync_error_message')
                    ->comment('Error classification code');
            }

            // Recovery tracking
            if (!Schema::hasColumn('appointments', 'sync_verified_at')) {
                $table->timestamp('sync_verified_at')->nullable()
                    ->after('sync_error_code')
                    ->comment('Last successful verification timestamp');
            }

            if (!Schema::hasColumn('appointments', 'requires_manual_review')) {
                $table->boolean('requires_manual_review')->default(false)
                    ->after('sync_verified_at')
                    ->comment('Flagged for admin attention');
            }

            if (!Schema::hasColumn('appointments', 'manual_review_flagged_at')) {
                $table->timestamp('manual_review_flagged_at')->nullable()
                    ->after('requires_manual_review')
                    ->comment('When flagged for manual review');
            }
        });

        // Add indexes for performance (check if they don't exist)
        if (!$this->indexExists('appointments', 'idx_appointments_sync_status')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->index(['calcom_sync_status', 'company_id'], 'idx_appointments_sync_status');
            });
        }

        if (!$this->indexExists('appointments', 'idx_appointments_manual_review')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->index(['requires_manual_review', 'company_id'], 'idx_appointments_manual_review');
            });
        }

        if (!$this->indexExists('appointments', 'idx_appointments_last_sync')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->index(['last_sync_attempt_at'], 'idx_appointments_last_sync');
            });
        }

        // Log migration success
        \Log::info('✅ Migration completed: add_calcom_sync_tracking_to_appointments', [
            'columns_added' => [
                'calcom_sync_status',
                'last_sync_attempt_at',
                'sync_attempt_count',
                'sync_error_message',
                'sync_error_code',
                'sync_verified_at',
                'requires_manual_review',
                'manual_review_flagged_at'
            ],
            'indexes_added' => [
                'idx_appointments_sync_status',
                'idx_appointments_manual_review',
                'idx_appointments_last_sync'
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('appointments')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table) {
            // Drop indexes first
            if ($this->indexExists('appointments', 'idx_appointments_sync_status')) {
                $table->dropIndex('idx_appointments_sync_status');
            }

            if ($this->indexExists('appointments', 'idx_appointments_manual_review')) {
                $table->dropIndex('idx_appointments_manual_review');
            }

            if ($this->indexExists('appointments', 'idx_appointments_last_sync')) {
                $table->dropIndex('idx_appointments_last_sync');
            }

            // Drop columns
            $columnsToRemove = [
                'calcom_sync_status',
                'last_sync_attempt_at',
                'sync_attempt_count',
                'sync_error_message',
                'sync_error_code',
                'sync_verified_at',
                'requires_manual_review',
                'manual_review_flagged_at'
            ];

            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('appointments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        \Log::info('Migration rolled back: add_calcom_sync_tracking_to_appointments');
    }

    /**
     * Check if index exists
     */
    private function indexExists(string $table, string $index): bool
    {
        try {
            $indexes = Schema::getIndexes($table);
            return collect($indexes)->pluck('name')->contains($index);
        } catch (\Exception $e) {
            return false;
        }
    }
};
