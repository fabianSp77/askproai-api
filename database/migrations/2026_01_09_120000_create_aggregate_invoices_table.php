<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Partner-Level Aggregate Invoices
     *
     * Enables monthly billing where a partner (e.g., IT Systemhaus) receives
     * ONE invoice covering ALL their managed companies, with detailed breakdown
     * per company.
     *
     * Flow: Partner → AggregateInvoice → AggregateInvoiceItems (per Company)
     */
    public function up(): void
    {
        Schema::create('aggregate_invoices', function (Blueprint $table) {
            $table->id();

            // Partner who receives this invoice
            $table->foreignId('partner_company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            // Stripe Integration
            $table->string('stripe_invoice_id')->nullable()->unique();
            $table->string('stripe_customer_id')->nullable();
            $table->string('stripe_hosted_invoice_url', 500)->nullable();
            $table->string('stripe_pdf_url', 500)->nullable();

            // Invoice Details
            $table->string('invoice_number')->unique();
            $table->date('billing_period_start');
            $table->date('billing_period_end');

            // Amounts (in cents)
            $table->unsignedBigInteger('subtotal_cents')->default(0);
            $table->unsignedBigInteger('tax_cents')->default(0);
            $table->unsignedBigInteger('total_cents')->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->decimal('tax_rate', 5, 2)->default(19.00); // German VAT

            // Status tracking
            $table->enum('status', [
                'draft',           // Being prepared
                'open',            // Sent to customer, awaiting payment
                'paid',            // Payment received
                'void',            // Cancelled
                'uncollectible',   // Payment failed, marked as bad debt
            ])->default('draft');

            // Timestamps
            $table->timestamp('finalized_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('due_at')->nullable();

            // Metadata
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['partner_company_id', 'status']);
            $table->index(['billing_period_start', 'billing_period_end']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aggregate_invoices');
    }
};
