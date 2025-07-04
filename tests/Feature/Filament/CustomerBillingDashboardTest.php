<?php

namespace Tests\Feature\Filament;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\BillingPeriod;
use App\Models\Call;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Filament\Admin\Pages\CustomerBillingDashboard;
use Carbon\Carbon;

class CustomerBillingDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create([
            'stripe_customer_id' => 'cus_test123'
        ]);
        
        $this->user = User::factory()->create([
            'company_id' => $this->company->id
        ]);
        
        $this->actingAs($this->user);
    }

    public function test_can_access_billing_dashboard()
    {
        Livewire::test(CustomerBillingDashboard::class)
            ->assertSuccessful()
            ->assertSee('Current Billing Period')
            ->assertSee('Usage Trends')
            ->assertSee('Billing History');
    }

    public function test_displays_current_period_usage()
    {
        $period = BillingPeriod::factory()->create([
            'company_id' => $this->company->id,
            'start_date' => Carbon::now()->startOfMonth(),
            'end_date' => Carbon::now()->endOfMonth(),
            'status' => 'active',
            'included_minutes' => 500
        ]);
        
        // Create calls for current month
        Call::factory()->count(10)->create([
            'company_id' => $this->company->id,
            'created_at' => Carbon::now(),
            'duration_seconds' => 180, // 3 minutes each
            'status' => 'completed'
        ]);
        
        // Create appointments
        Appointment::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'created_at' => Carbon::now()
        ]);
        
        Livewire::test(CustomerBillingDashboard::class)
            ->assertSee('10') // Total calls
            ->assertSee('30.0') // Total minutes (10 * 3)
            ->assertSee('5') // Appointments
            ->assertSee('of 500 included'); // Included minutes
    }

    public function test_displays_billing_history()
    {
        // Create past billing periods
        $periods = [];
        for ($i = 3; $i >= 1; $i--) {
            $period = BillingPeriod::factory()->create([
                'company_id' => $this->company->id,
                'start_date' => Carbon::now()->subMonths($i)->startOfMonth(),
                'end_date' => Carbon::now()->subMonths($i)->endOfMonth(),
                'status' => 'invoiced',
                'total_minutes' => 100 * $i,
                'total_cost' => 50 * $i,
                'is_invoiced' => true
            ]);
            
            // Create associated invoice
            $invoice = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'total' => 50 * $i,
                'status' => 'paid'
            ]);
            
            $period->update(['invoice_id' => $invoice->id]);
            
            // Create appointments for count
            Appointment::factory()->count($i * 2)->create([
                'company_id' => $this->company->id,
                'created_at' => $period->start_date->addDays(5)
            ]);
        }
        
        Livewire::test(CustomerBillingDashboard::class)
            ->assertSee('€150.00') // Most expensive period
            ->assertSee('€100.00')
            ->assertSee('€50.00');
    }

    public function test_displays_usage_trends_data()
    {
        // Create data for last 6 months
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            
            Call::factory()->count(10 + $i)->create([
                'company_id' => $this->company->id,
                'created_at' => $date,
                'duration_seconds' => 120 // 2 minutes each
            ]);
            
            Appointment::factory()->count(5 + $i)->create([
                'company_id' => $this->company->id,
                'created_at' => $date
            ]);
        }
        
        $component = Livewire::test(CustomerBillingDashboard::class);
        
        // Check that trend data is populated
        $this->assertIsArray($component->get('usageTrends'));
        $this->assertCount(6, $component->get('usageTrends'));
        
        // Verify trend data structure
        $firstMonth = $component->get('usageTrends')[0];
        $this->assertArrayHasKey('month', $firstMonth);
        $this->assertArrayHasKey('calls', $firstMonth);
        $this->assertArrayHasKey('minutes', $firstMonth);
        $this->assertArrayHasKey('appointments', $firstMonth);
    }

    public function test_displays_upcoming_charges()
    {
        $subscription = Subscription::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'active',
            'amount' => 99.00,
            'current_period_end' => Carbon::now()->addDays(5)
        ]);
        
        $this->company->update([
            'subscription_status' => 'active'
        ]);
        
        Livewire::test(CustomerBillingDashboard::class)
            ->assertSee('Upcoming Charges')
            ->assertSee('Subscription Renewal')
            ->assertSee('€99.00')
            ->assertSee($subscription->current_period_end->format('Y-m-d'));
    }

    public function test_refresh_data_clears_cache()
    {
        // Create initial data
        Call::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'created_at' => Carbon::now(),
            'duration_seconds' => 60
        ]);
        
        $component = Livewire::test(CustomerBillingDashboard::class);
        
        // Create more calls after initial load
        Call::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'created_at' => Carbon::now(),
            'duration_seconds' => 60
        ]);
        
        // Refresh should update the data
        $component->call('refreshData')
            ->assertNotified('Dashboard data refreshed successfully.');
    }

    public function test_export_usage_data()
    {
        // Create some usage data
        for ($i = 2; $i >= 0; $i--) {
            Call::factory()->count(10)->create([
                'company_id' => $this->company->id,
                'created_at' => Carbon::now()->subMonths($i),
                'duration_seconds' => 120
            ]);
        }
        
        Livewire::test(CustomerBillingDashboard::class)
            ->call('exportUsageData')
            ->assertFileDownloaded('usage_data_' . now()->format('Y-m-d') . '.csv');
    }

    public function test_payment_methods_modal_without_stripe_customer()
    {
        $this->company->update(['stripe_customer_id' => null]);
        
        Livewire::test(CustomerBillingDashboard::class)
            ->call('openPaymentMethodsModal')
            ->assertNotified('No payment methods configured.');
    }

    public function test_displays_current_active_subscription()
    {
        $subscription = Subscription::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'active',
            'name' => 'Professional Plan',
            'amount' => 149.00
        ]);
        
        $component = Livewire::test(CustomerBillingDashboard::class);
        
        $this->assertNotNull($component->get('activeSubscription'));
        $this->assertEquals($subscription->id, $component->get('activeSubscription')->id);
    }

    public function test_calculates_projected_costs_correctly()
    {
        $period = BillingPeriod::factory()->create([
            'company_id' => $this->company->id,
            'start_date' => Carbon::now()->startOfMonth(),
            'end_date' => Carbon::now()->endOfMonth(),
            'status' => 'active',
            'included_minutes' => 1000,
            'price_per_minute' => 0.10,
            'base_fee' => 49.00
        ]);
        
        // Create calls totaling 60 minutes
        Call::factory()->count(20)->create([
            'company_id' => $this->company->id,
            'created_at' => Carbon::now(),
            'duration_seconds' => 180, // 3 minutes each
            'status' => 'completed'
        ]);
        
        $component = Livewire::test(CustomerBillingDashboard::class);
        
        $currentUsage = $component->get('currentUsage');
        
        // Should have base fee only (no overage yet)
        $this->assertEquals(49.00, $currentUsage['total_cost']);
    }
}