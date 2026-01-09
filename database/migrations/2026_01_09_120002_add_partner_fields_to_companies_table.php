<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add partner billing fields to companies table
     *
     * Partners (like IT Systemhaus) are companies that manage other companies
     * and receive aggregated monthly invoices for all their clients.
     *
     * Key fields:
     * - is_partner: Marks a company as a billing partner
     * - managed_by_company_id: Links client companies to their partner
     * - partner_stripe_customer_id: Stripe customer for partner-level billing
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Partner identification
            $table->boolean('is_partner')->default(false)->after('is_active');

            // Which partner manages this company (for client companies)
            $table->foreignId('managed_by_company_id')
                ->nullable()
                ->after('is_partner')
                ->constrained('companies')
                ->nullOnDelete();

            // Partner-level Stripe customer ID (separate from company stripe_customer_id)
            // Used for aggregate invoicing when this company is a partner
            $table->string('partner_stripe_customer_id')->nullable()->after('managed_by_company_id');

            // Partner billing contact info
            $table->string('partner_billing_email')->nullable()->after('partner_stripe_customer_id');
            $table->string('partner_billing_name')->nullable()->after('partner_billing_email');
            $table->json('partner_billing_address')->nullable()->after('partner_billing_name');

            // Partner billing preferences
            $table->unsignedTinyInteger('partner_payment_terms_days')->default(14)->after('partner_billing_address');
            $table->enum('partner_invoice_delivery', ['email', 'manual', 'both'])->default('email')->after('partner_payment_terms_days');

            // Indexes
            $table->index('is_partner');
            $table->index('managed_by_company_id');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['managed_by_company_id']);
            $table->dropIndex(['is_partner']);
            $table->dropIndex(['managed_by_company_id']);

            $table->dropColumn([
                'is_partner',
                'managed_by_company_id',
                'partner_stripe_customer_id',
                'partner_billing_email',
                'partner_billing_name',
                'partner_billing_address',
                'partner_payment_terms_days',
                'partner_invoice_delivery',
            ]);
        });
    }
};
