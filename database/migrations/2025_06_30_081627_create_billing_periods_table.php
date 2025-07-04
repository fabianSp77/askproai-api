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
        Schema::create('billing_periods', function (Blueprint $table) {
            $table->id();
            $table->uuid('company_id');
            $table->uuid('branch_id')->nullable();
            $table->uuid('subscription_id')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 50)->default('pending');
            $table->decimal('total_minutes', 10, 2)->default(0);
            $table->decimal('used_minutes', 10, 2)->default(0);
            $table->integer('included_minutes')->default(0);
            $table->integer('overage_minutes')->default(0);
            $table->decimal('price_per_minute', 10, 4)->default(0);
            $table->decimal('base_fee', 10, 2)->default(0);
            $table->decimal('overage_cost', 10, 2)->default(0);
            $table->decimal('total_cost', 10, 2)->default(0);
            $table->decimal('total_revenue', 10, 2)->default(0);
            $table->decimal('margin', 10, 2)->default(0);
            $table->decimal('margin_percentage', 10, 2)->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->boolean('is_prorated')->default(false);
            $table->decimal('proration_factor', 5, 4)->nullable();
            $table->boolean('is_invoiced')->default(false);
            $table->timestamp('invoiced_at')->nullable();
            $table->string('stripe_invoice_id')->nullable();
            $table->timestamp('stripe_invoice_created_at')->nullable();
            $table->uuid('invoice_id')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['company_id', 'start_date']);
            $table->index(['subscription_id']);
            $table->index(['status']);
            $table->index(['stripe_invoice_id']);
            $table->index('company_id');
            $table->index('branch_id');
            $table->index('is_invoiced');
            $table->index(['start_date', 'end_date']);
            
            // Foreign keys - commented out due to different ID types in existing tables
            // $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            // $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            // $table->foreign('subscription_id')->references('id')->on('subscriptions')->onDelete('set null');
            // $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_periods');
    }
};
