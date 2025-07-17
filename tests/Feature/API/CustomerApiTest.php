<?php

namespace Tests\Feature\API;

use Tests\TestCase;
use App\Models\Company;
use App\Models\PortalUser;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Call;
use Laravel\Sanctum\Sanctum;

class CustomerApiTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    private Company $company;
    private PortalUser $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->user = PortalUser::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'admin',
        ]);
        
        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function can_list_customers()
    {
        Customer::factory()->count(25)->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->getJson('/api/customers');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'phone_number',
                        'address',
                        'city',
                        'postal_code',
                        'is_active',
                        'created_at',
                        'last_contact_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);
    }

    /** @test */
    public function can_search_customers()
    {
        Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
        
        Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
        ]);

        $response = $this->getJson('/api/customers?search=john');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'John Doe');
    }

    /** @test */
    public function can_create_customer()
    {
        $customerData = [
            'name' => 'New Customer',
            'email' => 'new@example.com',
            'phone_number' => '+49123456789',
            'address' => '123 Main St',
            'city' => 'Berlin',
            'postal_code' => '10115',
            'notes' => 'VIP customer',
        ];

        $response = $this->postJson('/api/customers', $customerData);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'phone_number',
                    'address',
                    'city',
                    'postal_code',
                    'notes',
                ],
            ]);

        $this->assertDatabaseHas('customers', [
            'company_id' => $this->company->id,
            'email' => 'new@example.com',
        ]);
    }

    /** @test */
    public function cannot_create_customer_with_duplicate_email()
    {
        Customer::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'existing@example.com',
        ]);

        $response = $this->postJson('/api/customers', [
            'name' => 'New Customer',
            'email' => 'existing@example.com',
            'phone_number' => '+49987654321',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function can_update_customer()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'phone_number' => '+49111222333',
            'notes' => 'Updated notes',
        ];

        $response = $this->putJson("/api/customers/{$customer->id}", $updateData);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.notes', 'Updated notes');

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Updated Name',
        ]);
    }

    /** @test */
    public function can_delete_customer_without_appointments()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->deleteJson("/api/customers/{$customer->id}");

        $response->assertOk()
            ->assertJson([
                'message' => 'Customer deleted successfully',
            ]);

        $this->assertSoftDeleted('customers', [
            'id' => $customer->id,
        ]);
    }

    /** @test */
    public function cannot_delete_customer_with_appointments()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
        ]);
        
        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
        ]);

        $response = $this->deleteJson("/api/customers/{$customer->id}");

        $response->assertUnprocessable()
            ->assertJson([
                'message' => 'Cannot delete customer with existing appointments',
            ]);
    }

    /** @test */
    public function can_get_customer_details_with_history()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
        ]);
        
        // Create history
        Appointment::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
        ]);
        
        Call::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
        ]);

        $response = $this->getJson("/api/customers/{$customer->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'phone_number',
                    'statistics' => [
                        'total_appointments',
                        'completed_appointments',
                        'no_show_count',
                        'total_calls',
                        'last_contact_date',
                        'lifetime_value',
                    ],
                    'recent_appointments',
                    'recent_calls',
                ],
            ]);
    }

    /** @test */
    public function can_get_customer_appointments()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
        ]);
        
        Appointment::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
        ]);

        $response = $this->getJson("/api/customers/{$customer->id}/appointments");

        $response->assertOk()
            ->assertJsonCount(5, 'data');
    }

    /** @test */
    public function can_merge_duplicate_customers()
    {
        $primaryCustomer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'primary@example.com',
        ]);
        
        $duplicateCustomer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'duplicate@example.com',
        ]);
        
        // Create data for duplicate
        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $duplicateCustomer->id,
        ]);

        $response = $this->postJson("/api/customers/{$primaryCustomer->id}/merge", [
            'duplicate_customer_id' => $duplicateCustomer->id,
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Customers merged successfully',
                'merged_data' => [
                    'appointments_transferred' => 1,
                    'calls_transferred' => 0,
                ],
            ]);

        $this->assertDatabaseHas('appointments', [
            'customer_id' => $primaryCustomer->id,
        ]);
        
        $this->assertSoftDeleted('customers', [
            'id' => $duplicateCustomer->id,
        ]);
    }

    /** @test */
    public function can_add_customer_tags()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->postJson("/api/customers/{$customer->id}/tags", [
            'tags' => ['vip', 'frequent-buyer', 'newsletter'],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.tags', ['vip', 'frequent-buyer', 'newsletter']);
    }

    /** @test */
    public function can_get_customer_communication_preferences()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'preferences' => [
                'contact_method' => 'email',
                'language' => 'de',
                'newsletter' => true,
            ],
        ]);

        $response = $this->getJson("/api/customers/{$customer->id}/preferences");

        $response->assertOk()
            ->assertJson([
                'contact_method' => 'email',
                'language' => 'de',
                'newsletter' => true,
            ]);
    }

    /** @test */
    public function can_update_customer_preferences()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->putJson("/api/customers/{$customer->id}/preferences", [
            'contact_method' => 'sms',
            'language' => 'en',
            'newsletter' => false,
            'appointment_reminders' => true,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'preferences->contact_method' => 'sms',
            'preferences->language' => 'en',
        ]);
    }

    /** @test */
    public function can_export_customers()
    {
        Customer::factory()->count(10)->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->postJson('/api/customers/export', [
            'format' => 'csv',
            'fields' => ['name', 'email', 'phone_number', 'created_at'],
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'download_url',
                'expires_at',
            ]);
    }

    /** @test */
    public function can_import_customers()
    {
        $csvContent = "name,email,phone_number\nJohn Doe,john@example.com,+49123456789\nJane Smith,jane@example.com,+49987654321";
        
        $response = $this->postJson('/api/customers/import', [
            'file' => \Illuminate\Http\UploadedFile::fake()->createWithContent(
                'customers.csv',
                $csvContent
            ),
            'format' => 'csv',
        ]);

        $response->assertOk()
            ->assertJson([
                'imported' => 2,
                'failed' => 0,
                'errors' => [],
            ]);

        $this->assertDatabaseHas('customers', [
            'company_id' => $this->company->id,
            'email' => 'john@example.com',
        ]);
    }

    /** @test */
    public function can_get_customer_timeline()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Create timeline events
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'created_at' => now()->subDays(5),
        ]);
        
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'created_at' => now()->subDays(3),
        ]);

        $response = $this->getJson("/api/customers/{$customer->id}/timeline");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'type',
                        'title',
                        'description',
                        'datetime',
                        'metadata',
                    ],
                ],
            ])
            ->assertJsonCount(2, 'data');
    }
}