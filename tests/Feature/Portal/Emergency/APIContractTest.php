<?php

namespace Tests\Feature\Portal\Emergency;

use Tests\TestCase;
use App\Models\Customer;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;

class APIContractTest extends TestCase
{
    use RefreshDatabase;

    protected $customer;
    protected $company;
    protected $branch;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id
        ]);
    }

    /**
     * Test Dashboard Stats API contract
     */
    public function test_dashboard_stats_api_contract()
    {
        $response = $this->actingAs($this->customer, 'customer')
            ->getJson('/business/api/stats');

        $response->assertOk();
        $response->assertJsonStructure([
            'total_calls' => ['value', 'change', 'trend'],
            'total_appointments' => ['value', 'change', 'trend'],
            'conversion_rate' => ['value', 'change', 'trend'],
            'avg_call_duration' => ['value', 'change', 'trend'],
        ]);
    }

    /**
     * Test Customer List API contract
     */
    public function test_customer_list_api_contract()
    {
        Customer::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->getJson('/business/api/customers');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'company_name',
                    'tags',
                    'created_at',
                    'last_appointment',
                    'total_appointments'
                ]
            ],
            'meta' => [
                'current_page',
                'from',
                'last_page',
                'per_page',
                'to',
                'total'
            ]
        ]);
    }

    /**
     * Test Customer Detail API contract
     */
    public function test_customer_detail_api_contract()
    {
        $testCustomer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->getJson("/business/api/customers/{$testCustomer->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'email',
                'phone',
                'company_name',
                'address',
                'tags',
                'notes',
                'created_at',
                'updated_at',
                'statistics' => [
                    'total_appointments',
                    'completed_appointments',
                    'cancelled_appointments',
                    'no_show_appointments',
                    'total_revenue',
                    'last_appointment_date'
                ],
                'recent_activities' => [
                    '*' => [
                        'type',
                        'description',
                        'created_at'
                    ]
                ]
            ]
        ]);
    }

    /**
     * Test Appointments List API contract
     */
    public function test_appointments_list_api_contract()
    {
        $response = $this->actingAs($this->customer, 'customer')
            ->getJson('/business/api/appointments');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'customer' => ['id', 'name', 'phone'],
                    'service' => ['id', 'name', 'duration', 'price'],
                    'staff' => ['id', 'name'],
                    'date',
                    'time',
                    'status',
                    'notes',
                    'created_at'
                ]
            ],
            'meta' => [
                'current_page',
                'total'
            ]
        ]);
    }

    /**
     * Test Billing Data API contract
     */
    public function test_billing_data_api_contract()
    {
        $response = $this->actingAs($this->customer, 'customer')
            ->getJson('/business/api/billing');

        $response->assertOk();
        $response->assertJsonStructure([
            'balance' => [
                'current',
                'currency',
                'low_balance_threshold'
            ],
            'usage' => [
                'current_month' => [
                    'calls',
                    'minutes',
                    'appointments',
                    'cost'
                ],
                'last_month' => [
                    'calls',
                    'minutes',
                    'appointments',
                    'cost'
                ]
            ],
            'auto_topup' => [
                'enabled',
                'amount',
                'threshold'
            ],
            'payment_methods' => [
                '*' => [
                    'id',
                    'type',
                    'last4',
                    'brand',
                    'is_default'
                ]
            ]
        ]);
    }

    /**
     * Test User Profile API contract
     */
    public function test_user_profile_api_contract()
    {
        $response = $this->actingAs($this->customer, 'customer')
            ->getJson('/business/api/user');

        $response->assertOk();
        $response->assertJsonStructure([
            'id',
            'name',
            'email',
            'phone',
            'company' => [
                'id',
                'name',
                'logo_url'
            ],
            'branch' => [
                'id',
                'name',
                'address'
            ],
            'permissions' => [],
            'preferences' => [
                'locale',
                'timezone',
                'notifications'
            ]
        ]);
    }

    /**
     * Test error response format
     */
    public function test_error_response_format()
    {
        // Test 404 error
        $response = $this->actingAs($this->customer, 'customer')
            ->getJson('/business/api/customers/999999');

        $response->assertNotFound();
        $response->assertJsonStructure([
            'error',
            'message'
        ]);

        // Test validation error
        $response = $this->actingAs($this->customer, 'customer')
            ->postJson('/business/api/customers', []);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'message',
            'errors' => []
        ]);
    }

    /**
     * Test pagination parameters
     */
    public function test_pagination_parameters()
    {
        Customer::factory()->count(30)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id
        ]);

        // Test page parameter
        $response = $this->actingAs($this->customer, 'customer')
            ->getJson('/business/api/customers?page=2&per_page=10');

        $response->assertOk();
        $response->assertJson([
            'meta' => [
                'current_page' => 2,
                'per_page' => 10
            ]
        ]);

        // Test per_page limit
        $response = $this->actingAs($this->customer, 'customer')
            ->getJson('/business/api/customers?per_page=100');

        $response->assertOk();
        // Should be limited to max 50
        $this->assertLessThanOrEqual(50, count($response->json('data')));
    }

    /**
     * Test search and filter parameters
     */
    public function test_search_and_filter_parameters()
    {
        Customer::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'John Doe',
            'tags' => ['vip', 'regular']
        ]);

        // Test search
        $response = $this->actingAs($this->customer, 'customer')
            ->getJson('/business/api/customers?search=John');

        $response->assertOk();
        $response->assertJsonPath('data.0.name', 'John Doe');

        // Test tag filter
        $response = $this->actingAs($this->customer, 'customer')
            ->getJson('/business/api/customers?tag=vip');

        $response->assertOk();
        $this->assertGreaterThan(0, count($response->json('data')));
    }

    /**
     * Test sorting parameters
     */
    public function test_sorting_parameters()
    {
        Customer::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id
        ]);

        // Test sort by created_at desc (default)
        $response = $this->actingAs($this->customer, 'customer')
            ->getJson('/business/api/customers?sort_by=created_at&sort_order=desc');

        $response->assertOk();
        
        $data = $response->json('data');
        $this->assertTrue(
            $data[0]['created_at'] >= $data[1]['created_at']
        );
    }
}