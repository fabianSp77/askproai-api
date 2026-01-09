<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CRITICAL FIX: Prevents duplicate invoices for same partner/period.
     * 
     * Race Condition Scenario (before fix):
     * - Request A: Check existing = null
     * - Request B: Check existing = null (parallel)
     * - Request A: INSERT success
     * - Request B: INSERT success → DUPLICATE!
     * 
     * After fix: Database enforces uniqueness, second INSERT fails.
     */
    public function up(): void
    {
        Schema::table('aggregate_invoices', function (Blueprint $table) {
            // Unique constraint prevents duplicate invoices for same partner/period
            $table->unique(
                ['partner_company_id', 'billing_period_start'],
                'unique_partner_billing_period'
            );
            
            // Index for webhook lookups
            $table->index('stripe_invoice_id', 'idx_stripe_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('aggregate_invoices', function (Blueprint $table) {
            $table->dropUnique('unique_partner_billing_period');
            $table->dropIndex('idx_stripe_invoice_id');
        });
    }
};
