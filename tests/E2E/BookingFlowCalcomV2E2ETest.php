<?php

namespace Tests\E2E;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\CalcomEventType;
use App\Jobs\ProcessRetellCallEndedJob;
use App\Jobs\RefreshCallDataJob;
use App\Jobs\SyncCalcomBookingsJob;
use App\Mail\AppointmentConfirmation;
use App\Services\AppointmentBookingService;
use App\Services\CalcomV2Service;
use App\Services\Calcom\CalcomV2Client;
use App\Services\Calcom\DTOs\SlotDTO;
use App\Services\Calcom\DTOs\BookingDTO;
use App\Services\Calcom\DTOs\AttendeeDTO;
use App\Services\Calcom\DTOs\EventTypeDTO;
use App\Services\Calcom\Exceptions\CalcomApiException;
use App\Services\Calcom\Exceptions\CalcomRateLimitException;
use App\Services\Calcom\Exceptions\CalcomValidationException;
use App\Events\AppointmentCreated;
use Carbon\Carbon;
use Mockery;

class BookingFlowCalcomV2E2ETest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected Staff $staff;
    protected Service $service;
    protected CalcomEventType $eventType;
    protected $mockCalcomClient;
    protected $appointmentDate;

    protected function setUp(): void
    {
        parent::setUp();

        // Set test date to next Monday at 10:00
        $this->appointmentDate = Carbon::now()->next('Monday')->setTime(10, 0);
        Carbon::setTestNow(Carbon::now()->startOfWeek());

        // Setup test environment
        $this->setupTestData();
        $this->setupMockServices();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Mockery::close();
        parent::tearDown();
    }

    protected function setupTestData(): void
    {
        // Create company with realistic data
        $this->company = Company::factory()->create([
            'name' => 'Berlin Dental Care',
            'calcom_api_key' => 'cal_live_1234567890abcdef',
            'calcom_team_slug' => 'berlin-dental',
            'retell_api_key' => 'key_retell_xyz123',
            'retell_agent_id' => 'agent_dental_001',
            'settings' => [
                'booking_buffer_minutes' => 15,
                'max_advance_booking_days' => 90,
                'auto_confirm_appointments' => true,
                'send_sms_reminders' => true,
                'reminder_hours_before' => 24,
            ],
        ]);

        // Create main branch
        $this->branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Hauptpraxis Mitte',
            'phone' => '+493012345678',
            'email' => 'mitte@berlindental.de',
            'address' => 'Friedrichstraße 123, 10117 Berlin',
            'settings' => [
                'working_hours' => [
                    'monday' => ['start' => '08:00', 'end' => '18:00'],
                    'tuesday' => ['start' => '08:00', 'end' => '18:00'],
                    'wednesday' => ['start' => '08:00', 'end' => '18:00'],
                    'thursday' => ['start' => '08:00', 'end' => '20:00'],
                    'friday' => ['start' => '08:00', 'end' => '16:00'],
                    'saturday' => ['start' => '09:00', 'end' => '13:00'],
                    'sunday' => null,
                ],
            ],
        ]);

        // Create dentist
        $this->staff = Staff::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Dr. Sarah Schmidt',
            'email' => 'dr.schmidt@berlindental.de',
            'phone' => '+493012345679',
            'calcom_user_id' => 42,
            'is_active' => true,
            'settings' => [
                'lunch_break' => ['start' => '12:00', 'end' => '13:00'],
                'buffer_between_appointments' => 10,
            ],
        ]);

        // Create service
        $this->service = Service::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Kontrolluntersuchung',
            'description' => 'Umfassende zahnärztliche Kontrolluntersuchung inkl. Beratung',
            'duration' => 30,
            'price' => 89.00,
            'buffer_time' => 10,
            'is_active' => true,
        ]);

        // Create Cal.com event type
        $this->eventType = CalcomEventType::factory()->create([
            'company_id' => $this->company->id,
            'calcom_id' => 12345,
            'title' => 'Kontrolluntersuchung - 30 Min',
            'slug' => 'kontrolluntersuchung-30',
            'description' => 'Umfassende zahnärztliche Kontrolluntersuchung',
            'length' => 30,
            'locations' => [
                ['type' => 'inPerson', 'address' => 'Friedrichstraße 123, 10117 Berlin']
            ],
            'metadata' => [
                'service_id' => $this->service->id,
                'requires_confirmation' => false,
            ],
        ]);

        // Link relationships
        $this->staff->services()->attach($this->service, [
            'is_available' => true,
            'custom_price' => null,
            'custom_duration' => null,
        ]);

        $this->staff->eventTypes()->attach($this->eventType, [
            'is_primary' => true,
            'custom_availability' => null,
        ]);
    }

    protected function setupMockServices(): void
    {
        // Mock CalcomV2Client
        $this->mockCalcomClient = Mockery::mock(CalcomV2Client::class);
        $this->app->instance(CalcomV2Client::class, $this->mockCalcomClient);
    }

    /** @test */
    public function complete_booking_flow_from_retell_webhook_to_confirmation_email()
    {
        Event::fake([AppointmentCreated::class]);
        Mail::fake();
        Queue::fake([RefreshCallDataJob::class, SyncCalcomBookingsJob::class]);

        // Step 1: Setup Cal.com availability mock
        $availableSlots = [
            new SlotDTO([
                'time' => $this->appointmentDate->toIso8601String(),
                'duration' => 30,
                'workingHours' => [
                    'start' => $this->appointmentDate->copy()->setTime(8, 0)->toIso8601String(),
                    'end' => $this->appointmentDate->copy()->setTime(18, 0)->toIso8601String(),
                ],
            ]),
            new SlotDTO([
                'time' => $this->appointmentDate->copy()->addMinutes(30)->toIso8601String(),
                'duration' => 30,
                'workingHours' => [
                    'start' => $this->appointmentDate->copy()->setTime(8, 0)->toIso8601String(),
                    'end' => $this->appointmentDate->copy()->setTime(18, 0)->toIso8601String(),
                ],
            ]),
        ];

        $this->mockCalcomClient
            ->shouldReceive('getAvailableSlots')
            ->with(
                Mockery::on(function ($eventTypeId) {
                    return $eventTypeId == 12345;
                }),
                Mockery::on(function ($startDate) {
                    return $startDate instanceof Carbon;
                }),
                Mockery::on(function ($endDate) {
                    return $endDate instanceof Carbon;
                }),
                Mockery::any() // timezone
            )
            ->andReturn($availableSlots);

        // Step 2: Setup Cal.com booking creation mock
        $mockBooking = new BookingDTO([
            'id' => 98765,
            'uid' => 'book_abc123def456',
            'title' => 'Kontrolluntersuchung mit Max Mustermann',
            'status' => 'accepted',
            'startTime' => $this->appointmentDate->toIso8601String(),
            'endTime' => $this->appointmentDate->copy()->addMinutes(30)->toIso8601String(),
            'attendees' => [
                new AttendeeDTO([
                    'id' => 1,
                    'email' => 'max.mustermann@email.de',
                    'name' => 'Max Mustermann',
                    'timeZone' => 'Europe/Berlin',
                    'locale' => 'de',
                ]),
            ],
            'user' => [
                'id' => 42,
                'email' => 'dr.schmidt@berlindental.de',
                'name' => 'Dr. Sarah Schmidt',
                'timeZone' => 'Europe/Berlin',
            ],
            'payment' => [],
            'metadata' => [
                'appointment_id' => null, // Will be set after creation
            ],
            'responses' => [
                'phone' => '+4930987654321',
                'notes' => 'Erstpatient, kommt auf Empfehlung',
            ],
        ]);

        $this->mockCalcomClient
            ->shouldReceive('createBooking')
            ->once()
            ->andReturn($mockBooking);

        // Step 3: Simulate Retell webhook
        $webhookPayload = [
            'event' => 'call_ended',
            'data' => [
                'call_id' => 'call_flow_test_123',
                'agent_id' => 'agent_dental_001',
                'from_number' => '+4930987654321',
                'to_number' => '+493012345678',
                'direction' => 'inbound',
                'status' => 'ended',
                'recording_url' => 'https://api.retell.ai/recordings/call_flow_test_123.mp3',
                'transcript' => $this->getRealisticTranscript(),
                'transcript_object' => $this->getTranscriptObject(),
                'summary' => 'Kunde Max Mustermann möchte einen Termin für eine Kontrolluntersuchung am nächsten Montag um 10:00 Uhr vereinbaren.',
                'duration_ms' => 185000,
                'call_analysis' => [
                    'appointment_scheduled' => true,
                    'customer_name' => 'Max Mustermann',
                    'customer_phone' => '+4930987654321',
                    'customer_email' => 'max.mustermann@email.de',
                    'service_requested' => 'Kontrolluntersuchung',
                    'preferred_date' => $this->appointmentDate->toDateString(),
                    'preferred_time' => '10:00',
                    'is_new_patient' => true,
                    'notes' => 'Erstpatient, kommt auf Empfehlung',
                ],
                'metadata' => [
                    'company_id' => $this->company->id,
                    'branch_id' => $this->branch->id,
                ],
            ],
        ];

        // Generate valid signature
        $signature = $this->generateRetellSignature($webhookPayload);

        // Step 4: Send webhook request
        $response = $this->postJson('/api/retell/webhook', $webhookPayload, [
            'X-Retell-Signature' => $signature,
        ]);

        $response->assertStatus(204);

        // Step 5: Verify call record was created
        $this->assertDatabaseHas('calls', [
            'retell_call_id' => 'call_flow_test_123',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'phone_number' => '+4930987654321',
            'direction' => 'inbound',
            'status' => 'ended',
            'duration' => 185,
            'summary' => 'Kunde Max Mustermann möchte einen Termin für eine Kontrolluntersuchung am nächsten Montag um 10:00 Uhr vereinbaren.',
        ]);

        $call = Call::where('retell_call_id', 'call_flow_test_123')->first();
        $this->assertNotNull($call);

        // Step 6: Process the webhook job
        Queue::assertPushed(ProcessRetellCallEndedJob::class, function ($job) use ($call) {
            return $job->webhookData['data']['call_id'] === 'call_flow_test_123';
        });

        // Manually execute the job
        $job = new ProcessRetellCallEndedJob($webhookPayload['data']);
        $job->handle(
            app(AppointmentBookingService::class),
            app(CalcomV2Service::class)
        );

        // Step 7: Verify customer was created/found
        $this->assertDatabaseHas('customers', [
            'company_id' => $this->company->id,
            'name' => 'Max Mustermann',
            'phone' => '+4930987654321',
            'email' => 'max.mustermann@email.de',
            'is_active' => true,
            'source' => 'phone_call',
        ]);

        $customer = Customer::where('phone', '+4930987654321')->first();
        $this->assertNotNull($customer);
        $this->assertNotNull($customer->first_contact_date);

        // Step 8: Verify appointment was created
        $this->assertDatabaseHas('appointments', [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'status' => 'scheduled',
            'duration' => 30,
            'price' => 89.00,
            'calcom_booking_id' => 98765,
            'calcom_uid' => 'book_abc123def456',
            'source' => 'phone_ai',
        ]);

        $appointment = Appointment::where('customer_id', $customer->id)->first();
        $this->assertNotNull($appointment);
        $this->assertEquals(
            $this->appointmentDate->format('Y-m-d H:i:s'),
            $appointment->start_time->format('Y-m-d H:i:s')
        );
        $this->assertEquals(
            $this->appointmentDate->copy()->addMinutes(30)->format('Y-m-d H:i:s'),
            $appointment->end_time->format('Y-m-d H:i:s')
        );

        // Step 9: Verify call was updated with appointment
        $call->refresh();
        $this->assertEquals($appointment->id, $call->appointment_id);
        $this->assertEquals($customer->id, $call->customer_id);
        $this->assertEquals('completed', $call->status);

        // Step 10: Verify appointment confirmation email
        Mail::assertQueued(AppointmentConfirmation::class, function ($mail) use ($customer, $appointment) {
            return $mail->hasTo($customer->email) &&
                   $mail->appointment->id === $appointment->id &&
                   $mail->subject === 'Terminbestätigung - Berlin Dental Care';
        });

        // Step 11: Verify event was fired
        Event::assertDispatched(AppointmentCreated::class, function ($event) use ($appointment) {
            return $event->appointment->id === $appointment->id;
        });

        // Step 12: Verify activity log
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Appointment::class,
            'subject_id' => $appointment->id,
            'description' => 'created',
            'causer_type' => Call::class,
            'causer_id' => $call->id,
            'properties' => json_encode([
                'source' => 'phone_ai',
                'call_id' => $call->id,
                'customer_name' => 'Max Mustermann',
            ]),
        ]);

        // Step 13: Verify cache was updated
        $cacheKey = "appointments:company:{$this->company->id}:date:{$this->appointmentDate->format('Y-m-d')}";
        $this->assertFalse(Cache::has($cacheKey)); // Should be invalidated

        // Step 14: Verify metrics
        $this->assertDatabaseHas('metrics', [
            'company_id' => $this->company->id,
            'metric_type' => 'appointment_created',
            'value' => 1,
            'dimensions' => json_encode([
                'source' => 'phone_ai',
                'service_id' => $this->service->id,
                'staff_id' => $this->staff->id,
            ]),
        ]);
    }

    /** @test */
    public function handles_no_availability_scenario_gracefully()
    {
        Event::fake();
        Mail::fake();
        Log::fake();

        // Mock no available slots
        $this->mockCalcomClient
            ->shouldReceive('getAvailableSlots')
            ->andReturn([]);

        $webhookPayload = [
            'event' => 'call_ended',
            'data' => [
                'call_id' => 'no_availability_123',
                'agent_id' => 'agent_dental_001',
                'from_number' => '+4930111222333',
                'to_number' => '+493012345678',
                'direction' => 'inbound',
                'status' => 'ended',
                'call_analysis' => [
                    'appointment_scheduled' => false,
                    'customer_name' => 'Hans Mueller',
                    'customer_phone' => '+4930111222333',
                    'service_requested' => 'Kontrolluntersuchung',
                    'preferred_date' => $this->appointmentDate->toDateString(),
                    'preferred_time' => '10:00',
                    'no_availability' => true,
                ],
                'metadata' => [
                    'company_id' => $this->company->id,
                    'branch_id' => $this->branch->id,
                ],
            ],
        ];

        $signature = $this->generateRetellSignature($webhookPayload);

        $response = $this->postJson('/api/retell/webhook', $webhookPayload, [
            'X-Retell-Signature' => $signature,
        ]);

        $response->assertStatus(204);

        // Process the job
        $job = new ProcessRetellCallEndedJob($webhookPayload['data']);
        
        try {
            $job->handle(
                app(AppointmentBookingService::class),
                app(CalcomV2Service::class)
            );
        } catch (\Exception $e) {
            // Expected behavior - no availability
        }

        // Verify no appointment was created
        $this->assertDatabaseMissing('appointments', [
            'company_id' => $this->company->id,
        ]);

        // Verify call status
        $call = Call::where('retell_call_id', 'no_availability_123')->first();
        $this->assertNotNull($call);
        $this->assertEquals('no_availability', $call->status);
        $this->assertNull($call->appointment_id);

        // Verify customer was still created for follow-up
        $this->assertDatabaseHas('customers', [
            'company_id' => $this->company->id,
            'name' => 'Hans Mueller',
            'phone' => '+4930111222333',
        ]);

        // Verify appropriate logging
        Log::assertLogged('warning', function ($message, $context) {
            return str_contains($message, 'No available slots found');
        });
    }

    /** @test */
    public function handles_existing_customer_with_appointment_history()
    {
        Event::fake();
        Mail::fake();

        // Create existing customer with appointment history
        $existingCustomer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Maria Schmidt',
            'phone' => '+4930777888999',
            'email' => 'maria.schmidt@email.de',
            'source' => 'website',
            'tags' => ['vip', 'regular'],
            'preferences' => [
                'preferred_staff' => $this->staff->id,
                'preferred_time' => 'morning',
                'reminder_preference' => 'email_and_sms',
            ],
        ]);

        // Create past appointments
        $pastAppointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $existingCustomer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'start_time' => Carbon::now()->subMonths(3),
            'end_time' => Carbon::now()->subMonths(3)->addMinutes(30),
            'status' => 'completed',
        ]);

        // Mock Cal.com responses
        $this->mockCalcomClient
            ->shouldReceive('getAvailableSlots')
            ->andReturn([
                new SlotDTO([
                    'time' => $this->appointmentDate->toIso8601String(),
                    'duration' => 30,
                ]),
            ]);

        $this->mockCalcomClient
            ->shouldReceive('createBooking')
            ->andReturn(new BookingDTO([
                'id' => 98766,
                'uid' => 'book_existing_customer',
                'title' => 'Kontrolluntersuchung mit Maria Schmidt',
                'startTime' => $this->appointmentDate->toIso8601String(),
                'endTime' => $this->appointmentDate->copy()->addMinutes(30)->toIso8601String(),
                'attendees' => [
                    new AttendeeDTO([
                        'email' => 'maria.schmidt@email.de',
                        'name' => 'Maria Schmidt',
                    ]),
                ],
            ]));

        $webhookPayload = [
            'event' => 'call_ended',
            'data' => [
                'call_id' => 'existing_customer_123',
                'from_number' => '+4930777888999',
                'call_analysis' => [
                    'appointment_scheduled' => true,
                    'customer_phone' => '+4930777888999',
                    'service_requested' => 'Kontrolluntersuchung',
                    'preferred_date' => $this->appointmentDate->toDateString(),
                    'preferred_time' => '10:00',
                ],
                'metadata' => [
                    'company_id' => $this->company->id,
                    'branch_id' => $this->branch->id,
                ],
            ],
        ];

        $signature = $this->generateRetellSignature($webhookPayload);

        $response = $this->postJson('/api/retell/webhook', $webhookPayload, [
            'X-Retell-Signature' => $signature,
        ]);

        $response->assertStatus(204);

        // Process the job
        $job = new ProcessRetellCallEndedJob($webhookPayload['data']);
        $job->handle(
            app(AppointmentBookingService::class),
            app(CalcomV2Service::class)
        );

        // Verify existing customer was used (not duplicated)
        $this->assertEquals(1, Customer::where('phone', '+4930777888999')->count());

        // Verify appointment was created for existing customer
        $newAppointment = Appointment::where('customer_id', $existingCustomer->id)
            ->where('id', '!=', $pastAppointment->id)
            ->first();

        $this->assertNotNull($newAppointment);
        $this->assertEquals($existingCustomer->id, $newAppointment->customer_id);
        $this->assertEquals('scheduled', $newAppointment->status);

        // Verify customer stats were updated
        $existingCustomer->refresh();
        $this->assertEquals(2, $existingCustomer->total_appointments);
        $this->assertNotNull($existingCustomer->last_appointment_date);
    }

    /** @test */
    public function handles_calcom_api_errors_with_retry_logic()
    {
        Event::fake();
        Mail::fake();
        Log::fake();

        // First call fails with rate limit
        $this->mockCalcomClient
            ->shouldReceive('getAvailableSlots')
            ->once()
            ->andThrow(new CalcomRateLimitException('Rate limit exceeded', 429));

        // Second call succeeds after retry
        $this->mockCalcomClient
            ->shouldReceive('getAvailableSlots')
            ->once()
            ->andReturn([
                new SlotDTO([
                    'time' => $this->appointmentDate->toIso8601String(),
                    'duration' => 30,
                ]),
            ]);

        // Booking succeeds
        $this->mockCalcomClient
            ->shouldReceive('createBooking')
            ->once()
            ->andReturn(new BookingDTO([
                'id' => 98767,
                'uid' => 'book_retry_success',
                'startTime' => $this->appointmentDate->toIso8601String(),
                'endTime' => $this->appointmentDate->copy()->addMinutes(30)->toIso8601String(),
                'attendees' => [
                    new AttendeeDTO([
                        'email' => 'retry.customer@email.de',
                        'name' => 'Retry Customer',
                    ]),
                ],
            ]));

        $webhookPayload = [
            'event' => 'call_ended',
            'data' => [
                'call_id' => 'retry_test_123',
                'from_number' => '+4930444555666',
                'call_analysis' => [
                    'appointment_scheduled' => true,
                    'customer_name' => 'Retry Customer',
                    'customer_phone' => '+4930444555666',
                    'customer_email' => 'retry.customer@email.de',
                    'service_requested' => 'Kontrolluntersuchung',
                    'preferred_date' => $this->appointmentDate->toDateString(),
                    'preferred_time' => '10:00',
                ],
                'metadata' => [
                    'company_id' => $this->company->id,
                    'branch_id' => $this->branch->id,
                ],
            ],
        ];

        $signature = $this->generateRetellSignature($webhookPayload);

        $response = $this->postJson('/api/retell/webhook', $webhookPayload, [
            'X-Retell-Signature' => $signature,
        ]);

        $response->assertStatus(204);

        // Process with retry logic
        $job = new ProcessRetellCallEndedJob($webhookPayload['data']);
        $job->handle(
            app(AppointmentBookingService::class),
            app(CalcomV2Service::class)
        );

        // Verify appointment was created despite initial failure
        $this->assertDatabaseHas('appointments', [
            'company_id' => $this->company->id,
            'calcom_booking_id' => 98767,
            'status' => 'scheduled',
        ]);

        // Verify retry was logged
        Log::assertLogged('warning', function ($message, $context) {
            return str_contains($message, 'Rate limit exceeded');
        });

        Log::assertLogged('info', function ($message, $context) {
            return str_contains($message, 'Retry successful');
        });
    }

    /** @test */
    public function handles_concurrent_booking_attempts_safely()
    {
        Event::fake();
        Mail::fake();

        // Setup mock for concurrent scenario
        $this->mockCalcomClient
            ->shouldReceive('getAvailableSlots')
            ->andReturn([
                new SlotDTO([
                    'time' => $this->appointmentDate->toIso8601String(),
                    'duration' => 30,
                ]),
            ]);

        // First booking succeeds
        $this->mockCalcomClient
            ->shouldReceive('createBooking')
            ->once()
            ->andReturn(new BookingDTO([
                'id' => 98768,
                'uid' => 'book_concurrent_1',
                'startTime' => $this->appointmentDate->toIso8601String(),
                'endTime' => $this->appointmentDate->copy()->addMinutes(30)->toIso8601String(),
                'attendees' => [
                    new AttendeeDTO([
                        'email' => 'customer1@email.de',
                        'name' => 'Customer One',
                    ]),
                ],
            ]));

        // Second booking fails due to slot taken
        $this->mockCalcomClient
            ->shouldReceive('createBooking')
            ->once()
            ->andThrow(new CalcomValidationException('This time slot is no longer available', 422));

        // Simulate two concurrent webhook calls
        $webhookPayload1 = [
            'event' => 'call_ended',
            'data' => [
                'call_id' => 'concurrent_1',
                'from_number' => '+4930111111111',
                'call_analysis' => [
                    'appointment_scheduled' => true,
                    'customer_name' => 'Customer One',
                    'customer_phone' => '+4930111111111',
                    'customer_email' => 'customer1@email.de',
                    'service_requested' => 'Kontrolluntersuchung',
                    'preferred_date' => $this->appointmentDate->toDateString(),
                    'preferred_time' => '10:00',
                ],
                'metadata' => [
                    'company_id' => $this->company->id,
                    'branch_id' => $this->branch->id,
                ],
            ],
        ];

        $webhookPayload2 = [
            'event' => 'call_ended',
            'data' => [
                'call_id' => 'concurrent_2',
                'from_number' => '+4930222222222',
                'call_analysis' => [
                    'appointment_scheduled' => true,
                    'customer_name' => 'Customer Two',
                    'customer_phone' => '+4930222222222',
                    'customer_email' => 'customer2@email.de',
                    'service_requested' => 'Kontrolluntersuchung',
                    'preferred_date' => $this->appointmentDate->toDateString(),
                    'preferred_time' => '10:00',
                ],
                'metadata' => [
                    'company_id' => $this->company->id,
                    'branch_id' => $this->branch->id,
                ],
            ],
        ];

        // Send both webhooks
        $signature1 = $this->generateRetellSignature($webhookPayload1);
        $signature2 = $this->generateRetellSignature($webhookPayload2);

        $response1 = $this->postJson('/api/retell/webhook', $webhookPayload1, [
            'X-Retell-Signature' => $signature1,
        ]);
        $response2 = $this->postJson('/api/retell/webhook', $webhookPayload2, [
            'X-Retell-Signature' => $signature2,
        ]);

        $response1->assertStatus(204);
        $response2->assertStatus(204);

        // Process both jobs
        $job1 = new ProcessRetellCallEndedJob($webhookPayload1['data']);
        $job2 = new ProcessRetellCallEndedJob($webhookPayload2['data']);

        // First succeeds
        $job1->handle(
            app(AppointmentBookingService::class),
            app(CalcomV2Service::class)
        );

        // Second fails
        try {
            $job2->handle(
                app(AppointmentBookingService::class),
                app(CalcomV2Service::class)
            );
        } catch (\Exception $e) {
            // Expected
        }

        // Verify only one appointment was created
        $this->assertEquals(1, Appointment::where('company_id', $this->company->id)->count());

        // Verify the successful appointment
        $appointment = Appointment::first();
        $this->assertEquals('Customer One', $appointment->customer->name);
        $this->assertEquals(98768, $appointment->calcom_booking_id);

        // Verify second call marked as failed
        $call2 = Call::where('retell_call_id', 'concurrent_2')->first();
        $this->assertEquals('booking_conflict', $call2->status);
        $this->assertNull($call2->appointment_id);
    }

    /** @test */
    public function validates_and_handles_invalid_webhook_data()
    {
        Log::fake();

        // Test missing required fields
        $invalidPayloads = [
            // Missing call_id
            [
                'event' => 'call_ended',
                'data' => [
                    'from_number' => '+4930333333333',
                ],
            ],
            // Missing metadata
            [
                'event' => 'call_ended',
                'data' => [
                    'call_id' => 'invalid_1',
                    'from_number' => '+4930333333333',
                ],
            ],
            // Invalid phone number
            [
                'event' => 'call_ended',
                'data' => [
                    'call_id' => 'invalid_2',
                    'from_number' => 'not-a-phone-number',
                    'metadata' => [
                        'company_id' => $this->company->id,
                    ],
                ],
            ],
        ];

        foreach ($invalidPayloads as $payload) {
            $signature = $this->generateRetellSignature($payload);

            $response = $this->postJson('/api/retell/webhook', $payload, [
                'X-Retell-Signature' => $signature,
            ]);

            // Should still return 204 to prevent retries
            $response->assertStatus(204);

            // But should log the error
            Log::assertLogged('error', function ($message) {
                return str_contains($message, 'Invalid webhook data') ||
                       str_contains($message, 'Validation failed');
            });
        }

        // Verify no calls or appointments were created
        $this->assertEquals(0, Call::count());
        $this->assertEquals(0, Appointment::count());
    }

    /** @test */
    public function tracks_complete_appointment_lifecycle_with_proper_database_state()
    {
        Event::fake();
        Mail::fake();

        // Setup Cal.com mocks
        $this->mockCalcomClient
            ->shouldReceive('getAvailableSlots')
            ->andReturn([
                new SlotDTO([
                    'time' => $this->appointmentDate->toIso8601String(),
                    'duration' => 30,
                ]),
            ]);

        $this->mockCalcomClient
            ->shouldReceive('createBooking')
            ->andReturn(new BookingDTO([
                'id' => 98769,
                'uid' => 'book_lifecycle_test',
                'startTime' => $this->appointmentDate->toIso8601String(),
                'endTime' => $this->appointmentDate->copy()->addMinutes(30)->toIso8601String(),
                'attendees' => [
                    new AttendeeDTO([
                        'email' => 'lifecycle@email.de',
                        'name' => 'Lifecycle Test',
                    ]),
                ],
            ]));

        // Start transaction to verify rollback behavior
        DB::beginTransaction();

        try {
            $webhookPayload = [
                'event' => 'call_ended',
                'data' => [
                    'call_id' => 'lifecycle_test_123',
                    'from_number' => '+4930999888777',
                    'call_analysis' => [
                        'appointment_scheduled' => true,
                        'customer_name' => 'Lifecycle Test',
                        'customer_phone' => '+4930999888777',
                        'customer_email' => 'lifecycle@email.de',
                        'service_requested' => 'Kontrolluntersuchung',
                        'preferred_date' => $this->appointmentDate->toDateString(),
                        'preferred_time' => '10:00',
                    ],
                    'metadata' => [
                        'company_id' => $this->company->id,
                        'branch_id' => $this->branch->id,
                    ],
                ],
            ];

            $signature = $this->generateRetellSignature($webhookPayload);

            $response = $this->postJson('/api/retell/webhook', $webhookPayload, [
                'X-Retell-Signature' => $signature,
            ]);

            $response->assertStatus(204);

            // Process the job
            $job = new ProcessRetellCallEndedJob($webhookPayload['data']);
            $job->handle(
                app(AppointmentBookingService::class),
                app(CalcomV2Service::class)
            );

            // Verify all database states
            $call = Call::where('retell_call_id', 'lifecycle_test_123')->first();
            $customer = Customer::where('phone', '+4930999888777')->first();
            $appointment = Appointment::where('customer_id', $customer->id)->first();

            // Call state
            $this->assertNotNull($call);
            $this->assertEquals('completed', $call->status);
            $this->assertEquals($appointment->id, $call->appointment_id);
            $this->assertEquals($customer->id, $call->customer_id);

            // Customer state
            $this->assertNotNull($customer);
            $this->assertEquals('Lifecycle Test', $customer->name);
            $this->assertEquals('lifecycle@email.de', $customer->email);
            $this->assertEquals(1, $customer->total_appointments);

            // Appointment state
            $this->assertNotNull($appointment);
            $this->assertEquals('scheduled', $appointment->status);
            $this->assertEquals(98769, $appointment->calcom_booking_id);
            $this->assertEquals('book_lifecycle_test', $appointment->calcom_uid);
            $this->assertEquals($this->service->price, $appointment->price);
            $this->assertEquals($this->service->duration, $appointment->duration);

            // Verify relationships
            $this->assertTrue($appointment->customer->is($customer));
            $this->assertTrue($appointment->staff->is($this->staff));
            $this->assertTrue($appointment->service->is($this->service));
            $this->assertTrue($appointment->branch->is($this->branch));
            $this->assertTrue($appointment->company->is($this->company));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        // Final verification after commit
        $this->assertDatabaseHas('calls', ['retell_call_id' => 'lifecycle_test_123']);
        $this->assertDatabaseHas('customers', ['phone' => '+4930999888777']);
        $this->assertDatabaseHas('appointments', ['calcom_booking_id' => 98769]);
    }

    /**
     * Generate realistic transcript for testing
     */
    protected function getRealisticTranscript(): string
    {
        return "AI: Guten Tag, Berlin Dental Care, Sie sprechen mit der Terminvergabe. Wie kann ich Ihnen helfen?
Customer: Hallo, ich würde gerne einen Termin für eine Kontrolluntersuchung vereinbaren.
AI: Sehr gerne helfe ich Ihnen dabei. Darf ich zunächst Ihren Namen erfahren?
Customer: Ja, mein Name ist Max Mustermann.
AI: Vielen Dank, Herr Mustermann. Und unter welcher Telefonnummer sind Sie erreichbar?
Customer: Meine Nummer ist 030 98765 4321.
AI: Perfekt. Haben Sie eine E-Mail-Adresse für die Terminbestätigung?
Customer: Ja, max.mustermann@email.de
AI: Danke schön. Wann würde es Ihnen denn für die Kontrolluntersuchung passen?
Customer: Am liebsten nächsten Montag vormittags, wenn das möglich ist.
AI: Lassen Sie mich kurz nachschauen... Ja, wir hätten am Montag um 10:00 Uhr einen Termin bei Dr. Schmidt frei. Passt Ihnen das?
Customer: Ja, das passt perfekt.
AI: Wunderbar! Ich habe Ihnen den Termin für Montag, den " . $this->appointmentDate->format('d.m.Y') . " um 10:00 Uhr bei Dr. Schmidt reserviert. Die Kontrolluntersuchung dauert etwa 30 Minuten und kostet 89 Euro. Sie erhalten gleich eine Bestätigung per E-Mail an max.mustermann@email.de.
Customer: Vielen Dank!
AI: Sehr gerne! Bitte kommen Sie etwa 10 Minuten früher, falls Sie noch Formulare ausfüllen müssen. Unsere Praxis befindet sich in der Friedrichstraße 123 in Berlin-Mitte. Haben Sie noch Fragen?
Customer: Nein, alles klar. Vielen Dank!
AI: Perfekt! Dann sehen wir uns am Montag. Ich wünsche Ihnen noch einen schönen Tag, Herr Mustermann!
Customer: Danke, Ihnen auch. Auf Wiederhören!
AI: Auf Wiederhören!";
    }

    /**
     * Generate transcript object for testing
     */
    protected function getTranscriptObject(): array
    {
        return [
            [
                'role' => 'agent',
                'content' => 'Guten Tag, Berlin Dental Care, Sie sprechen mit der Terminvergabe. Wie kann ich Ihnen helfen?',
                'timestamp' => 0.5,
            ],
            [
                'role' => 'user',
                'content' => 'Hallo, ich würde gerne einen Termin für eine Kontrolluntersuchung vereinbaren.',
                'timestamp' => 5.2,
            ],
            [
                'role' => 'agent',
                'content' => 'Sehr gerne helfe ich Ihnen dabei. Darf ich zunächst Ihren Namen erfahren?',
                'timestamp' => 9.8,
            ],
            [
                'role' => 'user',
                'content' => 'Ja, mein Name ist Max Mustermann.',
                'timestamp' => 13.4,
            ],
            // ... additional entries
        ];
    }

    /**
     * Generate valid Retell signature
     */
    protected function generateRetellSignature(array $payload): string
    {
        $secret = config('services.retell.webhook_secret', 'test_webhook_secret');
        $timestamp = time();
        $body = json_encode($payload);
        
        $signatureBase = "{$timestamp}.{$body}";
        $signature = hash_hmac('sha256', $signatureBase, $secret);
        
        return "t={$timestamp},v1={$signature}";
    }
}