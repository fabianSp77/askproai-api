<?php

namespace App\Services\Billing;

use App\Models\AggregateInvoice;
use App\Models\AggregateInvoiceItem;
use App\Models\Company;
use App\Models\StripeEvent;
use App\Notifications\InvoicePaymentFailedNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

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
 *
 * Webhook Idempotency:
 * - All webhook handlers track event IDs in stripe_events table
 * - Duplicate events are logged and skipped gracefully
 * - Prevents double-processing when Stripe retries webhooks
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
     * Execute a Stripe API call with retry logic and exponential backoff.
     *
     * Handles:
     * - Rate limiting (HTTP 429)
     * - Transient connection errors
     * - Temporary API unavailability
     *
     * @param  callable  $callback  The Stripe API call to execute
     * @param  string  $operation  Description of the operation (for logging)
     * @param  int  $maxAttempts  Maximum number of attempts (default: 3)
     * @return mixed Result of the callback
     *
     * @throws ApiErrorException If all attempts fail
     */
    private function retryStripeCall(callable $callback, string $operation, int $maxAttempts = 3): mixed
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $maxAttempts) {
            $attempt++;

            try {
                return $callback();
            } catch (ApiErrorException $e) {
                $lastException = $e;
                $httpStatus = $e->getHttpStatus();

                // Determine if retry is appropriate
                $isRetryable = in_array($httpStatus, [429, 500, 502, 503, 504])
                    || $e instanceof \Stripe\Exception\RateLimitException
                    || $e instanceof \Stripe\Exception\ApiConnectionException;

                if (! $isRetryable || $attempt >= $maxAttempts) {
                    Log::error('Stripe API call failed (non-retryable or max attempts reached)', [
                        'operation' => $operation,
                        'attempt' => $attempt,
                        'http_status' => $httpStatus,
                        'error' => $e->getMessage(),
                        'stripe_code' => $e->getStripeCode(),
                    ]);
                    throw $e;
                }

                // Calculate exponential backoff: 1s, 2s, 4s, 8s...
                $delay = (int) pow(2, $attempt - 1);

                // Check for Retry-After header (rate limiting)
                $retryAfter = $e->getHttpHeaders()['retry-after'] ?? null;
                if ($retryAfter !== null) {
                    $delay = max($delay, (int) $retryAfter);
                }

                Log::warning('Stripe API call failed, retrying', [
                    'operation' => $operation,
                    'attempt' => $attempt,
                    'max_attempts' => $maxAttempts,
                    'http_status' => $httpStatus,
                    'error' => $e->getMessage(),
                    'delay_seconds' => $delay,
                ]);

                sleep($delay);
            }
        }

        // Should not reach here, but for safety
        throw $lastException ?? new \RuntimeException("Stripe API call failed after {$maxAttempts} attempts");
    }

    /**
     * Check if Stripe is properly configured.
     */
    public function isConfigured(): bool
    {
        return ! empty(config('services.stripe.secret'));
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
            Log::info('Using test billing email override', [
                'original' => $email,
                'override' => $testEmail,
            ]);

            return $testEmail;
        }

        // Only allow internal emails in non-production
        $allowedDomains = ['askproai.de', 'askpro.ai'];
        $domain = substr(strrchr($email, '@'), 1);

        if (! in_array($domain, $allowedDomains)) {
            $fallbackEmail = config('mail.admin_email', 'fabian@askproai.de');
            Log::warning('Blocked external email in non-production', [
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
                $this->retryStripeCall(
                    fn () => $this->getStripe()->customers->retrieve($partner->partner_stripe_customer_id),
                    "retrieve_customer:{$partner->id}"
                );

                return $partner->partner_stripe_customer_id;
            } catch (ApiErrorException $e) {
                Log::warning("Partner Stripe customer not found, creating new: {$partner->id}");
            }
        }

        // Create new Stripe customer (using safe email to prevent external sends)
        $safeEmail = $this->getSafeBillingEmail($partner);
        $customer = $this->retryStripeCall(
            fn () => $this->getStripe()->customers->create([
                'name' => $partner->name,
                'email' => $safeEmail,
                'metadata' => [
                    'partner_id' => $partner->id,
                    'partner_name' => $partner->name,
                    'type' => 'partner_billing',
                ],
                'address' => $this->formatAddress($partner),
                'preferred_locales' => ['de'],
            ]),
            "create_customer:{$partner->id}"
        );

        // Save to partner record
        $partner->update(['partner_stripe_customer_id' => $customer->id]);

        Log::info('Created Stripe customer for partner', [
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

        Log::info('Created aggregate invoice', [
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
            throw new \Exception('Cannot add items to non-draft invoice');
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
            throw new \Exception('Can only finalize draft invoices');
        }

        // Recalculate totals
        $invoice->calculateTotals();

        if ($invoice->total_cents === 0) {
            Log::info('Skipping invoice with zero total', ['invoice_id' => $invoice->id]);
            $invoice->void();

            return $invoice;
        }

        try {
            // Create Stripe invoice (with retry)
            $stripeInvoice = $this->retryStripeCall(
                fn () => $this->getStripe()->invoices->create([
                    'customer' => $invoice->stripe_customer_id,
                    'collection_method' => 'send_invoice',
                    'days_until_due' => $invoice->partnerCompany->getPartnerPaymentTermsDays(),
                    'auto_advance' => false,
                    'metadata' => [
                        'aggregate_invoice_id' => $invoice->id,
                        'partner_id' => $invoice->partner_company_id,
                        'billing_period' => $invoice->billing_period_start->format('Y-m'),
                    ],
                ]),
                "create_invoice:{$invoice->id}"
            );

            // Add line items to Stripe
            $this->syncLineItemsToStripe($invoice, $stripeInvoice->id);

            // Finalize the Stripe invoice (with retry)
            $finalizedInvoice = $this->retryStripeCall(
                fn () => $this->getStripe()->invoices->finalizeInvoice($stripeInvoice->id),
                "finalize_invoice:{$invoice->id}"
            );

            // Send the invoice (with retry)
            $sentInvoice = $this->retryStripeCall(
                fn () => $this->getStripe()->invoices->sendInvoice($stripeInvoice->id),
                "send_invoice:{$invoice->id}"
            );

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

            Log::info('Invoice finalized and sent', [
                'invoice_id' => $invoice->id,
                'stripe_invoice_id' => $sentInvoice->id,
                'total' => $invoice->total,
            ]);

            return $invoice->fresh();

        } catch (ApiErrorException $e) {
            Log::error('Failed to create Stripe invoice', [
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
            throw new \Exception('Invoice already has Stripe invoice');
        }

        // Recalculate totals
        $invoice->calculateTotals();

        if ($invoice->total_cents === 0) {
            throw new \Exception('Cannot create invoice with zero total');
        }

        try {
            // Create Stripe invoice (draft, with retry)
            $stripeInvoice = $this->retryStripeCall(
                fn () => $this->getStripe()->invoices->create([
                    'customer' => $invoice->stripe_customer_id,
                    'collection_method' => 'send_invoice',
                    'days_until_due' => $invoice->partnerCompany->getPartnerPaymentTermsDays(),
                    'auto_advance' => false,
                    'metadata' => [
                        'aggregate_invoice_id' => $invoice->id,
                        'partner_id' => $invoice->partner_company_id,
                        'billing_period' => $invoice->billing_period_start->format('Y-m'),
                    ],
                ]),
                "create_invoice_draft:{$invoice->id}"
            );

            // Add line items
            $this->syncLineItemsToStripe($invoice, $stripeInvoice->id);

            // Update local record
            $invoice->update([
                'stripe_invoice_id' => $stripeInvoice->id,
            ]);

            return $invoice->fresh();

        } catch (ApiErrorException $e) {
            Log::error('Failed to create Stripe invoice draft', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Sync line items to Stripe invoice.
     *
     * Format: "Firmenname: Beschreibung (Details)"
     * Example: "IT-Systemhaus Test GmbH: Call-Minuten (32 Anrufe, 55.30 Min)"
     *
     * Note: Previously used 0€ header lines for company grouping, but this was
     * confusing on the invoice. Now the company name is included in each line item.
     */
    private function syncLineItemsToStripe(AggregateInvoice $invoice, string $stripeInvoiceId): void
    {
        $itemsByCompany = $invoice->getItemsByCompany();

        foreach ($itemsByCompany as $companyId => $data) {
            $company = $data['company'];

            // Add each item with company name prefix
            foreach ($data['items'] as $item) {
                // Build description: "Firmenname: Beschreibung (Details)"
                $description = "{$company->name}: {$item->description}";
                if ($item->description_detail) {
                    $description .= " ({$item->description_detail})";
                }

                $stripeItem = $this->retryStripeCall(
                    fn () => $this->getStripe()->invoiceItems->create([
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
                    ]),
                    "add_line_item:{$invoice->id}:{$item->id}"
                );

                // Store Stripe line item ID
                $item->update(['stripe_line_item_id' => $stripeItem->id]);
            }
        }

        // Add tax as a separate line item (with retry)
        if ($invoice->tax_cents > 0) {
            $this->retryStripeCall(
                fn () => $this->getStripe()->invoiceItems->create([
                    'customer' => $invoice->stripe_customer_id,
                    'invoice' => $stripeInvoiceId,
                    'description' => sprintf('MwSt. (%.0f%%)', $invoice->tax_rate),
                    'amount' => $invoice->tax_cents,
                    'currency' => 'eur',
                ]),
                "add_tax_line_item:{$invoice->id}"
            );
        }
    }

    /**
     * Handle invoice.paid webhook event.
     *
     * Idempotent: Safe to call multiple times (Stripe may retry webhooks).
     */
    public function handleInvoicePaid(array $event): void
    {
        $resolved = $this->resolveInvoiceForWebhook($event, 'invoice.paid');

        if (! $resolved['shouldProcess']) {
            return;
        }

        $invoice = $resolved['invoice'];

        // IDEMPOTENCY CHECK: Skip if already paid
        if ($invoice->status === AggregateInvoice::STATUS_PAID) {
            Log::info('Invoice already paid, skipping duplicate webhook', [
                'invoice_id' => $invoice->id,
                'stripe_invoice_id' => $resolved['stripeInvoice']['id'],
                'paid_at' => $invoice->paid_at?->toIso8601String(),
            ]);

            $this->markEventProcessed($resolved['stripeEventId'], 'invoice.paid');

            return;
        }

        $invoice->markAsPaid();

        $this->markEventProcessed($resolved['stripeEventId'], 'invoice.paid');

        Log::info('Invoice marked as paid via webhook', [
            'invoice_id' => $invoice->id,
            'stripe_invoice_id' => $resolved['stripeInvoice']['id'],
        ]);
    }

    /**
     * Handle invoice.payment_failed webhook event.
     *
     * Idempotent: Safe to call multiple times (Stripe may retry webhooks).
     */
    public function handleInvoicePaymentFailed(array $event): void
    {
        $resolved = $this->resolveInvoiceForWebhook($event, 'invoice.payment_failed');

        if (! $resolved['shouldProcess']) {
            return;
        }

        $invoice = $resolved['invoice'];
        $stripeInvoice = $resolved['stripeInvoice'];

        // Update metadata with failure info
        $invoice->update([
            'metadata' => array_merge($invoice->metadata ?? [], [
                'last_payment_failure' => now()->toIso8601String(),
                'failure_message' => $stripeInvoice['last_finalization_error']['message'] ?? 'Unknown',
            ]),
        ]);

        Log::warning('Invoice payment failed', [
            'invoice_id' => $invoice->id,
            'stripe_invoice_id' => $stripeInvoice['id'],
        ]);

        // Send notification to admin
        $this->notifyAdminOfPaymentFailure($invoice, $stripeInvoice, $resolved['stripeEventId']);

        $this->markEventProcessed($resolved['stripeEventId'], 'invoice.payment_failed');
    }

    /**
     * Notify admin(s) of invoice payment failure.
     */
    private function notifyAdminOfPaymentFailure(
        AggregateInvoice $invoice,
        array $stripeInvoice,
        ?string $stripeEventId
    ): void {
        $failureMessage = $stripeInvoice['last_finalization_error']['message']
            ?? $stripeInvoice['status_transitions']['paid_at'] === null
                ? 'Payment not completed'
                : 'Unknown payment failure';

        // Get admin email(s) - can be comma-separated for multiple recipients
        $adminEmails = config('mail.admin_email', 'fabian@askproai.de');
        $recipients = array_map('trim', explode(',', $adminEmails));

        $sentCount = 0;
        $failedCount = 0;

        foreach ($recipients as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                try {
                    Notification::route('mail', $email)
                        ->notify(new InvoicePaymentFailedNotification(
                            $invoice,
                            $failureMessage,
                            $stripeEventId
                        ));
                    $sentCount++;
                } catch (\Throwable $e) {
                    $failedCount++;
                    Log::error('Failed to send payment failure notification', [
                        'invoice_id' => $invoice->id,
                        'recipient' => $email,
                        'error' => $e->getMessage(),
                        'stripe_event_id' => $stripeEventId,
                    ]);
                }
            }
        }

        if ($sentCount > 0) {
            Log::info('Payment failure notification sent', [
                'invoice_id' => $invoice->id,
                'recipients_sent' => $sentCount,
                'recipients_failed' => $failedCount,
            ]);
        } elseif ($failedCount > 0) {
            Log::warning('All payment failure notifications failed', [
                'invoice_id' => $invoice->id,
                'recipients_attempted' => count($recipients),
            ]);
        }
    }

    /**
     * Resolve invoice for webhook event with idempotency checks.
     *
     * This helper method handles the common webhook pattern:
     * 1. Extract event data
     * 2. Check for duplicate events
     * 3. Find the invoice
     * 4. Handle not-found cases
     *
     * @param array $event The Stripe webhook event
     * @param string $eventType The event type (e.g., 'invoice.paid')
     * @return array{invoice: ?AggregateInvoice, stripeInvoice: array, stripeEventId: ?string, shouldProcess: bool}
     */
    private function resolveInvoiceForWebhook(array $event, string $eventType): array
    {
        $stripeInvoice = $event['data']['object'];
        $stripeInvoiceId = $stripeInvoice['id'];
        $stripeEventId = $event['id'] ?? null;

        // Check for duplicate event
        if ($stripeEventId && StripeEvent::isDuplicate($stripeEventId)) {
            Log::info("Duplicate {$eventType} event, skipping", [
                'stripe_event_id' => $stripeEventId,
                'stripe_invoice_id' => $stripeInvoiceId,
            ]);

            return [
                'invoice' => null,
                'stripeInvoice' => $stripeInvoice,
                'stripeEventId' => $stripeEventId,
                'shouldProcess' => false,
            ];
        }

        $invoice = AggregateInvoice::where('stripe_invoice_id', $stripeInvoiceId)->first();

        if (! $invoice) {
            Log::warning("Received {$eventType} for unknown invoice", [
                'stripe_invoice_id' => $stripeInvoiceId,
                'stripe_event_id' => $stripeEventId,
            ]);

            // Mark event as processed even if invoice not found
            if ($stripeEventId) {
                StripeEvent::markAsProcessed($stripeEventId, $eventType);
            }

            return [
                'invoice' => null,
                'stripeInvoice' => $stripeInvoice,
                'stripeEventId' => $stripeEventId,
                'shouldProcess' => false,
            ];
        }

        return [
            'invoice' => $invoice,
            'stripeInvoice' => $stripeInvoice,
            'stripeEventId' => $stripeEventId,
            'shouldProcess' => true,
        ];
    }

    /**
     * Mark a Stripe event as processed.
     */
    private function markEventProcessed(?string $stripeEventId, string $eventType): void
    {
        if ($stripeEventId) {
            StripeEvent::markAsProcessed($stripeEventId, $eventType);
        }
    }

    /**
     * Handle invoice.finalized webhook event.
     *
     * Idempotent: Safe to call multiple times (Stripe may retry webhooks).
     */
    public function handleInvoiceFinalized(array $event): void
    {
        $resolved = $this->resolveInvoiceForWebhook($event, 'invoice.finalized');

        if (! $resolved['shouldProcess']) {
            return;
        }

        $invoice = $resolved['invoice'];
        $stripeInvoice = $resolved['stripeInvoice'];

        $invoice->update([
            'stripe_hosted_invoice_url' => $stripeInvoice['hosted_invoice_url'] ?? null,
            'stripe_pdf_url' => $stripeInvoice['invoice_pdf'] ?? null,
        ]);

        $this->markEventProcessed($resolved['stripeEventId'], 'invoice.finalized');

        Log::info('Invoice finalized via webhook', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'stripe_invoice_id' => $stripeInvoice['id'],
        ]);
    }

    /**
     * Handle invoice.voided webhook event.
     *
     * Idempotent: Safe to call multiple times (Stripe may retry webhooks).
     */
    public function handleInvoiceVoided(array $event): void
    {
        $resolved = $this->resolveInvoiceForWebhook($event, 'invoice.voided');

        if (! $resolved['shouldProcess']) {
            return;
        }

        $invoice = $resolved['invoice'];
        $invoice->void();

        $this->markEventProcessed($resolved['stripeEventId'], 'invoice.voided');

        Log::info('Invoice voided via webhook', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'stripe_invoice_id' => $resolved['stripeInvoice']['id'],
        ]);
    }

    /**
     * Resend an already-sent invoice via Stripe.
     *
     * Can be used when the customer didn't receive the email or needs a reminder.
     * Stripe handles email delivery with proper retry logic.
     *
     * @throws \Exception If invoice is not in 'open' status
     * @throws ApiErrorException If Stripe API call fails
     */
    public function resendInvoice(AggregateInvoice $invoice): AggregateInvoice
    {
        if ($invoice->status !== AggregateInvoice::STATUS_OPEN) {
            throw new \Exception('Only open invoices can be resent');
        }

        if (! $invoice->stripe_invoice_id) {
            throw new \Exception('Invoice has no Stripe invoice ID');
        }

        try {
            // Stripe's sendInvoice can be called multiple times on open invoices
            $sentInvoice = $this->retryStripeCall(
                fn () => $this->getStripe()->invoices->sendInvoice($invoice->stripe_invoice_id),
                "resend_invoice:{$invoice->id}"
            );

            // Update sent timestamp
            $invoice->update([
                'sent_at' => now(),
                'metadata' => array_merge($invoice->metadata ?? [], [
                    'last_resent_at' => now()->toIso8601String(),
                    'resend_count' => ($invoice->metadata['resend_count'] ?? 0) + 1,
                ]),
            ]);

            Log::info('Invoice resent via Stripe', [
                'invoice_id' => $invoice->id,
                'stripe_invoice_id' => $sentInvoice->id,
                'resend_count' => $invoice->metadata['resend_count'] ?? 1,
            ]);

            return $invoice->fresh();

        } catch (ApiErrorException $e) {
            Log::error('Failed to resend invoice', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Void a Stripe invoice.
     */
    public function voidStripeInvoice(AggregateInvoice $invoice): void
    {
        if (! $invoice->stripe_invoice_id) {
            $invoice->void();

            return;
        }

        try {
            $this->retryStripeCall(
                fn () => $this->getStripe()->invoices->voidInvoice($invoice->stripe_invoice_id),
                "void_invoice:{$invoice->id}"
            );
            $invoice->void();
        } catch (ApiErrorException $e) {
            Log::error('Failed to void Stripe invoice', [
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

        if (! $address) {
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
