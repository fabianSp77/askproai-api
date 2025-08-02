<?php

namespace Tests\Feature\Portal\Emergency;

use Tests\TestCase;
use App\Models\Customer;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Call;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CriticalPathTest extends TestCase
{
    use RefreshDatabase;

    protected $company;
    protected $branch;
    protected $customer;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test company and branch
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'password' => bcrypt('password')
        ]);
    }

    /**
     * Test customer can access login page
     */
    public function test_customer_can_access_login_page()
    {
        $response = $this->get('/business/login');
        
        $response->assertOk();
        $response->assertSee('Login');
        $response->assertSessionMissing('errors');
    }

    /**
     * Test customer can login with valid credentials
     */
    public function test_customer_can_login()
    {
        $response = $this->post('/business/login', [
            'email' => $this->customer->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/business/dashboard');
        $this->assertAuthenticatedAs($this->customer, 'customer');
    }

    /**
     * Test customer cannot login with invalid credentials
     */
    public function test_customer_cannot_login_with_invalid_credentials()
    {
        $response = $this->post('/business/login', [
            'email' => $this->customer->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest('customer');
    }

    /**
     * Test dashboard loads for authenticated customer
     */
    public function test_dashboard_loads_for_authenticated_customer()
    {
        $response = $this->actingAs($this->customer, 'customer')
            ->get('/business/dashboard');

        $response->assertOk();
        // React app should be loaded
        $response->assertSee('id="root"', false);
    }

    /**
     * Test unauthenticated user is redirected to login
     */
    public function test_unauthenticated_user_redirected_to_login()
    {
        $response = $this->get('/business/dashboard');
        
        $response->assertRedirect('/business/login');
    }

    /**
     * Test customer API endpoints require authentication
     */
    public function test_api_endpoints_require_authentication()
    {
        $endpoints = [
            '/business/api/customers',
            '/business/api/stats',
            '/business/api/user',
            '/business/api/appointments',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            $response->assertUnauthorized();
        }
    }

    /**
     * Test authenticated customer can access API endpoints
     */
    public function test_authenticated_customer_can_access_api_endpoints()
    {
        $response = $this->actingAs($this->customer, 'customer')
            ->getJson('/business/api/user');

        $response->assertOk();
        $response->assertJsonStructure(['id', 'name', 'email']);
    }

    /**
     * Test customer list API returns paginated data
     */
    public function test_customer_list_api_returns_paginated_data()
    {
        // Create additional test customers
        Customer::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->getJson('/business/api/customers');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'email', 'phone']
            ],
            'meta' => ['current_page', 'total']
        ]);
    }

    /**
     * Test CSRF protection is disabled for API routes
     */
    public function test_csrf_protection_disabled_for_api_routes()
    {
        // This should not return 419 CSRF error
        $response = $this->actingAs($this->customer, 'customer')
            ->postJson('/business/api/appointments', [
                'customer_id' => $this->customer->id,
                'date' => now()->addDays(1)->format('Y-m-d'),
                'time' => '14:00',
                'service' => 'Test Service'
            ]);

        // Should not be 419 (CSRF error)
        $this->assertNotEquals(419, $response->status());
    }

    /**
     * Test recent calls are displayed on dashboard
     */
    public function test_recent_calls_displayed_on_dashboard()
    {
        // Create test calls
        $calls = Call::factory()->count(3)->create([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'company_id' => $this->company->id
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->getJson('/business/api/dashboard/recent-calls');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    /**
     * Test upcoming appointments are displayed
     */
    public function test_upcoming_appointments_displayed()
    {
        // Create test appointments
        $appointments = Appointment::factory()->count(2)->create([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'company_id' => $this->company->id,
            'date' => now()->addDays(1),
            'status' => 'scheduled'
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->getJson('/business/api/dashboard/upcoming-appointments');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    /**
     * Test customer can logout
     */
    public function test_customer_can_logout()
    {
        $this->actingAs($this->customer, 'customer');

        $response = $this->post('/business/logout');

        $response->assertRedirect('/business/login');
        $this->assertGuest('customer');
    }

    /**
     * Test mobile navigation renders correctly
     */
    public function test_mobile_navigation_renders_correctly()
    {
        $response = $this->actingAs($this->customer, 'customer')
            ->get('/business/dashboard');

        $response->assertOk();
        // Mobile nav component should be included
        $response->assertSee('MobileBottomNavAntd', false);
    }

    /**
     * Test error handling for failed API calls
     */
    public function test_error_handling_for_failed_api_calls()
    {
        // Test non-existent customer
        $response = $this->actingAs($this->customer, 'customer')
            ->getJson('/business/api/customers/999999');

        $response->assertNotFound();
        $response->assertJson(['error' => 'Customer not found']);
    }
}