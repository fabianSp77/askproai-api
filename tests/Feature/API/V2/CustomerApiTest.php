<?php

namespace Tests\Feature\API\V2;

use App\Models\Appointment;
use App\Models\Call;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CustomerApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Company $company;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create tenant and company
        $this->tenant = Tenant::factory()->create();
        $this->company = Company::factory()->create(['tenant_id' => $this->tenant->id]);
        
        // Create user with company association
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'tenant_id' => $this->tenant->id
        ]);
    }

    /**
     * Test unauthenticated access is rejected
     */
    #[Test]
    public function test_unauthenticated_access_is_rejected()
    {
        $response = $this->getJson('/api/v2/customers');
        
        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.'
            ]);
    }

    /**
     * Test GET /customers - List customers with pagination
     */
    #[Test]
    public function test_can_list_customers_with_pagination()
    {
        Sanctum::actingAs($this->user);
        
        // Create 30 customers
        Customer::factory(30)->create(['company_id' => $this->company->id]);
        
        $response = $this->getJson('/api/v2/customers?page=1&per_page=15');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'phone',
                        'mobile',
                        'birthdate',
                        'gender',
                        'address',
                        'city',
                        'postal_code',
                        'country',
                        'notes',
                        'tags',
                        'total_appointments',
                        'last_appointment_date',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'per_page',
                    'total'
                ],
                'links' => ['first', 'last', 'prev', 'next']
            ])
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.total', 30);
    }

    /**
     * Test POST /customers - Create new customer
     */
    #[Test]
    public function test_can_create_customer()
    {
        Sanctum::actingAs($this->user);
        
        $customerData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+49 30 12345678',
            'mobile' => '+49 170 1234567',
            'birthdate' => '1990-05-15',
            'gender' => 'male',
            'address' => 'Hauptstraße 123',
            'city' => 'Berlin',
            'postal_code' => '10115',
            'country' => 'Germany',
            'notes' => 'VIP customer'
        ];
        
        $response = $this->postJson('/api/v2/customers', $customerData);
        
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'phone',
                    'mobile',
                    'birthdate',
                    'gender',
                    'address',
                    'city',
                    'postal_code',
                    'country',
                    'notes',
                    'tags',
                    'created_at',
                    'updated_at'
                ]
            ])
            ->assertJsonPath('data.email', 'john.doe@example.com')
            ->assertJsonPath('data.first_name', 'John');
        
        $this->assertDatabaseHas('customers', [
            'email' => 'john.doe@example.com',
            'company_id' => $this->company->id
        ]);
    }

    /**
     * Test POST /customers validation
     */
    #[Test]
    public function test_create_customer_validation()
    {
        Sanctum::actingAs($this->user);
        
        // Missing required fields
        $response = $this->postJson('/api/v2/customers', []);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'last_name']);
        
        // Invalid email format
        $response = $this->postJson('/api/v2/customers', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'invalid-email'
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
        
        // Duplicate email
        $existingCustomer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'existing@example.com'
        ]);
        
        $response = $this->postJson('/api/v2/customers', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'existing@example.com'
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
        
        // Invalid phone format
        $response = $this->postJson('/api/v2/customers', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '123' // Too short
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
        
        // Invalid birthdate
        $response = $this->postJson('/api/v2/customers', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birthdate' => 'not-a-date'
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['birthdate']);
        
        // Future birthdate
        $response = $this->postJson('/api/v2/customers', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birthdate' => now()->addDay()->format('Y-m-d')
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['birthdate']);
    }

    /**
     * Test GET /customers/{id} - Show single customer
     */
    #[Test]
    public function test_can_show_single_customer()
    {
        Sanctum::actingAs($this->user);
        
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);
        
        $response = $this->getJson("/api/v2/customers/{$customer->id}");
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'phone',
                    'mobile',
                    'birthdate',
                    'gender',
                    'address',
                    'city',
                    'postal_code',
                    'country',
                    'notes',
                    'tags',
                    'total_appointments',
                    'last_appointment_date',
                    'no_show_count',
                    'created_at',
                    'updated_at'
                ]
            ])
            ->assertJsonPath('data.id', $customer->id);
    }

    /**
     * Test PUT /customers/{id} - Update customer
     */
    #[Test]
    public function test_can_update_customer()
    {
        Sanctum::actingAs($this->user);
        
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);
        
        $updateData = [
            'first_name' => 'Jane',
            'email' => 'jane.updated@example.com',
            'notes' => 'Updated customer notes'
        ];
        
        $response = $this->putJson("/api/v2/customers/{$customer->id}", $updateData);
        
        $response->assertStatus(200)
            ->assertJsonPath('data.first_name', 'Jane')
            ->assertJsonPath('data.email', 'jane.updated@example.com')
            ->assertJsonPath('data.notes', 'Updated customer notes');
        
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'first_name' => 'Jane',
            'email' => 'jane.updated@example.com'
        ]);
    }

    /**
     * Test DELETE /customers/{id} - Delete customer
     */
    #[Test]
    public function test_can_delete_customer()
    {
        Sanctum::actingAs($this->user);
        
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);
        
        $response = $this->deleteJson("/api/v2/customers/{$customer->id}");
        
        $response->assertStatus(204);
        
        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    /**
     * Test cannot delete customer with appointments
     */
    #[Test]
    public function test_cannot_delete_customer_with_appointments()
    {
        Sanctum::actingAs($this->user);
        
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);
        
        // Create an appointment for the customer
        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id
        ]);
        
        $response = $this->deleteJson("/api/v2/customers/{$customer->id}");
        
        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Cannot delete customer with existing appointments'
            ]);
        
        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
    }

    /**
     * Test GET /customers/search - Search customers
     */
    #[Test]
    public function test_can_search_customers()
    {
        Sanctum::actingAs($this->user);
        
        // Create customers with searchable data
        Customer::factory()->create([
            'company_id' => $this->company->id,
            'first_name' => 'Maximilian',
            'last_name' => 'Schmidt',
            'email' => 'max.schmidt@example.com'
        ]);
        
        Customer::factory()->create([
            'company_id' => $this->company->id,
            'first_name' => 'Maria',
            'last_name' => 'Müller',
            'phone' => '+49 30 98765432'
        ]);
        
        // Search by name
        $response = $this->getJson('/api/v2/customers/search?q=Maximilian');
        
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.first_name', 'Maximilian');
        
        // Search by email
        $response = $this->getJson('/api/v2/customers/search?q=schmidt@example');
        
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
        
        // Search by phone
        $response = $this->getJson('/api/v2/customers/search?q=98765432');
        
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.first_name', 'Maria');
    }

    /**
     * Test GET /customers/{id}/appointments - Get customer appointments
     */
    #[Test]
    public function test_can_get_customer_appointments()
    {
        Sanctum::actingAs($this->user);
        
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);
        
        // Create appointments for the customer
        Appointment::factory(5)->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'status' => 'completed'
        ]);
        
        Appointment::factory(2)->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'status' => 'scheduled'
        ]);
        
        $response = $this->getJson("/api/v2/customers/{$customer->id}/appointments");
        
        $response->assertStatus(200)
            ->assertJsonCount(7, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'start_time',
                        'end_time',
                        'status',
                        'service',
                        'staff',
                        'branch'
                    ]
                ]
            ]);
    }

    /**
     * Test POST /customers/{id}/tags - Add tag to customer
     */
    #[Test]
    public function test_can_add_tag_to_customer()
    {
        Sanctum::actingAs($this->user);
        
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);
        
        $response = $this->postJson("/api/v2/customers/{$customer->id}/tags", [
            'tag' => 'VIP'
        ]);
        
        $response->assertStatus(200)
            ->assertJsonPath('data.tags', ['VIP']);
        
        // Add another tag
        $response = $this->postJson("/api/v2/customers/{$customer->id}/tags", [
            'tag' => 'Regular'
        ]);
        
        $response->assertStatus(200)
            ->assertJsonPath('data.tags', ['VIP', 'Regular']);
    }

    /**
     * Test DELETE /customers/{id}/tags/{tag} - Remove tag from customer
     */
    #[Test]
    public function test_can_remove_tag_from_customer()
    {
        Sanctum::actingAs($this->user);
        
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'tags' => ['VIP', 'Regular', 'Newsletter']
        ]);
        
        $response = $this->deleteJson("/api/v2/customers/{$customer->id}/tags/Regular");
        
        $response->assertStatus(200)
            ->assertJsonPath('data.tags', ['VIP', 'Newsletter']);
    }

    /**
     * Test multi-tenancy isolation
     */
    #[Test]
    public function test_customers_are_isolated_by_tenant()
    {
        // Create another tenant with customers
        $otherTenant = Tenant::factory()->create();
        $otherCompany = Company::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherUser = User::factory()->create([
            'company_id' => $otherCompany->id,
            'tenant_id' => $otherTenant->id
        ]);
        
        // Create customers for both tenants
        Customer::factory(8)->create(['company_id' => $this->company->id]);
        Customer::factory(5)->create(['company_id' => $otherCompany->id]);
        
        // Login as first tenant user
        Sanctum::actingAs($this->user);
        $response = $this->getJson('/api/v2/customers');
        
        $response->assertStatus(200)
            ->assertJsonCount(8, 'data');
        
        // Login as other tenant user
        Sanctum::actingAs($otherUser);
        $response = $this->getJson('/api/v2/customers');
        
        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    /**
     * Test filtering customers
     */
    #[Test]
    public function test_can_filter_customers()
    {
        Sanctum::actingAs($this->user);
        
        // Create customers with different attributes
        Customer::factory(3)->create([
            'company_id' => $this->company->id,
            'gender' => 'male'
        ]);
        
        Customer::factory(2)->create([
            'company_id' => $this->company->id,
            'gender' => 'female'
        ]);
        
        Customer::factory()->create([
            'company_id' => $this->company->id,
            'tags' => ['VIP']
        ]);
        
        // Filter by gender
        $response = $this->getJson('/api/v2/customers?gender=male');
        
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
        
        // Filter by tag
        $response = $this->getJson('/api/v2/customers?tag=VIP');
        
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
        
        // Filter by created date
        $response = $this->getJson('/api/v2/customers?created_after=' . now()->subDay()->format('Y-m-d'));
        
        $response->assertStatus(200);
    }

    /**
     * Test sorting customers
     */
    #[Test]
    public function test_can_sort_customers()
    {
        Sanctum::actingAs($this->user);
        
        // Create customers with different names
        Customer::factory()->create([
            'company_id' => $this->company->id,
            'last_name' => 'Anderson'
        ]);
        
        Customer::factory()->create([
            'company_id' => $this->company->id,
            'last_name' => 'Zimmermann'
        ]);
        
        Customer::factory()->create([
            'company_id' => $this->company->id,
            'last_name' => 'Miller'
        ]);
        
        // Sort by last name ascending
        $response = $this->getJson('/api/v2/customers?sort=last_name&order=asc');
        
        $response->assertStatus(200)
            ->assertJsonPath('data.0.last_name', 'Anderson')
            ->assertJsonPath('data.2.last_name', 'Zimmermann');
        
        // Sort by last name descending
        $response = $this->getJson('/api/v2/customers?sort=last_name&order=desc');
        
        $response->assertStatus(200)
            ->assertJsonPath('data.0.last_name', 'Zimmermann')
            ->assertJsonPath('data.2.last_name', 'Anderson');
    }

    /**
     * Test rate limiting
     */
    #[Test]
    public function test_rate_limiting_is_enforced()
    {
        Sanctum::actingAs($this->user);
        
        // Clear rate limiter
        RateLimiter::clear('api:' . $this->user->id);
        
        // Make requests up to the limit
        for ($i = 0; $i < 60; $i++) {
            $this->getJson('/api/v2/customers')->assertStatus(200);
        }
        
        // Next request should be rate limited
        $response = $this->getJson('/api/v2/customers');
        
        $response->assertStatus(429)
            ->assertHeader('X-RateLimit-Limit')
            ->assertHeader('X-RateLimit-Remaining');
    }

    /**
     * Test API versioning headers
     */
    #[Test]
    public function test_api_versioning_headers()
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->getJson('/api/v2/customers');
        
        $response->assertStatus(200)
            ->assertHeader('X-API-Version', 'v2');
    }

    /**
     * Test handling of non-existent resources
     */
    #[Test]
    public function test_returns_404_for_non_existent_customer()
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->getJson('/api/v2/customers/99999');
        
        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Resource not found'
            ]);
    }

    /**
     * Test cross-tenant access is blocked
     */
    #[Test]
    public function test_cannot_access_other_tenant_customer()
    {
        Sanctum::actingAs($this->user);
        
        // Create customer for another tenant
        $otherTenant = Tenant::factory()->create();
        $otherCompany = Company::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherCustomer = Customer::factory()->create(['company_id' => $otherCompany->id]);
        
        $response = $this->getJson("/api/v2/customers/{$otherCustomer->id}");
        
        $response->assertStatus(404);
    }

    /**
     * Test customer merge functionality
     */
    #[Test]
    public function test_can_merge_duplicate_customers()
    {
        Sanctum::actingAs($this->user);
        
        // Create two customers
        $customer1 = Customer::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'john@example.com'
        ]);
        
        $customer2 = Customer::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'john.doe@example.com'
        ]);
        
        // Create appointments for both
        Appointment::factory(3)->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer1->id
        ]);
        
        Appointment::factory(2)->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer2->id
        ]);
        
        // Merge customer2 into customer1
        $response = $this->postJson("/api/v2/customers/{$customer1->id}/merge", [
            'source_customer_id' => $customer2->id
        ]);
        
        $response->assertStatus(200)
            ->assertJsonPath('data.id', $customer1->id);
        
        // Verify appointments were transferred
        $this->assertEquals(5, Appointment::where('customer_id', $customer1->id)->count());
        
        // Verify source customer was deleted
        $this->assertDatabaseMissing('customers', ['id' => $customer2->id]);
    }

    /**
     * Test customer export functionality
     */
    #[Test]
    public function test_can_export_customers()
    {
        Sanctum::actingAs($this->user);
        
        Customer::factory(10)->create(['company_id' => $this->company->id]);
        
        $response = $this->getJson('/api/v2/customers/export?format=csv');
        
        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertHeader('Content-Disposition');
    }
}