<?php

namespace Tests\Unit\Services\Billing;

use App\Models\Call;
use App\Models\Company;
use App\Models\CompanyFeeSchedule;
use App\Models\PricingPlan;
use App\Services\CostCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerSecondBillingTest extends TestCase
{
    use RefreshDatabase;

    private CostCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = app(CostCalculator::class);
    }

    /** @test */
    public function it_calculates_per_second_billing_correctly(): void
    {
        // Setup: 157 seconds at 0.12 EUR/min
        // Per-second: 157/60 = 2.617 min × 0.12 = 0.314 EUR = 31 cents
        $pricingPlan = PricingPlan::factory()->create([
            'price_per_minute' => 0.12,
        ]);

        $company = Company::factory()->create([
            'pricing_plan_id' => $pricingPlan->id,
        ]);

        // Create fee schedule with per-second billing
        CompanyFeeSchedule::create([
            'company_id' => $company->id,
            'billing_mode' => CompanyFeeSchedule::BILLING_MODE_PER_SECOND,
        ]);

        $call = Call::factory()->create([
            'company_id' => $company->id,
            'duration_sec' => 157,
        ]);

        $result = $this->calculator->calculateAndStoreCosts($call);

        // 157/60 * 0.12 * 100 = 31.4 → rounds to 31
        $this->assertEquals(31, $result['customer_cost']);
    }

    /** @test */
    public function it_calculates_per_minute_billing_with_ceiling(): void
    {
        // Setup: 157 seconds at 0.12 EUR/min
        // Per-minute (legacy): ceil(157/60) = 3 min × 0.12 = 0.36 EUR = 36 cents
        $pricingPlan = PricingPlan::factory()->create([
            'price_per_minute' => 0.12,
        ]);

        $company = Company::factory()->create([
            'pricing_plan_id' => $pricingPlan->id,
        ]);

        // Create fee schedule with per-minute billing (legacy)
        CompanyFeeSchedule::create([
            'company_id' => $company->id,
            'billing_mode' => CompanyFeeSchedule::BILLING_MODE_PER_MINUTE,
        ]);

        $call = Call::factory()->create([
            'company_id' => $company->id,
            'duration_sec' => 157,
        ]);

        $result = $this->calculator->calculateAndStoreCosts($call);

        // ceil(157/60) * 0.12 * 100 = 36
        $this->assertEquals(36, $result['customer_cost']);
    }

    /** @test */
    public function per_second_billing_saves_money_on_short_overruns(): void
    {
        // 61 seconds: per-minute = 2 min = 24 cents, per-second = 1.017 min = 12 cents
        $pricingPlan = PricingPlan::factory()->create([
            'price_per_minute' => 0.12,
        ]);

        $company = Company::factory()->create([
            'pricing_plan_id' => $pricingPlan->id,
        ]);

        CompanyFeeSchedule::create([
            'company_id' => $company->id,
            'billing_mode' => CompanyFeeSchedule::BILLING_MODE_PER_SECOND,
        ]);

        $call = Call::factory()->create([
            'company_id' => $company->id,
            'duration_sec' => 61, // Just 1 second over a minute
        ]);

        $result = $this->calculator->calculateAndStoreCosts($call);

        // 61/60 * 0.12 * 100 = 12.2 → rounds to 12
        $this->assertEquals(12, $result['customer_cost']);

        // Compare with legacy per-minute (would be 24 cents)
        // Savings: 24 - 12 = 12 cents (50% savings!)
    }

    /** @test */
    public function exact_minute_boundaries_produce_same_result(): void
    {
        // 120 seconds: both modes should produce 24 cents
        $pricingPlan = PricingPlan::factory()->create([
            'price_per_minute' => 0.12,
        ]);

        $company = Company::factory()->create([
            'pricing_plan_id' => $pricingPlan->id,
        ]);

        // Per-second mode
        $feeSchedulePerSecond = CompanyFeeSchedule::create([
            'company_id' => $company->id,
            'billing_mode' => CompanyFeeSchedule::BILLING_MODE_PER_SECOND,
        ]);

        $call = Call::factory()->create([
            'company_id' => $company->id,
            'duration_sec' => 120, // Exactly 2 minutes
        ]);

        $resultPerSecond = $this->calculator->calculateAndStoreCosts($call);

        // Update to per-minute mode
        $feeSchedulePerSecond->update(['billing_mode' => CompanyFeeSchedule::BILLING_MODE_PER_MINUTE]);

        // Recalculate
        $call->refresh();
        $resultPerMinute = $this->calculator->calculateAndStoreCosts($call);

        // Both should be 24 cents (2 × 0.12 × 100)
        $this->assertEquals(24, $resultPerSecond['customer_cost']);
        $this->assertEquals(24, $resultPerMinute['customer_cost']);
    }

    /** @test */
    public function default_billing_mode_is_per_second(): void
    {
        // Company without explicit fee schedule should use per-second billing
        $pricingPlan = PricingPlan::factory()->create([
            'price_per_minute' => 0.12,
        ]);

        $company = Company::factory()->create([
            'pricing_plan_id' => $pricingPlan->id,
        ]);

        // No CompanyFeeSchedule created

        $call = Call::factory()->create([
            'company_id' => $company->id,
            'duration_sec' => 157,
        ]);

        $result = $this->calculator->calculateAndStoreCosts($call);

        // Should use per-second billing by default: 157/60 * 0.12 * 100 = 31
        $this->assertEquals(31, $result['customer_cost']);
    }

    /** @test */
    public function very_short_calls_are_billed_correctly(): void
    {
        // 5 seconds at 0.12 EUR/min
        $pricingPlan = PricingPlan::factory()->create([
            'price_per_minute' => 0.12,
        ]);

        $company = Company::factory()->create([
            'pricing_plan_id' => $pricingPlan->id,
        ]);

        CompanyFeeSchedule::create([
            'company_id' => $company->id,
            'billing_mode' => CompanyFeeSchedule::BILLING_MODE_PER_SECOND,
        ]);

        $call = Call::factory()->create([
            'company_id' => $company->id,
            'duration_sec' => 5,
        ]);

        $result = $this->calculator->calculateAndStoreCosts($call);

        // 5/60 * 0.12 * 100 = 1 cent
        $this->assertEquals(1, $result['customer_cost']);
    }

    /** @test */
    public function long_calls_maintain_precision(): void
    {
        // 3723 seconds (62 minutes 3 seconds) at 0.15 EUR/min
        $pricingPlan = PricingPlan::factory()->create([
            'price_per_minute' => 0.15,
        ]);

        $company = Company::factory()->create([
            'pricing_plan_id' => $pricingPlan->id,
        ]);

        CompanyFeeSchedule::create([
            'company_id' => $company->id,
            'billing_mode' => CompanyFeeSchedule::BILLING_MODE_PER_SECOND,
        ]);

        $call = Call::factory()->create([
            'company_id' => $company->id,
            'duration_sec' => 3723,
        ]);

        $result = $this->calculator->calculateAndStoreCosts($call);

        // 3723/60 * 0.15 * 100 = 930.75 → rounds to 931
        $this->assertEquals(931, $result['customer_cost']);
    }
}
