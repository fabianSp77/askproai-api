<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Testing-only migration: create MINIMAL base tables for RefreshDatabase.
 *
 * IMPORTANT DESIGN RULES:
 * 1. Only create tables that have NO Schema::create in regular migrations
 * 2. Only include columns that are: (a) primary key, (b) referenced by FK in
 *    other migrations, (c) NOT NULL without default, or (d) needed by factories
 * 3. All other columns are added by later ALTER TABLE migrations
 * 4. NO foreign key constraints (avoids ordering issues)
 *
 * Tables created here (no own Schema::create migration):
 *   users, companies, services, staff, customers, appointments,
 *   service_output_configurations, tenants, invoices, pricing_plans,
 *   phone_numbers, appointment_policy_violations, notification_logs
 *
 * Tables NOT created here (have own migrations):
 *   branches, calls, retell_call_sessions, retell_call_events,
 *   retell_function_traces, retell_transcript_segments, callback_requests,
 *   callback_escalations, notification_providers, notification_preferences,
 *   appointment_audit_logs, appointment_modifications, policy_configurations,
 *   appointment_modification_stats, service_cases, service_case_categories,
 *   service_case_activity_logs, service_gateway_exchange_logs, etc.
 *
 * Last updated: 2026-02-09
 */
return new class extends Migration
{
    public function up(): void
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            $this->createCoreTables();
            $this->createAppointmentTables();
            $this->createServiceGatewayTables();
            $this->createBillingTables();
            $this->createMiscTables();
        } finally {
            \DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    // ================================================================
    // CORE TABLES (users, companies, services, staff, customers)
    // ================================================================

    private function createCoreTables(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->unsignedBigInteger('company_id')->nullable();
                $table->rememberToken();
                $table->timestamps();
                // Additional columns added by:
                // - 2025_09_23_124000_add_missing_columns_to_users_table.php
                // - 2025_10_26_201516_add_branch_id_and_staff_id_to_users_table.php
                // - 2025_11_24_120447_create_customer_portal_infrastructure.php
            });
        }

        if (!Schema::hasTable('companies')) {
            Schema::create('companies', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->string('status')->default('active');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
                // Additional columns added by many later migrations
            });
        }

        if (!Schema::hasTable('services')) {
            Schema::create('services', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('name');
                $table->integer('duration')->default(30);
                $table->unsignedBigInteger('calcom_event_type_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
                // Additional columns added by:
                // - 2025_09_23_091422_add_calcom_sync_fields_to_services_table.php
                // - 2025_09_23_110445_add_assignment_metadata_to_services_table.php
                // - 2025_09_24_123235_add_composite_fields_to_services_table.php
                // - 2025_09_29_add_display_name_to_services.php
                // - 2025_09_30_add_default_flag_to_services.php
                // - 2025_10_28_133429_add_processing_time_to_services_table.php
            });
        }

        if (!Schema::hasTable('staff')) {
            Schema::create('staff', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->unsignedBigInteger('company_id');
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->unsignedBigInteger('calcom_user_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
                // Additional columns added by:
                // - 2025_09_25_calendar_sync_fields.php
                // - 2025_10_18_000004_add_phonetic_columns_for_agent_optimization.php
            });
        }

        if (!Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->timestamps();
                $table->softDeletes();
                // Additional columns added by:
                // - 2025_09_26_add_source_to_customers_table.php
                // - 2025_09_26_advanced_notification_system.php
                // - 2025_11_11_231608_fix_customers_email_unique_constraint.php
            });
        }
    }

    // ================================================================
    // APPOINTMENT TABLES
    // ================================================================

    private function createAppointmentTables(): void
    {
        if (!Schema::hasTable('appointments')) {
            Schema::create('appointments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->unsignedBigInteger('service_id')->nullable();
                $table->char('staff_id', 36)->nullable();
                $table->timestamp('date')->nullable();
                $table->string('time_slot')->nullable();
                $table->string('status')->default('pending');
                $table->text('notes')->nullable();
                $table->string('calcom_booking_uid')->nullable();
                $table->timestamps();
                $table->softDeletes();
                // Many columns added by later migrations:
                // - 2025_09_22_fix_customer_management_tables.php (hasColumn guards)
                // - 2025_09_24_123351_add_composite_fields_to_appointments_table.php (hasColumn guards)
                // - 2025_09_25_calendar_sync_fields.php (hasColumn guards)
                // - 2025_09_26_173500_create_nested_booking_slots_table.php (NO guards)
                // - 2025_09_26_fix_appointments_company_id.php (NO guards, but just company_id which we have)
                // - 2025_10_05_064849_add_booking_timezone_to_appointments_table.php (hasColumn guards)
                // - 2025_10_06_000003_extend_appointments_for_assignment_model.php (NO guards)
                // - 2025_10_06_140002_add_calcom_host_id_to_appointments_table.php (NO guards)
                // - 2025_10_11_000000_add_calcom_sync_tracking_to_appointments.php (hasColumn guards)
                // - 2025_10_18_000002_add_idempotency_keys.php (hasColumn guards)
                // - 2025_10_28_181703_add_branch_id_to_appointments_table.php (hasColumn guards)
                // - 2025_11_17_143500_add_calcom_booking_uid_to_appointments.php (NO guards)
                // - 2025_11_24_120447_create_customer_portal_infrastructure.php (hasColumn guards)
                // - 2025_11_25_105856_add_reschedule_tracking_to_appointments_table.php (NO guards)
            });
        }

        if (!Schema::hasTable('appointment_policy_violations')) {
            Schema::create('appointment_policy_violations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('appointment_id')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->string('violation_type');
                $table->string('severity')->default('medium');
                $table->text('description')->nullable();
                $table->longText('metadata')->nullable();
                $table->boolean('resolved')->default(false);
                $table->timestamps();
            });
        }
    }

    // ================================================================
    // SERVICE GATEWAY TABLES
    // ================================================================

    private function createServiceGatewayTables(): void
    {
        if (!Schema::hasTable('service_output_configurations')) {
            Schema::create('service_output_configurations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('name');
                $table->string('output_type')->default('email');
                $table->boolean('is_active')->default(true);
                $table->longText('email_recipients')->nullable();
                $table->string('webhook_url')->nullable();
                $table->timestamps();
                // Additional columns added by:
                // - 2025_12_22_130516_add_audio_config_to_service_output_configurations.php
                // - 2025_12_22_182432_add_email_show_admin_link_to_service_output_configurations.php
                // - 2025_12_22_183908_add_delivery_config_to_service_output_configurations.php
                // - 2025_12_23_121213_add_webhook_secret_to_service_output_configurations.php
                // - 2026_01_03_220600_add_email_template_type_to_service_output_configurations.php
                // - 2026_01_04_100000_create_webhook_presets_table.php
                // - 2026_01_05_140000_add_muted_recipients_to_service_output_configurations.php
                // - 2026_01_08_100001_add_contact_type_override_to_output_configs.php
                // - 2026_01_10_063032_add_template_id_to_service_output_configurations.php
                // - 2026_01_12_122503_add_billing_to_service_gateway.php
            });
        }
    }

    // ================================================================
    // BILLING TABLES (tenants, invoices, pricing_plans)
    // ================================================================

    private function createBillingTables(): void
    {
        if (!Schema::hasTable('tenants')) {
            Schema::create('tenants', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
                // Many columns added by:
                // - 2025_09_23_131000_add_missing_columns_to_tenants_table.php (hasColumn guards)
                // - 2025_09_23_132100_fix_missing_tenant_columns.php (hasColumn guards)
            });
        }

        if (!Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('invoice_number')->nullable();
                $table->string('status')->default('draft');
                $table->decimal('total', 10, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pricing_plans')) {
            Schema::create('pricing_plans', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->string('name');
                $table->string('type')->default('package');
                $table->string('billing_interval')->default('monthly');
                $table->decimal('base_price', 10, 2)->default(0.00);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                // Many columns added by:
                // - 2025_09_23_170000_upgrade_pricing_plans_table.php (hasColumn guards)
            });
        }
    }

    // ================================================================
    // MISC TABLES (phone_numbers, notification_logs)
    // ================================================================

    private function createMiscTables(): void
    {
        if (!Schema::hasTable('phone_numbers')) {
            Schema::create('phone_numbers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('number');
                $table->timestamps();
                // Additional columns added by:
                // - 2025_09_22_112232_add_missing_fields_to_phone_numbers_table.php (hasColumn guards)
                // - 2025_09_30_125033_add_number_normalized_to_phone_numbers_table.php (hasColumn guards)
            });
        }

        // NOTE: 'roles' table is created by Spatie permission migration
        // (2025_10_17_205256_create_permission_tables.php)

        if (!Schema::hasTable('notification_logs')) {
            Schema::create('notification_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->string('type')->nullable();
                $table->string('channel')->nullable();
                $table->string('recipient')->nullable();
                $table->string('status')->default('pending');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $tables = [
            'notification_logs', 'phone_numbers',
            'pricing_plans', 'invoices', 'tenants',
            'service_output_configurations',
            'appointment_policy_violations', 'appointments',
            'customers', 'staff', 'services', 'companies', 'users',
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }

        \DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};
