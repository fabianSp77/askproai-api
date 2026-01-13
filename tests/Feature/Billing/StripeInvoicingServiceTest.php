<?php

namespace Tests\Feature\Billing;

use App\Models\AggregateInvoice;
use App\Models\Company;
use App\Services\Billing\StripeInvoicingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Integration tests for StripeInvoicingService.
 *
 * These tests verify:
 * - Configuration detection
 * - Safe billing email handling
 * - Webhook event processing (invoice.paid, invoice.payment_failed)
 * - Idempotency in webhook handlers
 *
 * Note: Tests that require actual Stripe API calls are skipped
 * as they would require a test Stripe account and API keys.
 */
class StripeInvoicingServiceTest extends TestCase
{
    use DatabaseTransactions;

    private StripeInvoicingService $service;
    private Company $partner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(StripeInvoicingService::class);

        // Create partner company (test DB has limited schema - no email column)
        $this->partner = Company::factory()->create([
            'name' => 'Test Partner GmbH',
            'is_partner' => true,
            'partner_billing_email' => 'billing@external-domain.com',
            'partner_payment_terms_days' => 14,
        ]);
    }

    /** @test */
    public function it_detects_when_stripe_is_configured(): void
    {
        // Test with API key set
        Config::set('services.stripe.secret', 'sk_test_12345');

        $this->assertTrue($this->service->isConfigured());

        // Test without API key
        Config::set('services.stripe.secret', null);

        $this->assertFalse($this->service->isConfigured());

        // Test with empty string
        Config::set('services.stripe.secret', '');

        $this->assertFalse($this->service->isConfigured());
    }

    /** @test */
    public function it_uses_test_email_override_in_non_production(): void
    {
        // Ensure we're not in production
        $this->app->detectEnvironment(fn () => 'testing');

        // Set test email override
        Config::set('services.stripe.test_billing_email', 'test-override@askproai.de');

        $result = $this->service->getSafeBillingEmail($this->partner);

        $this->assertEquals('test-override@askproai.de', $result);
    }

    /** @test */
    public function it_blocks_external_emails_in_non_production(): void
    {
        // Ensure we're not in production
        $this->app->detectEnvironment(fn () => 'testing');

        // Clear any test email override
        Config::set('services.stripe.test_billing_email', null);
        Config::set('mail.admin_email', 'admin@askproai.de');

        // Partner has external email
        $this->partner->update(['partner_billing_email' => 'billing@external-domain.com']);

        $result = $this->service->getSafeBillingEmail($this->partner);

        // External email should be blocked and fallback to admin email
        $this->assertEquals('admin@askproai.de', $result);
    }

    /** @test */
    public function it_allows_internal_emails_in_non_production(): void
    {
        // Ensure we're not in production
        $this->app->detectEnvironment(fn () => 'testing');

        // Clear any test email override
        Config::set('services.stripe.test_billing_email', null);

        // Partner has internal email
        $this->partner->update(['partner_billing_email' => 'billing@askproai.de']);

        $result = $this->service->getSafeBillingEmail($this->partner);

        $this->assertEquals('billing@askproai.de', $result);
    }

    /** @test */
    public function it_marks_invoice_as_paid_on_webhook(): void
    {
        // Create invoice with Stripe ID
        $invoice = AggregateInvoice::factory()->create([
            'partner_company_id' => $this->partner->id,
            'stripe_invoice_id' => 'in_test_123456',
            'status' => AggregateInvoice::STATUS_OPEN,
            'total_cents' => 10000,
        ]);

        // Simulate Stripe webhook event
        $event = [
            'id' => 'evt_test_123',
            'type' => 'invoice.paid',
            'data' => [
                'object' => [
                    'id' => 'in_test_123456',
                    'status' => 'paid',
                    'amount_paid' => 10000,
                ],
            ],
        ];

        $this->service->handleInvoicePaid($event);

        $invoice->refresh();
        $this->assertEquals(AggregateInvoice::STATUS_PAID, $invoice->status);
        $this->assertNotNull($invoice->paid_at);
    }

    /** @test */
    public function it_is_idempotent_for_duplicate_paid_webhooks(): void
    {
        // Create already-paid invoice
        $paidAt = Carbon::now()->subHour();
        $invoice = AggregateInvoice::factory()->create([
            'partner_company_id' => $this->partner->id,
            'stripe_invoice_id' => 'in_test_123456',
            'status' => AggregateInvoice::STATUS_PAID,
            'paid_at' => $paidAt,
            'total_cents' => 10000,
        ]);

        // Simulate duplicate webhook
        $event = [
            'id' => 'evt_test_duplicate',
            'type' => 'invoice.paid',
            'data' => [
                'object' => [
                    'id' => 'in_test_123456',
                    'status' => 'paid',
                ],
            ],
        ];

        // Should not throw, just skip
        $this->service->handleInvoicePaid($event);

        $invoice->refresh();
        // paid_at should remain unchanged (idempotent)
        $this->assertEquals($paidAt->toDateTimeString(), $invoice->paid_at->toDateTimeString());
    }

    /** @test */
    public function it_handles_unknown_invoice_in_paid_webhook_gracefully(): void
    {
        $event = [
            'id' => 'evt_test_unknown',
            'type' => 'invoice.paid',
            'data' => [
                'object' => [
                    'id' => 'in_nonexistent',
                    'status' => 'paid',
                ],
            ],
        ];

        // Should not throw, just return early when invoice not found
        $this->service->handleInvoicePaid($event);

        // If we get here without exception, the test passes
        $this->assertTrue(true);
    }

    /** @test */
    public function it_records_payment_failure_on_webhook(): void
    {
        Notification::fake();

        // Create invoice
        $invoice = AggregateInvoice::factory()->create([
            'partner_company_id' => $this->partner->id,
            'stripe_invoice_id' => 'in_test_failed',
            'status' => AggregateInvoice::STATUS_OPEN,
            'total_cents' => 10000,
            'metadata' => [],
        ]);

        // Simulate payment failed event
        $event = [
            'id' => 'evt_test_failed',
            'type' => 'invoice.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'in_test_failed',
                    'status' => 'open',
                    'last_finalization_error' => [
                        'message' => 'Your card was declined.',
                    ],
                ],
            ],
        ];

        $this->service->handleInvoicePaymentFailed($event);

        $invoice->refresh();

        // Check metadata was updated
        $this->assertArrayHasKey('last_payment_failure', $invoice->metadata);
        $this->assertArrayHasKey('failure_message', $invoice->metadata);
        $this->assertEquals('Your card was declined.', $invoice->metadata['failure_message']);
    }

    /** @test */
    public function it_handles_finalized_webhook(): void
    {
        // Create draft invoice
        $invoice = AggregateInvoice::factory()->create([
            'partner_company_id' => $this->partner->id,
            'stripe_invoice_id' => 'in_test_finalized',
            'status' => AggregateInvoice::STATUS_DRAFT,
            'total_cents' => 15000,
        ]);

        $event = [
            'id' => 'evt_test_finalized',
            'type' => 'invoice.finalized',
            'data' => [
                'object' => [
                    'id' => 'in_test_finalized',
                    'status' => 'open',
                    'hosted_invoice_url' => 'https://invoice.stripe.com/test123',
                    'invoice_pdf' => 'https://invoice.stripe.com/test123/pdf',
                ],
            ],
        ];

        $this->service->handleInvoiceFinalized($event);

        $invoice->refresh();
        // Note: handleInvoiceFinalized only updates URLs, not status
        $this->assertEquals('https://invoice.stripe.com/test123', $invoice->stripe_hosted_invoice_url);
        $this->assertEquals('https://invoice.stripe.com/test123/pdf', $invoice->stripe_pdf_url);
    }

    /** @test */
    public function it_handles_voided_webhook(): void
    {
        // Create open invoice
        $invoice = AggregateInvoice::factory()->create([
            'partner_company_id' => $this->partner->id,
            'stripe_invoice_id' => 'in_test_voided',
            'status' => AggregateInvoice::STATUS_OPEN,
            'total_cents' => 20000,
        ]);

        $event = [
            'id' => 'evt_test_voided',
            'type' => 'invoice.voided',
            'data' => [
                'object' => [
                    'id' => 'in_test_voided',
                    'status' => 'void',
                ],
            ],
        ];

        $this->service->handleInvoiceVoided($event);

        $invoice->refresh();
        $this->assertEquals(AggregateInvoice::STATUS_VOID, $invoice->status);
    }

    /** @test */
    public function it_formats_address_correctly(): void
    {
        // Create partner with billing address
        $partnerWithAddress = Company::factory()->create([
            'name' => 'Partner With Address',
            'is_partner' => true,
            'partner_billing_address' => [
                'street' => 'Musterstraße 123',
                'line2' => 'Etage 3',
                'city' => 'Berlin',
                'postal_code' => '10115',
                'country' => 'DE',
            ],
        ]);

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('formatAddress');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $partnerWithAddress);

        $this->assertNotNull($result);
        $this->assertArrayHasKey('line1', $result);
        $this->assertArrayHasKey('city', $result);
        $this->assertArrayHasKey('postal_code', $result);
        $this->assertArrayHasKey('country', $result);
        $this->assertEquals('Musterstraße 123', $result['line1']);
        $this->assertEquals('Berlin', $result['city']);
    }
}
