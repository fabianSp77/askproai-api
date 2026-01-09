<?php

namespace App\Services\Billing;

use App\Models\AggregateInvoice;
use App\Models\AggregateInvoiceItem;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

/**
 * Stripe Invoicing Service
 *
 * Handles creation and management of Stripe invoices for partner billing.
 * Partners receive aggregated monthly invoices covering all their managed companies.
 *
 * Flow:
 * 1. Create local AggregateInvoice record
 * 2. Create Stripe Invoice (draft)
 * 3. Add line items for each company's charges
 * 4. Finalize and send invoice
 * 5. Handle webhook events for payment status
 */
class StripeInvoicingService
{
    private ?StripeClient $stripe = null;

    /**
     * Get Stripe client instance (lazy initialization).
     *
     * @throws \RuntimeException if STRIPE_SECRET is not configured
     */
    private function getStripe(): StripeClient
    {
        if ($this->stripe === null) {
            $secret = config('services.stripe.secret');

            if (empty($secret)) {
                throw new \RuntimeException(
                    'STRIPE_SECRET is not configured. Please set it in .env file.'
                );
            }

            $this->stripe = new StripeClient($secret);
        }

        return $this->stripe;
    }

    /**
     * Check if Stripe is properly configured.
     */
    public function isConfigured(): bool
    {
        return !empty(config('services.stripe.secret'));
    }

    /**
     * Get safe billing email - prevents accidental external emails in non-production.
     *
     * In non-production environments, this will:
     * 1. Use BILLING_TEST_EMAIL override if set
     * 2. Only allow @askproai.de emails
     * 3. Fall back to configured admin email
     */
    public function getSafeBillingEmail(Company $partner): string
    {
        $email = $partner->getPartnerBillingEmail();

        // In production, use the actual email
        if (app()->environment('production')) {
            return $email;
        }

        // Check for test email override
        $testEmail = config('services.stripe.test_billing_email');
        if ($testEmail) {
            Log::info("Using test billing email override", [
                'original' => $email,
                'override' => $testEmail,
            ]);
            return $testEmail;
        }

        // Only allow internal emails in non-production
        $allowedDomains = ['askproai.de', 'askpro.ai'];
        $domain = substr(strrchr($email, '@'), 1);

        if (!in_array($domain, $allowedDomains)) {
            $fallbackEmail = config('mail.admin_email', 'fabian@askproai.de');
            Log::warning("Blocked external email in non-production", [
                'partner_id' => $partner->id,
                'blocked_email' => $email,
                'using_fallback' => $fallbackEmail,
            ]);
            return $fallbackEmail;
        }

        return $email;
    }

    /**
     * Get or create a Stripe customer for partner billing.
     */
    public function getOrCreatePartnerStripeCustomer(Company $partner): string
    {
        // Return existing customer ID if available
        if ($partner->partner_stripe_customer_id) {
            // Verify it still exists in Stripe
            try {
                $this->getStripe()->customers->retrieve($partner->partner_stripe_customer_id);
                return $partner->partner_stripe_customer_id;
            } catch (ApiErrorException $e) {
                Log::warning("Partner Stripe customer not found, creating new: {$partner->id}");
            }
        }

        // Create new Stripe customer (using safe email to prevent external sends)
        $safeEmail = $this->getSafeBillingEmail($partner);
        $customer = $this->getStripe()->customers->create([
            'name' => $partner->name,
            'email' => $safeEmail,
            'metadata' => [
                'partner_id' => $partner->id,
                'partner_name' => $partner->name,
                'type' => 'partner_billing',
            ],
            'address' => $this->formatAddress($partner),
            'preferred_locales' => ['de'],
        ]);

        // Save to partner record
        $partner->update(['partner_stripe_customer_id' => $customer->id]);

        Log::info("Created Stripe customer for partner", [
            'partner_id' => $partner->id,
            'stripe_customer_id' => $customer->id,
        ]);

        return $customer->id;
    }

    /**
     * Create a monthly invoice for a partner.
     */
    public function createMonthlyInvoice(
        Company $partner,
        Carbon $periodStart,
        Carbon $periodEnd
    ): AggregateInvoice {
        // Ensure partner has Stripe customer
        $stripeCustomerId = $this->getOrCreatePartnerStripeCustomer($partner);

        // Check for existing invoice for this period
        $existing = AggregateInvoice::where('partner_company_id', $partner->id)
            ->forPeriod($periodStart, $periodEnd)
            ->whereNotIn('status', [AggregateInvoice::STATUS_VOID])
            ->first();

        if ($existing) {
            throw new \Exception("Invoice already exists for period {$periodStart->format('Y-m')}");
        }

        // Create local invoice record
        $invoice = AggregateInvoice::create([
            'partner_company_id' => $partner->id,
            'stripe_customer_id' => $stripeCustomerId,
            'invoice_number' => AggregateInvoice::generateInvoiceNumber(),
            'billing_period_start' => $periodStart,
            'billing_period_end' => $periodEnd,
            'currency' => 'EUR',
            'tax_rate' => 19.00, // German VAT
            'status' => AggregateInvoice::STATUS_DRAFT,
            'metadata' => [
                'partner_name' => $partner->name,
                'created_by' => 'system',
            ],
        ]);

        Log::info("Created aggregate invoice", [
            'invoice_id' => $invoice->id,
            'partner_id' => $partner->id,
            'period' => $periodStart->format('Y-m'),
        ]);

        return $invoice;
    }

    /**
     * Add a line item to the invoice.
     */
    public function addLineItem(
        AggregateInvoice $invoice,
        AggregateInvoiceItem $item
    ): void {
        if ($invoice->status !== AggregateInvoice::STATUS_DRAFT) {
            throw new \Exception("Cannot add items to non-draft invoice");
        }

        // No Stripe sync needed for draft invoices
        // Items are synced when invoice is finalized
    }

    /**
     * Sync invoice to Stripe and finalize it.
     */
    public function finalizeAndSend(AggregateInvoice $invoice): AggregateInvoice
    {
        if ($invoice->status !== AggregateInvoice::STATUS_DRAFT) {
            throw new \Exception("Can only finalize draft invoices");
        }

        // Recalculate totals
        $invoice->calculateTotals();

        if ($invoice->total_cents === 0) {
            Log::info("Skipping invoice with zero total", ['invoice_id' => $invoice->id]);
            $invoice->void();
            return $invoice;
        }

        try {
            // Create Stripe invoice
            $stripeInvoice = $this->getStripe()->invoices->create([
                'customer' => $invoice->stripe_customer_id,
                'collection_method' => 'send_invoice',
                'days_until_due' => $invoice->partnerCompany->getPartnerPaymentTermsDays(),
                'auto_advance' => false,
                'metadata' => [
                    'aggregate_invoice_id' => $invoice->id,
                    'partner_id' => $invoice->partner_company_id,
                    'billing_period' => $invoice->billing_period_start->format('Y-m'),
                ],
            ]);

            // Add line items to Stripe
            $this->syncLineItemsToStripe($invoice, $stripeInvoice->id);

            // Finalize the Stripe invoice
            $finalizedInvoice = $this->getStripe()->invoices->finalizeInvoice($stripeInvoice->id);

            // Send the invoice
            $sentInvoice = $this->getStripe()->invoices->sendInvoice($stripeInvoice->id);

            // Update local record
            $invoice->update([
                'stripe_invoice_id' => $sentInvoice->id,
                'stripe_hosted_invoice_url' => $sentInvoice->hosted_invoice_url,
                'stripe_pdf_url' => $sentInvoice->invoice_pdf,
                'status' => AggregateInvoice::STATUS_OPEN,
                'finalized_at' => now(),
                'sent_at' => now(),
                'due_at' => Carbon::createFromTimestamp($sentInvoice->due_date),
            ]);

            Log::info("Invoice finalized and sent", [
                'invoice_id' => $invoice->id,
                'stripe_invoice_id' => $sentInvoice->id,
                'total' => $invoice->total,
            ]);

            return $invoice->fresh();

        } catch (ApiErrorException $e) {
            Log::error("Failed to create Stripe invoice", [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Create invoice in Stripe but don't send (for preview/manual approval).
     */
    public function createStripeInvoiceDraft(AggregateInvoice $invoice): AggregateInvoice
    {
        if ($invoice->stripe_invoice_id) {
            throw new \Exception("Invoice already has Stripe invoice");
        }

        // Recalculate totals
        $invoice->calculateTotals();

        if ($invoice->total_cents === 0) {
            throw new \Exception("Cannot create invoice with zero total");
        }

        try {
            // Create Stripe invoice (draft)
            $stripeInvoice = $this->getStripe()->invoices->create([
                'customer' => $invoice->stripe_customer_id,
                'collection_method' => 'send_invoice',
                'days_until_due' => $invoice->partnerCompany->getPartnerPaymentTermsDays(),
                'auto_advance' => false,
                'metadata' => [
                    'aggregate_invoice_id' => $invoice->id,
                    'partner_id' => $invoice->partner_company_id,
                    'billing_period' => $invoice->billing_period_start->format('Y-m'),
                ],
            ]);

            // Add line items
            $this->syncLineItemsToStripe($invoice, $stripeInvoice->id);

            // Update local record
            $invoice->update([
                'stripe_invoice_id' => $stripeInvoice->id,
            ]);

            return $invoice->fresh();

        } catch (ApiErrorException $e) {
            Log::error("Failed to create Stripe invoice draft", [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Sync line items to Stripe invoice.
     */
    private function syncLineItemsToStripe(AggregateInvoice $invoice, string $stripeInvoiceId): void
    {
        $itemsByCompany = $invoice->getItemsByCompany();

        foreach ($itemsByCompany as $companyId => $data) {
            $company = $data['company'];

            // Create a section header for each company
            $this->getStripe()->invoiceItems->create([
                'customer' => $invoice->stripe_customer_id,
                'invoice' => $stripeInvoiceId,
                'description' => "── {$company->name} ──",
                'amount' => 0,
                'currency' => 'eur',
            ]);

            // Add each item
            foreach ($data['items'] as $item) {
                $description = $item->description;
                if ($item->description_detail) {
                    $description .= " ({$item->description_detail})";
                }

                $stripeItem = $this->getStripe()->invoiceItems->create([
                    'customer' => $invoice->stripe_customer_id,
                    'invoice' => $stripeInvoiceId,
                    'description' => $description,
                    'amount' => $item->amount_cents,
                    'currency' => 'eur',
                    'metadata' => [
                        'company_id' => $companyId,
                        'company_name' => $company->name,
                        'item_type' => $item->item_type,
                        'aggregate_invoice_item_id' => $item->id,
                    ],
                ]);

                // Store Stripe line item ID
                $item->update(['stripe_line_item_id' => $stripeItem->id]);
            }
        }

        // Add tax as a separate line item (Stripe can also handle this automatically)
        if ($invoice->tax_cents > 0) {
            $this->getStripe()->invoiceItems->create([
                'customer' => $invoice->stripe_customer_id,
                'invoice' => $stripeInvoiceId,
                'description' => sprintf('MwSt. (%.0f%%)', $invoice->tax_rate),
                'amount' => $invoice->tax_cents,
                'currency' => 'eur',
            ]);
        }
    }

    /**
     * Handle invoice.paid webhook event.
     *
     * Idempotent: Safe to call multiple times (Stripe may retry webhooks).
     */
    public function handleInvoicePaid(array $event): void
    {
        $stripeInvoice = $event['data']['object'];
        $stripeInvoiceId = $stripeInvoice['id'];
        $stripeEventId = $event['id'] ?? null;

        $invoice = AggregateInvoice::where('stripe_invoice_id', $stripeInvoiceId)->first();

        if (!$invoice) {
            Log::warning("Received invoice.paid for unknown invoice", [
                'stripe_invoice_id' => $stripeInvoiceId,
                'stripe_event_id' => $stripeEventId,
            ]);
            return;
        }

        // IDEMPOTENCY CHECK: Skip if already paid
        if ($invoice->status === AggregateInvoice::STATUS_PAID) {
            Log::info("Invoice already paid, skipping duplicate webhook", [
                'invoice_id' => $invoice->id,
                'stripe_invoice_id' => $stripeInvoiceId,
                'stripe_event_id' => $stripeEventId,
                'paid_at' => $invoice->paid_at?->toIso8601String(),
            ]);
            return;
        }

        $invoice->markAsPaid();

        Log::info("Invoice marked as paid via webhook", [
            'invoice_id' => $invoice->id,
            'stripe_invoice_id' => $stripeInvoiceId,
            'stripe_event_id' => $stripeEventId,
        ]);
    }

    /**
     * Handle invoice.payment_failed webhook event.
     */
    public function handleInvoicePaymentFailed(array $event): void
    {
        $stripeInvoice = $event['data']['object'];
        $stripeInvoiceId = $stripeInvoice['id'];

        $invoice = AggregateInvoice::where('stripe_invoice_id', $stripeInvoiceId)->first();

        if (!$invoice) {
            Log::warning("Received invoice.payment_failed for unknown invoice", [
                'stripe_invoice_id' => $stripeInvoiceId,
            ]);
            return;
        }

        // Update metadata with failure info
        $invoice->update([
            'metadata' => array_merge($invoice->metadata ?? [], [
                'last_payment_failure' => now()->toIso8601String(),
                'failure_message' => $stripeInvoice['last_finalization_error']['message'] ?? 'Unknown',
            ]),
        ]);

        Log::warning("Invoice payment failed", [
            'invoice_id' => $invoice->id,
            'stripe_invoice_id' => $stripeInvoiceId,
        ]);

        // TODO: Send notification to admin
    }

    /**
     * Handle invoice.finalized webhook event.
     */
    public function handleInvoiceFinalized(array $event): void
    {
        $stripeInvoice = $event['data']['object'];
        $stripeInvoiceId = $stripeInvoice['id'];

        $invoice = AggregateInvoice::where('stripe_invoice_id', $stripeInvoiceId)->first();

        if (!$invoice) {
            return;
        }

        $invoice->update([
            'stripe_hosted_invoice_url' => $stripeInvoice['hosted_invoice_url'] ?? null,
            'stripe_pdf_url' => $stripeInvoice['invoice_pdf'] ?? null,
        ]);
    }

    /**
     * Handle invoice.voided webhook event.
     */
    public function handleInvoiceVoided(array $event): void
    {
        $stripeInvoice = $event['data']['object'];
        $stripeInvoiceId = $stripeInvoice['id'];

        $invoice = AggregateInvoice::where('stripe_invoice_id', $stripeInvoiceId)->first();

        if ($invoice) {
            $invoice->void();
        }
    }

    /**
     * Void a Stripe invoice.
     */
    public function voidStripeInvoice(AggregateInvoice $invoice): void
    {
        if (!$invoice->stripe_invoice_id) {
            $invoice->void();
            return;
        }

        try {
            $this->getStripe()->invoices->voidInvoice($invoice->stripe_invoice_id);
            $invoice->void();
        } catch (ApiErrorException $e) {
            Log::error("Failed to void Stripe invoice", [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get Stripe invoice preview URL.
     */
    public function getPreviewUrl(AggregateInvoice $invoice): ?string
    {
        return $invoice->stripe_hosted_invoice_url;
    }

    /**
     * Get Stripe invoice PDF URL.
     */
    public function getPdfUrl(AggregateInvoice $invoice): ?string
    {
        return $invoice->stripe_pdf_url;
    }

    /**
     * Format address for Stripe.
     */
    private function formatAddress(Company $partner): ?array
    {
        $address = $partner->partner_billing_address;

        if (!$address) {
            return null;
        }

        return [
            'line1' => $address['street'] ?? null,
            'line2' => $address['line2'] ?? null,
            'city' => $address['city'] ?? null,
            'postal_code' => $address['postal_code'] ?? null,
            'country' => $address['country'] ?? 'DE',
        ];
    }
}
