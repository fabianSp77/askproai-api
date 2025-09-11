<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Consolidated Cal.com integration tables
     * Includes event types and bookings with all fields
     */
    public function up(): void
    {
        // Cal.com Event Types - Available booking types
        Schema::create('calcom_event_types', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id');
            $table->unsignedBigInteger('staff_id')->nullable()->index();
            $table->string('calcom_id')->index(); // Cal.com event type ID (string)
            $table->unsignedBigInteger('calcom_numeric_event_type_id')->nullable()->index();
            $table->string('title');
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->integer('length')->nullable(); // Legacy field
            $table->decimal('price', 8, 2)->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('is_active')->default(true); // Alternative field
            $table->json('settings')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index('tenant_id');
            $table->index(['tenant_id', 'active']);
            $table->index(['tenant_id', 'staff_id']);
            $table->unique(['tenant_id', 'calcom_id']);
        });

        // Cal.com Bookings - Individual booking records
        Schema::create('calcom_bookings', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id');
            $table->unsignedBigInteger('appointment_id')->nullable()->index();
            $table->string('calcom_booking_id')->index(); // Cal.com booking ID (integer)
            $table->string('calcom_uid')->nullable()->unique(); // Cal.com booking UID
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->string('attendee_name')->nullable();
            $table->string('attendee_email')->nullable();
            $table->string('attendee_timezone')->nullable();
            $table->enum('status', ['booked', 'rescheduled', 'cancelled', 'completed'])->default('booked');
            $table->json('raw_payload')->nullable(); // Complete webhook JSON
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index('tenant_id');
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'start_time']);
            $table->index(['tenant_id', 'attendee_email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calcom_bookings');
        Schema::dropIfExists('calcom_event_types');
    }
};