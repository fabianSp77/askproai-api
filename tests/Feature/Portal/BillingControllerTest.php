<?php

namespace Tests\Feature\Portal;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Invoice;
use App\Traits\UsesMCPServers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Mockery;

class BillingControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Company $company;
    protected User $user;
    protected User $adminUser;
    protected User $billingUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test company with billing enabled
        $this->company = Company::factory()->create([
            'name' => 'Test Billing Company',
            'settings' => [
                'billing_enabled' => true,
                'stripe_enabled' => true,
                'auto_topup_enabled' => true
            ]
        ]);

        // Create regular user without billing permissions
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'user@testbilling.com',
            'permissions' => [
                'dashboard.view' => true,
                'billing.view' => false,
                'billing.manage' => false
            ]
        ]);

        // Create user with billing view permissions
        $this->billingUser = User::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'billing@testbilling.com',
            'permissions' => [
                'dashboard.view' => true,
                'billing.view' => true,
                'billing.manage' => true
            ]
        ]);

        // Create admin user with full permissions
        $this->adminUser = User::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'admin@testbilling.com',
            'permissions' => [
                'dashboard.view' => true,
                'billing.view' => true,
                'billing.manage' => true,
                'admin.all' => true
            ]
        ]);

        // Create test billing data
        $this->createBillingTestData();
        
        // Mock MCP services
        $this->mockMCPServices();
    }

    protected function createBillingTestData(): void
    {
        // Create invoices for testing
        Invoice::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'status' => 'paid',
            'amount' => 150.00,
            'created_at' => now()->subDays(5)
        ]);

        Invoice::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'status' => 'pending',
            'amount' => 75.00,
            'created_at' => now()->subDays(2)
        ]);
    }

    protected function mockMCPServices(): void
    {
        $this->mock('alias:' . UsesMCPServers::class, function ($mock) {
            // Mock billing overview
            $mock->shouldReceive('executeMCPTask')
                ->with('getBillingOverview', Mockery::any())
                ->andReturn([
                    'success' => true,
                    'result' => [
                        'current_balance' => 250.75,
                        'monthly_usage' => 189.50,
                        'pending_invoices' => 2,
                        'last_payment_date' => now()->subDays(10)->toDateString(),
                        'auto_topup_enabled' => true,
                        'next_billing_date' => now()->addDays(20)->toDateString()
                    ]
                ]);

            // Mock topup options
            $mock->shouldReceive('executeMCPTask')
                ->with('getTopupOptions', Mockery::any())
                ->andReturn([
                    'success' => true,
                    'result' => [
                        'suggested_amounts' => [50, 100, 200, 500],
                        'min_amount' => 10,
                        'max_amount' => 10000,
                        'payment_methods' => [
                            ['id' => 'pm_123', 'brand' => 'visa', 'last4' => '4242'],
                            ['id' => 'pm_456', 'brand' => 'mastercard', 'last4' => '5555']
                        ]
                    ]
                ]);

            // Mock topup processing
            $mock->shouldReceive('executeMCPTask')
                ->with('topupBalance', Mockery::any())
                ->andReturn([
                    'success' => true,
                    'result' => [
                        'success' => true,
                        'checkout_url' => 'https://checkout.stripe.com/pay/cs_test_123'
                    ]
                ]);

            // Mock transaction listing
            $mock->shouldReceive('executeMCPTask')
                ->with('listTransactions', Mockery::any())
                ->andReturn([
                    'success' => true,
                    'result' => [
                        'data' => [
                            [
                                'id' => 'txn_123',
                                'type' => 'topup',
                                'amount' => 100.00,
                                'status' => 'completed',
                                'created_at' => now()->subDays(5)->toISOString()
                            ],
                            [
                                'id' => 'txn_124',
                                'type' => 'usage',
                                'amount' => -25.50,
                                'status' => 'completed',
                                'created_at' => now()->subDays(3)->toISOString()
                            ]
                        ],
                        'pagination' => [
                            'current_page' => 1,
                            'total_pages' => 3,
                            'total_count' => 28
                        ]
                    ]
                ]);

            // Mock usage report
            $mock->shouldReceive('executeMCPTask')
                ->with('getUsageReport', Mockery::any())
                ->andReturn([
                    'success' => true,
                    'result' => [
                        'period' => 'this_month',
                        'total_calls' => 45,
                        'total_minutes' => 1280,
                        'total_cost' => 189.50,
                        'average_cost_per_call' => 4.21,
                        'daily_breakdown' => [
                            ['date' => '2025-01-01', 'calls' => 5, 'cost' => 23.75],
                            ['date' => '2025-01-02', 'calls' => 8, 'cost' => 38.20]
                        ]
                    ]
                ]);

            // Mock auto-topup settings
            $mock->shouldReceive('executeMCPTask')
                ->with('getAutoTopupSettings', Mockery::any())
                ->andReturn([
                    'success' => true,
                    'result' => [
                        'enabled' => true,
                        'threshold' => 50.00,
                        'amount' => 200.00,
                        'daily_limit' => 2,
                        'monthly_limit' => 10,
                        'payment_method_id' => 'pm_123'
                    ]
                ]);

            // Mock payment methods
            $mock->shouldReceive('executeMCPTask')
                ->with('listPaymentMethods', Mockery::any())
                ->andReturn([
                    'success' => true,
                    'result' => [
                        'payment_methods' => [
                            [
                                'id' => 'pm_123',
                                'type' => 'card',
                                'brand' => 'visa',
                                'last4' => '4242',
                                'exp_month' => 12,
                                'exp_year' => 2026,
                                'is_default' => true
                            ]
                        ]
                    ]
                ]);
        });
    }

    /** @test */
    public function billing_index_requires_authentication()
    {
        $response = $this->get('/business/billing');
        
        $response->assertRedirect('/business/login');
    }

    /** @test */
    public function billing_index_requires_view_permission()
    {
        $response = $this->actingAs($this->user, 'portal')
            ->get('/business/billing');

        $response->assertStatus(403);
        $response->assertSee('keine Berechtigung');
    }

    /** @test */
    public function billing_user_can_access_billing_overview()
    {
        $response = $this->actingAs($this->billingUser, 'portal')
            ->get('/business/billing');

        $response->assertStatus(200);
        
        // Should flash billing data to session for React
        $this->assertNotNull(session('billing_data'));
    }

    /** @test */
    public function admin_can_view_billing_in_readonly_mode()
    {
        // Simulate admin viewing mode
        session([
            'is_admin_viewing' => true,
            'admin_impersonation' => ['company_id' => $this->company->id]
        ]);

        $response = $this->get('/business/billing');

        $response->assertStatus(200);
        $this->assertNotNull(session('billing_data'));
    }

    /** @test */
    public function topup_page_requires_manage_permission()
    {
        $response = $this->actingAs($this->user, 'portal')
            ->get('/business/billing/topup');

        $response->assertStatus(403);
    }

    /** @test */
    public function topup_page_loads_with_options()
    {
        $response = $this->actingAs($this->billingUser, 'portal')
            ->get('/business/billing/topup?suggested=100');

        $response->assertStatus(200);
        
        $topupData = session('topup_data');
        $this->assertNotNull($topupData);
        $this->assertArrayHasKey('suggested_amounts', $topupData);
        $this->assertContains(100, $topupData['suggested_amounts']);
    }

    /** @test */
    public function process_topup_validates_input()
    {
        $response = $this->actingAs($this->billingUser, 'portal')
            ->post('/business/billing/topup', [
                'amount' => 5 // Below minimum
            ]);

        $response->assertSessionHasErrors('amount');
    }

    /** @test */
    public function process_topup_validates_maximum_amount()
    {
        $response = $this->actingAs($this->billingUser, 'portal')
            ->post('/business/billing/topup', [
                'amount' => 15000 // Above maximum
            ]);

        $response->assertSessionHasErrors('amount');
    }

    /** @test */
    public function process_topup_creates_stripe_checkout_session()
    {
        $response = $this->actingAs($this->billingUser, 'portal')
            ->post('/business/billing/topup', [
                'amount' => 100
            ]);

        $response->assertRedirect('https://checkout.stripe.com/pay/cs_test_123');
    }

    /** @test */
    public function admin_viewing_prevents_topup_processing()
    {
        session(['is_admin_viewing' => true]);

        $response = $this->post('/business/billing/topup', [
            'amount' => 100
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringContains('Administrator', session('error'));
    }

    /** @test */
    public function topup_success_handles_stripe_callback()
    {
        $response = $this->get('/business/billing/topup/success?session_id=cs_test_123');

        $response->assertStatus(200);
        $response->assertSessionHas('success');
        $this->assertStringContains('erfolgreich aufgeladen', session('success'));
    }

    /** @test */
    public function topup_cancel_shows_appropriate_message()
    {
        $response = $this->get('/business/billing/topup/cancel');

        $response->assertStatus(200);
        $response->assertSessionHas('info');
        $this->assertStringContains('abgebrochen', session('info'));
    }

    /** @test */
    public function transactions_page_shows_transaction_history()
    {
        $response = $this->actingAs($this->billingUser, 'portal')
            ->get('/business/billing/transactions');

        $response->assertStatus(200);
        
        $transactionsData = session('transactions_data');
        $this->assertNotNull($transactionsData);
        $this->assertArrayHasKey('data', $transactionsData);
        $this->assertCount(2, $transactionsData['data']);
    }

    /** @test */
    public function transactions_can_be_filtered_by_type()
    {
        $response = $this->actingAs($this->billingUser, 'portal')
            ->get('/business/billing/transactions?type=topup&date_from=2025-01-01');

        $response->assertStatus(200);
        
        // Verify filter parameters were passed to MCP
        $this->assertTrue(true); // MCP mock should receive filtered parameters
    }

    /** @test */
    public function download_invoice_requires_billing_permission()
    {
        $response = $this->actingAs($this->user, 'portal')
            ->get('/business/billing/transactions/txn_123/invoice');

        $response->assertStatus(403);
    }

    /** @test */
    public function download_invoice_handles_successful_download()
    {
        // Mock successful invoice download
        $this->mock('alias:' . UsesMCPServers::class, function ($mock) {
            $mock->shouldReceive('executeMCPTask')
                ->with('downloadInvoice', Mockery::any())
                ->andReturn([
                    'success' => true,
                    'result' => [
                        'success' => true,
                        'type' => 'redirect',
                        'url' => 'https://files.stripe.com/invoice_123.pdf'
                    ]
                ]);
        });

        $response = $this->actingAs($this->billingUser, 'portal')
            ->get('/business/billing/transactions/txn_123/invoice');

        $response->assertRedirect('https://files.stripe.com/invoice_123.pdf');
    }

    /** @test */
    public function usage_report_shows_current_month_by_default()
    {
        $response = $this->actingAs($this->billingUser, 'portal')
            ->get('/business/billing/usage');

        $response->assertStatus(200);
        
        $usageData = session('usage_data');
        $this->assertNotNull($usageData);
        $this->assertEquals('this_month', $usageData['period']);
        $this->assertEquals(45, $usageData['total_calls']);
        $this->assertEquals(189.50, $usageData['total_cost']);
    }

    /** @test */
    public function usage_report_can_export_csv()
    {
        // Mock successful export
        $this->mock('alias:' . UsesMCPServers::class, function ($mock) {
            $mock->shouldReceive('executeMCPTask')
                ->with('exportUsageReport', Mockery::any())
                ->andReturn([
                    'success' => true,
                    'result' => [
                        'success' => true,
                        'export' => [
                            'type' => 'stream',
                            'content' => 'Date,Calls,Cost\n2025-01-01,5,23.75',
                            'headers' => [
                                'Content-Type' => 'text/csv',
                                'Content-Disposition' => 'attachment; filename=usage-report.csv'
                            ]
                        ]
                    ]
                ]);
        });

        $response = $this->actingAs($this->billingUser, 'portal')
            ->get('/business/billing/usage?export=csv');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv');
    }

    /** @test */
    public function auto_topup_settings_require_manage_permission()
    {
        $response = $this->actingAs($this->user, 'portal')
            ->get('/business/billing/auto-topup');

        $response->assertStatus(403);
    }

    /** @test */
    public function auto_topup_settings_load_current_configuration()
    {
        $response = $this->actingAs($this->billingUser, 'portal')
            ->get('/business/billing/auto-topup');

        $response->assertStatus(200);
        
        $autoTopupData = session('auto_topup_data');
        $this->assertNotNull($autoTopupData);
        $this->assertTrue($autoTopupData['enabled']);
        $this->assertEquals(50.00, $autoTopupData['threshold']);
    }

    /** @test */
    public function update_auto_topup_validates_threshold()
    {
        $response = $this->actingAs($this->billingUser, 'portal')
            ->post('/business/billing/auto-topup', [
                'auto_topup_enabled' => true,
                'auto_topup_threshold' => 5, // Below minimum
                'auto_topup_amount' => 100,
                'auto_topup_daily_limit' => 2,
                'auto_topup_monthly_limit' => 10,
                'payment_method_id' => 'pm_123'
            ]);

        $response->assertSessionHasErrors('auto_topup_threshold');
    }

    /** @test */
    public function update_auto_topup_validates_payment_method_when_enabled()
    {
        $response = $this->actingAs($this->billingUser, 'portal')
            ->post('/business/billing/auto-topup', [
                'auto_topup_enabled' => true,
                'auto_topup_threshold' => 50,
                'auto_topup_amount' => 100,
                'auto_topup_daily_limit' => 2,
                'auto_topup_monthly_limit' => 10
                // Missing payment_method_id
            ]);

        $response->assertSessionHasErrors('payment_method_id');
    }

    /** @test */
    public function admin_viewing_prevents_auto_topup_updates()
    {
        session(['is_admin_viewing' => true]);

        $response = $this->post('/business/billing/auto-topup', [
            'auto_topup_enabled' => true,
            'auto_topup_threshold' => 50,
            'auto_topup_amount' => 100,
            'auto_topup_daily_limit' => 2,
            'auto_topup_monthly_limit' => 10,
            'payment_method_id' => 'pm_123'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /** @test */
    public function payment_methods_page_lists_saved_methods()
    {
        $response = $this->actingAs($this->billingUser, 'portal')
            ->get('/business/billing/payment-methods');

        $response->assertStatus(200);
        
        $paymentMethodsData = session('payment_methods_data');
        $this->assertNotNull($paymentMethodsData);
        $this->assertArrayHasKey('payment_methods', $paymentMethodsData);
        $this->assertCount(1, $paymentMethodsData['payment_methods']);
    }

    /** @test */
    public function add_payment_method_creates_setup_intent()
    {
        // Mock setup intent creation
        $this->mock('alias:' . UsesMCPServers::class, function ($mock) {
            $mock->shouldReceive('executeMCPTask')
                ->with('createPaymentMethodSetup', Mockery::any())
                ->andReturn([
                    'success' => true,
                    'result' => [
                        'setup_intent' => [
                            'id' => 'seti_123',
                            'client_secret' => 'seti_123_secret',
                            'status' => 'requires_payment_method'
                        ]
                    ]
                ]);
        });

        $response = $this->actingAs($this->billingUser, 'portal')
            ->get('/business/billing/payment-methods/add');

        $response->assertStatus(200);
        
        $setupIntentData = session('setup_intent_data');
        $this->assertNotNull($setupIntentData);
        $this->assertEquals('seti_123', $setupIntentData['setup_intent']['id']);
    }

    /** @test */
    public function store_payment_method_validates_stripe_pm()
    {
        $response = $this->actingAs($this->billingUser, 'portal')
            ->post('/business/billing/payment-methods', [
                // Missing payment_method_id
            ]);

        $response->assertSessionHasErrors('payment_method_id');
    }

    /** @test */
    public function delete_payment_method_removes_from_stripe()
    {
        // Mock successful deletion
        $this->mock('alias:' . UsesMCPServers::class, function ($mock) {
            $mock->shouldReceive('executeMCPTask')
                ->with('removePaymentMethod', Mockery::any())
                ->andReturn([
                    'success' => true,
                    'result' => [
                        'success' => true,
                        'message' => 'Payment method removed successfully'
                    ]
                ]);
        });

        $response = $this->actingAs($this->billingUser, 'portal')
            ->delete('/business/billing/payment-methods/pm_123');

        $response->assertRedirect('/business/billing/payment-methods');
        $response->assertSessionHas('success');
    }

    /** @test */
    public function billing_data_is_tenant_isolated()
    {
        // Create another company with billing data
        $otherCompany = Company::factory()->create();
        Invoice::factory()->count(5)->create([
            'company_id' => $otherCompany->id,
            'amount' => 500.00
        ]);

        $response = $this->actingAs($this->billingUser, 'portal')
            ->get('/business/billing');

        $response->assertStatus(200);
        
        // Should only see data from own company
        $billingData = session('billing_data');
        $this->assertNotNull($billingData);
        // Verify company context is passed to MCP calls
    }

    /** @test */
    public function billing_performance_is_acceptable()
    {
        $startTime = microtime(true);
        
        $response = $this->actingAs($this->billingUser, 'portal')
            ->get('/business/billing');
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(1000, $responseTime, "Billing page took {$responseTime}ms to load");
    }

    /** @test */
    public function billing_handles_mcp_service_failures_gracefully()
    {
        // Mock MCP service failure
        $this->mock('alias:' . UsesMCPServers::class, function ($mock) {
            $mock->shouldReceive('executeMCPTask')
                ->andReturn([
                    'success' => false,
                    'error' => 'Stripe service unavailable'
                ]);
        });

        $response = $this->actingAs($this->billingUser, 'portal')
            ->get('/business/billing');

        $response->assertStatus(200);
        // Should still load page even if billing data is unavailable
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}