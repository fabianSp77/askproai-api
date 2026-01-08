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
        // Appointments table indexes
        
        if (!Schema::hasTable('appointments')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table) {
            // Composite index for date range queries
            if (!Schema::hasIndex('appointments', 'idx_appointments_datetime')) {
                $table->index(['starts_at', 'ends_at'], 'idx_appointments_datetime');
            }

            // Compound index for staff scheduling
            if (!Schema::hasIndex('appointments', 'idx_appointments_staff_time')) {
                $table->index(['staff_id', 'starts_at', 'status'], 'idx_appointments_staff_time');
            }

            // Index for customer appointments
            if (!Schema::hasIndex('appointments', 'idx_appointments_customer_date')) {
                $table->index(['customer_id', 'starts_at'], 'idx_appointments_customer_date');
            }

            // Index for service appointments
            if (!Schema::hasIndex('appointments', 'idx_appointments_service')) {
                $table->index(['service_id', 'status'], 'idx_appointments_service');
            }

            // Index for branch filtering (if column exists)
            if (!Schema::hasIndex('appointments', 'idx_appointments_branch') &&
                Schema::hasColumn('appointments', 'branch_id')) {
                $table->index(['branch_id', 'starts_at'], 'idx_appointments_branch');
            }

            // Index for status filtering
            if (!Schema::hasIndex('appointments', 'idx_appointments_status')) {
                $table->index('status', 'idx_appointments_status');
            }
        });

        // Calls table indexes
        
        if (!Schema::hasTable('calls')) {
            return;
        }

        Schema::table('calls', function (Blueprint $table) {
            // Index for date range queries
            if (!Schema::hasIndex('calls', 'idx_calls_created')) {
                $table->index('created_at', 'idx_calls_created');
            }

            // Compound index for successful calls
            if (!Schema::hasIndex('calls', 'idx_calls_success_date')) {
                $table->index(['call_successful', 'created_at'], 'idx_calls_success_date');
            }

            // Index for appointment tracking
            if (!Schema::hasIndex('calls', 'idx_calls_appointment')) {
                $table->index(['appointment_made', 'created_at'], 'idx_calls_appointment');
            }

            // Index for customer calls
            if (!Schema::hasIndex('calls', 'idx_calls_customer')) {
                $table->index(['customer_id', 'created_at'], 'idx_calls_customer');
            }

            // Index for sentiment analysis
            if (!Schema::hasIndex('calls', 'idx_calls_sentiment')) {
                $table->index(['sentiment', 'created_at'], 'idx_calls_sentiment');
            }

            // Index for status filtering - skip if any status index exists (already created in create_calls_table)
            // $table->index('status', 'idx_calls_status'); // Already exists from create_calls_table
        });

        // Customers table indexes
        
        if (!Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            // Index for journey status queries
            if (!Schema::hasIndex('customers', 'idx_customers_journey') &&
                Schema::hasColumn('customers', 'journey_status') &&
                Schema::hasColumn('customers', 'last_appointment_at')) {
                $table->index(['journey_status', 'last_appointment_at'], 'idx_customers_journey');
            }

            // Index for engagement tracking (skip if columns don't exist)
            if (!Schema::hasIndex('customers', 'idx_customers_engagement') &&
                Schema::hasColumn('customers', 'engagement_score') &&
                Schema::hasColumn('customers', 'status')) {
                $table->index(['engagement_score', 'status'], 'idx_customers_engagement');
            }

            // Index for company filtering
            if (!Schema::hasIndex('customers', 'idx_customers_company') &&
                Schema::hasColumn('customers', 'company_id') &&
                Schema::hasColumn('customers', 'status')) {
                $table->index(['company_id', 'status'], 'idx_customers_company');
            }

            // Index for branch preferences
            if (!Schema::hasIndex('customers', 'idx_customers_branch') &&
                Schema::hasColumn('customers', 'preferred_branch_id')) {
                $table->index('preferred_branch_id', 'idx_customers_branch');
            }

            // Index for customer number lookup
            if (!Schema::hasIndex('customers', 'idx_customers_number') &&
                Schema::hasColumn('customers', 'customer_number')) {
                $table->index('customer_number', 'idx_customers_number');
            }

            // Index for email lookup (if not unique and column exists)
            if (!Schema::hasIndex('customers', 'idx_customers_email') &&
                Schema::hasColumn('customers', 'email')) {
                $table->index('email', 'idx_customers_email');
            }

            // Index for phone lookup (if column exists)
            if (!Schema::hasIndex('customers', 'idx_customers_phone') &&
                Schema::hasColumn('customers', 'phone')) {
                $table->index('phone', 'idx_customers_phone');
            }

            // Index for VIP customers
            if (!Schema::hasIndex('customers', 'idx_customers_vip') &&
                Schema::hasColumn('customers', 'is_vip') &&
                Schema::hasColumn('customers', 'total_revenue')) {
                $table->index(['is_vip', 'total_revenue'], 'idx_customers_vip');
            }
        });

        // Services table indexes
        
        if (!Schema::hasTable('services')) {
            return;
        }

        Schema::table('services', function (Blueprint $table) {
            // Index for active services
            if (!Schema::hasIndex('services', 'idx_services_active')) {
                $table->index(['company_id', 'is_active'], 'idx_services_active');
            }

            // Index for branch services (if column exists)
            if (!Schema::hasIndex('services', 'idx_services_branch') &&
                Schema::hasColumn('services', 'branch_id')) {
                $table->index(['branch_id', 'is_active'], 'idx_services_branch');
            }

            // Index for category filtering (if column exists)
            if (!Schema::hasIndex('services', 'idx_services_category') &&
                Schema::hasColumn('services', 'category')) {
                $table->index(['category', 'is_active'], 'idx_services_category');
            }

            // Index for cal.com sync (if column exists)
            if (!Schema::hasIndex('services', 'idx_services_calcom') &&
                Schema::hasColumn('services', 'calcom_event_type_id')) {
                $table->index('calcom_event_type_id', 'idx_services_calcom');
            }
        });

        // Staff table indexes
        
        if (!Schema::hasTable('staff')) {
            return;
        }

        Schema::table('staff', function (Blueprint $table) {
            // Index for active staff
            if (!Schema::hasIndex('staff', 'idx_staff_active')) {
                $table->index(['company_id', 'is_active'], 'idx_staff_active');
            }

            // Index for branch staff
            if (!Schema::hasIndex('staff', 'idx_staff_branch')) {
                $table->index(['branch_id', 'is_active'], 'idx_staff_branch');
            }
        });

        // Working hours table indexes
        
        if (!Schema::hasTable('working_hours')) {
            return;
        }

        Schema::table('working_hours', function (Blueprint $table) {
            // Index for staff schedule
            if (!Schema::hasIndex('working_hours', 'idx_working_hours_staff')) {
                $table->index(['staff_id', 'day_of_week', 'is_active'], 'idx_working_hours_staff');
            }

            // Index for company schedule
            if (!Schema::hasIndex('working_hours', 'idx_working_hours_company')) {
                $table->index(['company_id', 'day_of_week'], 'idx_working_hours_company');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop appointments indexes
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('idx_appointments_datetime');
            $table->dropIndex('idx_appointments_staff_time');
            $table->dropIndex('idx_appointments_customer_date');
            $table->dropIndex('idx_appointments_service');
            $table->dropIndex('idx_appointments_branch');
            $table->dropIndex('idx_appointments_status');
        });

        // Drop calls indexes
        Schema::table('calls', function (Blueprint $table) {
            $table->dropIndex('idx_calls_created');
            $table->dropIndex('idx_calls_success_date');
            $table->dropIndex('idx_calls_appointment');
            $table->dropIndex('idx_calls_customer');
            $table->dropIndex('idx_calls_sentiment');
            $table->dropIndex('idx_calls_status');
        });

        // Drop customers indexes
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('idx_customers_journey');
            $table->dropIndex('idx_customers_engagement');
            $table->dropIndex('idx_customers_company');
            $table->dropIndex('idx_customers_branch');
            $table->dropIndex('idx_customers_number');
            $table->dropIndex('idx_customers_email');
            $table->dropIndex('idx_customers_phone');
            $table->dropIndex('idx_customers_vip');
        });

        // Drop services indexes
        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex('idx_services_active');
            $table->dropIndex('idx_services_branch');
            $table->dropIndex('idx_services_category');
            $table->dropIndex('idx_services_calcom');
        });

        // Drop staff indexes
        Schema::table('staff', function (Blueprint $table) {
            $table->dropIndex('idx_staff_active');
            $table->dropIndex('idx_staff_branch');
        });

        // Drop working hours indexes
        Schema::table('working_hours', function (Blueprint $table) {
            $table->dropIndex('idx_working_hours_staff');
            $table->dropIndex('idx_working_hours_company');
        });
    }
};