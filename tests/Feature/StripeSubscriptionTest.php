<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Models\Company;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Billing\StripeSubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
use Stripe\StripeClient;

class StripeSubscriptionTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    
    protected $stripeService;
    protected $mockStripe;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock Stripe client
        $this->mockStripe = Mockery::mock(StripeClient::class);
        $this->app->instance(StripeClient::class, $this->mockStripe);
        
        // Create service instance
        $this->stripeService = new StripeSubscriptionService();
    }
    
    public function test_subscription_model_relationships()
    {
        $company = Company::factory()->create();
        $subscription = Subscription::create([
            'company_id' => $company->id,
            'stripe_subscription_id' => 'sub_test123',
            'stripe_customer_id' => 'cus_test123',
            'stripe_status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);
        
        $this->assertInstanceOf(Company::class, $subscription->company);
        $this->assertEquals($company->id, $subscription->company->id);
    }
    
    public function test_subscription_status_checks()
    {
        $subscription = Subscription::create([
            'company_id' => Company::factory()->create()->id,
            'stripe_subscription_id' => 'sub_test123',
            'stripe_customer_id' => 'cus_test123',
            'stripe_status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);
        
        $this->assertTrue($subscription->active());
        $this->assertFalse($subscription->onTrial());
        $this->assertFalse($subscription->canceled());
        $this->assertFalse($subscription->pastDue());
        
        // Test trial subscription
        $trialSubscription = Subscription::create([
            'company_id' => Company::factory()->create()->id,
            'stripe_subscription_id' => 'sub_test456',
            'stripe_customer_id' => 'cus_test456',
            'stripe_status' => 'trialing',
            'trial_ends_at' => now()->addDays(7),
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);
        
        $this->assertTrue($trialSubscription->active());
        $this->assertTrue($trialSubscription->onTrial());
        $this->assertEquals(7, $trialSubscription->trialDaysRemaining());
    }
    
    public function test_subscription_scope_queries()
    {
        // Create various subscriptions
        $active = Subscription::create([
            'company_id' => Company::factory()->create()->id,
            'stripe_subscription_id' => 'sub_active',
            'stripe_customer_id' => 'cus_active',
            'stripe_status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);
        
        $pastDue = Subscription::create([
            'company_id' => Company::factory()->create()->id,
            'stripe_subscription_id' => 'sub_past_due',
            'stripe_customer_id' => 'cus_past_due',
            'stripe_status' => 'past_due',
            'current_period_start' => now()->subMonth(),
            'current_period_end' => now()->subDays(5),
        ]);
        
        $canceled = Subscription::create([
            'company_id' => Company::factory()->create()->id,
            'stripe_subscription_id' => 'sub_canceled',
            'stripe_customer_id' => 'cus_canceled',
            'stripe_status' => 'canceled',
            'ends_at' => now()->subDay(),
            'current_period_start' => now()->subMonth(),
            'current_period_end' => now()->subDay(),
        ]);
        
        // Test scopes
        $this->assertEquals(1, Subscription::active()->count());
        $this->assertEquals(1, Subscription::needsAttention()->count());
        $this->assertTrue(Subscription::active()->first()->is($active));
        $this->assertTrue(Subscription::needsAttention()->first()->is($pastDue));
    }
    
    public function test_company_subscription_methods()
    {
        $company = Company::factory()->create();
        
        // No subscription yet
        $this->assertNull($company->activeSubscription());
        $this->assertFalse($company->hasActiveSubscription());
        
        // Create active subscription
        $subscription = Subscription::create([
            'company_id' => $company->id,
            'stripe_subscription_id' => 'sub_test',
            'stripe_customer_id' => 'cus_test',
            'stripe_status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);
        
        $company->refresh();
        $this->assertNotNull($company->activeSubscription());
        $this->assertTrue($company->hasActiveSubscription());
        $this->assertTrue($company->activeSubscription()->is($subscription));
    }
    
    public function test_billing_api_endpoints_require_auth()
    {
        $this->getJson('/api/billing/plans')->assertStatus(401);
        $this->postJson('/api/billing/subscriptions')->assertStatus(401);
        $this->postJson('/api/billing/portal-session')->assertStatus(401);
    }
    
    public function test_billing_api_get_plans()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        // Mock Stripe responses
        $mockProducts = $this->mockStripe->shouldReceive('products')->andReturn(
            Mockery::mock(['all' => (object)['data' => []]])
        );
        
        $mockPrices = $this->mockStripe->shouldReceive('prices')->andReturn(
            Mockery::mock(['all' => (object)['data' => []]])
        );
        
        $response = $this->getJson('/api/billing/plans');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'plans'
            ]);
    }
}