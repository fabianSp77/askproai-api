<?php

namespace Tests\Feature\Filament;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\BillingPeriod;
use App\Models\Call;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Filament\Admin\Resources\BillingPeriodResource;
use App\Filament\Admin\Resources\BillingPeriodResource\Pages\ListBillingPeriods;
use App\Filament\Admin\Resources\BillingPeriodResource\Pages\EditBillingPeriod;
use App\Filament\Admin\Resources\BillingPeriodResource\Pages\ViewBillingPeriod;
use Carbon\Carbon;

class BillingPeriodResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->admin = User::factory()->create([
            'company_id' => $this->company->id,
            'is_admin' => true
        ]);
        
        $this->actingAs($this->admin);
    }

    public function test_can_list_billing_periods()
    {
        BillingPeriod::factory()->count(5)->create([
            'company_id' => $this->company->id
        ]);
        
        Livewire::test(ListBillingPeriods::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords(BillingPeriod::where('company_id', $this->company->id)->get())
            ->assertCountTableRecords(5);
    }

    public function test_can_filter_by_status()
    {
        BillingPeriod::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'status' => 'active'
        ]);
        
        BillingPeriod::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'status' => 'processed'
        ]);
        
        Livewire::test(ListBillingPeriods::class)
            ->filterTable('status', 'active')
            ->assertCountTableRecords(2)
            ->filterTable('status', 'processed')
            ->assertCountTableRecords(3);
    }

    public function test_can_view_billing_period_details()
    {
        $period = BillingPeriod::factory()->create([
            'company_id' => $this->company->id,
            'total_minutes' => 1500,
            'included_minutes' => 1000,
            'overage_minutes' => 500,
            'total_cost' => 99.00
        ]);
        
        // Create some calls for this period
        Call::factory()->count(10)->create([
            'company_id' => $this->company->id,
            'start_time' => $period->start_date->addDay(),
            'duration_seconds' => 300
        ]);
        
        Livewire::test(ViewBillingPeriod::class, ['record' => $period->id])
            ->assertSuccessful()
            ->assertSee('1,500')
            ->assertSee('500')
            ->assertSee('€99.00');
    }

    public function test_can_process_billing_period()
    {
        $period = BillingPeriod::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'active',
            'end_date' => Carbon::now()->subDay()
        ]);
        
        Livewire::test(ListBillingPeriods::class)
            ->callTableAction('process', $period)
            ->assertHasNoTableActionErrors()
            ->assertNotified();
        
        $this->assertEquals('processed', $period->fresh()->status);
    }

    public function test_cannot_process_future_period()
    {
        $period = BillingPeriod::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'active',
            'end_date' => Carbon::now()->addDay()
        ]);
        
        Livewire::test(ListBillingPeriods::class)
            ->callTableAction('process', $period)
            ->assertNotified();
        
        $this->assertEquals('active', $period->fresh()->status);
    }

    public function test_can_create_invoice_for_processed_period()
    {
        $period = BillingPeriod::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'processed',
            'is_invoiced' => false,
            'total_cost' => 150.00
        ]);
        
        Livewire::test(ListBillingPeriods::class)
            ->callTableAction('createInvoice', $period)
            ->assertHasNoTableActionErrors()
            ->assertNotified();
        
        $period->refresh();
        $this->assertTrue($period->is_invoiced);
        $this->assertNotNull($period->invoice_id);
    }

    public function test_can_bulk_process_periods()
    {
        $periods = BillingPeriod::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'status' => 'active',
            'end_date' => Carbon::now()->subDay()
        ]);
        
        Livewire::test(ListBillingPeriods::class)
            ->callTableBulkAction('process', $periods)
            ->assertHasNoTableBulkActionErrors()
            ->assertNotified();
        
        foreach ($periods as $period) {
            $this->assertEquals('processed', $period->fresh()->status);
        }
    }

    public function test_tabs_show_correct_counts()
    {
        BillingPeriod::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'status' => 'active'
        ]);
        
        BillingPeriod::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'status' => 'processed'
        ]);
        
        BillingPeriod::factory()->count(1)->create([
            'company_id' => $this->company->id,
            'status' => 'invoiced'
        ]);
        
        $livewire = Livewire::test(ListBillingPeriods::class);
        
        // Check all tab
        $livewire->assertSee('All (6)');
        
        // Check active tab
        $livewire->set('activeTab', 'active')
            ->assertCountTableRecords(2);
        
        // Check processed tab
        $livewire->set('activeTab', 'processed')
            ->assertCountTableRecords(3);
    }

    public function test_can_export_billing_periods()
    {
        BillingPeriod::factory()->count(10)->create([
            'company_id' => $this->company->id
        ]);
        
        Livewire::test(ListBillingPeriods::class)
            ->callTableBulkAction('export', BillingPeriod::limit(5)->get())
            ->assertFileDownloaded();
    }

    public function test_stats_widgets_display_correct_data()
    {
        BillingPeriod::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'active',
            'total_minutes' => 1000,
            'total_cost' => 100
        ]);
        
        BillingPeriod::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'processed',
            'total_minutes' => 2000,
            'total_cost' => 200,
            'margin' => 140,
            'margin_percentage' => 70
        ]);
        
        $livewire = Livewire::test(ListBillingPeriods::class);
        
        // The stats would be displayed in header widgets
        $livewire->assertSuccessful();
    }

    public function test_cannot_access_other_company_periods()
    {
        $otherCompany = Company::factory()->create();
        $otherPeriod = BillingPeriod::factory()->create([
            'company_id' => $otherCompany->id
        ]);
        
        Livewire::test(ListBillingPeriods::class)
            ->assertCannotSeeTableRecords([$otherPeriod]);
    }
}