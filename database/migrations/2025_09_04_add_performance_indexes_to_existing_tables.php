<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add performance indexes to existing production tables
     * Safe to run on production - only adds missing indexes
     */
    public function up(): void
    {
        // Helper function to safely add index if it doesn't exist
        $addIndexIfNotExists = function($table, $column, $indexName) {
            $exists = DB::select("
                SELECT COUNT(1) as count 
                FROM information_schema.statistics 
                WHERE table_schema = DATABASE() 
                AND table_name = ? 
                AND index_name = ?
            ", [$table, $indexName]);
            
            if ($exists[0]->count == 0) {
                DB::statement("ALTER TABLE `{$table}` ADD INDEX `{$indexName}` (`{$column}`)");
                echo "✅ Added index {$indexName} on {$table}.{$column}\n";
            } else {
                echo "⏭️  Index {$indexName} already exists on {$table}\n";
            }
        };

        // Helper for composite indexes
        $addCompositeIndexIfNotExists = function($table, $columns, $indexName) {
            $exists = DB::select("
                SELECT COUNT(1) as count 
                FROM information_schema.statistics 
                WHERE table_schema = DATABASE() 
                AND table_name = ? 
                AND index_name = ?
            ", [$table, $indexName]);
            
            if ($exists[0]->count == 0) {
                $columnList = implode('`, `', $columns);
                DB::statement("ALTER TABLE `{$table}` ADD INDEX `{$indexName}` (`{$columnList}`)");
                echo "✅ Added composite index {$indexName} on {$table}\n";
            } else {
                echo "⏭️  Composite index {$indexName} already exists on {$table}\n";
            }
        };

        echo "\n🚀 Starting Performance Index Optimization...\n\n";

        // CALLS TABLE INDEXES
        if (Schema::hasTable('calls')) {
            echo "📞 Optimizing calls table...\n";
            
            // Check if tenant_id column exists before adding index
            if (Schema::hasColumn('calls', 'tenant_id')) {
                $addIndexIfNotExists('calls', 'tenant_id', 'idx_calls_tenant_id');
                $addCompositeIndexIfNotExists('calls', ['tenant_id', 'created_at'], 'idx_calls_tenant_created');
                $addCompositeIndexIfNotExists('calls', ['tenant_id', 'call_successful'], 'idx_calls_tenant_status');
            }
            
            if (Schema::hasColumn('calls', 'customer_id')) {
                $addIndexIfNotExists('calls', 'customer_id', 'idx_calls_customer_id');
            }
            
            if (Schema::hasColumn('calls', 'agent_id')) {
                $addIndexIfNotExists('calls', 'agent_id', 'idx_calls_agent_id');
            }
            
            if (Schema::hasColumn('calls', 'call_id')) {
                $addIndexIfNotExists('calls', 'call_id', 'idx_calls_call_id');
            }
            
            if (Schema::hasColumn('calls', 'conversation_id')) {
                $addIndexIfNotExists('calls', 'conversation_id', 'idx_calls_conversation_id');
            }
            
            $addIndexIfNotExists('calls', 'created_at', 'idx_calls_created_at');
        }

        // APPOINTMENTS TABLE INDEXES
        if (Schema::hasTable('appointments')) {
            echo "\n📅 Optimizing appointments table...\n";
            
            if (Schema::hasColumn('appointments', 'tenant_id')) {
                $addIndexIfNotExists('appointments', 'tenant_id', 'idx_appointments_tenant_id');
                $addCompositeIndexIfNotExists('appointments', ['tenant_id', 'start_time'], 'idx_appointments_tenant_start');
                $addCompositeIndexIfNotExists('appointments', ['tenant_id', 'status'], 'idx_appointments_tenant_status');
            }
            
            if (Schema::hasColumn('appointments', 'customer_id')) {
                $addIndexIfNotExists('appointments', 'customer_id', 'idx_appointments_customer_id');
            }
            
            if (Schema::hasColumn('appointments', 'staff_id')) {
                $addIndexIfNotExists('appointments', 'staff_id', 'idx_appointments_staff_id');
            }
            
            if (Schema::hasColumn('appointments', 'service_id')) {
                $addIndexIfNotExists('appointments', 'service_id', 'idx_appointments_service_id');
            }
            
            if (Schema::hasColumn('appointments', 'start_time')) {
                $addIndexIfNotExists('appointments', 'start_time', 'idx_appointments_start_time');
            }
        }

        // CUSTOMERS TABLE INDEXES
        if (Schema::hasTable('customers')) {
            echo "\n👥 Optimizing customers table...\n";
            
            if (Schema::hasColumn('customers', 'tenant_id')) {
                $addIndexIfNotExists('customers', 'tenant_id', 'idx_customers_tenant_id');
                $addCompositeIndexIfNotExists('customers', ['tenant_id', 'created_at'], 'idx_customers_tenant_created');
            }
            
            if (Schema::hasColumn('customers', 'email')) {
                $addIndexIfNotExists('customers', 'email', 'idx_customers_email');
                
                if (Schema::hasColumn('customers', 'tenant_id')) {
                    $addCompositeIndexIfNotExists('customers', ['email', 'tenant_id'], 'idx_customers_email_tenant');
                }
            }
            
            if (Schema::hasColumn('customers', 'phone')) {
                $addIndexIfNotExists('customers', 'phone', 'idx_customers_phone');
            }
        }

        // USERS TABLE INDEXES
        if (Schema::hasTable('users')) {
            echo "\n👤 Optimizing users table...\n";
            
            if (Schema::hasColumn('users', 'tenant_id')) {
                $addIndexIfNotExists('users', 'tenant_id', 'idx_users_tenant_id');
                $addCompositeIndexIfNotExists('users', ['email', 'tenant_id'], 'idx_users_email_tenant');
            }
        }

        // STAFF TABLE INDEXES
        if (Schema::hasTable('staff')) {
            echo "\n👨‍💼 Optimizing staff table...\n";
            
            if (Schema::hasColumn('staff', 'tenant_id')) {
                $addIndexIfNotExists('staff', 'tenant_id', 'idx_staff_tenant_id');
            }
            
            if (Schema::hasColumn('staff', 'branch_id')) {
                $addIndexIfNotExists('staff', 'branch_id', 'idx_staff_branch_id');
            }
            
            if (Schema::hasColumn('staff', 'home_branch_id')) {
                $addIndexIfNotExists('staff', 'home_branch_id', 'idx_staff_home_branch_id');
            }
        }

        // SERVICES TABLE INDEXES
        if (Schema::hasTable('services')) {
            echo "\n🛠️ Optimizing services table...\n";
            
            if (Schema::hasColumn('services', 'tenant_id')) {
                $addIndexIfNotExists('services', 'tenant_id', 'idx_services_tenant_id');
                $addCompositeIndexIfNotExists('services', ['tenant_id', 'is_active'], 'idx_services_tenant_active');
            }
        }

        // BRANCHES TABLE INDEXES  
        if (Schema::hasTable('branches')) {
            echo "\n🏢 Optimizing branches table...\n";
            
            if (Schema::hasColumn('branches', 'tenant_id')) {
                $addIndexIfNotExists('branches', 'tenant_id', 'idx_branches_tenant_id');
            }
            
            if (Schema::hasColumn('branches', 'customer_id')) {
                $addIndexIfNotExists('branches', 'customer_id', 'idx_branches_customer_id');
            }
        }

        // INTEGRATIONS TABLE INDEXES
        if (Schema::hasTable('integrations')) {
            echo "\n🔌 Optimizing integrations table...\n";
            
            if (Schema::hasColumn('integrations', 'tenant_id')) {
                $addIndexIfNotExists('integrations', 'tenant_id', 'idx_integrations_tenant_id');
            }
            
            if (Schema::hasColumn('integrations', 'customer_id')) {
                $addIndexIfNotExists('integrations', 'customer_id', 'idx_integrations_customer_id');
            }
            
            $addIndexIfNotExists('integrations', 'type', 'idx_integrations_type');
        }

        // CALCOM TABLES
        if (Schema::hasTable('calcom_event_types')) {
            echo "\n📆 Optimizing Cal.com tables...\n";
            
            if (Schema::hasColumn('calcom_event_types', 'tenant_id')) {
                $addIndexIfNotExists('calcom_event_types', 'tenant_id', 'idx_calcom_event_types_tenant_id');
            }
            
            if (Schema::hasColumn('calcom_event_types', 'staff_id')) {
                $addIndexIfNotExists('calcom_event_types', 'staff_id', 'idx_calcom_event_types_staff_id');
            }
        }

        if (Schema::hasTable('calcom_bookings')) {
            if (Schema::hasColumn('calcom_bookings', 'tenant_id')) {
                $addIndexIfNotExists('calcom_bookings', 'tenant_id', 'idx_calcom_bookings_tenant_id');
            }
            
            if (Schema::hasColumn('calcom_bookings', 'appointment_id')) {
                $addIndexIfNotExists('calcom_bookings', 'appointment_id', 'idx_calcom_bookings_appointment_id');
            }
        }

        echo "\n✅ Performance index optimization complete!\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Index removal is intentionally not implemented
        // Removing indexes should be a deliberate decision
        echo "⚠️  Index removal not implemented for safety. Remove manually if needed.\n";
    }
};