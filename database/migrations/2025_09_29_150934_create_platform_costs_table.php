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
        if (Schema::hasTable('platform_costs')) {
            return;
        }

        Schema::create('platform_costs', function (Blueprint $table) {
            $table->id();
            $table->enum('service_name', ['calcom', 'retellai', 'twilio', 'openai', 'elevenlabs', 'other'])
                ->index()
                ->comment('Name of the external service');
            $table->enum('cost_type', ['fixed_monthly', 'per_minute', 'per_user', 'per_number', 'per_token', 'per_call'])
                ->comment('Type of cost structure');
            $table->integer('amount')->comment('Amount in smallest currency unit (cents)');
            $table->enum('currency', ['USD', 'EUR', 'GBP'])->default('EUR');
            $table->integer('amount_in_eur')->nullable()->comment('Converted amount in EUR cents');
            $table->decimal('exchange_rate', 10, 6)->nullable()->comment('Exchange rate used for conversion');
            $table->string('description')->nullable();
            $table->date('effective_date')->comment('When this cost becomes effective');
            $table->date('valid_until')->nullable()->comment('When this cost expires');
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable()->comment('Additional service-specific data');
            $table->timestamps();

            // Indexes for better query performance
            $table->index(['service_name', 'is_active']);
            $table->index(['effective_date', 'valid_until']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_costs');
    }
};
