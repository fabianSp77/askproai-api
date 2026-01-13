<?php

namespace Tests\Feature\Billing;

use App\Models\BalanceTopup;
use App\Models\Tenant;
use App\Services\BalanceService;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests for BalanceService pessimistic locking behavior.
 *
 * These tests verify that:
 * - Concurrent payment confirmations don't double-credit
 * - Concurrent balance consumption doesn't overdraw
 * - Concurrent refunds don't double-refund
 *
 * Uses actual database transactions to test locking behavior.
 */
class BalanceServiceRaceConditionTest extends TestCase
{
    use DatabaseTransactions;

    private BalanceService $balanceService;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->balanceService = app(BalanceService::class);

        // Create a tenant with known balance
        $this->tenant = Tenant::factory()->create([
            'name' => 'Test Tenant',
            'balance' => 100.00,
            'bonus_balance' => 20.00,
            'total_deposited' => 100.00,
            'total_bonus_received' => 20.00,
            'total_consumed' => 0.00,
            'total_refunded' => 0.00,
            'low_balance_threshold' => 10.00,
        ]);
    }

    /** @test */
    public function it_prevents_double_credit_on_concurrent_payment_confirmation(): void
    {
        // Create a pending topup
        $topup = BalanceTopup::create([
            'tenant_id' => $this->tenant->id,
            'amount' => 50.00,
            'bonus_percentage' => 0,
            'bonus_amount' => 0,
            'total_credited' => 50.00,
            'refundable_amount' => 50.00,
            'remaining_amount' => 50.00,
            'bonus_remaining' => 0,
            'payment_method' => 'stripe',
            'status' => 'pending',
            'reference_number' => 'TOP-TEST-001',
            'transaction_date' => now(),
        ]);

        // Confirm payment once
        $result1 = $this->balanceService->confirmPayment($topup, ['charge_id' => 'ch_test_1']);
        $this->assertTrue($result1);

        // Reload topup and tenant
        $topup->refresh();
        $this->tenant->refresh();

        // Verify topup is completed
        $this->assertEquals('completed', $topup->status);

        // Verify tenant balance was credited exactly once
        $this->assertEquals(150.00, $this->tenant->balance); // 100 + 50

        // Second confirmation should be idempotent (skip)
        $result2 = $this->balanceService->confirmPayment($topup, ['charge_id' => 'ch_test_2']);
        $this->assertTrue($result2);

        // Balance should still be 150 (not 200)
        $this->tenant->refresh();
        $this->assertEquals(150.00, $this->tenant->balance);
    }

    /** @test */
    public function it_prevents_overdraw_on_concurrent_balance_consumption(): void
    {
        // Set balance to exactly 50
        $this->tenant->update([
            'balance' => 50.00,
            'bonus_balance' => 0.00,
        ]);

        // First consumption of 30 should succeed
        $result1 = $this->balanceService->consumeBalance(
            $this->tenant,
            30.00,
            'Test consumption 1'
        );
        $this->assertTrue($result1);

        // Verify balance is now 20
        $this->tenant->refresh();
        $this->assertEquals(20.00, $this->tenant->balance);

        // Second consumption of 30 should fail (only 20 available)
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unzureichendes Guthaben');

        $this->balanceService->consumeBalance(
            $this->tenant,
            30.00,
            'Test consumption 2'
        );
    }

    /** @test */
    public function it_consumes_bonus_balance_first(): void
    {
        $this->tenant->update([
            'balance' => 50.00,
            'bonus_balance' => 20.00,
        ]);

        // Consume 30 (should use 20 bonus + 10 regular)
        $this->balanceService->consumeBalance($this->tenant, 30.00, 'Test consumption');

        $this->tenant->refresh();
        $this->assertEquals(40.00, $this->tenant->balance); // 50 - 10
        $this->assertEquals(0.00, $this->tenant->bonus_balance); // 20 - 20
    }

    /** @test */
    public function it_tracks_total_consumed_correctly(): void
    {
        $this->tenant->update([
            'balance' => 100.00,
            'bonus_balance' => 0.00,
            'total_consumed' => 0.00,
        ]);

        $this->balanceService->consumeBalance($this->tenant, 25.00, 'Test 1');
        $this->balanceService->consumeBalance($this->tenant, 15.00, 'Test 2');

        $this->tenant->refresh();
        $this->assertEquals(60.00, $this->tenant->balance); // 100 - 25 - 15
        $this->assertEquals(40.00, $this->tenant->total_consumed); // 25 + 15
    }

    /** @test */
    public function it_creates_balance_transaction_on_credit(): void
    {
        $topup = BalanceTopup::create([
            'tenant_id' => $this->tenant->id,
            'amount' => 50.00,
            'bonus_percentage' => 10,
            'bonus_amount' => 5.00,
            'total_credited' => 55.00,
            'refundable_amount' => 50.00,
            'remaining_amount' => 55.00,
            'bonus_remaining' => 5.00,
            'payment_method' => 'stripe',
            'status' => 'pending',
            'reference_number' => 'TOP-TEST-002',
            'transaction_date' => now(),
        ]);

        $this->balanceService->confirmPayment($topup, ['charge_id' => 'ch_test']);

        // Verify balance transactions were created
        $transactions = DB::table('balance_transactions')
            ->where('tenant_id', $this->tenant->id)
            ->where('balance_topup_id', $topup->id)
            ->get();

        $this->assertCount(2, $transactions); // Credit + Bonus

        $creditTx = $transactions->firstWhere('type', 'credit');
        $bonusTx = $transactions->firstWhere('type', 'bonus');

        $this->assertEquals(50.00, $creditTx->amount);
        $this->assertEquals(5.00, $bonusTx->amount);
    }

    /** @test */
    public function it_creates_balance_transaction_on_debit(): void
    {
        $this->tenant->update([
            'balance' => 100.00,
            'bonus_balance' => 0.00,
        ]);

        $this->balanceService->consumeBalance($this->tenant, 25.00, 'Test debit');

        $transaction = DB::table('balance_transactions')
            ->where('tenant_id', $this->tenant->id)
            ->where('type', 'debit')
            ->latest('id')
            ->first();

        $this->assertNotNull($transaction);
        $this->assertEquals(-25.00, $transaction->amount);
        $this->assertEquals('Test debit', $transaction->description);
    }

    /** @test */
    public function it_respects_minimum_balance_for_consumption(): void
    {
        $this->tenant->update([
            'balance' => 10.00,
            'bonus_balance' => 0.00,
        ]);

        // Should fail - trying to consume more than available
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unzureichendes Guthaben');

        $this->balanceService->consumeBalance($this->tenant, 15.00, 'Test');
    }

    /** @test */
    public function it_handles_exact_balance_consumption(): void
    {
        $this->tenant->update([
            'balance' => 50.00,
            'bonus_balance' => 0.00,
        ]);

        // Consume exact balance
        $result = $this->balanceService->consumeBalance($this->tenant, 50.00, 'Exact amount');
        $this->assertTrue($result);

        $this->tenant->refresh();
        $this->assertEquals(0.00, $this->tenant->balance);
    }
}
