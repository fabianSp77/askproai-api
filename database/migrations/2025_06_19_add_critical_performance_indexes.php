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
        // Add critical indexes for foreign keys that are missing
        
        // Appointments table - critical for performance
        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                // Foreign key indexes that are often used in queries
                if (!$this->indexExists('appointments', 'idx_appointments_company_id')) {
                    $table->index('company_id', 'idx_appointments_company_id');
                }
                
                if (!$this->indexExists('appointments', 'idx_appointments_branch_id')) {
                    $table->index('branch_id', 'idx_appointments_branch_id');
                }
                
                if (!$this->indexExists('appointments', 'idx_appointments_staff_id')) {
                    $table->index('staff_id', 'idx_appointments_staff_id');
                }
                
                if (!$this->indexExists('appointments', 'idx_appointments_customer_id')) {
                    $table->index('customer_id', 'idx_appointments_customer_id');
                }
                
                if (!$this->indexExists('appointments', 'idx_appointments_service_id')) {
                    $table->index('service_id', 'idx_appointments_service_id');
                }
                
                // Composite index for common queries
                if (!$this->indexExists('appointments', 'idx_appointments_starts_status')) {
                    $table->index(['starts_at', 'status'], 'idx_appointments_starts_status');
                }
            });
        }
        
        // Calls table - critical for webhook processing
        if (Schema::hasTable('calls')) {
            Schema::table('calls', function (Blueprint $table) {
                if (!$this->indexExists('calls', 'idx_calls_company_id')) {
                    $table->index('company_id', 'idx_calls_company_id');
                }
                
                if (!$this->indexExists('calls', 'idx_calls_branch_id')) {
                    $table->index('branch_id', 'idx_calls_branch_id');
                }
                
                if (!$this->indexExists('calls', 'idx_calls_appointment_id')) {
                    $table->index('appointment_id', 'idx_calls_appointment_id');
                }
                
                if (!$this->indexExists('calls', 'idx_calls_call_id')) {
                    $table->index('call_id', 'idx_calls_call_id');
                }
                
                if (!$this->indexExists('calls', 'idx_calls_created_at')) {
                    $table->index('created_at', 'idx_calls_created_at');
                }
            });
        }
        
        // Branches table
        if (Schema::hasTable('branches')) {
            Schema::table('branches', function (Blueprint $table) {
                if (!$this->indexExists('branches', 'idx_branches_company_id')) {
                    $table->index('company_id', 'idx_branches_company_id');
                }
                
                if (!$this->indexExists('branches', 'idx_branches_is_active')) {
                    $table->index('is_active', 'idx_branches_is_active');
                }
                
                // Composite index for company + active
                if (!$this->indexExists('branches', 'idx_branches_company_active')) {
                    $table->index(['company_id', 'is_active'], 'idx_branches_company_active');
                }
            });
        }
        
        // Staff table
        if (Schema::hasTable('staff')) {
            Schema::table('staff', function (Blueprint $table) {
                if (!$this->indexExists('staff', 'idx_staff_home_branch_id')) {
                    $table->index('home_branch_id', 'idx_staff_home_branch_id');
                }
            });
        }
        
        // Services table
        if (Schema::hasTable('services')) {
            Schema::table('services', function (Blueprint $table) {
                if (!$this->indexExists('services', 'idx_services_company_id')) {
                    $table->index('company_id', 'idx_services_company_id');
                }
                
                if (!$this->indexExists('services', 'idx_services_active')) {
                    $table->index('active', 'idx_services_active');
                }
            });
        }
        
        // Customers table
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if (!$this->indexExists('customers', 'idx_customers_phone')) {
                    $table->index('phone', 'idx_customers_phone');
                }
                
                if (!$this->indexExists('customers', 'idx_customers_email')) {
                    $table->index('email', 'idx_customers_email');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes in reverse order
        $tables = [
            'appointments' => [
                'idx_appointments_company_id',
                'idx_appointments_branch_id',
                'idx_appointments_staff_id',
                'idx_appointments_customer_id',
                'idx_appointments_service_id',
                'idx_appointments_starts_status',
            ],
            'calls' => [
                'idx_calls_company_id',
                'idx_calls_branch_id',
                'idx_calls_appointment_id',
                'idx_calls_call_id',
                'idx_calls_created_at',
            ],
            'branches' => [
                'idx_branches_company_id',
                'idx_branches_is_active',
                'idx_branches_company_active',
            ],
            'staff' => [
                'idx_staff_home_branch_id',
            ],
            'services' => [
                'idx_services_company_id',
                'idx_services_is_active',
            ],
            'customers' => [
                'idx_customers_phone',
                'idx_customers_email',
            ],
        ];
        
        foreach ($tables as $table => $indexes) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) use ($indexes) {
                    foreach ($indexes as $index) {
                        if ($this->indexExists($table->getTable(), $index)) {
                            $table->dropIndex($index);
                        }
                    }
                });
            }
        }
    }
    
};