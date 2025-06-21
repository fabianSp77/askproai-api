<?php

namespace Tests\Integration\Stripe;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\WebhookEvent;
use App\Jobs\ProcessWebhookJob;
use App\Services\Webhooks\StripeWebhookHandler;
use App\Services\StripeInvoiceService;
use Carbon\Carbon;
use Mockery;

class StripeWebhookIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected StripeWebhookHandler $handler;
    protected $stripeServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock StripeInvoiceService
        $this->stripeServiceMock = Mockery::mock(StripeInvoiceService::class);
        $this->app->instance(StripeInvoiceService::class, $this->stripeServiceMock);

        $this->handler = app(StripeWebhookHandler::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function createWebhookEvent(string $type, array $data): WebhookEvent
    {
        return WebhookEvent::create([
            'source' => 'stripe',
            'event_type' => $type,
            'event_id' => 'evt_' . uniqid(),
            'payload' => $data,
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function it_processes_invoice_paid_webhook_end_to_end()
    {
        Queue::fake();

        // Create company and invoice
        $company = Company::factory()->create([
            'stripe_customer_id' => 'cus_test123',
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'stripe_invoice_id' => 'in_test123',
            'status' => 'finalized',
            'total_amount' => 150.00,
        ]);

        // Simulate webhook payload from Stripe
        $webhookPayload = [
            'id' => 'evt_1234567890',
            'type' => 'invoice.payment_succeeded',
            'created' => time(),
            'data' => [
                'object' => [
                    'id' => 'in_test123',
                    'customer' => 'cus_test123',
                    'amount_paid' => 15000,
                    'currency' => 'eur',
                    'payment_intent' => 'pi_test123',
                    'charge' => 'ch_test123',
                    'status' => 'paid',
                    'paid' => true,
                    'number' => 'INV-2024-001',
                    'hosted_invoice_url' => 'https://invoice.stripe.com/test',
                    'invoice_pdf' => 'https://invoice.stripe.com/test.pdf',
                    'lines' => [
                        'data' => [
                            [
                                'description' => 'AI-Telefonate',
                                'amount' => 7500,
                                'quantity' => 50,
                            ],
                            [
                                'description' => 'Gebuchte Termine',
                                'amount' => 6000,
                                'quantity' => 20,
                            ],
                        ],
                    ],
                    'metadata' => [
                        'company_id' => $company->id,
                        'invoice_id' => $invoice->id,
                    ],
                ],
            ],
        ];

        // Create webhook event
        $webhookEvent = $this->createWebhookEvent('invoice.payment_succeeded', $webhookPayload);

        // Process webhook synchronously for testing
        Queue::sync();

        // Dispatch the job
        ProcessWebhookJob::dispatch($webhookEvent);

        // Assert job was dispatched
        Queue::assertPushed(ProcessWebhookJob::class, function ($job) use ($webhookEvent) {
            return $job->webhookEvent->id === $webhookEvent->id;
        });

        // Process the webhook directly
        $result = $this->handler->handle($webhookEvent);

        $this->assertTrue($result);
        $this->assertEquals('processed', $webhookEvent->fresh()->status);

        // Verify invoice was updated
        $updatedInvoice = $invoice->fresh();
        $this->assertEquals('paid', $updatedInvoice->status);
        $this->assertNotNull($updatedInvoice->paid_at);
        $this->assertEquals('https://invoice.stripe.com/test', $updatedInvoice->invoice_url);
        $this->assertEquals('https://invoice.stripe.com/test.pdf', $updatedInvoice->pdf_url);

        // Verify payment was created
        $payment = Payment::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(150.00, $payment->amount);
        $this->assertEquals('stripe', $payment->payment_method);
        $this->assertEquals('pi_test123', $payment->stripe_payment_intent_id);
        $this->assertEquals('ch_test123', $payment->stripe_charge_id);
        $this->assertEquals('completed', $payment->status);
    }

    /** @test */
    public function it_handles_customer_subscription_lifecycle_webhooks()
    {
        $company = Company::factory()->create([
            'stripe_customer_id' => 'cus_test123',
        ]);

        // Test subscription created
        $subscriptionCreatedPayload = [
            'type' => 'customer.subscription.created',
            'data' => [
                'object' => [
                    'id' => 'sub_test123',
                    'customer' => 'cus_test123',
                    'status' => 'active',
                    'current_period_start' => time(),
                    'current_period_end' => time() + 2592000, // 30 days
                    'items' => [
                        'data' => [
                            [
                                'price' => [
                                    'id' => 'price_test123',
                                    'product' => 'prod_test123',
                                    'unit_amount' => 9900,
                                    'currency' => 'eur',
                                    'recurring' => [
                                        'interval' => 'month',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'metadata' => [
                        'company_id' => $company->id,
                    ],
                ],
            ],
        ];

        $webhookEvent = $this->createWebhookEvent('customer.subscription.created', $subscriptionCreatedPayload);
        $result = $this->handler->handle($webhookEvent);

        $this->assertTrue($result);

        // Verify company subscription data was updated
        $updatedCompany = $company->fresh();
        $this->assertEquals('sub_test123', $updatedCompany->stripe_subscription_id);
        $this->assertEquals('active', $updatedCompany->subscription_status);
        $this->assertEquals('price_test123', $updatedCompany->stripe_price_id);
        $this->assertNotNull($updatedCompany->subscription_ends_at);

        // Test subscription updated (downgrade)
        $subscriptionUpdatedPayload = [
            'type' => 'customer.subscription.updated',
            'data' => [
                'object' => [
                    'id' => 'sub_test123',
                    'customer' => 'cus_test123',
                    'status' => 'active',
                    'cancel_at_period_end' => true,
                    'items' => [
                        'data' => [
                            [
                                'price' => [
                                    'id' => 'price_basic',
                                    'unit_amount' => 4900,
                                ],
                            ],
                        ],
                    ],
                ],
                'previous_attributes' => [
                    'cancel_at_period_end' => false,
                    'items' => [
                        'data' => [
                            [
                                'price' => [
                                    'id' => 'price_test123',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $webhookEvent2 = $this->createWebhookEvent('customer.subscription.updated', $subscriptionUpdatedPayload);
        $this->handler->handle($webhookEvent2);

        $updatedCompany = $company->fresh();
        $this->assertEquals('price_basic', $updatedCompany->stripe_price_id);
        $this->assertTrue($updatedCompany->subscription_canceling);

        // Test subscription cancelled
        $subscriptionCancelledPayload = [
            'type' => 'customer.subscription.deleted',
            'data' => [
                'object' => [
                    'id' => 'sub_test123',
                    'customer' => 'cus_test123',
                    'status' => 'canceled',
                    'canceled_at' => time(),
                ],
            ],
        ];

        $webhookEvent3 = $this->createWebhookEvent('customer.subscription.deleted', $subscriptionCancelledPayload);
        $this->handler->handle($webhookEvent3);

        $updatedCompany = $company->fresh();
        $this->assertEquals('canceled', $updatedCompany->subscription_status);
        $this->assertNull($updatedCompany->stripe_subscription_id);
    }

    /** @test */
    public function it_handles_payment_failure_webhooks_with_retry_notification()
    {
        $company = Company::factory()->create([
            'stripe_customer_id' => 'cus_test123',
            'email' => 'billing@company.com',
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'stripe_invoice_id' => 'in_failed',
            'status' => 'finalized',
            'total_amount' => 200.00,
        ]);

        $paymentFailedPayload = [
            'type' => 'invoice.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'in_failed',
                    'customer' => 'cus_test123',
                    'amount_due' => 20000,
                    'attempt_count' => 1,
                    'next_payment_attempt' => time() + 259200, // 3 days
                    'charge' => 'ch_failed',
                    'payment_intent' => 'pi_failed',
                ],
            ],
        ];

        // Mock email notification
        $this->stripeServiceMock->shouldReceive('sendPaymentFailureNotification')
            ->once()
            ->with(Mockery::on(function ($invoice) {
                return $invoice->stripe_invoice_id === 'in_failed';
            }));

        $webhookEvent = $this->createWebhookEvent('invoice.payment_failed', $paymentFailedPayload);
        $result = $this->handler->handle($webhookEvent);

        $this->assertTrue($result);

        // Verify invoice status
        $updatedInvoice = $invoice->fresh();
        $this->assertEquals('payment_failed', $updatedInvoice->status);
        $this->assertEquals(1, $updatedInvoice->payment_attempts);
        $this->assertNotNull($updatedInvoice->next_payment_attempt);

        // Verify payment failure record
        $payment = Payment::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('failed', $payment->status);
        $this->assertEquals('ch_failed', $payment->stripe_charge_id);
        $this->assertNotNull($payment->failure_reason);
    }

    /** @test */
    public function it_handles_checkout_session_completed_for_one_time_payment()
    {
        $company = Company::factory()->create([
            'stripe_customer_id' => 'cus_test123',
        ]);

        $checkoutPayload = [
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test123',
                    'customer' => 'cus_test123',
                    'payment_status' => 'paid',
                    'amount_total' => 5000,
                    'currency' => 'eur',
                    'payment_intent' => 'pi_checkout',
                    'invoice' => null, // One-time payment
                    'mode' => 'payment',
                    'metadata' => [
                        'company_id' => $company->id,
                        'type' => 'credit_purchase',
                        'credits' => '100',
                    ],
                    'line_items' => [
                        'data' => [
                            [
                                'description' => '100 Credits',
                                'amount_total' => 5000,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $webhookEvent = $this->createWebhookEvent('checkout.session.completed', $checkoutPayload);
        $result = $this->handler->handle($webhookEvent);

        $this->assertTrue($result);

        // Verify company credits were added
        $updatedCompany = $company->fresh();
        $this->assertEquals(100, $updatedCompany->credit_balance);

        // Verify payment record
        $payment = Payment::where('company_id', $company->id)
            ->where('stripe_payment_intent_id', 'pi_checkout')
            ->first();
        $this->assertNotNull($payment);
        $this->assertEquals(50.00, $payment->amount);
        $this->assertEquals('credit_purchase', $payment->type);
        $this->assertEquals('completed', $payment->status);
    }

    /** @test */
    public function it_prevents_duplicate_webhook_processing()
    {
        $company = Company::factory()->create([
            'stripe_customer_id' => 'cus_test123',
        ]);

        $webhookPayload = [
            'id' => 'evt_duplicate',
            'type' => 'customer.updated',
            'data' => [
                'object' => [
                    'id' => 'cus_test123',
                    'email' => 'new@email.com',
                ],
            ],
        ];

        // Process first time
        $webhookEvent1 = $this->createWebhookEvent('customer.updated', $webhookPayload);
        $result1 = $this->handler->handle($webhookEvent1);
        $this->assertTrue($result1);

        // Try to process duplicate
        $webhookEvent2 = $this->createWebhookEvent('customer.updated', $webhookPayload);
        $webhookEvent2->update(['event_id' => 'evt_duplicate']); // Same Stripe event ID
        
        $result2 = $this->handler->handle($webhookEvent2);
        $this->assertFalse($result2); // Should be rejected as duplicate

        // Verify only processed once
        $processedEvents = WebhookEvent::where('event_id', 'evt_duplicate')
            ->where('status', 'processed')
            ->count();
        $this->assertEquals(1, $processedEvents);
    }

    /** @test */
    public function it_handles_webhook_errors_gracefully()
    {
        $invalidPayload = [
            'type' => 'invoice.payment_succeeded',
            'data' => [
                'object' => [
                    'id' => 'in_invalid',
                    'customer' => 'cus_nonexistent',
                    // Missing required fields
                ],
            ],
        ];

        $webhookEvent = $this->createWebhookEvent('invoice.payment_succeeded', $invalidPayload);
        
        // Should not throw exception
        $result = $this->handler->handle($webhookEvent);
        
        $this->assertFalse($result);
        $this->assertEquals('failed', $webhookEvent->fresh()->status);
        $this->assertNotNull($webhookEvent->fresh()->error_message);
    }

    /** @test */
    public function it_handles_payment_method_webhooks()
    {
        $company = Company::factory()->create([
            'stripe_customer_id' => 'cus_test123',
        ]);

        // Payment method attached
        $attachedPayload = [
            'type' => 'payment_method.attached',
            'data' => [
                'object' => [
                    'id' => 'pm_test123',
                    'customer' => 'cus_test123',
                    'type' => 'card',
                    'card' => [
                        'brand' => 'visa',
                        'last4' => '4242',
                        'exp_month' => 12,
                        'exp_year' => 2025,
                    ],
                ],
            ],
        ];

        $webhookEvent = $this->createWebhookEvent('payment_method.attached', $attachedPayload);
        $this->handler->handle($webhookEvent);

        // Verify payment method was stored
        $updatedCompany = $company->fresh();
        $this->assertNotNull($updatedCompany->payment_methods);
        $paymentMethods = json_decode($updatedCompany->payment_methods, true);
        $this->assertArrayHasKey('pm_test123', $paymentMethods);
        $this->assertEquals('visa', $paymentMethods['pm_test123']['brand']);
        $this->assertEquals('4242', $paymentMethods['pm_test123']['last4']);

        // Payment method detached
        $detachedPayload = [
            'type' => 'payment_method.detached',
            'data' => [
                'object' => [
                    'id' => 'pm_test123',
                    'customer' => null,
                ],
                'previous_attributes' => [
                    'customer' => 'cus_test123',
                ],
            ],
        ];

        $webhookEvent2 = $this->createWebhookEvent('payment_method.detached', $detachedPayload);
        $this->handler->handle($webhookEvent2);

        // Verify payment method was removed
        $updatedCompany = $company->fresh();
        $paymentMethods = json_decode($updatedCompany->payment_methods, true);
        $this->assertArrayNotHasKey('pm_test123', $paymentMethods);
    }
}