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
        // Add indexes for prepaid_transactions table
        Schema::table('prepaid_transactions', function (Blueprint $table) {
            // For revenue calculations per company and type
            if (!Schema::hasIndex('prepaid_transactions', 'idx_company_type_date')) {
                $table->index(['company_id', 'type', 'created_at'], 'idx_company_type_date');
            }
            
            // For aggregations with amount
            if (!Schema::hasIndex('prepaid_transactions', 'idx_type_date_amount')) {
                $table->index(['type', 'created_at'], 'idx_type_date_amount');
            }
        });
        
        // Add indexes for companies table
        Schema::table('companies', function (Blueprint $table) {
            // For reseller queries
            if (!Schema::hasIndex('companies', 'idx_type_active_parent')) {
                $table->index(['company_type', 'is_active', 'parent_company_id'], 'idx_type_active_parent');
            }
            
            // For child company lookups
            if (!Schema::hasIndex('companies', 'idx_parent_type_active')) {
                $table->index(['parent_company_id', 'company_type', 'is_active'], 'idx_parent_type_active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prepaid_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_company_type_date');
            $table->dropIndex('idx_type_date_amount');
        });
        
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex('idx_type_active_parent');
            $table->dropIndex('idx_parent_type_active');
        });
    }
};