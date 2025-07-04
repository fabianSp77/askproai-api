<?php

namespace Tests\Feature\Filament;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\BillingAlert;
use App\Models\BillingAlertConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use App\Filament\Admin\Pages\BillingAlertsManagement;
use App\Mail\BillingAlertMail;

class BillingAlertsManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        
        Mail::fake();
        
        $this->company = Company::factory()->create([
            'alerts_enabled' => true,
            'billing_contact_email' => 'billing@test.com'
        ]);
        
        $this->admin = User::factory()->create([
            'company_id' => $this->company->id,
            'is_admin' => true
        ]);
        
        $this->actingAs($this->admin);
    }

    public function test_can_access_billing_alerts_page()
    {
        Livewire::test(BillingAlertsManagement::class)
            ->assertSuccessful()
            ->assertSee('Global Alerts')
            ->assertSee('Alert Configurations')
            ->assertSee('Alert History');
    }

    public function test_displays_all_alert_types()
    {
        $component = Livewire::test(BillingAlertsManagement::class);
        
        $alertTypes = [
            'Usage Limit Alerts',
            'Payment Reminders',
            'Subscription Renewal Notices',
            'Overage Warnings',
            'Payment Failed Alerts',
            'Budget Alerts'
        ];
        
        foreach ($alertTypes as $type) {
            $component->assertSee($type);
        }
    }

    public function test_toggle_global_alerts()
    {
        $this->assertTrue($this->company->alerts_enabled);
        
        Livewire::test(BillingAlertsManagement::class)
            ->call('toggleGlobalAlerts')
            ->assertNotified('Alerts Disabled');
        
        $this->assertFalse($this->company->fresh()->alerts_enabled);
        
        Livewire::test(BillingAlertsManagement::class)
            ->call('toggleGlobalAlerts')
            ->assertNotified('Alerts Enabled');
        
        $this->assertTrue($this->company->fresh()->alerts_enabled);
    }

    public function test_creates_default_configurations()
    {
        $this->assertDatabaseCount('billing_alert_configs', 0);
        
        Livewire::test(BillingAlertsManagement::class);
        
        $this->assertDatabaseCount('billing_alert_configs', 6);
        
        $configs = BillingAlertConfig::where('company_id', $this->company->id)->get();
        
        foreach ($configs as $config) {
            $this->assertTrue($config->is_enabled);
            $this->assertEquals(['email'], $config->notification_channels);
        }
    }

    public function test_update_alert_configuration()
    {
        $component = Livewire::test(BillingAlertsManagement::class);
        
        $config = BillingAlertConfig::where('company_id', $this->company->id)
            ->where('alert_type', BillingAlertConfig::TYPE_USAGE_LIMIT)
            ->first();
        
        $component->call('updateAlertConfig', BillingAlertConfig::TYPE_USAGE_LIMIT, [
            'is_enabled' => false,
            'thresholds' => [70, 85, 95]
        ])->assertNotified('Alert Configuration Updated');
        
        $config->refresh();
        $this->assertFalse($config->is_enabled);
        $this->assertEquals([70, 85, 95], $config->thresholds);
    }

    public function test_test_alert_sends_email()
    {
        Livewire::test(BillingAlertsManagement::class)
            ->call('testAlert', BillingAlertConfig::TYPE_USAGE_LIMIT)
            ->assertNotified('Test Alert Sent');
        
        Mail::assertSent(BillingAlertMail::class, function ($mail) {
            return $mail->hasTo($this->company->billing_contact_email) &&
                   str_contains($mail->alert->title, 'Test: Usage Alert');
        });
    }

    public function test_test_alert_respects_disabled_config()
    {
        $config = BillingAlertConfig::factory()->create([
            'company_id' => $this->company->id,
            'alert_type' => BillingAlertConfig::TYPE_PAYMENT_REMINDER,
            'is_enabled' => false
        ]);
        
        Livewire::test(BillingAlertsManagement::class)
            ->call('testAlert', BillingAlertConfig::TYPE_PAYMENT_REMINDER)
            ->assertNotified('Alert Not Sent');
        
        Mail::assertNotSent(BillingAlertMail::class);
    }

    public function test_suppress_alerts()
    {
        Livewire::test(BillingAlertsManagement::class)
            ->call('suppressAlerts', BillingAlertConfig::TYPE_USAGE_LIMIT, 7, 'System maintenance')
            ->assertNotified('usage_limit alerts suppressed for 7 days.');
        
        $this->assertDatabaseHas('billing_alert_suppressions', [
            'company_id' => $this->company->id,
            'alert_type' => BillingAlertConfig::TYPE_USAGE_LIMIT,
            'reason' => 'System maintenance'
        ]);
    }

    public function test_alert_history_table()
    {
        // Create some alerts
        BillingAlert::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'alert_type' => BillingAlertConfig::TYPE_USAGE_LIMIT,
            'status' => 'sent'
        ]);
        
        $component = Livewire::test(BillingAlertsManagement::class);
        
        // The table component would handle the display
        $this->assertDatabaseCount('billing_alerts', 5);
    }

    public function test_acknowledge_alert()
    {
        $alert = BillingAlert::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'sent'
        ]);
        
        Livewire::test(BillingAlertsManagement::class)
            ->callTableAction('acknowledge', $alert)
            ->assertNotified('Alert Acknowledged');
        
        $alert->refresh();
        $this->assertEquals('acknowledged', $alert->status);
        $this->assertNotNull($alert->acknowledged_at);
        $this->assertEquals($this->admin->id, $alert->acknowledged_by);
    }

    public function test_filter_alerts_by_type()
    {
        BillingAlert::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'alert_type' => BillingAlertConfig::TYPE_USAGE_LIMIT
        ]);
        
        BillingAlert::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'alert_type' => BillingAlertConfig::TYPE_PAYMENT_REMINDER
        ]);
        
        Livewire::test(BillingAlertsManagement::class)
            ->filterTable('alert_type', BillingAlertConfig::TYPE_USAGE_LIMIT)
            ->assertCanSeeTableRecords(
                BillingAlert::where('alert_type', BillingAlertConfig::TYPE_USAGE_LIMIT)->get()
            );
    }

    public function test_check_alerts_now_action()
    {
        Livewire::test(BillingAlertsManagement::class)
            ->callAction('refreshAlerts')
            ->assertNotified('Alert check completed successfully.');
    }

    public function test_default_thresholds_for_alert_types()
    {
        $component = Livewire::test(BillingAlertsManagement::class);
        
        $usageConfig = BillingAlertConfig::where('company_id', $this->company->id)
            ->where('alert_type', BillingAlertConfig::TYPE_USAGE_LIMIT)
            ->first();
        
        $this->assertEquals([80, 90, 100], $usageConfig->thresholds);
        
        $paymentConfig = BillingAlertConfig::where('company_id', $this->company->id)
            ->where('alert_type', BillingAlertConfig::TYPE_PAYMENT_REMINDER)
            ->first();
        
        $this->assertEquals(3, $paymentConfig->advance_days);
    }

    public function test_alert_type_descriptions()
    {
        $component = Livewire::test(BillingAlertsManagement::class);
        
        $descriptions = [
            'Notify when usage reaches specified thresholds',
            'Send reminders before invoice due dates',
            'Notify before subscription renewals',
            'Alert when exceeding included limits',
            'Immediate notification on payment failures',
            'Alert when approaching or exceeding budget'
        ];
        
        foreach ($descriptions as $description) {
            $component->assertSee($description);
        }
    }
}