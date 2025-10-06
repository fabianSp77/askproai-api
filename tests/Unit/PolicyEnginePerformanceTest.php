<?php

namespace Tests\Unit;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\PolicyConfiguration;
use App\Services\Policies\AppointmentPolicyEngine;
use App\Services\Policies\PolicyConfigurationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PolicyEnginePerformanceTest extends TestCase
{
    use DatabaseTransactions;

    private AppointmentPolicyEngine $engine;
    private PolicyConfigurationService $policyService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policyService = new PolicyConfigurationService();
        $this->engine = new AppointmentPolicyEngine($this->policyService);
    }

    /** @test */
    public function policy_check_completes_under_100ms()
    {
        // Setup: Create test data
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $appointment = Appointment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'branch_id' => $branch->id,
            'starts_at' => Carbon::now()->addHours(50),
            'status' => 'confirmed',
        ]);

        PolicyConfiguration::create([
            'configurable_type' => 'App\\Models\\Company',
            'configurable_id' => $company->id,
            'policy_type' => 'cancellation',
            'config' => ['hours_before' => 24, 'fee' => 25.00]
        ]);

        // Warmup
        $this->engine->canCancel($appointment);

        // Measure: 100 iterations for statistical significance
        $start = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $result = $this->engine->canCancel($appointment);
            $this->assertTrue($result->allowed); // Verify correctness
        }
        $duration = (microtime(true) - $start) * 1000; // Convert to ms
        $avgPerCheck = $duration / 100;

        // Report results
        $this->addToAssertionCount(1);
        echo "\n";
        echo "📊 Policy Engine Performance:\n";
        echo "   Total (100 checks): " . round($duration, 2) . "ms\n";
        echo "   Average per check: " . round($avgPerCheck, 3) . "ms\n";
        echo "   Requirement: <100ms\n";
        echo "   Status: " . ($avgPerCheck < 100 ? "✅ PASS" : "❌ FAIL") . "\n";

        // Assert performance requirement
        $this->assertLessThan(
            100,
            $avgPerCheck,
            "Policy check took {$avgPerCheck}ms, exceeds 100ms requirement"
        );
    }

    /** @test */
    public function policy_check_with_cache_is_fast()
    {
        // Setup
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $appointment = Appointment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'starts_at' => Carbon::now()->addHours(50),
            'status' => 'confirmed',
        ]);

        PolicyConfiguration::create([
            'configurable_type' => 'App\\Models\\Company',
            'configurable_id' => $company->id,
            'policy_type' => 'cancellation',
            'config' => ['hours_before' => 24, 'max_cancellations_per_month' => 3]
        ]);

        // Prime cache
        $this->engine->canCancel($appointment);

        // Measure cached performance
        $start = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $result = $this->engine->canCancel($appointment);
        }
        $durationCached = (microtime(true) - $start) * 1000;
        $avgCached = $durationCached / 100;

        echo "\n";
        echo "📊 Cached Performance:\n";
        echo "   Average per check: " . round($avgCached, 3) . "ms\n";
        echo "   Status: " . ($avgCached < 100 ? "✅ PASS" : "❌ FAIL") . "\n";

        $this->assertLessThan(100, $avgCached, "Cached policy check too slow");
    }
}
