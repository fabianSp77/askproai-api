<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;

class CustomerApiTest extends TestCase
{
    use RefreshDatabase;
    
    protected User $user;
    protected Company $company;
    protected Branch $branch;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        
        // Authenticate user
        Sanctum::actingAs($this->user);
    }
    
    public function test_list_customers_returns_paginated_results()
    {
        // Create customers
        Customer::factory()->count(35)->create([
            'company_id' => $this->company->id
        ]);
        
        $response = $this->getJson('/api/customers');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'customers' => [
                        '*' => [
                            'id',
                            'first_name',
                            'last_name',
                            'full_name',
                            'email',
                            'phone',
                            'tags',
                            'created_at'
                        ]
                    ],
                    'pagination' => [
                        'total',
                        'per_page',
                        'current_page',
                        'last_page'
                    ]
                ]
            ]);
        
        $this->assertEquals(35, $response->json('data.pagination.total'));
        $this->assertEquals(20, $response->json('data.pagination.per_page'));
    }
    
    public function test_list_customers_with_filters()
    {
        // Create customers with tags
        Customer::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'tags' => ['vip']
        ]);
        
        Customer::factory()->count(10)->create([
            'company_id' => $this->company->id,
            'tags' => ['regular']
        ]);
        
        Customer::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'tags' => ['new'],
            'created_at' => Carbon::today()
        ]);
        
        // Filter by tags
        $response = $this->getJson('/api/customers?tags[]=vip');
        
        $response->assertStatus(200);
        $this->assertEquals(5, $response->json('data.pagination.total'));
        
        // Filter by creation date
        $response = $this->getJson('/api/customers?created_from=' . Carbon::today()->format('Y-m-d'));
        
        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('data.pagination.total'));
    }
    
    public function test_get_customer_details()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'notes' => 'VIP customer',
            'tags' => ['vip', 'regular']
        ]);
        
        // Create related data
        $service = Service::factory()->create(['company_id' => $this->company->id]);
        $staff = Staff::factory()->create(['branch_id' => $this->branch->id]);
        
        Appointment::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'branch_id' => $this->branch->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id
        ]);
        
        Call::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id
        ]);
        
        $response = $this->getJson("/api/customers/{$customer->id}");
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $customer->id,
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'email' => 'john@example.com',
                    'phone' => '+1234567890',
                    'notes' => 'VIP customer',
                    'tags' => ['vip', 'regular'],
                    'stats' => [
                        'total_appointments' => 3,
                        'total_calls' => 2
                    ]
                ]
            ]);
    }
    
    public function test_create_customer()
    {
        $response = $this->postJson('/api/customers', [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
            'phone' => '+1987654321',
            'date_of_birth' => '1990-05-15',
            'address' => '123 Main St',
            'city' => 'New York',
            'postal_code' => '10001',
            'notes' => 'New customer from API',
            'tags' => ['new', 'api-created']
        ]);
        
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Customer created successfully'
            ]);
        
        $this->assertDatabaseHas('customers', [
            'company_id' => $this->company->id,
            'email' => 'jane@example.com',
            'phone' => '+1987654321'
        ]);
        
        $customer = Customer::where('email', 'jane@example.com')->first();
        $this->assertContains('new', $customer->tags);
        $this->assertContains('api-created', $customer->tags);
    }
    
    public function test_create_customer_validation()
    {
        // Test missing required fields
        $response = $this->postJson('/api/customers', [
            'email' => 'invalid-email'
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'last_name', 'email']);
        
        // Test duplicate email
        Customer::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'existing@example.com'
        ]);
        
        $response = $this->postJson('/api/customers', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'existing@example.com',
            'phone' => '+1234567890'
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
    
    public function test_update_customer()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'first_name' => 'Old',
            'last_name' => 'Name',
            'email' => 'old@example.com'
        ]);
        
        $response = $this->putJson("/api/customers/{$customer->id}", [
            'first_name' => 'New',
            'last_name' => 'Name',
            'email' => 'new@example.com',
            'tags' => ['updated', 'vip']
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Customer updated successfully'
            ]);
        
        $customer->refresh();
        $this->assertEquals('New', $customer->first_name);
        $this->assertEquals('new@example.com', $customer->email);
        $this->assertContains('updated', $customer->tags);
        $this->assertContains('vip', $customer->tags);
    }
    
    public function test_search_customers()
    {
        // Create customers with searchable data
        $customer1 = Customer::factory()->create([
            'company_id' => $this->company->id,
            'first_name' => 'John',
            'last_name' => 'Smith',
            'email' => 'john.smith@example.com',
            'phone' => '+1234567890'
        ]);
        
        $customer2 = Customer::factory()->create([
            'company_id' => $this->company->id,
            'first_name' => 'Jane',
            'last_name' => 'Johnson',
            'email' => 'jane@example.com',
            'phone' => '+1987654321'
        ]);
        
        $customer3 = Customer::factory()->create([
            'company_id' => $this->company->id,
            'first_name' => 'Bob',
            'last_name' => 'Smith',
            'email' => 'bob@example.com',
            'phone' => '+1555555555'
        ]);
        
        // Search by name
        $response = $this->getJson('/api/customers/search?query=Smith');
        
        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('data.total'));
        
        $foundIds = array_column($response->json('data.results'), 'id');
        $this->assertContains($customer1->id, $foundIds);
        $this->assertContains($customer3->id, $foundIds);
        
        // Search by email
        $response = $this->getJson('/api/customers/search?query=jane@example.com');
        
        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('data.total'));
        $this->assertEquals($customer2->id, $response->json('data.results.0.id'));
        
        // Search by phone
        $response = $this->getJson('/api/customers/search?query=555555');
        
        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('data.total'));
        $this->assertEquals($customer3->id, $response->json('data.results.0.id'));
    }
    
    public function test_get_customer_history()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id
        ]);
        
        $service = Service::factory()->create(['company_id' => $this->company->id]);
        $staff = Staff::factory()->create(['branch_id' => $this->branch->id]);
        
        // Create appointments
        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'branch_id' => $this->branch->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id,
            'status' => 'completed',
            'starts_at' => Carbon::now()->subDays(10)
        ]);
        
        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'branch_id' => $this->branch->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id,
            'status' => 'scheduled',
            'starts_at' => Carbon::now()->addDays(5)
        ]);
        
        // Create calls
        Call::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'created_at' => Carbon::now()->subDays(7)
        ]);
        
        $response = $this->getJson("/api/customers/{$customer->id}/history");
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'appointments',
                    'calls',
                    'timeline'
                ]
            ]);
        
        $data = $response->json('data');
        $this->assertCount(2, $data['appointments']);
        $this->assertCount(1, $data['calls']);
        $this->assertCount(3, $data['timeline']); // Combined timeline
    }
    
    public function test_get_customer_stats()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'created_at' => Carbon::now()->subMonths(6)
        ]);
        
        $service = Service::factory()->create([
            'company_id' => $this->company->id,
            'price' => 100
        ]);
        
        $staff = Staff::factory()->create(['branch_id' => $this->branch->id]);
        
        // Create appointments with different statuses
        Appointment::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'branch_id' => $this->branch->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id,
            'status' => 'completed',
            'price' => 100
        ]);
        
        Appointment::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'branch_id' => $this->branch->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id,
            'status' => 'cancelled'
        ]);
        
        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'branch_id' => $this->branch->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id,
            'status' => 'no_show'
        ]);
        
        // Create calls
        Call::factory()->count(10)->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'duration' => 180
        ]);
        
        $response = $this->getJson("/api/customers/{$customer->id}/stats");
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'lifetime_value',
                    'total_appointments',
                    'completed_appointments',
                    'cancelled_appointments',
                    'no_show_appointments',
                    'cancellation_rate',
                    'no_show_rate',
                    'total_calls',
                    'average_call_duration',
                    'customer_since',
                    'last_appointment',
                    'last_call'
                ]
            ]);
        
        $stats = $response->json('data');
        $this->assertEquals(500, $stats['lifetime_value']); // 5 completed * 100
        $this->assertEquals(8, $stats['total_appointments']);
        $this->assertEquals(5, $stats['completed_appointments']);
        $this->assertEquals(25, $stats['cancellation_rate']); // 2/8 * 100
        $this->assertEquals(12.5, $stats['no_show_rate']); // 1/8 * 100
        $this->assertEquals(10, $stats['total_calls']);
        $this->assertEquals(180, $stats['average_call_duration']);
    }
    
    public function test_merge_customers()
    {
        // Create primary customer
        $primaryCustomer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890'
        ]);
        
        // Create duplicate customer
        $duplicateCustomer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'first_name' => 'Johnny',
            'last_name' => 'Doe',
            'email' => null,
            'phone' => '+1234567890'
        ]);
        
        $service = Service::factory()->create(['company_id' => $this->company->id]);
        $staff = Staff::factory()->create(['branch_id' => $this->branch->id]);
        
        // Create data for duplicate customer
        Appointment::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'customer_id' => $duplicateCustomer->id,
            'branch_id' => $this->branch->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id
        ]);
        
        Call::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'customer_id' => $duplicateCustomer->id
        ]);
        
        $response = $this->postJson('/api/customers/merge', [
            'primary_customer_id' => $primaryCustomer->id,
            'duplicate_customer_id' => $duplicateCustomer->id
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Customers merged successfully'
            ]);
        
        // Verify data was transferred
        $this->assertEquals(3, Appointment::where('customer_id', $primaryCustomer->id)->count());
        $this->assertEquals(2, Call::where('customer_id', $primaryCustomer->id)->count());
        
        // Verify duplicate was soft deleted
        $this->assertSoftDeleted('customers', [
            'id' => $duplicateCustomer->id
        ]);
    }
    
    public function test_delete_customer()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id
        ]);
        
        $response = $this->deleteJson("/api/customers/{$customer->id}");
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Customer deleted successfully'
            ]);
        
        $this->assertSoftDeleted('customers', [
            'id' => $customer->id
        ]);
    }
    
    public function test_customer_not_found()
    {
        $response = $this->getJson('/api/customers/99999');
        
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => 'Customer not found'
            ]);
    }
    
    public function test_unauthorized_access()
    {
        auth()->logout();
        
        $response = $this->getJson('/api/customers');
        
        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.'
            ]);
    }
    
    public function test_cross_company_isolation()
    {
        // Create another company with customers
        $otherCompany = Company::factory()->create();
        Customer::factory()->count(5)->create([
            'company_id' => $otherCompany->id
        ]);
        
        // Create own company customers
        Customer::factory()->count(3)->create([
            'company_id' => $this->company->id
        ]);
        
        // Should only see customers from user's company
        $response = $this->getJson('/api/customers');
        
        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('data.pagination.total'));
        
        $customers = $response->json('data.customers');
        foreach ($customers as $customer) {
            $this->assertEquals($this->company->id, $customer['company_id']);
        }
    }
}