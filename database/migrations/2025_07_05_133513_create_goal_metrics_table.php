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
        Schema::create('goal_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_goal_id')->constrained()->onDelete('cascade');
            $table->enum('metric_type', [
                'calls_received',
                'calls_answered',
                'data_collected',
                'appointments_booked',
                'appointments_completed',
                'revenue_generated',
                'customer_satisfaction',
                'average_call_duration',
                'conversion_rate',
                'custom'
            ]);
            $table->string('metric_name');
            $table->text('description')->nullable();
            $table->decimal('target_value', 10, 2);
            $table->enum('target_unit', ['count', 'percentage', 'currency', 'seconds', 'score']);
            $table->decimal('weight', 5, 2)->default(1.0); // For weighted goal achievement
            $table->json('calculation_method')->nullable(); // Custom calculation formula
            $table->json('conditions')->nullable(); // Conditions for metric (e.g., only count calls > 30 seconds)
            $table->string('comparison_operator')->default('gte'); // gte, lte, eq, between
            $table->boolean('is_primary')->default(false); // Main metric for the goal
            $table->timestamps();
            
            $table->index(['company_goal_id', 'metric_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goal_metrics');
    }
};
