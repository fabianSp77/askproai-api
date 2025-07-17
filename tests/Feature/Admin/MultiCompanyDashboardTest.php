<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\PrepaidBalance;
use App\Models\Call;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Filament\Admin\Widgets\MultiCompanyOverviewWidget;

class MultiCompanyDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $regularAdmin;

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
    }

    /** @test */
    public function multi_company_widget_is_visible_for_super_admin()
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get('/admin');

        $response->assertOk();
        // Widget should be included in dashboard
        $this->assertTrue(MultiCompanyOverviewWidget::canView());
    }

    /** @test */
    public function multi_company_widget_is_not_visible_for_regular_admin()
    {
        $this->actingAs($this->regularAdmin);

        $response = $this->get('/admin');

        $response->assertOk();
        // Widget should not be visible
        $this->assertFalse(MultiCompanyOverviewWidget::canView());
    }

    /** @test */
    public function widget_shows_top_5_companies_by_activity()
    {
        $this->actingAs($this->superAdmin);

        // Create companies with different call counts
        $companies = [];
        for ($i = 0; $i < 7; $i++) {
            $company = Company::factory()->create();
            PrepaidBalance::factory()->create([
                'company_id' => $company->id,
                'balance' => rand(100, 1000),
            ]);
            
            // Create calls for today
            $callCount = 7 - $i; // Descending order
            for ($j = 0; $j < $callCount; $j++) {
                Call::factory()->create([
                    'company_id' => $company->id,
                    'created_at' => now(),
                ]);
            }
            
            $companies[] = $company;
        }

        Livewire::test(MultiCompanyOverviewWidget::class)
            ->assertSee('Kundenverwaltung - Multi-Company Übersicht')
            ->assertSee($companies[0]->name) // Most active
            ->assertSee($companies[1]->name)
            ->assertSee($companies[2]->name)
            ->assertSee($companies[3]->name)
            ->assertSee($companies[4]->name)
            ->assertDontSee($companies[5]->name) // Should not see 6th
            ->assertDontSee($companies[6]->name); // Should not see 7th
    }

    /** @test */
    public function widget_displays_company_statistics()
    {
        $this->actingAs($this->superAdmin);

        $company = Company::factory()->create(['name' => 'Test Company']);
        $balance = PrepaidBalance::factory()->create([
            'company_id' => $company->id,
            'balance' => 500.50,
            'low_balance_threshold' => 100.00,
        ]);

        // Create some calls for today
        Call::factory()->count(5)->create([
            'company_id' => $company->id,
            'created_at' => now(),
        ]);

        Livewire::test(MultiCompanyOverviewWidget::class)
            ->assertSee('Test Company')
            ->assertSee('500,50 €') // Balance
            ->assertSee('5') // Calls today
            ->assertSee('Portal'); // Portal button
    }

    /** @test */
    public function widget_shows_low_balance_warning()
    {
        $this->actingAs($this->superAdmin);

        $company = Company::factory()->create(['name' => 'Low Balance Company']);
        PrepaidBalance::factory()->create([
            'company_id' => $company->id,
            'balance' => 50.00,
            'low_balance_threshold' => 100.00,
        ]);

        Livewire::test(MultiCompanyOverviewWidget::class)
            ->assertSee('Low Balance Company')
            ->assertSee('Niedriges Guthaben');
    }

    /** @test */
    public function widget_shows_quick_action_buttons()
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(MultiCompanyOverviewWidget::class)
            ->assertSee('Alle Kunden verwalten')
            ->assertSee('Neuen Kunden anlegen')
            ->assertSee('Guthaben verwalten')
            ->assertSee('Heutige Aktivitäten');
    }

    /** @test */
    public function widget_calculates_total_statistics()
    {
        $this->actingAs($this->superAdmin);

        // Create 3 companies
        Company::factory()->count(3)->create();
        
        // 2 companies with calls today
        $activeCompanies = Company::factory()->count(2)->create();
        foreach ($activeCompanies as $company) {
            Call::factory()->create([
                'company_id' => $company->id,
                'created_at' => now(),
            ]);
        }

        $widget = new MultiCompanyOverviewWidget();
        $stats = $widget->getTotalStats();

        $this->assertEquals(5, $stats['total_companies']); // 3 + 2
        $this->assertEquals(2, $stats['active_today']);
        $this->assertEquals(2, $stats['total_calls_today']);
    }
}