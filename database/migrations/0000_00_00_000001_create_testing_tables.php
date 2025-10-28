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

        // Users table (required for authentication)
        if (!Schema::hasTable('users')) {
            Schema::create('users', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->unsignedBigInteger('staff_id')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }

        // Roles & Permissions tables (Spatie)
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
                $table->unique(['name', 'guard_name']);
            });
        }

        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
                $table->unique(['name', 'guard_name']);
            });
        }

        if (!Schema::hasTable('model_has_roles')) {
            Schema::create('model_has_roles', function ($table) {
                $table->unsignedBigInteger('role_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->primary(['role_id', 'model_id', 'model_type']);
                $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('model_has_permissions')) {
            Schema::create('model_has_permissions', function ($table) {
                $table->unsignedBigInteger('permission_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->primary(['permission_id', 'model_id', 'model_type'], 'model_has_permissions_permission_model_type_primary');
                $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('role_has_permissions')) {
            Schema::create('role_has_permissions', function ($table) {
                $table->unsignedBigInteger('permission_id');
                $table->unsignedBigInteger('role_id');
                $table->primary(['permission_id', 'role_id']);
                $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
                $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('companies')) {
            Schema::create('companies', function ($table) {
                $table->id();
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('calcom_team_id')->nullable();
                $table->string('api_key', 500)->nullable();
                $table->timestamps();
                $table->softDeletes(); // Required for SoftDeletes trait
            });
        }

        if (!Schema::hasTable('branches')) {
            Schema::create('branches', function ($table) {
                $table->char('id', 36)->primary(); // UUID as primary key
                $table->unsignedBigInteger('company_id');
                $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
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
                $table->char('id', 36)->primary(); // UUID as primary key
                $table->unsignedBigInteger('company_id');
                $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
                $table->char('branch_id', 36)->nullable(); // UUID foreign key
                $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
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
                $table->uuid('id')->primary();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->char('branch_id', 36)->nullable();
                $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();

                $table->string('phone_number', 20);
                $table->string('number_normalized', 20)->unique();

                $table->string('retell_agent_id')->nullable();
                $table->string('agent_id')->nullable();
                $table->string('type', 50)->default('hotline');

                $table->boolean('is_active')->default(true);
                $table->boolean('is_primary')->default(false);

                $table->string('friendly_name')->nullable();
                $table->text('description')->nullable();
                $table->string('provider')->nullable();
                $table->string('country_code', 10)->default('+49');

                $table->timestamps();

                $table->index('number_normalized');
                $table->index(['company_id', 'is_active']);
            });
        }

        if (!Schema::hasTable('appointments')) {
            Schema::create('appointments', function ($table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->char('branch_id', 36)->nullable(); // UUID foreign key
                $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
                $table->foreignId('service_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
                $table->char('staff_id', 36)->nullable(); // UUID foreign key
                $table->foreign('staff_id')->references('id')->on('staff')->nullOnDelete();
                $table->timestamp('start_time');
                $table->timestamp('end_time');
                $table->enum('status', ['pending', 'booked', 'confirmed', 'completed', 'cancelled', 'no-show'])->default('pending');
                $table->string('source')->nullable();
                $table->unsignedInteger('calcom_booking_id')->nullable();
                $table->string('calcom_v2_booking_id')->nullable();
                $table->decimal('price', 10, 2)->nullable();
                $table->json('metadata')->nullable();
                $table->text('notes')->nullable();
                $table->string('google_event_id')->nullable();
                $table->string('outlook_event_id')->nullable();
                $table->boolean('is_recurring')->default(false);
                $table->json('recurring_pattern')->nullable();
                $table->string('external_calendar_source')->nullable();
                $table->string('external_calendar_id')->nullable();
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
                $table->char('branch_id', 36)->nullable(); // UUID foreign key
                $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
                $table->char('assigned_staff_id', 36)->nullable(); // UUID foreign key
                $table->foreign('assigned_staff_id')->references('id')->on('staff')->nullOnDelete();
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
                $table->char('escalated_by_id', 36)->nullable(); // UUID foreign key
                $table->foreign('escalated_by_id')->references('id')->on('staff')->nullOnDelete();
                $table->char('escalated_to_id', 36)->nullable(); // UUID foreign key
                $table->foreign('escalated_to_id')->references('id')->on('staff')->nullOnDelete();
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

        // Add foreign keys to users table (after other tables exist)
        // In test environment, foreign keys are optional for performance
        // The factories will handle data integrity
        if (Schema::hasTable('users') && Schema::hasTable('companies') && Schema::hasColumn('users', 'company_id')) {
            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
                });
            } catch (\Exception $e) {
                // FK already exists or other error - skip silently in test environment
            }
        }

        if (Schema::hasTable('users') && Schema::hasTable('branches') && Schema::hasColumn('users', 'branch_id')) {
            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
                });
            } catch (\Exception $e) {
                // FK already exists - skip
            }
        }

        if (Schema::hasTable('users') && Schema::hasTable('staff') && Schema::hasColumn('users', 'staff_id')) {
            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->foreign('staff_id')->references('id')->on('staff')->onDelete('set null');
                });
            } catch (\Exception $e) {
                // FK already exists - skip
            }
        }

        // Seed essential roles for testing
        \DB::table('roles')->insertOrIgnore([
            ['name' => 'super_admin', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'admin', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'manager', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'staff', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'company_owner', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'company_admin', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'company_manager', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'company_staff', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
        ]);
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
