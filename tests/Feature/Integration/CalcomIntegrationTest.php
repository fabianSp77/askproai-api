<?php

namespace Tests\Feature\Integration;

use Tests\TestCase;
use App\Models\Tenant;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Appointment;
use App\Services\CalcomService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessCalcomBookingJob;

class CalcomIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private string $webhookSecret = 'test-calcom-secret';

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenant = Tenant::factory()->create();
        config([
            'services.calcom.webhook_secret' => $this->webhookSecret,
            'services.calcom.base_url' => 'https://api.cal.com/v2',
            'services.calcom.api_key' => 'test-api-key'
        ]);
        
        Queue::fake();
    }

    /** @test */
    public function it_handles_complete_booking_flow_from_webhook_to_appointment()
    {
        // Arrange
        $staff = Staff::factory()->create(['tenant_id' => $this->tenant->id]);
        $service = Service::factory()->create(['tenant_id' => $this->tenant->id]);
        
        // Mock Cal.com API responses
        Http::fake([
            'https://api.cal.com/v2/event-types/123' => Http::response([
                'event_type' => [
                    'id' => 123,
                    'length' => 60,
                    'title' => 'Consultation'
                ]
            ], 200),
            'https://api.cal.com/v2/bookings' => Http::response([
                'booking' => [
                    'id' => 12345,
                    'status' => 'ACCEPTED'
                ]
            ], 201)
        ]);

        $webhookPayload = [
            'triggerEvent' => 'BOOKING_CREATED',
            'createdAt' => '2025-09-05T10:00:00.000Z',
            'payload' => [
                'bookingId' => 12345,
                'type' => 'Consultation',
                'title' => 'Consultation between John Doe and Dr. Smith',
                'startTime' => '2025-09-06T14:00:00.000Z',
                'endTime' => '2025-09-06T15:00:00.000Z',
                'status' => 'ACCEPTED',
                'organizer' => [
                    'email' => 'dr.smith@example.com',
                    'name' => 'Dr. Smith'
                ],
                'attendees' => [
                    [
                        'email' => 'john@example.com',
                        'name' => 'John Doe',
                        'timeZone' => 'Europe/Berlin'
                    ]
                ]
            ]
        ];

        $payloadJson = json_encode($webhookPayload);
        $signature = hash_hmac('sha256', $payloadJson, $this->webhookSecret);

        // Act - Send webhook
        $response = $this->withHeaders([
            'X-Cal-Signature-256' => $signature,
            'Content-Type' => 'application/json'
        ])->post('/api/webhooks/calcom', $webhookPayload);

        // Assert webhook response
        $response->assertSuccessful();
        
        // Assert job was queued
        Queue::assertPushed(ProcessCalcomBookingJob::class);
        
        // Process the job manually for testing
        $job = new ProcessCalcomBookingJob($webhookPayload, $this->tenant);
        $job->handle();

        // Assert customer was created
        $this->assertDatabaseHas('customers', [
            'tenant_id' => $this->tenant->id,
            'email' => 'john@example.com',
            'name' => 'John Doe'
        ]);

        // Assert appointment was created
        $this->assertDatabaseHas('appointments', [
            'tenant_id' => $this->tenant->id,
            'calcom_booking_id' => '12345',
            'status' => 'scheduled'
        ]);
    }

    /** @test */
    public function it_handles_booking_cancellation_flow()
    {
        // Arrange
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'john@example.com'
        ]);
        
        $appointment = Appointment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
            'calcom_booking_id' => '12345',
            'status' => 'scheduled'
        ]);

        $webhookPayload = [
            'triggerEvent' => 'BOOKING_CANCELLED',
            'createdAt' => '2025-09-05T12:00:00.000Z',
            'payload' => [
                'bookingId' => 12345,
                'cancellationReason' => 'Patient unable to attend',
                'organizer' => [
                    'email' => 'dr.smith@example.com',
                    'name' => 'Dr. Smith'
                ],
                'attendees' => [
                    [
                        'email' => 'john@example.com',
                        'name' => 'John Doe'
                    ]
                ]
            ]
        ];

        $payloadJson = json_encode($webhookPayload);
        $signature = hash_hmac('sha256', $payloadJson, $this->webhookSecret);

        // Act
        $response = $this->withHeaders([
            'X-Cal-Signature-256' => $signature,
            'Content-Type' => 'application/json'
        ])->post('/api/webhooks/calcom', $webhookPayload);

        // Process job
        $job = new ProcessCalcomBookingJob($webhookPayload, $this->tenant);
        $job->handle();

        // Assert
        $response->assertSuccessful();
        
        $appointment->refresh();
        $this->assertEquals('cancelled', $appointment->status);
        $this->assertStringContains('Patient unable to attend', $appointment->notes);
    }

    /** @test */
    public function it_handles_booking_rescheduling_flow()
    {
        // Arrange
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'john@example.com'
        ]);
        
        $appointment = Appointment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
            'calcom_booking_id' => '12345',
            'start_time' => '2025-09-06T14:00:00Z',
            'end_time' => '2025-09-06T15:00:00Z',
            'status' => 'scheduled'
        ]);

        $webhookPayload = [
            'triggerEvent' => 'BOOKING_RESCHEDULED',
            'createdAt' => '2025-09-05T11:00:00.000Z',
            'payload' => [
                'bookingId' => 12345,
                'type' => 'Consultation',
                'startTime' => '2025-09-07T16:00:00.000Z', // New time
                'endTime' => '2025-09-07T17:00:00.000Z',
                'status' => 'ACCEPTED',
                'organizer' => [
                    'email' => 'dr.smith@example.com',
                    'name' => 'Dr. Smith'
                ],
                'attendees' => [
                    [
                        'email' => 'john@example.com',
                        'name' => 'John Doe'
                    ]
                ],
                'rescheduleReason' => 'Patient requested different time'
            ]
        ];

        $payloadJson = json_encode($webhookPayload);
        $signature = hash_hmac('sha256', $payloadJson, $this->webhookSecret);

        // Act
        $response = $this->withHeaders([
            'X-Cal-Signature-256' => $signature,
            'Content-Type' => 'application/json'
        ])->post('/api/webhooks/calcom', $webhookPayload);

        // Process job
        $job = new ProcessCalcomBookingJob($webhookPayload, $this->tenant);
        $job->handle();

        // Assert
        $response->assertSuccessful();
        
        $appointment->refresh();
        $this->assertEquals('2025-09-07 16:00:00', $appointment->start_time->toDateTimeString());
        $this->assertEquals('2025-09-07 17:00:00', $appointment->end_time->toDateTimeString());
        $this->assertStringContains('Patient requested different time', $appointment->notes);
    }

    /** @test */
    public function it_creates_appointment_through_calcom_service()
    {
        // Arrange
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com'
        ]);

        Http::fake([
            'https://api.cal.com/v2/event-types/123' => Http::response([
                'event_type' => [
                    'id' => 123,
                    'length' => 30,
                    'title' => 'Quick Consultation'
                ]
            ], 200),
            'https://api.cal.com/v2/bookings' => Http::response([
                'booking' => [
                    'id' => 67890,
                    'uid' => 'booking-uid-123',
                    'startTime' => '2025-09-08T10:00:00Z',
                    'endTime' => '2025-09-08T10:30:00Z',
                    'status' => 'ACCEPTED'
                ]
            ], 201)
        ]);

        $calcomService = app(CalcomService::class);
        $bookingData = [
            'attendee_name' => 'Jane Doe',
            'attendee_email' => 'jane@example.com',
            'start_time' => '2025-09-08T10:00:00Z',
            'notes' => 'Follow-up appointment'
        ];

        // Act
        $result = $calcomService->createBookingFromCall($bookingData);

        // Assert
        $this->assertArrayHasKey('booking', $result);
        $this->assertEquals(67890, $result['booking']['id']);
        $this->assertEquals('ACCEPTED', $result['booking']['status']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.cal.com/v2/event-types/123' &&
                   $request->method() === 'GET';
        });

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.cal.com/v2/bookings' &&
                   $request->method() === 'POST';
        });
    }

    /** @test */
    public function it_handles_cal_com_api_errors_gracefully()
    {
        // Arrange
        Http::fake([
            'https://api.cal.com/v2/event-types/123' => Http::response(['error' => 'Not found'], 404),
            'https://api.cal.com/v2/bookings' => Http::response(['error' => 'Validation failed'], 422)
        ]);

        $calcomService = app(CalcomService::class);
        $bookingData = [
            'attendee_name' => 'Test User',
            'attendee_email' => 'test@example.com',
            'start_time' => '2025-09-08T10:00:00Z'
        ];

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to fetch event type');
        
        $calcomService->createBookingFromCall($bookingData);
    }

    /** @test */
    public function it_synchronizes_cal_com_bookings_with_local_appointments()
    {
        // Arrange
        Http::fake([
            'https://api.cal.com/v2/bookings*' => Http::response([
                'bookings' => [
                    [
                        'id' => 11111,
                        'title' => 'Existing Booking',
                        'startTime' => '2025-09-08T14:00:00Z',
                        'endTime' => '2025-09-08T15:00:00Z',
                        'status' => 'ACCEPTED',
                        'attendees' => [
                            ['email' => 'existing@example.com', 'name' => 'Existing Customer']
                        ]
                    ],
                    [
                        'id' => 22222,
                        'title' => 'Another Booking',
                        'startTime' => '2025-09-09T10:00:00Z',
                        'endTime' => '2025-09-09T11:00:00Z',
                        'status' => 'ACCEPTED',
                        'attendees' => [
                            ['email' => 'another@example.com', 'name' => 'Another Customer']
                        ]
                    ]
                ]
            ], 200)
        ]);

        $calcomService = app(CalcomService::class);

        // Act
        $calcomService->syncBookings($this->tenant);

        // Assert
        $this->assertDatabaseHas('customers', [
            'tenant_id' => $this->tenant->id,
            'email' => 'existing@example.com',
            'name' => 'Existing Customer'
        ]);

        $this->assertDatabaseHas('appointments', [
            'tenant_id' => $this->tenant->id,
            'calcom_booking_id' => '11111',
            'status' => 'scheduled'
        ]);

        $this->assertDatabaseHas('appointments', [
            'tenant_id' => $this->tenant->id,
            'calcom_booking_id' => '22222',
            'status' => 'scheduled'
        ]);
    }

    /** @test */
    public function it_handles_cal_com_rate_limiting()
    {
        // Arrange
        Http::fake([
            'https://api.cal.com/v2/event-types/123' => Http::sequence()
                ->push('', 429, ['Retry-After' => '1']) // Rate limited
                ->push([
                    'event_type' => [
                        'id' => 123,
                        'length' => 30,
                        'title' => 'Test Event'
                    ]
                ], 200) // Success after retry
        ]);

        $calcomService = app(CalcomService::class);

        // Act
        $result = $calcomService->getEventType(123);

        // Assert
        $this->assertArrayHasKey('event_type', $result);
        $this->assertEquals(123, $result['event_type']['id']);

        // Should have made 2 requests (1 rate limited + 1 success)
        Http::assertSentCount(2);
    }

    /** @test */
    public function it_validates_webhook_signature_correctly()
    {
        // Arrange
        $webhookPayload = [
            'triggerEvent' => 'BOOKING_CREATED',
            'payload' => ['bookingId' => 123]
        ];

        $payloadJson = json_encode($webhookPayload);
        $validSignature = hash_hmac('sha256', $payloadJson, $this->webhookSecret);
        $invalidSignature = 'invalid_signature_123';

        // Act & Assert - Valid signature
        $validResponse = $this->withHeaders([
            'X-Cal-Signature-256' => $validSignature,
            'Content-Type' => 'application/json'
        ])->post('/api/webhooks/calcom', $webhookPayload);

        $validResponse->assertSuccessful();

        // Act & Assert - Invalid signature
        $invalidResponse = $this->withHeaders([
            'X-Cal-Signature-256' => $invalidSignature,
            'Content-Type' => 'application/json'
        ])->post('/api/webhooks/calcom', $webhookPayload);

        $invalidResponse->assertStatus(401);
        $invalidResponse->assertJsonFragment(['error' => 'Invalid signature']);
    }

    /** @test */
    public function it_processes_cal_com_webhook_with_custom_fields()
    {
        // Arrange
        $webhookPayload = [
            'triggerEvent' => 'BOOKING_CREATED',
            'createdAt' => '2025-09-05T10:00:00.000Z',
            'payload' => [
                'bookingId' => 55555,
                'type' => 'Special Consultation',
                'startTime' => '2025-09-10T09:00:00.000Z',
                'endTime' => '2025-09-10T10:00:00.000Z',
                'organizer' => [
                    'email' => 'specialist@example.com',
                    'name' => 'Dr. Specialist'
                ],
                'attendees' => [
                    [
                        'email' => 'patient@example.com',
                        'name' => 'Patient Name',
                        'responses' => [
                            'phone' => '+491234567890',
                            'insurance' => 'Public Health',
                            'symptoms' => 'Headache and fatigue',
                            'preferred_language' => 'German'
                        ]
                    ]
                ],
                'metadata' => [
                    'department' => 'neurology',
                    'priority' => 'high',
                    'referral_source' => 'general_practitioner'
                ]
            ]
        ];

        $payloadJson = json_encode($webhookPayload);
        $signature = hash_hmac('sha256', $payloadJson, $this->webhookSecret);

        // Act
        $response = $this->withHeaders([
            'X-Cal-Signature-256' => $signature,
            'Content-Type' => 'application/json'
        ])->post('/api/webhooks/calcom', $webhookPayload);

        // Process job
        $job = new ProcessCalcomBookingJob($webhookPayload, $this->tenant);
        $job->handle();

        // Assert
        $response->assertSuccessful();

        // Customer should be created with additional info
        $customer = Customer::where('email', 'patient@example.com')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('+491234567890', $customer->phone);
        $this->assertEquals('German', $customer->preferred_language);

        // Appointment should be created with metadata
        $appointment = Appointment::where('calcom_booking_id', '55555')->first();
        $this->assertNotNull($appointment);
        $this->assertStringContains('neurology', $appointment->notes);
        $this->assertStringContains('high', $appointment->notes);
    }
}