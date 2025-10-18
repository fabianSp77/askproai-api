<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 2: Transactional Consistency - Add Idempotency Keys
     *
     * PURPOSE:
     * - Prevent duplicate bookings from retried requests
     * - Track webhook processing to prevent duplicate webhooks
     * - Enable transaction rollback if local booking fails after Cal.com succeeds
     *
     * @author Phase 2 Implementation
     * @date 2025-10-18
     */
    public function up(): void
    {
        // Add idempotency key to appointments
        Schema::table('appointments', function (Blueprint $table) {
            // Idempotency key: UUID v5 generated from customer+service+time+source
            // NOTE: Not using unique index due to 64-index limit on appointments table
            // Uniqueness enforced at application level via IdempotencyCache
            if (!Schema::hasColumn('appointments', 'idempotency_key')) {
                $table->string('idempotency_key', 36)
                    ->nullable()
                    ->after('id')
                    ->comment('UUID v5 for deduplication of retried requests');

                \Log::info('✅ Added idempotency_key column to appointments');
            }

            // Webhook deduplication tracking
            if (!Schema::hasColumn('appointments', 'webhook_id')) {
                $table->string('webhook_id', 100)
                    ->nullable()
                    ->after('idempotency_key')
                    ->comment('Cal.com webhook ID for deduplication');

                \Log::info('✅ Added webhook_id column to appointments');
            }

            // Sync failure tracking
            if (!Schema::hasColumn('appointments', 'sync_attempt_count')) {
                $table->integer('sync_attempt_count')
                    ->default(0)
                    ->after('calcom_sync_status')
                    ->comment('Number of sync attempts (for reconciliation)');

                \Log::info('✅ Added sync_attempt_count column to appointments');
            }

            if (!Schema::hasColumn('appointments', 'last_sync_attempted_at')) {
                $table->timestamp('last_sync_attempted_at')
                    ->nullable()
                    ->after('sync_attempt_count')
                    ->comment('Timestamp of last sync attempt');

                \Log::info('✅ Added last_sync_attempted_at column to appointments');
            }
        });

        // Create sync_failures tracking table
        if (!Schema::hasTable('sync_failures')) {
            Schema::create('sync_failures', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('appointment_id')->nullable();
                $table->string('calcom_booking_id', 100)->nullable();
                $table->string('failure_type'); // 'calcom_success_local_failed', 'webhook_duplicate', etc
                $table->text('error_message');
                $table->string('status')->default('pending'); // pending, resolved, manual_review
                $table->integer('attempt_count')->default(1);
                $table->timestamp('last_attempt_at')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->index('appointment_id');
                $table->index('calcom_booking_id');
                $table->index('status');
                $table->index('created_at');

                \Log::info('✅ Created sync_failures table');
            });
        }

        \Log::info('📊 Phase 2 Migration: Idempotency Keys Setup Complete');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasColumn('appointments', 'idempotency_key')) {
                $table->dropColumn('idempotency_key');
            }
            if (Schema::hasColumn('appointments', 'webhook_id')) {
                $table->dropColumn('webhook_id');
            }
            if (Schema::hasColumn('appointments', 'sync_attempt_count')) {
                $table->dropColumn('sync_attempt_count');
            }
            if (Schema::hasColumn('appointments', 'last_sync_attempted_at')) {
                $table->dropColumn('last_sync_attempted_at');
            }
        });

        if (Schema::hasTable('sync_failures')) {
            Schema::dropIfExists('sync_failures');
        }
    }
};
