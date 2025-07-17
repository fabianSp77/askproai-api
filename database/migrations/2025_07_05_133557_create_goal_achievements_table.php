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
        Schema::create('goal_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_goal_id')->constrained()->onDelete('cascade');
            $table->foreignId('goal_metric_id')->nullable()->constrained()->onDelete('set null');
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->dateTime('period_start');
            $table->dateTime('period_end');
            $table->enum('period_type', ['hourly', 'daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'custom']);
            $table->decimal('achieved_value', 10, 2);
            $table->decimal('target_value', 10, 2);
            $table->decimal('achievement_percentage', 5, 2);
            $table->json('breakdown')->nullable(); // Detailed breakdown of achievement
            $table->json('funnel_data')->nullable(); // Conversion funnel data for this period
            $table->timestamps();
            
            $table->index(['company_goal_id', 'period_start', 'period_end']);
            $table->index(['goal_metric_id', 'period_type']);
            $table->index('achievement_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goal_achievements');
    }
};
