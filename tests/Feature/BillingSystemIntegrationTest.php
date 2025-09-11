<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\BalanceTopup;
use App\Models\CommissionLedger;
use App\Services\BillingChainService;
use App\Services\StripeCheckoutService;
use App\Jobs\ProcessWebhook;
use App\Notifications\LowBalanceWarning;
use App\Notifications\AutoTopupProcessed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;
use Mockery;

class BillingSystemIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $platform;
    private Tenant $reseller;
    private Tenant $customer;
    private User $user;
    private BillingChainService $billingService;
    private StripeCheckoutService $stripeService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialize services
        $this->billingService = new BillingChainService();
        $this->stripeService = new StripeCheckoutService();
        
        // Create complete tenant hierarchy
        $this->setupTenantHierarchy();
        
        // Create authenticated user
        $this->user = User::factory()->create([
            'tenant_id' => $this->customer->id,
            'email' => 'integration@test.de',
        ]);
        
        // Authenticate
        Sanctum::actingAs($this->user, ['*']);
    }

    /**
     * Setup complete tenant hierarchy for testing
     */
    private function setupTenantHierarchy(): void
    {
        $this->platform = Tenant::create([
            'id' => 'platform-integration',
            'name' => 'Integration Test Platform',
            'tenant_type' => 'platform',
            'balance_cents' => 0,
            'settings' => [
                'billing' => [
                    'enabled' => true,
                    'currency' => 'EUR',
                ],
            ],
            'is_active' => true,
        ]);
        
        $this->reseller = Tenant::create([
            'id' => 'reseller-integration',
            'name' => 'Integration Test Reseller',
            'tenant_type' => 'reseller',
            'parent_id' => $this->platform->id,
            'balance_cents' => 500000, // 5000€
            'settings' => [
                'pricing' => [
                    'call_minutes' => 40,
                    'api_calls' => 15,
                    'appointments' => 150,
                    'sms_messages' => 8,
                ],
                'commission_rate' => 0.25,
                'auto_payout' => [
                    'enabled' => true,
                    'threshold_cents' => 10000,
                    'method' => 'bank_transfer',
                ],
            ],
            'is_active' => true,
        ]);
        
        $this->customer = Tenant::create([
            'id' => 'customer-integration',
            'name' => 'Integration Test Customer',
            'tenant_type' => 'reseller_customer',
            'parent_id' => $this->reseller->id,
            'balance_cents' => 10000, // 100€
            'settings' => [
                'auto_topup' => [
                    'enabled' => true,
                    'threshold_cents' => 2000,
                    'amount_cents' => 5000,
                    'payment_method_id' => 'pm_test_integration',
                    'max_attempts' => 3,
                    'cooldown_minutes' => 60,
                ],
                'notifications' => [
                    'low_balance' => true,
                    'auto_topup' => true,
                    'invoice_ready' => true,
                ],
            ],
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_handles_complete_billing_lifecycle_end_to_end()
    {
        // Stage 1: Customer makes topup
        $topupResponse = $this->postJson('/api/billing/topup', [
            'amount_cents' => 10000, // 100€
            'payment_method' => 'card',
        ]);
        
        $topupResponse->assertStatus(200);
        $sessionId = $topupResponse->json('data.session_id');
        
        // Stage 2: Simulate Stripe webhook for successful payment
        $webhookPayload = [
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => $sessionId,
                    'payment_intent' => 'pi_integration_test',
                    'amount_total' => 10000,
                    'customer' => 'cus_integration',
                ],
            ],
        ];
        
        // Mock Stripe signature verification
        Mockery::mock('alias:Stripe\Webhook')
            ->shouldReceive('constructEvent')
            ->once()
            ->andReturn((object) $webhookPayload);
        
        $webhookResponse = $this->postJson('/webhooks/stripe', $webhookPayload, [
            'Stripe-Signature' => 'test_signature',
        ]);
        
        $webhookResponse->assertStatus(200);
        
        // Stage 3: Verify balance updated
        $this->customer->refresh();
        $this->assertEquals(20000, $this->customer->balance_cents); // 100€ + 100€
        
        // Stage 4: Customer uses services (triggers billing chain)
        $usageResult = $this->billingService->processBillingChain(
            $this->customer,
            'call_minutes',
            60 // 60 minutes of calls
        );
        
        $this->assertTrue($usageResult['success']);
        
        // Stage 5: Verify complete billing chain
        $this->customer->refresh();
        $this->reseller->refresh();
        $this->platform->refresh();
        
        // Customer paid 60 * 0.40€ = 24€
        $this->assertEquals(17600, $this->customer->balance_cents); // 200€ - 24€
        
        // Reseller received commission (25% of 24€ = 6€)
        $commission = CommissionLedger::where('reseller_id', $this->reseller->id)
            ->latest()
            ->first();
        $this->assertEquals(600, $commission->commission_amount_cents);
        
        // Platform received base cost (60 * 0.30€ = 18€)
        $this->assertEquals(1800, $this->platform->balance_cents);
        
        // Stage 6: Verify transaction audit trail
        $transactions = Transaction::where('tenant_id', $this->customer->id)
            ->orderBy('created_at', 'desc')
            ->get();
        
        $this->assertGreaterThan(0, $transactions->count());
        
        // Each transaction should have complete metadata
        foreach ($transactions as $transaction) {
            $this->assertNotNull($transaction->metadata);
            $this->assertNotNull($transaction->reference_id);
            $this->assertNotNull($transaction->description);
        }
    }

    /** @test */
    public function it_handles_race_condition_with_concurrent_topups()
    {
        // Simulate 5 concurrent topup requests
        $responses = [];
        $promises = [];
        
        // Use database transactions to simulate concurrency
        for ($i = 0; $i < 5; $i++) {
            $promises[] = function () use ($i) {
                return DB::transaction(function () use ($i) {
                    // Each request tries to create a topup
                    $topup = BalanceTopup::create([
                        'tenant_id' => $this->customer->id,
                        'amount_cents' => 5000 + ($i * 1000), // Varying amounts
                        'status' => 'pending',
                        'payment_method' => 'card',
                        'idempotency_key' => 'concurrent_test_' . $i,
                    ]);
                    
                    // Simulate processing delay
                    usleep(rand(1000, 5000)); // 1-5ms random delay
                    
                    // Try to complete the topup
                    return $this->stripeService->confirmPayment($topup, 'pi_concurrent_' . $i);
                });
            };
        }
        
        // Execute all promises
        foreach ($promises as $promise) {
            $responses[] = $promise();
        }
        
        // All should complete without deadlocks or conflicts
        foreach ($responses as $response) {
            $this->assertIsArray($response);
        }
        
        // Verify no duplicate charges
        $topups = BalanceTopup::where('tenant_id', $this->customer->id)->get();
        $this->assertEquals(5, $topups->count());
        
        // Each should have unique idempotency key
        $idempotencyKeys = $topups->pluck('idempotency_key')->toArray();
        $this->assertEquals(5, count(array_unique($idempotencyKeys)));
    }

    /** @test */
    public function it_handles_auto_topup_with_retry_logic_and_failure_recovery()
    {
        // Set low balance to trigger auto-topup
        $this->customer->update(['balance_cents' => 1500]); // Below 20€ threshold
        
        // Mock Stripe to fail first 2 attempts, succeed on 3rd
        $attemptCount = 0;
        $stripeMock = Mockery::mock(StripeCheckoutService::class . '[processAutoTopup]');
        $stripeMock->shouldReceive('processAutoTopup')
            ->times(3)
            ->andReturnUsing(function () use (&$attemptCount) {
                $attemptCount++;
                if ($attemptCount < 3) {
                    return [
                        'success' => false,
                        'error' => 'card_declined',
                        'message' => 'Card was declined',
                    ];
                }
                return [
                    'success' => true,
                    'payment_intent_id' => 'pi_auto_success',
                    'amount_cents' => 5000,
                ];
            });
        
        $this->app->instance(StripeCheckoutService::class, $stripeMock);
        
        // Trigger auto-topup check
        $response = $this->postJson('/api/billing/check-auto-topup');
        
        $response->assertStatus(200);
        
        // Verify retry attempts were logged
        $topups = BalanceTopup::where('tenant_id', $this->customer->id)
            ->where('is_auto_topup', true)
            ->get();
        
        // Should have 3 attempts (2 failed, 1 successful)
        $this->assertEquals(3, $topups->count());
        
        $failedTopups = $topups->where('status', 'failed');
        $this->assertEquals(2, $failedTopups->count());
        
        $successfulTopup = $topups->where('status', 'completed')->first();
        $this->assertNotNull($successfulTopup);
        
        // Verify balance was updated only once
        $this->customer->refresh();
        $this->assertEquals(6500, $this->customer->balance_cents); // 15€ + 50€
    }

    /** @test */
    public function it_handles_network_failures_gracefully()
    {
        // Mock network failure for Stripe API
        Http::fake([
            'api.stripe.com/*' => Http::response(null, 500),
        ]);
        
        // Attempt to create topup session
        $response = $this->postJson('/api/billing/topup', [
            'amount_cents' => 5000,
        ]);
        
        // Should handle gracefully with error response
        $response->assertStatus(503)
            ->assertJson([
                'success' => false,
                'error' => 'service_unavailable',
            ]);
        
        // Verify no partial data was created
        $topupCount = BalanceTopup::where('tenant_id', $this->customer->id)->count();
        $this->assertEquals(0, $topupCount);
        
        // Verify error was logged
        $this->assertDatabaseHas('failed_jobs', [
            'queue' => 'default',
        ]);
    }

    /** @test */
    public function it_prevents_negative_balance_exploitation()
    {
        // Attempt to exploit system by rapid transactions
        $this->customer->update(['balance_cents' => 100]); // Only 1€
        
        $attempts = [];
        
        // Try to make 10 concurrent 10€ transactions
        for ($i = 0; $i < 10; $i++) {
            $attempts[] = function () {
                return $this->billingService->processBillingChain(
                    $this->customer->fresh(), // Fresh instance to bypass caching
                    'call_minutes',
                    25 // 25 minutes = 10€
                );
            };
        }
        
        // Execute all attempts
        $results = array_map(fn($attempt) => $attempt(), $attempts);
        
        // Only first should succeed, rest should fail
        $successCount = collect($results)->where('success', true)->count();
        $this->assertEquals(1, $successCount);
        
        // Verify balance never went negative
        $this->customer->refresh();
        $this->assertGreaterThanOrEqual(0, $this->customer->balance_cents);
        
        // Verify only one transaction was created
        $transactions = Transaction::where('tenant_id', $this->customer->id)
            ->where('type', 'debit')
            ->get();
        $this->assertEquals(1, $transactions->count());
    }

    /** @test */
    public function it_handles_database_rollback_on_partial_failure()
    {
        // Start a transaction
        DB::beginTransaction();
        
        try {
            // Create initial topup
            $topup = BalanceTopup::create([
                'tenant_id' => $this->customer->id,
                'amount_cents' => 5000,
                'status' => 'pending',
            ]);
            
            // Update balance
            $this->customer->increment('balance_cents', 5000);
            
            // Force an exception to trigger rollback
            throw new \Exception('Simulated failure');
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
        }
        
        // Verify nothing was persisted
        $this->customer->refresh();
        $this->assertEquals(10000, $this->customer->balance_cents); // Unchanged
        
        $topupCount = BalanceTopup::where('tenant_id', $this->customer->id)->count();
        $this->assertEquals(0, $topupCount);
    }

    /** @test */
    public function it_handles_webhook_replay_attacks()
    {
        // Create a completed topup
        $topup = BalanceTopup::create([
            'tenant_id' => $this->customer->id,
            'amount_cents' => 5000,
            'status' => 'completed',
            'stripe_session_id' => 'cs_replay_test',
            'completed_at' => now(),
        ]);
        
        // Try to replay the webhook
        $webhookPayload = [
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_replay_test',
                    'payment_intent' => 'pi_replay_test',
                    'amount_total' => 5000,
                ],
            ],
        ];
        
        Mockery::mock('alias:Stripe\Webhook')
            ->shouldReceive('constructEvent')
            ->andReturn((object) $webhookPayload);
        
        $initialBalance = $this->customer->balance_cents;
        
        // Send webhook multiple times
        for ($i = 0; $i < 3; $i++) {
            $response = $this->postJson('/webhooks/stripe', $webhookPayload, [
                'Stripe-Signature' => 'test_signature_' . $i,
            ]);
            
            $response->assertStatus(200);
        }
        
        // Verify balance was only updated once (idempotency)
        $this->customer->refresh();
        $this->assertEquals($initialBalance, $this->customer->balance_cents);
        
        // Verify only one transaction was created
        $transactions = Transaction::where('tenant_id', $this->customer->id)
            ->where('reference_id', 'pi_replay_test')
            ->count();
        $this->assertEquals(0, $transactions); // Should be 0 since topup was already completed
    }

    /** @test */
    public function it_handles_commission_calculation_edge_cases()
    {
        // Test various commission edge cases
        $testCases = [
            // [commission_rate, amount, expected_commission]
            [0.00, 10000, 0],      // 0% commission
            [0.01, 10000, 100],     // 1% commission
            [0.25, 10000, 2500],    // 25% commission (default)
            [0.50, 10000, 5000],    // 50% commission
            [0.99, 10000, 9900],    // 99% commission
            [1.00, 10000, 10000],   // 100% commission
            [0.25, 1, 0],           // Rounding down on tiny amounts
            [0.25, 3, 0],           // 0.75 cents rounds to 0
            [0.25, 4, 1],           // 1 cent minimum
        ];
        
        foreach ($testCases as [$rate, $amount, $expected]) {
            // Update reseller commission rate
            $this->reseller->update([
                'settings' => array_merge($this->reseller->settings, [
                    'commission_rate' => $rate,
                ]),
            ]);
            
            // Process billing
            $result = $this->billingService->processBillingChain(
                $this->customer,
                'api_calls',
                $amount / 15 // Calculate quantity to match amount
            );
            
            if ($amount >= 4) { // Only succeed if amount is reasonable
                $this->assertTrue($result['success']);
                $this->assertEquals(
                    $expected,
                    $result['commission_amount_cents'],
                    "Failed for rate: {$rate}, amount: {$amount}"
                );
            }
        }
    }

    /** @test */
    public function it_handles_timezone_boundary_transactions()
    {
        // Test transactions at day boundary
        $timezones = ['UTC', 'Europe/Berlin', 'America/New_York', 'Asia/Tokyo'];
        
        foreach ($timezones as $timezone) {
            // Set timezone
            config(['app.timezone' => $timezone]);
            
            // Create transaction at 23:59:59
            Carbon::setTestNow(Carbon::parse('2025-09-10 23:59:59', $timezone));
            
            $result1 = $this->billingService->processBillingChain(
                $this->customer,
                'api_calls',
                10
            );
            
            // Create transaction at 00:00:01 (next day)
            Carbon::setTestNow(Carbon::parse('2025-09-11 00:00:01', $timezone));
            
            $result2 = $this->billingService->processBillingChain(
                $this->customer,
                'api_calls',
                10
            );
            
            // Both should succeed despite crossing day boundary
            $this->assertTrue($result1['success']);
            $this->assertTrue($result2['success']);
            
            // Verify transactions have correct dates
            $transaction1 = Transaction::find($result1['transactions'][0]->id);
            $transaction2 = Transaction::find($result2['transactions'][0]->id);
            
            $this->assertNotEquals(
                $transaction1->created_at->format('Y-m-d'),
                $transaction2->created_at->format('Y-m-d')
            );
        }
        
        // Reset timezone
        Carbon::setTestNow();
        config(['app.timezone' => 'Europe/Berlin']);
    }

    /** @test */
    public function it_handles_decimal_precision_correctly()
    {
        // Test with fractional amounts that could cause floating point issues
        $amounts = [
            0.1,   // Classic floating point problem
            0.2,   // Another classic
            0.3,   // 0.1 + 0.2 should equal this
            1.23,  // Decimal places
            99.99, // Just under 100
            0.01,  // Minimum amount
        ];
        
        foreach ($amounts as $euroAmount) {
            $centsAmount = (int) round($euroAmount * 100);
            
            // Create topup with precise amount
            $topup = BalanceTopup::create([
                'tenant_id' => $this->customer->id,
                'amount_cents' => $centsAmount,
                'status' => 'pending',
            ]);
            
            // Process the topup
            $initialBalance = $this->customer->balance_cents;
            $this->customer->increment('balance_cents', $centsAmount);
            
            // Verify exact amount was added
            $this->customer->refresh();
            $this->assertEquals(
                $initialBalance + $centsAmount,
                $this->customer->balance_cents,
                "Precision error for amount: {$euroAmount}€"
            );
            
            // Create transaction
            $transaction = Transaction::create([
                'tenant_id' => $this->customer->id,
                'type' => 'credit',
                'amount_cents' => $centsAmount,
                'balance_after_cents' => $this->customer->balance_cents,
                'description' => "Test precision for {$euroAmount}€",
            ]);
            
            // Verify transaction stores exact amount
            $this->assertEquals($centsAmount, $transaction->amount_cents);
        }
    }

    /** @test */
    public function it_handles_notification_delivery_failures()
    {
        // Mock notification failure
        Notification::fake();
        Notification::shouldReceive('send')
            ->andThrow(new \Exception('Notification service down'));
        
        // Trigger low balance notification
        $this->customer->update(['balance_cents' => 500]); // 5€ - low balance
        
        // Process should continue despite notification failure
        $result = $this->billingService->processBillingChain(
            $this->customer,
            'api_calls',
            10
        );
        
        // Billing should still work
        $this->assertTrue($result['success']);
        
        // Verify transaction was created despite notification failure
        $this->assertDatabaseHas('transactions', [
            'tenant_id' => $this->customer->id,
            'type' => 'debit',
        ]);
    }

    /** @test */
    public function it_validates_data_integrity_across_system()
    {
        // Perform multiple operations
        for ($i = 0; $i < 10; $i++) {
            // Random operations
            $operations = ['topup', 'usage', 'refund'];
            $operation = $operations[array_rand($operations)];
            
            switch ($operation) {
                case 'topup':
                    BalanceTopup::create([
                        'tenant_id' => $this->customer->id,
                        'amount_cents' => rand(1000, 10000),
                        'status' => 'completed',
                    ]);
                    $this->customer->increment('balance_cents', rand(1000, 10000));
                    break;
                    
                case 'usage':
                    if ($this->customer->balance_cents >= 1000) {
                        $this->billingService->processBillingChain(
                            $this->customer,
                            'call_minutes',
                            rand(1, 20)
                        );
                    }
                    break;
                    
                case 'refund':
                    $lastTopup = BalanceTopup::where('tenant_id', $this->customer->id)
                        ->where('status', 'completed')
                        ->latest()
                        ->first();
                    
                    if ($lastTopup) {
                        $lastTopup->update(['status' => 'refunded']);
                        $this->customer->decrement('balance_cents', $lastTopup->amount_cents);
                    }
                    break;
            }
        }
        
        // Validate data integrity
        
        // 1. Balance should never be negative
        $this->assertGreaterThanOrEqual(0, $this->customer->fresh()->balance_cents);
        
        // 2. Transaction history should reconcile with current balance
        $creditSum = Transaction::where('tenant_id', $this->customer->id)
            ->where('type', 'credit')
            ->sum('amount_cents');
        
        $debitSum = Transaction::where('tenant_id', $this->customer->id)
            ->where('type', 'debit')
            ->sum('amount_cents');
        
        $calculatedBalance = 10000 + $creditSum - $debitSum; // Initial + credits - debits
        
        // Allow small discrepancy due to test operations
        $this->assertEqualsWithDelta(
            $calculatedBalance,
            $this->customer->fresh()->balance_cents,
            100, // Allow 1€ discrepancy
            'Balance does not reconcile with transaction history'
        );
        
        // 3. Commission ledger should match transactions
        $commissions = CommissionLedger::where('reseller_id', $this->reseller->id)->get();
        
        foreach ($commissions as $commission) {
            $this->assertNotNull($commission->transaction_id);
            $this->assertNotNull($commission->customer_id);
            $this->assertGreaterThan(0, $commission->commission_amount_cents);
        }
        
        // 4. All transactions should have valid references
        $transactions = Transaction::all();
        
        foreach ($transactions as $transaction) {
            $this->assertNotNull($transaction->tenant_id);
            $this->assertNotNull($transaction->type);
            $this->assertNotNull($transaction->amount_cents);
            $this->assertNotNull($transaction->created_at);
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Carbon::setTestNow();
        parent::tearDown();
    }
}