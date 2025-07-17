<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Helper function to safely add an index
     */
    private function addIndexIfNotExists($table, $columns, $indexName)
    {
        try {
            Schema::table($table, function (Blueprint $table) use ($columns, $indexName) {
                $table->index($columns, $indexName);
            });
        } catch (\Exception $e) {
            // Index already exists, ignore
            if (!str_contains($e->getMessage(), 'already exists')) {
                throw $e;
            }
        }
    }
    
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Indexes for calls table
        if (Schema::hasTable('calls')) {
            // Composite index for company and date filtering
            $this->addIndexIfNotExists('calls', ['company_id', 'created_at'], 'idx_calls_company_date');
            
            // Index for phone number lookups
            $this->addIndexIfNotExists('calls', ['to_number', 'created_at'], 'idx_calls_to_number_date');
            
            // Index for customer lookups
            $this->addIndexIfNotExists('calls', ['customer_id', 'created_at'], 'idx_calls_customer_date');
            
            // Index for branch filtering
            $this->addIndexIfNotExists('calls', ['branch_id', 'created_at'], 'idx_calls_branch_date');
            
            // Index for status filtering
            $this->addIndexIfNotExists('calls', 'status', 'idx_calls_status');
        }
        
        // Indexes for call_portal_data table
        if (Schema::hasTable('call_portal_data')) {
            // Index for assignment lookups
            $this->addIndexIfNotExists('call_portal_data', ['assigned_to', 'status'], 'idx_portal_assigned_status');
            
            // Index for status filtering
            $this->addIndexIfNotExists('call_portal_data', ['status', 'updated_at'], 'idx_portal_status_updated');
            
            // Index for callback scheduling
            $this->addIndexIfNotExists('call_portal_data', 'callback_scheduled_at', 'idx_portal_callback');
        }
        
        // Indexes for appointments table
        if (Schema::hasTable('appointments')) {
            // Composite index for company and time filtering
            $this->addIndexIfNotExists('appointments', ['company_id', 'starts_at'], 'idx_appointments_company_starts');
            
            // Index for branch filtering
            $this->addIndexIfNotExists('appointments', ['branch_id', 'starts_at'], 'idx_appointments_branch_starts');
            
            // Index for staff filtering
            $this->addIndexIfNotExists('appointments', ['staff_id', 'starts_at'], 'idx_appointments_staff_starts');
            
            // Index for status filtering
            $this->addIndexIfNotExists('appointments', ['status', 'starts_at'], 'idx_appointments_status_starts');
        }
        
        // Indexes for portal_users table
        if (Schema::hasTable('portal_users')) {
            // Index for company lookups
            $this->addIndexIfNotExists('portal_users', ['company_id', 'is_active'], 'idx_portal_users_company_active');
            
            // Index for email lookups (already exists as unique, skip if error)
            $this->addIndexIfNotExists('portal_users', 'email', 'idx_portal_users_email');
        }
        
        // Indexes for customers table
        if (Schema::hasTable('customers')) {
            // Index for phone number lookups
            $this->addIndexIfNotExists('customers', ['phone', 'company_id'], 'idx_customers_phone_company');
            
            // Index for email lookups
            $this->addIndexIfNotExists('customers', ['email', 'company_id'], 'idx_customers_email_company');
        }
    }

    /**
     * Helper function to safely drop an index
     */
    private function dropIndexIfExists($table, $indexName)
    {
        try {
            Schema::table($table, function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        } catch (\Exception $e) {
            // Index doesn't exist, ignore
        }
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes from calls table
        if (Schema::hasTable('calls')) {
            $this->dropIndexIfExists('calls', 'idx_calls_company_date');
            $this->dropIndexIfExists('calls', 'idx_calls_to_number_date');
            $this->dropIndexIfExists('calls', 'idx_calls_customer_date');
            $this->dropIndexIfExists('calls', 'idx_calls_branch_date');
            $this->dropIndexIfExists('calls', 'idx_calls_status');
        }
        
        // Remove indexes from call_portal_data table
        if (Schema::hasTable('call_portal_data')) {
            $this->dropIndexIfExists('call_portal_data', 'idx_portal_assigned_status');
            $this->dropIndexIfExists('call_portal_data', 'idx_portal_status_updated');
            $this->dropIndexIfExists('call_portal_data', 'idx_portal_callback');
        }
        
        // Remove indexes from appointments table
        if (Schema::hasTable('appointments')) {
            $this->dropIndexIfExists('appointments', 'idx_appointments_company_starts');
            $this->dropIndexIfExists('appointments', 'idx_appointments_branch_starts');
            $this->dropIndexIfExists('appointments', 'idx_appointments_staff_starts');
            $this->dropIndexIfExists('appointments', 'idx_appointments_status_starts');
        }
        
        // Remove indexes from portal_users table
        if (Schema::hasTable('portal_users')) {
            $this->dropIndexIfExists('portal_users', 'idx_portal_users_company_active');
            $this->dropIndexIfExists('portal_users', 'idx_portal_users_email');
        }
        
        // Remove indexes from customers table
        if (Schema::hasTable('customers')) {
            $this->dropIndexIfExists('customers', 'idx_customers_phone_company');
            $this->dropIndexIfExists('customers', 'idx_customers_email_company');
        }
    }
};
