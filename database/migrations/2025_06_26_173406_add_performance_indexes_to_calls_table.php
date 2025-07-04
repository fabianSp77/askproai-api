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
        Schema::table('calls', function (Blueprint $table) {
            // Composite index for company and timestamp queries (most common)
            $table->index(['company_id', 'start_timestamp'], 'idx_company_timestamp');
            
            // Index for real-time status queries
            $table->index(['company_id', 'call_status'], 'idx_company_status');
            
            // Index for customer lookups
            $table->index(['company_id', 'customer_id'], 'idx_company_customer');
            
            // Index for appointment conversions
            $table->index(['company_id', 'appointment_id'], 'idx_company_appointment');
            
            // Index for phone number searches
            $table->index('from_number', 'idx_from_number');
            
            // Index for recent calls queries
            $table->index(['company_id', 'created_at'], 'idx_company_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropIndex('idx_company_timestamp');
            $table->dropIndex('idx_company_status');
            $table->dropIndex('idx_company_customer');
            $table->dropIndex('idx_company_appointment');
            $table->dropIndex('idx_from_number');
            $table->dropIndex('idx_company_created');
        });
    }
};