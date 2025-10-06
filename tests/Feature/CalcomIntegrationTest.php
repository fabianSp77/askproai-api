<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Jobs\ImportEventTypeJob;
use App\Jobs\UpdateCalcomEventTypeJob;
use App\Services\CalcomService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CalcomIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up Cal.com configuration for testing
        config([
            'services.calcom.api_key' => 'test_api_key',
            'services.calcom.base_url' => 'https://api.cal.com/v1',
            'services.calcom.webhook_secret' => 'test_webhook_secret',
        ]);
    }

    /**
     * Test webhook signature validation accepts valid signatures
     */
    public function test_webhook_accepts_valid_signature()
    {
        $payload = [
            'triggerEvent' => 'EVENT_TYPE.CREATED',
            'payload' => [
                'id' => 12345,
                'title' => 'Test Event Type',
            ]
        ];

        $secret = config('services.calcom.webhook_secret');
        $signature = 'sha256=' . hash_hmac('sha256', json_encode($payload), $secret);

        $response = $this->postJson('/api/calcom/webhook', $payload, [
            'X-Cal-Signature-256' => $signature,
        ]);

        $response->assertStatus(200);
    }

    /**
     * Test webhook signature validation rejects invalid signatures
     */
    public function test_webhook_rejects_invalid_signature()
    {
        $payload = [
            'triggerEvent' => 'EVENT_TYPE.CREATED',
            'payload' => [
                'id' => 12345,
                'title' => 'Test Event Type',
            ]
        ];

        $response = $this->postJson('/api/calcom/webhook', $payload, [
            'X-Cal-Signature-256' => 'invalid_signature',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test EVENT_TYPE.CREATED webhook dispatches import job
     */
    public function test_event_type_created_webhook_dispatches_job()
    {
        Queue::fake();

        $payload = [
            'triggerEvent' => 'EVENT_TYPE.CREATED',
            'payload' => [
                'id' => 12345,
                'title' => 'New Test Service',
                'length' => 30,
                'price' => 100,
                'currency' => 'EUR',
                'hidden' => false,
            ]
        ];

        $secret = config('services.calcom.webhook_secret');
        $signature = 'sha256=' . hash_hmac('sha256', json_encode($payload), $secret);

        $response = $this->postJson('/api/calcom/webhook', $payload, [
            'X-Cal-Signature-256' => $signature,
        ]);

        $response->assertStatus(200);
        Queue::assertPushed(ImportEventTypeJob::class);
    }

    /**
     * Test EVENT_TYPE.UPDATED webhook dispatches import job
     */
    public function test_event_type_updated_webhook_dispatches_job()
    {
        Queue::fake();

        $payload = [
            'triggerEvent' => 'EVENT_TYPE.UPDATED',
            'payload' => [
                'id' => 12345,
                'title' => 'Updated Test Service',
                'length' => 45,
                'price' => 150,
                'currency' => 'EUR',
                'hidden' => false,
            ]
        ];

        $secret = config('services.calcom.webhook_secret');
        $signature = 'sha256=' . hash_hmac('sha256', json_encode($payload), $secret);

        $response = $this->postJson('/api/calcom/webhook', $payload, [
            'X-Cal-Signature-256' => $signature,
        ]);

        $response->assertStatus(200);
        Queue::assertPushed(ImportEventTypeJob::class);
    }

    /**
     * Test Import job creates new service
     */
    public function test_import_job_creates_new_service()
    {
        $eventTypeData = [
            'id' => 12345,
            'title' => 'Test Service',
            'slug' => 'test-service',
            'description' => 'Test description',
            'length' => 30,
            'price' => 100,
            'currency' => 'EUR',
            'hidden' => false,
            'scheduleId' => null,
            'locations' => [],
            'metadata' => [],
            'bookingFields' => [],
        ];

        $job = new ImportEventTypeJob($eventTypeData);
        $job->handle();

        $this->assertDatabaseHas('services', [
            'calcom_event_type_id' => 12345,
            'name' => 'Test Service',
            'duration_minutes' => 30,
            'price' => 100,
            'is_active' => true,
            'sync_status' => 'synced',
        ]);
    }

    /**
     * Test Import job updates existing service
     */
    public function test_import_job_updates_existing_service()
    {
        // Create existing service
        $service = Service::factory()->create([
            'calcom_event_type_id' => 12345,
            'name' => 'Old Name',
            'duration_minutes' => 30,
            'price' => 100,
        ]);

        $eventTypeData = [
            'id' => 12345,
            'title' => 'Updated Name',
            'slug' => 'updated-service',
            'description' => 'Updated description',
            'length' => 45,
            'price' => 150,
            'currency' => 'EUR',
            'hidden' => false,
            'scheduleId' => null,
            'locations' => [],
            'metadata' => [],
            'bookingFields' => [],
        ];

        $job = new ImportEventTypeJob($eventTypeData);
        $job->handle();

        $service->refresh();

        $this->assertEquals('Updated Name', $service->name);
        $this->assertEquals(45, $service->duration_minutes);
        $this->assertEquals(150, $service->price);
        $this->assertEquals('synced', $service->sync_status);
    }

    /**
     * Test service observer prevents creation without Cal.com ID
     */
    public function test_service_observer_prevents_creation_without_calcom_id()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Services must be created through Cal.com first');

        Service::create([
            'name' => 'Invalid Service',
            'company_id' => 1,
            'duration_minutes' => 30,
            'price' => 100,
            // No calcom_event_type_id provided
        ]);
    }

    /**
     * Test service update triggers sync job
     */
    public function test_service_update_triggers_sync_job()
    {
        Queue::fake();

        $service = Service::factory()->create([
            'calcom_event_type_id' => 12345,
            'name' => 'Original Name',
            'sync_status' => 'synced',
        ]);

        $service->update(['name' => 'Updated Name']);

        Queue::assertPushed(UpdateCalcomEventTypeJob::class);
        $this->assertEquals('pending', $service->fresh()->sync_status);
    }

    /**
     * Test CalcomService connection check
     */
    public function test_calcom_service_connection_check()
    {
        Http::fake([
            'api.cal.com/v1/me*' => Http::response([
                'user' => [
                    'id' => 1,
                    'email' => 'test@example.com',
                ]
            ], 200),
        ]);

        $service = new CalcomService();
        $result = $service->testConnection();

        $this->assertTrue($result['success']);
        $this->assertEquals('Cal.com Verbindung erfolgreich', $result['message']);
    }

    /**
     * Test CalcomService fetch event types
     */
    public function test_calcom_service_fetch_event_types()
    {
        Http::fake([
            'api.cal.com/v1/event-types*' => Http::response([
                'event_types' => [
                    ['id' => 1, 'title' => 'Event 1'],
                    ['id' => 2, 'title' => 'Event 2'],
                ]
            ], 200),
        ]);

        $service = new CalcomService();
        $response = $service->fetchEventTypes();

        $this->assertTrue($response->successful());
        $this->assertCount(2, $response->json()['event_types']);
    }

    /**
     * Test sync command with check-only option
     */
    public function test_sync_command_check_only_mode()
    {
        Http::fake([
            'api.cal.com/v1/event-types*' => Http::response([
                'event_types' => [
                    ['id' => 1, 'title' => 'Event 1', 'length' => 30],
                ]
            ], 200),
        ]);

        $this->artisan('calcom:sync-services', ['--check-only' => true])
            ->assertExitCode(0)
            ->expectsOutput('Found 1 Event Types in Cal.com')
            ->expectsOutput('CHECK ONLY - No actual changes were made');
    }

    /**
     * Test webhook handles EVENT_TYPE.DELETED
     */
    public function test_event_type_deleted_webhook_deactivates_service()
    {
        $service = Service::factory()->create([
            'calcom_event_type_id' => 12345,
            'is_active' => true,
        ]);

        $payload = [
            'triggerEvent' => 'EVENT_TYPE.DELETED',
            'payload' => [
                'id' => 12345,
            ]
        ];

        $secret = config('services.calcom.webhook_secret');
        $signature = 'sha256=' . hash_hmac('sha256', json_encode($payload), $secret);

        $response = $this->postJson('/api/calcom/webhook', $payload, [
            'X-Cal-Signature-256' => $signature,
        ]);

        $response->assertStatus(200);

        $service->refresh();
        $this->assertFalse($service->is_active);
        $this->assertEquals('error', $service->sync_status);
    }
}