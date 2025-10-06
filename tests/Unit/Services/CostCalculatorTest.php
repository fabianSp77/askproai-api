<?php

namespace Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Services\CostCalculator;
use App\Models\Call;
use App\Models\Company;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class CostCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private CostCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new CostCalculator();

        // Create roles (idempotent to avoid unique constraint issues)
        Role::firstOrCreate(
            ['name' => 'super-admin', 'guard_name' => 'web'],
            []
        );
        Role::firstOrCreate(
            ['name' => 'reseller_admin', 'guard_name' => 'web'],
            []
        );
        Role::firstOrCreate(
            ['name' => 'customer', 'guard_name' => 'web'],
            []
        );
    }

    #[Test]
    public function base_cost_calculation(): void
    {
        $call = Call::factory()->create([
            'duration_sec' => 120, // 2 minutes
            'llm_token_usage' => null,
        ]);

        $this->calculator->updateCallCosts($call);
        $call->refresh();

        // 2 minutes * 10 cents + 5 cents base = 25 cents
        $this->assertEquals(25, $call->base_cost);
    }

    #[Test]
    public function reseller_cost_calculation(): void
    {
        $reseller = Company::factory()->reseller()->create();

        $customer = Company::factory()->underReseller($reseller)->create();

        $call = Call::factory()->create([
            'company_id' => $customer->id,
            'duration_sec' => 60,
        ]);

        $this->calculator->updateCallCosts($call);
        $call->refresh();

        // Base: 1 minute * 10 + 5 = 15 cents
        // Reseller: 15 * 1.2 = 18 cents
        $this->assertEquals(15, $call->base_cost);
        $this->assertEquals(18, $call->reseller_cost);
    }

    #[Test]
    public function profit_calculation_reseller_scenario(): void
    {
        $reseller = Company::factory()->reseller()->create();

        $customer = Company::factory()->underReseller($reseller)->create();

        $call = Call::factory()->create([
            'company_id' => $customer->id,
            'duration_sec' => 100,
            'base_cost' => 20,
        ]);

        $this->calculator->updateCallCosts($call);
        $call->refresh();

        // Platform profit = reseller_cost - base_cost
        // Reseller profit = customer_cost - reseller_cost
        $this->assertGreaterThan(0, $call->platform_profit);
        $this->assertGreaterThan(0, $call->reseller_profit);
        $this->assertEquals($call->platform_profit + $call->reseller_profit, $call->total_profit);
    }

    #[Test]
    public function profit_calculation_direct_customer(): void
    {
        $customer = Company::factory()->directCustomer()->create();

        $call = Call::factory()->create([
            'company_id' => $customer->id,
            'duration_sec' => 120,
        ]);

        $this->calculator->updateCallCosts($call);
        $call->refresh();

        // For direct customer: platform_profit = customer_cost - base_cost, reseller_profit = 0
        $this->assertGreaterThan(0, $call->platform_profit);
        $this->assertEquals(0, $call->reseller_profit);
        $this->assertEquals($call->platform_profit, $call->total_profit);
    }

    #[Test]
    public function display_cost_super_admin(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $call = Call::factory()->create([
            'base_cost' => 10,
            'reseller_cost' => 12,
            'customer_cost' => 15,
            'cost_cents' => 0,
        ]);

        $displayCost = $this->calculator->getDisplayCost($call, $superAdmin);

        // Super admin should see customer cost
        $this->assertEquals(15, $displayCost);
    }

    #[Test]
    public function display_cost_reseller_admin(): void
    {
        $resellerCompany = Company::factory()->reseller()->create();
        $resellerAdmin = User::factory()->create([
            'company_id' => $resellerCompany->id
        ]);
        $resellerAdmin->assignRole('reseller_admin');

        $this->assertTrue($resellerAdmin->hasRole('reseller_admin'));
        $this->assertFalse($resellerAdmin->hasRole('super-admin'));

        $customerCompany = Company::factory()->underReseller($resellerCompany)->create();

        $call = Call::factory()->create([
            'company_id' => $customerCompany->id,
            'base_cost' => 10,
            'reseller_cost' => 12,
            'customer_cost' => 15,
            'cost_cents' => 0,
        ]);

        $this->assertEquals($customerCompany->id, $call->company_id);
        $this->assertEquals($resellerCompany->id, $call->company->parent_company_id);
        $this->assertEquals(12, $call->reseller_cost);

        $displayCost = $this->calculator->getDisplayCost($call, $resellerAdmin);

        // Reseller admin should see reseller cost for their customers
        $this->assertEquals(12, $displayCost);
    }

    #[Test]
    public function display_cost_regular_customer(): void
    {
        $customerCompany = Company::factory()->customer()->create();
        $customerUser = User::factory()->create([
            'company_id' => $customerCompany->id
        ]);
        $customerUser->assignRole('customer');

        $call = Call::factory()->create([
            'company_id' => $customerCompany->id,
            'base_cost' => 10,
            'reseller_cost' => 12,
            'customer_cost' => 15,
            'cost_cents' => 0,
        ]);

        $displayCost = $this->calculator->getDisplayCost($call, $customerUser);

        // Customer should see customer cost
        $this->assertEquals(15, $displayCost);
    }

    #[Test]
    public function display_profit_super_admin(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $call = Call::factory()->create([
            'platform_profit' => 5,
            'reseller_profit' => 3,
            'total_profit' => 8,
            'profit_margin_total' => 80,
        ]);

        $profitData = $this->calculator->getDisplayProfit($call, $superAdmin);

        // Super admin should see total profit and breakdown
        $this->assertEquals(8, $profitData['profit']);
        $this->assertEquals(80, $profitData['margin']);
        $this->assertEquals('total', $profitData['type']);
        $this->assertArrayHasKey('breakdown', $profitData);
        $this->assertEquals(5, $profitData['breakdown']['platform']);
        $this->assertEquals(3, $profitData['breakdown']['reseller']);
    }

    #[Test]
    public function display_profit_reseller_admin(): void
    {
        $resellerCompany = Company::factory()->reseller()->create();
        $resellerAdmin = User::factory()->create([
            'company_id' => $resellerCompany->id
        ]);
        $resellerAdmin->assignRole('reseller_admin');

        $customerCompany = Company::factory()->underReseller($resellerCompany)->create();

        $call = Call::factory()->create([
            'company_id' => $customerCompany->id,
            'platform_profit' => 5,
            'reseller_profit' => 3,
            'total_profit' => 8,
            'profit_margin_reseller' => 25,
        ]);

        $profitData = $this->calculator->getDisplayProfit($call, $resellerAdmin);

        // Reseller should see only their profit
        $this->assertEquals(3, $profitData['profit']);
        $this->assertEquals(25, $profitData['margin']);
        $this->assertEquals('reseller', $profitData['type']);
        $this->assertArrayNotHasKey('breakdown', $profitData);
    }

    #[Test]
    public function display_profit_customer_sees_nothing(): void
    {
        $customerCompany = Company::factory()->customer()->create();
        $customerUser = User::factory()->create([
            'company_id' => $customerCompany->id
        ]);
        $customerUser->assignRole('customer');

        $call = Call::factory()->create([
            'company_id' => $customerCompany->id,
            'platform_profit' => 5,
            'reseller_profit' => 3,
            'total_profit' => 8,
        ]);

        $profitData = $this->calculator->getDisplayProfit($call, $customerUser);

        // Customer should not see any profit
        $this->assertEquals(0, $profitData['profit']);
        $this->assertEquals(0, $profitData['margin']);
        $this->assertEquals('none', $profitData['type']);
    }

    #[Test]
    public function reseller_cannot_see_other_reseller_profits(): void
    {
        $resellerA = Company::factory()->reseller()->create();
        $resellerB = Company::factory()->reseller()->create();

        $resellerAdminA = User::factory()->create([
            'company_id' => $resellerA->id
        ]);
        $resellerAdminA->assignRole('reseller_admin');

        $customerOfB = Company::factory()->underReseller($resellerB)->create();

        $call = Call::factory()->create([
            'company_id' => $customerOfB->id,
            'reseller_profit' => 10,
        ]);

        $profitData = $this->calculator->getDisplayProfit($call, $resellerAdminA);

        // Reseller A should not see profits from Reseller B's customer
        $this->assertEquals(0, $profitData['profit']);
        $this->assertEquals('none', $profitData['type']);
    }

    #[Test]
    public function handling_null_values(): void
    {
        $call = Call::factory()->create([
            'base_cost' => null,
            'reseller_cost' => null,
            'customer_cost' => null,
            'platform_profit' => null,
            'reseller_profit' => null,
            'total_profit' => null,
            'cost_cents' => 0,
            'cost' => null,
        ]);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $displayCost = $this->calculator->getDisplayCost($call, $superAdmin);
        $profitData = $this->calculator->getDisplayProfit($call, $superAdmin);

        // Should handle nulls gracefully
        $this->assertEquals(0, $displayCost);
        $this->assertEquals(0, $profitData['profit']);
    }

    #[Test]
    public function negative_profit_handling(): void
    {
        $call = Call::factory()->loss()->create();

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $profitData = $this->calculator->getDisplayProfit($call, $superAdmin);

        // Should handle negative profits correctly
        $this->assertLessThan(0, $profitData['profit']);
        $this->assertLessThan(0, $profitData['margin']);
    }

    #[Test]
    public function profit_margin_division_by_zero(): void
    {
        $call = Call::factory()->create([
            'base_cost' => 0,
            'customer_cost' => 10,
        ]);

        $this->calculator->updateCallCosts($call);
        $call->refresh();

        // Should handle division by zero
        $this->assertNotNull($call->profit_margin_total);
    }
}