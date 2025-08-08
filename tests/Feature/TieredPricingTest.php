<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Company;
use App\Models\CompanyPricingTier;
use App\Models\Call;
use App\Services\TieredPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TieredPricingTest extends TestCase
{
    use RefreshDatabase;

    private TieredPricingService $pricingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pricingService = new TieredPricingService();
    }

    public function test_reseller_can_set_different_prices_for_clients()
    {
        // Create reseller
        $reseller = Company::factory()->create([
            'name' => 'Test Reseller GmbH',
            'company_type' => 'reseller'
        ]);

        // Create client
        $client = Company::factory()->create([
            'name' => 'Friseur Schmidt',
            'company_type' => 'client',
            'parent_company_id' => $reseller->id
        ]);

        // Create pricing tier
        $pricing = CompanyPricingTier::create([
            'company_id' => $reseller->id,
            'child_company_id' => $client->id,
            'pricing_type' => 'inbound',
            'cost_price' => 0.30,
            'sell_price' => 0.40,
            'included_minutes' => 100,
            'is_active' => true
        ]);

        // Create a call for the client
        $call = Call::factory()->create([
            'company_id' => $client->id,
            'duration_minutes' => 10,
            'direction' => 'inbound'
        ]);

        // Calculate cost
        $cost = $this->pricingService->calculateCallCost($call);

        // Assert pricing
        $this->assertEquals(4.00, $cost['sell_cost']); // 10 min × 0.40€
        $this->assertGreaterThan(0, $cost['total_margin']);
        $this->assertEquals(10, $cost['included_minutes_used']); // All 10 minutes from included
        $this->assertEquals(0, $cost['billable_minutes']); // No overage
    }

    public function test_overage_pricing_works_correctly()
    {
        $reseller = Company::factory()->create(['company_type' => 'reseller']);
        $client = Company::factory()->create([
            'company_type' => 'client',
            'parent_company_id' => $reseller->id
        ]);

        $pricing = CompanyPricingTier::create([
            'company_id' => $reseller->id,
            'child_company_id' => $client->id,
            'pricing_type' => 'inbound',
            'cost_price' => 0.30,
            'sell_price' => 0.40,
            'included_minutes' => 100,
            'is_active' => true
        ]);

        $call = Call::factory()->create([
            'company_id' => $client->id,
            'duration_minutes' => 150, // 50 minutes overage
            'direction' => 'inbound'
        ]);

        $cost = $this->pricingService->calculateCallCost($call);

        $this->assertEquals(100, $cost['included_minutes_used']);
        $this->assertEquals(50, $cost['billable_minutes']);
        $this->assertEquals(20.00, $cost['sell_cost']); // 50 overage min × 0.40€
    }

    public function test_margin_calculation_is_accurate()
    {
        $reseller = Company::factory()->create(['company_type' => 'reseller']);

        // Reseller's own pricing (what they pay)
        $resellerPricing = CompanyPricingTier::create([
            'company_id' => $reseller->id,
            'child_company_id' => null,
            'pricing_type' => 'inbound',
            'cost_price' => 0.25,
            'sell_price' => 0.25, // Reseller pays cost price
            'is_active' => true
        ]);

        $margin = $resellerPricing->calculateMargin();

        $this->assertEquals(0, $margin['amount']);
        $this->assertEquals(0, $margin['percentage']);

        // Client pricing
        $client = Company::factory()->create([
            'company_type' => 'client',
            'parent_company_id' => $reseller->id
        ]);

        $clientPricing = CompanyPricingTier::create([
            'company_id' => $reseller->id,
            'child_company_id' => $client->id,
            'pricing_type' => 'inbound',
            'cost_price' => 0.25,
            'sell_price' => 0.45,
            'is_active' => true
        ]);

        $margin = $clientPricing->calculateMargin();

        $this->assertEquals(0.20, $margin['amount']); // 0.45 - 0.25
        $this->assertEquals(80, $margin['percentage']); // (0.20 / 0.25) * 100
    }

    public function test_monthly_invoice_includes_all_charges()
    {
        $reseller = Company::factory()->create(['company_type' => 'reseller']);
        $client = Company::factory()->create([
            'company_type' => 'client',
            'parent_company_id' => $reseller->id
        ]);

        // Setup pricing
        CompanyPricingTier::create([
            'company_id' => $reseller->id,
            'child_company_id' => $client->id,
            'pricing_type' => 'inbound',
            'cost_price' => 0.30,
            'sell_price' => 0.40,
            'included_minutes' => 500,
            'is_active' => true
        ]);

        CompanyPricingTier::create([
            'company_id' => $reseller->id,
            'child_company_id' => $client->id,
            'pricing_type' => 'monthly',
            'cost_price' => 0,
            'sell_price' => 0,
            'monthly_fee' => 99.00,
            'is_active' => true
        ]);

        // Create calls
        $month = now()->startOfMonth();
        Call::factory()->count(5)->create([
            'company_id' => $client->id,
            'duration_minutes' => 60,
            'direction' => 'inbound',
            'created_at' => $month->copy()->addDays(rand(1, 28))
        ]);

        // Calculate invoice
        $invoice = $this->pricingService->calculateMonthlyInvoice($client, $month);

        $this->assertCount(2, $invoice['line_items']); // Calls + Monthly fee
        $this->assertEquals(99.00, $invoice['line_items'][1]['amount']); // Monthly fee
        $this->assertEquals($invoice['subtotal'] * 0.19, $invoice['tax']); // 19% VAT
        $this->assertEquals($invoice['subtotal'] + $invoice['tax'], $invoice['total']);
    }

    public function test_fallback_to_default_pricing_when_no_tier_exists()
    {
        $company = Company::factory()->create([
            'price_per_minute' => 0.35
        ]);

        $call = Call::factory()->create([
            'company_id' => $company->id,
            'duration_minutes' => 10,
            'direction' => 'inbound'
        ]);

        $cost = $this->pricingService->calculateCallCost($call);

        $this->assertEquals(3.50, $cost['sell_cost']); // 10 min × 0.35€
        $this->assertEquals(10, $cost['billable_minutes']);
        $this->assertEquals(0, $cost['included_minutes_used']);
    }

    public function test_cache_is_used_for_pricing_tier_lookup()
    {
        $reseller = Company::factory()->create(['company_type' => 'reseller']);
        $client = Company::factory()->create([
            'company_type' => 'client',
            'parent_company_id' => $reseller->id
        ]);

        CompanyPricingTier::create([
            'company_id' => $reseller->id,
            'child_company_id' => $client->id,
            'pricing_type' => 'inbound',
            'cost_price' => 0.30,
            'sell_price' => 0.40,
            'is_active' => true
        ]);

        // First lookup should cache the result
        $tier1 = $this->pricingService->getApplicablePricingTier($client, 'inbound');
        $tier2 = $this->pricingService->getApplicablePricingTier($client, 'inbound');

        $this->assertNotNull($tier1);
        $this->assertNotNull($tier2);
        $this->assertEquals($tier1->id, $tier2->id);
    }

    public function test_update_client_pricing_validates_ownership()
    {
        $reseller1 = Company::factory()->create(['company_type' => 'reseller']);
        $reseller2 = Company::factory()->create(['company_type' => 'reseller']);
        $client = Company::factory()->create([
            'company_type' => 'client',
            'parent_company_id' => $reseller1->id
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Client does not belong to this reseller');

        $this->pricingService->updateClientPricing($reseller2, $client, [
            'pricing_type' => 'inbound',
            'cost_price' => 0.30,
            'sell_price' => 0.40
        ]);
    }

    public function test_margin_report_calculates_correctly()
    {
        $reseller = Company::factory()->create(['company_type' => 'reseller']);
        $client = Company::factory()->create([
            'company_type' => 'client',
            'parent_company_id' => $reseller->id
        ]);

        CompanyPricingTier::create([
            'company_id' => $reseller->id,
            'child_company_id' => $client->id,
            'pricing_type' => 'inbound',
            'cost_price' => 0.30,
            'sell_price' => 0.50,
            'is_active' => true
        ]);

        // Create test calls
        $startDate = now()->subDays(7);
        $endDate = now();
        
        Call::factory()->count(3)->create([
            'company_id' => $client->id,
            'duration_minutes' => 10,
            'direction' => 'inbound',
            'created_at' => $startDate->copy()->addDays(1)
        ]);

        $report = $this->pricingService->getMarginReport($reseller, $startDate, $endDate);

        $this->assertEquals($reseller->name, $report['reseller']);
        $this->assertCount(1, $report['clients']);
        $this->assertEquals(3, $report['clients'][0]['calls']);
        $this->assertEquals(30, $report['clients'][0]['minutes']);
        $this->assertGreaterThan(0, $report['totals']['margin']);
    }

    public function test_outbound_calls_use_correct_pricing()
    {
        $reseller = Company::factory()->create(['company_type' => 'reseller']);
        $client = Company::factory()->create([
            'company_type' => 'client',
            'parent_company_id' => $reseller->id
        ]);

        // Create separate pricing for outbound calls
        CompanyPricingTier::create([
            'company_id' => $reseller->id,
            'child_company_id' => $client->id,
            'pricing_type' => 'outbound',
            'cost_price' => 0.35,
            'sell_price' => 0.60,
            'is_active' => true
        ]);

        $call = Call::factory()->create([
            'company_id' => $client->id,
            'duration_minutes' => 5,
            'direction' => 'outbound'
        ]);

        $cost = $this->pricingService->calculateCallCost($call);

        $this->assertEquals(3.00, $cost['sell_cost']); // 5 min × 0.60€
        $this->assertEquals(1.75, $cost['base_cost']); // 5 min × 0.35€
        $this->assertEquals(1.25, $cost['margin']); // 3.00 - 1.75
    }

    public function test_setup_fee_and_monthly_fee_handling()
    {
        $reseller = Company::factory()->create(['company_type' => 'reseller']);
        $client = Company::factory()->create([
            'company_type' => 'client',
            'parent_company_id' => $reseller->id
        ]);

        $pricingData = [
            'pricing_type' => 'inbound',
            'cost_price' => 0.30,
            'sell_price' => 0.45,
            'setup_fee' => 150.00,
            'monthly_fee' => 49.99,
            'included_minutes' => 1000,
            'is_active' => true
        ];

        $tier = $this->pricingService->updateClientPricing($reseller, $client, $pricingData);

        $this->assertEquals(150.00, $tier->setup_fee);
        $this->assertEquals(49.99, $tier->monthly_fee);
        $this->assertEquals(1000, $tier->included_minutes);
        $this->assertTrue($tier->is_active);
    }

    public function test_inactive_pricing_tiers_are_ignored()
    {
        $reseller = Company::factory()->create(['company_type' => 'reseller']);
        $client = Company::factory()->create([
            'company_type' => 'client',
            'parent_company_id' => $reseller->id,
            'price_per_minute' => 0.25 // fallback price
        ]);

        // Create inactive pricing tier
        CompanyPricingTier::create([
            'company_id' => $reseller->id,
            'child_company_id' => $client->id,
            'pricing_type' => 'inbound',
            'cost_price' => 0.30,
            'sell_price' => 0.40,
            'is_active' => false
        ]);

        $call = Call::factory()->create([
            'company_id' => $client->id,
            'duration_minutes' => 10,
            'direction' => 'inbound'
        ]);

        $cost = $this->pricingService->calculateCallCost($call);

        // Should use fallback pricing since tier is inactive
        $this->assertEquals(2.50, $cost['sell_cost']); // 10 min × 0.25€
    }
}