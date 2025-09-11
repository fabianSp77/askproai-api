<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\StripeCheckoutService;
use App\Models\Tenant;
use App\Models\BalanceTopup;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Stripe\Exception\ApiErrorException;

class StripeCheckoutServiceTest extends TestCase
{
    use RefreshDatabase;

    private StripeCheckoutService $service;
    private Tenant $tenant;
    private $stripeMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock Stripe client
        $this->stripeMock = Mockery::mock('alias:Stripe\StripeClient');
        
        $this->service = new StripeCheckoutService();
        
        // Create test tenant
        $this->tenant = Tenant::create([
            'id' => 'test-tenant',
            'name' => 'Test Tenant',
            'tenant_type' => 'direct_customer',
            'balance_cents' => 1000,
            'settings' => [
                'stripe_customer_id' => 'cus_test123',
                'payment_method_id' => 'pm_test123',
            ],
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_checkout_session_with_idempotency()
    {
        // Arrange: Mock Stripe session creation
        $mockSession = new \stdClass();
        $mockSession->id = 'cs_test_123';
        $mockSession->url = 'https://checkout.stripe.com/pay/cs_test_123';
        $mockSession->payment_intent = 'pi_test_123';
        
        $this->stripeMock->checkout = Mockery::mock();
        $this->stripeMock->checkout->sessions = Mockery::mock();
        $this->stripeMock->checkout->sessions->shouldReceive('create')
            ->once()
            ->andReturn($mockSession);
        
        // Act: Create checkout session
        $result = $this->service->createTopupSession($this->tenant, 5000, [
            'success_url' => 'https://api.askproai.de/success',
            'cancel_url' => 'https://api.askproai.de/cancel',
        ]);
        
        // Assert: Verify session created successfully
        $this->assertTrue($result['success']);
        $this->assertEquals('cs_test_123', $result['session_id']);
        $this->assertEquals('https://checkout.stripe.com/pay/cs_test_123', $result['checkout_url']);
        
        // Verify topup record created
        $topup = BalanceTopup::where('tenant_id', $this->tenant->id)->first();
        $this->assertNotNull($topup);
        $this->assertEquals(5000, $topup->amount_cents);
        $this->assertEquals('pending', $topup->status);
        
        // Test idempotency - second call should return cached result
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn($result);
        
        $result2 = $this->service->createTopupSession($this->tenant, 5000, [
            'success_url' => 'https://api.askproai.de/success',
            'cancel_url' => 'https://api.askproai.de/cancel',
        ]);
        
        $this->assertEquals($result['session_id'], $result2['session_id']);
    }

    /** @test */
    public function it_applies_bonus_for_large_topups()
    {
        // Test bonus tiers
        $bonusTests = [
            ['amount' => 5000, 'expected_bonus' => 0],      // 50€ - no bonus
            ['amount' => 10000, 'expected_bonus' => 500],   // 100€ - 5% bonus
            ['amount' => 25000, 'expected_bonus' => 2500],  // 250€ - 10% bonus
            ['amount' => 50000, 'expected_bonus' => 7500],  // 500€ - 15% bonus
            ['amount' => 100000, 'expected_bonus' => 20000], // 1000€ - 20% bonus
        ];
        
        foreach ($bonusTests as $test) {
            // Create topup
            $topup = BalanceTopup::create([
                'tenant_id' => $this->tenant->id,
                'amount_cents' => $test['amount'],
                'bonus_cents' => 0,
                'status' => 'pending',
                'payment_method' => 'card',
            ]);
            
            // Calculate bonus
            $bonus = $this->service->calculateBonus($test['amount']);
            
            // Assert correct bonus
            $this->assertEquals(
                $test['expected_bonus'], 
                $bonus,
                "Failed for amount: {$test['amount']}"
            );
        }
    }

    /** @test */
    public function it_handles_payment_confirmation_correctly()
    {
        // Arrange: Create pending topup
        $topup = BalanceTopup::create([
            'tenant_id' => $this->tenant->id,
            'amount_cents' => 5000,
            'bonus_cents' => 250,
            'status' => 'pending',
            'stripe_session_id' => 'cs_test_123',
            'payment_method' => 'card',
        ]);
        
        // Mock Stripe payment intent
        $mockPaymentIntent = new \stdClass();
        $mockPaymentIntent->id = 'pi_test_123';
        $mockPaymentIntent->status = 'succeeded';
        $mockPaymentIntent->amount = 5000;
        
        $this->stripeMock->paymentIntents = Mockery::mock();
        $this->stripeMock->paymentIntents->shouldReceive('retrieve')
            ->with('pi_test_123')
            ->andReturn($mockPaymentIntent);
        
        // Act: Confirm payment
        $result = $this->service->confirmPayment($topup, 'pi_test_123');
        
        // Assert: Verify payment confirmed
        $this->assertTrue($result['success']);
        
        // Verify topup status updated
        $topup->refresh();
        $this->assertEquals('completed', $topup->status);
        $this->assertNotNull($topup->completed_at);
        
        // Verify balance updated (including bonus)
        $this->tenant->refresh();
        $this->assertEquals(6250, $this->tenant->balance_cents); // 10€ + 50€ + 2.50€ bonus
        
        // Verify transactions created
        $transactions = Transaction::where('tenant_id', $this->tenant->id)->get();
        $this->assertCount(2, $transactions); // Main credit + bonus credit
        
        $mainTransaction = $transactions->where('type', 'credit')
            ->where('amount_cents', 5000)
            ->first();
        $this->assertNotNull($mainTransaction);
        $this->assertEquals('Guthaben-Aufladung via Stripe', $mainTransaction->description);
        
        $bonusTransaction = $transactions->where('type', 'credit')
            ->where('amount_cents', 250)
            ->first();
        $this->assertNotNull($bonusTransaction);
        $this->assertStringContainsString('Bonus', $bonusTransaction->description);
    }

    /** @test */
    public function it_handles_payment_failure_gracefully()
    {
        // Arrange: Create pending topup
        $topup = BalanceTopup::create([
            'tenant_id' => $this->tenant->id,
            'amount_cents' => 5000,
            'status' => 'pending',
            'stripe_session_id' => 'cs_test_123',
        ]);
        
        // Mock failed payment intent
        $mockPaymentIntent = new \stdClass();
        $mockPaymentIntent->id = 'pi_test_123';
        $mockPaymentIntent->status = 'failed';
        $mockPaymentIntent->last_payment_error = (object)[
            'message' => 'Card declined',
            'code' => 'card_declined',
        ];
        
        $this->stripeMock->paymentIntents = Mockery::mock();
        $this->stripeMock->paymentIntents->shouldReceive('retrieve')
            ->andReturn($mockPaymentIntent);
        
        // Act: Try to confirm failed payment
        $result = $this->service->confirmPayment($topup, 'pi_test_123');
        
        // Assert: Should handle failure
        $this->assertFalse($result['success']);
        $this->assertEquals('payment_failed', $result['error']);
        
        // Verify topup marked as failed
        $topup->refresh();
        $this->assertEquals('failed', $topup->status);
        $this->assertNotNull($topup->metadata['failure_reason']);
        
        // Verify balance unchanged
        $this->tenant->refresh();
        $this->assertEquals(1000, $this->tenant->balance_cents);
        
        // Verify no credit transactions created
        $transactions = Transaction::where('tenant_id', $this->tenant->id)
            ->where('type', 'credit')
            ->count();
        $this->assertEquals(0, $transactions);
    }

    /** @test */
    public function it_prevents_double_processing_with_locks()
    {
        // Arrange: Create topup
        $topup = BalanceTopup::create([
            'tenant_id' => $this->tenant->id,
            'amount_cents' => 5000,
            'status' => 'pending',
            'stripe_session_id' => 'cs_test_123',
        ]);
        
        // Mock successful payment
        $mockPaymentIntent = new \stdClass();
        $mockPaymentIntent->id = 'pi_test_123';
        $mockPaymentIntent->status = 'succeeded';
        $mockPaymentIntent->amount = 5000;
        
        $this->stripeMock->paymentIntents = Mockery::mock();
        $this->stripeMock->paymentIntents->shouldReceive('retrieve')
            ->andReturn($mockPaymentIntent);
        
        // Act: Process payment twice (simulating race condition)
        $result1 = $this->service->confirmPayment($topup, 'pi_test_123');
        $result2 = $this->service->confirmPayment($topup, 'pi_test_123');
        
        // Assert: First should succeed, second should detect already processed
        $this->assertTrue($result1['success']);
        $this->assertFalse($result2['success']);
        $this->assertEquals('already_processed', $result2['error']);
        
        // Verify balance only increased once
        $this->tenant->refresh();
        $this->assertEquals(6000, $this->tenant->balance_cents); // Only one 50€ addition
        
        // Verify only one set of transactions
        $transactions = Transaction::where('tenant_id', $this->tenant->id)->count();
        $this->assertEquals(1, $transactions);
    }

    /** @test */
    public function it_handles_webhook_signature_verification()
    {
        // Arrange: Create webhook payload
        $payload = json_encode([
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_123',
                    'payment_intent' => 'pi_test_123',
                    'amount_total' => 5000,
                ],
            ],
        ]);
        
        $signature = 'test_signature';
        $secret = 'whsec_test123';
        
        // Mock Stripe webhook signature verification
        Mockery::mock('alias:Stripe\Webhook')
            ->shouldReceive('constructEvent')
            ->with($payload, $signature, $secret)
            ->once()
            ->andReturn(json_decode($payload));
        
        // Act: Verify webhook
        $result = $this->service->verifyWebhookSignature($payload, $signature, $secret);
        
        // Assert: Should pass verification
        $this->assertTrue($result['valid']);
        $this->assertNotNull($result['event']);
    }

    /** @test */
    public function it_handles_stripe_api_errors()
    {
        // Arrange: Mock API error
        $this->stripeMock->checkout = Mockery::mock();
        $this->stripeMock->checkout->sessions = Mockery::mock();
        $this->stripeMock->checkout->sessions->shouldReceive('create')
            ->andThrow(new \Stripe\Exception\ApiConnectionException('Network error'));
        
        // Act: Try to create session
        $result = $this->service->createTopupSession($this->tenant, 5000);
        
        // Assert: Should handle error gracefully
        $this->assertFalse($result['success']);
        $this->assertEquals('stripe_api_error', $result['error']);
        $this->assertStringContainsString('Network error', $result['message']);
        
        // Verify no topup record created
        $topupCount = BalanceTopup::where('tenant_id', $this->tenant->id)->count();
        $this->assertEquals(0, $topupCount);
    }

    /** @test */
    public function it_validates_minimum_and_maximum_amounts()
    {
        // Test minimum amount
        $result = $this->service->createTopupSession($this->tenant, 500); // 5€ - below minimum
        $this->assertFalse($result['success']);
        $this->assertEquals('invalid_amount', $result['error']);
        
        // Test maximum amount
        $result = $this->service->createTopupSession($this->tenant, 100001); // 1000.01€ - above maximum
        $this->assertFalse($result['success']);
        $this->assertEquals('invalid_amount', $result['error']);
        
        // Test valid amount
        $mockSession = new \stdClass();
        $mockSession->id = 'cs_test_valid';
        $mockSession->url = 'https://checkout.stripe.com/pay/cs_test_valid';
        
        $this->stripeMock->checkout = Mockery::mock();
        $this->stripeMock->checkout->sessions = Mockery::mock();
        $this->stripeMock->checkout->sessions->shouldReceive('create')
            ->andReturn($mockSession);
        
        $result = $this->service->createTopupSession($this->tenant, 5000); // 50€ - valid
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_supports_different_payment_methods()
    {
        $paymentMethods = ['card', 'sepa_debit', 'giropay', 'sofort'];
        
        foreach ($paymentMethods as $method) {
            // Mock session creation with specific payment method
            $mockSession = new \stdClass();
            $mockSession->id = "cs_test_{$method}";
            $mockSession->url = "https://checkout.stripe.com/pay/cs_test_{$method}";
            
            $this->stripeMock->checkout = Mockery::mock();
            $this->stripeMock->checkout->sessions = Mockery::mock();
            $this->stripeMock->checkout->sessions->shouldReceive('create')
                ->withArgs(function ($args) use ($method) {
                    return in_array($method, $args['payment_method_types']);
                })
                ->andReturn($mockSession);
            
            // Create session with specific payment method
            $result = $this->service->createTopupSession($this->tenant, 5000, [
                'payment_method_types' => [$method],
            ]);
            
            $this->assertTrue($result['success'], "Failed for payment method: {$method}");
        }
    }

    /** @test */
    public function it_handles_currency_conversion()
    {
        // Test EUR (default)
        $result = $this->service->formatCurrency(5000, 'EUR');
        $this->assertEquals('50,00 €', $result);
        
        // Test USD
        $result = $this->service->formatCurrency(5000, 'USD');
        $this->assertEquals('$50.00', $result);
        
        // Test GBP
        $result = $this->service->formatCurrency(5000, 'GBP');
        $this->assertEquals('£50.00', $result);
    }

    /** @test */
    public function it_logs_all_payment_activities()
    {
        // Arrange: Set up log expectations
        Log::shouldReceive('info')
            ->with(Mockery::pattern('/Creating checkout session/'), Mockery::any())
            ->once();
        
        Log::shouldReceive('info')
            ->with(Mockery::pattern('/Checkout session created/'), Mockery::any())
            ->once();
        
        // Mock Stripe response
        $mockSession = new \stdClass();
        $mockSession->id = 'cs_test_log';
        $mockSession->url = 'https://checkout.stripe.com/pay/cs_test_log';
        
        $this->stripeMock->checkout = Mockery::mock();
        $this->stripeMock->checkout->sessions = Mockery::mock();
        $this->stripeMock->checkout->sessions->shouldReceive('create')
            ->andReturn($mockSession);
        
        // Act: Create session
        $this->service->createTopupSession($this->tenant, 5000);
        
        // Assertions are in the log expectations above
    }

    /** @test */
    public function it_handles_refunds_properly()
    {
        // Arrange: Create completed topup
        $topup = BalanceTopup::create([
            'tenant_id' => $this->tenant->id,
            'amount_cents' => 5000,
            'status' => 'completed',
            'stripe_payment_intent_id' => 'pi_test_refund',
        ]);
        
        // Update tenant balance
        $this->tenant->update(['balance_cents' => 6000]);
        
        // Mock Stripe refund
        $mockRefund = new \stdClass();
        $mockRefund->id = 'rf_test_123';
        $mockRefund->status = 'succeeded';
        $mockRefund->amount = 5000;
        
        $this->stripeMock->refunds = Mockery::mock();
        $this->stripeMock->refunds->shouldReceive('create')
            ->with(['payment_intent' => 'pi_test_refund', 'amount' => 5000])
            ->andReturn($mockRefund);
        
        // Act: Process refund
        $result = $this->service->refundTopup($topup, 5000, 'Customer request');
        
        // Assert: Verify refund processed
        $this->assertTrue($result['success']);
        $this->assertEquals('rf_test_123', $result['refund_id']);
        
        // Verify topup status
        $topup->refresh();
        $this->assertEquals('refunded', $topup->status);
        
        // Verify balance adjusted
        $this->tenant->refresh();
        $this->assertEquals(1000, $this->tenant->balance_cents); // 60€ - 50€ refund
        
        // Verify refund transaction created
        $refundTransaction = Transaction::where('tenant_id', $this->tenant->id)
            ->where('type', 'debit')
            ->where('amount_cents', 5000)
            ->first();
        
        $this->assertNotNull($refundTransaction);
        $this->assertStringContainsString('Rückerstattung', $refundTransaction->description);
    }
}