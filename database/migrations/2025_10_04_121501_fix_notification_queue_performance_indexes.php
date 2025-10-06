<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fix for broken P4 performance indexes migration
     *
     * Original migration 2025_10_04_110927 used wrong table name:
     * - Used: 'notification_queues' (PLURAL - doesn't exist)
     * - Should be: 'notification_queue' (SINGULAR - actual table name)
     *
     * This migration creates the intended indexes on the CORRECT table.
     */
    public function up(): void
    {
        Schema::table('notification_queue', function (Blueprint $table) {
            // Create indexes that were supposed to be created by 2025_10_04_110927
            $table->index(['status', 'created_at'], 'idx_nq_status_created');
            $table->index(['channel', 'created_at', 'status'], 'idx_nq_channel_created_status');
            $table->index(['created_at', 'status'], 'idx_nq_created_status');

            // Additional performance index for company_id queries (from SEC-003 fixes)
            $table->index(['company_id', 'status', 'created_at'], 'idx_nq_company_status_created');
            $table->index(['company_id', 'channel', 'created_at'], 'idx_nq_company_channel_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_queue', function (Blueprint $table) {
            $table->dropIndex('idx_nq_status_created');
            $table->dropIndex('idx_nq_channel_created_status');
            $table->dropIndex('idx_nq_created_status');
            $table->dropIndex('idx_nq_company_status_created');
            $table->dropIndex('idx_nq_company_channel_created');
        });
    }
};
