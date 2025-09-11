<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Performance Index Optimization
     * Adds critical indexes for foreign keys and common query patterns
     */
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════════
        // CALLS TABLE INDEXES - High Priority
        // ═══════════════════════════════════════════════════════════
        
        Schema::table('calls', function (Blueprint $table) {
            // Foreign key indexes (if not already present)
            $table->index('tenant_id', 'idx_calls_tenant_id');
            $table->index('customer_id', 'idx_calls_customer_id');
            $table->index('agent_id', 'idx_calls_agent_id');
            $table->index('branch_id', 'idx_calls_branch_id');
            $table->index('appointment_id', 'idx_calls_appointment_id');
            
            // Composite indexes for common query patterns
            $table->index(['tenant_id', 'created_at'], 'idx_calls_tenant_created');
            $table->index(['tenant_id', 'call_successful'], 'idx_calls_tenant_success');
            $table->index(['tenant_id', 'start_timestamp'], 'idx_calls_tenant_start');
            $table->index(['call_successful', 'start_timestamp'], 'idx_calls_success_start');
            
            // Performance indexes for dashboard queries
            $table->index('call_successful', 'idx_calls_successful');
            $table->index('start_timestamp', 'idx_calls_start_timestamp');
            $table->index('end_timestamp', 'idx_calls_end_timestamp');
            $table->index('from_number', 'idx_calls_from_number');
            
            // Reporting and analytics indexes
            $table->index(['tenant_id', 'start_timestamp', 'call_successful'], 'idx_calls_tenant_start_success');
        });

        // ═══════════════════════════════════════════════════════════
        // APPOINTMENTS TABLE INDEXES
        // ═══════════════════════════════════════════════════════════
        
        Schema::table('appointments', function (Blueprint $table) {
            // Foreign key indexes
            $table->index('tenant_id', 'idx_appointments_tenant_id');
            $table->index('customer_id', 'idx_appointments_customer_id');
            $table->index('staff_id', 'idx_appointments_staff_id');
            $table->index('service_id', 'idx_appointments_service_id');
            $table->index('branch_id', 'idx_appointments_branch_id');
            $table->index('call_id', 'idx_appointments_call_id');
            
            // Composite indexes for common queries
            $table->index(['tenant_id', 'status'], 'idx_appointments_tenant_status');
            $table->index(['tenant_id', 'starts_at'], 'idx_appointments_tenant_starts');
            $table->index(['tenant_id', 'created_at'], 'idx_appointments_tenant_created');
            $table->index(['status', 'starts_at'], 'idx_appointments_status_starts');
            
            // Performance indexes for scheduling queries
            $table->index('starts_at', 'idx_appointments_starts_at');
            $table->index('ends_at', 'idx_appointments_ends_at');
            $table->index('status', 'idx_appointments_status');
            $table->index('calcom_booking_id', 'idx_appointments_calcom_booking');
            
            // Dashboard and reporting indexes
            $table->index(['staff_id', 'starts_at', 'status'], 'idx_appointments_staff_starts_status');
            $table->index(['customer_id', 'starts_at'], 'idx_appointments_customer_starts');
        });

        // ═══════════════════════════════════════════════════════════
        // CUSTOMERS TABLE INDEXES
        // ═══════════════════════════════════════════════════════════
        
        Schema::table('customers', function (Blueprint $table) {
            // Foreign key indexes
            $table->index('tenant_id', 'idx_customers_tenant_id');
            
            // Composite indexes for tenant-scoped queries
            $table->index(['tenant_id', 'created_at'], 'idx_customers_tenant_created');
            $table->index(['tenant_id', 'name'], 'idx_customers_tenant_name');
            $table->index(['tenant_id', 'email'], 'idx_customers_tenant_email');
            
            // Search and lookup indexes
            $table->index('phone', 'idx_customers_phone');
            $table->index('birthdate', 'idx_customers_birthdate');
            
            // Full-text search preparation
            $table->index(['name', 'email', 'phone'], 'idx_customers_search_fields');
        });

        // ═══════════════════════════════════════════════════════════
        // USERS TABLE INDEXES (Additional)
        // ═══════════════════════════════════════════════════════════
        
        Schema::table('users', function (Blueprint $table) {
            // Composite indexes for authentication and authorization
            $table->index(['email', 'tenant_id'], 'idx_users_email_tenant');
            $table->index(['tenant_id', 'role'], 'idx_users_tenant_role');
            $table->index(['tenant_id', 'is_active'], 'idx_users_tenant_active');
            
            // Performance indexes
            $table->index('role', 'idx_users_role');
            $table->index('is_active', 'idx_users_active');
        });

        // ═══════════════════════════════════════════════════════════
        // STAFF TABLE INDEXES
        // ═══════════════════════════════════════════════════════════
        
        if (Schema::hasTable('staff')) {
            Schema::table('staff', function (Blueprint $table) {
                // Foreign key indexes
                $table->index('tenant_id', 'idx_staff_tenant_id');
                $table->index('home_branch_id', 'idx_staff_home_branch');
                
                // Composite indexes
                $table->index(['tenant_id', 'name'], 'idx_staff_tenant_name');
                $table->index(['tenant_id', 'created_at'], 'idx_staff_tenant_created');
                
                // Performance indexes
                $table->index('email', 'idx_staff_email');
                $table->index('phone', 'idx_staff_phone');
            });
        }

        // ═══════════════════════════════════════════════════════════
        // SERVICES TABLE INDEXES
        // ═══════════════════════════════════════════════════════════
        
        if (Schema::hasTable('services')) {
            Schema::table('services', function (Blueprint $table) {
                // Foreign key indexes
                $table->index('tenant_id', 'idx_services_tenant_id');
                
                // Composite indexes for tenant-scoped queries
                $table->index(['tenant_id', 'name'], 'idx_services_tenant_name');
                $table->index(['tenant_id', 'created_at'], 'idx_services_tenant_created');
                
                // Performance indexes
                $table->index('duration_minutes', 'idx_services_duration');
                $table->index('price_cents', 'idx_services_price');
            });
        }

        // ═══════════════════════════════════════════════════════════
        // BRANCHES TABLE INDEXES
        // ═══════════════════════════════════════════════════════════
        
        if (Schema::hasTable('branches')) {
            Schema::table('branches', function (Blueprint $table) {
                // Foreign key indexes
                $table->index('tenant_id', 'idx_branches_tenant_id');
                $table->index('customer_id', 'idx_branches_customer_id');
                
                // Composite indexes
                $table->index(['tenant_id', 'name'], 'idx_branches_tenant_name');
                $table->index(['tenant_id', 'created_at'], 'idx_branches_tenant_created');
                
                // Performance indexes
                $table->index('phone', 'idx_branches_phone');
            });
        }

        // ═══════════════════════════════════════════════════════════
        // INTEGRATION-SPECIFIC INDEXES
        // ═══════════════════════════════════════════════════════════
        
        // CalCom Event Types
        if (Schema::hasTable('calcom_event_types')) {
            Schema::table('calcom_event_types', function (Blueprint $table) {
                $table->index('tenant_id', 'idx_calcom_event_types_tenant_id');
                $table->index('staff_id', 'idx_calcom_event_types_staff_id');
                $table->index('calcom_id', 'idx_calcom_event_types_calcom_id');
                $table->index('slug', 'idx_calcom_event_types_slug');
                $table->index(['tenant_id', 'calcom_id'], 'idx_calcom_event_types_tenant_calcom');
            });
        }

        // CalCom Bookings
        if (Schema::hasTable('calcom_bookings')) {
            Schema::table('calcom_bookings', function (Blueprint $table) {
                $table->index('tenant_id', 'idx_calcom_bookings_tenant_id');
                $table->index('appointment_id', 'idx_calcom_bookings_appointment_id');
                $table->index('calcom_booking_id', 'idx_calcom_bookings_calcom_id');
                $table->index(['tenant_id', 'start_time'], 'idx_calcom_bookings_tenant_start');
                $table->index('attendee_email', 'idx_calcom_bookings_attendee_email');
            });
        }

        // Working Hours
        if (Schema::hasTable('working_hours')) {
            Schema::table('working_hours', function (Blueprint $table) {
                $table->index('tenant_id', 'idx_working_hours_tenant_id');
                $table->index('staff_id', 'idx_working_hours_staff_id');
                $table->index('branch_id', 'idx_working_hours_branch_id');
                $table->index(['tenant_id', 'day_of_week'], 'idx_working_hours_tenant_day');
                $table->index('day_of_week', 'idx_working_hours_day');
            });
        }

        // ═══════════════════════════════════════════════════════════
        // SYSTEM & AUDIT INDEXES
        // ═══════════════════════════════════════════════════════════
        
        // Activity Log (Spatie)
        if (Schema::hasTable('activity_log')) {
            Schema::table('activity_log', function (Blueprint $table) {
                $table->index('log_name', 'idx_activity_log_name');
                $table->index('subject_type', 'idx_activity_log_subject_type');
                $table->index(['subject_type', 'subject_id'], 'idx_activity_log_subject');
                $table->index('causer_type', 'idx_activity_log_causer_type');
                $table->index(['causer_type', 'causer_id'], 'idx_activity_log_causer');
                $table->index('created_at', 'idx_activity_log_created');
            });
        }

        // Failed Jobs
        if (Schema::hasTable('failed_jobs')) {
            Schema::table('failed_jobs', function (Blueprint $table) {
                $table->index('queue', 'idx_failed_jobs_queue');
                $table->index('failed_at', 'idx_failed_jobs_failed_at');
                $table->index(['queue', 'failed_at'], 'idx_failed_jobs_queue_failed');
            });
        }

        // Jobs Queue
        if (Schema::hasTable('jobs')) {
            Schema::table('jobs', function (Blueprint $table) {
                $table->index('queue', 'idx_jobs_queue');
                $table->index('available_at', 'idx_jobs_available_at');
                $table->index('reserved_at', 'idx_jobs_reserved_at');
                $table->index(['queue', 'reserved_at'], 'idx_jobs_queue_reserved');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes in reverse order to avoid dependency issues
        
        // System tables
        if (Schema::hasTable('jobs')) {
            Schema::table('jobs', function (Blueprint $table) {
                $table->dropIndex('idx_jobs_queue_reserved');
                $table->dropIndex('idx_jobs_reserved_at');
                $table->dropIndex('idx_jobs_available_at');
                $table->dropIndex('idx_jobs_queue');
            });
        }

        if (Schema::hasTable('failed_jobs')) {
            Schema::table('failed_jobs', function (Blueprint $table) {
                $table->dropIndex('idx_failed_jobs_queue_failed');
                $table->dropIndex('idx_failed_jobs_failed_at');
                $table->dropIndex('idx_failed_jobs_queue');
            });
        }

        if (Schema::hasTable('activity_log')) {
            Schema::table('activity_log', function (Blueprint $table) {
                $table->dropIndex('idx_activity_log_created');
                $table->dropIndex('idx_activity_log_causer');
                $table->dropIndex('idx_activity_log_causer_type');
                $table->dropIndex('idx_activity_log_subject');
                $table->dropIndex('idx_activity_log_subject_type');
                $table->dropIndex('idx_activity_log_name');
            });
        }

        // Integration tables
        if (Schema::hasTable('working_hours')) {
            Schema::table('working_hours', function (Blueprint $table) {
                $table->dropIndex('idx_working_hours_day');
                $table->dropIndex('idx_working_hours_tenant_day');
                $table->dropIndex('idx_working_hours_branch_id');
                $table->dropIndex('idx_working_hours_staff_id');
                $table->dropIndex('idx_working_hours_tenant_id');
            });
        }

        if (Schema::hasTable('calcom_bookings')) {
            Schema::table('calcom_bookings', function (Blueprint $table) {
                $table->dropIndex('idx_calcom_bookings_attendee_email');
                $table->dropIndex('idx_calcom_bookings_tenant_start');
                $table->dropIndex('idx_calcom_bookings_calcom_id');
                $table->dropIndex('idx_calcom_bookings_appointment_id');
                $table->dropIndex('idx_calcom_bookings_tenant_id');
            });
        }

        if (Schema::hasTable('calcom_event_types')) {
            Schema::table('calcom_event_types', function (Blueprint $table) {
                $table->dropIndex('idx_calcom_event_types_tenant_calcom');
                $table->dropIndex('idx_calcom_event_types_slug');
                $table->dropIndex('idx_calcom_event_types_calcom_id');
                $table->dropIndex('idx_calcom_event_types_staff_id');
                $table->dropIndex('idx_calcom_event_types_tenant_id');
            });
        }

        // Core business tables
        if (Schema::hasTable('branches')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->dropIndex('idx_branches_phone');
                $table->dropIndex('idx_branches_tenant_created');
                $table->dropIndex('idx_branches_tenant_name');
                $table->dropIndex('idx_branches_customer_id');
                $table->dropIndex('idx_branches_tenant_id');
            });
        }

        if (Schema::hasTable('services')) {
            Schema::table('services', function (Blueprint $table) {
                $table->dropIndex('idx_services_price');
                $table->dropIndex('idx_services_duration');
                $table->dropIndex('idx_services_tenant_created');
                $table->dropIndex('idx_services_tenant_name');
                $table->dropIndex('idx_services_tenant_id');
            });
        }

        if (Schema::hasTable('staff')) {
            Schema::table('staff', function (Blueprint $table) {
                $table->dropIndex('idx_staff_phone');
                $table->dropIndex('idx_staff_email');
                $table->dropIndex('idx_staff_tenant_created');
                $table->dropIndex('idx_staff_tenant_name');
                $table->dropIndex('idx_staff_home_branch');
                $table->dropIndex('idx_staff_tenant_id');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_active');
            $table->dropIndex('idx_users_role');
            $table->dropIndex('idx_users_tenant_active');
            $table->dropIndex('idx_users_tenant_role');
            $table->dropIndex('idx_users_email_tenant');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('idx_customers_search_fields');
            $table->dropIndex('idx_customers_birthdate');
            $table->dropIndex('idx_customers_phone');
            $table->dropIndex('idx_customers_tenant_email');
            $table->dropIndex('idx_customers_tenant_name');
            $table->dropIndex('idx_customers_tenant_created');
            $table->dropIndex('idx_customers_tenant_id');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('idx_appointments_customer_starts');
            $table->dropIndex('idx_appointments_staff_starts_status');
            $table->dropIndex('idx_appointments_calcom_booking');
            $table->dropIndex('idx_appointments_status');
            $table->dropIndex('idx_appointments_ends_at');
            $table->dropIndex('idx_appointments_starts_at');
            $table->dropIndex('idx_appointments_status_starts');
            $table->dropIndex('idx_appointments_tenant_created');
            $table->dropIndex('idx_appointments_tenant_starts');
            $table->dropIndex('idx_appointments_tenant_status');
            $table->dropIndex('idx_appointments_call_id');
            $table->dropIndex('idx_appointments_branch_id');
            $table->dropIndex('idx_appointments_service_id');
            $table->dropIndex('idx_appointments_staff_id');
            $table->dropIndex('idx_appointments_customer_id');
            $table->dropIndex('idx_appointments_tenant_id');
        });

        Schema::table('calls', function (Blueprint $table) {
            $table->dropIndex('idx_calls_tenant_start_success');
            $table->dropIndex('idx_calls_from_number');
            $table->dropIndex('idx_calls_end_timestamp');
            $table->dropIndex('idx_calls_start_timestamp');
            $table->dropIndex('idx_calls_successful');
            $table->dropIndex('idx_calls_success_start');
            $table->dropIndex('idx_calls_tenant_start');
            $table->dropIndex('idx_calls_tenant_success');
            $table->dropIndex('idx_calls_tenant_created');
            $table->dropIndex('idx_calls_appointment_id');
            $table->dropIndex('idx_calls_branch_id');
            $table->dropIndex('idx_calls_agent_id');
            $table->dropIndex('idx_calls_customer_id');
            $table->dropIndex('idx_calls_tenant_id');
        });
    }
};