<?php

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Customer;
use App\Models\CalcomEventType;
use App\Services\RetellService;
use App\Services\CalcomService;
use App\Services\BookingService;
use App\Services\CustomerMatchingService;
use App\Jobs\ProcessRetellCallEndedJob;
use App\Jobs\SendAppointmentConfirmationJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

/**
 * Integration test for the complete phone-to-appointment flow
 * This tests the entire business process from call to confirmation
 */
class CompleteFlowTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    private Company $company;
    private Branch $branch;
    private Staff $staff;
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup fake queues and notifications
        Queue::fake();
        Mail::fake();
        Notification::fake();
        
        // Create test company with complete setup
        $this->setupTestCompany();
        
        // Mock external APIs
        $this->mockExternalServices();
    }

    /** @test */
    public function complete_phone_to_appointment_flow_works_end_to_end()
    {
        // Step 1: Incoming call webhook from Retell.ai
        $callWebhookPayload = [
            'event' => 'call_started',
            'call' => [
                'call_id' => 'retell_call_123',
                'from_number' => '+49123456789',
                'to_number' => $this->company->phone_number,
                'status' => 'in_progress',
            ],
        ];

        $response = $this->postJson('/api/retell/webhook', $callWebhookPayload, [
            'X-Retell-Signature' => $this->generateSignature($callWebhookPayload),
        ]);

        $response->assertOk();

        // Verify call record created
        $this->assertDatabaseHas('calls', [
            'external_id' => 'retell_call_123',
            'phone_number' => '+49123456789',
            'status' => 'in_progress',
        ]);

        // Step 2: Call ends with booking intent
        $callEndedPayload = [
            'event' => 'call_ended',
            'call' => [
                'call_id' => 'retell_call_123',
                'duration_ms' => 180000, // 3 minutes
                'status' => 'completed',
                'transcript' => 'Hallo, ich möchte gerne einen Termin für eine Beratung morgen um 14 Uhr buchen.',
                'recording_url' => 'https://recordings.retell.ai/call_123.mp3',
                'analysis' => [
                    'sentiment' => 'positive',
                    'intent' => 'booking',
                    'extracted_data' => [
                        'customer_name' => 'Max Mustermann',
                        'service_type' => 'Beratung',
                        'preferred_date' => '2024-01-16',
                        'preferred_time' => '14:00',
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/retell/webhook', $callEndedPayload, [
            'X-Retell-Signature' => $this->generateSignature($callEndedPayload),
        ]);

        $response->assertOk();

        // Verify job was dispatched
        Queue::assertPushed(ProcessRetellCallEndedJob::class, function ($job) {
            return $job->callId === 'retell_call_123';
        });

        // Step 3: Process the job (simulate queue worker)
        $call = \App\Models\Call::where('external_id', 'retell_call_123')->first();
        $job = new ProcessRetellCallEndedJob($call);
        $job->handle(
            new RetellService(),
            new CustomerMatchingService(),
            new BookingService(),
            new CalcomService()
        );

        // Step 4: Verify customer was created/matched
        $customer = Customer::where('phone_number', '+49123456789')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('Max Mustermann', $customer->name);

        // Step 5: Verify appointment was created
        $appointment = $customer->appointments()->first();
        $this->assertNotNull($appointment);
        $this->assertEquals('scheduled', $appointment->status);
        $this->assertEquals($this->service->id, $appointment->service_id);
        $this->assertEquals('2024-01-16 14:00:00', $appointment->appointment_datetime);

        // Step 6: Verify Cal.com integration
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.cal.com/v2/bookings' &&
                   $request['eventTypeId'] === $this->service->calcom_event_type_id &&
                   $request['start'] === '2024-01-16T14:00:00+01:00';
        });

        // Step 7: Verify confirmation email was queued
        Queue::assertPushed(SendAppointmentConfirmationJob::class, function ($job) use ($appointment) {
            return $job->appointmentId === $appointment->id;
        });

        // Step 8: Process email job
        $emailJob = new SendAppointmentConfirmationJob($appointment);
        $emailJob->handle();

        // Step 9: Verify email was sent
        Mail::assertSent(\App\Mail\AppointmentConfirmationMail::class, function ($mail) use ($customer) {
            return $mail->hasTo($customer->email) &&
                   $mail->hasSubject('Terminbestätigung');
        });

        // Step 10: Verify all data integrity
        $call->refresh();
        $this->assertEquals('completed', $call->status);
        $this->assertEquals(180, $call->duration);
        $this->assertNotNull($call->customer_id);
        $this->assertEquals($customer->id, $call->customer_id);
        $this->assertNotNull($call->metadata['booking_created']);
        $this->assertEquals($appointment->id, $call->metadata['appointment_id']);
    }

    /** @test */
    public function handles_booking_failure_gracefully()
    {
        // Mock Cal.com to fail
        Http::fake([
            'api.cal.com/*' => Http::response(['error' => 'No availability'], 400),
        ]);

        $callPayload = [
            'event' => 'call_ended',
            'call' => [
                'call_id' => 'retell_call_456',
                'duration_ms' => 120000,
                'status' => 'completed',
                'phone_number' => '+49987654321',
                'analysis' => [
                    'intent' => 'booking',
                    'extracted_data' => [
                        'customer_name' => 'Jane Doe',
                        'service_type' => 'Beratung',
                        'preferred_date' => '2024-01-16',
                        'preferred_time' => '14:00',
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/retell/webhook', $callPayload, [
            'X-Retell-Signature' => $this->generateSignature($callPayload),
        ]);

        $response->assertOk();

        // Process the job
        $call = \App\Models\Call::where('external_id', 'retell_call_456')->first();
        $job = new ProcessRetellCallEndedJob($call);
        
        try {
            $job->handle(
                new RetellService(),
                new CustomerMatchingService(),
                new BookingService(),
                new CalcomService()
            );
        } catch (\Exception $e) {
            // Expected to fail
        }

        // Verify appointment was not created
        $this->assertEquals(0, \App\Models\Appointment::count());

        // Verify error was logged
        $call->refresh();
        $this->assertArrayHasKey('booking_error', $call->metadata);
        $this->assertStringContainsString('No availability', $call->metadata['booking_error']);

        // Verify fallback notification was sent
        Notification::assertSentTo(
            [$this->company],
            \App\Notifications\BookingFailedNotification::class
        );
    }

    /** @test */
    public function respects_business_hours_and_availability()
    {
        // Set working hours for the branch
        $this->branch->workingHours()->create([
            'day_of_week' => 3, // Wednesday
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);

        // Create existing appointment to block a slot
        \App\Models\Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'staff_id' => $this->staff->id,
            'appointment_datetime' => '2024-01-17 14:00:00', // Wednesday 2pm
            'duration_minutes' => 60,
        ]);

        // Attempt to book same time slot
        $bookingService = new BookingService();
        $availability = $bookingService->checkAvailability([
            'branch_id' => $this->branch->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'date' => '2024-01-17',
            'time' => '14:00',
        ]);

        $this->assertFalse($availability['available']);
        $this->assertEquals('Time slot not available', $availability['reason']);
        
        // Check suggested alternatives
        $this->assertNotEmpty($availability['alternatives']);
        $this->assertContains('15:00', $availability['alternatives']); // Next available slot
    }

    /**
     * Helper method to set up test company with all required data
     */
    private function setupTestCompany(): void
    {
        $this->company = Company::factory()->create([
            'name' => 'Test Company GmbH',
            'phone_number' => '+49301234567',
            'retell_api_key' => 'test_retell_key',
            'calcom_api_key' => 'test_calcom_key',
        ]);

        $this->branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Hauptfiliale',
        ]);

        $this->staff = Staff::factory()->create([
            'branch_id' => $this->branch->id,
            'name' => 'Dr. Test',
            'email' => 'dr.test@example.com',
        ]);

        $this->service = Service::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Beratung',
            'duration_minutes' => 60,
            'price' => 80.00,
        ]);

        // Link service to Cal.com event type
        $eventType = CalcomEventType::factory()->create([
            'company_id' => $this->company->id,
            'external_id' => 'cal_event_123',
            'title' => 'Beratung',
            'length' => 60,
        ]);

        $this->service->update(['calcom_event_type_id' => $eventType->id]);

        // Assign staff to service
        $this->staff->services()->attach($this->service);
    }

    /**
     * Mock external service responses
     */
    private function mockExternalServices(): void
    {
        // Mock Retell.ai API
        Http::fake([
            'api.retellai.com/*' => Http::response([
                'success' => true,
                'data' => [
                    'call_id' => 'retell_call_123',
                    'status' => 'completed',
                ],
            ], 200),
            
            // Mock Cal.com API
            'api.cal.com/v2/bookings' => Http::response([
                'id' => 'cal_booking_123',
                'uid' => 'unique_booking_id',
                'title' => 'Beratung mit Max Mustermann',
                'startTime' => '2024-01-16T14:00:00+01:00',
                'endTime' => '2024-01-16T15:00:00+01:00',
                'status' => 'ACCEPTED',
            ], 201),
            
            'api.cal.com/v2/slots/*' => Http::response([
                'slots' => [
                    ['time' => '2024-01-16T14:00:00+01:00'],
                    ['time' => '2024-01-16T15:00:00+01:00'],
                    ['time' => '2024-01-16T16:00:00+01:00'],
                ],
            ], 200),
        ]);
    }

    /**
     * Generate webhook signature for testing
     */
    private function generateSignature(array $payload): string
    {
        $secret = config('services.retell.webhook_secret', 'test_secret');
        return hash_hmac('sha256', json_encode($payload), $secret);
    }
}