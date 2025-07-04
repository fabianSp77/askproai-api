<?php

namespace Tests\Unit\Services\Billing;

use Tests\TestCase;
use App\Services\Billing\AdvancedPricingService;
use App\Models\Company;
use App\Models\PricingPlan;
use App\Models\ServiceAddon;
use App\Models\Subscription;
use App\Models\PriceRule;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class AdvancedPricingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AdvancedPricingService $service;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new AdvancedPricingService();
        $this->company = Company::factory()->create();
    }

    public function test_calculate_subscription_cost_with_base_price_only()
    {
        $plan = PricingPlan::factory()->create([
            'company_id' => $this->company->id,
            'base_price' => 99.99,
            'currency' => 'EUR',
        ]);

        $subscription = Subscription::factory()->create([
            'company_id' => $this->company->id,
            'pricing_plan_id' => $plan->id,
        ]);

        $cost = $this->service->calculateSubscriptionCost($subscription);

        $this->assertEquals(99.99, $cost['base_price']);
        $this->assertEquals(99.99, $cost['total']);
        $this->assertEquals('EUR', $cost['currency']);
        $this->assertEmpty($cost['addons']);
        $this->assertEmpty($cost['discounts']);
    }

    public function test_calculate_subscription_cost_with_custom_price()
    {
        $plan = PricingPlan::factory()->create([
            'company_id' => $this->company->id,
            'base_price' => 99.99,
        ]);

        $subscription = Subscription::factory()->create([
            'company_id' => $this->company->id,
            'pricing_plan_id' => $plan->id,
            'custom_price' => 79.99,
        ]);

        $cost = $this->service->calculateSubscriptionCost($subscription);

        $this->assertEquals(79.99, $cost['base_price']);
        $this->assertEquals(79.99, $cost['total']);
    }

    public function test_calculate_subscription_cost_with_addons()
    {
        $plan = PricingPlan::factory()->create([
            'company_id' => $this->company->id,
            'base_price' => 100.00,
        ]);

        $subscription = Subscription::factory()->create([
            'company_id' => $this->company->id,
            'pricing_plan_id' => $plan->id,
        ]);

        $addon1 = ServiceAddon::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'SMS Package',
            'price' => 20.00,
        ]);

        $addon2 = ServiceAddon::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Priority Support',
            'price' => 30.00,
        ]);

        $subscription->addons()->attach($addon1->id, [
            'quantity' => 1,
            'start_date' => now(),
            'status' => 'active',
        ]);

        $subscription->addons()->attach($addon2->id, [
            'quantity' => 2,
            'price_override' => 25.00,
            'start_date' => now(),
            'status' => 'active',
        ]);

        $cost = $this->service->calculateSubscriptionCost($subscription);

        $this->assertEquals(100.00, $cost['base_price']);
        $this->assertEquals(170.00, $cost['total']); // 100 + 20 + (25 * 2)
        $this->assertCount(2, $cost['addons']);
        
        // Check addon details
        $this->assertEquals('SMS Package', $cost['addons'][0]['name']);
        $this->assertEquals(20.00, $cost['addons'][0]['total_price']);
        
        $this->assertEquals('Priority Support', $cost['addons'][1]['name']);
        $this->assertEquals(50.00, $cost['addons'][1]['total_price']); // 25 * 2
    }

    public function test_calculate_subscription_cost_with_price_rules()
    {
        $plan = PricingPlan::factory()->create([
            'company_id' => $this->company->id,
            'base_price' => 100.00,
        ]);

        $subscription = Subscription::factory()->create([
            'company_id' => $this->company->id,
            'pricing_plan_id' => $plan->id,
        ]);

        // Create a 20% discount rule
        $rule = PriceRule::factory()->create([
            'company_id' => $this->company->id,
            'pricing_plan_id' => $plan->id,
            'name' => 'Weekend Discount',
            'type' => 'time_based',
            'conditions' => [
                'day_of_week' => [strtolower(Carbon::now()->format('l'))],
            ],
            'modification_type' => 'percentage',
            'modification_value' => 20,
            'is_active' => true,
        ]);

        $context = [
            'current_time' => Carbon::now(),
        ];

        $cost = $this->service->calculateSubscriptionCost($subscription, $context);

        $this->assertEquals(100.00, $cost['base_price']);
        $this->assertEquals(80.00, $cost['total']); // 20% off
        $this->assertCount(1, $cost['discounts']);
        $this->assertEquals(20.00, $cost['discounts'][0]['amount']);
        $this->assertContains('Weekend Discount', $cost['rules_applied']);
    }

    public function test_calculate_overage_charges()
    {
        $plan = PricingPlan::factory()->create([
            'company_id' => $this->company->id,
            'included_minutes' => 100,
            'included_appointments' => 10,
            'overage_price_per_minute' => 0.05,
            'overage_price_per_appointment' => 2.50,
        ]);

        $subscription = Subscription::factory()->create([
            'company_id' => $this->company->id,
            'pricing_plan_id' => $plan->id,
        ]);

        $overages = $this->service->calculateOverageCharges($subscription, 150, 12);

        $this->assertEquals(50, $overages['minutes']['overage']);
        $this->assertEquals(2.50, $overages['minutes']['cost']); // 50 * 0.05
        
        $this->assertEquals(2, $overages['appointments']['overage']);
        $this->assertEquals(5.00, $overages['appointments']['cost']); // 2 * 2.50
        
        $this->assertEquals(7.50, $overages['total']);
    }

    public function test_get_available_addons_filters_incompatible()
    {
        $plan = PricingPlan::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'package',
            'base_price' => 50.00,
            'included_features' => ['basic_support'],
        ]);

        $subscription = Subscription::factory()->create([
            'company_id' => $this->company->id,
            'pricing_plan_id' => $plan->id,
        ]);

        // Compatible addon
        $addon1 = ServiceAddon::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'SMS Package',
            'requirements' => [],
        ]);

        // Incompatible - requires higher plan
        $addon2 = ServiceAddon::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Enterprise Features',
            'requirements' => [
                'min_plan_price' => 100.00,
            ],
        ]);

        // Incompatible - requires different plan type
        $addon3 = ServiceAddon::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Usage Analytics',
            'requirements' => [
                'plan_types' => ['usage_based', 'hybrid'],
            ],
        ]);

        // Already subscribed
        $addon4 = ServiceAddon::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Already Have This',
        ]);
        $subscription->addons()->attach($addon4->id, [
            'start_date' => now(),
            'status' => 'active',
        ]);

        $available = $this->service->getAvailableAddons($subscription);

        $this->assertCount(1, $available);
        $this->assertEquals('SMS Package', $available->first()->name);
    }

    public function test_add_addon_to_subscription()
    {
        $plan = PricingPlan::factory()->create(['company_id' => $this->company->id]);
        $subscription = Subscription::factory()->create([
            'company_id' => $this->company->id,
            'pricing_plan_id' => $plan->id,
        ]);

        $addon = ServiceAddon::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'SMS Package',
            'price' => 20.00,
        ]);

        $result = $this->service->addAddonToSubscription($subscription, $addon, [
            'quantity' => 2,
            'price_override' => 15.00,
        ]);

        $this->assertTrue($result);
        
        $subscription->refresh();
        $attachedAddon = $subscription->addons()->first();
        
        $this->assertNotNull($attachedAddon);
        $this->assertEquals(2, $attachedAddon->pivot->quantity);
        $this->assertEquals(15.00, $attachedAddon->pivot->price_override);
        $this->assertEquals('active', $attachedAddon->pivot->status);
    }

    public function test_remove_addon_from_subscription_immediately()
    {
        $plan = PricingPlan::factory()->create(['company_id' => $this->company->id]);
        $subscription = Subscription::factory()->create([
            'company_id' => $this->company->id,
            'pricing_plan_id' => $plan->id,
        ]);

        $addon = ServiceAddon::factory()->create(['company_id' => $this->company->id]);
        
        $subscription->addons()->attach($addon->id, [
            'start_date' => now()->subDays(10),
            'status' => 'active',
        ]);

        $result = $this->service->removeAddonFromSubscription($subscription, $addon, true);

        $this->assertTrue($result);
        
        $subscription->refresh();
        $removedAddon = $subscription->addons()->first();
        
        $this->assertEquals('cancelled', $removedAddon->pivot->status);
        $this->assertTrue(Carbon::parse($removedAddon->pivot->end_date)->isToday());
    }

    public function test_apply_promo_code_success()
    {
        $plan = PricingPlan::factory()->create([
            'company_id' => $this->company->id,
            'base_price' => 100.00,
        ]);

        $subscription = Subscription::factory()->create([
            'company_id' => $this->company->id,
            'pricing_plan_id' => $plan->id,
        ]);

        $promoRule = PriceRule::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'promotional',
            'conditions' => [
                'promo_code' => 'SAVE20',
                'max_uses' => 100,
            ],
            'modification_type' => 'percentage',
            'modification_value' => 20,
            'is_active' => true,
        ]);

        $result = $this->service->applyPromoCode($subscription, 'SAVE20');

        $this->assertTrue($result['success']);
        $this->assertEquals(20.00, $result['discount']);
        $this->assertStringContainsString('applied successfully', $result['message']);
        
        // Check that usage was recorded
        $this->assertDatabaseHas('promo_code_uses', [
            'price_rule_id' => $promoRule->id,
            'subscription_id' => $subscription->id,
        ]);
    }

    public function test_apply_promo_code_invalid()
    {
        $subscription = Subscription::factory()->create(['company_id' => $this->company->id]);

        $result = $this->service->applyPromoCode($subscription, 'INVALID');

        $this->assertFalse($result['success']);
        $this->assertEquals(0, $result['discount']);
        $this->assertStringContainsString('Invalid or expired', $result['message']);
    }
}