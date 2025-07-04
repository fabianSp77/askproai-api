<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends CompatibleMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Log migration start
        echo "Adding critical performance indexes...\n";
        
        // 1. Appointments table - Most critical for customer lookup and scheduling
        if (Schema::hasTable('appointments')) {
            // Composite index for company + customer + date lookups
            $this->addIndexIfNotExists('appointments', ['company_id', 'customer_id', 'starts_at'], 'idx_appointments_company_customer_date');
            echo "✅ Added index: idx_appointments_company_customer_date\n";
            
            // Index for status filtering (common in queries)
            $this->addIndexIfNotExists('appointments', 'status', 'idx_appointments_status');
            echo "✅ Added index: idx_appointments_status\n";
            
            // Index for branch-based queries
            $this->addIndexIfNotExists('appointments', ['branch_id', 'starts_at'], 'idx_appointments_branch_date');
            echo "✅ Added index: idx_appointments_branch_date\n";
        }
        
        // 2. Customers table - Critical for phone number lookups
        if (Schema::hasTable('customers')) {
            // Composite index for company + phone (unique customer lookup)
            $this->addIndexIfNotExists('customers', ['company_id', 'phone'], 'idx_customers_company_phone');
            echo "✅ Added index: idx_customers_company_phone\n";
            
            // Index for email lookups (customer portal)
            $this->addIndexIfNotExists('customers', 'email', 'idx_customers_email');
            echo "✅ Added index: idx_customers_email\n";
        }
        
        // 3. Calls table - Critical for webhook processing and reporting
        if (Schema::hasTable('calls')) {
            // Index for retell_call_id (webhook lookups)
            $this->addIndexIfNotExists('calls', 'retell_call_id', 'idx_calls_retell_call_id');
            echo "✅ Added index: idx_calls_retell_call_id\n";
            
            // Index for conversation_id (transcript lookups)
            $this->addIndexIfNotExists('calls', 'conversation_id', 'idx_calls_conversation_id');
            echo "✅ Added index: idx_calls_conversation_id\n";
            
            // Composite index for company + created_at (reporting)
            $this->addIndexIfNotExists('calls', ['company_id', 'created_at'], 'idx_calls_company_created');
            echo "✅ Added index: idx_calls_company_created\n";
            
            // Index for customer lookups
            $this->addIndexIfNotExists('calls', 'customer_id', 'idx_calls_customer');
            echo "✅ Added index: idx_calls_customer\n";
        }
        
        // 4. Webhook events table - Critical for deduplication
        if (Schema::hasTable('webhook_events')) {
            // Index for idempotency key (deduplication)
            $this->addIndexIfNotExists('webhook_events', 'idempotency_key', 'idx_webhook_events_idempotency');
            echo "✅ Added index: idx_webhook_events_idempotency\n";
            
            // Composite index for provider + created_at (monitoring)
            $this->addIndexIfNotExists('webhook_events', ['provider', 'created_at'], 'idx_webhook_events_provider_created');
            echo "✅ Added index: idx_webhook_events_provider_created\n";
        }
        
        // 5. Phone numbers table - Critical for routing
        if (Schema::hasTable('phone_numbers')) {
            // Check if is_active column exists
            if (Schema::hasColumn('phone_numbers', 'is_active')) {
                // Composite index for phone + branch lookup
                $this->addIndexIfNotExists('phone_numbers', ['phone_number', 'branch_id', 'is_active'], 'idx_phone_branch_lookup');
                echo "✅ Added index: idx_phone_branch_lookup\n";
            } else {
                // Fallback without is_active column
                $this->addIndexIfNotExists('phone_numbers', ['phone_number', 'branch_id'], 'idx_phone_branch_lookup');
                echo "✅ Added index: idx_phone_branch_lookup (without is_active)\n";
            }
        }
        
        // 6. Staff table - For availability lookups
        if (Schema::hasTable('staff')) {
            // Index for branch-based staff lookups
            $this->addIndexIfNotExists('staff', ['branch_id', 'active'], 'idx_staff_branch_active');
            echo "✅ Added index: idx_staff_branch_active\n";
        }
        
        // 7. Branches table - For company queries
        if (Schema::hasTable('branches')) {
            // Index for company-based branch lookups
            $this->addIndexIfNotExists('branches', 'company_id', 'idx_branches_company');
            echo "✅ Added index: idx_branches_company\n";
        }
        
        // 8. Services table - For service lookups
        if (Schema::hasTable('services')) {
            // Index for company-based service lookups
            $this->addIndexIfNotExists('services', 'company_id', 'idx_services_company');
            echo "✅ Added index: idx_services_company\n";
        }
        
        // Analyze tables to update optimizer statistics (MySQL only)
        if (!$this->isSQLite()) {
            $this->analyzeTablesForOptimizer();
        }
        
        echo "\n✅ All critical performance indexes added successfully!\n";
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes in reverse order
        if (Schema::hasTable('services')) {
            $this->dropIndexIfExists('services', 'idx_services_company');
        }
        
        if (Schema::hasTable('branches')) {
            $this->dropIndexIfExists('branches', 'idx_branches_company');
        }
        
        if (Schema::hasTable('staff')) {
            $this->dropIndexIfExists('staff', 'idx_staff_branch_active');
        }
        
        if (Schema::hasTable('phone_numbers')) {
            $this->dropIndexIfExists('phone_numbers', 'idx_phone_branch_lookup');
        }
        
        if (Schema::hasTable('webhook_events')) {
            $this->dropIndexIfExists('webhook_events', 'idx_webhook_events_provider_created');
            $this->dropIndexIfExists('webhook_events', 'idx_webhook_events_idempotency');
        }
        
        if (Schema::hasTable('calls')) {
            $this->dropIndexIfExists('calls', 'idx_calls_customer');
            $this->dropIndexIfExists('calls', 'idx_calls_company_created');
            $this->dropIndexIfExists('calls', 'idx_calls_conversation_id');
            $this->dropIndexIfExists('calls', 'idx_calls_retell_call_id');
        }
        
        if (Schema::hasTable('customers')) {
            $this->dropIndexIfExists('customers', 'idx_customers_email');
            $this->dropIndexIfExists('customers', 'idx_customers_company_phone');
        }
        
        if (Schema::hasTable('appointments')) {
            $this->dropIndexIfExists('appointments', 'idx_appointments_branch_date');
            $this->dropIndexIfExists('appointments', 'idx_appointments_status');
            $this->dropIndexIfExists('appointments', 'idx_appointments_company_customer_date');
        }
    }
    
    /**
     * Analyze tables to update optimizer statistics
     */
    private function analyzeTablesForOptimizer(): void
    {
        $tables = [
            'appointments',
            'customers', 
            'calls',
            'webhook_events',
            'phone_numbers',
            'staff',
            'branches',
            'services'
        ];
        
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                try {
                    DB::statement("ANALYZE TABLE {$table}");
                    echo "📊 Analyzed table: {$table}\n";
                } catch (\Exception $e) {
                    // Ignore analyze errors
                }
            }
        }
    }
};