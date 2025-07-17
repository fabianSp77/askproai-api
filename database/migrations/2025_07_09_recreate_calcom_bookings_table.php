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
        // Create calcom_bookings table with the correct structure matching the model
        Schema::create('calcom_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('calcom_uid')->nullable()->index(); // External Cal.com booking ID
            $table->foreignId('appointment_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('status')->default('active');
            $table->json('raw_payload')->nullable(); // Store the raw Cal.com webhook payload
            $table->timestamps();
            
            // Add indexes for performance
            $table->index('status');
            $table->index(['appointment_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calcom_bookings');
    }
};