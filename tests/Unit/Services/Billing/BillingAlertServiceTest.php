<?php

namespace Tests\Unit\Services\Billing;

use Tests\TestCase;
use App\Services\Billing\BillingAlertService;
use App\Services\NotificationService;
use App\Models\BillingAlert;
use App\Models\BillingAlertConfig;
use App\Models\Company;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use App\Mail\BillingAlertMail;
use Mockery;

class BillingAlertServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BillingAlertService $service;
    protected $mockNotificationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        Mail::fake();
        
        $this->mockNotificationService = Mockery::mock(NotificationService::class);
        $this->service = new BillingAlertService($this->mockNotificationService);
    }

    public function test_check_usage_limit_alerts_creates_alert_at_threshold()
    {
        $company = Company::factory()->create([
            'alerts_enabled' => true,
            'billing_contact_email' => 'billing@company.com'
        ]);
        
        $config = BillingAlertConfig::factory()->create([
            'company_id' => $company->id,
            'alert_type' => BillingAlertConfig::TYPE_USAGE_LIMIT,
            'is_enabled' => true,
            'thresholds' => [80, 90, 100],
            'notification_channels' => ['email']
        ]);
        
        $alerts = $this->service->checkUsageLimitAlerts($company, 450, 500);
        
        $this->assertCount(1, $alerts);
        $this->assertEquals(90, $alerts[0]->threshold_value);
        $this->assertEquals(450, $alerts[0]->current_value);
        $this->assertEquals('warning', $alerts[0]->severity);
        
        Mail::assertSent(BillingAlertMail::class);
    }

    public function test_check_usage_limit_alerts_respects_suppression()
    {
        $company = Company::factory()->create(['alerts_enabled' => true]);
        
        $config = BillingAlertConfig::factory()->create([
            'company_id' => $company->id,
            'alert_type' => BillingAlertConfig::TYPE_USAGE_LIMIT,
            'is_enabled' => true,
            'thresholds' => [80, 90, 100]
        ]);
        
        // Create suppression
        \DB::table('billing_alert_suppressions')->insert([
            'company_id' => $company->id,
            'alert_type' => BillingAlertConfig::TYPE_USAGE_LIMIT,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        $alerts = $this->service->checkUsageLimitAlerts($company, 450, 500);
        
        $this->assertCount(0, $alerts);
        Mail::assertNotSent(BillingAlertMail::class);
    }

    public function test_check_payment_reminders_creates_alert_for_due_invoices()
    {
        $company = Company::factory()->create(['alerts_enabled' => true]);
        
        $config = BillingAlertConfig::factory()->create([
            'company_id' => $company->id,
            'alert_type' => BillingAlertConfig::TYPE_PAYMENT_REMINDER,
            'is_enabled' => true,
            'advance_days' => 3
        ]);
        
        // Create invoice due in 3 days
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'due_date' => now()->addDays(3),
            'status' => 'pending',
            'total' => 99.00
        ]);
        
        $alerts = $this->service->checkPaymentReminders($company);
        
        $this->assertCount(1, $alerts);
        $this->assertEquals(BillingAlertConfig::TYPE_PAYMENT_REMINDER, $alerts[0]->alert_type);
        $this->assertStringContainsString('99.00', $alerts[0]->message);
    }

    public function test_check_budget_exceeded_creates_progressive_alerts()
    {
        $company = Company::factory()->create([
            'alerts_enabled' => true,
            'usage_budget' => 500.00
        ]);
        
        $config = BillingAlertConfig::factory()->create([
            'company_id' => $company->id,
            'alert_type' => BillingAlertConfig::TYPE_BUDGET_EXCEEDED,
            'is_enabled' => true,
            'thresholds' => [75, 90, 100]
        ]);
        
        // First check at 76% - should create alert
        $alerts = $this->service->checkBudgetExceeded($company, 380.00);
        $this->assertCount(1, $alerts);
        $this->assertEquals(75, $alerts[0]->threshold_value);
        
        // Second check at 76% - should not create duplicate
        $alerts = $this->service->checkBudgetExceeded($company, 380.00);
        $this->assertCount(0, $alerts);
        
        // Third check at 91% - should create new alert
        $alerts = $this->service->checkBudgetExceeded($company, 455.00);
        $this->assertCount(1, $alerts);
        $this->assertEquals(90, $alerts[0]->threshold_value);
    }

    public function test_create_immediate_alert_sends_notification()
    {
        $company = Company::factory()->create([
            'alerts_enabled' => true,
            'billing_contact_email' => 'billing@test.com'
        ]);
        
        $alert = $this->service->createImmediateAlert(
            $company,
            BillingAlertConfig::TYPE_PAYMENT_FAILED,
            [
                'severity' => 'critical',
                'title' => 'Payment Failed',
                'message' => 'Your payment of $99.00 has failed.',
                'data' => ['invoice_id' => 'inv_123']
            ]
        );
        
        $this->assertInstanceOf(BillingAlert::class, $alert);
        $this->assertEquals('critical', $alert->severity);
        $this->assertEquals('sent', $alert->status);
        $this->assertNotNull($alert->sent_at);
        
        Mail::assertSent(BillingAlertMail::class);
    }

    public function test_create_immediate_alert_respects_disabled_alerts()
    {
        $company = Company::factory()->create(['alerts_enabled' => false]);
        
        $alert = $this->service->createImmediateAlert(
            $company,
            BillingAlertConfig::TYPE_PAYMENT_FAILED,
            ['title' => 'Test', 'message' => 'Test']
        );
        
        $this->assertNull($alert);
        Mail::assertNotSent(BillingAlertMail::class);
    }

    public function test_check_company_alerts_runs_all_checks()
    {
        $company = Company::factory()->create([
            'alerts_enabled' => true,
            'usage_budget' => 1000
        ]);
        
        // Create all alert configs
        $alertTypes = [
            BillingAlertConfig::TYPE_USAGE_LIMIT,
            BillingAlertConfig::TYPE_PAYMENT_REMINDER,
            BillingAlertConfig::TYPE_SUBSCRIPTION_RENEWAL,
            BillingAlertConfig::TYPE_BUDGET_EXCEEDED
        ];
        
        foreach ($alertTypes as $type) {
            BillingAlertConfig::factory()->create([
                'company_id' => $company->id,
                'alert_type' => $type,
                'is_enabled' => true
            ]);
        }
        
        $this->service->checkCompanyAlerts($company);
        
        // Verify that alert checks were performed
        $this->assertDatabaseHas('billing_alerts', [
            'company_id' => $company->id
        ]);
    }

    public function test_get_alert_history()
    {
        $company = Company::factory()->create();
        
        // Create alerts with different statuses
        BillingAlert::factory()->create([
            'company_id' => $company->id,
            'status' => 'sent',
            'created_at' => now()->subDays(5)
        ]);
        
        BillingAlert::factory()->create([
            'company_id' => $company->id,
            'status' => 'acknowledged',
            'created_at' => now()->subDays(3)
        ]);
        
        BillingAlert::factory()->create([
            'company_id' => $company->id,
            'status' => 'pending',
            'created_at' => now()->subDay()
        ]);
        
        $history = $this->service->getAlertHistory($company, 7);
        
        $this->assertCount(3, $history);
        $this->assertEquals('pending', $history[0]->status); // Most recent first
    }

    public function test_acknowledge_alert()
    {
        $user = \App\Models\User::factory()->create();
        $alert = BillingAlert::factory()->create(['status' => 'sent']);
        
        $this->service->acknowledgeAlert($alert, $user);
        
        $alert->refresh();
        $this->assertEquals('acknowledged', $alert->status);
        $this->assertNotNull($alert->acknowledged_at);
        $this->assertEquals($user->id, $alert->acknowledged_by);
    }

    public function test_is_alert_suppressed()
    {
        $company = Company::factory()->create();
        
        // No suppression - should return false
        $this->assertFalse($this->service->isAlertSuppressed(
            $company,
            BillingAlertConfig::TYPE_USAGE_LIMIT
        ));
        
        // Add suppression
        \DB::table('billing_alert_suppressions')->insert([
            'company_id' => $company->id,
            'alert_type' => BillingAlertConfig::TYPE_USAGE_LIMIT,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // With active suppression - should return true
        $this->assertTrue($this->service->isAlertSuppressed(
            $company,
            BillingAlertConfig::TYPE_USAGE_LIMIT
        ));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}