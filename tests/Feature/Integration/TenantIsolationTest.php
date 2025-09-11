<?php

namespace Tests\Feature\Integration;

use Tests\TestCase;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Customer;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\Staff;
use App\Models\Service;
use App\Services\ApiKeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant1;
    private Tenant $tenant2;
    private ApiKeyService $apiKeyService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenant1 = Tenant::factory()->create(['name' => 'Tenant One']);
        $this->tenant2 = Tenant::factory()->create(['name' => 'Tenant Two']);
        $this->apiKeyService = app(ApiKeyService::class);
    }

    /** @test */
    public function it_isolates_customer_data_between_tenants()
    {
        // Arrange
        $customer1 = Customer::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Customer One',
            'email' => 'customer1@example.com'
        ]);

        $customer2 = Customer::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'Customer Two',
            'email' => 'customer2@example.com'
        ]);

        $apiKey1 = $this->apiKeyService->generateForTenant($this->tenant1);
        $apiKey2 = $this->apiKeyService->generateForTenant($this->tenant2);

        // Act & Assert - Tenant 1 can only see their customers
        $response1 = $this->withHeaders([
            'Authorization' => "Bearer {$apiKey1}"
        ])->get('/api/customers');

        $response1->assertSuccessful();
        $response1->assertJsonCount(1, 'data');
        $response1->assertJsonFragment(['name' => 'Customer One']);
        $response1->assertJsonMissing(['name' => 'Customer Two']);

        // Act & Assert - Tenant 2 can only see their customers
        $response2 = $this->withHeaders([
            'Authorization' => "Bearer {$apiKey2}"
        ])->get('/api/customers');

        $response2->assertSuccessful();
        $response2->assertJsonCount(1, 'data');
        $response2->assertJsonFragment(['name' => 'Customer Two']);
        $response2->assertJsonMissing(['name' => 'Customer One']);
    }

    /** @test */
    public function it_prevents_cross_tenant_customer_access()
    {
        // Arrange
        $customer1 = Customer::factory()->create(['tenant_id' => $this->tenant1->id]);
        $customer2 = Customer::factory()->create(['tenant_id' => $this->tenant2->id]);

        $apiKey1 = $this->apiKeyService->generateForTenant($this->tenant1);

        // Act - Tenant 1 tries to access Tenant 2's customer
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$apiKey1}"
        ])->get("/api/customers/{$customer2->id}");

        // Assert
        $response->assertNotFound();
    }

    /** @test */
    public function it_isolates_call_data_between_tenants()
    {
        // Arrange
        $customer1 = Customer::factory()->create(['tenant_id' => $this->tenant1->id]);
        $customer2 = Customer::factory()->create(['tenant_id' => $this->tenant2->id]);

        $call1 = Call::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'customer_id' => $customer1->id,
            'call_id' => 'tenant1_call_123'
        ]);

        $call2 = Call::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'customer_id' => $customer2->id,
            'call_id' => 'tenant2_call_456'
        ]);

        $apiKey1 = $this->apiKeyService->generateForTenant($this->tenant1);
        $apiKey2 = $this->apiKeyService->generateForTenant($this->tenant2);

        // Act & Assert - Tenant 1 can only see their calls
        $response1 = $this->withHeaders([
            'Authorization' => "Bearer {$apiKey1}"
        ])->get('/api/calls');

        $response1->assertSuccessful();
        $response1->assertJsonCount(1, 'data');
        $response1->assertJsonFragment(['call_id' => 'tenant1_call_123']);
        $response1->assertJsonMissing(['call_id' => 'tenant2_call_456']);

        // Act & Assert - Tenant 2 can only see their calls
        $response2 = $this->withHeaders([
            'Authorization' => "Bearer {$apiKey2}"
        ])->get('/api/calls');

        $response2->assertSuccessful();
        $response2->assertJsonCount(1, 'data');
        $response2->assertJsonFragment(['call_id' => 'tenant2_call_456']);
        $response2->assertJsonMissing(['call_id' => 'tenant1_call_123']);
    }

    /** @test */
    public function it_isolates_appointment_data_between_tenants()
    {
        // Arrange
        $customer1 = Customer::factory()->create(['tenant_id' => $this->tenant1->id]);
        $customer2 = Customer::factory()->create(['tenant_id' => $this->tenant2->id]);

        $appointment1 = Appointment::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'customer_id' => $customer1->id,
            'start_time' => now()->addDay(),
            'notes' => 'Tenant 1 appointment'
        ]);

        $appointment2 = Appointment::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'customer_id' => $customer2->id,
            'start_time' => now()->addDays(2),
            'notes' => 'Tenant 2 appointment'
        ]);

        $apiKey1 = $this->apiKeyService->generateForTenant($this->tenant1);
        $apiKey2 = $this->apiKeyService->generateForTenant($this->tenant2);

        // Act & Assert - Tenant 1 can only see their appointments
        $response1 = $this->withHeaders([
            'Authorization' => "Bearer {$apiKey1}"
        ])->get('/api/appointments');

        $response1->assertSuccessful();
        $response1->assertJsonCount(1, 'data');
        $response1->assertJsonFragment(['notes' => 'Tenant 1 appointment']);
        $response1->assertJsonMissing(['notes' => 'Tenant 2 appointment']);

        // Act & Assert - Tenant 2 can only see their appointments
        $response2 = $this->withHeaders([
            'Authorization' => "Bearer {$apiKey2}"
        ])->get('/api/appointments');

        $response2->assertSuccessful();
        $response2->assertJsonCount(1, 'data');
        $response2->assertJsonFragment(['notes' => 'Tenant 2 appointment']);
        $response2->assertJsonMissing(['notes' => 'Tenant 1 appointment']);
    }

    /** @test */
    public function it_prevents_cross_tenant_data_creation()
    {
        // Arrange
        $customer2 = Customer::factory()->create(['tenant_id' => $this->tenant2->id]);
        $apiKey1 = $this->apiKeyService->generateForTenant($this->tenant1);

        // Act - Tenant 1 tries to create appointment for Tenant 2's customer
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$apiKey1}"
        ])->post('/api/appointments', [
            'customer_id' => $customer2->id,
            'start_time' => now()->addDay()->toISOString(),
            'end_time' => now()->addDay()->addHour()->toISOString(),
            'notes' => 'Cross-tenant appointment attempt'
        ]);

        // Assert
        $response->assertStatus(422); // Validation error or forbidden
        $this->assertDatabaseMissing('appointments', [
            'customer_id' => $customer2->id,
            'tenant_id' => $this->tenant1->id
        ]);
    }

    /** @test */
    public function it_isolates_staff_data_between_tenants()
    {
        // Arrange
        $staff1 = Staff::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Staff One',
            'email' => 'staff1@example.com'
        ]);

        $staff2 = Staff::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'Staff Two',
            'email' => 'staff2@example.com'
        ]);

        $apiKey1 = $this->apiKeyService->generateForTenant($this->tenant1);
        $apiKey2 = $this->apiKeyService->generateForTenant($this->tenant2);

        // Act & Assert - Tenant 1 can only see their staff
        $response1 = $this->withHeaders([
            'Authorization' => "Bearer {$apiKey1}"
        ])->get('/api/staff');

        $response1->assertSuccessful();
        $response1->assertJsonCount(1, 'data');
        $response1->assertJsonFragment(['name' => 'Staff One']);
        $response1->assertJsonMissing(['name' => 'Staff Two']);

        // Act & Assert - Tenant 2 can only see their staff
        $response2 = $this->withHeaders([
            'Authorization' => "Bearer {$apiKey2}"
        ])->get('/api/staff');

        $response2->assertSuccessful();
        $response2->assertJsonCount(1, 'data');
        $response2->assertJsonFragment(['name' => 'Staff Two']);
        $response2->assertJsonMissing(['name' => 'Staff One']);
    }

    /** @test */
    public function it_isolates_service_data_between_tenants()
    {
        // Arrange
        $service1 = Service::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Service One',
            'description' => 'Tenant 1 service'
        ]);

        $service2 = Service::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'Service Two',
            'description' => 'Tenant 2 service'
        ]);

        $apiKey1 = $this->apiKeyService->generateForTenant($this->tenant1);
        $apiKey2 = $this->apiKeyService->generateForTenant($this->tenant2);

        // Act & Assert - Tenant 1 can only see their services
        $response1 = $this->withHeaders([
            'Authorization' => "Bearer {$apiKey1}"
        ])->get('/api/services');

        $response1->assertSuccessful();
        $response1->assertJsonCount(1, 'data');
        $response1->assertJsonFragment(['name' => 'Service One']);
        $response1->assertJsonMissing(['name' => 'Service Two']);

        // Act & Assert - Tenant 2 can only see their services
        $response2 = $this->withHeaders([
            'Authorization' => "Bearer {$apiKey2}"
        ])->get('/api/services');

        $response2->assertSuccessful();
        $response2->assertJsonCount(1, 'data');
        $response2->assertJsonFragment(['name' => 'Service Two']);
        $response2->assertJsonMissing(['name' => 'Service One']);
    }

    /** @test */
    public function it_isolates_dashboard_statistics_between_tenants()
    {
        // Arrange
        $customer1 = Customer::factory()->create(['tenant_id' => $this->tenant1->id]);
        $customer2 = Customer::factory()->create(['tenant_id' => $this->tenant2->id]);

        // Create 5 calls for tenant 1
        Call::factory(5)->create([
            'tenant_id' => $this->tenant1->id,
            'customer_id' => $customer1->id,
            'call_successful' => true
        ]);

        // Create 3 calls for tenant 2
        Call::factory(3)->create([
            'tenant_id' => $this->tenant2->id,
            'customer_id' => $customer2->id,
            'call_successful' => true
        ]);

        $apiKey1 = $this->apiKeyService->generateForTenant($this->tenant1);
        $apiKey2 = $this->apiKeyService->generateForTenant($this->tenant2);

        // Act & Assert - Tenant 1 sees only their stats
        $response1 = $this->withHeaders([
            'Authorization' => "Bearer {$apiKey1}"
        ])->get('/api/dashboard/stats');

        $response1->assertSuccessful();
        $stats1 = $response1->json();
        $this->assertEquals(5, $stats1['calls_today']);

        // Act & Assert - Tenant 2 sees only their stats
        $response2 = $this->withHeaders([
            'Authorization' => "Bearer {$apiKey2}"
        ])->get('/api/dashboard/stats');

        $response2->assertSuccessful();
        $stats2 = $response2->json();
        $this->assertEquals(3, $stats2['calls_today']);
    }

    /** @test */
    public function it_handles_webhook_data_isolation()
    {
        // Arrange
        $webhookSecret = 'test-webhook-secret';
        config(['services.retell.webhook_secret' => $webhookSecret]);

        $webhookPayload = [
            'event' => 'call_ended',
            'data' => [
                'call_id' => 'isolation_test_call_123',
                'conversation_id' => 'conv_isolation_456',
                'from_number' => '+491234567890',
                'to_number' => '+491234567891',
                'start_timestamp' => 1699123456,
                'end_timestamp' => 1699123556,
                'duration_sec' => 100,
                'call_successful' => true,
                'transcript' => 'Test call for tenant isolation'
            ]
        ];

        $payloadJson = json_encode($webhookPayload);
        $signature = hash_hmac('sha256', $payloadJson, $webhookSecret);

        // Act - Process webhook (should determine tenant context somehow)
        $response = $this->withHeaders([
            'X-Retell-Signature' => $signature,
            'X-Tenant-ID' => $this->tenant1->id, // Assuming tenant context is provided
            'Content-Type' => 'application/json'
        ])->post('/api/webhooks/retell', $webhookPayload);

        // Assert
        $response->assertSuccessful();

        // Call should only be associated with the correct tenant
        $call = Call::where('call_id', 'isolation_test_call_123')->first();
        $this->assertNotNull($call);
        $this->assertEquals($this->tenant1->id, $call->tenant_id);

        // Other tenant should not have access to this call
        $apiKey2 = $this->apiKeyService->generateForTenant($this->tenant2);
        $callResponse = $this->withHeaders([
            'Authorization' => "Bearer {$apiKey2}"
        ])->get("/api/calls/{$call->id}");

        $callResponse->assertNotFound();
    }

    /** @test */
    public function it_prevents_api_key_cross_usage()
    {
        // Arrange
        $apiKey1 = $this->apiKeyService->generateForTenant($this->tenant1);
        $apiKey2 = $this->apiKeyService->generateForTenant($this->tenant2);

        $customer2 = Customer::factory()->create(['tenant_id' => $this->tenant2->id]);

        // Act - Tenant 1's API key tries to access Tenant 2's resources
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$apiKey1}"
        ])->get("/api/customers/{$customer2->id}");

        // Assert
        $response->assertNotFound(); // Should not find resource from different tenant
    }

    /** @test */
    public function it_maintains_data_isolation_in_database_queries()
    {
        // Arrange
        Customer::factory(10)->create(['tenant_id' => $this->tenant1->id]);
        Customer::factory(15)->create(['tenant_id' => $this->tenant2->id]);

        Call::factory(20)->create(['tenant_id' => $this->tenant1->id]);
        Call::factory(25)->create(['tenant_id' => $this->tenant2->id]);

        Appointment::factory(8)->create(['tenant_id' => $this->tenant1->id]);
        Appointment::factory(12)->create(['tenant_id' => $this->tenant2->id]);

        // Act & Assert - Verify tenant 1 counts
        $this->assertEquals(10, Customer::where('tenant_id', $this->tenant1->id)->count());
        $this->assertEquals(20, Call::where('tenant_id', $this->tenant1->id)->count());
        $this->assertEquals(8, Appointment::where('tenant_id', $this->tenant1->id)->count());

        // Act & Assert - Verify tenant 2 counts
        $this->assertEquals(15, Customer::where('tenant_id', $this->tenant2->id)->count());
        $this->assertEquals(25, Call::where('tenant_id', $this->tenant2->id)->count());
        $this->assertEquals(12, Appointment::where('tenant_id', $this->tenant2->id)->count());

        // Act & Assert - Cross-tenant queries should return empty
        $this->assertEquals(0, Customer::where('tenant_id', $this->tenant1->id)
            ->whereIn('id', Customer::where('tenant_id', $this->tenant2->id)->pluck('id'))
            ->count());
    }

    /** @test */
    public function it_ensures_unique_constraints_are_tenant_scoped()
    {
        // Arrange & Act - Same email should be allowed across different tenants
        $customer1 = Customer::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'email' => 'same@example.com'
        ]);

        $customer2 = Customer::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'email' => 'same@example.com'
        ]);

        // Assert - Both customers should exist
        $this->assertDatabaseHas('customers', [
            'tenant_id' => $this->tenant1->id,
            'email' => 'same@example.com'
        ]);

        $this->assertDatabaseHas('customers', [
            'tenant_id' => $this->tenant2->id,
            'email' => 'same@example.com'
        ]);

        $this->assertEquals(2, Customer::where('email', 'same@example.com')->count());
    }

    /** @test */
    public function it_isolates_file_uploads_and_attachments()
    {
        // This test would verify that file uploads are stored in tenant-specific directories
        // and that tenants cannot access each other's files
        
        $apiKey1 = $this->apiKeyService->generateForTenant($this->tenant1);
        $apiKey2 = $this->apiKeyService->generateForTenant($this->tenant2);

        // Mock file upload for tenant 1
        $uploadResponse1 = $this->withHeaders([
            'Authorization' => "Bearer {$apiKey1}"
        ])->post('/api/uploads', [
            'file' => \Illuminate\Http\UploadedFile::fake()->image('tenant1.jpg')
        ]);

        $uploadResponse1->assertSuccessful();
        $file1Path = $uploadResponse1->json('file_path');

        // Mock file upload for tenant 2
        $uploadResponse2 = $this->withHeaders([
            'Authorization' => "Bearer {$apiKey2}"
        ])->post('/api/uploads', [
            'file' => \Illuminate\Http\UploadedFile::fake()->image('tenant2.jpg')
        ]);

        $uploadResponse2->assertSuccessful();
        $file2Path = $uploadResponse2->json('file_path');

        // Assert files are in different tenant directories
        $this->assertStringContains($this->tenant1->id, $file1Path);
        $this->assertStringContains($this->tenant2->id, $file2Path);

        // Tenant 1 should not be able to access tenant 2's file
        $accessResponse = $this->withHeaders([
            'Authorization' => "Bearer {$apiKey1}"
        ])->get("/api/files/{$file2Path}");

        $accessResponse->assertForbidden();
    }
}