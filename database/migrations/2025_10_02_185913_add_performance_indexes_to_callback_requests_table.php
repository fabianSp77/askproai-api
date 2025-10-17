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
        if (!Schema::hasTable('callback_requests')) {
            return;
        }

        Schema::table('callback_requests', function (Blueprint $table) {
            // Index for navigation badge query (most frequently used)
            if (!$this->indexExists('callback_requests', 'idx_callback_status') &&
                Schema::hasColumn('callback_requests', 'status')) {
                $table->index(['status'], 'idx_callback_status');
            }

            // Composite index for overdue queries
            if (!$this->indexExists('callback_requests', 'idx_callback_overdue') &&
                Schema::hasColumn('callback_requests', 'expires_at')) {
                $table->index(['expires_at', 'status'], 'idx_callback_overdue');
            }

            // Index for priority sorting
            if (!$this->indexExists('callback_requests', 'idx_callback_priority') &&
                Schema::hasColumn('callback_requests', 'priority')) {
                $table->index(['priority', 'expires_at'], 'idx_callback_priority');
            }

            // Index for branch filtering
            if (!$this->indexExists('callback_requests', 'idx_callback_branch_status') &&
                Schema::hasColumn('callback_requests', 'branch_id')) {
                $table->index(['branch_id', 'status'], 'idx_callback_branch_status');
            }

            // Index for date range queries
            if (!$this->indexExists('callback_requests', 'idx_callback_created')) {
                $table->index(['created_at'], 'idx_callback_created');
            }

            // Index for assigned callbacks
            if (!$this->indexExists('callback_requests', 'idx_callback_assigned') &&
                Schema::hasColumn('callback_requests', 'assigned_to')) {
                $table->index(['assigned_to', 'status'], 'idx_callback_assigned');
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $indexes = Schema::getIndexes($table);
        return collect($indexes)->pluck('name')->contains($index);
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
