<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('appointment_wishes', function (Blueprint $table) {
            $table->id();

            // Multi-Tenant & Call Context
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('call_id');
            $table->unsignedBigInteger('customer_id')->nullable();

            // Desired Appointment Details
            $table->datetime('desired_date')->nullable();
            $table->time('desired_time')->nullable();
            $table->integer('desired_duration')->default(45); // in minutes
            $table->string('desired_service')->nullable(); // Service name from agent

            // Alternatives Offered
            $table->json('alternatives_offered')->nullable(); // Array of offered alternatives

            // Status & Tracking
            $table->enum('rejection_reason', [
                'not_available',      // System couldn't find available slot
                'customer_declined',  // Customer rejected offered alternatives
                'technical_error',    // Error during booking process
                'low_confidence',     // Booking confidence too low
            ])->nullable();

            $table->enum('status', [
                'pending',      // Wish recorded, awaiting follow-up
                'contacted',    // Team reached out to customer
                'rebooked',     // Customer successfully rebooked
                'expired',      // 30 days passed, archived
            ])->default('pending');

            // Follow-up Information
            $table->text('follow_up_notes')->nullable();
            $table->datetime('contacted_at')->nullable();
            $table->datetime('resolved_at')->nullable();
            $table->unsignedBigInteger('follow_up_by_user_id')->nullable(); // Who contacted customer

            // Metadata
            $table->json('metadata')->nullable(); // Additional context (agent_id, extraction_confidence, etc.)

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes for Performance
            $table->index(['company_id', 'status', 'created_at']);
            $table->index(['call_id']);
            $table->index(['customer_id', 'status']);
            $table->index(['status', 'created_at']); // For expiration job & queries

            // Foreign Keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('call_id')->references('id')->on('calls')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_wishes');
    }
};
