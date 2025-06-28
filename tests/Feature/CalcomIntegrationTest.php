<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;
use App\Services\CalcomV2Service;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CalcomIntegrationTest extends TestCase
{
    use RefreshDatabase;
    
    protected Company $company;
    protected User $user;
    protected string $apiKey = 'cal_test_key_123456';
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test company and user
        $this->company = Company::factory()->create([
            'calcom_api_key' => $this->apiKey
        ]);
        
        $this->user = User::factory()->create([
            'company_id' => $this->company->id
        ]);
        
        $this->actingAs($this->user);
    }
    
    /** @test */

    #[Test]
    public function it_can_test_calcom_api_connection()
    {
        // Mock successful v2 API response
        Http::fake([
            'https://api.cal.com/v2/bookings*' => Http::response([
                'status' => 'success',
                'data' => [
                    [
                        'id' => 123,
                        'uid' => 'test-uid-123',
                        'title' => 'Test Booking',
                        'status' => 'accepted',
                        'start' => '2025-02-01T10:00:00.000Z',
                        'end' => '2025-02-01T11:00:00.000Z',
                        'attendees' => [
                            ['name' => 'Test Customer', 'email' => 'test@example.com']
                        ]
                    ]
                ]
            ], 200),
            
            // Mock failed v1 API response
            'https://api.cal.com/v1/*' => Http::response([
                'error' => 'You are not authorized to perform this request.'
            ], 403)
        ]);
        
        $service = new CalcomV2Service($this->apiKey);
        
        // Test v2 getBookings
        $response = $service->getBookings();
        
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('bookings', $response['data']);
        $this->assertCount(1, $response['data']['bookings']);
        $this->assertEquals('Test Booking', $response['data']['bookings'][0]['title']);
    }
    
    /** @test */

    #[Test]
    public function it_can_sync_bookings_from_calcom()
    {
        // Prepare test data
        $branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $staff = Staff::factory()->create(['company_id' => $this->company->id]);
        $service = Service::factory()->create(['company_id' => $this->company->id]);
        
        // Mock Cal.com API response
        Http::fake([
            'https://api.cal.com/v2/bookings*' => Http::response([
                'status' => 'success',
                'data' => [
                    [
                        'id' => 5567543,
                        'uid' => 'btZ1yBBgmu2HBbZfTvRJw7',
                        'title' => 'Test Appointment',
                        'description' => 'Test description',
                        'status' => 'accepted',
                        'start' => '2025-02-01T10:00:00.000Z',
                        'end' => '2025-02-01T11:00:00.000Z',
                        'eventTypeId' => 1726384,
                        'location' => 'Test Location',
                        'attendees' => [
                            [
                                'name' => 'John Doe',
                                'email' => 'john@example.com',
                                'timeZone' => 'Europe/Berlin'
                            ]
                        ],
                        'hosts' => [
                            [
                                'id' => 1346408,
                                'name' => 'Test Host',
                                'email' => 'host@example.com'
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);
        
        // Dispatch sync job
        \App\Jobs\SyncCalcomBookingsJob::dispatchSync($this->company, $this->apiKey);
        
        // Assert appointment was created
        $this->assertDatabaseHas('appointments', [
            'calcom_v2_booking_id' => 5567543,
            'external_id' => 'btZ1yBBgmu2HBbZfTvRJw7',
            'status' => 'accepted',
            'company_id' => $this->company->id
        ]);
        
        // Assert customer was created
        $this->assertDatabaseHas('customers', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'company_id' => $this->company->id
        ]);
        
        $appointment = Appointment::where('calcom_v2_booking_id', 5567543)->first();
        $this->assertNotNull($appointment);
        $this->assertEquals('2025-02-01 10:00:00', $appointment->starts_at->format('Y-m-d H:i:s'));
        $this->assertEquals('2025-02-01 11:00:00', $appointment->ends_at->format('Y-m-d H:i:s'));
    }
    
    /** @test */

    #[Test]
    public function it_updates_existing_appointments_on_sync()
    {
        // Create existing appointment
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'calcom_v2_booking_id' => 5567543,
            'customer_id' => $customer->id,
            'status' => 'confirmed'
        ]);
        
        // Mock updated booking data
        Http::fake([
            'https://api.cal.com/v2/bookings*' => Http::response([
                'status' => 'success',
                'data' => [
                    [
                        'id' => 5567543,
                        'uid' => 'btZ1yBBgmu2HBbZfTvRJw7',
                        'title' => 'Updated Appointment',
                        'status' => 'cancelled',
                        'start' => '2025-02-01T14:00:00.000Z',
                        'end' => '2025-02-01T15:00:00.000Z',
                        'attendees' => [
                            [
                                'name' => 'John Doe',
                                'email' => 'john@example.com'
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);
        
        // Sync again
        \App\Jobs\SyncCalcomBookingsJob::dispatchSync($this->company, $this->apiKey);
        
        // Refresh and check
        $appointment->refresh();
        $this->assertEquals('cancelled', $appointment->status);
        $this->assertEquals('2025-02-01 14:00:00', $appointment->starts_at->format('Y-m-d H:i:s'));
    }
    
    /** @test */

    #[Test]
    public function it_handles_different_calcom_statuses_correctly()
    {
        $statusMappings = [
            'ACCEPTED' => 'accepted',
            'PENDING' => 'pending',
            'CANCELLED' => 'cancelled',
            'REJECTED' => 'cancelled',
            'accepted' => 'accepted'
        ];
        
        foreach ($statusMappings as $calcomStatus => $expectedStatus) {
            Http::fake([
                'https://api.cal.com/v2/bookings*' => Http::response([
                    'status' => 'success',
                    'data' => [
                        [
                            'id' => 1000 + rand(1, 999),
                            'uid' => 'test-uid-' . $calcomStatus,
                            'title' => 'Test Status ' . $calcomStatus,
                            'status' => $calcomStatus,
                            'start' => '2025-02-01T10:00:00.000Z',
                            'end' => '2025-02-01T11:00:00.000Z',
                            'attendees' => [
                                ['name' => 'Test', 'email' => 'test' . $calcomStatus . '@example.com']
                            ]
                        ]
                    ]
                ], 200)
            ]);
            
            \App\Jobs\SyncCalcomBookingsJob::dispatchSync($this->company, $this->apiKey);
            
            $appointment = Appointment::where('external_id', 'test-uid-' . $calcomStatus)->first();
            $this->assertNotNull($appointment);
            $this->assertEquals($expectedStatus, $appointment->status);
        }
    }
    
    /** @test */

    #[Test]
    public function it_processes_calcom_webhook_for_booking_created()
    {
        $payload = [
            'triggerEvent' => 'BOOKING_CREATED',
            'createdAt' => now()->toIso8601String(),
            'payload' => [
                'id' => 999999,
                'uid' => 'webhook-test-uid',
                'title' => 'Webhook Test Booking',
                'description' => 'Created via webhook',
                'startTime' => '2025-02-15T10:00:00.000Z',
                'endTime' => '2025-02-15T11:00:00.000Z',
                'attendees' => [
                    [
                        'email' => 'webhook@example.com',
                        'name' => 'Webhook Customer',
                        'timeZone' => 'Europe/Berlin'
                    ]
                ],
                'eventType' => [
                    'id' => 12345,
                    'title' => 'Test Event Type'
                ],
                'status' => 'ACCEPTED'
            ]
        ];
        
        // Calculate signature
        $secret = config('services.calcom.webhook_secret', 'test-secret');
        $signature = hash_hmac('sha256', json_encode($payload), $secret);
        
        // Send webhook request
        $response = $this->postJson('/api/calcom/webhook', $payload, [
            'X-Cal-Signature-256' => $signature
        ]);
        
        $response->assertStatus(200);
        
        // Check appointment was created
        $this->assertDatabaseHas('appointments', [
            'calcom_v2_booking_id' => 999999,
            'external_id' => 'webhook-test-uid',
            'status' => 'accepted'
        ]);
        
        // Check customer was created
        $this->assertDatabaseHas('customers', [
            'email' => 'webhook@example.com',
            'name' => 'Webhook Customer'
        ]);
    }
    
    /** @test */

    #[Test]
    public function it_handles_booking_cancelled_webhook()
    {
        // Create existing appointment
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'calcom_v2_booking_id' => 888888,
            'customer_id' => $customer->id,
            'status' => 'confirmed'
        ]);
        
        $payload = [
            'triggerEvent' => 'BOOKING_CANCELLED',
            'createdAt' => now()->toIso8601String(),
            'payload' => [
                'id' => 888888,
                'uid' => $appointment->external_id,
                'status' => 'CANCELLED',
                'cancellationReason' => 'Customer request'
            ]
        ];
        
        $secret = config('services.calcom.webhook_secret', 'test-secret');
        $signature = hash_hmac('sha256', json_encode($payload), $secret);
        
        $response = $this->postJson('/api/calcom/webhook', $payload, [
            'X-Cal-Signature-256' => $signature
        ]);
        
        $response->assertStatus(200);
        
        $appointment->refresh();
        $this->assertEquals('cancelled', $appointment->status);
        $this->assertStringContainsString('Customer request', $appointment->meta['calcom_webhook']['cancellation_reason']);
    }
    
    /** @test */

    #[Test]
    public function it_rejects_webhook_with_invalid_signature()
    {
        $payload = [
            'triggerEvent' => 'BOOKING_CREATED',
            'payload' => ['id' => 123]
        ];
        
        $response = $this->postJson('/api/calcom/webhook', $payload, [
            'X-Cal-Signature-256' => 'invalid-signature'
        ]);
        
        $response->assertStatus(401);
    }
    
    /** @test */

    #[Test]
    public function it_can_check_slot_availability()
    {
        Http::fake([
            'https://api.cal.com/v2/slots/available*' => Http::response([
                'status' => 'success',
                'data' => [
                    'slots' => [
                        '2025-02-01T09:00:00.000Z',
                        '2025-02-01T10:00:00.000Z',
                        '2025-02-01T11:00:00.000Z',
                        '2025-02-01T14:00:00.000Z',
                        '2025-02-01T15:00:00.000Z'
                    ]
                ]
            ], 200)
        ]);
        
        $service = new CalcomV2Service($this->apiKey);
        $result = $service->checkAvailability(
            eventTypeId: 1,
            date: '2025-02-01',
            timezone: 'Europe/Berlin'
        );
        
        $this->assertTrue($result['success']);
        $this->assertCount(5, $result['data']['slots']);
    }
    
    /** @test */

    #[Test]
    public function it_handles_api_errors_gracefully()
    {
        Http::fake([
            'https://api.cal.com/v2/bookings*' => Http::response([
                'error' => 'Internal server error'
            ], 500)
        ]);
        
        $service = new CalcomV2Service($this->apiKey);
        $response = $service->getBookings();
        
        $this->assertFalse($response['success']);
        $this->assertStringContainsString('Failed to fetch bookings', $response['error']);
    }
    
    /** @test */

    #[Test]
    public function it_tracks_sync_statistics()
    {
        // Mock multiple bookings
        Http::fake([
            'https://api.cal.com/v2/bookings*' => Http::response([
                'status' => 'success',
                'data' => [
                    ['id' => 1, 'uid' => 'uid-1', 'title' => 'Booking 1', 'status' => 'accepted', 'start' => '2025-02-01T10:00:00.000Z', 'end' => '2025-02-01T11:00:00.000Z', 'attendees' => [['name' => 'Customer 1', 'email' => 'customer1@example.com']]],
                    ['id' => 2, 'uid' => 'uid-2', 'title' => 'Booking 2', 'status' => 'pending', 'start' => '2025-02-02T10:00:00.000Z', 'end' => '2025-02-02T11:00:00.000Z', 'attendees' => [['name' => 'Customer 2', 'email' => 'customer2@example.com']]],
                    ['id' => 3, 'uid' => 'uid-3', 'title' => 'Booking 3', 'status' => 'cancelled', 'start' => '2025-02-03T10:00:00.000Z', 'end' => '2025-02-03T11:00:00.000Z', 'attendees' => [['name' => 'Customer 3', 'email' => 'customer3@example.com']]]
                ]
            ], 200)
        ]);
        
        \App\Jobs\SyncCalcomBookingsJob::dispatchSync($this->company, $this->apiKey);
        
        // Check all appointments were created
        $this->assertEquals(3, Appointment::whereNotNull('calcom_v2_booking_id')->count());
        
        // Check status distribution
        $this->assertEquals(1, Appointment::where('status', 'accepted')->count());
        $this->assertEquals(1, Appointment::where('status', 'pending')->count());
        $this->assertEquals(1, Appointment::where('status', 'cancelled')->count());
    }
}