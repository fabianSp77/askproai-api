<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('callback_requests', function (Blueprint $table) {
            // Index for navigation badge query (most frequently used)
            $table->index(['status'], 'idx_callback_status');

            // Composite index for overdue queries
            $table->index(['expires_at', 'status'], 'idx_callback_overdue');

            // Index for priority sorting
            $table->index(['priority', 'expires_at'], 'idx_callback_priority');

            // Index for branch filtering
            $table->index(['branch_id', 'status'], 'idx_callback_branch_status');

            // Index for date range queries
            $table->index(['created_at'], 'idx_callback_created');

            // Index for assigned callbacks
            $table->index(['assigned_to', 'status'], 'idx_callback_assigned');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('callback_requests', function (Blueprint $table) {
            $table->dropIndex('idx_callback_status');
            $table->dropIndex('idx_callback_overdue');
            $table->dropIndex('idx_callback_priority');
            $table->dropIndex('idx_callback_branch_status');
            $table->dropIndex('idx_callback_created');
            $table->dropIndex('idx_callback_assigned');
        });
    }
};
