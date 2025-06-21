<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->createTableIfNotExists('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->uuid('branch_id')->nullable();
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->string('stripe_invoice_id')->nullable()->unique();
            $table->string('invoice_number')->unique();
            $table->enum('status', ['draft', 'open', 'paid', 'void', 'uncollectible'])->default('draft');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->date('paid_date')->nullable();
            $table->string('payment_method')->nullable();
            $table->integer('payment_terms')->default(30);
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('pdf_url')->nullable();
            $this->addJsonColumn($table, 'metadata', true);
            $table->text('notes')->nullable();
            $table->string('billing_reason')->nullable();
            $table->boolean('auto_advance')->default(false);
            
            // Tax compliance fields
            $this->addJsonColumn($table, 'tax_configuration', true);
            $table->boolean('is_reverse_charge')->default(false);
            $table->string('customer_vat_id')->nullable();
            $table->enum('invoice_type', ['invoice', 'credit_note', 'debit_note'])->default('invoice');
            $table->foreignId('original_invoice_id')->nullable()->constrained('invoices');
            $table->timestamp('finalized_at')->nullable();
            $table->text('tax_note')->nullable();
            $table->boolean('is_tax_exempt')->default(false);
            
            // Period fields
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'invoice_date']);
            $table->index('status');
            $table->index('invoice_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropTableIfExists('invoices');
    }
};