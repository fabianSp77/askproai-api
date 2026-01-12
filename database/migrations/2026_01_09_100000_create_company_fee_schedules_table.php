<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Billing System Extension: Company Fee Schedules
     *
     * Stores company-specific billing configurations including:
     * - Per-second vs per-minute billing mode
     * - Setup fee tracking
     * - Per-minute rate overrides
     * - Discount percentage overrides
     */
    public function up(): void
    {
        Schema::create('company_fee_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');

            // Billing mode: per_second (new default) or per_minute (legacy)
            $table->enum('billing_mode', ['per_second', 'per_minute'])->default('per_second');

            // Setup fee (one-time, charged on onboarding)
            $table->decimal('setup_fee', 10, 2)->default(0);
            $table->timestamp('setup_fee_billed_at')->nullable();
            $table->foreignId('setup_fee_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();

            // Rate overrides (null = use PricingPlan default)
            $table->decimal('override_per_minute_rate', 10, 3)->nullable()
                ->comment('Override pricing plan per-minute rate');
            $table->decimal('override_discount_percentage', 5, 2)->nullable()
                ->comment('Override discount percentage');

            // Metadata
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // One fee schedule per company
            $table->unique('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_fee_schedules');
    }
};
