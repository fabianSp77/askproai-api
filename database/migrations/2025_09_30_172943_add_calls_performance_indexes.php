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
        if (Schema::hasTable('calls')) {
            Schema::table('calls', function (Blueprint $table) {
                // Composite index for cost aggregations with date filtering
                // NEW: Optimizes monthly cost queries in CallStatsOverview widget
                if (!$this->indexExists('calls', 'idx_calls_cost_date')) {
                    $table->index(['created_at', 'cost_cents'], 'idx_calls_cost_date');
                }

                // Composite index for success/appointment filtering with date
                // NEW: Optimizes queries filtering by both call_successful AND appointment_made
                if (!$this->indexExists('calls', 'idx_calls_success_appointment_date')) {
                    $table->index(['call_successful', 'appointment_made', 'created_at'], 'idx_calls_success_appointment_date');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('calls')) {
            Schema::table('calls', function (Blueprint $table) {
                if ($this->indexExists('calls', 'idx_calls_cost_date')) {
                    $table->dropIndex('idx_calls_cost_date');
                }
                if ($this->indexExists('calls', 'idx_calls_success_appointment_date')) {
                    $table->dropIndex('idx_calls_success_appointment_date');
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
