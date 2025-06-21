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
        // Appointments - Performance Indexes für KPI-Queries
        Schema::table('appointments', function (Blueprint $table) {
            // Revenue Calculation Index
            if (!Schema::hasIndex('appointments', 'idx_appointments_revenue_calc')) {
                $table->index(['company_id', 'status', 'starts_at', 'service_id'], 'idx_appointments_revenue_calc');
            }
            
            // Conversion Tracking Index
            if (!Schema::hasIndex('appointments', 'idx_appointments_conversion_track')) {
                $table->index(['company_id', 'call_id', 'created_at'], 'idx_appointments_conversion_track');
            }
            
            // Branch Date Filter Index
            if (!Schema::hasIndex('appointments', 'idx_appointments_branch_date')) {
                $table->index(['company_id', 'branch_id', 'starts_at'], 'idx_appointments_branch_date');
            }
            
            // Reminder Status Index
            if (!Schema::hasIndex('appointments', 'idx_appointments_reminder_status')) {
                $table->index(['company_id', 'status', 'reminder_24h_sent_at'], 'idx_appointments_reminder_status');
            }
        });
        
        // Calls - Performance Indexes für KPI-Queries
        Schema::table('calls', function (Blueprint $table) {
            // Company Date Index
            if (!Schema::hasIndex('calls', 'idx_calls_company_date')) {
                $table->index(['company_id', 'created_at'], 'idx_calls_company_date');
            }
            
            // Status Duration Index
            if (!Schema::hasIndex('calls', 'idx_calls_status_duration')) {
                $table->index(['company_id', 'call_status', 'duration_sec'], 'idx_calls_status_duration');
            }
            
            // Phone Search Index (optimized)
            if (!Schema::hasIndex('calls', 'idx_calls_phone_normalized')) {
                $table->index(['company_id', 'from_number'], 'idx_calls_phone_normalized');
            }
            
            // Sentiment Analysis Index
            if (!Schema::hasIndex('calls', 'idx_calls_sentiment_date')) {
                $table->index(['company_id', 'created_at'], 'idx_calls_sentiment_date');
            }
        });
        
        // Customers - Performance Indexes für KPI-Queries
        Schema::table('customers', function (Blueprint $table) {
            // Duplicate Prevention Index
            if (!Schema::hasIndex('customers', 'idx_customers_company_phone')) {
                $table->index(['company_id', 'phone'], 'idx_customers_company_phone');
            }
            
            // Name Search Index
            if (!Schema::hasIndex('customers', 'idx_customers_name_company')) {
                $table->index(['company_id', 'name'], 'idx_customers_name_company');
            }
            
            // Created Date Index for Growth Tracking
            if (!Schema::hasIndex('customers', 'idx_customers_company_created')) {
                $table->index(['company_id', 'created_at'], 'idx_customers_company_created');
            }
        });
        
        // Staff - Performance Indexes für Filter Queries
        Schema::table('staff', function (Blueprint $table) {
            if (!Schema::hasIndex('staff', 'idx_staff_company_name')) {
                $table->index(['company_id', 'name'], 'idx_staff_company_name');
            }
        });
        
        // Services - Performance Indexes für Filter Queries
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasIndex('services', 'idx_services_company_name')) {
                $table->index(['company_id', 'name'], 'idx_services_company_name');
            }
        });
        
        // Branches - Performance Indexes für Filter Queries
        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasIndex('branches', 'idx_branches_company_name')) {
                $table->index(['company_id', 'name'], 'idx_branches_company_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop Appointments Indexes
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'status', 'starts_at', 'service_id']);
            $table->dropIndex(['company_id', 'call_id', 'created_at']);
            $table->dropIndex(['company_id', 'branch_id', 'starts_at']);
            $table->dropIndex(['company_id', 'status', 'reminder_24h_sent_at']);
        });
        
        // Drop Calls Indexes
        Schema::table('calls', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'created_at']);
            $table->dropIndex(['company_id', 'call_status', 'duration_sec']);
            $table->dropIndex(['company_id', 'from_number']);
        });
        
        // Drop Customers Indexes
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'phone']);
            $table->dropIndex(['company_id', 'name']);
            $table->dropIndex(['company_id', 'created_at']);
        });
        
        // Drop Staff Indexes
        Schema::table('staff', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'name']);
        });
        
        // Drop Services Indexes
        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'name']);
        });
        
        // Drop Branches Indexes
        Schema::table('branches', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'name']);
        });
    }
};