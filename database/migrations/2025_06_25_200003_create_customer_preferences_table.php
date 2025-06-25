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
        Schema::create('customer_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            
            // Booking preferences
            $table->json('preferred_days_of_week')->nullable(); // [1,3,5] = Mon, Wed, Fri
            $table->json('preferred_time_slots')->nullable(); // ["morning", "afternoon"]
            $table->time('earliest_booking_time')->nullable();
            $table->time('latest_booking_time')->nullable();
            $table->integer('preferred_duration_minutes')->nullable();
            $table->integer('advance_booking_days')->default(7); // How far in advance they prefer to book
            
            // Service preferences
            $table->json('preferred_services')->nullable(); // Array of service IDs
            $table->json('avoided_services')->nullable();
            $table->json('preferred_staff_ids')->nullable(); // Array of staff IDs
            $table->json('avoided_staff_ids')->nullable();
            $table->foreignId('preferred_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            
            // Communication preferences
            $table->boolean('reminder_24h')->default(true);
            $table->boolean('reminder_2h')->default(true);
            $table->boolean('reminder_sms')->default(false);
            $table->boolean('reminder_whatsapp')->default(false);
            $table->boolean('marketing_consent')->default(false);
            $table->boolean('birthday_greetings')->default(true);
            $table->json('communication_blackout_times')->nullable(); // Times not to contact
            
            // Special requirements
            $table->json('accessibility_needs')->nullable();
            $table->json('health_conditions')->nullable();
            $table->json('allergies')->nullable();
            $table->text('special_instructions')->nullable();
            
            // Behavior patterns (auto-learned)
            $table->json('booking_patterns')->nullable(); // ML-derived patterns
            $table->json('cancellation_patterns')->nullable();
            $table->float('punctuality_score')->default(1.0); // 0-1 score
            $table->float('reliability_score')->default(1.0);
            $table->json('service_history')->nullable(); // Frequency of different services
            
            // Pricing preferences
            $table->boolean('price_sensitive')->default(false);
            $table->decimal('average_spend', 10, 2)->default(0);
            $table->json('preferred_payment_methods')->nullable();
            $table->boolean('auto_charge_enabled')->default(false);
            
            $table->timestamps();
            
            // Indexes
            $table->unique(['customer_id', 'company_id']);
            $table->index('preferred_branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_preferences');
    }
};