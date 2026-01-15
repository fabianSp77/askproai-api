<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Billing System: Transactions Table
     *
     * Tracks all financial transactions for tenant accounts:
     * - Credit transactions (topups, refunds, bonuses)
     * - Debit transactions (usage, fees, adjustments)
     * - Balance tracking before/after each transaction
     *
     * Note: This migration was added retroactively (2026-01-15) to fix CI/CD.
     * The table already exists in production.
     */
    public function up(): void
    {
        if (Schema::hasTable('transactions')) {
            return; // Skip if table already exists (production)
        }

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

            // Transaction type: topup, usage, refund, adjustment, bonus, fee
            $table->string('type', 50);

            // Amount in cents (positive for credits, negative for debits)
            $table->integer('amount_cents');

            // Balance tracking
            $table->integer('balance_before_cents')->default(0);
            $table->integer('balance_after_cents')->default(0);

            // Description for audit trail
            $table->text('description')->nullable();

            // Related entities (nullable foreign keys)
            $table->foreignId('topup_id')->nullable()->constrained('balance_topups')->nullOnDelete();
            $table->foreignId('call_id')->nullable()->constrained('calls')->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();

            // Note: service_change_fee_id and fee_schedule_id are added by
            // migration 2026_01_09_100002_add_billing_fields_to_transactions_table.php

            // Flexible metadata storage
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Performance indexes
            $table->index('tenant_id');
            $table->index('type');
            $table->index('created_at');
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
