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
        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                // Composite index for revenue aggregations with date and status filtering
                // Optimizes monthly revenue queries in AppointmentStats widget
                if (!$this->indexExists('appointments', 'idx_appointments_revenue_date_status')) {
                    $table->index(['starts_at', 'status', 'price'], 'idx_appointments_revenue_date_status');
                }

                // Composite index for completion rate calculations
                // Optimizes queries filtering by status AND date range
                if (!$this->indexExists('appointments', 'idx_appointments_completion_tracking')) {
                    $table->index(['status', 'starts_at', 'created_at'], 'idx_appointments_completion_tracking');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                if ($this->indexExists('appointments', 'idx_appointments_revenue_date_status')) {
                    $table->dropIndex('idx_appointments_revenue_date_status');
                }
                if ($this->indexExists('appointments', 'idx_appointments_completion_tracking')) {
                    $table->dropIndex('idx_appointments_completion_tracking');
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
