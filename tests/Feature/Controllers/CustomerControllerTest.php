<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Customer;
use App\Models\Call;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);
        
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_list_customers()
    {
        // Arrange
        Customer::factory(5)->create(['tenant_id' => $this->tenant->id]);
        
        // Create customers for other tenant (should not appear)
        $otherTenant = Tenant::factory()->create();
        Customer::factory(3)->create(['tenant_id' => $otherTenant->id]);

        // Act
        $response = $this->get('/api/customers');

        // Assert
        $response->assertSuccessful();
        $response->assertJsonCount(5, 'data');
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'created_at',
                    'updated_at'
                ]
            ],
            'meta' => [
                'current_page',
                'total',
                'per_page'
            ]
        ]);
    }

    /** @test */
    public function it_can_create_customer()
    {
        // Arrange
        $customerData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+491234567890',
            'birthdate' => '1990-05-15'
        ];

        // Act
        $response = $this->post('/api/customers', $customerData);

        // Assert
        $response->assertCreated();
        $response->assertJsonFragment([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+491234567890'
        ]);

        $this->assertDatabaseHas('customers', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+491234567890',
            'tenant_id' => $this->tenant->id
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_customer()
    {
        // Act
        $response = $this->post('/api/customers', []);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'phone']);
    }

    /** @test */
    public function it_validates_email_format()
    {
        // Act
        $response = $this->post('/api/customers', [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'phone' => '+491234567890'
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_validates_phone_format()
    {
        // Act
        $response = $this->post('/api/customers', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => 'invalid-phone'
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['phone']);
    }

    /** @test */
    public function it_prevents_duplicate_email_within_tenant()
    {
        // Arrange
        Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'john@example.com'
        ]);

        // Act
        $response = $this->post('/api/customers', [
            'name' => 'John Duplicate',
            'email' => 'john@example.com',
            'phone' => '+491234567891'
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_allows_duplicate_email_across_different_tenants()
    {
        // Arrange
        $otherTenant = Tenant::factory()->create();
        Customer::factory()->create([
            'tenant_id' => $otherTenant->id,
            'email' => 'john@example.com'
        ]);

        // Act
        $response = $this->post('/api/customers', [
            'name' => 'John Different Tenant',
            'email' => 'john@example.com',
            'phone' => '+491234567890'
        ]);

        // Assert
        $response->assertCreated();
    }

    /** @test */
    public function it_can_show_single_customer()
    {
        // Arrange
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com'
        ]);

        // Act
        $response = $this->get("/api/customers/{$customer->id}");

        // Assert
        $response->assertSuccessful();
        $response->assertJsonFragment([
            'id' => $customer->id,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com'
        ]);
    }

    /** @test */
    public function it_shows_customer_with_related_calls_and_appointments()
    {
        // Arrange
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $calls = Call::factory(3)->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id
        ]);
        
        $appointments = Appointment::factory(2)->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id
        ]);

        // Act
        $response = $this->get("/api/customers/{$customer->id}?include=calls,appointments");

        // Assert
        $response->assertSuccessful();
        $response->assertJsonCount(3, 'calls');
        $response->assertJsonCount(2, 'appointments');
    }

    /** @test */
    public function it_cannot_access_customer_from_different_tenant()
    {
        // Arrange
        $otherTenant = Tenant::factory()->create();
        $otherCustomer = Customer::factory()->create([
            'tenant_id' => $otherTenant->id
        ]);

        // Act
        $response = $this->get("/api/customers/{$otherCustomer->id}");

        // Assert
        $response->assertNotFound();
    }

    /** @test */
    public function it_can_update_customer()
    {
        // Arrange
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Old Name',
            'email' => 'old@example.com'
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '+491234567890'
        ];

        // Act
        $response = $this->put("/api/customers/{$customer->id}", $updateData);

        // Assert
        $response->assertSuccessful();
        $response->assertJsonFragment([
            'name' => 'Updated Name',
            'email' => 'updated@example.com'
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '+491234567890'
        ]);
    }

    /** @test */
    public function it_can_delete_customer()
    {
        // Arrange
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);

        // Act
        $response = $this->delete("/api/customers/{$customer->id}");

        // Assert
        $response->assertSuccessful();
        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    /** @test */
    public function it_cannot_delete_customer_with_associated_appointments()
    {
        // Arrange
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
        Appointment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
            'status' => 'scheduled'
        ]);

        // Act
        $response = $this->delete("/api/customers/{$customer->id}");

        // Assert
        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'Cannot delete customer with scheduled appointments'
        ]);
        
        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
    }

    /** @test */
    public function it_can_search_customers()
    {
        // Arrange
        Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'John Smith',
            'email' => 'john.smith@example.com'
        ]);
        
        Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Jane Doe',
            'email' => 'jane.doe@example.com'
        ]);

        // Act
        $response = $this->get('/api/customers?search=John');

        // Assert
        $response->assertSuccessful();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['name' => 'John Smith']);
    }

    /** @test */
    public function it_can_filter_customers_by_date_range()
    {
        // Arrange
        Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'created_at' => now()->subDays(10)
        ]);
        
        Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'created_at' => now()->subDays(2)
        ]);

        // Act
        $response = $this->get('/api/customers?from_date=' . now()->subDays(5)->toDateString());

        // Assert
        $response->assertSuccessful();
        $response->assertJsonCount(1, 'data');
    }

    /** @test */
    public function it_paginates_customers_correctly()
    {
        // Arrange
        Customer::factory(25)->create(['tenant_id' => $this->tenant->id]);

        // Act
        $response = $this->get('/api/customers?per_page=10&page=2');

        // Assert
        $response->assertSuccessful();
        $response->assertJsonCount(10, 'data');
        $response->assertJsonPath('meta.current_page', 2);
        $response->assertJsonPath('meta.total', 25);
    }

    /** @test */
    public function it_can_export_customers_to_csv()
    {
        // Arrange
        Customer::factory(5)->create(['tenant_id' => $this->tenant->id]);

        // Act
        $response = $this->get('/api/customers/export?format=csv');

        // Assert
        $response->assertSuccessful();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename="customers.csv"');
    }

    /** @test */
    public function it_tracks_customer_activity_history()
    {
        // Arrange
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
        
        Call::factory(2)->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
            'created_at' => now()->subDays(5)
        ]);
        
        Appointment::factory(1)->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
            'start_time' => now()->subDays(3)
        ]);

        // Act
        $response = $this->get("/api/customers/{$customer->id}/activity");

        // Assert
        $response->assertSuccessful();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'type', // 'call' or 'appointment'
                    'date',
                    'details'
                ]
            ]
        ]);
        
        $this->assertCount(3, $response->json('data')); // 2 calls + 1 appointment
    }
}