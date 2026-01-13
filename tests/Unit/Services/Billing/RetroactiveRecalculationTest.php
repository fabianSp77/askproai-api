<?php

namespace Tests\Unit\Services\Billing;

use App\Models\Call;
use App\Models\Company;
use App\Models\CompanyFeeSchedule;
use App\Models\PricingPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class RetroactiveRecalculationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_recalculates_calls_with_per_second_billing(): void
    {
        $pricingPlan = PricingPlan::factory()->create([
            'price_per_minute' => 0.12,
        ]);

        $company = Company::factory()->create([
            'pricing_plan_id' => $pricingPlan->id,
        ]);

        // Create call with legacy per-minute cost
        $call = Call::factory()->create([
            'company_id' => $company->id,
            'duration_sec' => 157,
            'customer_cost' => 36, // Legacy: ceil(157/60) * 0.12 * 100 = 36
        ]);

        // Run recalculation command
        Artisan::call('billing:recalculate-calls', [
            '--company' => $company->id,
            '--billing-mode' => 'per_second',
            '--force' => true,
        ]);

        $call->refresh();

        // New cost should be: 157/60 * 0.12 * 100 = 31.4 → 31
        $this->assertEquals(31, $call->customer_cost);

        // Verify audit trail exists
        $this->assertNotNull($call->cost_breakdown);
        $this->assertArrayHasKey('recalculation_history', $call->cost_breakdown);
        $this->assertNotEmpty($call->cost_breakdown['recalculation_history']);

        $lastRecalc = end($call->cost_breakdown['recalculation_history']);
        $this->assertEquals(36, $lastRecalc['old_customer_cost']);
        $this->assertEquals(31, $lastRecalc['new_customer_cost']);
        $this->assertEquals('per_second', $lastRecalc['billing_mode']);
    }

    /** @test */
    public function dry_run_does_not_modify_calls(): void
    {
        $pricingPlan = PricingPlan::factory()->create([
            'price_per_minute' => 0.12,
        ]);

        $company = Company::factory()->create([
            'pricing_plan_id' => $pricingPlan->id,
        ]);

        $call = Call::factory()->create([
            'company_id' => $company->id,
            'duration_sec' => 157,
            'customer_cost' => 36,
        ]);

        Artisan::call('billing:recalculate-calls', [
            '--company' => $company->id,
            '--billing-mode' => 'per_second',
            '--dry-run' => true,
        ]);

        $call->refresh();

        // Cost should remain unchanged
        $this->assertEquals(36, $call->customer_cost);
    }

    /** @test */
    public function it_filters_by_date_range(): void
    {
        $pricingPlan = PricingPlan::factory()->create([
            'price_per_minute' => 0.12,
        ]);

        $company = Company::factory()->create([
            'pricing_plan_id' => $pricingPlan->id,
        ]);

        // Old call (should not be recalculated)
        $oldCall = Call::factory()->create([
            'company_id' => $company->id,
            'duration_sec' => 157,
            'customer_cost' => 36,
            'created_at' => now()->subMonths(3),
        ]);

        // Recent call (should be recalculated)
        $recentCall = Call::factory()->create([
            'company_id' => $company->id,
            'duration_sec' => 157,
            'customer_cost' => 36,
            'created_at' => now()->subDays(5),
        ]);

        Artisan::call('billing:recalculate-calls', [
            '--company' => $company->id,
            '--from' => now()->subMonth()->format('Y-m-d'),
            '--billing-mode' => 'per_second',
            '--force' => true,
        ]);

        $oldCall->refresh();
        $recentCall->refresh();

        // Old call unchanged
        $this->assertEquals(36, $oldCall->customer_cost);

        // Recent call recalculated
        $this->assertEquals(31, $recentCall->customer_cost);
    }

    /** @test */
    public function it_preserves_recalculation_history(): void
    {
        $pricingPlan = PricingPlan::factory()->create([
            'price_per_minute' => 0.12,
        ]);

        $company = Company::factory()->create([
            'pricing_plan_id' => $pricingPlan->id,
        ]);

        $call = Call::factory()->create([
            'company_id' => $company->id,
            'duration_sec' => 157,
            'customer_cost' => 40, // Original incorrect value
            'cost_breakdown' => [
                'recalculation_history' => [
                    [
                        'timestamp' => now()->subDay()->toIso8601String(),
                        'old_customer_cost' => 45,
                        'new_customer_cost' => 40,
                        'billing_mode' => 'per_minute',
                        'reason' => 'manual correction',
                    ],
                ],
            ],
        ]);

        Artisan::call('billing:recalculate-calls', [
            '--company' => $company->id,
            '--billing-mode' => 'per_second',
            '--force' => true,
        ]);

        $call->refresh();

        // Should have 2 history entries now
        $this->assertCount(2, $call->cost_breakdown['recalculation_history']);

        // First entry preserved
        $this->assertEquals(45, $call->cost_breakdown['recalculation_history'][0]['old_customer_cost']);

        // Second entry added
        $this->assertEquals(40, $call->cost_breakdown['recalculation_history'][1]['old_customer_cost']);
        $this->assertEquals(31, $call->cost_breakdown['recalculation_history'][1]['new_customer_cost']);
    }

    /** @test */
    public function it_handles_calls_with_zero_duration(): void
    {
        $pricingPlan = PricingPlan::factory()->create([
            'price_per_minute' => 0.12,
        ]);

        $company = Company::factory()->create([
            'pricing_plan_id' => $pricingPlan->id,
        ]);

        // Zero duration call should be skipped
        $zeroCall = Call::factory()->create([
            'company_id' => $company->id,
            'duration_sec' => 0,
            'customer_cost' => 0,
        ]);

        $normalCall = Call::factory()->create([
            'company_id' => $company->id,
            'duration_sec' => 60,
            'customer_cost' => 12,
        ]);

        $exitCode = Artisan::call('billing:recalculate-calls', [
            '--company' => $company->id,
            '--billing-mode' => 'per_second',
            '--force' => true,
        ]);

        $this->assertEquals(0, $exitCode);

        $zeroCall->refresh();
        $normalCall->refresh();

        // Zero call unchanged (filtered out)
        $this->assertEquals(0, $zeroCall->customer_cost);

        // Normal call recalculated
        $this->assertEquals(12, $normalCall->customer_cost); // 60/60 * 0.12 * 100 = 12
    }

    /** @test */
    public function it_uses_company_override_rates_during_recalculation(): void
    {
        $pricingPlan = PricingPlan::factory()->create([
            'price_per_minute' => 0.12,
        ]);

        $company = Company::factory()->create([
            'pricing_plan_id' => $pricingPlan->id,
        ]);

        // Company has custom rate override
        CompanyFeeSchedule::create([
            'company_id' => $company->id,
            'billing_mode' => CompanyFeeSchedule::BILLING_MODE_PER_SECOND,
            'override_per_minute_rate' => 0.08, // Custom rate
        ]);

        $call = Call::factory()->create([
            'company_id' => $company->id,
            'duration_sec' => 120,
            'customer_cost' => 24, // Old: 2 * 0.12 * 100
        ]);

        Artisan::call('billing:recalculate-calls', [
            '--company' => $company->id,
            '--billing-mode' => 'per_second',
            '--force' => true,
        ]);

        $call->refresh();

        // Should use override rate: 2 * 0.08 * 100 = 16
        $this->assertEquals(16, $call->customer_cost);
    }
}
