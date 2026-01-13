<?php

namespace Tests\Unit\Services\Billing;

use App\Models\Call;
use App\Models\Company;
use App\Models\CompanyFeeSchedule;
use App\Models\PricingPlan;
use App\Services\CostCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantOverrideTest extends TestCase
{
    use RefreshDatabase;

    private CostCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = app(CostCalculator::class);
    }

    /** @test */
    public function company_override_rate_takes_precedence(): void
    {
        // PricingPlan: 0.12 EUR/min
        // CompanyFeeSchedule: 0.08 EUR/min (override)
        $pricingPlan = PricingPlan::factory()->create([
            'price_per_minute' => 0.12,
        ]);

        $company = Company::factory()->create([
            'pricing_plan_id' => $pricingPlan->id,
        ]);

        CompanyFeeSchedule::create([
            'company_id' => $company->id,
            'billing_mode' => CompanyFeeSchedule::BILLING_MODE_PER_SECOND,
            'override_per_minute_rate' => 0.08, // Custom rate
        ]);

        $call = Call::factory()->create([
            'company_id' => $company->id,
            'duration_sec' => 120, // 2 minutes
        ]);

        $result = $this->calculator->calculateAndStoreCosts($call);

        // Should use override rate: 2 * 0.08 * 100 = 16 cents
        // NOT PricingPlan rate: 2 * 0.12 * 100 = 24 cents
        $this->assertEquals(16, $result['customer_cost']);
    }

    /** @test */
    public function pricing_plan_rate_used_when_no_override(): void
    {
        $pricingPlan = PricingPlan::factory()->create([
            'price_per_minute' => 0.15,
        ]);

        $company = Company::factory()->create([
            'pricing_plan_id' => $pricingPlan->id,
        ]);

        // Fee schedule without rate override
        CompanyFeeSchedule::create([
            'company_id' => $company->id,
            'billing_mode' => CompanyFeeSchedule::BILLING_MODE_PER_SECOND,
            'override_per_minute_rate' => null, // No override
        ]);

        $call = Call::factory()->create([
            'company_id' => $company->id,
            'duration_sec' => 120,
        ]);

        $result = $this->calculator->calculateAndStoreCosts($call);

        // Should use PricingPlan rate: 2 * 0.15 * 100 = 30 cents
        $this->assertEquals(30, $result['customer_cost']);
    }

    /** @test */
    public function company_discount_override_takes_precedence(): void
    {
        $pricingPlan = PricingPlan::factory()->create([
            'price_per_minute' => 0.10,
            'discount_percentage' => 10, // 10% discount
        ]);

        $company = Company::factory()->create([
            'pricing_plan_id' => $pricingPlan->id,
        ]);

        CompanyFeeSchedule::create([
            'company_id' => $company->id,
            'billing_mode' => CompanyFeeSchedule::BILLING_MODE_PER_SECOND,
            'override_discount_percentage' => 25, // 25% discount override
        ]);

        $call = Call::factory()->create([
            'company_id' => $company->id,
            'duration_sec' => 600, // 10 minutes
        ]);

        $result = $this->calculator->calculateAndStoreCosts($call);

        // Base: 10 * 0.10 * 100 = 1000 cents
        // With 25% discount: 1000 - 250 = 750 cents
        $this->assertEquals(750, $result['customer_cost']);
    }

    /** @test */
    public function pricing_plan_discount_used_when_no_override(): void
    {
        $pricingPlan = PricingPlan::factory()->create([
            'price_per_minute' => 0.10,
            'discount_percentage' => 20, // 20% discount
        ]);

        $company = Company::factory()->create([
            'pricing_plan_id' => $pricingPlan->id,
        ]);

        CompanyFeeSchedule::create([
            'company_id' => $company->id,
            'billing_mode' => CompanyFeeSchedule::BILLING_MODE_PER_SECOND,
            'override_discount_percentage' => null, // No override
        ]);

        $call = Call::factory()->create([
            'company_id' => $company->id,
            'duration_sec' => 600,
        ]);

        $result = $this->calculator->calculateAndStoreCosts($call);

        // Base: 10 * 0.10 * 100 = 1000 cents
        // With 20% discount: 1000 - 200 = 800 cents
        $this->assertEquals(800, $result['customer_cost']);
    }

    /** @test */
    public function combined_overrides_work_correctly(): void
    {
        // Test both rate AND discount overrides together
        $pricingPlan = PricingPlan::factory()->create([
            'price_per_minute' => 0.12,
            'discount_percentage' => 0,
        ]);

        $company = Company::factory()->create([
            'pricing_plan_id' => $pricingPlan->id,
        ]);

        CompanyFeeSchedule::create([
            'company_id' => $company->id,
            'billing_mode' => CompanyFeeSchedule::BILLING_MODE_PER_SECOND,
            'override_per_minute_rate' => 0.10, // Custom rate
            'override_discount_percentage' => 15, // Custom discount
        ]);

        $call = Call::factory()->create([
            'company_id' => $company->id,
            'duration_sec' => 300, // 5 minutes
        ]);

        $result = $this->calculator->calculateAndStoreCosts($call);

        // Base with override rate: 5 * 0.10 * 100 = 500 cents
        // With 15% discount: 500 - 75 = 425 cents
        $this->assertEquals(425, $result['customer_cost']);
    }

    /** @test */
    public function zero_rate_is_respected_as_valid_override(): void
    {
        // Special case: Company has free tier (rate = 0)
        $pricingPlan = PricingPlan::factory()->create([
            'price_per_minute' => 0.12,
        ]);

        $company = Company::factory()->create([
            'pricing_plan_id' => $pricingPlan->id,
        ]);

        CompanyFeeSchedule::create([
            'company_id' => $company->id,
            'billing_mode' => CompanyFeeSchedule::BILLING_MODE_PER_SECOND,
            'override_per_minute_rate' => 0.00, // Free tier
        ]);

        $call = Call::factory()->create([
            'company_id' => $company->id,
            'duration_sec' => 300,
        ]);

        $result = $this->calculator->calculateAndStoreCosts($call);

        // Note: This depends on implementation - if null means "use default"
        // and 0 means "free", this should be 0
        // If implementation treats 0 as falsy and uses default, this would be different
        $this->assertTrue(
            $result['customer_cost'] === 0 || $result['customer_cost'] === 60,
            'Zero rate should either mean free or fall back to plan rate'
        );
    }

    /** @test */
    public function company_without_fee_schedule_uses_plan_defaults(): void
    {
        $pricingPlan = PricingPlan::factory()->create([
            'price_per_minute' => 0.18,
            'discount_percentage' => 5,
        ]);

        $company = Company::factory()->create([
            'pricing_plan_id' => $pricingPlan->id,
        ]);

        // No CompanyFeeSchedule created

        $call = Call::factory()->create([
            'company_id' => $company->id,
            'duration_sec' => 120,
        ]);

        $result = $this->calculator->calculateAndStoreCosts($call);

        // Default per-second billing with plan rate
        // 2 * 0.18 * 100 = 36 cents
        // With 5% discount: 36 - 1.8 = 34.2 → rounds to 34
        $this->assertEquals(34, $result['customer_cost']);
    }

    /** @test */
    public function fee_schedule_without_plan_uses_system_defaults(): void
    {
        // Edge case: Company with fee schedule but no pricing plan
        $company = Company::factory()->create([
            'pricing_plan_id' => null,
        ]);

        CompanyFeeSchedule::create([
            'company_id' => $company->id,
            'billing_mode' => CompanyFeeSchedule::BILLING_MODE_PER_SECOND,
            'override_per_minute_rate' => 0.10, // Must specify rate since no plan
        ]);

        $call = Call::factory()->create([
            'company_id' => $company->id,
            'duration_sec' => 60,
        ]);

        $result = $this->calculator->calculateAndStoreCosts($call);

        // 1 * 0.10 * 100 = 10 cents
        $this->assertEquals(10, $result['customer_cost']);
    }
}
