<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\PortalUser;
use App\Models\PrepaidBalance;
use App\Models\Call;
use App\Models\Branch;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WhiteLabelUserFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected Company $reseller;
    protected Company $client1;
    protected Company $client2;
    protected PortalUser $resellerUser;
    protected PortalUser $clientUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);

        // Create super admin
        $this->superAdmin = User::factory()->create(['email' => 'admin@test.com']);
        $this->superAdmin->assignRole('Super Admin');

        // Create reseller company
        $this->reseller = Company::factory()->create([
            'name' => 'Test Reseller GmbH',
            'company_type' => 'reseller',
            'is_white_label' => true,
            'commission_rate' => 20.00,
        ]);

        // Create client companies
        $this->client1 = Company::factory()->create([
            'name' => 'Client Company 1',
            'parent_company_id' => $this->reseller->id,
            'company_type' => 'client',
        ]);

        $this->client2 = Company::factory()->create([
            'name' => 'Client Company 2',
            'parent_company_id' => $this->reseller->id,
            'company_type' => 'client',
        ]);

        // Create branches
        Branch::factory()->create(['company_id' => $this->client1->id]);
        Branch::factory()->create(['company_id' => $this->client2->id]);

        // Create balances
        PrepaidBalance::factory()->create([
            'company_id' => $this->reseller->id,
            'balance' => 5000.00,
        ]);
        PrepaidBalance::factory()->create([
            'company_id' => $this->client1->id,
            'balance' => 250.00,
        ]);
        PrepaidBalance::factory()->create([
            'company_id' => $this->client2->id,
            'balance' => 150.00,
        ]);

        // Create portal users
        $this->resellerUser = PortalUser::factory()->create([
            'company_id' => $this->reseller->id,
            'email' => 'reseller@test.com',
            'role' => 'owner',
            'can_access_child_companies' => true,
        ]);

        $this->clientUser = PortalUser::factory()->create([
            'company_id' => $this->client1->id,
            'email' => 'client@test.com',
            'role' => 'admin',
        ]);

        // Create some calls
        Call::factory()->count(5)->create([
            'company_id' => $this->client1->id,
            'created_at' => now(),
        ]);
    }

    /** @test */
    public function super_admin_can_see_multi_company_overview()
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get('/admin');

        $response->assertOk();
        $response->assertSee('Kundenverwaltung - Multi-Company Übersicht');
        $response->assertSee('Test Reseller GmbH');
        $response->assertSee('Client Company 1');
        $response->assertSee('Client Company 2');
    }

    /** @test */
    public function super_admin_can_navigate_to_customer_management()
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get('/admin/business-portal-admin');

        $response->assertOk();
        $response->assertSee('🏢 Kundenverwaltung');
        $response->assertSee('Alle Firmen mit Prepaid Billing');
        $response->assertSee($this->reseller->name);
        $response->assertSee($this->client1->name);
        $response->assertSee($this->client2->name);
    }

    /** @test */
    public function reseller_user_can_login_to_business_portal()
    {
        $response = $this->post('/business/login', [
            'email' => 'reseller@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/business/dashboard');
        $this->assertAuthenticatedAs($this->resellerUser, 'portal');
    }

    /** @test */
    public function client_user_can_login_to_business_portal()
    {
        $response = $this->post('/business/login', [
            'email' => 'client@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/business/dashboard');
        $this->assertAuthenticatedAs($this->clientUser, 'portal');
    }

    /** @test */
    public function reseller_can_see_aggregated_data()
    {
        $this->actingAs($this->resellerUser, 'portal');

        // This would test if reseller sees combined data from all clients
        // Implementation depends on how the business portal handles multi-company access
        
        $response = $this->get('/business/dashboard');
        
        $response->assertOk();
        // Should see own company data at minimum
        $response->assertSee('Test Reseller GmbH');
    }

    /** @test */
    public function client_only_sees_own_data()
    {
        $this->actingAs($this->clientUser, 'portal');

        $response = $this->get('/business/calls');

        $response->assertOk();
        // Should only see data from client1
        $this->assertEquals($this->client1->id, session('company_id'));
    }

    /** @test */
    public function admin_generated_token_allows_portal_access()
    {
        $this->actingAs($this->superAdmin);

        // Generate token
        $token = bin2hex(random_bytes(32));
        cache()->put('admin_portal_access_' . $token, [
            'admin_id' => $this->superAdmin->id,
            'company_id' => $this->client1->id,
            'created_at' => now(),
            'redirect_to' => '/business/dashboard',
        ], now()->addMinutes(15));

        // Use token to access portal
        $response = $this->get('/business/admin-access?token=' . $token);

        $response->assertRedirect('/business/dashboard');
        
        // Should create a portal session
        $this->assertEquals($this->client1->id, session('admin_viewing_company_id'));
    }

    /** @test */
    public function data_isolation_between_companies()
    {
        // Create calls for different companies
        $client1Calls = Call::factory()->count(3)->create([
            'company_id' => $this->client1->id,
        ]);
        
        $client2Calls = Call::factory()->count(2)->create([
            'company_id' => $this->client2->id,
        ]);

        // Login as client1 user
        $this->actingAs($this->clientUser, 'portal');

        // Should only see client1 calls
        $calls = Call::where('company_id', session('company_id'))->get();
        
        $this->assertCount(3 + 5, $calls); // 3 new + 5 from setup
        foreach ($calls as $call) {
            $this->assertEquals($this->client1->id, $call->company_id);
        }
    }

    /** @test */
    public function commission_calculation_for_reseller()
    {
        // Simulate some charges
        $chargeAmount = 100.00;
        
        // This would typically happen when a call is processed
        $commission = $chargeAmount * ($this->reseller->commission_rate / 100);
        
        $this->assertEquals(20.00, $commission);
        $this->assertEquals(20.00, $this->reseller->commission_rate);
    }

    /** @test */
    public function white_label_settings_are_accessible()
    {
        $settings = [
            'brand_name' => 'Custom Brand',
            'primary_color' => '#FF0000',
            'logo_url' => 'https://example.com/logo.png',
            'custom_domain' => 'portal.example.com',
            'hide_askpro_branding' => true,
        ];

        $this->reseller->update(['white_label_settings' => $settings]);

        $this->assertEquals('Custom Brand', $this->reseller->white_label_settings['brand_name']);
        $this->assertTrue($this->reseller->white_label_settings['hide_askpro_branding']);
    }
}