<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Safety check - ensure we're not accidentally dropping critical indexes
        if (!$this->indexExists('calls', 'calls_retell_call_id_unique')) {
            throw new \Exception('Critical unique index calls_retell_call_id_unique not found. Aborting migration for safety.');
        }
        
        echo "Starting index optimization for calls table...\n";
        echo "Current index count: " . $this->getIndexCount('calls') . "\n";
        
        // Add optimized composite indexes first (before removing old ones)
        $this->addOptimizedIndexes();
        
        // Remove redundant and duplicate indexes
        $this->removeRedundantIndexes();
        
        // Analyze table for MySQL optimizer
        $this->analyzeTable();
        
        echo "Final index count: " . $this->getIndexCount('calls') . "\n";
        echo "Index optimization completed successfully!\n";
    }

    /**
     * Remove redundant and duplicate indexes
     */
    private function removeRedundantIndexes(): void
    {
        $redundantIndexes = [
            // Duplicate retell_call_id indexes (keep the unique one)
            'calls_retell_call_id_index',
            
            // Redundant single column indexes that are covered by composite indexes
            'calls_company_id_index', // Will be covered by composite indexes
            'calls_created_at_index', // Will be covered by composite indexes
            'calls_call_status_index', // Will be covered by composite indexes
            'calls_customer_id_index', // Will be covered by composite indexes
            
            // Duplicate composite indexes
            'idx_calls_company_date', // Duplicate of calls_company_created_at_index
            'idx_calls_created_company', // Different order of same columns
            'idx_calls_company_id', // Duplicate of calls_company_id_index
            'idx_calls_created_at', // Duplicate of calls_created_at_index
            'idx_calls_company_created', // Similar to other company+created indexes
            'calls_company_created_at_index', // Will be replaced with optimized version
            'calls_company_call_status_index', // Will be replaced with optimized version
            
            // Less useful single column indexes
            'calls_duration_sec_index', // Rarely queried alone
            'calls_cost_index', // Rarely queried alone
            'calls_from_number_index', // Covered by composite phone indexes
            'calls_to_number_index', // Covered by composite phone indexes
            'idx_calls_to_number', // Duplicate
            'idx_from_number', // Duplicate
            
            // Redundant status indexes
            'calls_status_index', // Will be covered by composite indexes
            'idx_calls_branch_status', // Less commonly used
            'calls_company_id_status_index', // Will be replaced with optimized version
            
            // Redundant timestamp indexes
            'calls_start_timestamp_index', // Less commonly used
            'idx_calls_company_start_timestamp', // Less commonly used
            'idx_company_timestamp', // Less commonly used
            'idx_calls_company_time', // Less commonly used
            
            // Less useful composite indexes
            'idx_calls_sentiment_date', // Rarely used together
            'idx_calls_status_duration', // Less common pattern
            'idx_calls_phone_normalized', // Very specific use case
            'idx_calls_company_agent', // Less common pattern
            'idx_calls_company_phone', // Less common pattern
            'idx_calls_company_status_time', // Replaced by better composite
            'idx_company_status', // Will be covered by better composite
            'idx_company_customer', // Less common pattern
            'idx_company_appointment', // Less common pattern
            'idx_status_created', // Wrong order for most queries
            'idx_calls_status_recent', // Wrong order for most queries
        ];

        foreach ($redundantIndexes as $indexName) {
            if ($this->indexExists('calls', $indexName)) {
                try {
                    Schema::table('calls', function (Blueprint $table) use ($indexName) {
                        $table->dropIndex($indexName);
                    });
                    echo "Dropped redundant index: {$indexName}\n";
                } catch (\Exception $e) {
                    // Continue if index doesn't exist or can't be dropped
                    echo "Could not drop index {$indexName}: " . $e->getMessage() . "\n";
                }
            }
        }
    }

    /**
     * Add optimized composite indexes for common query patterns
     */
    private function addOptimizedIndexes(): void
    {
        // Dashboard query patterns (company + time-based filtering + status)
        if (!$this->indexExists('calls', 'idx_dashboard_calls_primary')) {
            Schema::table('calls', function (Blueprint $table) {
                $table->index(['company_id', 'created_at', 'call_status'], 'idx_dashboard_calls_primary');
            });
            echo "Added dashboard primary index\n";
        }

        // API query patterns (company + status + time ordering)
        if (!$this->indexExists('calls', 'idx_api_calls_primary')) {
            Schema::table('calls', function (Blueprint $table) {
                $table->index(['company_id', 'status', 'created_at'], 'idx_api_calls_primary');
            });
            echo "Added API primary index\n";
        }

        // Recent calls widget (company + time descending)
        if (!$this->indexExists('calls', 'idx_recent_calls_optimized')) {
            Schema::table('calls', function (Blueprint $table) {
                // MySQL doesn't support DESC in index definition, but optimizer will use it efficiently
                $table->index(['company_id', 'created_at'], 'idx_recent_calls_optimized');
            });
            echo "Added recent calls optimized index\n";
        }

        // Phone number lookups (most critical for webhook processing)
        if (!$this->indexExists('calls', 'idx_phone_lookup_optimized')) {
            Schema::table('calls', function (Blueprint $table) {
                $table->index(['from_number', 'company_id', 'created_at'], 'idx_phone_lookup_optimized');
            });
            echo "Added phone lookup optimized index\n";
        }

        // Customer call history
        if (!$this->indexExists('calls', 'idx_customer_calls_optimized')) {
            Schema::table('calls', function (Blueprint $table) {
                $table->index(['customer_id', 'created_at', 'status'], 'idx_customer_calls_optimized');
            });
            echo "Added customer calls optimized index\n";
        }

        // Appointment-related calls
        if (!$this->indexExists('calls', 'idx_appointment_calls_optimized')) {
            Schema::table('calls', function (Blueprint $table) {
                $table->index(['appointment_id', 'created_at'], 'idx_appointment_calls_optimized');
            });
            echo "Added appointment calls optimized index\n";
        }

        // Retell webhook processing (critical path)
        if (!$this->indexExists('calls', 'idx_retell_processing_optimized')) {
            Schema::table('calls', function (Blueprint $table) {
                $table->index(['retell_call_id', 'company_id'], 'idx_retell_processing_optimized');
            });
            echo "Added Retell processing optimized index\n";
        }

        // Analytics and reporting (date-based aggregations)
        if (!$this->indexExists('calls', 'idx_analytics_optimized')) {
            Schema::table('calls', function (Blueprint $table) {
                $table->index(['company_id', 'start_timestamp', 'call_status', 'duration_sec'], 'idx_analytics_optimized');
            });
            echo "Added analytics optimized index\n";
        }

        // Branch-specific calls (for multi-location businesses)
        if (!$this->indexExists('calls', 'idx_branch_calls_optimized')) {
            Schema::table('calls', function (Blueprint $table) {
                $table->index(['branch_id', 'created_at', 'status'], 'idx_branch_calls_optimized');
            });
            echo "Added branch calls optimized index\n";
        }

        // Call status transitions and monitoring
        if (!$this->indexExists('calls', 'idx_call_monitoring_optimized')) {
            Schema::table('calls', function (Blueprint $table) {
                $table->index(['call_status', 'company_id', 'updated_at'], 'idx_call_monitoring_optimized');
            });
            echo "Added call monitoring optimized index\n";
        }
    }

    /**
     * Analyze table for MySQL optimizer
     */
    private function analyzeTable(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            try {
                DB::statement('ANALYZE TABLE calls');
                echo "Analyzed calls table for MySQL optimizer\n";
            } catch (\Exception $e) {
                echo "Could not analyze table: " . $e->getMessage() . "\n";
            }
        }
    }

    /**
     * Check if an index exists
     */
    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $connection = config('database.default');
            $database = config("database.connections.{$connection}.database");
            
            $result = DB::select("
                SELECT COUNT(*) as count 
                FROM information_schema.statistics 
                WHERE table_schema = ? 
                AND table_name = ? 
                AND index_name = ?
            ", [$database, $table, $indexName]);
            
            return $result[0]->count > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the count of indexes on a table
     */
    private function getIndexCount(string $table): int
    {
        try {
            $connection = config('database.default');
            $database = config("database.connections.{$connection}.database");
            
            $result = DB::select("
                SELECT COUNT(DISTINCT index_name) as count 
                FROM information_schema.statistics 
                WHERE table_schema = ? AND table_name = ?
            ", [$database, $table]);
            
            return $result[0]->count;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the optimized indexes we added
        $optimizedIndexes = [
            'idx_dashboard_calls_primary',
            'idx_api_calls_primary', 
            'idx_recent_calls_optimized',
            'idx_phone_lookup_optimized',
            'idx_customer_calls_optimized',
            'idx_appointment_calls_optimized',
            'idx_retell_processing_optimized',
            'idx_analytics_optimized',
            'idx_branch_calls_optimized',
            'idx_call_monitoring_optimized',
        ];

        foreach ($optimizedIndexes as $indexName) {
            if ($this->indexExists('calls', $indexName)) {
                try {
                    Schema::table('calls', function (Blueprint $table) use ($indexName) {
                        $table->dropIndex($indexName);
                    });
                } catch (\Exception $e) {
                    // Continue if index can't be dropped
                }
            }
        }

        // Recreate some of the essential indexes that were removed
        $essentialIndexes = [
            ['calls_company_id_index', ['company_id']],
            ['calls_created_at_index', ['created_at']],
            ['calls_call_status_index', ['call_status']],
            ['calls_customer_id_index', ['customer_id']],
            ['calls_retell_call_id_index', ['retell_call_id']], // Non-unique version
        ];

        foreach ($essentialIndexes as [$indexName, $columns]) {
            if (!$this->indexExists('calls', $indexName)) {
                try {
                    Schema::table('calls', function (Blueprint $table) use ($columns, $indexName) {
                        $table->index($columns, $indexName);
                    });
                } catch (\Exception $e) {
                    // Continue if index can't be created
                }
            }
        }
    }
};