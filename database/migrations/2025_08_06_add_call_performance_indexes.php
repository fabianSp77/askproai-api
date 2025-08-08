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
        // Add performance indexes for calls table
        Schema::table('calls', function (Blueprint $table) {
            // Composite index for company-based queries with date sorting
            $table->index(['company_id', 'created_at'], 'idx_company_created');
            $table->index(['company_id', 'start_timestamp'], 'idx_company_start');
            
            // Customer-based queries
            $table->index(['customer_id', 'start_timestamp'], 'idx_customer_start');
            
            // Status and appointment filtering
            $table->index(['status', 'appointment_made'], 'idx_status_appointment');
            $table->index(['call_status', 'session_outcome'], 'idx_call_session_status');
            
            // Duration-based queries (partial index for long calls)
            $table->index('duration_sec', 'idx_duration');
            
            // Sentiment analysis queries
            $table->index(['sentiment', 'sentiment_score'], 'idx_sentiment');
            
            // Date range queries
            $table->index(['created_at', 'company_id'], 'idx_created_company');
            
            // Cost tracking
            $table->index(['cost', 'cost_cents'], 'idx_cost');
            
            // Branch-based queries
            $table->index(['branch_id', 'created_at'], 'idx_branch_created');
        });

        // Add indexes for appointments table (related queries)
        Schema::table('appointments', function (Blueprint $table) {
            // Call relationship optimization
            $table->index(['call_id', 'status'], 'idx_call_status');
            
            // Date-based queries
            $table->index(['starts_at', 'company_id'], 'idx_starts_company');
        });

        // Add indexes for customers table (join optimization)
        Schema::table('customers', function (Blueprint $table) {
            // Phone number lookup
            $table->index('phone', 'idx_phone');
            
            // Company-based customer queries
            $table->index(['company_id', 'created_at'], 'idx_company_created');
        });

        // Add indexes for call_charges table (billing queries)
        if (Schema::hasTable('call_charges')) {
            Schema::table('call_charges', function (Blueprint $table) {
                $table->index(['call_id', 'refund_status'], 'idx_call_refund');
                $table->index(['company_id', 'created_at'], 'idx_company_created');
            });
        }

        // Create a partial index for long calls (MySQL doesn't support partial indexes, so we use a generated column)
        DB::statement('ALTER TABLE calls ADD INDEX idx_long_calls (duration_sec) WHERE duration_sec > 300');

        // Add FULLTEXT index for transcript search
        DB::statement('ALTER TABLE calls ADD FULLTEXT idx_transcript_search (transcript, call_summary)');
        
        // Optimize table statistics
        DB::statement('ANALYZE TABLE calls');
        DB::statement('ANALYZE TABLE appointments');
        DB::statement('ANALYZE TABLE customers');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropIndex('idx_company_created');
            $table->dropIndex('idx_company_start');
            $table->dropIndex('idx_customer_start');
            $table->dropIndex('idx_status_appointment');
            $table->dropIndex('idx_call_session_status');
            $table->dropIndex('idx_duration');
            $table->dropIndex('idx_sentiment');
            $table->dropIndex('idx_created_company');
            $table->dropIndex('idx_cost');
            $table->dropIndex('idx_branch_created');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('idx_call_status');
            $table->dropIndex('idx_starts_company');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('idx_phone');
            $table->dropIndex('idx_company_created');
        });

        if (Schema::hasTable('call_charges')) {
            Schema::table('call_charges', function (Blueprint $table) {
                $table->dropIndex('idx_call_refund');
                $table->dropIndex('idx_company_created');
            });
        }

        // Drop FULLTEXT index
        DB::statement('ALTER TABLE calls DROP INDEX idx_transcript_search');
    }
};