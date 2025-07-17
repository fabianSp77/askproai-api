<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\PortalUser;
use App\Models\PrepaidBalance;
use App\Models\BalanceTransaction;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Filament\Admin\Pages\BusinessPortalAdmin;

class BusinessPortalAdminTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $regularAdmin;
    protected Company $testCompany;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        Role::create(['name' => 'Admin', 'guard_name' => 'web']);

        // Create users
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('Super Admin');

        $this->regularAdmin = User::factory()->create();
        $this->regularAdmin->assignRole('Admin');

        // Create test company
        $this->testCompany = Company::factory()->create(['name' => 'Test Company']);
        PrepaidBalance::factory()->create([
            'company_id' => $this->testCompany->id,
            'balance' => 250.00,
        ]);
    }

    /** @test */
    public function page_is_accessible_for_super_admin()
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get('/admin/business-portal-admin');

        $response->assertOk();
        $response->assertSee('B2B Portal Admin');
    }

    /** @test */
    public function page_is_not_accessible_for_regular_admin()
    {
        $this->actingAs($this->regularAdmin);

        $response = $this->get('/admin/business-portal-admin');

        $response->assertForbidden();
    }

    /** @test */
    public function can_select_company_and_view_stats()
    {
        $this->actingAs($this->superAdmin);

        // Create portal users
        PortalUser::factory()->count(3)->create([
            'company_id' => $this->testCompany->id,
        ]);

        Livewire::test(BusinessPortalAdmin::class)
            ->set('selectedCompanyId', $this->testCompany->id)
            ->assertSee($this->testCompany->name)
            ->assertSee('250,00 €') // Balance
            ->assertSee('3') // Portal users count
            ->assertSee('Kundenportal öffnen')
            ->assertSee('Guthaben anpassen');
    }

    /** @test */
    public function can_adjust_company_balance()
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(BusinessPortalAdmin::class)
            ->set('selectedCompanyId', $this->testCompany->id)
            ->call('adjustBalance')
            ->assertDispatched('open-modal', id: 'adjust-balance-modal')
            ->set('adjustmentType', 'credit')
            ->set('adjustmentAmount', '50.00')
            ->set('adjustmentDescription', 'Test credit')
            ->call('processBalanceAdjustment')
            ->assertDispatched('close-modal', id: 'adjust-balance-modal');

        // Check balance was updated
        $this->testCompany->refresh();
        $this->assertEquals(300.00, $this->testCompany->prepaidBalance->balance);

        // Check transaction was created
        $this->assertDatabaseHas('balance_transactions', [
            'company_id' => $this->testCompany->id,
            'type' => 'credit',
            'amount' => 50.00,
            'description' => 'Test credit',
        ]);
    }

    /** @test */
    public function shows_all_companies_table()
    {
        $this->actingAs($this->superAdmin);

        // Create multiple companies
        $companies = Company::factory()->count(3)->create();
        foreach ($companies as $company) {
            PrepaidBalance::factory()->create([
                'company_id' => $company->id,
                'balance' => rand(100, 500),
            ]);
        }

        Livewire::test(BusinessPortalAdmin::class)
            ->assertSee('Alle Firmen mit Prepaid Billing')
            ->assertSee($companies[0]->name)
            ->assertSee($companies[1]->name)
            ->assertSee($companies[2]->name)
            ->assertSee('Portal öffnen');
    }

    /** @test */
    public function can_open_portal_for_company()
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(BusinessPortalAdmin::class)
            ->set('selectedCompanyId', $this->testCompany->id)
            ->call('openCustomerPortal')
            ->assertDispatched('redirect-to-portal');

        // Check that token was created in cache
        $this->assertNotNull(cache()->get('admin_portal_access_' . $this->testCompany->id));
    }

    /** @test */
    public function can_open_portal_directly_from_table()
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(BusinessPortalAdmin::class)
            ->call('openPortalForCompany', $this->testCompany->id)
            ->assertSet('selectedCompanyId', $this->testCompany->id)
            ->assertDispatched('redirect-to-portal');
    }

    /** @test */
    public function shows_parent_child_relationships()
    {
        $this->actingAs($this->superAdmin);

        // Create reseller with clients
        $reseller = Company::factory()->create([
            'name' => 'Reseller Company',
            'company_type' => 'reseller',
        ]);
        
        $client1 = Company::factory()->create([
            'name' => 'Client 1',
            'parent_company_id' => $reseller->id,
            'company_type' => 'client',
        ]);
        
        $client2 = Company::factory()->create([
            'name' => 'Client 2',
            'parent_company_id' => $reseller->id,
            'company_type' => 'client',
        ]);

        // Create balances
        foreach ([$reseller, $client1, $client2] as $company) {
            PrepaidBalance::factory()->create(['company_id' => $company->id]);
        }

        Livewire::test(BusinessPortalAdmin::class)
            ->assertSee('Reseller Company')
            ->assertSee('Client 1')
            ->assertSee('Client 2');
    }

    /** @test */
    public function auto_opens_portal_when_url_parameter_provided()
    {
        $this->actingAs($this->superAdmin);

        // Visit page with open_company parameter
        $response = $this->get('/admin/business-portal-admin?open_company=' . $this->testCompany->id);
        
        $response->assertOk();
        
        // Component should auto-dispatch the event
        Livewire::test(BusinessPortalAdmin::class)
            ->assertSet('selectedCompanyId', $this->testCompany->id)
            ->assertDispatched('auto-open-portal', ['companyId' => $this->testCompany->id]);
    }
}