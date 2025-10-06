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
        // Drop the old table and recreate with correct structure
        Schema::dropIfExists('platform_costs');

        if (Schema::hasTable('platform_costs')) {
            return;
        }

        Schema::create('platform_costs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->enum('platform', ['retell', 'twilio', 'calcom', 'openai', 'elevenlabs', 'other'])
                ->index()
                ->comment('Platform/service name');
            $table->string('service_type', 50)->nullable()
                ->comment('Type of service (api_call, telephony, subscription, etc.)');
            $table->enum('cost_type', ['usage', 'fixed', 'subscription', 'one_time'])
                ->comment('Cost structure type');
            $table->integer('amount_cents')->comment('Amount in cents');
            $table->enum('currency', ['USD', 'EUR', 'GBP'])->default('EUR');
            $table->datetime('period_start')->comment('Period start date');
            $table->datetime('period_end')->nullable()->comment('Period end date');
            $table->decimal('usage_quantity', 12, 4)->nullable()
                ->comment('Quantity of usage (minutes, API calls, etc.)');
            $table->string('usage_unit', 50)->nullable()
                ->comment('Unit of measurement (minutes, calls, users, etc.)');
            $table->string('external_reference_id')->nullable()->index()
                ->comment('External reference (call ID, invoice ID, etc.)');
            $table->json('metadata')->nullable()->comment('Additional platform-specific data');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Add indexes
            $table->index(['company_id', 'platform']);
            $table->index(['period_start', 'period_end']);
            $table->index(['platform', 'service_type']);

            // Add foreign key
            $table->foreign('company_id')->references('id')->on('companies')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_costs');

        // Recreate the original structure
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

            $table->index(['service_name', 'is_active']);
            $table->index(['effective_date', 'valid_until']);
        });
    }
};