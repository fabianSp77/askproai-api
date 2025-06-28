<?php

namespace Tests\Feature\Dashboard;

use App\Filament\Admin\Widgets\AppointmentKpiWidget;
use App\Filament\Admin\Widgets\CallKpiWidget;
use App\Filament\Admin\Widgets\GlobalFilterWidget;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GlobalFilterSynchronizationTest extends TestCase
{
    use RefreshDatabase;
    
    private User $user;
    private Company $company;
    private Branch $branch;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user with company
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        
        $this->actingAs($this->user);
    }    #[Test]
    public function global_filter_widget_initializes_with_default_values()
    {
        Livewire::test(GlobalFilterWidget::class)
            ->assertSet('globalFilters.company_id', $this->company->id)
            ->assertSet('globalFilters.period', 'today')
            ->assertSet('globalFilters.branch_id', null)
            ->assertSet('globalFilters.staff_id', null)
            ->assertSet('globalFilters.service_id', null);
    }    #[Test]
    public function changing_period_broadcasts_to_other_widgets()
    {
        // Test global filter widget
        $filterWidget = Livewire::test(GlobalFilterWidget::class);
        
        // Test KPI widget listening for events
        $kpiWidget = Livewire::test(AppointmentKpiWidget::class);
        
        // Change period in filter widget
        $filterWidget->call('updatedGlobalFiltersPeriod', 'this_week');
        
        // Assert event was dispatched
        $filterWidget->assertDispatched('global-filter-updated');
        
        // Assert filter was persisted in session
        $this->assertEquals('this_week', Session::get('dashboard.period'));
    }    #[Test]
    public function branch_filter_updates_staff_and_service_dropdowns()
    {
        // Create additional branches, staff, and services
        $branch2 = Branch::factory()->create(['company_id' => $this->company->id]);
        
        $staff1 = Staff::factory()->create(['company_id' => $this->company->id]);
        $staff2 = Staff::factory()->create(['company_id' => $this->company->id]);
        
        // Assign staff to branches
        $staff1->branches()->attach($this->branch);
        $staff2->branches()->attach($branch2);
        
        $service1 = Service::factory()->create(['company_id' => $this->company->id]);
        $service2 = Service::factory()->create(['company_id' => $this->company->id]);
        
        // Assign services to branches
        $this->branch->services()->attach($service1);
        $branch2->services()->attach($service2);
        
        $widget = Livewire::test(GlobalFilterWidget::class);
        
        // Initially, all staff and services should be available
        $allStaff = $widget->call('getStaff');
        $this->assertCount(2, $allStaff);
        
        // Select first branch
        $widget->call('updatedGlobalFiltersBranchId', $this->branch->id);
        
        // Now only staff and services from first branch should be available
        $filteredStaff = $widget->call('getStaff');
        $this->assertCount(1, $filteredStaff);
        $this->assertArrayHasKey($staff1->id, $filteredStaff);
        $this->assertArrayNotHasKey($staff2->id, $filteredStaff);
        
        $filteredServices = $widget->call('getServices');
        $this->assertCount(1, $filteredServices);
        $this->assertArrayHasKey($service1->id, $filteredServices);
        $this->assertArrayNotHasKey($service2->id, $filteredServices);
    }    #[Test]
    public function custom_date_range_shows_date_picker()
    {
        $widget = Livewire::test(GlobalFilterWidget::class)
            ->assertSet('showDatePicker', false);
        
        // Select custom period
        $widget->call('updatedGlobalFiltersPeriod', 'custom');
        
        // Date picker should now be visible
        $widget->assertSet('showDatePicker', true)
            ->assertSet('globalFilters.period', 'custom');
        
        // Apply date range
        $widget->set('globalFilters.date_from', '2025-06-01')
            ->set('globalFilters.date_to', '2025-06-18')
            ->call('applyDateRange');
        
        // Assert event was dispatched with date range
        $widget->assertDispatched('global-filter-updated');
    }    #[Test]
    public function active_filter_count_updates_correctly()
    {
        $widget = Livewire::test(GlobalFilterWidget::class);
        
        // Initially only default filters
        $this->assertEquals(0, $widget->call('getActiveFilterCount'));
        
        // Change period from default
        $widget->set('globalFilters.period', 'this_week');
        $this->assertEquals(1, $widget->call('getActiveFilterCount'));
        
        // Add branch filter
        $widget->set('globalFilters.branch_id', $this->branch->id);
        $this->assertEquals(2, $widget->call('getActiveFilterCount'));
        
        // Add staff filter
        $staff = Staff::factory()->create(['company_id' => $this->company->id]);
        $widget->set('globalFilters.staff_id', $staff->id);
        $this->assertEquals(3, $widget->call('getActiveFilterCount'));
    }    #[Test]
    public function filter_description_shows_active_filters()
    {
        $staff = Staff::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'John Doe'
        ]);
        
        $widget = Livewire::test(GlobalFilterWidget::class);
        
        // Set multiple filters
        $widget->set('globalFilters.period', 'this_week')
            ->set('globalFilters.branch_id', $this->branch->id)
            ->set('globalFilters.staff_id', $staff->id);
        
        $description = $widget->call('getActiveFilterDescription');
        
        // Description should contain all active filters
        $this->assertStringContainsString('Diese Woche', $description);
        $this->assertStringContainsString($this->branch->name, $description);
        $this->assertStringContainsString('John Doe', $description);
    }    #[Test]
    public function reset_filters_clears_all_selections()
    {
        $widget = Livewire::test(GlobalFilterWidget::class);
        
        // Set multiple filters
        $widget->set('globalFilters.period', 'this_month')
            ->set('globalFilters.branch_id', $this->branch->id)
            ->set('globalFilters.staff_id', 1)
            ->set('globalFilters.service_id', 1);
        
        // Reset filters
        $widget->call('resetGlobalFilters');
        
        // All filters should be reset to defaults
        $widget->assertSet('globalFilters.period', 'today')
            ->assertSet('globalFilters.branch_id', null)
            ->assertSet('globalFilters.staff_id', null)
            ->assertSet('globalFilters.service_id', null);
        
        // Event should be dispatched
        $widget->assertDispatched('global-filter-updated');
    }    #[Test]
    public function filters_persist_across_page_loads()
    {
        // Set filters in session
        Session::put('dashboard.period', 'this_month');
        Session::put('dashboard.branch_id', $this->branch->id);
        
        // Load widget - should restore from session
        $widget = Livewire::test(GlobalFilterWidget::class);
        
        $widget->assertSet('globalFilters.period', 'this_month')
            ->assertSet('globalFilters.branch_id', $this->branch->id);
    }    #[Test]
    public function kpi_widgets_receive_filter_updates()
    {
        // Create appointment KPI widget
        $kpiWidget = Livewire::test(AppointmentKpiWidget::class);
        
        // Initial state
        $kpiWidget->assertSet('globalFilters.period', 'today');
        
        // Simulate filter update event
        $kpiWidget->dispatch('global-filter-updated', filters: [
            'period' => 'this_week',
            'branch_id' => $this->branch->id
        ]);
        
        // KPI widget should update its filters
        $kpiWidget->assertSet('globalFilters.period', 'this_week')
            ->assertSet('globalFilters.branch_id', $this->branch->id);
    }    #[Test]
    public function handles_missing_company_id_gracefully()
    {
        // Create user without company
        $userWithoutCompany = User::factory()->create(['company_id' => null]);
        $this->actingAs($userWithoutCompany);
        
        // Widget should still load but with no data
        $widget = Livewire::test(GlobalFilterWidget::class);
        
        $widget->assertSet('globalFilters.company_id', null);
        
        // Methods should return empty arrays
        $this->assertEquals([], $widget->call('getBranches'));
        $this->assertEquals([], $widget->call('getStaff'));
        $this->assertEquals([], $widget->call('getServices'));
    }
}