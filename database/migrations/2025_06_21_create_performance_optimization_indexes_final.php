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
        // Phone number lookups - most critical path
        if (!$this->indexExists('phone_numbers', 'idx_phone_branch_lookup')) {
            Schema::table('phone_numbers', function (Blueprint $table) {
                $table->index(['number', 'branch_id', 'is_active'], 'idx_phone_branch_lookup');
            });
        }

        // Branch performance indexes
        if (!$this->indexExists('branches', 'idx_company_active_branches')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->index(['company_id', 'is_active', 'id'], 'idx_company_active_branches');
            });
        }
        
        if (!$this->indexExists('branches', 'idx_calcom_event_lookup')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->index(['calcom_event_type_id', 'is_active'], 'idx_calcom_event_lookup');
            });
        }

        // Appointments performance indexes
        if (!$this->indexExists('appointments', 'idx_branch_appointments_time')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->index(['branch_id', 'starts_at', 'status'], 'idx_branch_appointments_time');
            });
        }
        
        if (!$this->indexExists('appointments', 'idx_customer_appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->index(['customer_id', 'starts_at', 'status'], 'idx_customer_appointments');
            });
        }
        
        if (!$this->indexExists('appointments', 'idx_staff_schedule')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->index(['staff_id', 'starts_at', 'ends_at'], 'idx_staff_schedule');
            });
        }

        // Calls performance indexes
        if (!$this->indexExists('calls', 'idx_company_recent_calls')) {
            Schema::table('calls', function (Blueprint $table) {
                $table->index(['company_id', 'created_at', 'call_status'], 'idx_company_recent_calls');
            });
        }
        
        if (!$this->indexExists('calls', 'idx_phone_call_history')) {
            Schema::table('calls', function (Blueprint $table) {
                $table->index(['from_number', 'created_at'], 'idx_phone_call_history');
            });
        }
        
        if (!$this->indexExists('calls', 'idx_retell_call_status')) {
            Schema::table('calls', function (Blueprint $table) {
                $table->index(['retell_call_id', 'call_status'], 'idx_retell_call_status');
            });
        }

        // Staff performance indexes
        if (!$this->indexExists('staff', 'idx_branch_active_staff')) {
            Schema::table('staff', function (Blueprint $table) {
                $table->index(['branch_id', 'is_active', 'id'], 'idx_branch_active_staff');
            });
        }
        
        if (!$this->indexExists('staff', 'idx_company_staff')) {
            Schema::table('staff', function (Blueprint $table) {
                $table->index(['company_id', 'is_active'], 'idx_company_staff');
            });
        }

        // Customers performance indexes
        if (!$this->indexExists('customers', 'idx_customer_phone_lookup')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->index(['phone', 'company_id'], 'idx_customer_phone_lookup');
            });
        }
        
        if (!$this->indexExists('customers', 'idx_customer_email_lookup')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->index(['email', 'company_id'], 'idx_customer_email_lookup');
            });
        }
        
        if (!$this->indexExists('customers', 'idx_company_recent_customers')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->index(['company_id', 'created_at'], 'idx_company_recent_customers');
            });
        }

        // Services performance indexes - only if is_active column exists
        if (Schema::hasColumn('services', 'is_active')) {
            if (!$this->indexExists('services', 'idx_company_active_services')) {
                Schema::table('services', function (Blueprint $table) {
                    $table->index(['company_id', 'is_active', 'id'], 'idx_company_active_services');
                });
            }
        } else {
            // Index without is_active if column doesn't exist
            if (!$this->indexExists('services', 'idx_company_services')) {
                Schema::table('services', function (Blueprint $table) {
                    $table->index(['company_id', 'id'], 'idx_company_services');
                });
            }
        }

        // Create database-specific optimizations
        if (DB::connection()->getDriverName() === 'mysql') {
            // Check if tables exist before optimizing
            $tables = ['appointments', 'calls', 'customers', 'phone_numbers', 'branches'];
            $existingTables = [];
            
            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    $existingTables[] = $table;
                }
            }
            
            if (!empty($existingTables)) {
                // Add table statistics
                try {
                    DB::statement('ANALYZE TABLE ' . implode(', ', $existingTables));
                } catch (\Exception $e) {
                    // Ignore analyze errors
                }
            }
        }
    }

    /**
     * Check if an index exists
     */
    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $connection = config('database.default');
            $database = config("database.connections.{$connection}.database");
            
            $result = DB::select("
                SELECT COUNT(*) as count 
                FROM information_schema.statistics 
                WHERE table_schema = ? 
                AND table_name = ? 
                AND index_name = ?
            ", [$database, $table, $indexName]);
            
            return $result[0]->count > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes in reverse order
        $indexesToDrop = [
            ['phone_numbers', 'idx_phone_branch_lookup'],
            ['branches', 'idx_company_active_branches'],
            ['branches', 'idx_calcom_event_lookup'],
            ['appointments', 'idx_branch_appointments_time'],
            ['appointments', 'idx_customer_appointments'],
            ['appointments', 'idx_staff_schedule'],
            ['calls', 'idx_company_recent_calls'],
            ['calls', 'idx_phone_call_history'],
            ['calls', 'idx_retell_call_status'],
            ['staff', 'idx_branch_active_staff'],
            ['staff', 'idx_company_staff'],
            ['customers', 'idx_customer_phone_lookup'],
            ['customers', 'idx_customer_email_lookup'],
            ['customers', 'idx_company_recent_customers'],
            ['services', 'idx_company_active_services'],
            ['services', 'idx_company_services'],
        ];
        
        foreach ($indexesToDrop as [$table, $index]) {
            if (Schema::hasTable($table) && $this->indexExists($table, $index)) {
                Schema::table($table, function (Blueprint $table) use ($index) {
                    $table->dropIndex($index);
                });
            }
        }
    }
};