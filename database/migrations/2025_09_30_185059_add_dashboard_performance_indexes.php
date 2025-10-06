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
        // Dashboard Calls Aggregation Indexes
        if (Schema::hasTable('calls')) {
            Schema::table('calls', function (Blueprint $table) {
                // Composite index for daily call aggregations with duration
                // Optimizes: Call count trends, average duration calculations
                if (!$this->indexExists('calls', 'idx_calls_dashboard_daily_duration')) {
                    $table->index(['created_at', 'duration_sec'], 'idx_calls_dashboard_daily_duration');
                }
            });
        }

        // Dashboard Appointments Aggregation Indexes
        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                // Composite index for appointment status filtering with date
                // Optimizes: Appointment trends, status-based queries
                if (!$this->indexExists('appointments', 'idx_appointments_dashboard_status_date')) {
                    $table->index(['starts_at', 'status'], 'idx_appointments_dashboard_status_date');
                }
            });
        }

        // Dashboard Invoice/Revenue Aggregation Indexes
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                // Composite index for revenue calculations
                // Optimizes: Revenue trends, paid invoice aggregations
                if (!$this->indexExists('invoices', 'idx_invoices_dashboard_revenue')) {
                    $table->index(['issue_date', 'status', 'total_amount'], 'idx_invoices_dashboard_revenue');
                }
            });
        }

        // Dashboard Customer Growth Indexes
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                // Composite index for customer status and growth tracking
                // Optimizes: Active customer counts, new customer trends
                if (!$this->indexExists('customers', 'idx_customers_dashboard_growth')) {
                    $table->index(['status', 'created_at'], 'idx_customers_dashboard_growth');
                }

                // Index for customer churn rate calculations
                // Optimizes: Inactive customer tracking with update time
                if (!$this->indexExists('customers', 'idx_customers_dashboard_churn')) {
                    $table->index(['status', 'updated_at'], 'idx_customers_dashboard_churn');
                }
            });
        }

        // Dashboard Company Growth Indexes
        if (Schema::hasTable('companies')) {
            Schema::table('companies', function (Blueprint $table) {
                // Index for company growth trends
                // Optimizes: Company count aggregations by date
                if (!$this->indexExists('companies', 'idx_companies_dashboard_growth')) {
                    $table->index(['created_at'], 'idx_companies_dashboard_growth');
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
                if ($this->indexExists('calls', 'idx_calls_dashboard_daily_duration')) {
                    $table->dropIndex('idx_calls_dashboard_daily_duration');
                }
            });
        }

        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                if ($this->indexExists('appointments', 'idx_appointments_dashboard_status_date')) {
                    $table->dropIndex('idx_appointments_dashboard_status_date');
                }
            });
        }

        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                if ($this->indexExists('invoices', 'idx_invoices_dashboard_revenue')) {
                    $table->dropIndex('idx_invoices_dashboard_revenue');
                }
            });
        }

        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if ($this->indexExists('customers', 'idx_customers_dashboard_growth')) {
                    $table->dropIndex('idx_customers_dashboard_growth');
                }
                if ($this->indexExists('customers', 'idx_customers_dashboard_churn')) {
                    $table->dropIndex('idx_customers_dashboard_churn');
                }
            });
        }

        if (Schema::hasTable('companies')) {
            Schema::table('companies', function (Blueprint $table) {
                if ($this->indexExists('companies', 'idx_companies_dashboard_growth')) {
                    $table->dropIndex('idx_companies_dashboard_growth');
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
