<?php

namespace Tests\Unit\Services\Billing;

use App\Models\Company;
use App\Models\CompanyFeeSchedule;
use App\Models\PricingPlan;
use App\Models\ServiceChangeFee;
use App\Models\Transaction;
use App\Services\Billing\FeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeeServiceTest extends TestCase
{
    use RefreshDatabase;

    private FeeService $feeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->feeService = app(FeeService::class);
    }

    /** @test */
    public function it_charges_setup_fee_for_new_company(): void
    {
        $pricingPlan = PricingPlan::factory()->create([
            'setup_fee' => 99.00,
        ]);

        $company = Company::factory()->create([
            'pricing_plan_id' => $pricingPlan->id,
        ]);

        // Create fee schedule
        $feeSchedule = CompanyFeeSchedule::create([
            'company_id' => $company->id,
            'setup_fee' => 99.00,
            'setup_fee_billed' => false,
        ]);

        $transaction = $this->feeService->chargeSetupFee($company);

        $this->assertNotNull($transaction);
        $this->assertEquals(Transaction::TYPE_FEE, $transaction->type);
        $this->assertEquals(-9900, $transaction->amount); // 99 EUR in cents, negative
        $this->assertStringContainsString('Einrichtungsgebühr', $transaction->description);

        // Verify fee schedule is marked as billed
        $feeSchedule->refresh();
        $this->assertTrue($feeSchedule->setup_fee_billed);
        $this->assertNotNull($feeSchedule->setup_fee_billed_at);
    }

    /** @test */
    public function it_does_not_charge_setup_fee_twice(): void
    {
        $company = Company::factory()->create();

        // Create fee schedule with setup fee already billed
        CompanyFeeSchedule::create([
            'company_id' => $company->id,
            'setup_fee' => 99.00,
            'setup_fee_billed' => true,
            'setup_fee_billed_at' => now(),
        ]);

        $transaction = $this->feeService->chargeSetupFee($company);

        $this->assertNull($transaction);
    }

    /** @test */
    public function it_does_not_charge_zero_setup_fee(): void
    {
        $company = Company::factory()->create();

        CompanyFeeSchedule::create([
            'company_id' => $company->id,
            'setup_fee' => 0,
            'setup_fee_billed' => false,
        ]);

        $transaction = $this->feeService->chargeSetupFee($company);

        $this->assertNull($transaction);
    }

    /** @test */
    public function it_creates_service_change_fee(): void
    {
        $company = Company::factory()->create();
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $fee = $this->feeService->createServiceChangeFee(
            $company,
            ServiceChangeFee::CATEGORY_AGENT_CHANGE,
            75.00,
            'Retell Agent Prompt Anpassung',
            [
                'description' => 'Neue Begrüßung und Verabschiedung',
                'hours_worked' => 1.0,
                'hourly_rate' => 75.00,
            ]
        );

        $this->assertNotNull($fee);
        $this->assertEquals($company->id, $fee->company_id);
        $this->assertEquals(ServiceChangeFee::CATEGORY_AGENT_CHANGE, $fee->category);
        $this->assertEquals(75.00, $fee->amount);
        $this->assertEquals('Retell Agent Prompt Anpassung', $fee->title);
        $this->assertEquals(ServiceChangeFee::STATUS_PENDING, $fee->status);
        $this->assertEquals(1.0, $fee->hours_worked);
    }

    /** @test */
    public function it_charges_service_change_fee_from_balance(): void
    {
        $company = Company::factory()->create([
            'balance' => 10000, // 100 EUR
        ]);

        $fee = ServiceChangeFee::create([
            'company_id' => $company->id,
            'category' => ServiceChangeFee::CATEGORY_FLOW_CHANGE,
            'title' => 'Call Flow Anpassung',
            'amount' => 50.00,
            'status' => ServiceChangeFee::STATUS_PENDING,
            'service_date' => now(),
            'created_by' => 1,
        ]);

        $transaction = $this->feeService->chargeServiceChangeFee($fee, 'balance');

        $this->assertNotNull($transaction);
        $this->assertEquals(-5000, $transaction->amount); // 50 EUR in cents
        $this->assertEquals(Transaction::TYPE_FEE, $transaction->type);

        // Verify fee status updated
        $fee->refresh();
        $this->assertEquals(ServiceChangeFee::STATUS_INVOICED, $fee->status);
        $this->assertNotNull($fee->invoiced_at);
        $this->assertEquals($transaction->id, $fee->transaction_id);
    }

    /** @test */
    public function it_does_not_charge_already_invoiced_fee(): void
    {
        $company = Company::factory()->create();

        $fee = ServiceChangeFee::create([
            'company_id' => $company->id,
            'category' => ServiceChangeFee::CATEGORY_GATEWAY_CONFIG,
            'title' => 'Service Gateway Konfiguration',
            'amount' => 100.00,
            'status' => ServiceChangeFee::STATUS_INVOICED, // Already invoiced
            'service_date' => now(),
            'created_by' => 1,
        ]);

        $transaction = $this->feeService->chargeServiceChangeFee($fee, 'balance');

        $this->assertNull($transaction);
    }

    /** @test */
    public function it_waives_pending_fee(): void
    {
        $company = Company::factory()->create();
        $user = \App\Models\User::factory()->create();

        $fee = ServiceChangeFee::create([
            'company_id' => $company->id,
            'category' => ServiceChangeFee::CATEGORY_SUPPORT,
            'title' => 'Support-Anfrage',
            'amount' => 25.00,
            'status' => ServiceChangeFee::STATUS_PENDING,
            'service_date' => now(),
            'created_by' => 1,
        ]);

        $waived = $this->feeService->waiveFee($fee, $user->id, 'Kulanzregelung für Neukunde');

        $this->assertEquals(ServiceChangeFee::STATUS_WAIVED, $waived->status);
        $this->assertEquals($user->id, $waived->waived_by);
        $this->assertNotNull($waived->waived_at);
        $this->assertEquals('Kulanzregelung für Neukunde', $waived->waived_reason);
    }

    /** @test */
    public function it_creates_fee_schedule_if_not_exists(): void
    {
        $company = Company::factory()->create();

        $this->assertNull($company->feeSchedule);

        $feeSchedule = $this->feeService->getOrCreateFeeSchedule($company);

        $this->assertNotNull($feeSchedule);
        $this->assertEquals($company->id, $feeSchedule->company_id);
        $this->assertEquals(CompanyFeeSchedule::BILLING_MODE_PER_SECOND, $feeSchedule->billing_mode);
    }

    /** @test */
    public function it_returns_existing_fee_schedule(): void
    {
        $company = Company::factory()->create();

        $existing = CompanyFeeSchedule::create([
            'company_id' => $company->id,
            'billing_mode' => CompanyFeeSchedule::BILLING_MODE_PER_MINUTE,
            'setup_fee' => 150.00,
        ]);

        $feeSchedule = $this->feeService->getOrCreateFeeSchedule($company);

        $this->assertEquals($existing->id, $feeSchedule->id);
        $this->assertEquals(150.00, $feeSchedule->setup_fee);
    }
}
