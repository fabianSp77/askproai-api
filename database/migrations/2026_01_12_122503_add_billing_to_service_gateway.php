<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add billing fields to Service Gateway tables.
 *
 * ServiceOutputConfiguration:
 * - billing_mode: per_case, monthly_flat, or none
 * - base_price_cents: Base price per case
 * - email_price_cents: Additional cost for email output
 * - webhook_price_cents: Additional cost for webhook output
 *
 * ServiceCase:
 * - billing_status: unbilled, billed, waived
 * - billed_at: When the case was billed
 * - invoice_item_id: Link to AggregateInvoiceItem
 */
return new class extends Migration
{
    public function up(): void
    {
        // Add billing fields to service_output_configurations
        Schema::table('service_output_configurations', function (Blueprint $table) {
            $table->enum('billing_mode', ['per_case', 'monthly_flat', 'none'])
                ->default('none')
                ->after('is_active')
                ->comment('How this output configuration bills: per_case, monthly_flat, or none');

            $table->unsignedInteger('base_price_cents')
                ->default(0)
                ->after('billing_mode')
                ->comment('Base price per case in cents (e.g., 50 = €0.50)');

            $table->unsignedInteger('email_price_cents')
                ->default(50)
                ->after('base_price_cents')
                ->comment('Additional cost for email output in cents');

            $table->unsignedInteger('webhook_price_cents')
                ->default(50)
                ->after('email_price_cents')
                ->comment('Additional cost for webhook output in cents');

            $table->unsignedInteger('monthly_flat_price_cents')
                ->default(2900)
                ->after('webhook_price_cents')
                ->comment('Monthly flat rate in cents (e.g., 2900 = €29.00)');
        });

        // Add billing tracking to service_cases
        Schema::table('service_cases', function (Blueprint $table) {
            $table->enum('billing_status', ['unbilled', 'billed', 'waived'])
                ->default('unbilled')
                ->after('output_error')
                ->comment('Billing status: unbilled, billed, waived');

            $table->timestamp('billed_at')
                ->nullable()
                ->after('billing_status')
                ->comment('When the case was billed');

            $table->unsignedBigInteger('invoice_item_id')
                ->nullable()
                ->after('billed_at')
                ->comment('Link to aggregate_invoice_items');

            $table->unsignedInteger('billed_amount_cents')
                ->nullable()
                ->after('invoice_item_id')
                ->comment('The amount that was billed in cents');

            // Index for billing queries
            $table->index(['company_id', 'billing_status', 'created_at'], 'idx_service_cases_billing');
        });

        // Add index for monthly billing aggregation
        Schema::table('service_output_configurations', function (Blueprint $table) {
            $table->index(['company_id', 'billing_mode', 'is_active'], 'idx_output_config_billing');
        });
    }

    public function down(): void
    {
        Schema::table('service_output_configurations', function (Blueprint $table) {
            $table->dropIndex('idx_output_config_billing');
            $table->dropColumn([
                'billing_mode',
                'base_price_cents',
                'email_price_cents',
                'webhook_price_cents',
                'monthly_flat_price_cents',
            ]);
        });

        Schema::table('service_cases', function (Blueprint $table) {
            $table->dropIndex('idx_service_cases_billing');
            $table->dropColumn([
                'billing_status',
                'billed_at',
                'invoice_item_id',
                'billed_amount_cents',
            ]);
        });
    }
};
