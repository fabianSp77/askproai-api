<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Performance Optimization for Appointments Table
     *
     * PROBLEM: Query performance degrades linearly with data growth
     * - Current: 16ms for 163 records
     * - Projected: 1s+ for 10K records
     * - Projected: 10s+ for 100K records
     *
     * SOLUTION: Optimize indexes for common query patterns
     * - Remove redundant single-column indexes
     * - Add composite indexes for filter combinations
     * - Optimize for Filament admin panel queries
     *
     * @author Performance Engineer
     * @date 2025-11-21
     */
    public function up(): void
    {
        // Skip in testing environment (SQLite doesn't support SHOW INDEXES)
        if (app()->environment('testing')) {
            return;
        }

        // Check current index count (MySQL limit is 128)
        $currentIndexes = DB::select("SHOW INDEXES FROM appointments");
        $indexCount = count(array_unique(array_column($currentIndexes, 'Key_name')));

        \Log::info("Current index count: {$indexCount}/128");

        // Drop redundant indexes that are covered by composite indexes
        $redundantIndexes = [
            'appointments_version_index',  // Rarely used alone
            'appointments_series_id_index', // Covered by customer_id_series_id
            'appointments_source_index',    // Low cardinality
            'appointments_is_recurring_index', // Low cardinality
            'idx_appointments_customer_id', // Will be replaced with composite
            'idx_appointments_service_id',  // Will be replaced with composite
            'idx_appointments_branch_id',   // Covered by other composites
            'idx_appointments_staff_id',    // Covered by other composites
        ];

        foreach ($redundantIndexes as $indexName) {
            if ($this->indexExists('appointments', $indexName)) {
                Schema::table('appointments', function (Blueprint $table) use ($indexName) {
                    $table->dropIndex($indexName);
                });
                \Log::info("Dropped redundant index: {$indexName}");
            }
        }

        // Add optimized composite indexes for common query patterns
        Schema::table('appointments', function (Blueprint $table) {
            // For main list page with filters (most common)
            if (!$this->indexExists('appointments', 'idx_appt_list_optimized')) {
                $table->index(
                    ['company_id', 'starts_at', 'status'],
                    'idx_appt_list_optimized'
                );
                \Log::info('Added index: idx_appt_list_optimized');
            }

            // For customer relationship queries
            if (!$this->indexExists('appointments', 'idx_appt_customer_optimized')) {
                $table->index(
                    ['customer_id', 'starts_at', 'status'],
                    'idx_appt_customer_optimized'
                );
                \Log::info('Added index: idx_appt_customer_optimized');
            }

            // For service analytics
            if (!$this->indexExists('appointments', 'idx_appt_service_optimized')) {
                $table->index(
                    ['service_id', 'starts_at', 'status'],
                    'idx_appt_service_optimized'
                );
                \Log::info('Added index: idx_appt_service_optimized');
            }

            // For staff scheduling views
            if (!$this->indexExists('appointments', 'idx_appt_staff_optimized')) {
                $table->index(
                    ['staff_id', 'starts_at', 'ends_at', 'status'],
                    'idx_appt_staff_optimized'
                );
                \Log::info('Added index: idx_appt_staff_optimized');
            }

            // For upcoming appointments widget
            if (!$this->indexExists('appointments', 'idx_appt_upcoming')) {
                $table->index(
                    ['starts_at', 'status', 'company_id'],
                    'idx_appt_upcoming'
                );
                \Log::info('Added index: idx_appt_upcoming');
            }

            // For cancellation tracking
            if (!$this->indexExists('appointments', 'idx_appt_cancellations')) {
                $table->index(
                    ['status', 'created_at', 'company_id'],
                    'idx_appt_cancellations'
                );
                \Log::info('Added index: idx_appt_cancellations');
            }
        });

        // Analyze table to update statistics
        DB::statement('ANALYZE TABLE appointments');

        // Log final index count
        $finalIndexes = DB::select("SHOW INDEXES FROM appointments");
        $finalCount = count(array_unique(array_column($finalIndexes, 'Key_name')));

        \Log::info("Performance optimization complete. Final index count: {$finalCount}/128");

        // Test query performance
        $this->testPerformance();
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Drop new composite indexes
            $newIndexes = [
                'idx_appt_list_optimized',
                'idx_appt_customer_optimized',
                'idx_appt_service_optimized',
                'idx_appt_staff_optimized',
                'idx_appt_upcoming',
                'idx_appt_cancellations'
            ];

            foreach ($newIndexes as $indexName) {
                if ($this->indexExists('appointments', $indexName)) {
                    $table->dropIndex($indexName);
                }
            }
        });

        \Log::warning('Performance optimization rolled back. Query performance may degrade.');
    }

    /**
     * Check if index exists
     */
    private function indexExists(string $table, string $index): bool
    {
        $indexes = DB::select("SHOW INDEXES FROM {$table} WHERE Key_name = ?", [$index]);
        return !empty($indexes);
    }

    /**
     * Test performance improvement
     */
    private function testPerformance(): void
    {
        $startTime = microtime(true);

        // Test common query pattern
        DB::select("
            SELECT SQL_NO_CACHE * FROM appointments
            WHERE company_id = 1
            AND starts_at >= NOW()
            AND status IN ('pending', 'confirmed')
            ORDER BY starts_at
            LIMIT 50
        ");

        $queryTime = (microtime(true) - $startTime) * 1000;

        \Log::info("Test query execution time: {$queryTime}ms");

        if ($queryTime > 50) {
            \Log::warning("Query still slow after optimization. Consider additional tuning.");
        } else {
            \Log::info("Performance optimization successful!");
        }
    }
};