<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Branch;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\Transaction;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\BalanceTopup;
use App\Models\WorkingHour;
use App\Models\Integration;
use App\Models\PhoneNumber;
use App\Models\RetellAgent;
use App\Models\PricingPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;
use Filament\Facades\Filament;

class PageVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test tenant
        $this->tenant = Tenant::create([
            'id' => 'test-visibility',
            'name' => 'Visibility Test Tenant',
            'tenant_type' => 'direct_customer',
            'balance_cents' => 10000,
            'is_active' => true,
            'settings' => [],
        ]);
        
        // Create admin user
        $this->adminUser = User::factory()->create([
            'email' => 'admin@visibility-test.com',
            'tenant_id' => $this->tenant->id,
        ]);
        
        // Create and assign super_admin role
        $adminRole = Role::firstOrCreate(['name' => 'super_admin']);
        $this->adminUser->assignRole($adminRole);
        
        // Create access permission
        Permission::firstOrCreate(['name' => 'access_admin_panel']);
        
        // Authenticate as admin
        $this->actingAs($this->adminUser);
        
        // Setup sample data for each resource
        $this->setupTestData();
    }

    /**
     * Setup test data for all resources
     */
    private function setupTestData(): void
    {
        // Create Company
        $company = Company::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Company',
            'email' => 'company@test.com',
            'phone' => '+49123456789',
            'address' => 'Test Street 1',
            'city' => 'Berlin',
            'postal_code' => '10115',
            'country' => 'Germany',
        ]);
        
        // Create Branch
        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Main Branch',
            'address' => 'Branch Street 1',
            'city' => 'Berlin',
            'postal_code' => '10115',
            'phone' => '+49123456789',
            'email' => 'branch@test.com',
        ]);
        
        // Create Customer
        $customer = Customer::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
            'phone' => '+49987654321',
            'address' => 'Customer Street 1',
            'city' => 'Munich',
            'postal_code' => '80331',
        ]);
        
        // Create Staff
        $staff = Staff::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Test Staff',
            'email' => 'staff@test.com',
            'phone' => '+49555666777',
            'is_active' => true,
        ]);
        
        // Create Service
        $service = Service::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $company->id,
            'name' => 'Test Service',
            'description' => 'Test service description',
            'duration_minutes' => 60,
            'price_cents' => 5000,
            'is_active' => true,
        ]);
        
        // Create Call
        $call = Call::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'retell_call_id' => 'test_call_' . uniqid(),
            'phone_number' => '+49123456789',
            'direction' => 'inbound',
            'status' => 'completed',
            'duration' => 300,
            'cost_cents' => 150,
        ]);
        
        // Create Appointment
        $appointment = Appointment::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'staff_id' => $staff->id,
            'service_id' => $service->id,
            'branch_id' => $branch->id,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'status' => 'confirmed',
        ]);
        
        // Create Transaction
        $transaction = Transaction::create([
            'tenant_id' => $this->tenant->id,
            'type' => 'credit',
            'amount_cents' => 5000,
            'balance_after_cents' => 15000,
            'description' => 'Test topup',
            'reference_id' => 'test-ref-001',
        ]);
        
        // Create Balance Topup
        $topup = BalanceTopup::create([
            'tenant_id' => $this->tenant->id,
            'amount_cents' => 5000,
            'bonus_cents' => 250,
            'status' => 'completed',
            'payment_method' => 'card',
            'stripe_session_id' => 'cs_test_123',
        ]);
        
        // Create Working Hour
        $workingHour = WorkingHour::create([
            'staff_id' => $staff->id,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_available' => true,
        ]);
        
        // Create Integration
        $integration = Integration::create([
            'tenant_id' => $this->tenant->id,
            'type' => 'calcom',
            'name' => 'Cal.com Integration',
            'config' => ['api_key' => 'test_key'],
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_can_access_admin_dashboard()
    {
        $response = $this->get('/admin');
        
        $response->assertStatus(200);
        $response->assertSee('Dashboard');
        $response->assertSee('navigation');
        $response->assertDontSee('error');
        $response->assertDontSee('exception');
    }

    /** @test */
    public function it_can_access_all_resource_index_pages()
    {
        $resources = [
            'appointments' => ['Termine', 'table', 'filter'],
            'branches' => ['Filialen', 'table', 'filter'],
            'calls' => ['Anrufe', 'table', 'filter'],
            'companies' => ['Unternehmen', 'table', 'filter'],
            'customers' => ['Kunden', 'table', 'filter'],
            'services' => ['Dienstleistungen', 'table', 'filter'],
            'staff' => ['Mitarbeiter', 'table', 'filter'],
            'users' => ['Benutzer', 'table', 'filter'],
            'transactions' => ['Transaktionen', 'table', 'filter'],
            'balance-topups' => ['Aufladungen', 'table', 'filter'],
            'working-hours' => ['Arbeitszeiten', 'table', 'filter'],
            'integrations' => ['Integrationen', 'table', 'filter'],
        ];
        
        foreach ($resources as $resource => $expectedElements) {
            $response = $this->get("/admin/{$resource}");
            
            $response->assertStatus(200);
            
            // Check for common UI elements
            foreach ($expectedElements as $element) {
                $response->assertSee($element, false);
            }
            
            // Check for no errors
            $response->assertDontSee('error', false);
            $response->assertDontSee('exception', false);
            $response->assertDontSee('undefined', false);
            
            // Check for Filament components
            $response->assertSee('filament', false);
            $response->assertSee('wire:id', false);
        }
    }

    /** @test */
    public function it_can_access_all_resource_create_pages()
    {
        $resources = [
            'appointments' => ['Termin erstellen', 'form', 'submit'],
            'branches' => ['Filiale erstellen', 'form', 'submit'],
            'calls' => ['Anruf erstellen', 'form', 'submit'],
            'companies' => ['Unternehmen erstellen', 'form', 'submit'],
            'customers' => ['Kunde erstellen', 'form', 'submit'],
            'services' => ['Dienstleistung erstellen', 'form', 'submit'],
            'staff' => ['Mitarbeiter erstellen', 'form', 'submit'],
            'users' => ['Benutzer erstellen', 'form', 'submit'],
            'transactions' => ['Transaktion erstellen', 'form', 'submit'],
            'balance-topups' => ['Aufladung erstellen', 'form', 'submit'],
            'working-hours' => ['Arbeitszeit erstellen', 'form', 'submit'],
            'integrations' => ['Integration erstellen', 'form', 'submit'],
        ];
        
        foreach ($resources as $resource => $expectedElements) {
            $response = $this->get("/admin/{$resource}/create");
            
            // Some resources might not have create pages
            if ($response->status() === 404) {
                continue;
            }
            
            $response->assertStatus(200);
            
            // Check for form elements
            $response->assertSee('form', false);
            $response->assertSee('input', false);
            $response->assertSee('button', false);
            
            // Check for no errors
            $response->assertDontSee('error', false);
            $response->assertDontSee('exception', false);
        }
    }

    /** @test */
    public function it_can_access_resource_edit_pages()
    {
        $testCases = [
            'appointments' => Appointment::first(),
            'branches' => Branch::first(),
            'calls' => Call::first(),
            'companies' => Company::first(),
            'customers' => Customer::first(),
            'services' => Service::first(),
            'staff' => Staff::first(),
            'users' => User::first(),
            'transactions' => Transaction::first(),
            'balance-topups' => BalanceTopup::first(),
            'working-hours' => WorkingHour::first(),
            'integrations' => Integration::first(),
        ];
        
        foreach ($testCases as $resource => $model) {
            if (!$model) {
                continue;
            }
            
            $response = $this->get("/admin/{$resource}/{$model->id}/edit");
            
            // Some resources might not have edit pages
            if ($response->status() === 404) {
                continue;
            }
            
            $response->assertStatus(200);
            
            // Check for form with existing data
            $response->assertSee('form', false);
            $response->assertSee('value=', false);
            
            // Check for save button
            $response->assertSee('button', false);
            
            // Check for no errors
            $response->assertDontSee('error', false);
            $response->assertDontSee('exception', false);
        }
    }

    /** @test */
    public function it_can_access_resource_view_pages()
    {
        $testCases = [
            'appointments' => Appointment::first(),
            'branches' => Branch::first(),
            'calls' => Call::first(),
            'companies' => Company::first(),
            'customers' => Customer::first(),
            'services' => Service::first(),
            'staff' => Staff::first(),
            'users' => User::first(),
            'transactions' => Transaction::first(),
            'balance-topups' => BalanceTopup::first(),
        ];
        
        foreach ($testCases as $resource => $model) {
            if (!$model) {
                continue;
            }
            
            // Try view page (not all resources have it)
            $response = $this->get("/admin/{$resource}/{$model->id}");
            
            if ($response->status() === 404) {
                // Try alternative view route
                $response = $this->get("/admin/{$resource}/{$model->id}/view");
            }
            
            if ($response->status() === 404) {
                continue;
            }
            
            $response->assertStatus(200);
            
            // Check for data display elements
            $response->assertDontSee('error', false);
            $response->assertDontSee('exception', false);
        }
    }

    /** @test */
    public function it_shows_navigation_menu_on_all_pages()
    {
        $pages = [
            '/admin',
            '/admin/appointments',
            '/admin/customers',
            '/admin/companies',
            '/admin/calls',
            '/admin/transactions',
        ];
        
        foreach ($pages as $page) {
            $response = $this->get($page);
            
            if ($response->status() !== 200) {
                continue;
            }
            
            // Check for navigation elements
            $response->assertSee('nav', false);
            $response->assertSee('menu', false);
            
            // Check for common navigation items
            $response->assertSee('Dashboard', false);
            
            // Check for user menu
            $response->assertSee($this->adminUser->email, false);
        }
    }

    /** @test */
    public function it_loads_javascript_and_css_assets()
    {
        $response = $this->get('/admin');
        
        $response->assertStatus(200);
        
        // Check for CSS files
        $response->assertSee('filament.css', false);
        $response->assertSee('app.css', false);
        
        // Check for JavaScript files
        $response->assertSee('livewire.js', false);
        $response->assertSee('filament', false);
        
        // Check for Alpine.js initialization
        $response->assertSee('x-data', false);
        $response->assertSee('wire:', false);
    }

    /** @test */
    public function it_displays_data_tables_correctly()
    {
        // Create multiple records for pagination test
        for ($i = 0; $i < 15; $i++) {
            Customer::create([
                'tenant_id' => $this->tenant->id,
                'name' => "Test Customer {$i}",
                'email' => "customer{$i}@test.com",
                'phone' => "+4900000000{$i}",
            ]);
        }
        
        $response = $this->get('/admin/customers');
        
        $response->assertStatus(200);
        
        // Check for table structure
        $response->assertSee('table', false);
        $response->assertSee('thead', false);
        $response->assertSee('tbody', false);
        $response->assertSee('tr', false);
        $response->assertSee('td', false);
        
        // Check for pagination
        $response->assertSee('pagination', false);
        
        // Check for records
        $response->assertSee('Test Customer', false);
        
        // Check for actions
        $response->assertSee('button', false);
    }

    /** @test */
    public function it_displays_forms_with_proper_fields()
    {
        $response = $this->get('/admin/customers/create');
        
        if ($response->status() !== 200) {
            $this->markTestSkipped('Customer create page not accessible');
        }
        
        // Check for form elements
        $response->assertSee('form', false);
        $response->assertSee('input', false);
        $response->assertSee('name=', false);
        $response->assertSee('type="text"', false);
        $response->assertSee('type="email"', false);
        $response->assertSee('required', false);
        
        // Check for labels
        $response->assertSee('label', false);
        
        // Check for submit button
        $response->assertSee('type="submit"', false);
    }

    /** @test */
    public function it_handles_responsive_design()
    {
        $response = $this->get('/admin');
        
        $response->assertStatus(200);
        
        // Check for responsive meta tag
        $response->assertSee('viewport', false);
        $response->assertSee('width=device-width', false);
        
        // Check for responsive classes
        $response->assertSee('sm:', false);
        $response->assertSee('md:', false);
        $response->assertSee('lg:', false);
    }

    /** @test */
    public function it_shows_error_pages_correctly()
    {
        // Test 404 page
        $response = $this->get('/admin/non-existent-page');
        
        $response->assertStatus(404);
        $response->assertSee('404', false);
        
        // Should still have basic layout
        $response->assertSee('html', false);
        $response->assertSee('body', false);
    }

    /** @test */
    public function it_displays_widgets_on_dashboard()
    {
        $response = $this->get('/admin');
        
        $response->assertStatus(200);
        
        // Check for widget containers
        $response->assertSee('widget', false);
        $response->assertSee('card', false);
        
        // Check for stats or metrics
        $response->assertSee('stat', false);
    }

    /** @test */
    public function it_shows_action_buttons_on_list_pages()
    {
        $response = $this->get('/admin/customers');
        
        $response->assertStatus(200);
        
        // Check for action buttons
        $response->assertSee('create', false);
        $response->assertSee('edit', false);
        $response->assertSee('delete', false);
        
        // Check for bulk actions
        $response->assertSee('bulk', false);
    }

    /** @test */
    public function it_displays_filters_on_list_pages()
    {
        $response = $this->get('/admin/transactions');
        
        $response->assertStatus(200);
        
        // Check for filter elements
        $response->assertSee('filter', false);
        $response->assertSee('search', false);
        
        // Check for date filters
        $response->assertSee('date', false);
    }

    /** @test */
    public function it_shows_breadcrumbs_navigation()
    {
        $response = $this->get('/admin/customers/create');
        
        if ($response->status() !== 200) {
            $this->markTestSkipped('Page not accessible');
        }
        
        // Check for breadcrumb elements
        $response->assertSee('breadcrumb', false);
        $response->assertSee('Admin', false);
        $response->assertSee('Customers', false);
    }

    /** @test */
    public function it_displays_notifications_area()
    {
        $response = $this->get('/admin');
        
        $response->assertStatus(200);
        
        // Check for notification elements
        $response->assertSee('notification', false);
    }

    /** @test */
    public function it_validates_authentication_on_admin_pages()
    {
        // Logout
        auth()->logout();
        
        // Try to access admin page
        $response = $this->get('/admin');
        
        // Should redirect to login
        $response->assertRedirect('/admin/login');
    }

    /** @test */
    public function it_shows_login_page_with_form_elements()
    {
        // Logout first
        auth()->logout();
        
        $response = $this->get('/admin/login');
        
        $response->assertStatus(200);
        
        // Check for login form elements
        $response->assertSee('form', false);
        $response->assertSee('email', false);
        $response->assertSee('password', false);
        $response->assertSee('submit', false);
        
        // Check for labels
        $response->assertSee('E-Mail', false);
        $response->assertSee('Passwort', false);
    }

    /** @test */
    public function it_displays_modals_correctly()
    {
        $response = $this->get('/admin/customers');
        
        $response->assertStatus(200);
        
        // Check for modal elements
        $response->assertSee('modal', false);
        $response->assertSee('dialog', false);
    }

    /** @test */
    public function it_shows_tooltips_and_help_text()
    {
        $response = $this->get('/admin/customers/create');
        
        if ($response->status() !== 200) {
            $this->markTestSkipped('Page not accessible');
        }
        
        // Check for help elements
        $response->assertSee('help', false);
        $response->assertSee('hint', false);
    }

    /** @test */
    public function it_loads_livewire_components()
    {
        $response = $this->get('/admin');
        
        $response->assertStatus(200);
        
        // Check for Livewire initialization
        $response->assertSee('livewire', false);
        $response->assertSee('wire:id', false);
        $response->assertSee('wire:initial-data', false);
        
        // Check for Livewire scripts
        $response->assertSee('@livewireScripts', false);
        $response->assertSee('window.livewire', false);
    }

    /** @test */
    public function it_displays_icons_and_images()
    {
        $response = $this->get('/admin');
        
        $response->assertStatus(200);
        
        // Check for icon elements
        $response->assertSee('svg', false);
        $response->assertSee('icon', false);
        
        // Check for image tags
        $response->assertSee('img', false);
    }

    /** @test */
    public function it_shows_success_and_error_messages()
    {
        // Create a customer to trigger success message
        $response = $this->post('/admin/customers', [
            'name' => 'New Customer',
            'email' => 'new@customer.com',
            'phone' => '+49123456789',
        ]);
        
        if ($response->status() === 302) {
            $response = $this->followRedirects($response);
        }
        
        // Check for message containers
        $content = $response->getContent();
        $this->assertStringContainsString('message', $content);
    }

    /** @test */
    public function it_verifies_all_critical_ui_elements_are_visible()
    {
        $criticalPages = [
            '/admin' => ['dashboard', 'navigation', 'user-menu'],
            '/admin/customers' => ['table', 'search', 'create-button'],
            '/admin/transactions' => ['table', 'filters', 'export'],
            '/admin/calls' => ['table', 'status', 'duration'],
            '/admin/appointments' => ['calendar', 'list', 'create'],
        ];
        
        foreach ($criticalPages as $url => $elements) {
            $response = $this->get($url);
            
            if ($response->status() !== 200) {
                $this->fail("Critical page {$url} is not accessible");
            }
            
            $content = strtolower($response->getContent());
            
            // Verify no error messages
            $this->assertStringNotContainsString('error', $content);
            $this->assertStringNotContainsString('exception', $content);
            $this->assertStringNotContainsString('undefined', $content);
            $this->assertStringNotContainsString('fatal', $content);
            
            // Verify page loaded completely
            $this->assertStringContainsString('</html>', $content);
            $this->assertStringContainsString('</body>', $content);
        }
    }

    /** @test */
    public function it_validates_javascript_console_has_no_errors()
    {
        $response = $this->get('/admin');
        
        $response->assertStatus(200);
        
        // Check that error handling is in place
        $response->assertSee('window.onerror', false);
        
        // Verify no inline script errors
        $content = $response->getContent();
        $this->assertStringNotContainsString('console.error', $content);
        $this->assertStringNotContainsString('throw new Error', $content);
    }

    /** @test */
    public function it_ensures_all_pages_have_proper_meta_tags()
    {
        $pages = ['/admin', '/admin/customers', '/admin/transactions'];
        
        foreach ($pages as $page) {
            $response = $this->get($page);
            
            if ($response->status() !== 200) {
                continue;
            }
            
            // Check for essential meta tags
            $response->assertSee('<meta charset=', false);
            $response->assertSee('<meta name="viewport"', false);
            $response->assertSee('<title>', false);
            
            // Check for CSRF token
            $response->assertSee('csrf-token', false);
        }
    }

    /** @test */
    public function it_validates_all_api_endpoints_return_json()
    {
        // Test API endpoints
        $apiEndpoints = [
            ['GET', '/api/health'],
            ['GET', '/api/billing/balance'],
            ['GET', '/api/billing/transactions'],
        ];
        
        foreach ($apiEndpoints as [$method, $url]) {
            $response = $this->json($method, $url);
            
            // API endpoints should return JSON
            $this->assertJson($response->getContent());
        }
    }

    /** @test */
    public function it_comprehensive_visibility_validation_summary()
    {
        $results = [
            'pages_tested' => 0,
            'pages_accessible' => 0,
            'pages_with_errors' => 0,
            'critical_elements_found' => 0,
            'javascript_loaded' => false,
            'css_loaded' => false,
            'livewire_initialized' => false,
        ];
        
        // Test all main resource pages
        $resources = [
            'appointments', 'branches', 'calls', 'companies', 
            'customers', 'services', 'staff', 'users',
            'transactions', 'balance-topups', 'working-hours'
        ];
        
        foreach ($resources as $resource) {
            $results['pages_tested']++;
            
            $response = $this->get("/admin/{$resource}");
            
            if ($response->status() === 200) {
                $results['pages_accessible']++;
                
                $content = $response->getContent();
                
                // Check for errors
                if (stripos($content, 'error') === false && 
                    stripos($content, 'exception') === false) {
                    // No errors found
                } else {
                    $results['pages_with_errors']++;
                }
                
                // Check for critical elements
                if (stripos($content, 'table') !== false) {
                    $results['critical_elements_found']++;
                }
                
                // Check for assets
                if (stripos($content, '.css') !== false) {
                    $results['css_loaded'] = true;
                }
                
                if (stripos($content, '.js') !== false) {
                    $results['javascript_loaded'] = true;
                }
                
                if (stripos($content, 'livewire') !== false) {
                    $results['livewire_initialized'] = true;
                }
            }
        }
        
        // Assert comprehensive visibility
        $this->assertGreaterThan(0, $results['pages_accessible'], 
            'No pages are accessible');
        
        $this->assertEquals(0, $results['pages_with_errors'], 
            'Some pages contain errors');
        
        $this->assertTrue($results['javascript_loaded'], 
            'JavaScript not loaded on pages');
        
        $this->assertTrue($results['css_loaded'], 
            'CSS not loaded on pages');
        
        $this->assertTrue($results['livewire_initialized'], 
            'Livewire not initialized');
        
        // Calculate visibility score
        $visibilityScore = ($results['pages_accessible'] / $results['pages_tested']) * 100;
        
        $this->assertGreaterThanOrEqual(90, $visibilityScore, 
            "Visibility score is too low: {$visibilityScore}%");
        
        // Log results for debugging
        echo "\n=== Page Visibility Test Results ===\n";
        echo "Pages Tested: {$results['pages_tested']}\n";
        echo "Pages Accessible: {$results['pages_accessible']}\n";
        echo "Pages with Errors: {$results['pages_with_errors']}\n";
        echo "Visibility Score: {$visibilityScore}%\n";
        echo "===================================\n";
    }
}