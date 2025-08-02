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
        // Add indexes for users table
        if (!$this->indexExists('users', 'users_company_id_created_at_index')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index(['company_id', 'created_at']);
            });
        }
        if (!$this->indexExists('users', 'users_company_id_email_index')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index(['company_id', 'email']);
            });
        }

        // Add indexes for portal_users table
        if (!$this->indexExists('portal_users', 'portal_users_company_id_is_active_index')) {
            Schema::table('portal_users', function (Blueprint $table) {
                $table->index(['company_id', 'is_active']);
            });
        }

        // Add indexes for calls table
        if (!$this->indexExists('calls', 'calls_company_id_created_at_index')) {
            Schema::table('calls', function (Blueprint $table) {
                $table->index(['company_id', 'created_at']);
            });
        }
        if (!$this->indexExists('calls', 'calls_company_id_status_index')) {
            Schema::table('calls', function (Blueprint $table) {
                $table->index(['company_id', 'status']);
            });
        }
        if (!$this->indexExists('calls', 'calls_from_number_created_at_index')) {
            Schema::table('calls', function (Blueprint $table) {
                $table->index(['from_number', 'created_at']);
            });
        }
        if (!$this->indexExists('calls', 'calls_to_number_created_at_index')) {
            Schema::table('calls', function (Blueprint $table) {
                $table->index(['to_number', 'created_at']);
            });
        }

        // Add indexes for appointments table
        if (!$this->indexExists('appointments', 'appointments_company_id_created_at_index')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->index(['company_id', 'created_at']);
            });
        }
        if (!$this->indexExists('appointments', 'appointments_company_id_status_index')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->index(['company_id', 'status']);
            });
        }
        if (!$this->indexExists('appointments', 'appointments_company_id_starts_at_index')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->index(['company_id', 'starts_at']);
            });
        }
        if (!$this->indexExists('appointments', 'appointments_customer_id_created_at_index')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->index(['customer_id', 'created_at']);
            });
        }
        if (!$this->indexExists('appointments', 'appointments_staff_id_starts_at_index')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->index(['staff_id', 'starts_at']);
            });
        }

        // Add indexes for customers table
        if (!$this->indexExists('customers', 'customers_company_id_created_at_index')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->index(['company_id', 'created_at']);
            });
        }
        if (!$this->indexExists('customers', 'customers_company_id_phone_index')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->index(['company_id', 'phone']);
            });
        }
        if (!$this->indexExists('customers', 'customers_phone_index')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->index('phone');
            });
        }

        // Add indexes for branches table
        if (!$this->indexExists('branches', 'branches_company_id_created_at_index')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->index(['company_id', 'created_at']);
            });
        }
        if (!$this->indexExists('branches', 'branches_company_id_is_active_index')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->index(['company_id', 'is_active']);
            });
        }

        // Add indexes for staff table
        if (!$this->indexExists('staff', 'staff_company_id_created_at_index')) {
            Schema::table('staff', function (Blueprint $table) {
                $table->index(['company_id', 'created_at']);
            });
        }
        if (!$this->indexExists('staff', 'staff_company_id_is_active_index')) {
            Schema::table('staff', function (Blueprint $table) {
                $table->index(['company_id', 'is_active']);
            });
        }
        if (!$this->indexExists('staff', 'staff_branch_id_is_active_index')) {
            Schema::table('staff', function (Blueprint $table) {
                $table->index(['branch_id', 'is_active']);
            });
        }

        // Add indexes for integrations table
        if (!$this->indexExists('integrations', 'integrations_company_id_system_index')) {
            Schema::table('integrations', function (Blueprint $table) {
                $table->index(['company_id', 'system']);
            });
        }
        if (!$this->indexExists('integrations', 'integrations_company_id_active_index')) {
            Schema::table('integrations', function (Blueprint $table) {
                $table->index(['company_id', 'active']);
            });
        }

        // Add indexes for invoices table
        if (!$this->indexExists('invoices', 'invoices_company_id_created_at_index')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->index(['company_id', 'created_at']);
            });
        }
        if (!$this->indexExists('invoices', 'invoices_company_id_status_index')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->index(['company_id', 'status']);
            });
        }
        if (!$this->indexExists('invoices', 'invoices_company_id_due_date_index')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->index(['company_id', 'due_date']);
            });
        }

        // Add indexes for webhook_events table
        if (!$this->indexExists('webhook_events', 'webhook_events_created_at_index')) {
            Schema::table('webhook_events', function (Blueprint $table) {
                $table->index('created_at');
            });
        }
        if (!$this->indexExists('webhook_events', 'webhook_events_event_type_created_at_index')) {
            Schema::table('webhook_events', function (Blueprint $table) {
                $table->index(['event_type', 'created_at']);
            });
        }
        if (!$this->indexExists('webhook_events', 'webhook_events_status_created_at_index')) {
            Schema::table('webhook_events', function (Blueprint $table) {
                $table->index(['status', 'created_at']);
            });
        }

        // Add indexes for api_call_logs table
        if (!$this->indexExists('api_call_logs', 'api_call_logs_created_at_index')) {
            Schema::table('api_call_logs', function (Blueprint $table) {
                $table->index('created_at');
            });
        }
        if (!$this->indexExists('api_call_logs', 'api_call_logs_service_created_at_index')) {
            Schema::table('api_call_logs', function (Blueprint $table) {
                $table->index(['service', 'created_at']);
            });
        }
        if (!$this->indexExists('api_call_logs', 'api_call_logs_correlation_id_index')) {
            Schema::table('api_call_logs', function (Blueprint $table) {
                $table->index('correlation_id');
            });
        }

        // Add indexes for balance_transactions table
        if (!$this->indexExists('balance_transactions', 'balance_transactions_company_id_created_at_index')) {
            Schema::table('balance_transactions', function (Blueprint $table) {
                $table->index(['company_id', 'created_at']);
            });
        }
        if (!$this->indexExists('balance_transactions', 'balance_transactions_company_id_type_index')) {
            Schema::table('balance_transactions', function (Blueprint $table) {
                $table->index(['company_id', 'type']);
            });
        }

        // Add indexes for balance_topups table
        if (!$this->indexExists('balance_topups', 'balance_topups_company_id_created_at_index')) {
            Schema::table('balance_topups', function (Blueprint $table) {
                $table->index(['company_id', 'created_at']);
            });
        }
        if (!$this->indexExists('balance_topups', 'balance_topups_company_id_status_index')) {
            Schema::table('balance_topups', function (Blueprint $table) {
                $table->index(['company_id', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes if they exist
        $this->dropIndexIfExists('users', 'users_company_id_created_at_index');
        $this->dropIndexIfExists('users', 'users_company_id_email_index');
        
        $this->dropIndexIfExists('portal_users', 'portal_users_company_id_is_active_index');
        
        $this->dropIndexIfExists('calls', 'calls_company_id_created_at_index');
        $this->dropIndexIfExists('calls', 'calls_company_id_status_index');
        $this->dropIndexIfExists('calls', 'calls_from_number_created_at_index');
        $this->dropIndexIfExists('calls', 'calls_to_number_created_at_index');
        
        $this->dropIndexIfExists('appointments', 'appointments_company_id_created_at_index');
        $this->dropIndexIfExists('appointments', 'appointments_company_id_status_index');
        $this->dropIndexIfExists('appointments', 'appointments_company_id_starts_at_index');
        $this->dropIndexIfExists('appointments', 'appointments_customer_id_created_at_index');
        $this->dropIndexIfExists('appointments', 'appointments_staff_id_starts_at_index');
        
        $this->dropIndexIfExists('customers', 'customers_company_id_created_at_index');
        $this->dropIndexIfExists('customers', 'customers_company_id_phone_index');
        $this->dropIndexIfExists('customers', 'customers_phone_index');
        
        $this->dropIndexIfExists('branches', 'branches_company_id_created_at_index');
        $this->dropIndexIfExists('branches', 'branches_company_id_is_active_index');
        
        $this->dropIndexIfExists('staff', 'staff_company_id_created_at_index');
        $this->dropIndexIfExists('staff', 'staff_company_id_is_active_index');
        $this->dropIndexIfExists('staff', 'staff_branch_id_is_active_index');
        
        $this->dropIndexIfExists('integrations', 'integrations_company_id_system_index');
        $this->dropIndexIfExists('integrations', 'integrations_company_id_active_index');
        
        $this->dropIndexIfExists('invoices', 'invoices_company_id_created_at_index');
        $this->dropIndexIfExists('invoices', 'invoices_company_id_status_index');
        $this->dropIndexIfExists('invoices', 'invoices_company_id_due_date_index');
        
        $this->dropIndexIfExists('webhook_events', 'webhook_events_created_at_index');
        $this->dropIndexIfExists('webhook_events', 'webhook_events_event_type_created_at_index');
        $this->dropIndexIfExists('webhook_events', 'webhook_events_status_created_at_index');
        
        $this->dropIndexIfExists('api_call_logs', 'api_call_logs_created_at_index');
        $this->dropIndexIfExists('api_call_logs', 'api_call_logs_service_created_at_index');
        $this->dropIndexIfExists('api_call_logs', 'api_call_logs_correlation_id_index');
        
        $this->dropIndexIfExists('balance_transactions', 'balance_transactions_company_id_created_at_index');
        $this->dropIndexIfExists('balance_transactions', 'balance_transactions_company_id_type_index');
        
        $this->dropIndexIfExists('balance_topups', 'balance_topups_company_id_created_at_index');
        $this->dropIndexIfExists('balance_topups', 'balance_topups_company_id_status_index');
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $indexName): bool
    {
        // Skip index checks in testing environment
        if (app()->environment('testing')) {
            return false;
        }
        
        try {
            $indexes = DB::select("SHOW INDEX FROM {$table}");
            foreach ($indexes as $index) {
                if ($index->Key_name === $indexName) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            // SQLite or other databases that don't support SHOW INDEX
            return false;
        }
        return false;
    }

    /**
     * Drop an index if it exists
     */
    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if ($this->indexExists($table, $indexName)) {
            Schema::table($table, function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        }
    }
};