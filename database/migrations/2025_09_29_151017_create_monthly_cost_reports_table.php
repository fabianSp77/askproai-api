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
        if (Schema::hasTable('monthly_cost_reports')) {
            return;
        }

        Schema::create('monthly_cost_reports', function (Blueprint $table) {
            $table->id();
            $table->date('month')->comment('First day of the reporting month');
            $table->enum('service_name', ['calcom', 'retellai', 'twilio', 'openai', 'elevenlabs', 'other']);
            $table->integer('fixed_costs')->default(0)->comment('Fixed monthly costs in EUR cents');
            $table->integer('usage_costs')->default(0)->comment('Variable usage costs in EUR cents');
            $table->integer('total_costs')->default(0)->comment('Total costs in EUR cents');
            $table->integer('call_count')->default(0)->comment('Number of calls this month');
            $table->integer('total_minutes')->default(0)->comment('Total minutes used this month');
            $table->enum('currency', ['USD', 'EUR', 'GBP'])->default('EUR');
            $table->decimal('avg_exchange_rate', 10, 6)->nullable()->comment('Average exchange rate used');
            $table->json('cost_breakdown')->nullable()->comment('Detailed breakdown of costs');
            $table->text('notes')->nullable();
            $table->boolean('is_final')->default(false)->comment('Whether this report is finalized');
            $table->timestamps();

            // Indexes
            $table->unique(['month', 'service_name']);
            $table->index(['month', 'is_final']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_cost_reports');
    }
};
