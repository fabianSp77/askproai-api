<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\BillingChainService;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\CommissionLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Cache;

class BillingChainServiceTest extends TestCase
{
    use RefreshDatabase;

    private BillingChainService $service;
    private Tenant $platform;
    private Tenant $reseller;
    private Tenant $customer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new BillingChainService();
        
        // Create test tenant hierarchy
        $this->platform = Tenant::create([
            'id' => 'platform-test',
            'name' => 'Test Platform',
            'tenant_type' => 'platform',
            'balance_cents' => 0,
            'settings' => [],
            'is_active' => true,
        ]);
        
        $this->reseller = Tenant::create([
            'id' => 'reseller-test',
            'name' => 'Test Reseller',
            'tenant_type' => 'reseller',
            'parent_id' => $this->platform->id,
            'balance_cents' => 100000, // 1000€
            'settings' => [
                'pricing' => [
                    'call_minutes' => 40, // 0.40€ per minute
                    'api_calls' => 15,
                    'appointments' => 150,
                ],
                'commission_rate' => 0.25,
            ],
            'is_active' => true,
        ]);
        
        $this->customer = Tenant::create([
            'id' => 'customer-test',
            'name' => 'Test Customer',
            'tenant_type' => 'reseller_customer',
            'parent_id' => $this->reseller->id,
            'balance_cents' => 5000, // 50€
            'settings' => [],
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_processes_complete_billing_chain_for_reseller_customer()
    {
        // Act: Process 10 minutes of calls
        $result = $this->service->processBillingChain($this->customer, 'call_minutes', 10);
        
        // Assert: Check the result structure
        $this->assertTrue($result['success']);
        $this->assertCount(4, $result['transactions']);
        $this->assertEquals(1000, $result['commission_amount_cents']); // 10€ commission
        
        // Verify customer balance decreased
        $this->customer->refresh();
        $this->assertEquals(1000, $this->customer->balance_cents); // 50€ - 40€ = 10€
        
        // Verify reseller balance (receives payment minus platform cost)
        $this->reseller->refresh();
        $this->assertEquals(101000, $this->reseller->balance_cents); // 1000€ + 10€ commission
        
        // Verify platform balance increased
        $this->platform->refresh();
        $this->assertEquals(3000, $this->platform->balance_cents); // 30€ platform cost
        
        // Verify commission ledger entry
        $commission = CommissionLedger::where('reseller_id', $this->reseller->id)->first();
        $this->assertNotNull($commission);
        $this->assertEquals(1000, $commission->commission_amount_cents);
    }

    /** @test */
    public function it_handles_insufficient_balance_gracefully()
    {
        // Arrange: Set customer balance to 1€ only
        $this->customer->update(['balance_cents' => 100]);
        
        // Act: Try to process 10 minutes (costs 40€)
        $result = $this->service->processBillingChain($this->customer, 'call_minutes', 10);
        
        // Assert: Should fail with proper error
        $this->assertFalse($result['success']);
        $this->assertEquals('insufficient_balance', $result['error']);
        $this->assertStringContainsString('Unzureichendes Guthaben', $result['message']);
        
        // Verify no transactions were created
        $this->assertEquals(0, Transaction::count());
        
        // Verify balances unchanged
        $this->customer->refresh();
        $this->assertEquals(100, $this->customer->balance_cents);
    }

    /** @test */
    public function it_rolls_back_on_transaction_failure()
    {
        // Arrange: Mock a database exception during transaction
        DB::shouldReceive('transaction')
            ->once()
            ->andThrow(new \Exception('Database error'));
        
        // Act: Attempt to process billing
        $result = $this->service->processBillingChain($this->customer, 'call_minutes', 10);
        
        // Assert: Should handle failure gracefully
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Database error', $result['message']);
        
        // Verify no partial data was saved
        $this->assertEquals(0, Transaction::count());
        $this->assertEquals(0, CommissionLedger::count());
    }

    /** @test */
    public function it_calculates_correct_amounts_for_different_services()
    {
        $testCases = [
            ['service' => 'call_minutes', 'quantity' => 5, 'expected_cost' => 200],
            ['service' => 'api_calls', 'quantity' => 100, 'expected_cost' => 1500],
            ['service' => 'appointments', 'quantity' => 3, 'expected_cost' => 450],
            ['service' => 'sms_messages', 'quantity' => 50, 'expected_cost' => 400],
        ];
        
        foreach ($testCases as $case) {
            // Reset customer balance
            $this->customer->update(['balance_cents' => 10000]);
            
            // Process billing
            $result = $this->service->processBillingChain(
                $this->customer, 
                $case['service'], 
                $case['quantity']
            );
            
            // Verify cost calculation
            $this->assertTrue($result['success'], "Failed for service: {$case['service']}");
            $this->assertEquals(
                $case['expected_cost'], 
                $result['total_amount_cents'],
                "Incorrect amount for {$case['service']}"
            );
        }
    }

    /** @test */
    public function it_handles_direct_customer_without_billing_chain()
    {
        // Arrange: Create direct customer (no reseller)
        $directCustomer = Tenant::create([
            'id' => 'direct-customer',
            'name' => 'Direct Customer',
            'tenant_type' => 'direct_customer',
            'parent_id' => $this->platform->id,
            'balance_cents' => 5000,
            'settings' => [],
            'is_active' => true,
        ]);
        
        // Act: Process billing for direct customer
        $result = $this->service->processBillingChain($directCustomer, 'call_minutes', 10);
        
        // Assert: Should create only 2 transactions (no commission)
        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['transactions']);
        $this->assertEquals(0, $result['commission_amount_cents']);
        
        // Verify balances
        $directCustomer->refresh();
        $this->assertEquals(2000, $directCustomer->balance_cents); // 50€ - 30€
        
        $this->platform->refresh();
        $this->assertEquals(3000, $this->platform->balance_cents); // 30€
    }

    /** @test */
    public function it_respects_custom_commission_rates()
    {
        // Arrange: Set custom commission rate
        $this->reseller->update([
            'settings' => array_merge($this->reseller->settings, [
                'commission_rate' => 0.35, // 35% commission
            ])
        ]);
        
        // Act: Process billing
        $result = $this->service->processBillingChain($this->customer, 'call_minutes', 10);
        
        // Assert: Verify 35% commission calculated
        $this->assertTrue($result['success']);
        $this->assertEquals(1400, $result['commission_amount_cents']); // 14€ (35% of 40€)
    }

    /** @test */
    public function it_handles_concurrent_billing_operations()
    {
        // This tests race condition prevention
        $results = [];
        
        // Simulate concurrent requests
        for ($i = 0; $i < 3; $i++) {
            $results[] = $this->service->processBillingChain(
                $this->customer, 
                'call_minutes', 
                1
            );
        }
        
        // All should succeed without race conditions
        foreach ($results as $result) {
            $this->assertTrue($result['success']);
        }
        
        // Verify final balance is correct
        $this->customer->refresh();
        $this->assertEquals(3880, $this->customer->balance_cents); // 50€ - 3 × 0.40€
    }

    /** @test */
    public function it_creates_proper_audit_trail()
    {
        // Act: Process billing
        $result = $this->service->processBillingChain($this->customer, 'call_minutes', 10);
        
        // Assert: Verify all transactions have proper metadata
        $transactions = Transaction::all();
        
        foreach ($transactions as $transaction) {
            $this->assertNotNull($transaction->metadata);
            $this->assertArrayHasKey('service_type', $transaction->metadata);
            $this->assertArrayHasKey('quantity', $transaction->metadata);
            $this->assertArrayHasKey('unit_price', $transaction->metadata);
            $this->assertNotNull($transaction->description);
            $this->assertNotNull($transaction->reference_id);
        }
        
        // Verify transaction relationships
        $customerTransaction = Transaction::where('tenant_id', $this->customer->id)
            ->where('type', 'debit')
            ->first();
        
        $this->assertNotNull($customerTransaction->related_transaction_id);
    }

    /** @test */
    public function it_handles_zero_quantity_correctly()
    {
        // Act: Process zero quantity
        $result = $this->service->processBillingChain($this->customer, 'call_minutes', 0);
        
        // Assert: Should handle gracefully
        $this->assertFalse($result['success']);
        $this->assertEquals('invalid_quantity', $result['error']);
    }

    /** @test */
    public function it_validates_service_types()
    {
        // Act: Try invalid service type
        $result = $this->service->processBillingChain($this->customer, 'invalid_service', 10);
        
        // Assert: Should reject invalid service
        $this->assertFalse($result['success']);
        $this->assertEquals('invalid_service_type', $result['error']);
    }

    /** @test */
    public function it_caches_billing_calculations()
    {
        // First call - should cache
        $result1 = $this->service->processBillingChain($this->customer, 'call_minutes', 10);
        
        // Second identical call within cache period
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn($result1);
        
        $result2 = $this->service->processBillingChain($this->customer, 'call_minutes', 10);
        
        // Results should be identical (from cache)
        $this->assertEquals($result1['total_amount_cents'], $result2['total_amount_cents']);
    }

    /** @test */
    public function it_handles_decimal_quantities_properly()
    {
        // Act: Process fractional minutes
        $result = $this->service->processBillingChain($this->customer, 'call_minutes', 2.5);
        
        // Assert: Should calculate correctly (2.5 × 40 = 100 cents)
        $this->assertTrue($result['success']);
        $this->assertEquals(100, $result['total_amount_cents']);
    }

    /** @test */
    public function it_emits_proper_events()
    {
        // Arrange: Set up event fake
        Event::fake();
        
        // Act: Process billing
        $this->service->processBillingChain($this->customer, 'call_minutes', 10);
        
        // Assert: Verify events were dispatched
        Event::assertDispatched('billing.chain.processed');
        Event::assertDispatched('transaction.created');
        Event::assertDispatched('commission.earned');
    }

    /** @test */
    public function it_handles_inactive_tenant_correctly()
    {
        // Arrange: Deactivate customer
        $this->customer->update(['is_active' => false]);
        
        // Act: Try to process billing
        $result = $this->service->processBillingChain($this->customer, 'call_minutes', 10);
        
        // Assert: Should reject inactive tenant
        $this->assertFalse($result['success']);
        $this->assertEquals('tenant_inactive', $result['error']);
    }
}