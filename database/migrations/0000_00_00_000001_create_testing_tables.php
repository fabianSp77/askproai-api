<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Testing-only migration to create essential tables
 * Production already has these tables
 */
return new class extends Migration
{
    public function up(): void
    {
        // Create core tables for testing environment
        if (!Schema::hasTable('companies')) {
            Schema::create('companies', function ($table) {
                $table->id();
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('calcom_team_id')->nullable();
                $table->string('api_key', 500)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('branches')) {
            Schema::create('branches', function ($table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('slug')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['company_id', 'slug']);
            });
        }

        if (!Schema::hasTable('services')) {
            Schema::create('services', function ($table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->integer('duration_minutes')->default(30);
                $table->decimal('price', 10, 2)->nullable();
                $table->unsignedInteger('calcom_event_type_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('staff')) {
            Schema::create('staff', function ($table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('email')->nullable();
                $table->unsignedInteger('calcom_user_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('customers')) {
            Schema::create('customers', function ($table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('email')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('phone_numbers')) {
            Schema::create('phone_numbers', function ($table) {
                $table->id();
                $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
                $table->string('phone_number', 20);
                $table->boolean('is_primary')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('appointments')) {
            Schema::create('appointments', function ($table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('service_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
                $table->foreignId('staff_id')->nullable()->constrained()->nullOnDelete();
                $table->timestamp('start_time');
                $table->timestamp('end_time');
                $table->enum('status', ['scheduled', 'completed', 'cancelled', 'no_show'])->default('scheduled');
                $table->unsignedInteger('calcom_booking_id')->nullable();
                $table->timestamps();
            });
        }

        // New feature tables
        if (!Schema::hasTable('policy_configurations')) {
            Schema::create('policy_configurations', function ($table) {
                $table->id();
                $table->enum('entity_type', ['company', 'branch', 'service', 'staff']);
                $table->unsignedBigInteger('entity_id');
                $table->unsignedInteger('cancellation_hours')->default(24);
                $table->unsignedInteger('reschedule_hours')->default(12);
                $table->enum('cancellation_fee_type', ['none', 'fixed', 'percentage'])->default('none');
                $table->decimal('cancellation_fee_amount', 10, 2)->default(0.00);
                $table->enum('reschedule_fee_type', ['none', 'fixed', 'percentage'])->default('none');
                $table->decimal('reschedule_fee_amount', 10, 2)->default(0.00);
                $table->unsignedInteger('max_cancellations_per_month')->nullable();
                $table->unsignedInteger('max_reschedules_per_month')->nullable();
                $table->timestamps();
                $table->unique(['entity_type', 'entity_id']);
            });
        }

        if (!Schema::hasTable('appointment_modification_stats')) {
            Schema::create('appointment_modification_stats', function ($table) {
                $table->id();
                $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
                $table->enum('modification_type', ['cancellation', 'reschedule']);
                $table->timestamp('occurred_at');
                $table->timestamps();
                $table->index(['customer_id', 'modification_type', 'occurred_at'], 'idx_customer_type_date');
            });
        }

        if (!Schema::hasTable('callback_requests')) {
            Schema::create('callback_requests', function ($table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('assigned_staff_id')->nullable()->constrained('staff')->nullOnDelete();
                $table->string('customer_name');
                $table->string('customer_phone', 20);
                $table->string('customer_email')->nullable();
                $table->json('preferred_times')->nullable();
                $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
                $table->enum('status', ['pending', 'assigned', 'contacted', 'completed', 'cancelled'])->default('pending');
                $table->text('notes')->nullable();
                $table->timestamp('contacted_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
                $table->index(['status', 'priority']);
                $table->index(['assigned_staff_id', 'status']);
                $table->index(['expires_at', 'status']);
            });
        }

        if (!Schema::hasTable('callback_escalations')) {
            Schema::create('callback_escalations', function ($table) {
                $table->id();
                $table->foreignId('callback_request_id')->constrained()->cascadeOnDelete();
                $table->foreignId('escalated_by_id')->nullable()->constrained('staff')->nullOnDelete();
                $table->foreignId('escalated_to_id')->nullable()->constrained('staff')->nullOnDelete();
                $table->text('reason')->nullable();
                $table->timestamp('escalated_at');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('appointment_policy_violations')) {
            Schema::create('appointment_policy_violations', function ($table) {
                $table->id();
                $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
                $table->foreignId('policy_configuration_id')->nullable()->constrained()->nullOnDelete();
                $table->enum('violation_type', ['cancellation_too_late', 'reschedule_too_late', 'quota_exceeded']);
                $table->enum('attempted_action', ['cancel', 'reschedule']);
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
            Schema::create('notification_preferences', function ($table) {
                $table->id();
                $table->string('notifiable_type');
                $table->unsignedBigInteger('notifiable_id');
                $table->enum('channel', ['email', 'sms', 'push', 'in_app']);
                $table->string('event_type', 100);
                $table->boolean('is_enabled')->default(true);
                $table->timestamps();
                $table->unique(['notifiable_type', 'notifiable_id', 'channel', 'event_type'], 'unique_notification_pref');
            });
        }

        if (!Schema::hasTable('notification_logs')) {
            Schema::create('notification_logs', function ($table) {
                $table->id();
                $table->string('notifiable_type');
                $table->unsignedBigInteger('notifiable_id');
                $table->enum('channel', ['email', 'sms', 'push', 'in_app']);
                $table->string('event_type', 100);
                $table->enum('status', ['pending', 'sent', 'failed', 'delivered'])->default('pending');
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->text('failure_reason')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['notifiable_type', 'notifiable_id']);
                $table->index(['status', 'sent_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('phone_numbers');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('service_staff');
        Schema::dropIfExists('staff');
        Schema::dropIfExists('services');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('companies');
    }
};
