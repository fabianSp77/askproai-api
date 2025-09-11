<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Tenant;
use App\Models\BalanceTopup;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Mockery;

class BillingControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test tenant
        $this->tenant = Tenant::create([
            'id' => 'test-tenant-api',
            'name' => 'API Test Tenant',
            'tenant_type' => 'direct_customer',
            'balance_cents' => 5000,
            'settings' => [
                'auto_topup' => [
                    'enabled' => true,
                    'threshold_cents' => 1000,
                    'amount_cents' => 5000,
                ],
            ],
            'is_active' => true,
        ]);
        
        // Create test user
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'billing@test.de',
        ]);
        
        // Authenticate user
        Sanctum::actingAs($this->user, ['*']);
    }

    /** @test */
    public function it_can_get_current_balance()
    {
        // Act: Request balance
        $response = $this->getJson('/api/billing/balance');
        
        // Assert: Check response
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'balance_cents' => 5000,
                    'balance_formatted' => '50,00 €',
                    'currency' => 'EUR',
                    'low_balance' => false,
                    'auto_topup_enabled' => true,
                ],
            ]);
    }

    /** @test */
    public function it_can_list_transactions_with_pagination()
    {
        // Arrange: Create test transactions
        for ($i = 1; $i <= 25; $i++) {
            Transaction::create([
                'tenant_id' => $this->tenant->id,
                'type' => $i % 2 == 0 ? 'credit' : 'debit',
                'amount_cents' => 1000 * $i,
                'balance_after_cents' => 5000 + (1000 * $i),
                'description' => "Test Transaction {$i}",
                'reference_id' => "ref_{$i}",
                'metadata' => ['test' => true],
            ]);
        }
        
        // Act: Request transactions
        $response = $this->getJson('/api/billing/transactions?page=1&per_page=10');
        
        // Assert: Check pagination
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'transactions' => [
                        '*' => [
                            'id',
                            'type',
                            'amount_cents',
                            'amount_formatted',
                            'balance_after_cents',
                            'balance_after_formatted',
                            'description',
                            'created_at',
                        ],
                    ],
                    'pagination' => [
                        'current_page',
                        'total_pages',
                        'per_page',
                        'total',
                    ],
                ],
            ])
            ->assertJsonPath('data.pagination.total', 25)
            ->assertJsonPath('data.pagination.per_page', 10)
            ->assertJsonCount(10, 'data.transactions');
    }

    /** @test */
    public function it_can_initiate_topup_with_idempotency()
    {
        // Arrange: Mock Stripe service
        $stripeMock = Mockery::mock('App\Services\StripeCheckoutService');
        $stripeMock->shouldReceive('createTopupSession')
            ->once()
            ->andReturn([
                'success' => true,
                'session_id' => 'cs_test_123',
                'checkout_url' => 'https://checkout.stripe.com/pay/cs_test_123',
            ]);
        
        $this->app->instance('App\Services\StripeCheckoutService', $stripeMock);
        
        // Act: Request topup
        $response = $this->postJson('/api/billing/topup', [
            'amount_cents' => 10000,
            'payment_method' => 'card',
        ], [
            'Idempotency-Key' => 'unique-key-123',
        ]);
        
        // Assert: Check response
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'session_id' => 'cs_test_123',
                    'checkout_url' => 'https://checkout.stripe.com/pay/cs_test_123',
                ],
            ]);
        
        // Verify topup record created
        $topup = BalanceTopup::where('tenant_id', $this->tenant->id)->first();
        $this->assertNotNull($topup);
        $this->assertEquals(10000, $topup->amount_cents);
        $this->assertEquals('pending', $topup->status);
    }

    /** @test */
    public function it_validates_topup_amount_limits()
    {
        // Test below minimum
        $response = $this->postJson('/api/billing/topup', [
            'amount_cents' => 500, // 5€ - below minimum
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount_cents']);
        
        // Test above maximum
        $response = $this->postJson('/api/billing/topup', [
            'amount_cents' => 200000, // 2000€ - above maximum
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount_cents']);
        
        // Test valid amount
        $stripeMock = Mockery::mock('App\Services\StripeCheckoutService');
        $stripeMock->shouldReceive('createTopupSession')
            ->andReturn(['success' => true, 'session_id' => 'cs_valid']);
        
        $this->app->instance('App\Services\StripeCheckoutService', $stripeMock);
        
        $response = $this->postJson('/api/billing/topup', [
            'amount_cents' => 5000, // 50€ - valid
        ]);
        
        $response->assertStatus(200);
    }

    /** @test */
    public function it_handles_stripe_webhook_with_signature_verification()
    {
        // Arrange: Create pending topup
        $topup = BalanceTopup::create([
            'tenant_id' => $this->tenant->id,
            'amount_cents' => 5000,
            'status' => 'pending',
            'stripe_session_id' => 'cs_webhook_test',
        ]);
        
        // Mock Stripe webhook verification
        Mockery::mock('alias:Stripe\Webhook')
            ->shouldReceive('constructEvent')
            ->once()
            ->andReturn((object)[
                'type' => 'checkout.session.completed',
                'data' => (object)[
                    'object' => (object)[
                        'id' => 'cs_webhook_test',
                        'payment_intent' => 'pi_webhook_test',
                        'amount_total' => 5000,
                        'customer' => 'cus_test',
                    ],
                ],
            ]);
        
        // Act: Send webhook
        $response = $this->postJson('/webhooks/stripe', [
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_webhook_test',
                    'payment_intent' => 'pi_webhook_test',
                    'amount_total' => 5000,
                ],
            ],
        ], [
            'Stripe-Signature' => 'test_signature',
        ]);
        
        // Assert: Webhook processed
        $response->assertStatus(200)
            ->assertJson(['success' => true]);
        
        // Verify topup completed
        $topup->refresh();
        $this->assertEquals('completed', $topup->status);
        
        // Verify balance updated
        $this->tenant->refresh();
        $this->assertEquals(10000, $this->tenant->balance_cents); // 50€ + 50€
    }

    /** @test */
    public function it_rejects_webhook_with_invalid_signature()
    {
        // Mock failed signature verification
        Mockery::mock('alias:Stripe\Webhook')
            ->shouldReceive('constructEvent')
            ->andThrow(new \Stripe\Exception\SignatureVerificationException('Invalid signature'));
        
        // Act: Send webhook with bad signature
        $response = $this->postJson('/webhooks/stripe', [
            'type' => 'checkout.session.completed',
        ], [
            'Stripe-Signature' => 'invalid_signature',
        ]);
        
        // Assert: Webhook rejected
        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Invalid signature',
            ]);
    }

    /** @test */
    public function it_can_download_invoice_pdf()
    {
        // Arrange: Create completed topup with invoice
        $topup = BalanceTopup::create([
            'tenant_id' => $this->tenant->id,
            'amount_cents' => 10000,
            'bonus_cents' => 500,
            'status' => 'completed',
            'invoice_number' => 'INV-2025-001',
            'completed_at' => now(),
        ]);
        
        // Act: Request invoice download
        $response = $this->get("/api/billing/topups/{$topup->id}/invoice");
        
        // Assert: PDF response
        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="INV-2025-001.pdf"');
    }

    /** @test */
    public function it_can_get_topup_history()
    {
        // Arrange: Create multiple topups
        for ($i = 1; $i <= 5; $i++) {
            BalanceTopup::create([
                'tenant_id' => $this->tenant->id,
                'amount_cents' => 5000 * $i,
                'bonus_cents' => 250 * $i,
                'status' => $i % 2 == 0 ? 'completed' : 'failed',
                'payment_method' => 'card',
                'created_at' => now()->subDays($i),
            ]);
        }
        
        // Act: Request history
        $response = $this->getJson('/api/billing/topups');
        
        // Assert: Check response
        $response->assertStatus(200)
            ->assertJsonCount(5, 'data.topups')
            ->assertJsonStructure([
                'success',
                'data' => [
                    'topups' => [
                        '*' => [
                            'id',
                            'amount_cents',
                            'amount_formatted',
                            'bonus_cents',
                            'bonus_formatted',
                            'total_formatted',
                            'status',
                            'payment_method',
                            'created_at',
                        ],
                    ],
                    'summary' => [
                        'total_topped_up',
                        'total_bonuses',
                        'successful_count',
                        'failed_count',
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_can_update_auto_topup_settings()
    {
        // Act: Update settings
        $response = $this->putJson('/api/billing/auto-topup', [
            'enabled' => true,
            'threshold_cents' => 2000,
            'amount_cents' => 10000,
        ]);
        
        // Assert: Settings updated
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Auto-Topup-Einstellungen aktualisiert',
            ]);
        
        // Verify in database
        $this->tenant->refresh();
        $this->assertEquals(2000, $this->tenant->settings['auto_topup']['threshold_cents']);
        $this->assertEquals(10000, $this->tenant->settings['auto_topup']['amount_cents']);
    }

    /** @test */
    public function it_triggers_auto_topup_when_balance_low()
    {
        // Arrange: Set low balance
        $this->tenant->update([
            'balance_cents' => 800, // Below 10€ threshold
            'settings' => array_merge($this->tenant->settings, [
                'auto_topup' => [
                    'enabled' => true,
                    'threshold_cents' => 1000,
                    'amount_cents' => 5000,
                    'payment_method_id' => 'pm_test',
                ],
            ]),
        ]);
        
        // Mock Stripe off-session payment
        $stripeMock = Mockery::mock('App\Services\StripeCheckoutService');
        $stripeMock->shouldReceive('processAutoTopup')
            ->once()
            ->andReturn([
                'success' => true,
                'payment_intent_id' => 'pi_auto_123',
                'amount_cents' => 5000,
            ]);
        
        $this->app->instance('App\Services\StripeCheckoutService', $stripeMock);
        
        // Act: Trigger auto-topup check
        $response = $this->postJson('/api/billing/check-auto-topup');
        
        // Assert: Auto-topup triggered
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'triggered' => true,
                    'amount_cents' => 5000,
                ],
            ]);
    }

    /** @test */
    public function it_exports_transactions_as_csv()
    {
        // Arrange: Create transactions
        for ($i = 1; $i <= 10; $i++) {
            Transaction::create([
                'tenant_id' => $this->tenant->id,
                'type' => $i % 2 == 0 ? 'credit' : 'debit',
                'amount_cents' => 1000 * $i,
                'description' => "Transaction {$i}",
                'created_at' => now()->subDays($i),
            ]);
        }
        
        // Act: Request CSV export
        $response = $this->get('/api/billing/transactions/export?format=csv&from=2025-01-01&to=2025-12-31');
        
        // Assert: CSV response
        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv')
            ->assertHeader('Content-Disposition', 'attachment; filename="transactions_2025-01-01_2025-12-31.csv"');
        
        // Verify CSV content
        $content = $response->getContent();
        $this->assertStringContainsString('Datum,Typ,Betrag,Beschreibung,Saldo', $content);
        $this->assertStringContainsString('Transaction', $content);
    }

    /** @test */
    public function it_handles_concurrent_topup_requests()
    {
        // Simulate race condition with multiple concurrent topup requests
        $responses = [];
        
        // Mock Stripe to return unique session IDs
        $stripeMock = Mockery::mock('App\Services\StripeCheckoutService');
        $stripeMock->shouldReceive('createTopupSession')
            ->times(3)
            ->andReturnUsing(function () {
                static $counter = 0;
                $counter++;
                return [
                    'success' => true,
                    'session_id' => "cs_concurrent_{$counter}",
                    'checkout_url' => "https://checkout.stripe.com/pay/cs_concurrent_{$counter}",
                ];
            });
        
        $this->app->instance('App\Services\StripeCheckoutService', $stripeMock);
        
        // Send concurrent requests
        for ($i = 0; $i < 3; $i++) {
            $responses[] = $this->postJson('/api/billing/topup', [
                'amount_cents' => 5000,
            ]);
        }
        
        // All should succeed without conflicts
        foreach ($responses as $response) {
            $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data' => ['session_id']]);
        }
        
        // Verify all topups created
        $topups = BalanceTopup::where('tenant_id', $this->tenant->id)->get();
        $this->assertCount(3, $topups);
    }

    /** @test */
    public function it_requires_authentication()
    {
        // Logout
        auth()->logout();
        
        // Test endpoints without authentication
        $endpoints = [
            ['GET', '/api/billing/balance'],
            ['GET', '/api/billing/transactions'],
            ['POST', '/api/billing/topup'],
            ['GET', '/api/billing/topups'],
            ['PUT', '/api/billing/auto-topup'],
        ];
        
        foreach ($endpoints as [$method, $url]) {
            $response = $this->json($method, $url);
            $response->assertStatus(401);
        }
    }

    /** @test */
    public function it_enforces_rate_limiting()
    {
        // Send multiple rapid requests
        for ($i = 0; $i < 65; $i++) {
            $response = $this->getJson('/api/billing/balance');
            
            if ($i < 60) {
                $response->assertStatus(200);
            } else {
                // Should be rate limited after 60 requests per minute
                $response->assertStatus(429)
                    ->assertJsonStructure(['message']);
            }
        }
    }
}