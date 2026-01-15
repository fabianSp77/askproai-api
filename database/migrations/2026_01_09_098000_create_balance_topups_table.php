<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Billing System: Balance Topups Table
     *
     * Tracks credit purchases for company accounts:
     * - Stripe payment integration
     * - Bonus percentage/amount tracking
     * - Refund management
     * - Usage tracking (used_amount, remaining_amount)
     *
     * Note: This migration was added retroactively (2026-01-15) to fix CI/CD.
     * The table already exists in production.
     */
    public function up(): void
    {
        if (Schema::hasTable('balance_topups')) {
            return; // Skip if table already exists (production)
        }

        Schema::create('balance_topups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');

            // Payment amounts
            $table->decimal('amount', 10, 2); // Amount paid
            $table->decimal('bonus_percentage', 5, 2)->default(0);
            $table->decimal('bonus_amount', 10, 2)->default(0);
            $table->decimal('total_credited', 10, 2); // amount + bonus
            $table->decimal('refundable_amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('EUR');

            // Payment status
            $table->string('status', 50)->default('pending');

            // Stripe integration
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_checkout_session_id')->nullable();
            $table->json('stripe_response')->nullable();

            // Metadata
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('initiated_by')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Invoice tracking
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('stripe_invoice_id')->nullable();

            $table->timestamps();

            // Refund tracking
            $table->decimal('refunded_amount', 10, 2)->default(0);
            $table->timestamp('refunded_at')->nullable();
            $table->text('refund_reason')->nullable();
            $table->string('refund_status', 50)->nullable();

            // Usage tracking
            $table->decimal('used_amount', 10, 2)->default(0);
            $table->decimal('remaining_amount', 10, 2)->default(0);
            $table->decimal('bonus_used', 10, 2)->default(0);
            $table->decimal('bonus_remaining', 10, 2)->default(0);

            // Performance indexes
            $table->index('company_id');
            $table->index('status');
            $table->index('stripe_payment_intent_id');
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('balance_topups');
    }
};
