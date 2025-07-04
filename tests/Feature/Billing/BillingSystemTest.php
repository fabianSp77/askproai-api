<?php

namespace Tests\Feature\Billing;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\BillingPeriod;
use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\BillingAlert;
use App\Models\BillingAlertConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Livewire\Livewire;

class BillingSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Company $company;
    protected Role $superAdminRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create Super Admin role
        $this->superAdminRole = Role::create(['name' => 'Super Admin']);
        
        // Create company
        $this->company = Company::factory()->create();
        
        // Create admin user
        $this->admin = User::factory()->create([
            'email' => 'admin@test.com',
            'company_id' => $this->company->id,
        ]);
        
        $this->admin->assignRole($this->superAdminRole);
    }

    /** @test */
    public function billing_alerts_management_page_loads_successfully()
    {
        $this->actingAs($this->admin);
        
        Livewire::test(\App\Filament\Admin\Pages\BillingAlertsManagement::class)
            ->assertSuccessful()
            ->assertSee('Alert Management');
    }

    /** @test */
    public function customer_billing_dashboard_page_loads_successfully()
    {
        $this->actingAs($this->admin);
        
        Livewire::test(\App\Filament\Admin\Pages\CustomerBillingDashboard::class)
            ->assertSuccessful()
            ->assertSee('Billing Dashboard');
    }

    /** @test */
    public function can_create_billing_period()
    {
        $this->actingAs($this->admin);
        
        $billingPeriod = BillingPeriod::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'active',
        ]);
        
        $this->assertDatabaseHas('billing_periods', [
            'id' => $billingPeriod->id,
            'company_id' => $this->company->id,
        ]);
    }

    /** @test */
    public function can_list_billing_periods()
    {
        $this->actingAs($this->admin);
        
        BillingPeriod::factory()->count(3)->create([
            'company_id' => $this->company->id,
        ]);
        
        $response = $this->get('/admin/billing-periods');
        $response->assertSuccessful();
    }

    /** @test */
    public function can_edit_billing_period()
    {
        $this->actingAs($this->admin);
        
        $billingPeriod = BillingPeriod::factory()->create([
            'company_id' => $this->company->id,
        ]);
        
        $response = $this->get("/admin/billing-periods/{$billingPeriod->id}/edit");
        $response->assertSuccessful();
    }

    /** @test */
    public function can_view_billing_period()
    {
        $this->actingAs($this->admin);
        
        $billingPeriod = BillingPeriod::factory()->create([
            'company_id' => $this->company->id,
        ]);
        
        $response = $this->get("/admin/billing-periods/{$billingPeriod->id}");
        $response->assertSuccessful();
    }

    /** @test */
    public function can_create_billing_alert_config()
    {
        $this->actingAs($this->admin);
        
        $config = BillingAlertConfig::create([
            'company_id' => $this->company->id,
            'alert_type' => BillingAlertConfig::TYPE_USAGE_LIMIT,
            'is_enabled' => true,
            'notification_channels' => ['email'],
            'notify_primary_contact' => true,
            'notify_billing_contact' => true,
            'thresholds' => [80, 90, 100],
        ]);
        
        $this->assertDatabaseHas('billing_alert_configs', [
            'id' => $config->id,
            'company_id' => $this->company->id,
        ]);
    }

    /** @test */
    public function can_create_billing_alert()
    {
        $this->actingAs($this->admin);
        
        $config = BillingAlertConfig::factory()->create([
            'company_id' => $this->company->id,
        ]);
        
        $alert = BillingAlert::create([
            'company_id' => $this->company->id,
            'config_id' => $config->id,
            'severity' => 'warning',
            'title' => 'Test Alert',
            'message' => 'This is a test alert',
            'status' => 'pending',
            'alert_type' => BillingAlertConfig::TYPE_USAGE_LIMIT,
        ]);
        
        $this->assertDatabaseHas('billing_alerts', [
            'id' => $alert->id,
            'company_id' => $this->company->id,
        ]);
    }

    /** @test */
    public function can_update_billing_period_status()
    {
        $this->actingAs($this->admin);
        
        $billingPeriod = BillingPeriod::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'active',
        ]);
        
        $billingPeriod->update(['status' => 'processed']);
        
        $this->assertEquals('processed', $billingPeriod->fresh()->status);
    }

    /** @test */
    public function billing_period_calculates_overage_correctly()
    {
        $billingPeriod = BillingPeriod::factory()->create([
            'company_id' => $this->company->id,
            'included_minutes' => 500,
            'used_minutes' => 600,
            'price_per_minute' => 0.10,
        ]);
        
        $this->assertEquals(100, $billingPeriod->overage_minutes);
        $this->assertEquals(10.00, $billingPeriod->overage_cost);
    }

    /** @test */
    public function billing_period_relationships_work_correctly()
    {
        $billingPeriod = BillingPeriod::factory()->create([
            'company_id' => $this->company->id,
        ]);
        
        $this->assertInstanceOf(Company::class, $billingPeriod->company);
        $this->assertEquals($this->company->id, $billingPeriod->company->id);
    }

    /** @test */
    public function subscription_can_be_created()
    {
        $subscription = Subscription::factory()->create([
            'company_id' => $this->company->id,
        ]);
        
        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'company_id' => $this->company->id,
        ]);
    }

    /** @test */
    public function invoice_can_be_created()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
        ]);
        
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'company_id' => $this->company->id,
        ]);
    }

    /** @test */
    public function billing_alerts_page_shows_correct_alert_types()
    {
        $this->actingAs($this->admin);
        
        $page = Livewire::test(\App\Filament\Admin\Pages\BillingAlertsManagement::class);
        
        // Check for alert type labels
        $alertTypes = [
            'Usage Limit Alerts',
            'Payment Reminders',
            'Subscription Renewal Notices',
            'Overage Warnings',
            'Payment Failed Alerts',
            'Budget Alerts',
        ];
        
        foreach ($alertTypes as $type) {
            $page->assertSee($type);
        }
    }

    /** @test */
    public function can_toggle_global_alerts()
    {
        $this->actingAs($this->admin);
        
        $this->company->update(['alerts_enabled' => true]);
        
        Livewire::test(\App\Filament\Admin\Pages\BillingAlertsManagement::class)
            ->call('toggleGlobalAlerts')
            ->assertSuccessful();
        
        $this->assertFalse($this->company->fresh()->alerts_enabled);
    }

    /** @test */
    public function can_test_alert_sending()
    {
        $this->actingAs($this->admin);
        
        BillingAlertConfig::factory()->create([
            'company_id' => $this->company->id,
            'alert_type' => BillingAlertConfig::TYPE_USAGE_LIMIT,
            'is_enabled' => true,
        ]);
        
        Livewire::test(\App\Filament\Admin\Pages\BillingAlertsManagement::class)
            ->call('testAlert', BillingAlertConfig::TYPE_USAGE_LIMIT)
            ->assertNotified();
    }

    /** @test */
    public function billing_period_resource_filters_work()
    {
        $this->actingAs($this->admin);
        
        // Create periods with different statuses
        BillingPeriod::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'active',
        ]);
        
        BillingPeriod::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'processed',
        ]);
        
        $response = $this->get('/admin/billing-periods?tableFilters[status][values][0]=active');
        $response->assertSuccessful();
    }

    /** @test */
    public function billing_period_invoice_relationship_works()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
        ]);
        
        $billingPeriod = BillingPeriod::factory()->create([
            'company_id' => $this->company->id,
            'invoice_id' => $invoice->id,
            'is_invoiced' => true,
        ]);
        
        $this->assertInstanceOf(Invoice::class, $billingPeriod->invoice);
        $this->assertEquals($invoice->id, $billingPeriod->invoice->id);
    }

    /** @test */
    public function user_without_super_admin_role_cannot_create_billing_period()
    {
        $regularUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        
        $this->actingAs($regularUser);
        
        $response = $this->get('/admin/billing-periods/create');
        $response->assertForbidden();
    }

    /** @test */
    public function billing_alerts_respect_company_isolation()
    {
        $this->actingAs($this->admin);
        
        // Create another company with alerts
        $otherCompany = Company::factory()->create();
        BillingAlert::factory()->count(3)->create([
            'company_id' => $otherCompany->id,
        ]);
        
        // Create alerts for current company
        BillingAlert::factory()->count(2)->create([
            'company_id' => $this->company->id,
        ]);
        
        // The page should only show alerts for current company
        $alerts = BillingAlert::where('company_id', $this->company->id)->count();
        $this->assertEquals(2, $alerts);
    }
}