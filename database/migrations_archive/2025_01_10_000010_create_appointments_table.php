<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Consolidated appointments table with all fields
     * Includes relationships to customers, staff, services, branches, and calls
     */
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id');
            
            // Relationships
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('call_id')->nullable()->index();
            $table->unsignedBigInteger('staff_id')->nullable()->index();
            $table->unsignedBigInteger('service_id')->nullable()->index();
            $table->uuid('branch_id')->nullable()->index();
            
            // External integrations
            $table->string('external_id')->nullable()->index(); // Cal.com booking ID
            $table->string('calcom_booking_id')->nullable()->index();
            
            // Appointment details
            $table->timestamp('start_time')->nullable(); // Renamed from 'start' for clarity
            $table->timestamp('starts_at')->nullable(); // Alternative timestamp field
            $table->timestamp('end_time')->nullable();
            $table->timestamp('ends_at')->nullable(); // Alternative timestamp field
            $table->integer('duration_minutes')->nullable();
            
            // Status and metadata
            $table->enum('status', [
                'scheduled', 'confirmed', 'cancelled', 'completed', 
                'no_show', 'rescheduled', 'pending'
            ])->default('scheduled');
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->json('payload')->nullable(); // Raw data from external systems
            
            // Tracking fields
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index('tenant_id');
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'customer_id']);
            $table->index(['tenant_id', 'staff_id']);
            $table->index(['tenant_id', 'service_id']);
            $table->index(['tenant_id', 'branch_id']);
            $table->index(['tenant_id', 'start_time']);
            $table->index(['tenant_id', 'starts_at']);
            $table->index(['tenant_id', 'created_at']);
            $table->index(['status', 'start_time']);
            $table->index(['status', 'starts_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};