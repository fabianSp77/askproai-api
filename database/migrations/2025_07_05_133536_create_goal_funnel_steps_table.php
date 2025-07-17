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
        Schema::create('goal_funnel_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_goal_id')->constrained()->onDelete('cascade');
            $table->integer('step_order');
            $table->string('step_name');
            $table->text('description')->nullable();
            $table->enum('step_type', [
                'call_received',
                'call_answered',
                'data_captured',
                'email_captured',
                'phone_captured',
                'address_captured',
                'appointment_requested',
                'appointment_scheduled',
                'appointment_confirmed',
                'appointment_completed',
                'payment_received',
                'custom'
            ]);
            $table->json('required_fields')->nullable(); // Array of required data fields for this step
            $table->json('conditions')->nullable(); // Additional conditions for step completion
            $table->decimal('expected_conversion_rate', 5, 2)->nullable(); // Expected conversion rate from previous step
            $table->boolean('is_optional')->default(false);
            $table->timestamps();
            
            $table->index(['company_goal_id', 'step_order']);
            $table->unique(['company_goal_id', 'step_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goal_funnel_steps');
    }
};
