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
        // APPOINTMENTS TABLE - Most critical for performance
        Schema::table('appointments', function (Blueprint $table) {
            // Tenant isolation (most frequent WHERE clause)
            if (!$this->indexExists('appointments', 'appointments_company_id_index')) {
                $table->index('company_id', 'appointments_company_id_index');
            }
            
            // Time-based queries
            if (!$this->indexExists('appointments', 'appointments_starts_at_index')) {
                $table->index('starts_at', 'appointments_starts_at_index');
            }
            if (!$this->indexExists('appointments', 'appointments_ends_at_index')) {
                $table->index('ends_at', 'appointments_ends_at_index');
            }
            
            // Status filtering
            if (!$this->indexExists('appointments', 'appointments_status_index')) {
                $table->index('status', 'appointments_status_index');
            }
            
            // Foreign key lookups
            if (!$this->indexExists('appointments', 'appointments_customer_id_index')) {
                $table->index('customer_id', 'appointments_customer_id_index');
            }
            if (!$this->indexExists('appointments', 'appointments_branch_id_index')) {
                $table->index('branch_id', 'appointments_branch_id_index');
            }
            if (!$this->indexExists('appointments', 'appointments_staff_id_index')) {
                $table->index('staff_id', 'appointments_staff_id_index');
            }
            if (!$this->indexExists('appointments', 'appointments_service_id_index')) {
                $table->index('service_id', 'appointments_service_id_index');
            }
            
            // Cal.com integration
            if (!$this->indexExists('appointments', 'appointments_calcom_booking_id_index')) {
                $table->index('calcom_booking_id', 'appointments_calcom_booking_id_index');
            }
            if (!$this->indexExists('appointments', 'appointments_calcom_v2_booking_id_index')) {
                $table->index('calcom_v2_booking_id', 'appointments_calcom_v2_booking_id_index');
            }
            
            // Composite indexes for common query combinations
            if (!$this->indexExists('appointments', 'appointments_company_starts_at_index')) {
                $table->index(['company_id', 'starts_at'], 'appointments_company_starts_at_index');
            }
            if (!$this->indexExists('appointments', 'appointments_company_status_index')) {
                $table->index(['company_id', 'status'], 'appointments_company_status_index');
            }
            if (!$this->indexExists('appointments', 'appointments_status_starts_at_index')) {
                $table->index(['status', 'starts_at'], 'appointments_status_starts_at_index');
            }
            if (!$this->indexExists('appointments', 'appointments_branch_starts_at_index')) {
                $table->index(['branch_id', 'starts_at'], 'appointments_branch_starts_at_index');
            }
            if (!$this->indexExists('appointments', 'appointments_staff_starts_at_index')) {
                $table->index(['staff_id', 'starts_at'], 'appointments_staff_starts_at_index');
            }
            
            // Reminders
            if (!$this->indexExists('appointments', 'appointments_reminder_24h_sent_at_index')) {
                $table->index('reminder_24h_sent_at', 'appointments_reminder_24h_sent_at_index');
            }
        });
        
        // CALLS TABLE - Second most critical
        Schema::table('calls', function (Blueprint $table) {
            // Tenant isolation
            if (!$this->indexExists('calls', 'calls_company_id_index')) {
                $table->index('company_id', 'calls_company_id_index');
            }
            
            // Time-based queries
            if (!$this->indexExists('calls', 'calls_created_at_index')) {
                $table->index('created_at', 'calls_created_at_index');
            }
            if (!$this->indexExists('calls', 'calls_start_timestamp_index')) {
                $table->index('start_timestamp', 'calls_start_timestamp_index');
            }
            
            // Phone number lookups
            if (!$this->indexExists('calls', 'calls_from_number_index')) {
                $table->index('from_number', 'calls_from_number_index');
            }
            if (!$this->indexExists('calls', 'calls_to_number_index')) {
                $table->index('to_number', 'calls_to_number_index');
            }
            
            // Status and call tracking
            if (!$this->indexExists('calls', 'calls_call_status_index')) {
                $table->index('call_status', 'calls_call_status_index');
            }
            if (!$this->indexExists('calls', 'calls_retell_call_id_index')) {
                $table->unique('retell_call_id', 'calls_retell_call_id_index');
            }
            if (!$this->indexExists('calls', 'calls_call_id_index')) {
                $table->index('call_id', 'calls_call_id_index');
            }
            
            // Foreign keys
            if (!$this->indexExists('calls', 'calls_customer_id_index')) {
                $table->index('customer_id', 'calls_customer_id_index');
            }
            if (!$this->indexExists('calls', 'calls_appointment_id_index')) {
                $table->index('appointment_id', 'calls_appointment_id_index');
            }
            
            // Composite indexes
            if (!$this->indexExists('calls', 'calls_company_created_at_index')) {
                $table->index(['company_id', 'created_at'], 'calls_company_created_at_index');
            }
            if (!$this->indexExists('calls', 'calls_customer_created_at_index')) {
                $table->index(['customer_id', 'created_at'], 'calls_customer_created_at_index');
            }
            if (!$this->indexExists('calls', 'calls_company_call_status_index')) {
                $table->index(['company_id', 'call_status'], 'calls_company_call_status_index');
            }
            
            // Performance metrics
            if (!$this->indexExists('calls', 'calls_duration_sec_index')) {
                $table->index('duration_sec', 'calls_duration_sec_index');
            }
            if (!$this->indexExists('calls', 'calls_cost_index')) {
                $table->index('cost', 'calls_cost_index');
            }
        });
        
        // CUSTOMERS TABLE
        Schema::table('customers', function (Blueprint $table) {
            // Tenant isolation - only create if column exists
            if (Schema::hasColumn('customers', 'company_id') && !$this->indexExists('customers', 'customers_company_id_index')) {
                $table->index('company_id', 'customers_company_id_index');
            }
            
            // Phone lookup (critical for call matching)
            if (!$this->indexExists('customers', 'customers_phone_index')) {
                $table->index('phone', 'customers_phone_index');
            }
            
            // Email lookup
            if (!$this->indexExists('customers', 'customers_email_index')) {
                $table->index('email', 'customers_email_index');
            }
            
            // Composite for duplicate detection
            if (!$this->indexExists('customers', 'customers_company_phone_index')) {
                $table->index(['company_id', 'phone'], 'customers_company_phone_index');
            }
            if (!$this->indexExists('customers', 'customers_company_email_index')) {
                $table->index(['company_id', 'email'], 'customers_company_email_index');
            }
            
            // Search optimization
            if (!$this->indexExists('customers', 'customers_name_index')) {
                $table->index('name', 'customers_name_index');
            }
        });
        
        // BRANCHES TABLE
        Schema::table('branches', function (Blueprint $table) {
            if (!$this->indexExists('branches', 'branches_company_id_index')) {
                $table->index('company_id', 'branches_company_id_index');
            }
            if (!$this->indexExists('branches', 'branches_active_index')) {
                $table->index('active', 'branches_active_index');
            }
            if (!$this->indexExists('branches', 'branches_company_active_index')) {
                $table->index(['company_id', 'active'], 'branches_company_active_index');
            }
        });
        
        // STAFF TABLE
        Schema::table('staff', function (Blueprint $table) {
            if (!$this->indexExists('staff', 'staff_company_id_index')) {
                $table->index('company_id', 'staff_company_id_index');
            }
            if (!$this->indexExists('staff', 'staff_branch_id_index')) {
                $table->index('branch_id', 'staff_branch_id_index');
            }
            if (!$this->indexExists('staff', 'staff_email_index')) {
                $table->index('email', 'staff_email_index');
            }
            if (!$this->indexExists('staff', 'staff_company_branch_index')) {
                $table->index(['company_id', 'branch_id'], 'staff_company_branch_index');
            }
        });
        
        // SERVICES TABLE
        Schema::table('services', function (Blueprint $table) {
            if (!$this->indexExists('services', 'services_company_id_index')) {
                $table->index('company_id', 'services_company_id_index');
            }
            if (!$this->indexExists('services', 'services_deleted_at_index')) {
                $table->index('deleted_at', 'services_deleted_at_index');
            }
            if (!$this->indexExists('services', 'services_company_deleted_index')) {
                $table->index(['company_id', 'deleted_at'], 'services_company_deleted_index');
            }
        });
        
        // COMPANIES TABLE
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'is_active') && !$this->indexExists('companies', 'companies_is_active_index')) {
                $table->index('is_active', 'companies_is_active_index');
            }
            if (!$this->indexExists('companies', 'companies_deleted_at_index')) {
                $table->index('deleted_at', 'companies_deleted_at_index');
            }
            // subdomain column doesn't exist, skip this index
        });
        
        // CALCOM_EVENT_TYPES TABLE
        if (Schema::hasTable('calcom_event_types')) {
            Schema::table('calcom_event_types', function (Blueprint $table) {
                if (Schema::hasColumn('calcom_event_types', 'company_id') && !$this->indexExists('calcom_event_types', 'calcom_event_types_company_id_index')) {
                    $table->index('company_id', 'calcom_event_types_company_id_index');
                }
                if (Schema::hasColumn('calcom_event_types', 'calcom_event_type_id') && !$this->indexExists('calcom_event_types', 'calcom_event_types_calcom_event_type_id_index')) {
                    $table->index('calcom_event_type_id', 'calcom_event_types_calcom_event_type_id_index');
                }
                if (Schema::hasColumn('calcom_event_types', 'company_id') && 
                    Schema::hasColumn('calcom_event_types', 'calcom_event_type_id') && 
                    !$this->indexExists('calcom_event_types', 'calcom_event_types_company_calcom_index')) {
                    $table->unique(['company_id', 'calcom_event_type_id'], 'calcom_event_types_company_calcom_index');
                }
            });
        }
        
        // STAFF_EVENT_TYPES TABLE
        if (Schema::hasTable('staff_event_types')) {
            Schema::table('staff_event_types', function (Blueprint $table) {
                if (!$this->indexExists('staff_event_types', 'staff_event_types_staff_id_index')) {
                    $table->index('staff_id', 'staff_event_types_staff_id_index');
                }
                if (!$this->indexExists('staff_event_types', 'staff_event_types_event_type_id_index')) {
                    $table->index('event_type_id', 'staff_event_types_event_type_id_index');
                }
                if (!$this->indexExists('staff_event_types', 'staff_event_types_unique')) {
                    $table->unique(['staff_id', 'event_type_id'], 'staff_event_types_unique');
                }
            });
        }
        
        // WORKING_HOURS TABLE
        if (Schema::hasTable('working_hours')) {
            Schema::table('working_hours', function (Blueprint $table) {
                if (!$this->indexExists('working_hours', 'working_hours_staff_id_index')) {
                    $table->index('staff_id', 'working_hours_staff_id_index');
                }
                if (!$this->indexExists('working_hours', 'working_hours_day_of_week_index')) {
                    $table->index('day_of_week', 'working_hours_day_of_week_index');
                }
                if (!$this->indexExists('working_hours', 'working_hours_staff_day_index')) {
                    $table->index(['staff_id', 'day_of_week'], 'working_hours_staff_day_index');
                }
            });
        }
        
        // CALCOM_BOOKINGS TABLE
        if (Schema::hasTable('calcom_bookings')) {
            Schema::table('calcom_bookings', function (Blueprint $table) {
                if (!$this->indexExists('calcom_bookings', 'calcom_bookings_branch_id_index')) {
                    $table->index('branch_id', 'calcom_bookings_branch_id_index');
                }
                if (!$this->indexExists('calcom_bookings', 'calcom_bookings_calcom_uid_index')) {
                    $table->unique('calcom_uid', 'calcom_bookings_calcom_uid_index');
                }
                if (!$this->indexExists('calcom_bookings', 'calcom_bookings_starts_at_index')) {
                    $table->index('starts_at', 'calcom_bookings_starts_at_index');
                }
            });
        }
        
        // ACTIVITY_LOG TABLE
        if (Schema::hasTable('activity_log')) {
            Schema::table('activity_log', function (Blueprint $table) {
                if (!$this->indexExists('activity_log', 'activity_log_created_at_index')) {
                    $table->index('created_at', 'activity_log_created_at_index');
                }
                if (!$this->indexExists('activity_log', 'activity_log_causer_index')) {
                    $table->index(['causer_type', 'causer_id'], 'activity_log_causer_index');
                }
                if (!$this->indexExists('activity_log', 'activity_log_subject_index')) {
                    $table->index(['subject_type', 'subject_id'], 'activity_log_subject_index');
                }
                if (!$this->indexExists('activity_log', 'activity_log_event_index')) {
                    $table->index('event', 'activity_log_event_index');
                }
            });
        }
        
        // USERS TABLE
        Schema::table('users', function (Blueprint $table) {
            // company_id column doesn't exist in users table, skip this index
            if (!$this->indexExists('users', 'users_email_index')) {
                $table->unique('email', 'users_email_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes in reverse order
        $tables = [
            'appointments' => [
                'appointments_company_id_index',
                'appointments_starts_at_index',
                'appointments_ends_at_index',
                'appointments_status_index',
                'appointments_customer_id_index',
                'appointments_branch_id_index',
                'appointments_staff_id_index',
                'appointments_service_id_index',
                'appointments_calcom_booking_id_index',
                'appointments_calcom_v2_booking_id_index',
                'appointments_company_starts_at_index',
                'appointments_company_status_index',
                'appointments_status_starts_at_index',
                'appointments_branch_starts_at_index',
                'appointments_staff_starts_at_index',
                'appointments_reminder_24h_sent_at_index',
            ],
            'calls' => [
                'calls_company_id_index',
                'calls_created_at_index',
                'calls_start_timestamp_index',
                'calls_from_number_index',
                'calls_to_number_index',
                'calls_call_status_index',
                'calls_retell_call_id_index',
                'calls_call_id_index',
                'calls_customer_id_index',
                'calls_appointment_id_index',
                'calls_company_created_at_index',
                'calls_customer_created_at_index',
                'calls_company_call_status_index',
                'calls_duration_sec_index',
                'calls_cost_index',
            ],
            'customers' => [
                'customers_company_id_index',
                'customers_phone_index',
                'customers_email_index',
                'customers_company_phone_index',
                'customers_company_email_index',
                'customers_name_index',
            ],
            'branches' => [
                'branches_company_id_index',
                'branches_active_index',
                'branches_company_active_index',
            ],
            'staff' => [
                'staff_company_id_index',
                'staff_branch_id_index',
                'staff_email_index',
                'staff_company_branch_index',
            ],
            'services' => [
                'services_company_id_index',
                'services_deleted_at_index',
                'services_company_deleted_index',
            ],
            'companies' => [
                'companies_is_active_index',
                'companies_deleted_at_index',
            ],
            'users' => [
                'users_email_index',
            ],
        ];
        
        // Conditional tables
        if (Schema::hasTable('calcom_event_types')) {
            $tables['calcom_event_types'] = [
                'calcom_event_types_company_id_index',
                'calcom_event_types_calcom_event_type_id_index',
                'calcom_event_types_company_calcom_index',
            ];
        }
        
        if (Schema::hasTable('staff_event_types')) {
            $tables['staff_event_types'] = [
                'staff_event_types_staff_id_index',
                'staff_event_types_event_type_id_index',
                'staff_event_types_unique',
            ];
        }
        
        if (Schema::hasTable('working_hours')) {
            $tables['working_hours'] = [
                'working_hours_staff_id_index',
                'working_hours_day_of_week_index',
                'working_hours_staff_day_index',
            ];
        }
        
        if (Schema::hasTable('calcom_bookings')) {
            $tables['calcom_bookings'] = [
                'calcom_bookings_branch_id_index',
                'calcom_bookings_calcom_uid_index',
                'calcom_bookings_starts_at_index',
            ];
        }
        
        if (Schema::hasTable('activity_log')) {
            $tables['activity_log'] = [
                'activity_log_created_at_index',
                'activity_log_causer_index',
                'activity_log_subject_index',
                'activity_log_event_index',
            ];
        }
        
        // Drop all indexes
        foreach ($tables as $tableName => $indexes) {
            Schema::table($tableName, function (Blueprint $table) use ($indexes) {
                foreach ($indexes as $indexName) {
                    if ($this->indexExists($table->getTable(), $indexName)) {
                        $table->dropIndex($indexName);
                    }
                }
            });
        }
    }
};