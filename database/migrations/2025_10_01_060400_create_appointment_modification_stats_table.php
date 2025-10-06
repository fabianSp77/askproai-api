<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Materialized view implemented as regular table for O(1) quota checks.
     * Pre-calculates customer modification counts over rolling 30-day windows.
     * Updated by hourly scheduled job to avoid expensive real-time aggregations.
     *
     * Performance benefit: Transforms O(n) "COUNT(*) WHERE created_at > NOW() - 30 days"
     * into O(1) indexed lookup on pre-calculated stats.
     */
    public function up(): void
    {
        if (Schema::hasTable('appointment_modification_stats')) {
            return;
        }

        Schema::create('appointment_modification_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete()
                ->comment('Company owning this stats record');

            // Customer relationship
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->onDelete('cascade')
                ->comment('Customer these stats belong to');

            // Stat categorization
            $table->enum('stat_type', ['cancellation_count', 'reschedule_count'])
                ->comment('Type of modification being counted');

            // Rolling window definition
            $table->date('period_start')
                ->comment('Start date of 30-day rolling window');

            $table->date('period_end')
                ->comment('End date of 30-day rolling window');

            // Pre-calculated count
            $table->integer('count')->default(0)
                ->comment('Number of modifications in this period');

            // Calculation metadata
            $table->timestamp('calculated_at')
                ->useCurrent()
                ->comment('When this stat was last calculated');

            // Timestamps
            $table->timestamps();

            // Critical performance indexes
            $table->index('company_id', 'idx_company');
            // Primary lookup pattern: "get current stats for customer X in company Y"
            $table->index(['company_id', 'customer_id', 'stat_type', 'period_start'], 'idx_customer_stats_lookup');

            // For cleanup of old stats
            $table->index(['company_id', 'period_end', 'calculated_at'], 'idx_cleanup_old_stats');

            // For finding stale stats needing recalculation
            $table->index(['company_id', 'calculated_at', 'stat_type'], 'idx_stale_stats');

            // Unique constraint: one stat per company-customer per type per period
            $table->unique(['company_id', 'customer_id', 'stat_type', 'period_start'], 'unique_customer_stat_period');
        });

        // Table comment: Materialized stats for O(1) quota checks
        // Updated hourly by scheduled job
        // Query pattern: WHERE customer_id = ? AND stat_type = ? AND period_end >= CURDATE()
        // Note: SQLite doesn't support COMMENT ON TABLE, so this is documented in code only
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_modification_stats');
    }
};
