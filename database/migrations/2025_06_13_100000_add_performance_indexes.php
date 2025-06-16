<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Check if index exists
     */
    private function indexExists($table, $indexName): bool
    {
        // Skip index checks in SQLite for testing
        if (config('database.default') === 'sqlite') {
            return false; // Always allow index creation in SQLite tests
        }
        
        $results = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return count($results) > 0;
    }
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Appointments table indexes
        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                if (!$this->indexExists('appointments', 'idx_appointments_staff_starts')) {
                    $table->index(['staff_id', 'starts_at'], 'idx_appointments_staff_starts');
                }
                if (!$this->indexExists('appointments', 'idx_appointments_customer_starts')) {
                    $table->index(['customer_id', 'starts_at'], 'idx_appointments_customer_starts');
                }
                if (!$this->indexExists('appointments', 'idx_appointments_status_starts')) {
                    $table->index(['status', 'starts_at'], 'idx_appointments_status_starts');
                }
                
                // Only add branch_id index if column exists
                if (Schema::hasColumn('appointments', 'branch_id') && !$this->indexExists('appointments', 'idx_appointments_branch_starts')) {
                    $table->index(['branch_id', 'starts_at'], 'idx_appointments_branch_starts');
                }
                
                if (Schema::hasColumn('appointments', 'calcom_event_type_id') && !$this->indexExists('appointments', 'idx_appointments_event_status')) {
                    $table->index(['calcom_event_type_id', 'status'], 'idx_appointments_event_status');
                }
                
                if (!$this->indexExists('appointments', 'idx_appointments_created_starts')) {
                    $table->index(['created_at', 'starts_at'], 'idx_appointments_created_starts');
                }
            });
        }
        
        // Staff table indexes
        if (Schema::hasTable('staff')) {
            Schema::table('staff', function (Blueprint $table) {
                if (!$this->indexExists('staff', 'idx_staff_company_active')) {
                    $table->index(['company_id', 'active'], 'idx_staff_company_active');
                }
                if (Schema::hasColumn('staff', 'is_bookable') && !$this->indexExists('staff', 'idx_staff_company_bookable')) {
                    $table->index(['company_id', 'is_bookable'], 'idx_staff_company_bookable');
                }
            });
        }
        
        // CalcomEventTypes table indexes
        if (Schema::hasTable('calcom_event_types')) {
            Schema::table('calcom_event_types', function (Blueprint $table) {
                if (!$this->indexExists('calcom_event_types', 'idx_event_types_company_active')) {
                    $table->index(['company_id', 'is_active'], 'idx_event_types_company_active');
                }
                if (Schema::hasColumn('calcom_event_types', 'branch_id') && !$this->indexExists('calcom_event_types', 'idx_event_types_branch_active')) {
                    $table->index(['branch_id', 'is_active'], 'idx_event_types_branch_active');
                }
                if (!$this->indexExists('calcom_event_types', 'idx_event_types_duration')) {
                    $table->index(['duration_minutes'], 'idx_event_types_duration');
                }
                if (!$this->indexExists('calcom_event_types', 'idx_event_types_price')) {
                    $table->index(['price'], 'idx_event_types_price');
                }
            });
        }
        
        // Working hours indexes
        if (Schema::hasTable('working_hours')) {
            Schema::table('working_hours', function (Blueprint $table) {
                if (!$this->indexExists('working_hours', 'idx_working_hours_staff_day')) {
                    $table->index(['staff_id', 'day_of_week'], 'idx_working_hours_staff_day');
                }
                if (Schema::hasColumn('working_hours', 'is_working_day') && !$this->indexExists('working_hours', 'idx_working_hours_staff_working')) {
                    $table->index(['staff_id', 'is_working_day'], 'idx_working_hours_staff_working');
                }
            });
        }
        
        // Staff event types indexes
        if (Schema::hasTable('staff_event_types')) {
            Schema::table('staff_event_types', function (Blueprint $table) {
                if (!$this->indexExists('staff_event_types', 'idx_staff_event_types_staff')) {
                    $table->index(['staff_id'], 'idx_staff_event_types_staff');
                }
                if (!$this->indexExists('staff_event_types', 'idx_staff_event_types_event')) {
                    $table->index(['event_type_id'], 'idx_staff_event_types_event');
                }
            });
        }
        
        // Customers indexes
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if (!$this->indexExists('customers', 'idx_customers_company_email')) {
                    $table->index(['company_id', 'email'], 'idx_customers_company_email');
                }
                if (Schema::hasColumn('customers', 'push_token') && !$this->indexExists('customers', 'idx_customers_push_token')) {
                    $table->index(['push_token'], 'idx_customers_push_token');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                if ($this->indexExists('appointments', 'idx_appointments_staff_starts')) {
                    $table->dropIndex('idx_appointments_staff_starts');
                }
                if ($this->indexExists('appointments', 'idx_appointments_customer_starts')) {
                    $table->dropIndex('idx_appointments_customer_starts');
                }
                if ($this->indexExists('appointments', 'idx_appointments_status_starts')) {
                    $table->dropIndex('idx_appointments_status_starts');
                }
                if ($this->indexExists('appointments', 'idx_appointments_branch_starts')) {
                    $table->dropIndex('idx_appointments_branch_starts');
                }
                if ($this->indexExists('appointments', 'idx_appointments_event_status')) {
                    $table->dropIndex('idx_appointments_event_status');
                }
                if ($this->indexExists('appointments', 'idx_appointments_created_starts')) {
                    $table->dropIndex('idx_appointments_created_starts');
                }
            });
        }
        
        if (Schema::hasTable('staff')) {
            Schema::table('staff', function (Blueprint $table) {
                if ($this->indexExists('staff', 'idx_staff_company_active')) {
                    $table->dropIndex('idx_staff_company_active');
                }
                if ($this->indexExists('staff', 'idx_staff_company_bookable')) {
                    $table->dropIndex('idx_staff_company_bookable');
                }
            });
        }
        
        if (Schema::hasTable('calcom_event_types')) {
            Schema::table('calcom_event_types', function (Blueprint $table) {
                if ($this->indexExists('calcom_event_types', 'idx_event_types_company_active')) {
                    $table->dropIndex('idx_event_types_company_active');
                }
                if ($this->indexExists('calcom_event_types', 'idx_event_types_branch_active')) {
                    $table->dropIndex('idx_event_types_branch_active');
                }
                if ($this->indexExists('calcom_event_types', 'idx_event_types_duration')) {
                    $table->dropIndex('idx_event_types_duration');
                }
                if ($this->indexExists('calcom_event_types', 'idx_event_types_price')) {
                    $table->dropIndex('idx_event_types_price');
                }
            });
        }
        
        if (Schema::hasTable('working_hours')) {
            Schema::table('working_hours', function (Blueprint $table) {
                if ($this->indexExists('working_hours', 'idx_working_hours_staff_day')) {
                    $table->dropIndex('idx_working_hours_staff_day');
                }
                if ($this->indexExists('working_hours', 'idx_working_hours_staff_working')) {
                    $table->dropIndex('idx_working_hours_staff_working');
                }
            });
        }
        
        if (Schema::hasTable('staff_event_types')) {
            Schema::table('staff_event_types', function (Blueprint $table) {
                if ($this->indexExists('staff_event_types', 'idx_staff_event_types_staff')) {
                    $table->dropIndex('idx_staff_event_types_staff');
                }
                if ($this->indexExists('staff_event_types', 'idx_staff_event_types_event')) {
                    $table->dropIndex('idx_staff_event_types_event');
                }
            });
        }
        
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if ($this->indexExists('customers', 'idx_customers_company_email')) {
                    $table->dropIndex('idx_customers_company_email');
                }
                if ($this->indexExists('customers', 'idx_customers_push_token')) {
                    $table->dropIndex('idx_customers_push_token');
                }
            });
        }
    }
};