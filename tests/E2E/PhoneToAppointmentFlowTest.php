<?php

namespace Tests\E2E;

use App\Jobs\ProcessRetellCallJob;
use App\Jobs\RefreshCallDataJob;
use App\Mail\AppointmentConfirmation;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\CalcomEventType;
use App\Models\Call;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Services\CalcomV2Service;
use App\Services\RetellV2Service;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PhoneToAppointmentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected Staff $staff;
    protected Service $service;
    protected CalcomEventType $eventType;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup test data
        $this->setupTestEnvironment();
        
        // Configure fake HTTP responses
        $this->setupHttpFakes();
    }

    protected function setupTestEnvironment(): void
    {
        // Create company with API credentials
        $this->company = Company::factory()->create([
            'name' => 'Test Dental Clinic',
            'calcom_api_key' => 'test_calcom_key',
            'calcom_team_slug' => 'test-clinic',
            'retell_api_key' => 'test_retell_key',
            'retell_agent_id' => 'test_agent_123',
        ]);

        // Create branch
        $this->branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Main Office',
            'phone' => '+491234567890',
            'email' => 'main@testclinic.de',
            'address' => 'Test Street 123, Berlin',
        ]);

        // Create staff member
        $this->staff = Staff::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Dr. Schmidt',
            'email' => 'dr.schmidt@testclinic.de',
            'calcom_user_id' => 123,
        ]);

        // Create service
        $this->service = Service::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Routine Checkup',
            'duration' => 30,
            'price' => 75.00,
        ]);

        // Create Cal.com event type
        $this->eventType = CalcomEventType::factory()->create([
            'company_id' => $this->company->id,
            'calcom_id' => 456,
            'title' => 'Routine Checkup',
            'slug' => 'routine-checkup',
            'length' => 30,
        ]);

        // Link staff to service and event type
        $this->staff->services()->attach($this->service);
        $this->staff->eventTypes()->attach($this->eventType);
    }

    protected function setupHttpFakes(): void
    {
        // Mock Retell.ai API responses
        Http::fake([
            'api.retellai.com/v2/get-call/*' => Http::response([
                'call_id' => 'test_call_123',
                'status' => 'ended',
                'duration_seconds' => 180,
                'transcript' => 'AI: Good morning, Test Dental Clinic. How can I help you today?
Customer: Hi, I would like to schedule a routine checkup.
AI: Of course! I can help you with that. May I have your name please?
Customer: My name is Max Mustermann.
AI: Thank you, Mr. Mustermann. And what is your phone number?
Customer: +49 30 12345678
AI: Perfect. When would you like to come in for your checkup?
Customer: Next Monday at 10 AM would be great.
AI: Let me check our availability... Yes, Monday at 10 AM is available with Dr. Schmidt. 
AI: I have scheduled your routine checkup for Monday at 10 AM. You will receive a confirmation email shortly.
Customer: Thank you very much!
AI: You are welcome! We look forward to seeing you on Monday. Have a great day!',
                'variables' => [
                    'customer_name' => 'Max Mustermann',
                    'customer_phone' => '+493012345678',
                    'customer_email' => 'max.mustermann@email.de',
                    'service_requested' => 'routine checkup',
                    'appointment_date' => Carbon::now()->next('Monday')->setTime(10, 0)->toIso8601String(),
                    'staff_name' => 'Dr. Schmidt',
                ],
                'analysis' => [
                    'sentiment' => 'positive',
                    'appointment_requested' => true,
                    'appointment_confirmed' => true,
                ],
            ], 200),

            // Mock Cal.com availability check
            'api.cal.com/v2/slots/available' => Http::response([
                'data' => [
                    [
                        'time' => Carbon::now()->next('Monday')->setTime(10, 0)->toIso8601String(),
                        'attendees' => 0,
                        'duration' => 30,
                    ],
                ],
            ], 200),

            // Mock Cal.com booking creation
            'api.cal.com/v2/bookings' => Http::response([
                'data' => [
                    'id' => 789,
                    'uid' => 'booking_uid_123',
                    'title' => 'Routine Checkup with Max Mustermann',
                    'startTime' => Carbon::now()->next('Monday')->setTime(10, 0)->toIso8601String(),
                    'endTime' => Carbon::now()->next('Monday')->setTime(10, 30)->toIso8601String(),
                    'attendees' => [
                        [
                            'email' => 'max.mustermann@email.de',
                            'name' => 'Max Mustermann',
                        ],
                    ],
                    'user' => [
                        'id' => 123,
                        'name' => 'Dr. Schmidt',
                        'email' => 'dr.schmidt@testclinic.de',
                    ],
                ],
            ], 201),
        ]);
    }

    /** @test */

    #[Test]
    public function complete_phone_to_appointment_flow_works_correctly()
    {
        Event::fake();
        Mail::fake();
        Queue::fake();

        // Step 1: Simulate incoming call webhook from Retell.ai
        $webhookPayload = [
            'event' => 'call_ended',
            'call' => [
                'call_id' => 'test_call_123',
                'agent_id' => 'test_agent_123',
                'call_type' => 'inbound',
                'from_number' => '+493012345678',
                'to_number' => '+491234567890',
                'direction' => 'inbound',
                'call_status' => 'ended',
                'metadata' => [
                    'company_id' => $this->company->id,
                ],
            ],
        ];

        // Generate valid signature
        $signature = $this->generateRetellSignature($webhookPayload);

        $response = $this->postJson('/api/retell/webhook', $webhookPayload, [
            'X-Retell-Signature' => $signature,
        ]);

        $response->assertStatus(200);

        // Step 2: Verify call record was created
        $this->assertDatabaseHas('calls', [
            'retell_call_id' => 'test_call_123',
            'company_id' => $this->company->id,
            'phone_number' => '+493012345678',
            'direction' => 'inbound',
            'status' => 'ended',
        ]);

        $call = Call::where('retell_call_id', 'test_call_123')->first();
        $this->assertNotNull($call);

        // Step 3: Verify ProcessRetellCallJob was dispatched
        Queue::assertPushed(ProcessRetellCallJob::class, function ($job) use ($call) {
            return $job->call->id === $call->id;
        });

        // Step 4: Manually process the job to simulate queue processing
        Queue::fake(); // Reset queue fake to process manually
        
        $job = new ProcessRetellCallJob($call);
        $job->handle(app(RetellV2Service::class));

        // Step 5: Verify customer was created or found
        $this->assertDatabaseHas('customers', [
            'company_id' => $this->company->id,
            'name' => 'Max Mustermann',
            'phone' => '+493012345678',
            'email' => 'max.mustermann@email.de',
        ]);

        $customer = Customer::where('phone', '+493012345678')->first();
        $this->assertNotNull($customer);

        // Step 6: Verify appointment was created
        $this->assertDatabaseHas('appointments', [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'status' => 'scheduled',
        ]);

        $appointment = Appointment::where('customer_id', $customer->id)->first();
        $this->assertNotNull($appointment);
        $this->assertEquals(
            Carbon::now()->next('Monday')->setTime(10, 0)->format('Y-m-d H:i'),
            $appointment->start_time->format('Y-m-d H:i')
        );

        // Step 7: Verify Cal.com booking was linked
        $this->assertNotNull($appointment->calcom_booking_id);
        $this->assertEquals(789, $appointment->calcom_booking_id);
        $this->assertEquals('booking_uid_123', $appointment->calcom_uid);

        // Step 8: Verify call was updated with appointment info
        $call->refresh();
        $this->assertEquals($appointment->id, $call->appointment_id);
        $this->assertEquals($customer->id, $call->customer_id);
        $this->assertEquals('completed', $call->status);
        $this->assertNotNull($call->transcript);
        $this->assertStringContainsString('routine checkup', $call->transcript);

        // Step 9: Verify email was sent
        Mail::assertQueued(AppointmentConfirmation::class, function ($mail) use ($customer, $appointment) {
            return $mail->hasTo($customer->email) &&
                   $mail->appointment->id === $appointment->id;
        });

        // Step 10: Verify events were fired
        Event::assertDispatched('appointment.created', function ($event, $data) use ($appointment) {
            return $data[0]->id === $appointment->id;
        });

        Event::assertDispatched('customer.appointment.scheduled', function ($event, $data) use ($customer, $appointment) {
            return $data[0]->id === $customer->id &&
                   $data[1]->id === $appointment->id;
        });

        // Step 11: Test RefreshCallDataJob
        Queue::fake();
        
        RefreshCallDataJob::dispatch($call)->delay(now()->addMinutes(5));
        
        Queue::assertPushed(RefreshCallDataJob::class, function ($job) use ($call) {
            return $job->call->id === $call->id &&
                   $job->delay === 300; // 5 minutes in seconds
        });

        // Step 12: Verify activity log
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Appointment::class,
            'subject_id' => $appointment->id,
            'description' => 'created',
            'causer_type' => 'App\Models\System',
            'properties->source' => 'phone_call',
            'properties->call_id' => $call->id,
        ]);
    }

    /** @test */

    #[Test]
    public function handles_call_with_no_appointment_request()
    {
        Event::fake();
        Queue::fake();

        // Mock Retell response for informational call
        Http::fake([
            'api.retellai.com/v2/get-call/*' => Http::response([
                'call_id' => 'info_call_123',
                'status' => 'ended',
                'duration_seconds' => 60,
                'transcript' => 'AI: Good morning, Test Dental Clinic. How can I help you?
Customer: What are your opening hours?
AI: We are open Monday to Friday from 8 AM to 6 PM, and Saturday from 9 AM to 1 PM.
Customer: Thank you!
AI: You are welcome! Have a great day!',
                'variables' => [],
                'analysis' => [
                    'sentiment' => 'positive',
                    'appointment_requested' => false,
                    'information_only' => true,
                ],
            ], 200),
        ]);

        $webhookPayload = [
            'event' => 'call_ended',
            'call' => [
                'call_id' => 'info_call_123',
                'agent_id' => 'test_agent_123',
                'call_type' => 'inbound',
                'from_number' => '+493087654321',
                'to_number' => '+491234567890',
                'direction' => 'inbound',
                'call_status' => 'ended',
                'metadata' => [
                    'company_id' => $this->company->id,
                ],
            ],
        ];

        $signature = $this->generateRetellSignature($webhookPayload);

        $response = $this->postJson('/api/retell/webhook', $webhookPayload, [
            'X-Retell-Signature' => $signature,
        ]);

        $response->assertStatus(200);

        // Verify call was logged but no appointment created
        $this->assertDatabaseHas('calls', [
            'retell_call_id' => 'info_call_123',
            'status' => 'completed',
            'appointment_id' => null,
        ]);

        $this->assertDatabaseMissing('appointments', [
            'company_id' => $this->company->id,
        ]);

        // No appointment emails should be sent
        Mail::assertNothingQueued();
    }

    /** @test */

    #[Test]
    public function handles_failed_booking_gracefully()
    {
        Event::fake();
        Queue::fake();
        Log::fake();

        // Mock Cal.com booking failure
        Http::fake([
            'api.retellai.com/v2/get-call/*' => Http::response([
                'call_id' => 'failed_call_123',
                'status' => 'ended',
                'duration_seconds' => 120,
                'transcript' => 'Customer requested appointment',
                'variables' => [
                    'customer_name' => 'Failed Customer',
                    'customer_phone' => '+493011111111',
                    'appointment_date' => Carbon::now()->next('Monday')->setTime(10, 0)->toIso8601String(),
                ],
                'analysis' => [
                    'appointment_requested' => true,
                ],
            ], 200),
            
            'api.cal.com/v2/bookings' => Http::response([
                'error' => 'Time slot no longer available',
            ], 409),
        ]);

        $webhookPayload = [
            'event' => 'call_ended',
            'call' => [
                'call_id' => 'failed_call_123',
                'agent_id' => 'test_agent_123',
                'call_type' => 'inbound',
                'from_number' => '+493011111111',
                'to_number' => '+491234567890',
                'direction' => 'inbound',
                'call_status' => 'ended',
                'metadata' => [
                    'company_id' => $this->company->id,
                ],
            ],
        ];

        $signature = $this->generateRetellSignature($webhookPayload);

        $response = $this->postJson('/api/retell/webhook', $webhookPayload, [
            'X-Retell-Signature' => $signature,
        ]);

        $response->assertStatus(200);

        // Process the job
        $call = Call::where('retell_call_id', 'failed_call_123')->first();
        $job = new ProcessRetellCallJob($call);
        
        try {
            $job->handle(app(RetellV2Service::class));
        } catch (\Exception $e) {
            // Expected to fail
        }

        // Verify call status reflects failure
        $call->refresh();
        $this->assertEquals('failed', $call->status);
        $this->assertStringContainsString('booking failed', $call->raw_response['error'] ?? '');

        // Verify error was logged
        Log::assertLogged('error', function ($message, $context) {
            return str_contains($message, 'Failed to create Cal.com booking');
        });

        // Verify no appointment was created
        $this->assertDatabaseMissing('appointments', [
            'company_id' => $this->company->id,
        ]);
    }

    /** @test */

    #[Test]
    public function handles_webhook_replay_attacks()
    {
        Queue::fake();

        $webhookPayload = [
            'event' => 'call_ended',
            'call' => [
                'call_id' => 'replay_call_123',
                'agent_id' => 'test_agent_123',
                'call_type' => 'inbound',
                'from_number' => '+493099999999',
                'to_number' => '+491234567890',
                'direction' => 'inbound',
                'call_status' => 'ended',
                'metadata' => [
                    'company_id' => $this->company->id,
                ],
            ],
        ];

        $signature = $this->generateRetellSignature($webhookPayload);

        // First request should succeed
        $response1 = $this->postJson('/api/retell/webhook', $webhookPayload, [
            'X-Retell-Signature' => $signature,
        ]);
        $response1->assertStatus(200);

        // Duplicate request with same call_id should be idempotent
        $response2 = $this->postJson('/api/retell/webhook', $webhookPayload, [
            'X-Retell-Signature' => $signature,
        ]);
        $response2->assertStatus(200);

        // Should only create one call record
        $this->assertEquals(1, Call::where('retell_call_id', 'replay_call_123')->count());

        // Should only dispatch one job
        Queue::assertPushed(ProcessRetellCallJob::class, 1);
    }

    /** @test */

    #[Test]
    public function processes_different_service_types_correctly()
    {
        Queue::fake();

        // Create additional services
        $emergencyService = Service::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Emergency Treatment',
            'duration' => 60,
            'price' => 150.00,
            'is_emergency' => true,
        ]);

        $emergencyEventType = CalcomEventType::factory()->create([
            'company_id' => $this->company->id,
            'calcom_id' => 457,
            'title' => 'Emergency Treatment',
            'slug' => 'emergency-treatment',
            'length' => 60,
        ]);

        $this->staff->services()->attach($emergencyService);
        $this->staff->eventTypes()->attach($emergencyEventType);

        // Mock emergency appointment request
        Http::fake([
            'api.retellai.com/v2/get-call/*' => Http::response([
                'call_id' => 'emergency_call_123',
                'status' => 'ended',
                'duration_seconds' => 90,
                'transcript' => 'Customer: I have severe tooth pain and need emergency treatment!',
                'variables' => [
                    'customer_name' => 'Emergency Patient',
                    'customer_phone' => '+493055555555',
                    'service_requested' => 'emergency treatment',
                    'appointment_date' => Carbon::now()->addHours(2)->toIso8601String(),
                    'is_emergency' => true,
                ],
                'analysis' => [
                    'sentiment' => 'urgent',
                    'appointment_requested' => true,
                    'is_emergency' => true,
                ],
            ], 200),
            
            'api.cal.com/v2/bookings' => Http::response([
                'data' => [
                    'id' => 790,
                    'uid' => 'emergency_booking_123',
                    'title' => 'Emergency Treatment',
                    'startTime' => Carbon::now()->addHours(2)->toIso8601String(),
                    'endTime' => Carbon::now()->addHours(3)->toIso8601String(),
                ],
            ], 201),
        ]);

        $webhookPayload = [
            'event' => 'call_ended',
            'call' => [
                'call_id' => 'emergency_call_123',
                'agent_id' => 'test_agent_123',
                'call_type' => 'inbound',
                'from_number' => '+493055555555',
                'to_number' => '+491234567890',
                'direction' => 'inbound',
                'call_status' => 'ended',
                'metadata' => [
                    'company_id' => $this->company->id,
                ],
            ],
        ];

        $signature = $this->generateRetellSignature($webhookPayload);

        $response = $this->postJson('/api/retell/webhook', $webhookPayload, [
            'X-Retell-Signature' => $signature,
        ]);

        $response->assertStatus(200);

        // Process the job
        $call = Call::where('retell_call_id', 'emergency_call_123')->first();
        $job = new ProcessRetellCallJob($call);
        $job->handle(app(RetellV2Service::class));

        // Verify emergency appointment was created with correct service
        $this->assertDatabaseHas('appointments', [
            'company_id' => $this->company->id,
            'service_id' => $emergencyService->id,
            'status' => 'scheduled',
            'is_emergency' => true,
        ]);

        $appointment = Appointment::where('service_id', $emergencyService->id)->first();
        $this->assertNotNull($appointment);
        $this->assertEquals(60, $appointment->duration);
        $this->assertEquals(150.00, $appointment->price);
    }

    /**
     * Generate a valid Retell signature for webhook verification
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