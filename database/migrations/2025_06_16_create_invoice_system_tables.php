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
        // Rechnungen (synchronized with Stripe)
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('branch_id')->nullable()->constrained();
            $table->string('stripe_invoice_id')->unique()->nullable();
            $table->string('invoice_number')->unique();
            $table->string('status'); // draft, open, paid, void, uncollectible
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->date('invoice_date');
            $table->date('due_date');
            $table->date('paid_date')->nullable();
            $table->string('payment_method')->nullable(); // stripe, bank_transfer
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('pdf_url')->nullable();
            $table->json('metadata')->nullable(); // Store additional Stripe data
            $table->text('notes')->nullable();
            $table->string('billing_reason')->nullable(); // subscription_cycle, manual, subscription_update
            $table->boolean('auto_advance')->default(true); // Auto-finalize draft invoices
            $table->timestamps();
            
            $table->index(['company_id', 'status']);
            $table->index(['invoice_date']);
            $table->index(['due_date']);
        });

        // Rechnungspositionen
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->string('stripe_invoice_item_id')->nullable();
            $table->string('type'); // usage, service, setup_fee, adjustment
            $table->string('description');
            $table->decimal('quantity', 10, 2);
            $table->string('unit')->nullable(); // minutes, units, etc.
            $table->decimal('unit_price', 10, 4);
            $table->decimal('amount', 10, 2);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->foreignId('pricing_model_id')->nullable()->constrained('company_pricing');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->timestamps();
            
            $table->index(['invoice_id', 'type']);
        });

        // Zusätzliche Services Katalog
        Schema::create('additional_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained(); // null = platform-wide
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type'); // one_time, recurring
            $table->decimal('price', 10, 2);
            $table->string('unit')->default('unit'); // unit, hour, etc.
            $table->boolean('is_active')->default(true);
            $table->string('stripe_price_id')->nullable(); // For recurring services
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['company_id', 'is_active']);
        });

        // Gebuchte Services für Kunden
        Schema::create('customer_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('branch_id')->nullable()->constrained();
            $table->foreignId('service_id')->constrained('additional_services');
            $table->foreignId('invoice_id')->nullable()->constrained();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->date('service_date');
            $table->string('status')->default('pending'); // pending, invoiced, cancelled
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->index(['company_id', 'status']);
            $table->index(['invoice_id']);
            $table->index('service_date');
        });

        // Zahlungen
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained();
            $table->string('stripe_payment_id')->nullable();
            $table->string('payment_method'); // stripe, bank_transfer, cash
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->string('status'); // succeeded, pending, failed
            $table->date('payment_date');
            $table->string('reference_number')->nullable(); // For bank transfers
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['invoice_id']);
            $table->index(['payment_date']);
            $table->index(['status']);
        });

        // Preisanpassungen (Rabatte, Aufschläge)
        Schema::create('price_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('branch_id')->nullable()->constrained();
            $table->string('type'); // discount, surcharge
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2)->nullable(); // Fixed amount
            $table->decimal('percentage', 5, 2)->nullable(); // Percentage
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('applies_to')->default('all'); // all, usage, services
            $table->json('conditions')->nullable(); // e.g., min_usage, specific_services
            $table->timestamps();
            
            $table->index(['company_id', 'is_active']);
            $table->index(['valid_from', 'valid_until']);
        });

        // Track Setup-Fees
        Schema::create('setup_fee_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('pricing_model_id')->constrained('company_pricing');
            $table->foreignId('invoice_id')->constrained();
            $table->decimal('amount', 10, 2);
            $table->date('invoiced_date');
            $table->timestamps();
            
            $table->unique(['company_id', 'pricing_model_id']);
        });

        // Add invoice-related fields to existing tables
        Schema::table('billing_periods', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->after('is_invoiced')->constrained();
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->string('invoice_prefix')->nullable()->after('stripe_subscription_id');
            $table->integer('next_invoice_number')->default(1)->after('invoice_prefix');
            $table->string('payment_terms')->default('net30')->after('next_invoice_number'); // net15, net30, due_on_receipt
            $table->boolean('auto_invoice')->default(true)->after('payment_terms');
            $table->integer('invoice_day_of_month')->default(1)->after('auto_invoice'); // Which day to generate invoices
            $table->decimal('credit_limit', 10, 2)->nullable()->after('credit_balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billing_periods', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropColumn('invoice_id');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_prefix',
                'next_invoice_number',
                'payment_terms',
                'auto_invoice',
                'invoice_day_of_month',
                'credit_limit'
            ]);
        });

        Schema::dropIfExists('setup_fee_tracking');
        Schema::dropIfExists('price_adjustments');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('customer_services');
        Schema::dropIfExists('additional_services');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};