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
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                // Composite index for customer stats aggregations
                // Optimizes queries filtering by status, VIP, and date range
                if (!$this->indexExists('customers', 'idx_customers_stats_aggregation')) {
                    $table->index(['status', 'is_vip', 'created_at'], 'idx_customers_stats_aggregation');
                }

                // Composite index for revenue calculations
                // Optimizes total revenue queries with status filtering
                if (!$this->indexExists('customers', 'idx_customers_revenue_status')) {
                    $table->index(['status', 'total_revenue'], 'idx_customers_revenue_status');
                }

                // Composite index for journey status queries
                // Optimizes customer journey distribution queries
                if (!$this->indexExists('customers', 'idx_customers_journey_status')) {
                    $table->index(['journey_status', 'status'], 'idx_customers_journey_status');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if ($this->indexExists('customers', 'idx_customers_stats_aggregation')) {
                    $table->dropIndex('idx_customers_stats_aggregation');
                }
                if ($this->indexExists('customers', 'idx_customers_revenue_status')) {
                    $table->dropIndex('idx_customers_revenue_status');
                }
                if ($this->indexExists('customers', 'idx_customers_journey_status')) {
                    $table->dropIndex('idx_customers_journey_status');
                }
            });
        }
    }

    /**
     * Check if index exists
     */
    private function indexExists($table, $index): bool
    {
        $indexes = Schema::getIndexes($table);
        return collect($indexes)->pluck('name')->contains($index);
    }
};