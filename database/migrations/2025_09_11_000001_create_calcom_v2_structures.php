<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Cal.com V2 data structures
     */
    public function up(): void
    {
        // Create calcom_users table for mapping Cal.com users to staff
        Schema::create('calcom_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('calcom_user_id')->unique();
            $table->unsignedBigInteger('staff_id')->nullable()->index(); // No FK constraint for now
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('email')->index();
            $table->string('username')->nullable();
            $table->string('name');
            $table->string('bio', 500)->nullable();
            $table->string('avatar_url')->nullable();
            $table->string('timezone')->default('Europe/Berlin');
            $table->string('locale')->default('de');
            $table->integer('default_schedule_id')->nullable();
            $table->boolean('is_away')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            
            $table->index(['company_id', 'calcom_user_id']);
        });

        // Create calcom_teams table for team structures
        Schema::create('calcom_teams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('calcom_team_id')->unique();
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('bio')->nullable();
            $table->string('logo_url')->nullable();
            $table->boolean('hide_branding')->default(false);
            $table->string('parent_team_id')->nullable();
            $table->json('metadata')->nullable();
            $table->json('theme')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            
            $table->index(['company_id', 'branch_id']);
        });

        // Create calcom_schedules table for availability schedules
        Schema::create('calcom_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('calcom_schedule_id')->unique();
            $table->unsignedBigInteger('calcom_user_id')->nullable();
            $table->unsignedBigInteger('staff_id')->nullable()->index(); // No FK constraint for now
            $table->string('name');
            $table->string('timezone')->default('Europe/Berlin');
            $table->boolean('is_default')->default(false);
            $table->json('availability')->nullable(); // Store availability rules
            $table->json('working_hours')->nullable(); // Store working hours
            $table->json('date_overrides')->nullable(); // Store date-specific overrides
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            
            $table->index(['staff_id', 'is_default']);
            $table->index('calcom_user_id');
        });

        // Create calcom_team_members table for team membership
        Schema::create('calcom_team_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('calcom_team_id');
            $table->unsignedBigInteger('calcom_user_id');
            $table->string('role')->default('MEMBER'); // OWNER, ADMIN, MEMBER
            $table->boolean('accepted')->default(true);
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
            
            $table->unique(['calcom_team_id', 'calcom_user_id']);
            $table->index('calcom_team_id');
            $table->index('calcom_user_id');
        });

        // Create calcom_availability_slots table for cached availability
        Schema::create('calcom_availability_slots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('calcom_event_type_id')->index(); // Will add FK later
            $table->unsignedBigInteger('staff_id')->nullable()->index(); // No FK constraint for now
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_available')->default(true);
            $table->integer('seats_available')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('cached_at');
            $table->timestamps();
            
            $table->index(['calcom_event_type_id', 'date']);
            $table->index(['staff_id', 'date']);
            $table->unique(['calcom_event_type_id', 'date', 'start_time']);
        });

        // Extend appointments table with V2-specific fields
        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'calcom_booking_uid')) {
                $table->string('calcom_booking_uid')->nullable()->after('calcom_v2_booking_id');
            }
            if (!Schema::hasColumn('appointments', 'calcom_user_id')) {
                $table->unsignedBigInteger('calcom_user_id')->nullable()->after('calcom_booking_uid');
            }
            if (!Schema::hasColumn('appointments', 'calcom_team_id')) {
                $table->unsignedBigInteger('calcom_team_id')->nullable()->after('calcom_user_id');
            }
            if (!Schema::hasColumn('appointments', 'meeting_url')) {
                $table->string('meeting_url')->nullable();
            }
            if (!Schema::hasColumn('appointments', 'location_type')) {
                $table->string('location_type')->nullable(); // inPerson, link, phone, etc.
            }
            if (!Schema::hasColumn('appointments', 'location_value')) {
                $table->string('location_value')->nullable();
            }
            if (!Schema::hasColumn('appointments', 'attendees')) {
                $table->json('attendees')->nullable(); // Store all attendees
            }
            if (!Schema::hasColumn('appointments', 'responses')) {
                $table->json('responses')->nullable(); // Store booking form responses
            }
            if (!Schema::hasColumn('appointments', 'rescheduled_from_uid')) {
                $table->string('rescheduled_from_uid')->nullable();
            }
            if (!Schema::hasColumn('appointments', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable();
            }
            if (!Schema::hasColumn('appointments', 'rejected_reason')) {
                $table->text('rejected_reason')->nullable();
            }
            if (!Schema::hasColumn('appointments', 'is_recurring')) {
                $table->boolean('is_recurring')->default(false);
            }
            if (!Schema::hasColumn('appointments', 'recurring_event_id')) {
                $table->string('recurring_event_id')->nullable();
            }
            
            // Add indexes for performance
            if (!Schema::hasIndex('appointments', 'appointments_calcom_booking_uid_index')) {
                $table->index('calcom_booking_uid');
            }
            if (!Schema::hasIndex('appointments', 'appointments_calcom_user_id_index')) {
                $table->index('calcom_user_id');
            }
            if (!Schema::hasIndex('appointments', 'appointments_calcom_team_id_index')) {
                $table->index('calcom_team_id');
            }
        });

        // Extend calcom_event_types table with V2 fields
        Schema::table('calcom_event_types', function (Blueprint $table) {
            if (!Schema::hasColumn('calcom_event_types', 'calcom_user_id')) {
                $table->unsignedBigInteger('calcom_user_id')->nullable()->after('calcom_numeric_event_type_id');
            }
            if (!Schema::hasColumn('calcom_event_types', 'calcom_team_id')) {
                $table->unsignedBigInteger('calcom_team_id')->nullable()->after('calcom_user_id');
            }
            if (!Schema::hasColumn('calcom_event_types', 'hosts')) {
                $table->json('hosts')->nullable(); // Multiple hosts for team events
            }
            if (!Schema::hasColumn('calcom_event_types', 'availability_schedule_id')) {
                $table->unsignedBigInteger('availability_schedule_id')->nullable();
            }
            if (!Schema::hasColumn('calcom_event_types', 'booking_fields')) {
                $table->json('booking_fields')->nullable(); // Custom booking form fields
            }
            if (!Schema::hasColumn('calcom_event_types', 'confirmation_policy')) {
                $table->string('confirmation_policy')->default('auto'); // auto, manual
            }
            if (!Schema::hasColumn('calcom_event_types', 'cancellation_policy')) {
                $table->json('cancellation_policy')->nullable();
            }
            if (!Schema::hasColumn('calcom_event_types', 'rescheduling_policy')) {
                $table->json('rescheduling_policy')->nullable();
            }
            
            // Add indexes
            if (!Schema::hasIndex('calcom_event_types', 'calcom_event_types_calcom_user_id_index')) {
                $table->index('calcom_user_id');
            }
            if (!Schema::hasIndex('calcom_event_types', 'calcom_event_types_calcom_team_id_index')) {
                $table->index('calcom_team_id');
            }
        });

        // Create calcom_webhooks table for webhook configuration
        Schema::create('calcom_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('calcom_webhook_id')->unique();
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('subscriber_url');
            $table->json('event_triggers'); // booking.created, booking.cancelled, etc.
            $table->boolean('active')->default(true);
            $table->string('secret')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();
            
            $table->index('company_id');
        });

        // Create calcom_sync_logs table for tracking sync operations
        Schema::create('calcom_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('sync_type'); // bookings, event_types, schedules, users, teams
            $table->string('status'); // started, completed, failed
            $table->integer('records_processed')->default(0);
            $table->integer('records_created')->default(0);
            $table->integer('records_updated')->default(0);
            $table->integer('records_failed')->default(0);
            $table->json('errors')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['sync_type', 'status']);
            $table->index('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove extended columns from appointments
        Schema::table('appointments', function (Blueprint $table) {
            $columns = [
                'calcom_booking_uid', 'calcom_user_id', 'calcom_team_id',
                'meeting_url', 'location_type', 'location_value',
                'attendees', 'responses', 'rescheduled_from_uid',
                'cancellation_reason', 'rejected_reason', 'is_recurring',
                'recurring_event_id'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('appointments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // Remove extended columns from calcom_event_types
        Schema::table('calcom_event_types', function (Blueprint $table) {
            $columns = [
                'calcom_user_id', 'calcom_team_id', 'hosts',
                'availability_schedule_id', 'booking_fields',
                'confirmation_policy', 'cancellation_policy',
                'rescheduling_policy'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('calcom_event_types', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // Drop new tables
        Schema::dropIfExists('calcom_sync_logs');
        Schema::dropIfExists('calcom_webhooks');
        Schema::dropIfExists('calcom_availability_slots');
        Schema::dropIfExists('calcom_team_members');
        Schema::dropIfExists('calcom_schedules');
        Schema::dropIfExists('calcom_teams');
        Schema::dropIfExists('calcom_users');
    }
};