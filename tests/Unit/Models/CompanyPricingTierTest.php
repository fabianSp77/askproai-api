<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\CompanyPricingTier;
use App\Models\Company;
use App\Models\PricingMargin;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompanyPricingTierTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_pricing_tier()
    {
        $reseller = Company::factory()->create(['company_type' => 'reseller']);
        $client = Company::factory()->create(['company_type' => 'client']);

        $tier = CompanyPricingTier::create([
            'company_id' => $reseller->id,
            'child_company_id' => $client->id,
            'pricing_type' => 'inbound',
            'cost_price' => 0.30,
            'sell_price' => 0.45,
            'included_minutes' => 1000,
            'is_active' => true
        ]);

        $this->assertInstanceOf(CompanyPricingTier::class, $tier);
        $this->assertEquals('inbound', $tier->pricing_type);
        $this->assertEquals(0.30, $tier->cost_price);
        $this->assertEquals(0.45, $tier->sell_price);
        $this->assertTrue($tier->is_active);
    }

    public function test_belongs_to_company_relationships()
    {
        $reseller = Company::factory()->create(['company_type' => 'reseller']);
        $client = Company::factory()->create(['company_type' => 'client']);

        $tier = CompanyPricingTier::create([
            'company_id' => $reseller->id,
            'child_company_id' => $client->id,
            'pricing_type' => 'inbound',
            'cost_price' => 0.30,
            'sell_price' => 0.45,
            'is_active' => true
        ]);

        $this->assertInstanceOf(Company::class, $tier->company);
        $this->assertInstanceOf(Company::class, $tier->childCompany);
        $this->assertEquals($reseller->id, $tier->company->id);
        $this->assertEquals($client->id, $tier->childCompany->id);
    }

    public function test_has_many_margins_relationship()
    {
        $reseller = Company::factory()->create(['company_type' => 'reseller']);
        $client = Company::factory()->create(['company_type' => 'client']);

        $tier = CompanyPricingTier::create([
            'company_id' => $reseller->id,
            'child_company_id' => $client->id,
            'pricing_type' => 'inbound',
            'cost_price' => 0.30,
            'sell_price' => 0.45,
            'is_active' => true
        ]);

        // Create some margin records
        PricingMargin::factory()->count(3)->create([
            'company_pricing_tier_id' => $tier->id
        ]);

        $this->assertCount(3, $tier->margins);
        $this->assertInstanceOf(PricingMargin::class, $tier->margins->first());
    }

    public function test_calculate_margin_with_valid_prices()
    {
        $tier = CompanyPricingTier::factory()->make([
            'cost_price' => 0.25,
            'sell_price' => 0.50
        ]);

        $margin = $tier->calculateMargin();

        $this->assertEquals(0.25, $margin['amount']); // 0.50 - 0.25
        $this->assertEquals(100, $margin['percentage']); // (0.25 / 0.25) * 100
    }

    public function test_calculate_margin_with_zero_cost_price()
    {
        $tier = CompanyPricingTier::factory()->make([
            'cost_price' => 0,
            'sell_price' => 0.40
        ]);

        $margin = $tier->calculateMargin();

        $this->assertEquals(0.40, $margin['amount']);
        $this->assertEquals(0, $margin['percentage']); // Avoid division by zero
    }

    public function test_calculate_cost_with_no_included_minutes()
    {
        $tier = CompanyPricingTier::factory()->make([
            'cost_price' => 0.30,
            'sell_price' => 0.45,
            'included_minutes' => 0
        ]);

        $cost = $tier->calculateCost(100);

        $this->assertEquals(30.00, $cost['base_cost']); // 100 * 0.30
        $this->assertEquals(45.00, $cost['sell_cost']); // 100 * 0.45
        $this->assertEquals(15.00, $cost['margin']); // 45 - 30
        $this->assertEquals(0, $cost['included_minutes_used']);
        $this->assertEquals(100, $cost['billable_minutes']);
    }

    public function test_calculate_cost_with_included_minutes_not_exceeded()
    {
        $tier = CompanyPricingTier::factory()->make([
            'cost_price' => 0.30,
            'sell_price' => 0.45,
            'included_minutes' => 200
        ]);

        $cost = $tier->calculateCost(100);

        $this->assertEquals(0, $cost['base_cost']); 
        $this->assertEquals(0, $cost['sell_cost']); 
        $this->assertEquals(0, $cost['margin']);
        $this->assertEquals(100, $cost['included_minutes_used']);
    }

    public function test_calculate_cost_with_included_minutes_exceeded()
    {
        $tier = CompanyPricingTier::factory()->make([
            'cost_price' => 0.30,
            'sell_price' => 0.45,
            'included_minutes' => 50
        ]);

        $cost = $tier->calculateCost(100);

        $this->assertEquals(15.00, $cost['base_cost']); // 50 overage * 0.30
        $this->assertEquals(22.50, $cost['sell_cost']); // 50 overage * 0.45
        $this->assertEquals(7.50, $cost['margin']); // 22.50 - 15.00
        $this->assertEquals(50, $cost['included_minutes_used']);
        $this->assertEquals(50, $cost['billable_minutes']);
    }

    public function test_active_scope()
    {
        $active = CompanyPricingTier::factory()->create(['is_active' => true]);
        $inactive = CompanyPricingTier::factory()->create(['is_active' => false]);

        $activeTiers = CompanyPricingTier::active()->get();

        $this->assertCount(1, $activeTiers);
        $this->assertEquals($active->id, $activeTiers->first()->id);
    }

    public function test_reseller_own_scope()
    {
        $resellerTier = CompanyPricingTier::factory()->create(['child_company_id' => null]);
        $clientTier = CompanyPricingTier::factory()->create(['child_company_id' => 123]);

        $resellerTiers = CompanyPricingTier::resellerOwn()->get();

        $this->assertCount(1, $resellerTiers);
        $this->assertEquals($resellerTier->id, $resellerTiers->first()->id);
    }

    public function test_for_client_scope()
    {
        $client1Id = 123;
        $client2Id = 456;
        
        $tier1 = CompanyPricingTier::factory()->create(['child_company_id' => $client1Id]);
        $tier2 = CompanyPricingTier::factory()->create(['child_company_id' => $client2Id]);

        $client1Tiers = CompanyPricingTier::forClient($client1Id)->get();

        $this->assertCount(1, $client1Tiers);
        $this->assertEquals($tier1->id, $client1Tiers->first()->id);
    }

    public function test_pricing_type_display_attribute()
    {
        $inboundTier = CompanyPricingTier::factory()->make(['pricing_type' => 'inbound']);
        $outboundTier = CompanyPricingTier::factory()->make(['pricing_type' => 'outbound']);
        $smsTier = CompanyPricingTier::factory()->make(['pricing_type' => 'sms']);
        $monthlyTier = CompanyPricingTier::factory()->make(['pricing_type' => 'monthly']);
        $setupTier = CompanyPricingTier::factory()->make(['pricing_type' => 'setup']);
        $customTier = CompanyPricingTier::factory()->make(['pricing_type' => 'custom']);

        $this->assertEquals('Eingehende Anrufe', $inboundTier->pricing_type_display);
        $this->assertEquals('Ausgehende Anrufe', $outboundTier->pricing_type_display);
        $this->assertEquals('SMS', $smsTier->pricing_type_display);
        $this->assertEquals('Monatliche Gebühr', $monthlyTier->pricing_type_display);
        $this->assertEquals('Einrichtungsgebühr', $setupTier->pricing_type_display);
        $this->assertEquals('Custom', $customTier->pricing_type_display);
    }

    public function test_decimal_casting_precision()
    {
        $tier = CompanyPricingTier::create([
            'company_id' => 1,
            'pricing_type' => 'inbound',
            'cost_price' => 0.12345,
            'sell_price' => 0.56789,
            'setup_fee' => 99.999,
            'monthly_fee' => 49.999,
            'overage_rate' => 0.87654,
            'is_active' => true
        ]);

        // Test precision casting
        $this->assertEquals('0.1235', (string) $tier->cost_price); // 4 decimals
        $this->assertEquals('0.5679', (string) $tier->sell_price); // 4 decimals
        $this->assertEquals('100.00', (string) $tier->setup_fee); // 2 decimals
        $this->assertEquals('50.00', (string) $tier->monthly_fee); // 2 decimals
        $this->assertEquals('0.8765', (string) $tier->overage_rate); // 4 decimals
    }

    public function test_metadata_array_casting()
    {
        $metadata = [
            'notes' => 'Special pricing for VIP client',
            'contract_reference' => 'CONTRACT-2025-001',
            'discount_percentage' => 15
        ];

        $tier = CompanyPricingTier::create([
            'company_id' => 1,
            'pricing_type' => 'inbound',
            'cost_price' => 0.30,
            'sell_price' => 0.45,
            'metadata' => $metadata,
            'is_active' => true
        ]);

        $this->assertIsArray($tier->metadata);
        $this->assertEquals($metadata, $tier->metadata);
        $this->assertEquals('Special pricing for VIP client', $tier->metadata['notes']);
    }
}