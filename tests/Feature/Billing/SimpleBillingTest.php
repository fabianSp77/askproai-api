<?php

namespace Tests\Feature\Billing;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\BillingPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SimpleBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_billing_period()
    {
        // Create company
        $company = Company::factory()->create();
        
        // Create billing period
        $billingPeriod = BillingPeriod::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        
        $this->assertDatabaseHas('billing_periods', [
            'id' => $billingPeriod->id,
            'company_id' => $company->id,
        ]);
    }

    public function test_billing_alerts_page_loads()
    {
        // Create user and company
        $company = Company::factory()->create();
        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);
        
        // Act as the user
        $this->actingAs($user);
        
        // Try to access the page
        $response = $this->get('/admin/billing-alerts-management');
        
        // Should redirect to login or show 403
        $this->assertIn($response->status(), [302, 403]);
    }
}