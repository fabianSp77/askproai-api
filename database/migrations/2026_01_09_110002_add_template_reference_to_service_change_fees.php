<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds template reference to service_change_fees
 * Allows fees to be created from templates while maintaining the original price context
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_change_fees', function (Blueprint $table) {
            // Link to template (optional - can still be custom)
            $table->foreignId('template_id')
                ->nullable()
                ->after('category')
                ->constrained('service_fee_templates')
                ->nullOnDelete();

            // Link to company pricing override used (for audit)
            $table->foreignId('company_pricing_id')
                ->nullable()
                ->after('template_id')
                ->constrained('company_service_pricing')
                ->nullOnDelete();

            // Original template price at time of creation (for comparison)
            $table->decimal('template_price', 10, 2)
                ->nullable()
                ->after('amount')
                ->comment('Original-Preis aus Template zum Zeitpunkt der Erstellung');

            // Discount applied
            $table->decimal('discount_amount', 10, 2)
                ->nullable()
                ->after('template_price')
                ->comment('Gewährter Rabatt in EUR');

            // Recurring fee tracking
            $table->boolean('is_recurring')
                ->default(false)
                ->after('status')
                ->comment('Wiederkehrende Gebühr?');

            $table->string('recurring_interval', 20)
                ->nullable()
                ->after('is_recurring')
                ->comment('monthly, quarterly, yearly');

            $table->date('recurring_until')
                ->nullable()
                ->after('recurring_interval')
                ->comment('Wiederkehrend bis (null = unbegrenzt)');
        });
    }

    public function down(): void
    {
        Schema::table('service_change_fees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('template_id');
            $table->dropConstrainedForeignId('company_pricing_id');
            $table->dropColumn([
                'template_price',
                'discount_amount',
                'is_recurring',
                'recurring_interval',
                'recurring_until',
            ]);
        });
    }
};
