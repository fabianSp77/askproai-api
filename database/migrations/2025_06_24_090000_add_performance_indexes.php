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
        // Add performance indexes for common queries
        
        // Calls table indexes
        if (!$this->indexExists('calls', 'idx_calls_company_created')) {
            Schema::table('calls', function (Blueprint $table) {
                $table->index(['company_id', 'created_at'], 'idx_calls_company_created');
            });
        }
        
        if (!$this->indexExists('calls', 'idx_calls_branch_status')) {
            Schema::table('calls', function (Blueprint $table) {
                $table->index(['branch_id', 'status'], 'idx_calls_branch_status');
            });
        }
        
        if (!$this->indexExists('calls', 'idx_calls_customer_id')) {
            Schema::table('calls', function (Blueprint $table) {
                $table->index('customer_id', 'idx_calls_customer_id');
            });
        }
        
        // Appointments table indexes
        if (!$this->indexExists('appointments', 'idx_appointments_branch_starts')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->index(['branch_id', 'starts_at'], 'idx_appointments_branch_starts');
            });
        }
        
        if (!$this->indexExists('appointments', 'idx_appointments_staff_date')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->index(['staff_id', 'starts_at'], 'idx_appointments_staff_date');
            });
        }
        
        if (!$this->indexExists('appointments', 'idx_appointments_status')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->index('status', 'idx_appointments_status');
            });
        }
        
        // Customers table indexes
        if (!$this->indexExists('customers', 'idx_customers_phone')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->index('phone', 'idx_customers_phone');
            });
        }
        
        if (!$this->indexExists('customers', 'idx_customers_email')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->index('email', 'idx_customers_email');
            });
        }
        
        if (!$this->indexExists('customers', 'idx_customers_company_phone')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->index(['company_id', 'phone'], 'idx_customers_company_phone');
            });
        }
        
        // Staff table indexes - only if is_active column exists
        if (Schema::hasColumn('staff', 'is_active')) {
            if (!$this->indexExists('staff', 'idx_staff_branch_active')) {
                Schema::table('staff', function (Blueprint $table) {
                    $table->index(['branch_id', 'is_active'], 'idx_staff_branch_active');
                });
            }
        } else {
            // Create index without is_active if column doesn't exist
            if (!$this->indexExists('staff', 'idx_staff_branch')) {
                Schema::table('staff', function (Blueprint $table) {
                    $table->index('branch_id', 'idx_staff_branch');
                });
            }
        }
        
        // Webhook events indexes
        if (Schema::hasTable('webhook_events')) {
            if (!$this->indexExists('webhook_events', 'idx_webhook_events_status')) {
                Schema::table('webhook_events', function (Blueprint $table) {
                    $table->index(['status', 'created_at'], 'idx_webhook_events_status');
                });
            }
        }
        
        // Sessions table (for database driver)
        if (Schema::hasTable('sessions')) {
            if (!$this->indexExists('sessions', 'idx_sessions_last_activity')) {
                Schema::table('sessions', function (Blueprint $table) {
                    $table->index('last_activity', 'idx_sessions_last_activity');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes
        $indexes = [
            'calls' => [
                'idx_calls_company_created',
                'idx_calls_branch_status',
                'idx_calls_customer_id'
            ],
            'appointments' => [
                'idx_appointments_branch_starts',
                'idx_appointments_staff_date',
                'idx_appointments_status'
            ],
            'customers' => [
                'idx_customers_phone',
                'idx_customers_email',
                'idx_customers_company_phone'
            ],
            'staff' => [
                'idx_staff_branch_active'
            ],
            'webhook_events' => [
                'idx_webhook_events_status'
            ],
            'sessions' => [
                'idx_sessions_last_activity'
            ]
        ];
        
        foreach ($indexes as $table => $tableIndexes) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) use ($tableIndexes) {
                    foreach ($tableIndexes as $index) {
                        if ($this->indexExists($table->getTable(), $index)) {
                            $table->dropIndex($index);
                        }
                    }
                });
            }
        }
    }
};