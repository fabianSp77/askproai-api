<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Add calendar sync fields to staff table
        
        if (!Schema::hasTable('staff')) {
            return;
        }

        Schema::table('staff', function (Blueprint $table) {
            if (!Schema::hasColumn('staff', 'google_calendar_id')) {
                $table->string('google_calendar_id')->nullable();
            }
            if (!Schema::hasColumn('staff', 'google_calendar_token')) {
                $table->text('google_calendar_token')->nullable();
            }
            if (!Schema::hasColumn('staff', 'google_refresh_token')) {
                $table->text('google_refresh_token')->nullable();
            }
            if (!Schema::hasColumn('staff', 'google_webhook_id')) {
                $table->string('google_webhook_id')->nullable();
            }
            if (!Schema::hasColumn('staff', 'google_webhook_expires_at')) {
                $table->timestamp('google_webhook_expires_at')->nullable();
            }
            if (!Schema::hasColumn('staff', 'outlook_calendar_id')) {
                $table->string('outlook_calendar_id')->nullable();
            }
            if (!Schema::hasColumn('staff', 'outlook_access_token')) {
                $table->text('outlook_access_token')->nullable();
            }
            if (!Schema::hasColumn('staff', 'outlook_refresh_token')) {
                $table->text('outlook_refresh_token')->nullable();
            }
            if (!Schema::hasColumn('staff', 'outlook_token_expires_at')) {
                $table->timestamp('outlook_token_expires_at')->nullable();
            }
            if (!Schema::hasColumn('staff', 'outlook_webhook_id')) {
                $table->string('outlook_webhook_id')->nullable();
            }
            if (!Schema::hasColumn('staff', 'outlook_webhook_expires_at')) {
                $table->timestamp('outlook_webhook_expires_at')->nullable();
            }
            if (!Schema::hasColumn('staff', 'calendar_color')) {
                $table->string('calendar_color', 7)->default('#3788d8');
            }
            if (!Schema::hasColumn('staff', 'working_hours')) {
                $table->json('working_hours')->nullable();
            }
        });

        // Add calendar sync fields to appointments table
        
        if (!Schema::hasTable('appointments')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'google_event_id')) {
                $table->string('google_event_id')->nullable(); // No index due to table limit
            }
            if (!Schema::hasColumn('appointments', 'outlook_event_id')) {
                $table->string('outlook_event_id')->nullable(); // No index due to table limit
            }
            if (!Schema::hasColumn('appointments', 'is_recurring')) {
                $table->boolean('is_recurring')->default(false);
            }
            if (!Schema::hasColumn('appointments', 'recurring_pattern')) {
                $table->json('recurring_pattern')->nullable();
            }
            if (!Schema::hasColumn('appointments', 'external_calendar_source')) {
                $table->string('external_calendar_source')->nullable();
            }
            if (!Schema::hasColumn('appointments', 'external_calendar_id')) {
                $table->string('external_calendar_id')->nullable();
            }
        });

        // Add parent_appointment_id separately to avoid constraint issues
        if (!Schema::hasColumn('appointments', 'parent_appointment_id')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->unsignedBigInteger('parent_appointment_id')->nullable();
            });
        }

        // Create recurring appointments table for patterns
        if (Schema::hasTable('recurring_appointment_patterns')) {
            return;
        }

        Schema::create('recurring_appointment_patterns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->onDelete('cascade');
            $table->string('frequency'); // daily, weekly, monthly, yearly
            $table->integer('interval')->default(1); // every N days/weeks/months
            $table->json('days_of_week')->nullable(); // for weekly recurrence
            $table->integer('day_of_month')->nullable(); // for monthly recurrence
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->integer('occurrences')->nullable(); // number of occurrences
            $table->json('exceptions')->nullable(); // dates to skip
            $table->timestamps();

            $table->index(['appointment_id', 'start_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('recurring_appointment_patterns');

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn([
                'google_event_id',
                'outlook_event_id',
                'is_recurring',
                'recurring_pattern',
                'parent_appointment_id',
                'external_calendar_source',
                'external_calendar_id'
            ]);
        });

        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn([
                'google_calendar_id',
                'google_calendar_token',
                'google_refresh_token',
                'google_webhook_id',
                'google_webhook_expires_at',
                'outlook_calendar_id',
                'outlook_access_token',
                'outlook_refresh_token',
                'outlook_token_expires_at',
                'outlook_webhook_id',
                'outlook_webhook_expires_at',
                'calendar_color',
                'working_hours'
            ]);
        });
    }
};