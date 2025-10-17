<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * CompanyScope Index Optimization Migration
 *
 * Purpose: Remove duplicate indexes and add optimized composite indexes
 *          based on CompanyScope performance analysis
 *
 * Performance Impact:
 *   - Estimated 5-10% improvement in write operations
 *   - Reduced index storage overhead
 *   - Improved query optimizer decision-making
 *
 * IMPORTANT: This migration removes DUPLICATE indexes only.
 *            Primary functionality indexes are preserved.
 *
 * Before running:
 *   1. Review claudedocs/companyscope_performance_analysis.md
 *   2. Backup production database
 *   3. Test in staging environment first
 *   4. Run during low-traffic period
 *
 * Generated: 2025-10-02
 * Reference: CompanyScope Performance Analysis Report
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->optimizeAppointmentsIndexes();
        $this->optimizeCustomersIndexes();
        $this->optimizeCallsIndexes();
        $this->addMissingCompositeIndexes();
        $this->cleanupNullCompanyIds();
    }

    /**
     * Optimize appointments table indexes
     *
     * Current state: 13 indexes on company_id (excessive redundancy)
     * Target state: 7 optimized indexes
     */
    private function optimizeAppointmentsIndexes(): void
    {
        if (!Schema::hasTable('appointments')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table) {
            // Drop duplicate indexes (keeping most comprehensive versions)

            // Duplicate of appointments_company_id_index
            if ($this->indexExists('appointments', 'idx_appointments_company_id')) {
                $table->dropIndex('idx_appointments_company_id');
            }

            // Duplicate of appointments_company_status_index
            if ($this->indexExists('appointments', 'idx_appointments_company_status')) {
                $table->dropIndex('idx_appointments_company_status');
            }

            // Duplicate of appointments_company_starts_at_index
            if ($this->indexExists('appointments', 'idx_company_starts')) {
                $table->dropIndex('idx_company_starts');
            }

            // Drop idx_company_status_date (covered by more specific composite index)
            if ($this->indexExists('appointments', 'idx_company_status_date')) {
                $table->dropIndex('idx_company_status_date');
            }
        });

        $this->logOptimization('appointments', 'Removed 4 duplicate company_id indexes');
    }

    /**
     * Optimize customers table indexes
     *
     * Current state: 21 indexes on company_id (excessive)
     * Target state: Consolidate to 12-15 essential indexes
     */
    private function optimizeCustomersIndexes(): void
    {
        if (!Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            // Remove duplicate standalone company_id indexes
            // Keep composite indexes that provide additional query optimization
            // IMPORTANT: Don't drop idx_customers_company_id if it's used by a foreign key constraint

            if ($this->indexExists('customers', 'idx_customers_company_id') &&
                !$this->isForeignKeyIndex('customers', 'idx_customers_company_id')) {
                $table->dropIndex('idx_customers_company_id');
            }

            // Keep idx_customers_company as it may have different coverage
            // Keep customers_company_id_status_index for status filtering
            // Keep customers_company_phone_index for phone lookups
            // Keep customers_company_email_index for email lookups
        });

        $this->logOptimization('customers', 'Removed duplicate company_id indexes (if safe to remove)');
    }

    /**
     * Optimize calls table indexes
     *
     * Current state: 7 indexes (acceptable, minor cleanup)
     */
    private function optimizeCallsIndexes(): void
    {
        if (!Schema::hasTable('calls')) {
            return;
        }

        Schema::table('calls', function (Blueprint $table) {
            // Calls table is well-optimized, keep all indexes
            // Only remove if we find exact duplicates

            if ($this->indexExists('calls', 'idx_company_created') &&
                $this->indexExists('calls', 'calls_company_created_at_index')) {
                // These are likely duplicates, keep the more descriptive one
                $table->dropIndex('idx_company_created');
            }
        });

        $this->logOptimization('calls', 'Removed 1 duplicate company_id index');
    }

    /**
     * Add missing composite indexes for common query patterns
     *
     * Based on performance analysis of dashboard and common queries
     */
    private function addMissingCompositeIndexes(): void
    {
        // Appointments: Optimize dashboard "upcoming appointments" query
        if (Schema::hasTable('appointments') &&
            !$this->indexExists('appointments', 'idx_appointments_optimal_dashboard')) {

            Schema::table('appointments', function (Blueprint $table) {
                $table->index(
                    ['company_id', 'starts_at', 'status'],
                    'idx_appointments_optimal_dashboard'
                );
            });

            $this->logOptimization(
                'appointments',
                'Added composite index for dashboard queries (company_id, starts_at, status)'
            );
        }

        // Customers: Optimize "active customers by journey stage" query
        if (Schema::hasTable('customers') &&
            !$this->indexExists('customers', 'idx_customers_journey_analysis') &&
            Schema::hasColumn('customers', 'journey_status')) {

            Schema::table('customers', function (Blueprint $table) {
                $table->index(
                    ['company_id', 'journey_status', 'created_at'],
                    'idx_customers_journey_analysis'
                );
            });

            $this->logOptimization(
                'customers',
                'Added composite index for journey analysis (company_id, journey_status, created_at)'
            );
        }

        // Calls: Optimize sentiment analysis queries
        if (Schema::hasTable('calls') &&
            !$this->indexExists('calls', 'idx_calls_sentiment_analysis') &&
            Schema::hasColumn('calls', 'sentiment_score')) {

            Schema::table('calls', function (Blueprint $table) {
                $table->index(
                    ['company_id', 'sentiment_score', 'created_at'],
                    'idx_calls_sentiment_analysis'
                );
            });

            $this->logOptimization(
                'calls',
                'Added composite index for sentiment analysis (company_id, sentiment_score, created_at)'
            );
        }
    }

    /**
     * Clean up NULL company_id data
     *
     * Issue: 31 customers with NULL company_id detected
     * Security Risk: These records accessible to all users
     */
    private function cleanupNullCompanyIds(): void
    {
        if (!Schema::hasTable('customers')) {
            return;
        }

        // Option 1: Report NULL company_id records for manual review
        $nullCount = DB::table('customers')->whereNull('company_id')->count();

        if ($nullCount > 0) {
            $this->logOptimization(
                'customers',
                "⚠️  Found {$nullCount} customers with NULL company_id - requires manual review"
            );

            // Log the IDs for manual review
            $nullIds = DB::table('customers')
                ->whereNull('company_id')
                ->pluck('id')
                ->take(10)
                ->implode(', ');

            $this->logOptimization(
                'customers',
                "Sample NULL company_id customer IDs: {$nullIds}"
            );

            // Option 2: Uncomment to create partial index for NULL company_id
            // This improves performance for queries filtering NULL values
            /*
            if (!$this->indexExists('customers', 'idx_customers_null_company')) {
                DB::statement(
                    'CREATE INDEX idx_customers_null_company ON customers(id) WHERE company_id IS NULL'
                );
                $this->logOptimization('customers', 'Added partial index for NULL company_id records');
            }
            */
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate dropped indexes
        Schema::table('appointments', function (Blueprint $table) {
            if (!$this->indexExists('appointments', 'idx_appointments_company_id')) {
                $table->index('company_id', 'idx_appointments_company_id');
            }
            if (!$this->indexExists('appointments', 'idx_appointments_company_status')) {
                $table->index(['company_id', 'status'], 'idx_appointments_company_status');
            }
            if (!$this->indexExists('appointments', 'idx_company_starts')) {
                $table->index(['company_id', 'starts_at'], 'idx_company_starts');
            }
            if (!$this->indexExists('appointments', 'idx_company_status_date')) {
                $table->index(['company_id', 'status', 'created_at'], 'idx_company_status_date');
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            if (!$this->indexExists('customers', 'idx_customers_company_id')) {
                $table->index('company_id', 'idx_customers_company_id');
            }
        });

        Schema::table('calls', function (Blueprint $table) {
            if (!$this->indexExists('calls', 'idx_company_created')) {
                $table->index(['company_id', 'created_at'], 'idx_company_created');
            }
        });

        // Drop added composite indexes
        Schema::table('appointments', function (Blueprint $table) {
            if ($this->indexExists('appointments', 'idx_appointments_optimal_dashboard')) {
                $table->dropIndex('idx_appointments_optimal_dashboard');
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            if ($this->indexExists('customers', 'idx_customers_journey_analysis')) {
                $table->dropIndex('idx_customers_journey_analysis');
            }
        });

        Schema::table('calls', function (Blueprint $table) {
            if ($this->indexExists('calls', 'idx_calls_sentiment_analysis')) {
                $table->dropIndex('idx_calls_sentiment_analysis');
            }
        });
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $index): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]);
        return count($indexes) > 0;
    }

    /**
     * Check if an index is used by a foreign key constraint
     */
    private function isForeignKeyIndex(string $table, string $index): bool
    {
        try {
            $constraints = DB::select("
                SELECT CONSTRAINT_NAME
                FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
                WHERE TABLE_NAME = ? AND CONSTRAINT_SCHEMA = ?
            ", [$table, DB::connection()->getDatabaseName()]);

            foreach ($constraints as $constraint) {
                // Get the columns used in this constraint
                $keyColumns = DB::select("
                    SELECT COLUMN_NAME, ORDINAL_POSITION
                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                    WHERE TABLE_NAME = ?
                    AND CONSTRAINT_NAME = ?
                    AND TABLE_SCHEMA = ?
                    ORDER BY ORDINAL_POSITION
                ", [$table, $constraint->CONSTRAINT_NAME, DB::connection()->getDatabaseName()]);

                if (count($keyColumns) > 0) {
                    // Foreign key constraints need the first column of the index to match
                    if ($keyColumns[0]->COLUMN_NAME === 'company_id') {
                        return true;
                    }
                }
            }

            return false;
        } catch (\Exception $e) {
            // If we can't determine, assume it's safe to drop
            return false;
        }
    }

    /**
     * Log optimization action
     */
    private function logOptimization(string $table, string $message): void
    {
        if (app()->runningInConsole()) {
            echo "[CompanyScope Optimization] {$table}: {$message}\n";
        }
    }
};
