<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Testing-only migration to create essential tables matching production schema.
 *
 * Production already has these tables via incremental migrations.
 * This migration creates them all at once for RefreshDatabase in tests.
 *
 * Key design decisions:
 * - Non-essential columns are nullable with defaults for factory convenience
 * - Enum columns use string type (avoids ALTER TABLE for new values)
 * - Foreign key constraints omitted (factories handle data integrity)
 * - Column order matches production for easier comparison
 *
 * Last synced with production: 2026-02-08
 */
return new class extends Migration
{
    public function up(): void
    {
        // Disable FK checks to allow tables to be created in any order
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            $this->createCoreTables();
            $this->createAppointmentTables();
            $this->createCallTables();
            $this->createServiceGatewayTables();
            $this->createBillingTables();
            $this->createFeatureTables();
        } finally {
            \DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    // ================================================================
    // CORE TABLES
    // ================================================================

    private function createCoreTables(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('phone')->nullable();
                $table->string('avatar')->nullable();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->char('branch_id', 36)->nullable();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->boolean('is_active')->default(true);
                $table->text('two_factor_secret')->nullable();
                $table->text('two_factor_recovery_codes')->nullable();
                $table->timestamp('two_factor_confirmed_at')->nullable();
                $table->boolean('two_factor_enforced')->default(false);
                $table->string('two_factor_method')->nullable();
                $table->string('two_factor_phone_number')->nullable();
                $table->boolean('two_factor_phone_verified')->default(false);
                $table->rememberToken();
                $table->string('interface_language')->default('de');
                $table->string('content_language')->default('de');
                $table->boolean('auto_translate_content')->default(false);
                $table->unsignedBigInteger('kunde_id')->nullable();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->timestamp('last_login_at')->nullable();
                $table->string('last_login_ip')->nullable();
                $table->integer('failed_login_attempts')->default(0);
                $table->timestamp('locked_until')->nullable();
                $table->integer('login_count')->default(0);
                $table->integer('failed_login_count')->default(0);
                $table->boolean('two_factor_enabled')->default(false);
                $table->string('locale')->default('de');
                $table->string('timezone')->default('Europe/Berlin');
                $table->longText('settings')->nullable();
                $table->timestamp('blocked_at')->nullable();
                $table->text('blocked_reason')->nullable();
                $table->char('staff_id', 36)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('companies')) {
            Schema::create('companies', function (Blueprint $table) {
                $table->id();
                $table->boolean('notify_on_unfulfilled_wishes')->default(false);
                $table->longText('wish_notification_emails')->nullable();
                $table->integer('wish_notification_delay_minutes')->default(30);
                $table->unsignedBigInteger('parent_company_id')->nullable();
                $table->string('company_type')->nullable();
                $table->boolean('is_white_label')->default(false);
                $table->boolean('can_make_outbound_calls')->default(false);
                $table->longText('outbound_settings')->nullable();
                $table->integer('outbound_call_limit')->nullable();
                $table->integer('outbound_calls_used')->default(0);
                $table->longText('white_label_settings')->nullable();
                $table->decimal('commission_rate', 12, 2)->default(0);
                $table->string('name');
                $table->text('address')->nullable();
                $table->string('contact_person')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->string('billing_contact_email')->nullable();
                $table->string('billing_contact_phone')->nullable();
                $table->decimal('usage_budget', 12, 2)->nullable();
                $table->boolean('alerts_enabled')->default(true);
                $table->longText('opening_hours')->nullable();
                $table->text('calcom_api_key')->nullable();
                $table->string('calcom_v2_api_key')->nullable();
                $table->string('calcom_team_slug')->nullable();
                $table->string('calcom_user_id')->nullable();
                $table->integer('calcom_team_id')->nullable();
                $table->string('calcom_team_name')->nullable();
                $table->string('team_sync_status')->default('pending');
                $table->timestamp('last_team_sync')->nullable();
                $table->text('team_sync_error')->nullable();
                $table->integer('team_member_count')->default(0);
                $table->integer('team_event_type_count')->default(0);
                $table->text('retell_api_key')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_partner')->default(false);
                $table->unsignedBigInteger('managed_by_company_id')->nullable();
                $table->string('partner_stripe_customer_id')->nullable();
                $table->string('partner_billing_email')->nullable();
                $table->longText('partner_billing_cc_emails')->nullable();
                $table->string('partner_billing_name')->nullable();
                $table->longText('partner_billing_address')->nullable();
                $table->tinyInteger('partner_payment_terms_days')->default(14);
                $table->string('tax_number')->nullable();
                $table->string('vat_id')->nullable();
                $table->string('wirtschafts_id_nr')->nullable();
                $table->string('trade_register')->nullable();
                $table->string('trade_register_court')->nullable();
                $table->string('partner_invoice_delivery')->default('email');
                $table->boolean('escalation_rules_enabled')->default(false);
                $table->boolean('sla_tracking_enabled')->default(false);
                $table->boolean('is_pilot')->default(false);
                $table->timestamp('pilot_enabled_at')->nullable();
                $table->bigInteger('pilot_enabled_by')->nullable();
                $table->text('pilot_notes')->nullable();
                $table->boolean('send_call_summaries')->default(false);
                $table->longText('call_summary_recipients')->nullable();
                $table->boolean('include_transcript_in_summary')->default(false);
                $table->boolean('include_csv_export')->default(false);
                $table->string('summary_email_frequency')->default('daily');
                $table->longText('call_notification_settings')->nullable();
                $table->string('notification_provider')->default('internal');
                $table->boolean('calcom_handles_notifications')->default(false);
                $table->boolean('email_notifications_enabled')->default(true);
                $table->boolean('active')->default(true);
                $table->boolean('is_system')->default(false);
                $table->timestamp('balance_warning_sent_at')->nullable();
                $table->string('calcom_event_type_id')->nullable();
                $table->longText('api_test_errors')->nullable();
                $table->boolean('send_booking_confirmations')->default(true);
                $table->timestamp('archived_at')->nullable();
                $table->text('archive_reason')->nullable();
                $table->string('archived_by')->nullable();
                $table->string('retell_webhook_url')->nullable();
                $table->string('retell_agent_id')->nullable();
                $table->string('retell_conversation_flow_id')->nullable();
                $table->string('retell_voice')->nullable();
                $table->boolean('retell_enabled')->default(false);
                $table->longText('retell_default_settings')->nullable();
                $table->string('calcom_calendar_mode')->default('team');
                $table->string('billing_status')->default('active');
                $table->string('billing_type')->default('prepaid');
                $table->decimal('credit_balance', 12, 2)->default(0);
                $table->decimal('low_credit_threshold', 12, 2)->default(500);
                $table->longText('settings')->nullable();
                $table->longText('v128_config')->nullable();
                $table->longText('metadata')->nullable();
                $table->longText('alert_preferences')->nullable();
                $table->string('industry')->nullable();
                $table->string('logo')->nullable();
                $table->timestamp('trial_ends_at')->nullable();
                $table->string('subscription_status')->nullable();
                $table->string('subscription_plan')->nullable();
                $table->string('slug')->nullable();
                $table->string('website')->nullable();
                $table->string('city')->nullable();
                $table->string('state')->nullable();
                $table->string('postal_code')->nullable();
                $table->string('country')->default('DE');
                $table->string('timezone')->default('Europe/Berlin');
                $table->string('default_language')->default('de');
                $table->longText('supported_languages')->nullable();
                $table->boolean('auto_translate')->default(false);
                $table->string('translation_provider')->default('none');
                $table->string('currency')->default('EUR');
                $table->text('google_calendar_credentials')->nullable();
                $table->string('stripe_customer_id')->nullable();
                $table->string('stripe_subscription_id')->nullable();
                $table->longText('security_settings')->nullable();
                $table->longText('allowed_ip_addresses')->nullable();
                $table->string('webhook_signing_secret')->nullable();
                $table->string('payment_terms')->default('net_14');
                $table->date('small_business_threshold_date')->nullable();
                $table->unsignedBigInteger('default_event_type_id')->nullable();
                $table->boolean('prepaid_billing_enabled')->default(false);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table) {
                $table->char('id', 36)->primary();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->string('name');
                $table->string('slug')->nullable();
                $table->unsignedBigInteger('calcom_team_id')->nullable();
                $table->string('city')->nullable();
                $table->string('phone_number')->nullable();
                $table->string('notification_email')->nullable();
                $table->boolean('send_call_summaries')->nullable();
                $table->longText('call_summary_recipients')->nullable();
                $table->boolean('include_transcript_in_summary')->nullable();
                $table->boolean('include_csv_export')->nullable();
                $table->string('summary_email_frequency')->nullable();
                $table->longText('call_notification_overrides')->nullable();
                $table->boolean('active')->default(true);
                $table->boolean('invoice_recipient')->default(false);
                $table->string('invoice_name')->nullable();
                $table->string('invoice_email')->nullable();
                $table->string('invoice_address')->nullable();
                $table->string('invoice_phone')->nullable();
                $table->text('calcom_api_key')->nullable();
                $table->string('retell_agent_id')->nullable();
                $table->string('retell_conversation_flow_id')->nullable();
                $table->longText('integration_status')->nullable();
                $table->string('calendar_mode')->default('team');
                $table->timestamp('integrations_tested_at')->nullable();
                $table->string('calcom_user_id')->nullable();
                $table->longText('retell_agent_cache')->nullable();
                $table->timestamp('retell_last_sync')->nullable();
                $table->longText('configuration_status')->nullable();
                $table->longText('parent_settings')->nullable();
                $table->string('address')->nullable();
                $table->string('postal_code')->nullable();
                $table->string('website')->nullable();
                $table->longText('business_hours')->nullable();
                $table->longText('services_override')->nullable();
                $table->string('country')->default('DE');
                $table->string('uuid')->default('');
                $table->longText('settings')->nullable();
                $table->longText('coordinates')->nullable();
                $table->longText('features')->nullable();
                $table->longText('transport_info')->nullable();
                $table->integer('service_radius_km')->nullable();
                $table->boolean('accepts_walkins')->default(false);
                $table->boolean('parking_available')->default(false);
                $table->text('public_transport_access')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('services')) {
            Schema::create('services', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('display_name')->nullable();
                $table->string('calcom_name')->nullable();
                $table->string('slug')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->integer('priority')->default(0);
                $table->integer('duration_minutes')->default(30);
                $table->boolean('has_processing_time')->default(false);
                $table->integer('initial_duration')->nullable();
                $table->integer('processing_duration')->nullable();
                $table->integer('final_duration')->nullable();
                $table->boolean('is_online')->default(false);
                $table->boolean('composite')->default(false);
                $table->longText('segments')->nullable();
                $table->string('pause_bookable_policy')->default('none');
                $table->string('reminder_policy')->default('default');
                $table->longText('reschedule_policy')->nullable();
                $table->integer('min_staff_required')->default(1);
                $table->integer('buffer_time_minutes')->default(0);
                $table->integer('minimum_booking_notice')->default(0);
                $table->integer('before_event_buffer')->default(0);
                $table->boolean('requires_confirmation')->default(false);
                $table->boolean('disable_guests')->default(false);
                $table->text('booking_link')->nullable();
                $table->longText('locations_json')->nullable();
                $table->longText('metadata_json')->nullable();
                $table->longText('booking_fields_json')->nullable();
                $table->timestamp('last_calcom_sync')->nullable();
                $table->string('sync_status')->default('pending');
                $table->text('sync_error')->nullable();
                // NOTE: assignment_method, assignment_confidence, assignment_notes,
                // assignment_date, assigned_by are added by migration
                // 2025_09_23_110445_add_assignment_metadata_to_services_table.php
                $table->string('calcom_event_type_id')->nullable();
                $table->integer('schedule_id')->nullable();
                $table->unsignedBigInteger('company_id');
                $table->char('branch_id', 36)->nullable();
                $table->char('tenant_id', 36)->nullable();
                $table->text('description')->nullable();
                $table->decimal('price', 12, 2)->default(0);
                $table->string('category')->nullable();
                $table->integer('sort_order')->default(0);
                $table->integer('max_bookings_per_day')->nullable();
                $table->integer('duration')->nullable();
                $table->longText('required_skills')->nullable();
                $table->longText('required_certifications')->nullable();
                $table->string('complexity_level')->default('standard');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('staff')) {
            Schema::create('staff', function (Blueprint $table) {
                $table->char('id', 36)->primary();
                $table->unsignedBigInteger('company_id');
                $table->char('branch_id', 36)->nullable();
                $table->string('name');
                $table->string('phonetic_name_soundex')->nullable();
                $table->string('phonetic_name_metaphone')->nullable();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->boolean('active')->default(true);
                $table->char('home_branch_id', 36)->nullable();
                $table->string('calcom_user_id')->nullable();
                $table->string('calcom_calendar_link')->nullable();
                $table->boolean('is_bookable')->default(true);
                $table->text('notes')->nullable();
                $table->string('external_calendar_id')->nullable();
                $table->string('calendar_provider')->nullable();
                $table->longText('skills')->nullable();
                $table->longText('languages')->nullable();
                $table->integer('mobility_radius_km')->nullable();
                $table->longText('specializations')->nullable();
                $table->decimal('average_rating', 12, 2)->nullable();
                $table->longText('certifications')->nullable();
                $table->integer('experience_level')->default(1);
                $table->longText('working_hours')->nullable();
                $table->string('calcom_username')->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('google_calendar_id')->nullable();
                $table->text('google_calendar_token')->nullable();
                $table->text('google_refresh_token')->nullable();
                $table->string('google_webhook_id')->nullable();
                $table->timestamp('google_webhook_expires_at')->nullable();
                $table->string('outlook_calendar_id')->nullable();
                $table->text('outlook_access_token')->nullable();
                $table->text('outlook_refresh_token')->nullable();
                $table->timestamp('outlook_token_expires_at')->nullable();
                $table->string('outlook_webhook_id')->nullable();
                $table->timestamp('outlook_webhook_expires_at')->nullable();
                $table->string('calendar_color')->default('#3B82F6');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->string('status')->default('active');
                $table->string('customer_type')->nullable();
                $table->string('name');
                $table->string('company_name')->nullable();
                $table->string('customer_number')->nullable();
                $table->string('journey_status')->default('new');
                $table->timestamp('journey_status_updated_at')->nullable();
                $table->longText('journey_history')->nullable();
                $table->string('email')->nullable();
                $table->string('password')->nullable();
                $table->rememberToken();
                $table->timestamp('email_verified_at')->nullable();
                $table->boolean('portal_enabled')->default(false);
                $table->string('portal_access_token')->nullable();
                $table->timestamp('portal_token_expires_at')->nullable();
                $table->timestamp('last_portal_login_at')->nullable();
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamp('last_call_at')->nullable();
                $table->string('preferred_language')->default('de');
                $table->string('preferred_contact_method')->default('phone');
                $table->string('preferred_appointment_time')->nullable();
                $table->timestamp('privacy_consent_at')->nullable();
                $table->timestamp('marketing_consent_at')->nullable();
                $table->timestamp('deletion_requested_at')->nullable();
                $table->string('phone')->nullable();
                $table->longText('phone_variants')->nullable();
                $table->integer('matching_confidence')->default(0);
                $table->text('notes')->nullable();
                $table->longText('tags')->nullable();
                $table->text('internal_notes')->nullable();
                $table->integer('no_show_count')->default(0);
                $table->integer('cancelled_count')->default(0);
                $table->date('first_appointment_date')->nullable();
                $table->date('last_appointment_date')->nullable();
                $table->integer('appointment_count')->default(0);
                $table->timestamp('last_appointment_at')->nullable();
                $table->integer('completed_appointments')->default(0);
                $table->integer('cancelled_appointments')->default(0);
                $table->integer('no_show_appointments')->default(0);
                $table->decimal('total_revenue', 12, 2)->default(0);
                $table->integer('call_count')->default(0);
                $table->integer('loyalty_points')->default(0);
                $table->decimal('total_spent', 12, 2)->default(0);
                $table->decimal('average_booking_value', 12, 2)->default(0);
                $table->boolean('is_vip')->default(false);
                $table->string('loyalty_tier')->default('standard');
                $table->timestamp('vip_since')->nullable();
                $table->longText('special_requirements')->nullable();
                $table->date('birthday')->nullable();
                $table->integer('sort_order')->nullable();
                $table->string('source')->nullable();
                $table->char('preferred_branch_id', 36)->nullable();
                $table->char('preferred_staff_id', 36)->nullable();
                $table->longText('booking_history_summary')->nullable();
                $table->longText('location_data')->nullable();
                $table->longText('preference_data')->nullable();
                $table->longText('custom_attributes')->nullable();
                $table->longText('communication_preferences')->nullable();
                $table->timestamp('last_security_check')->nullable();
                $table->longText('security_flags')->nullable();
                $table->integer('failed_verification_attempts')->default(0);
                $table->string('notification_language')->default('de');
                $table->string('whatsapp_number')->nullable();
                $table->text('push_token')->nullable();
                $table->string('push_platform')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // NOTE: 'roles' table created by Spatie permission migration
        // (2025_10_17_205256_create_permission_tables.php)
        // Extra columns (description, color, icon, etc.) added by
        // 2025_09_23_120400_add_fields_to_roles_table.php with hasColumn guards

        // NOTE: 'stripe_events' created by migration
        // 2026_01_12_171412_create_stripe_events_table.php (no hasTable guard)

        if (!Schema::hasTable('notification_providers')) {
            Schema::create('notification_providers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->string('name');
                $table->string('type');
                $table->string('channel');
                $table->longText('credentials');
                $table->longText('config')->nullable();
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->integer('priority')->default(1);
                $table->decimal('balance', 10, 2)->nullable();
                $table->integer('rate_limit')->nullable();
                $table->longText('allowed_countries')->nullable();
                $table->longText('statistics')->nullable();
                $table->timestamps();
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
                $table->string('idempotency_key')->nullable();
                $table->string('webhook_id')->nullable();
                $table->unsignedBigInteger('parent_appointment_id')->nullable();
                $table->unsignedBigInteger('company_id');
                $table->char('branch_id', 36)->nullable();
                $table->unsignedBigInteger('customer_id');
                $table->string('external_id')->nullable();
                $table->string('series_id')->nullable();
                $table->string('group_booking_id')->nullable();
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->longText('payload')->nullable();
                $table->string('status')->default('pending');
                $table->string('cancellation_reason')->nullable();
                $table->string('cancelled_by_type')->nullable();
                $table->unsignedBigInteger('cancelled_by_id')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->boolean('is_nested')->default(false);
                $table->unsignedBigInteger('parent_booking_id')->nullable();
                $table->boolean('has_nested_slots')->default(false);
                $table->longText('phases')->nullable();
                $table->string('source')->default('voice');
                $table->string('booking_type')->default('standard');
                $table->boolean('is_composite')->default(false);
                $table->boolean('uses_consolidated_segments')->default(false);
                $table->char('composite_group_uid', 36)->nullable();
                $table->longText('segments')->nullable();
                $table->timestamp('rescheduled_at')->nullable();
                $table->string('rescheduled_by')->nullable();
                $table->smallInteger('rescheduled_count')->default(0);
                $table->timestamp('previous_starts_at')->nullable();
                $table->timestamp('last_modified_at')->useCurrent();
                $table->bigInteger('last_modified_by')->nullable();
                $table->unsignedBigInteger('call_id')->nullable();
                $table->char('staff_id', 36)->nullable();
                $table->string('assignment_model_used')->nullable();
                $table->boolean('was_fallback')->default(false);
                $table->longText('assignment_metadata')->nullable();
                $table->unsignedBigInteger('service_id')->nullable();
                $table->string('calcom_v2_booking_id')->nullable();
                $table->string('calcom_v2_booking_uid')->nullable();
                $table->string('calcom_previous_booking_uid')->nullable();
                $table->string('calcom_sync_status')->default('pending');
                $table->timestamp('calcom_last_sync_at')->nullable();
                $table->text('calcom_sync_error')->nullable();
                $table->smallInteger('calcom_sync_attempts')->default(0);
                $table->string('sync_origin')->nullable();
                $table->timestamp('sync_initiated_at')->nullable();
                $table->timestamp('last_sync_attempt_at')->nullable();
                $table->tinyInteger('sync_attempt_count')->default(0);
                $table->timestamp('last_sync_attempted_at')->nullable();
                $table->integer('retry_count')->default(0);
                $table->timestamp('circuit_breaker_open_at_booking')->nullable();
                $table->string('resilience_strategy')->nullable();
                $table->longText('sync_error_message')->nullable();
                $table->string('sync_error_code')->nullable();
                $table->timestamp('sync_verified_at')->nullable();
                $table->boolean('requires_manual_review')->default(false);
                $table->timestamp('manual_review_flagged_at')->nullable();
                $table->integer('calcom_host_id')->nullable();
                $table->unsignedBigInteger('calcom_event_type_id')->nullable();
                $table->text('notes')->nullable();
                $table->integer('price')->nullable();
                $table->unsignedBigInteger('calcom_booking_id')->nullable();
                $table->integer('version')->default(1);
                $table->timestamp('lock_expires_at')->nullable();
                $table->string('lock_token')->nullable();
                $table->timestamp('reminder_24h_sent_at')->nullable();
                $table->longText('booking_metadata')->nullable();
                $table->integer('travel_time_minutes')->nullable();
                $table->longText('metadata')->nullable();
                $table->string('booking_timezone')->default('Europe/Berlin');
                $table->longText('recurrence_rule')->nullable();
                $table->integer('package_sessions_total')->nullable();
                $table->integer('package_sessions_used')->default(0);
                $table->date('package_expires_at')->nullable();
                $table->string('google_event_id')->nullable();
                $table->string('outlook_event_id')->nullable();
                $table->boolean('is_recurring')->default(false);
                $table->longText('recurring_pattern')->nullable();
                $table->string('external_calendar_source')->nullable();
                $table->string('external_calendar_id')->nullable();
                $table->longText('notification_status')->nullable();
                $table->integer('reminder_count')->default(0);
                $table->timestamp('last_reminder_at')->nullable();
                $table->unsignedBigInteger('sync_initiated_by_user_id')->nullable();
                $table->string('sync_job_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('callback_requests')) {
            Schema::create('callback_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->char('branch_id', 36);
                $table->unsignedBigInteger('service_id')->nullable();
                $table->char('staff_id', 36)->nullable();
                $table->string('phone_number');
                $table->string('customer_name');
                $table->string('customer_email')->nullable();
                $table->longText('preferred_time_window')->nullable();
                $table->string('priority')->default('normal');
                $table->string('status')->default('pending');
                $table->char('assigned_to', 36)->nullable();
                $table->timestamp('assigned_at')->nullable();
                $table->timestamp('contacted_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->text('notes')->nullable();
                $table->longText('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('callback_escalations')) {
            Schema::create('callback_escalations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('callback_request_id');
                $table->char('escalated_by_id', 36)->nullable();
                $table->char('escalated_to_id', 36)->nullable();
                $table->text('reason')->nullable();
                $table->timestamp('escalated_at');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('appointment_audit_logs')) {
            Schema::create('appointment_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('appointment_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('action', 50);
                $table->longText('old_values')->nullable();
                $table->longText('new_values')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->text('reason')->nullable();
                $table->timestamp('created_at');
            });
        }

        if (!Schema::hasTable('appointment_modifications')) {
            Schema::create('appointment_modifications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('appointment_id');
                $table->unsignedBigInteger('customer_id');
                $table->string('modification_type');
                $table->boolean('within_policy')->default(true);
                $table->decimal('fee_charged', 10, 2)->default(0.00);
                $table->text('reason')->nullable();
                $table->string('modified_by_type')->nullable();
                $table->unsignedBigInteger('modified_by_id')->nullable();
                $table->longText('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    // ================================================================
    // CALL & RETELL TABLES
    // ================================================================

    private function createCallTables(): void
    {
        if (!Schema::hasTable('calls')) {
            Schema::create('calls', function (Blueprint $table) {
                $table->id();
                $table->string('external_id')->nullable();
                $table->text('transcript')->nullable();
                $table->longText('raw')->nullable();
                // Customer linking
                $table->unsignedBigInteger('kunde_id')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->string('customer_link_status')->default('unlinked');
                $table->string('customer_link_method')->nullable();
                $table->decimal('customer_link_confidence', 12, 2)->nullable();
                $table->timestamp('customer_linked_at')->nullable();
                $table->unsignedBigInteger('linked_by_user_id')->nullable();
                $table->longText('linking_metadata')->nullable();
                $table->string('customer_name')->nullable();
                $table->boolean('customer_name_verified')->default(false);
                $table->decimal('verification_confidence', 12, 2)->nullable();
                $table->string('verification_method')->nullable();
                $table->boolean('is_unknown_customer')->default(false);
                $table->string('unknown_reason')->nullable();
                $table->integer('customer_match_confidence')->nullable();
                $table->string('customer_match_method')->nullable();
                // Consent & forwarding
                $table->boolean('consent_given')->default(false);
                $table->boolean('data_forwarded')->default(false);
                $table->boolean('data_validation_completed')->nullable();
                $table->timestamp('consent_at')->nullable();
                $table->timestamp('forwarded_at')->nullable();
                // Retell call data
                $table->string('retell_call_id')->default('');
                $table->string('from_number')->nullable();
                $table->string('to_number')->nullable();
                $table->integer('duration_sec')->nullable();
                $table->integer('duration_ms')->nullable();
                $table->integer('agent_talk_time_ms')->nullable();
                $table->integer('customer_talk_time_ms')->nullable();
                $table->integer('silence_time_ms')->nullable();
                $table->integer('wait_time_sec')->nullable();
                $table->char('tmp_call_id', 36)->nullable();
                $table->char('phone_number_id', 36)->nullable();
                $table->string('agent_id')->nullable();
                // Cost tracking
                $table->integer('cost_cents')->nullable();
                $table->integer('calculated_cost')->nullable();
                // Sentiment & analysis
                $table->double('sentiment_score')->nullable();
                $table->decimal('sentiment_score_detailed', 12, 2)->nullable();
                $table->string('call_status')->nullable();
                $table->string('session_outcome')->nullable();
                $table->boolean('call_successful')->nullable();
                $table->longText('analysis')->nullable();
                $table->char('conversation_id', 36)->nullable();
                $table->string('call_id')->nullable();
                $table->longText('details')->nullable();
                // Audio & recording
                $table->string('audio_url')->nullable();
                $table->string('recording_url')->nullable();
                $table->string('disconnection_reason')->nullable();
                // Summary & language
                $table->text('summary')->nullable();
                $table->string('summary_language')->nullable();
                $table->longText('summary_translations')->nullable();
                $table->string('sentiment')->nullable();
                $table->string('detected_language')->nullable();
                $table->decimal('language_confidence', 12, 2)->nullable();
                $table->boolean('language_mismatch')->default(false);
                $table->string('public_log_url')->nullable();
                // Legacy extracted data (German field names)
                $table->string('name')->nullable();
                $table->string('email')->nullable();
                $table->string('health_insurance_company')->nullable();
                $table->date('datum_termin')->nullable();
                $table->time('uhrzeit_termin')->nullable();
                $table->string('dienstleistung')->nullable();
                $table->text('reason_for_visit')->nullable();
                $table->string('telefonnummer')->nullable();
                $table->text('grund')->nullable();
                $table->string('calcom_booking_id')->nullable();
                $table->string('phone_number')->nullable();
                $table->timestamp('call_time')->nullable();
                $table->integer('call_duration')->nullable();
                $table->string('disconnect_reason')->nullable();
                $table->string('type')->nullable();
                // Financial
                $table->decimal('cost', 12, 2)->nullable();
                $table->integer('base_cost')->nullable();
                $table->integer('reseller_cost')->nullable();
                $table->integer('customer_cost')->nullable();
                $table->integer('platform_profit')->nullable();
                $table->integer('reseller_profit')->nullable();
                $table->integer('total_profit')->nullable();
                $table->decimal('profit_margin_platform', 12, 2)->nullable();
                $table->decimal('profit_margin_reseller', 12, 2)->nullable();
                $table->decimal('profit_margin_total', 12, 2)->nullable();
                $table->string('cost_calculation_method')->nullable();
                $table->boolean('successful')->default(false);
                $table->string('user_sentiment')->nullable();
                $table->longText('raw_data')->nullable();
                // Domain-specific extracted data
                $table->string('behandlung_dauer')->nullable();
                $table->string('rezeptstatus')->nullable();
                $table->string('versicherungsstatus')->nullable();
                $table->string('haustiere_name')->nullable();
                $table->text('notiz')->nullable();
                // Relationships
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedBigInteger('campaign_id')->nullable();
                $table->char('branch_id', 36)->nullable();
                $table->unsignedBigInteger('appointment_id')->nullable();
                // Appointment tracking
                $table->boolean('has_appointment')->default(false);
                $table->string('appointment_link_status')->default('none');
                $table->timestamp('appointment_linked_at')->nullable();
                $table->unsignedBigInteger('appointment_with_advisor_id')->nullable();
                $table->boolean('appointment_made')->default(false);
                $table->boolean('converted_to_appointment')->default(false);
                $table->unsignedBigInteger('converted_appointment_id')->nullable();
                $table->timestamp('conversion_timestamp')->nullable();
                // Tags & timing
                $table->longText('tags')->nullable();
                $table->timestamp('start_timestamp')->nullable();
                $table->timestamp('end_timestamp')->nullable();
                $table->string('timezone')->default('Europe/Berlin');
                $table->string('call_type')->nullable();
                $table->string('direction')->nullable();
                // Transcript & metrics
                $table->longText('transcript_object')->nullable();
                $table->longText('transcript_with_tools')->nullable();
                $table->longText('latency_metrics')->nullable();
                $table->integer('end_to_end_latency')->nullable();
                $table->longText('cost_breakdown')->nullable();
                $table->longText('llm_usage')->nullable();
                $table->longText('retell_dynamic_variables')->nullable();
                $table->boolean('opt_out_sensitive_data')->default(false);
                $table->longText('metadata')->nullable();
                // Booking
                $table->longText('booking_details')->nullable();
                $table->boolean('booking_confirmed')->nullable();
                $table->string('booking_id')->nullable();
                $table->decimal('duration_minutes', 12, 2)->nullable();
                $table->longText('webhook_data')->nullable();
                // Agent & cost
                $table->string('agent_version')->nullable();
                $table->decimal('retell_cost', 12, 2)->nullable();
                $table->decimal('retell_cost_usd', 12, 2)->nullable();
                $table->integer('retell_cost_eur_cents')->nullable();
                $table->decimal('twilio_cost_usd', 12, 2)->nullable();
                $table->integer('twilio_cost_eur_cents')->nullable();
                $table->decimal('exchange_rate_used', 12, 2)->nullable();
                $table->integer('total_external_cost_eur_cents')->nullable();
                $table->longText('custom_sip_headers')->nullable();
                // Extracted info
                $table->boolean('appointment_requested')->default(false);
                $table->string('extracted_date')->nullable();
                $table->string('extracted_time')->nullable();
                $table->string('extracted_name')->nullable();
                $table->string('extracted_email')->nullable();
                // Status & versioning
                $table->integer('version')->default(1);
                $table->integer('duration')->nullable();
                $table->string('status')->default('pending');
                $table->string('gateway_mode')->nullable();
                $table->string('detected_intent')->nullable();
                $table->decimal('intent_confidence', 12, 2)->nullable();
                $table->longText('intent_keywords')->nullable();
                $table->string('lead_status')->nullable();
                $table->string('retell_agent_id')->nullable();
                $table->string('transcription_id')->nullable();
                $table->string('video_url')->nullable();
                $table->unsignedBigInteger('staff_id')->nullable();
                $table->text('notes')->nullable();
                $table->string('caller')->nullable();
                $table->string('agent_name')->nullable();
                $table->string('urgency_level')->nullable();
                $table->integer('no_show_count')->default(0);
                $table->integer('reschedule_count')->default(0);
                $table->boolean('first_visit')->nullable();
                $table->string('insurance_type')->nullable();
                $table->string('insurance_company')->nullable();
                $table->longText('custom_analysis_data')->nullable();
                $table->longText('customer_data_backup')->nullable();
                $table->timestamp('customer_data_collected_at')->nullable();
                $table->text('call_summary')->nullable();
                $table->longText('llm_token_usage')->nullable();
                $table->timestamp('called_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('retell_call_sessions')) {
            Schema::create('retell_call_sessions', function (Blueprint $table) {
                $table->char('id', 36)->primary();
                $table->string('call_id');
                $table->unsignedBigInteger('company_id')->nullable();
                $table->char('branch_id', 36)->nullable();
                $table->string('phone_number')->nullable();
                $table->string('branch_name')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->string('agent_id')->nullable();
                $table->integer('agent_version')->nullable();
                $table->timestamp('started_at');
                $table->timestamp('ended_at')->nullable();
                $table->string('call_status')->default('in_progress');
                $table->string('disconnection_reason')->nullable();
                $table->integer('duration_ms')->nullable();
                $table->string('conversation_flow_id')->nullable();
                $table->string('current_flow_node')->nullable();
                $table->longText('flow_state')->nullable();
                $table->integer('total_events')->default(0);
                $table->integer('function_call_count')->default(0);
                $table->integer('transcript_segment_count')->default(0);
                $table->integer('error_count')->default(0);
                $table->integer('avg_response_time_ms')->nullable();
                $table->integer('max_response_time_ms')->nullable();
                $table->integer('min_response_time_ms')->nullable();
                $table->longText('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('retell_call_events')) {
            Schema::create('retell_call_events', function (Blueprint $table) {
                $table->id();
                $table->char('call_session_id', 36);
                $table->char('correlation_id', 36)->nullable();
                $table->string('event_type');
                $table->timestamp('occurred_at');
                $table->integer('call_offset_ms')->nullable();
                $table->string('function_name')->nullable();
                $table->longText('function_arguments')->nullable();
                $table->longText('function_response')->nullable();
                $table->integer('response_time_ms')->nullable();
                $table->string('function_status')->nullable();
                $table->text('transcript_text')->nullable();
                $table->string('transcript_role')->nullable();
                $table->string('from_node')->nullable();
                $table->string('to_node')->nullable();
                $table->string('transition_trigger')->nullable();
                $table->string('error_code')->nullable();
                $table->text('error_message')->nullable();
                $table->longText('error_context')->nullable();
                $table->longText('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('retell_function_traces')) {
            Schema::create('retell_function_traces', function (Blueprint $table) {
                $table->id();
                $table->char('call_session_id', 36);
                $table->unsignedBigInteger('event_id')->nullable();
                $table->char('correlation_id', 36)->nullable();
                $table->string('function_name');
                $table->integer('execution_sequence')->default(0);
                $table->timestamp('started_at');
                $table->timestamp('completed_at')->nullable();
                $table->integer('duration_ms')->nullable();
                $table->longText('input_params')->nullable();
                $table->longText('output_result')->nullable();
                $table->string('status')->default('pending');
                $table->longText('error_details')->nullable();
                $table->integer('db_query_count')->nullable();
                $table->integer('db_query_time_ms')->nullable();
                $table->integer('external_api_calls')->nullable();
                $table->integer('external_api_time_ms')->nullable();
                $table->longText('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('retell_transcript_segments')) {
            Schema::create('retell_transcript_segments', function (Blueprint $table) {
                $table->id();
                $table->char('call_session_id', 36);
                $table->unsignedBigInteger('event_id')->nullable();
                $table->timestamp('occurred_at');
                $table->integer('call_offset_ms')->nullable();
                $table->integer('segment_sequence')->default(0);
                $table->string('role');
                $table->text('text');
                $table->integer('word_count')->nullable();
                $table->integer('duration_ms')->nullable();
                $table->char('related_function_trace_id', 36)->nullable();
                $table->string('sentiment')->nullable();
                $table->longText('metadata')->nullable();
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
                $table->longText('email_recipients')->nullable();
                $table->longText('muted_recipients')->nullable();
                $table->string('email_template_type')->default('standard');
                $table->string('email_subject_template')->nullable();
                $table->string('email_audio_option')->default('none');
                $table->boolean('include_transcript')->default(true);
                $table->boolean('include_summary')->default(true);
                $table->boolean('email_show_admin_link')->default(false);
                $table->text('email_body_template')->nullable();
                $table->unsignedBigInteger('webhook_configuration_id')->nullable();
                $table->string('webhook_url')->nullable();
                $table->longText('webhook_headers')->nullable();
                $table->text('webhook_payload_template')->nullable();
                $table->unsignedBigInteger('webhook_preset_id')->nullable();
                $table->text('webhook_secret')->nullable();
                $table->boolean('webhook_enabled')->default(true);
                $table->boolean('webhook_include_transcript')->default(false);
                $table->string('contact_type_override')->nullable();
                $table->longText('fallback_emails')->nullable();
                $table->boolean('retry_on_failure')->default(true);
                $table->boolean('wait_for_enrichment')->default(false);
                $table->integer('enrichment_timeout_seconds')->default(180);
                $table->smallInteger('audio_url_ttl_minutes')->default(60);
                $table->boolean('is_active')->default(true);
                $table->string('billing_mode')->default('per_case');
                $table->integer('base_price_cents')->default(0);
                $table->integer('email_price_cents')->default(0);
                $table->integer('webhook_price_cents')->default(0);
                $table->integer('monthly_flat_price_cents')->default(0);
                $table->unsignedBigInteger('template_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('service_cases')) {
            Schema::create('service_cases', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('call_id')->nullable();
                $table->char('retell_call_session_id', 36)->nullable();
                $table->integer('transcript_segment_count')->nullable();
                $table->integer('transcript_char_count')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->unsignedBigInteger('category_id')->nullable();
                $table->string('case_type')->default('incident');
                $table->string('priority')->default('normal');
                $table->string('urgency')->default('normal');
                $table->string('impact')->default('individual');
                $table->string('subject');
                $table->text('description');
                $table->longText('structured_data')->nullable();
                $table->longText('ai_metadata')->nullable();
                $table->string('status')->default('new');
                $table->string('external_reference')->nullable();
                $table->bigInteger('assigned_to')->nullable();
                $table->unsignedBigInteger('assigned_group_id')->nullable();
                $table->timestamp('sla_response_due_at')->nullable();
                $table->timestamp('sla_resolution_due_at')->nullable();
                $table->timestamp('sla_response_met_at')->nullable();
                $table->string('output_status')->default('pending');
                $table->string('enrichment_status')->default('pending');
                $table->string('source')->default('voice');
                $table->timestamp('enriched_at')->nullable();
                $table->string('audio_object_key')->nullable();
                $table->timestamp('audio_expires_at')->nullable();
                $table->timestamp('output_sent_at')->nullable();
                $table->text('output_error')->nullable();
                $table->string('billing_status')->default('unbilled');
                $table->timestamp('billed_at')->nullable();
                $table->unsignedBigInteger('invoice_item_id')->nullable();
                $table->integer('billed_amount_cents')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('service_case_categories')) {
            Schema::create('service_case_categories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('name');
                $table->string('slug');
                $table->text('description')->nullable();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->longText('intent_keywords')->nullable();
                $table->decimal('confidence_threshold', 12, 2)->default(0.7);
                $table->string('default_case_type')->nullable();
                $table->string('default_priority')->nullable();
                $table->integer('sla_response_hours')->nullable();
                $table->integer('sla_resolution_hours')->nullable();
                $table->unsignedBigInteger('output_configuration_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('service_case_activity_logs')) {
            Schema::create('service_case_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('service_case_id');
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('action');
                $table->longText('old_values')->nullable();
                $table->longText('new_values')->nullable();
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->text('reason')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('service_gateway_exchange_logs')) {
            Schema::create('service_gateway_exchange_logs', function (Blueprint $table) {
                $table->id();
                $table->char('event_id', 36);
                $table->string('direction')->default('outbound');
                $table->unsignedBigInteger('call_id')->nullable();
                $table->unsignedBigInteger('service_case_id')->nullable();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedBigInteger('output_configuration_id')->nullable();
                $table->string('endpoint');
                $table->string('http_method')->default('POST');
                $table->longText('request_body_redacted')->nullable();
                $table->longText('response_body_redacted')->nullable();
                $table->longText('headers_redacted')->nullable();
                $table->smallInteger('status_code')->nullable();
                $table->integer('duration_ms')->nullable();
                $table->tinyInteger('attempt_no')->default(1);
                $table->tinyInteger('max_attempts')->default(3);
                $table->string('error_class')->nullable();
                $table->text('error_message')->nullable();
                $table->char('correlation_id', 36)->nullable();
                $table->char('parent_event_id', 36)->nullable();
                $table->boolean('is_test')->default(false);
                $table->timestamp('notification_sent_at')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('completed_at')->nullable();
            });
        }
    }

    // ================================================================
    // BILLING TABLES
    // ================================================================

    private function createBillingTables(): void
    {
        if (!Schema::hasTable('tenants')) {
            Schema::create('tenants', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->string('name');
                $table->string('logo_url')->nullable();
                $table->string('type')->nullable();
                $table->string('domain')->nullable();
                $table->string('subdomain')->nullable();
                $table->bigInteger('balance_cents')->default(0);
                $table->string('slug')->nullable();
                $table->text('api_key')->nullable();
                $table->string('api_key_hash')->nullable();
                $table->string('api_key_prefix')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_verified')->default(false);
                $table->string('calcom_team_slug')->nullable();
                $table->string('email')->nullable();
                $table->text('api_secret')->nullable();
                $table->longText('allowed_ips')->nullable();
                $table->string('webhook_url')->nullable();
                $table->longText('webhook_events')->nullable();
                $table->string('webhook_secret')->nullable();
                $table->string('pricing_plan')->nullable();
                $table->decimal('monthly_fee', 10, 2)->nullable();
                $table->decimal('per_minute_rate', 10, 3)->nullable();
                $table->decimal('discount_percentage', 5, 2)->nullable();
                $table->longText('billing_info')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->id();
                $table->string('invoice_number')->nullable();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->date('due_date')->nullable();
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('tax_rate', 5, 2)->default(0);
                $table->decimal('tax_amount', 12, 2)->default(0);
                $table->decimal('total_amount', 12, 2)->default(0);
                $table->string('status')->default('draft');
                $table->string('currency', 3)->default('EUR');
                $table->string('stripe_invoice_id')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->longText('metadata')->nullable();
                $table->longText('line_items')->nullable();
                $table->string('billing_name')->nullable();
                $table->text('billing_address')->nullable();
                $table->string('billing_email')->nullable();
                $table->string('billing_phone')->nullable();
                $table->string('billing_tax_id')->nullable();
                $table->date('issue_date')->nullable();
                $table->decimal('balance_due', 12, 2)->default(0);
                $table->decimal('discount_amount', 12, 2)->default(0);
                $table->decimal('paid_amount', 12, 2)->default(0);
                $table->text('notes')->nullable();
                $table->text('terms_conditions')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // NOTE: 'aggregate_invoices' created by migration
        // 2026_01_09_120000_create_aggregate_invoices_table.php (no hasTable guard)

        // NOTE: 'company_fee_schedules' created by migration
        // 2026_01_09_100000_create_company_fee_schedules_table.php (no hasTable guard)

        if (!Schema::hasTable('pricing_plans')) {
            Schema::create('pricing_plans', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->string('name');
                $table->string('internal_name');
                $table->string('category')->default('starter');
                $table->string('tagline')->nullable();
                $table->text('description')->nullable();
                $table->text('long_description')->nullable();
                $table->string('type')->default('package');
                $table->string('billing_interval')->default('monthly');
                $table->integer('interval_count')->default(1);
                $table->decimal('base_price', 10, 2)->default(0.00);
                $table->decimal('price_monthly', 10, 2)->nullable()->default(0.00);
                $table->decimal('price_yearly', 10, 2)->nullable()->default(0.00);
                $table->string('currency', 3)->default('EUR');
                $table->integer('minutes_included')->nullable()->default(0);
                $table->integer('included_appointments')->default(0);
                $table->longText('included_features')->nullable();
                $table->decimal('overage_price_per_minute', 10, 4)->nullable();
                $table->decimal('overage_price_per_appointment', 10, 2)->nullable();
                $table->longText('volume_discounts')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->integer('trial_days')->default(0);
                $table->integer('sort_order')->default(0);
                $table->longText('metadata')->nullable();
                $table->timestamps();
                $table->string('billing_period')->default('monthly');
                $table->decimal('yearly_discount_percentage', 5, 2)->nullable();
                $table->decimal('setup_fee', 10, 2)->default(0.00);
                $table->integer('sms_included')->default(0);
                $table->decimal('price_per_minute', 10, 3)->nullable()->default(0.000);
                $table->decimal('price_per_sms', 10, 3)->default(0.190);
                $table->boolean('unlimited_minutes')->default(false);
                $table->boolean('fair_use_policy')->default(false);
                $table->longText('features')->nullable();
                $table->integer('max_users')->nullable();
                $table->integer('max_agents')->nullable();
                $table->integer('max_campaigns')->nullable();
                $table->integer('storage_gb')->nullable();
                $table->integer('api_calls_per_month')->nullable();
                $table->integer('retention_days')->nullable();
                $table->dateTime('available_from')->nullable();
                $table->dateTime('available_until')->nullable();
                $table->longText('target_countries')->nullable();
                $table->longText('customer_types')->nullable();
                $table->integer('min_contract_months')->default(1);
                $table->integer('notice_period_days')->default(30);
                $table->boolean('is_visible')->default(true);
                $table->boolean('is_new')->default(false);
                $table->boolean('requires_approval')->default(false);
                $table->boolean('auto_upgrade_eligible')->default(false);
                $table->string('stripe_product_id')->nullable();
                $table->string('stripe_price_id')->nullable();
                $table->string('tax_category')->default('standard');
                $table->string('welcome_email_template')->nullable();
                $table->boolean('send_usage_alerts')->default(false);
                $table->integer('usage_alert_threshold')->default(80);
                $table->boolean('is_popular')->nullable()->default(false);
            });
        }
    }

    // ================================================================
    // FEATURE TABLES
    // ================================================================

    private function createFeatureTables(): void
    {
        if (!Schema::hasTable('phone_numbers')) {
            Schema::create('phone_numbers', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->unsignedBigInteger('company_id');
                $table->char('branch_id', 36)->nullable();
                $table->string('number', 20)->nullable();
                $table->string('phone_number', 20);
                $table->string('number_normalized', 20)->nullable();
                $table->string('retell_agent_id')->nullable();
                $table->string('agent_id')->nullable();
                $table->string('type', 50)->default('hotline');
                $table->boolean('is_active')->default(true);
                $table->boolean('is_primary')->default(false);
                $table->string('friendly_name')->nullable();
                $table->text('description')->nullable();
                $table->string('provider')->nullable();
                $table->string('provider_id')->nullable();
                $table->string('country_code', 10)->default('+49');
                $table->decimal('monthly_cost', 8, 2)->nullable();
                $table->integer('usage_minutes')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->string('label')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index('number_normalized');
                $table->index(['company_id', 'is_active']);
            });
        }

        if (!Schema::hasTable('policy_configurations')) {
            Schema::create('policy_configurations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->char('branch_id', 36)->nullable();
                $table->string('configurable_type');
                $table->string('configurable_id');
                $table->string('policy_type', 50);
                $table->string('policy_value')->nullable();
                $table->text('description')->nullable();
                $table->longText('config');
                $table->boolean('is_override')->default(false);
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('overrides_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('appointment_modification_stats')) {
            Schema::create('appointment_modification_stats', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('appointment_id');
                $table->unsignedBigInteger('customer_id');
                $table->string('modification_type');
                $table->timestamp('occurred_at');
                $table->timestamps();
                $table->index(['customer_id', 'modification_type', 'occurred_at'], 'idx_customer_type_date');
            });
        }

        if (!Schema::hasTable('appointment_policy_violations')) {
            Schema::create('appointment_policy_violations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('appointment_id');
                $table->unsignedBigInteger('customer_id');
                $table->unsignedBigInteger('policy_configuration_id')->nullable();
                $table->string('violation_type');
                $table->string('attempted_action');
                $table->decimal('hours_before_appointment', 10, 2)->nullable();
                $table->unsignedInteger('quota_current')->nullable();
                $table->unsignedInteger('quota_max')->nullable();
                $table->decimal('fee_applied', 10, 2)->default(0.00);
                $table->timestamp('occurred_at');
                $table->timestamps();
                $table->index(['customer_id', 'occurred_at']);
            });
        }

        if (!Schema::hasTable('notification_preferences')) {
            Schema::create('notification_preferences', function (Blueprint $table) {
                $table->id();
                $table->string('notifiable_type');
                $table->unsignedBigInteger('notifiable_id');
                $table->string('channel');
                $table->string('event_type', 100);
                $table->boolean('is_enabled')->default(true);
                $table->timestamps();
                $table->unique(['notifiable_type', 'notifiable_id', 'channel', 'event_type'], 'unique_notification_pref');
            });
        }

        if (!Schema::hasTable('notification_logs')) {
            Schema::create('notification_logs', function (Blueprint $table) {
                $table->id();
                $table->string('notifiable_type');
                $table->unsignedBigInteger('notifiable_id');
                $table->string('channel');
                $table->string('event_type', 100);
                $table->string('status')->default('pending');
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->text('failure_reason')->nullable();
                $table->longText('metadata')->nullable();
                $table->timestamps();
                $table->index(['notifiable_type', 'notifiable_id']);
                $table->index(['status', 'sent_at']);
            });
        }
    }

    public function down(): void
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $tables = [
            'notification_logs', 'notification_preferences',
            'appointment_policy_violations', 'appointment_modification_stats',
            'appointment_audit_logs', 'appointment_modifications',
            'policy_configurations', 'phone_numbers',
            'pricing_plans',
            'invoices', 'tenants',
            'service_gateway_exchange_logs', 'service_case_activity_logs',
            'service_case_categories', 'service_cases',
            'service_output_configurations',
            'retell_transcript_segments', 'retell_function_traces',
            'retell_call_events', 'retell_call_sessions',
            'callback_escalations', 'callback_requests',
            'appointments', 'calls',
            'notification_providers',
            'customers', 'staff', 'services', 'branches',
            'companies', 'users',
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }

        \DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};
