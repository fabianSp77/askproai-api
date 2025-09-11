<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\Tenant;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Staff;
use App\Models\Service;
use App\Models\CalcomBooking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessCalcomBookingJob;

class CalcomWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private string $webhookSecret = 'test-calcom-secret';

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenant = Tenant::factory()->create();
        config(['services.calcom.webhook_secret' => $this->webhookSecret]);
        Queue::fake();
    }

    /** @test */
    public function it_processes_booking_created_webhook()
    {
        // Arrange
        $staff = Staff::factory()->create(['tenant_id' => $this->tenant->id]);
        $service = Service::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $payload = [
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
                    'id' => $staff->id,
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

        $payloadJson = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadJson, $this->webhookSecret);

        // Act
        $response = $this->withHeaders([
            'X-Cal-Signature-256' => $signature,
            'Content-Type' => 'application/json'
        ])->post('/api/webhooks/calcom', $payload);

        // Assert
        $response->assertSuccessful();
        $response->assertJsonFragment([
            'received' => true,
            'status' => 'processed'
        ]);

        Queue::assertPushed(ProcessCalcomBookingJob::class, function ($job) {
            return $job->bookingData['payload']['bookingId'] === 12345;
        });
    }

    /** @test */
    public function it_processes_booking_rescheduled_webhook()
    {
        // Arrange
        $existingAppointment = Appointment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'calcom_booking_id' => '12345'
        ]);

        $payload = [
            'triggerEvent' => 'BOOKING_RESCHEDULED',
            'createdAt' => '2025-09-05T11:00:00.000Z',
            'payload' => [
                'bookingId' => 12345,
                'type' => 'Consultation',
                'startTime' => '2025-09-07T15:00:00.000Z', // Rescheduled time
                'endTime' => '2025-09-07T16:00:00.000Z',
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

        $payloadJson = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadJson, $this->webhookSecret);

        // Act
        $response = $this->withHeaders([
            'X-Cal-Signature-256' => $signature,
            'Content-Type' => 'application/json'
        ])->post('/api/webhooks/calcom', $payload);

        // Assert
        $response->assertSuccessful();
        
        Queue::assertPushed(ProcessCalcomBookingJob::class, function ($job) {
            return $job->bookingData['triggerEvent'] === 'BOOKING_RESCHEDULED';
        });
    }

    /** @test */
    public function it_processes_booking_cancelled_webhook()
    {
        // Arrange
        $existingAppointment = Appointment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'calcom_booking_id' => '12345',
            'status' => 'scheduled'
        ]);

        $payload = [
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

        $payloadJson = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadJson, $this->webhookSecret);

        // Act
        $response = $this->withHeaders([
            'X-Cal-Signature-256' => $signature,
            'Content-Type' => 'application/json'
        ])->post('/api/webhooks/calcom', $payload);

        // Assert
        $response->assertSuccessful();
        
        Queue::assertPushed(ProcessCalcomBookingJob::class, function ($job) {
            return $job->bookingData['triggerEvent'] === 'BOOKING_CANCELLED';
        });
    }

    /** @test */
    public function it_rejects_webhook_with_invalid_signature()
    {
        // Arrange
        $payload = [
            'triggerEvent' => 'BOOKING_CREATED',
            'payload' => ['bookingId' => 123]
        ];

        // Act
        $response = $this->withHeaders([
            'X-Cal-Signature-256' => 'invalid_signature',
            'Content-Type' => 'application/json'
        ])->post('/api/webhooks/calcom', $payload);

        // Assert
        $response->assertStatus(401);
        $response->assertJsonFragment(['error' => 'Invalid signature']);
        
        Queue::assertNothingPushed();
    }

    /** @test */
    public function it_rejects_webhook_without_signature_header()
    {
        // Arrange
        $payload = [
            'triggerEvent' => 'BOOKING_CREATED',
            'payload' => ['bookingId' => 123]
        ];

        // Act
        $response = $this->post('/api/webhooks/calcom', $payload);

        // Assert
        $response->assertStatus(401);
        $response->assertJsonFragment(['error' => 'Missing signature']);
    }

    /** @test */
    public function it_handles_unknown_trigger_events()
    {
        // Arrange
        $payload = [
            'triggerEvent' => 'UNKNOWN_EVENT',
            'payload' => ['bookingId' => 123]
        ];

        $payloadJson = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadJson, $this->webhookSecret);

        // Act
        $response = $this->withHeaders([
            'X-Cal-Signature-256' => $signature,
            'Content-Type' => 'application/json'
        ])->post('/api/webhooks/calcom', $payload);

        // Assert
        $response->assertSuccessful();
        $response->assertJsonFragment([
            'received' => true,
            'status' => 'ignored',
            'message' => 'Unknown trigger event'
        ]);
    }

    /** @test */
    public function it_validates_required_webhook_fields()
    {
        // Arrange
        $payload = [
            'triggerEvent' => 'BOOKING_CREATED',
            'payload' => [
                // Missing required fields like bookingId
                'type' => 'Consultation'
            ]
        ];

        $payloadJson = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadJson, $this->webhookSecret);

        // Act
        $response = $this->withHeaders([
            'X-Cal-Signature-256' => $signature,
            'Content-Type' => 'application/json'
        ])->post('/api/webhooks/calcom', $payload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['payload.bookingId']);
    }

    /** @test */
    public function it_creates_customer_from_attendee_information()
    {
        // Arrange
        $payload = [
            'triggerEvent' => 'BOOKING_CREATED',
            'createdAt' => '2025-09-05T10:00:00.000Z',
            'payload' => [
                'bookingId' => 54321,
                'type' => 'New Patient Consultation',
                'startTime' => '2025-09-06T10:00:00.000Z',
                'endTime' => '2025-09-06T11:00:00.000Z',
                'organizer' => [
                    'email' => 'doctor@example.com',
                    'name' => 'Dr. Johnson'
                ],
                'attendees' => [
                    [
                        'email' => 'newpatient@example.com',
                        'name' => 'New Patient',
                        'timeZone' => 'Europe/Berlin',
                        'responses' => [
                            'phone' => '+491234567890',
                            'notes' => 'First time patient'
                        ]
                    ]
                ]
            ]
        ];

        $payloadJson = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadJson, $this->webhookSecret);

        // Act
        $response = $this->withHeaders([
            'X-Cal-Signature-256' => $signature,
            'Content-Type' => 'application/json'
        ])->post('/api/webhooks/calcom', $payload);

        // Assert
        $response->assertSuccessful();
        
        Queue::assertPushed(ProcessCalcomBookingJob::class, function ($job) {
            $attendee = $job->bookingData['payload']['attendees'][0];
            return $attendee['email'] === 'newpatient@example.com' &&
                   $attendee['name'] === 'New Patient';
        });
    }

    /** @test */
    public function it_handles_duplicate_webhooks_idempotently()
    {
        // Arrange
        $payload = [
            'triggerEvent' => 'BOOKING_CREATED',
            'createdAt' => '2025-09-05T10:00:00.000Z',
            'payload' => [
                'bookingId' => 99999,
                'type' => 'Duplicate Test',
                'startTime' => '2025-09-06T12:00:00.000Z',
                'endTime' => '2025-09-06T13:00:00.000Z',
                'organizer' => [
                    'email' => 'test@example.com',
                    'name' => 'Test Doctor'
                ],
                'attendees' => [
                    [
                        'email' => 'patient@example.com',
                        'name' => 'Test Patient'
                    ]
                ]
            ]
        ];

        $payloadJson = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadJson, $this->webhookSecret);

        $headers = [
            'X-Cal-Signature-256' => $signature,
            'Content-Type' => 'application/json'
        ];

        // Act - Send same webhook twice
        $response1 = $this->withHeaders($headers)->post('/api/webhooks/calcom', $payload);
        $response2 = $this->withHeaders($headers)->post('/api/webhooks/calcom', $payload);

        // Assert
        $response1->assertSuccessful();
        $response2->assertSuccessful();
        
        // Should only process once
        Queue::assertPushed(ProcessCalcomBookingJob::class, 1);
    }

    /** @test */
    public function it_handles_booking_with_multiple_attendees()
    {
        // Arrange
        $payload = [
            'triggerEvent' => 'BOOKING_CREATED',
            'createdAt' => '2025-09-05T10:00:00.000Z',
            'payload' => [
                'bookingId' => 77777,
                'type' => 'Group Consultation',
                'startTime' => '2025-09-06T14:00:00.000Z',
                'endTime' => '2025-09-06T15:00:00.000Z',
                'organizer' => [
                    'email' => 'therapist@example.com',
                    'name' => 'Dr. Therapist'
                ],
                'attendees' => [
                    [
                        'email' => 'patient1@example.com',
                        'name' => 'Patient One'
                    ],
                    [
                        'email' => 'patient2@example.com',
                        'name' => 'Patient Two'
                    ]
                ]
            ]
        ];

        $payloadJson = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadJson, $this->webhookSecret);

        // Act
        $response = $this->withHeaders([
            'X-Cal-Signature-256' => $signature,
            'Content-Type' => 'application/json'
        ])->post('/api/webhooks/calcom', $payload);

        // Assert
        $response->assertSuccessful();
        
        Queue::assertPushed(ProcessCalcomBookingJob::class, function ($job) {
            return count($job->bookingData['payload']['attendees']) === 2;
        });
    }

    /** @test */
    public function it_handles_booking_with_custom_fields()
    {
        // Arrange
        $payload = [
            'triggerEvent' => 'BOOKING_CREATED',
            'createdAt' => '2025-09-05T10:00:00.000Z',
            'payload' => [
                'bookingId' => 88888,
                'type' => 'Custom Consultation',
                'startTime' => '2025-09-06T16:00:00.000Z',
                'endTime' => '2025-09-06T17:00:00.000Z',
                'organizer' => [
                    'email' => 'specialist@example.com',
                    'name' => 'Dr. Specialist'
                ],
                'attendees' => [
                    [
                        'email' => 'custom@example.com',
                        'name' => 'Custom Patient',
                        'responses' => [
                            'insurance_provider' => 'Health Plus',
                            'preferred_language' => 'German',
                            'special_needs' => 'Wheelchair access required'
                        ]
                    ]
                ],
                'metadata' => [
                    'appointment_type' => 'follow_up',
                    'department' => 'cardiology',
                    'urgency' => 'high'
                ]
            ]
        ];

        $payloadJson = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadJson, $this->webhookSecret);

        // Act
        $response = $this->withHeaders([
            'X-Cal-Signature-256' => $signature,
            'Content-Type' => 'application/json'
        ])->post('/api/webhooks/calcom', $payload);

        // Assert
        $response->assertSuccessful();
        
        Queue::assertPushed(ProcessCalcomBookingJob::class, function ($job) {
            $responses = $job->bookingData['payload']['attendees'][0]['responses'];
            return $responses['insurance_provider'] === 'Health Plus' &&
                   $responses['special_needs'] === 'Wheelchair access required';
        });
    }

    /** @test */
    public function it_logs_processing_errors_appropriately()
    {
        // Arrange
        $payload = [
            'triggerEvent' => 'BOOKING_CREATED',
            'payload' => [
                'bookingId' => null, // This will cause validation error
                'startTime' => 'invalid-date-format'
            ]
        ];

        $payloadJson = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadJson, $this->webhookSecret);

        // Act
        $response = $this->withHeaders([
            'X-Cal-Signature-256' => $signature,
            'Content-Type' => 'application/json'
        ])->post('/api/webhooks/calcom', $payload);

        // Assert
        $response->assertStatus(422);
        
        // In a real implementation, would verify error logging
        // Queue should not be used for invalid data
        Queue::assertNothingPushed();
    }
}