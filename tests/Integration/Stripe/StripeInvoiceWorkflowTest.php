<?php

namespace Tests\Integration\Stripe;

use App\Models\Appointment;
use App\Models\BillingPeriod;
use App\Models\Call;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Services\PricingService;
use App\Services\StripeInvoiceService;
use App\Services\TaxService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Stripe\Charge;
use Stripe\Customer as StripeCustomer;
use Stripe\Invoice as StripeInvoice;
use Stripe\PaymentIntent;
use Stripe\StripeClient;
use Tests\TestCase;

class StripeInvoiceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected StripeInvoiceService $service;
    protected $stripeClientMock;
    protected Company $company;
    protected BillingPeriod $billingPeriod;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Stripe client
        $this->stripeClientMock = Mockery::mock(StripeClient::class);
        $this->stripeClientMock->customers = Mockery::mock();
        $this->stripeClientMock->invoices = Mockery::mock();
        $this->stripeClientMock->invoiceItems = Mockery::mock();
        $this->stripeClientMock->paymentIntents = Mockery::mock();

        // Override the StripeClient in the service
        $this->app->bind(StripeClient::class, function () {
            return $this->stripeClientMock;
        });

        // Create test company
        $this->company = Company::factory()->create([
            'name' => 'Test Company GmbH',
            'email' => 'billing@testcompany.de',
            'vat_number' => 'DE123456789',
            'country' => 'DE',
            'stripe_customer_id' => null,
        ]);

        // Create billing period
        $this->billingPeriod = BillingPeriod::create([
            'company_id' => $this->company->id,
            'start_date' => Carbon::now()->startOfMonth(),
            'end_date' => Carbon::now()->endOfMonth(),
            'status' => 'open',
        ]);

        $this->service = app(StripeInvoiceService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_complete_invoice_workflow_from_usage_to_payment()
    {
        // Step 1: Create Stripe customer
        $stripeCustomerId = 'cus_test123';
        $this->stripeClientMock->customers->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['name'] === 'Test Company GmbH' &&
                       $data['email'] === 'billing@testcompany.de' &&
                       $data['metadata']['company_id'] === $this->company->id;
            }))
            ->andReturn((object)['id' => $stripeCustomerId]);

        $customerId = $this->service->ensureStripeCustomer($this->company);
        $this->assertEquals($stripeCustomerId, $customerId);
        $this->assertEquals($stripeCustomerId, $this->company->fresh()->stripe_customer_id);

        // Step 2: Generate usage data (calls and appointments)
        $calls = Call::factory()->count(50)->create([
            'company_id' => $this->company->id,
            'duration_minutes' => 5,
            'status' => 'completed',
            'created_at' => Carbon::now()->subDays(15),
        ]);

        $appointments = Appointment::factory()->count(20)->create([
            'company_id' => $this->company->id,
            'status' => 'completed',
            'created_at' => Carbon::now()->subDays(10),
        ]);

        // Step 3: Generate monthly invoice
        $stripeInvoiceId = 'in_test123';
        $stripePriceIds = [
            'price_calls' => 'price_1234',
            'price_appointments' => 'price_5678',
        ];

        // Mock invoice item creation for calls
        $this->stripeClientMock->invoiceItems->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) use ($stripeCustomerId) {
                return $data['customer'] === $stripeCustomerId &&
                       $data['description'] === '50 AI-Telefonate' &&
                       $data['unit_amount'] === 1500 && // €0.15 per call in cents
                       $data['quantity'] === 50;
            }))
            ->andReturn((object)['id' => 'ii_calls']);

        // Mock invoice item creation for appointments
        $this->stripeClientMock->invoiceItems->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) use ($stripeCustomerId) {
                return $data['customer'] === $stripeCustomerId &&
                       $data['description'] === '20 Gebuchte Termine' &&
                       $data['unit_amount'] === 3000 && // €0.30 per appointment in cents
                       $data['quantity'] === 20;
            }))
            ->andReturn((object)['id' => 'ii_appointments']);

        // Mock invoice creation
        $this->stripeClientMock->invoices->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) use ($stripeCustomerId) {
                return $data['customer'] === $stripeCustomerId &&
                       $data['auto_advance'] === false &&
                       $data['collection_method'] === 'send_invoice' &&
                       $data['days_until_due'] === 14;
            }))
            ->andReturn((object)[
                'id' => $stripeInvoiceId,
                'number' => 'INV-2024-001',
                'amount_due' => 15750, // Total with tax
                'subtotal' => 13500,
                'tax' => 2250,
                'status' => 'draft',
                'created' => time(),
            ]);

        $invoice = $this->service->generateMonthlyInvoice($this->company, $this->billingPeriod);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals($stripeInvoiceId, $invoice->stripe_invoice_id);
        $this->assertEquals(157.50, $invoice->total_amount);
        $this->assertEquals(135.00, $invoice->subtotal);
        $this->assertEquals(22.50, $invoice->tax_amount);
        $this->assertEquals('draft', $invoice->status);

        // Verify invoice items were created
        $this->assertEquals(2, $invoice->items()->count());
        $callItem = $invoice->items()->where('description', 'AI-Telefonate')->first();
        $this->assertEquals(50, $callItem->quantity);
        $this->assertEquals(1.50, $callItem->unit_price);

        // Step 4: Finalize invoice
        $this->stripeClientMock->invoices->shouldReceive('finalizeInvoice')
            ->once()
            ->with($stripeInvoiceId)
            ->andReturn((object)[
                'id' => $stripeInvoiceId,
                'status' => 'open',
                'hosted_invoice_url' => 'https://invoice.stripe.com/test',
                'invoice_pdf' => 'https://invoice.stripe.com/test.pdf',
            ]);

        $finalizedInvoice = $this->service->finalizeInvoice($invoice);

        $this->assertEquals('finalized', $finalizedInvoice->status);
        $this->assertNotNull($finalizedInvoice->invoice_url);
        $this->assertNotNull($finalizedInvoice->pdf_url);

        // Step 5: Process payment
        $paymentIntentId = 'pi_test123';
        $chargeId = 'ch_test123';

        $this->stripeClientMock->invoices->shouldReceive('pay')
            ->once()
            ->with($stripeInvoiceId)
            ->andReturn((object)[
                'id' => $stripeInvoiceId,
                'status' => 'paid',
                'payment_intent' => $paymentIntentId,
                'charge' => $chargeId,
                'paid' => true,
                'paid_at' => time(),
            ]);

        $this->stripeClientMock->paymentIntents->shouldReceive('retrieve')
            ->once()
            ->with($paymentIntentId)
            ->andReturn((object)[
                'id' => $paymentIntentId,
                'amount' => 15750,
                'currency' => 'eur',
                'status' => 'succeeded',
                'charges' => (object)[
                    'data' => [
                        (object)[
                            'id' => $chargeId,
                            'payment_method_details' => (object)[
                                'type' => 'card',
                                'card' => (object)[
                                    'brand' => 'visa',
                                    'last4' => '4242',
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

        $paidInvoice = $this->service->processPayment($invoice, [
            'payment_intent' => $paymentIntentId,
            'charge' => $chargeId,
        ]);

        $this->assertEquals('paid', $paidInvoice->status);
        $this->assertNotNull($paidInvoice->paid_at);

        // Verify payment record was created
        $payment = Payment::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(157.50, $payment->amount);
        $this->assertEquals('stripe', $payment->payment_method);
        $this->assertEquals($paymentIntentId, $payment->stripe_payment_intent_id);
        $this->assertEquals('completed', $payment->status);

        // Step 6: Close billing period
        $this->assertEquals('closed', $this->billingPeriod->fresh()->status);
    }

    /** @test */
    #[Test]
    public function it_handles_invoice_generation_with_discounts_and_credits()
    {
        // Setup company with discount
        $this->company->update([
            'stripe_customer_id' => 'cus_existing',
            'discount_percentage' => 20,
            'credit_balance' => 50.00,
        ]);

        // Create usage
        Call::factory()->count(100)->create([
            'company_id' => $this->company->id,
            'duration_minutes' => 5,
            'status' => 'completed',
        ]);

        // Mock Stripe calls with discount calculation
        $this->stripeClientMock->invoiceItems->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['unit_amount'] === 1500 && $data['quantity'] === 100;
            }))
            ->andReturn((object)['id' => 'ii_calls']);

        // Add discount as negative line item
        $this->stripeClientMock->invoiceItems->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['description'] === '20% Rabatt' && 
                       $data['unit_amount'] === -3000; // 20% of 150€
            }))
            ->andReturn((object)['id' => 'ii_discount']);

        // Add credit as negative line item
        $this->stripeClientMock->invoiceItems->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['description'] === 'Guthaben' && 
                       $data['unit_amount'] === -5000; // €50 credit
            }))
            ->andReturn((object)['id' => 'ii_credit']);

        $this->stripeClientMock->invoices->shouldReceive('create')
            ->once()
            ->andReturn((object)[
                'id' => 'in_discount',
                'number' => 'INV-2024-002',
                'amount_due' => 11400, // (150 - 30 - 50) * 1.19 = 83.30 * 1.19 = 99.13
                'subtotal' => 7000,
                'tax' => 1330,
                'status' => 'draft',
                'created' => time(),
            ]);

        $invoice = $this->service->generateMonthlyInvoice($this->company, $this->billingPeriod);

        $this->assertEquals(83.30, $invoice->total_amount);
        $this->assertEquals(70.00, $invoice->subtotal);
        $this->assertEquals(13.30, $invoice->tax_amount);
        $this->assertEquals(30.00, $invoice->discount_amount);
        $this->assertEquals(50.00, $invoice->credit_applied);

        // Verify credit was deducted from company
        $this->assertEquals(0, $this->company->fresh()->credit_balance);
    }

    /** @test */
    public function it_handles_failed_payment_and_retry_logic()
    {
        $this->company->update(['stripe_customer_id' => 'cus_existing']);

        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'stripe_invoice_id' => 'in_failed',
            'status' => 'finalized',
            'total_amount' => 100.00,
        ]);

        // First payment attempt fails
        $this->stripeClientMock->invoices->shouldReceive('pay')
            ->once()
            ->with('in_failed')
            ->andThrow(new \Stripe\Exception\CardException('Your card was declined.', 'card_declined'));

        try {
            $this->service->processPayment($invoice, ['source' => 'pm_card']);
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            $this->assertStringContainsString('card was declined', $e->getMessage());
        }

        $this->assertEquals('payment_failed', $invoice->fresh()->status);
        $this->assertEquals(1, $invoice->payment_attempts);

        // Second payment attempt with different card succeeds
        $this->stripeClientMock->invoices->shouldReceive('pay')
            ->once()
            ->with('in_failed', ['payment_method' => 'pm_new_card'])
            ->andReturn((object)[
                'id' => 'in_failed',
                'status' => 'paid',
                'payment_intent' => 'pi_retry',
                'paid' => true,
                'paid_at' => time(),
            ]);

        $this->stripeClientMock->paymentIntents->shouldReceive('retrieve')
            ->once()
            ->andReturn((object)[
                'id' => 'pi_retry',
                'amount' => 10000,
                'status' => 'succeeded',
                'charges' => (object)['data' => [(object)['id' => 'ch_retry']]],
            ]);

        $paidInvoice = $this->service->retryPayment($invoice, 'pm_new_card');

        $this->assertEquals('paid', $paidInvoice->status);
        $this->assertEquals(2, $paidInvoice->payment_attempts);
        $this->assertNotNull($paidInvoice->paid_at);
    }

    /** @test */
    #[Test]
    public function it_generates_invoice_with_complex_tax_scenarios()
    {
        // EU B2B customer with reverse charge
        $this->company->update([
            'stripe_customer_id' => 'cus_eu',
            'country' => 'FR',
            'vat_number' => 'FR12345678901',
        ]);

        Call::factory()->count(50)->create([
            'company_id' => $this->company->id,
            'status' => 'completed',
        ]);

        $this->stripeClientMock->invoiceItems->shouldReceive('create')->once();
        $this->stripeClientMock->invoices->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                // Verify reverse charge metadata
                return isset($data['metadata']['reverse_charge']) && 
                       $data['metadata']['reverse_charge'] === 'true';
            }))
            ->andReturn((object)[
                'id' => 'in_eu',
                'amount_due' => 7500, // No VAT
                'subtotal' => 7500,
                'tax' => 0,
                'status' => 'draft',
            ]);

        $invoice = $this->service->generateMonthlyInvoice($this->company, $this->billingPeriod);

        $this->assertEquals(75.00, $invoice->total_amount);
        $this->assertEquals(0, $invoice->tax_amount);
        $this->assertEquals(0, $invoice->tax_rate);
        $this->assertTrue($invoice->is_reverse_charge);
    }
}