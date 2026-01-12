<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add Missing Billing Indexes
     *
     * This migration adds performance indexes for billing queries that were identified
     * during the Billing System QC review.
     *
     * Indexes added:
     * 1. service_cases(company_id, billing_status, created_at) - Already exists via 2026_01_12_122503
     * 2. aggregate_invoice_items(aggregate_invoice_id, company_id) - Already exists via 2026_01_09_120001
     * 3. aggregate_invoices(partner_company_id, status, billing_period_start) - NEW (this migration)
     *
     * The composite index on aggregate_invoices optimizes the common query pattern:
     * WHERE partner_company_id = ? AND status = ? AND billing_period_start >= ?
     */
    public function up(): void
    {
        Schema::table('aggregate_invoices', function (Blueprint $table) {
            // Add composite index for partner billing queries
            // Optimizes: SELECT * FROM aggregate_invoices WHERE partner_company_id = ? AND status = ? ORDER BY billing_period_start
            $table->index(
                ['partner_company_id', 'status', 'billing_period_start'],
                'idx_aggregate_invoices_partner_billing'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aggregate_invoices', function (Blueprint $table) {
            $table->dropIndex('idx_aggregate_invoices_partner_billing');
        });
    }
};
