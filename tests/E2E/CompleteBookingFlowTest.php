<?php

namespace Tests\E2E;

use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\PhoneNumber;
use App\Models\CalcomEventType;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

class CompleteBookingFlowTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Branch $branch1;
    private Branch $branch2;
    private Staff $doctor1;
    private Staff $doctor2;
    private Service $consultation;
    private Service $examination;
    private PhoneNumber $phoneNumber1;
    private PhoneNumber $phoneNumber2;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a complete test company setup
        $this->setupTestCompany();
        
        // Enable real queue processing for E2E test
        Queue::fake();
        Event::fake();
        Notification::fake();
    }

    private function setupTestCompany(): void
    {
        // Create company
        $this->company = Company::factory()->create([
            'name' => 'HealthCare Plus GmbH',
            'email' => 'info@healthcare-plus.de',
            'phone' => '+49 30 1234567',
            'retell_api_key' => 'retell_test_key_123',
            'calcom_api_key' => 'cal_test_key_456',
            'is_active' => true,
            'settings' => [
                'booking_confirmation_email' => true,
                'sms_notifications' => false,
                'auto_reminder' => true,
                'reminder_hours' => 24
            ]
        ]);
        
        // Create branches
        $this->branch1 = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Berlin Mitte',
            'address' => 'Friedrichstraße 123, 10117 Berlin',
            'phone' => '+49 30 1234567',
            'email' => 'mitte@healthcare-plus.de',
            'calcom_event_type_id' => 10001,
            'calcom_user_id' => 20001,
            'is_active' => true,
            'timezone' => 'Europe/Berlin',
            'business_hours' => [
                'monday' => ['start' => '08:00', 'end' => '18:00'],
                'tuesday' => ['start' => '08:00', 'end' => '18:00'],
                'wednesday' => ['start' => '08:00', 'end' => '18:00'],
                'thursday' => ['start' => '08:00', 'end' => '18:00'],
                'friday' => ['start' => '08:00', 'end' => '16:00'],
                'saturday' => ['start' => '09:00', 'end' => '13:00'],
                'sunday' => null
            ]
        ]);
        
        $this->branch2 = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Berlin Charlottenburg',
            'address' => 'Kurfürstendamm 456, 10719 Berlin',
            'phone' => '+49 30 9876543',
            'calcom_event_type_id' => 10002,
            'is_active' => true
        ]);
        
        // Create phone numbers
        $this->phoneNumber1 = PhoneNumber::factory()->create([
            'phone_number' => '+493012345670',
            'branch_id' => $this->branch1->id,
            'type' => 'main',
            'is_active' => true,
            'retell_agent_id' => 'agent_001'
        ]);
        
        $this->phoneNumber2 = PhoneNumber::factory()->create([
            'phone_number' => '+493098765430',
            'branch_id' => $this->branch2->id,
            'type' => 'main',
            'is_active' => true,
            'retell_agent_id' => 'agent_002'
        ]);
        
        // Create staff
        $this->doctor1 = Staff::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'name' => 'Dr. Maria Schmidt',
            'email' => 'dr.schmidt@healthcare-plus.de',
            'phone' => '+49 170 1111111',
            'calcom_user_id' => 30001,
            'role' => 'doctor',
            'specialization' => 'General Practitioner'
        ]);
        
        $this->doctor2 = Staff::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'name' => 'Dr. Hans Müller',
            'email' => 'dr.mueller@healthcare-plus.de',
            'calcom_user_id' => 30002,
            'role' => 'doctor',
            'specialization' => 'Internal Medicine'
        ]);
        
        // Create services
        $this->consultation = Service::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Erstberatung',
            'description' => 'Erstberatung und Anamnese',
            'duration' => 30,
            'price' => 60.00,
            'buffer_time' => 10,
            'is_active' => true
        ]);
        
        $this->examination = Service::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Gesundheitscheck',
            'description' => 'Vollständiger Gesundheitscheck',
            'duration' => 60,
            'price' => 120.00,
            'buffer_time' => 15,
            'is_active' => true
        ]);
        
        // Assign services to staff
        $this->doctor1->services()->attach([
            $this->consultation->id,
            $this->examination->id
        ]);
        
        $this->doctor2->services()->attach([
            $this->consultation->id
        ]);
        
        // Create Cal.com event types
        CalcomEventType::factory()->create([
            'company_id' => $this->company->id,
            'calcom_event_type_id' => 10001,
            'title' => 'Medical Consultation',
            'slug' => 'medical-consultation',
            'duration' => 30
        ]);
    }

    public function test_complete_phone_to_appointment_flow()
    {
        // Mock all external APIs
        $this->mockExternalAPIs();
        
        // Step 1: Customer calls the clinic
        $incomingCall = [
            'event' => 'call_started',
            'data' => [
                'call_id' => 'call_e2e_001',
                'agent_id' => 'agent_001',
                'to' => '+493012345670', // Branch 1 number
                'from' => '+491701234567',
                'start_timestamp' => Carbon::now()->timestamp
            ]
        ];
        
        $response = $this->postJson('/api/retell/webhook', $incomingCall, [
            'x-retell-signature' => $this->generateSignature($incomingCall)
        ]);
        
        $response->assertStatus(200);
        Queue::assertPushed(\App\Jobs\ProcessRetellWebhookJob::class);
        
        // Step 2: Call ends with appointment request
        $callEnded = [
            'event' => 'call_ended',
            'data' => [
                'call_id' => 'call_e2e_001',
                'agent_id' => 'agent_001',
                'to' => '+493012345670',
                'from' => '+491701234567',
                'duration' => 180,
                'end_timestamp' => Carbon::now()->addMinutes(3)->timestamp,
                'transcript' => "Agent: Guten Tag, HealthCare Plus Berlin Mitte, wie kann ich Ihnen helfen?\n" .
                               "Kunde: Hallo, ich möchte gerne einen Termin für eine Erstberatung vereinbaren.\n" .
                               "Agent: Natürlich, gerne. Wann würde es Ihnen passen?\n" .
                               "Kunde: Am liebsten nächsten Mittwoch vormittag.\n" .
                               "Agent: Ich schaue nach freien Terminen. Wie wäre es mit Mittwoch, dem 25. Juni um 10:00 Uhr bei Dr. Schmidt?\n" .
                               "Kunde: Das passt perfekt. Mein Name ist Thomas Wagner.\n" .
                               "Agent: Vielen Dank Herr Wagner. Können Sie mir noch Ihre E-Mail-Adresse für die Bestätigung geben?\n" .
                               "Kunde: thomas.wagner@email.de\n" .
                               "Agent: Perfekt. Ihr Termin ist gebucht für Mittwoch, 25. Juni um 10:00 Uhr. Sie erhalten eine Bestätigung per E-Mail.",
                'summary' => 'Kunde Thomas Wagner möchte Erstberatungstermin am 25.06. um 10:00 Uhr bei Dr. Schmidt',
                'custom_analysis' => [
                    'appointment_requested' => true,
                    'customer_name' => 'Thomas Wagner',
                    'customer_email' => 'thomas.wagner@email.de',
                    'service_requested' => 'Erstberatung',
                    'preferred_date' => '2025-06-25',
                    'preferred_time' => '10:00',
                    'staff_mentioned' => 'Dr. Schmidt',
                    'appointment_confirmed' => true
                ]
            ]
        ];
        
        $response = $this->postJson('/api/retell/webhook', $callEnded, [
            'x-retell-signature' => $this->generateSignature($callEnded)
        ]);
        
        $response->assertStatus(200);
        
        // Process the queued jobs
        $this->processQueuedJobs();
        
        // Step 3: Verify call was created
        $call = Call::where('retell_call_id', 'call_e2e_001')->first();
        $this->assertNotNull($call);
        $this->assertEquals($this->company->id, $call->company_id);
        $this->assertEquals($this->branch1->id, $call->branch_id);
        $this->assertEquals(180, $call->duration);
        $this->assertEquals('+491701234567', $call->from_number);
        
        // Step 4: Verify customer was created
        $customer = Customer::where('phone', '+491701234567')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('Thomas Wagner', $customer->name);
        $this->assertEquals('thomas.wagner@email.de', $customer->email);
        $this->assertEquals($this->company->id, $customer->company_id);
        
        // Step 5: Verify appointment was created
        $appointment = Appointment::where('customer_id', $customer->id)->first();
        $this->assertNotNull($appointment);
        $this->assertEquals('2025-06-25 10:00:00', $appointment->start_time);
        $this->assertEquals('2025-06-25 10:30:00', $appointment->end_time);
        $this->assertEquals($this->doctor1->id, $appointment->staff_id);
        $this->assertEquals($this->consultation->id, $appointment->service_id);
        $this->assertEquals($this->branch1->id, $appointment->branch_id);
        $this->assertEquals('scheduled', $appointment->status);
        $this->assertEquals(12345, $appointment->calcom_booking_id);
        
        // Step 6: Verify notifications were sent
        Notification::assertSentTo(
            $customer,
            \App\Notifications\AppointmentConfirmation::class,
            function ($notification, $channels) use ($appointment) {
                return $notification->appointment->id === $appointment->id;
            }
        );
        
        // Step 7: Verify events were fired
        Event::assertDispatched('call.ended');
        Event::assertDispatched('customer.created');
        Event::assertDispatched('appointment.created');
        Event::assertDispatched('appointment.synced');
    }

    public function test_appointment_lifecycle_complete_flow()
    {
        // Create initial appointment
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Lisa Meyer',
            'phone' => '+491709876543'
        ]);
        
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'customer_id' => $customer->id,
            'staff_id' => $this->doctor1->id,
            'service_id' => $this->consultation->id,
            'start_time' => Carbon::now()->addDays(2)->setTime(14, 0),
            'end_time' => Carbon::now()->addDays(2)->setTime(14, 30),
            'status' => 'scheduled',
            'calcom_booking_id' => 99999
        ]);
        
        // Test reminder 24 hours before
        Carbon::setTestNow(Carbon::now()->addDay());
        $this->artisan('appointments:send-reminders')->assertExitCode(0);
        
        Notification::assertSentTo(
            $customer,
            \App\Notifications\AppointmentReminder::class
        );
        
        // Test check-in on appointment day
        Carbon::setTestNow($appointment->start_time->subMinutes(15));
        
        $checkInResponse = $this->postJson("/api/appointments/{$appointment->id}/check-in");
        $checkInResponse->assertStatus(200);
        
        $appointment->refresh();
        $this->assertEquals('confirmed', $appointment->status);
        
        // Test marking as completed
        Carbon::setTestNow($appointment->end_time->addMinutes(5));
        
        $completeResponse = $this->postJson("/api/appointments/{$appointment->id}/complete", [
            'notes' => 'Patient presented with mild symptoms. Prescribed medication.'
        ]);
        $completeResponse->assertStatus(200);
        
        $appointment->refresh();
        $this->assertEquals('completed', $appointment->status);
        $this->assertStringContainsString('Prescribed medication', $appointment->notes);
        
        // Test follow-up appointment booking
        $followUpCall = [
            'event' => 'call_ended',
            'data' => [
                'call_id' => 'call_followup_001',
                'from' => '+491709876543',
                'to' => '+493012345670',
                'custom_analysis' => [
                    'appointment_requested' => true,
                    'is_follow_up' => true,
                    'previous_appointment_id' => $appointment->id,
                    'preferred_date' => Carbon::now()->addWeek()->format('Y-m-d'),
                    'preferred_time' => '14:00'
                ]
            ]
        ];
        
        $this->postJson('/api/retell/webhook', $followUpCall, [
            'x-retell-signature' => $this->generateSignature($followUpCall)
        ]);
        
        $this->processQueuedJobs();
        
        // Verify follow-up appointment
        $followUp = Appointment::where('customer_id', $customer->id)
            ->where('id', '!=', $appointment->id)
            ->first();
        
        $this->assertNotNull($followUp);
        $this->assertStringContainsString('Follow-up', $followUp->notes);
    }

    public function test_multi_branch_routing()
    {
        // Test call to branch 2
        $callToBranch2 = [
            'event' => 'call_ended',
            'data' => [
                'call_id' => 'call_branch2_001',
                'to' => '+493098765430', // Branch 2 number
                'from' => '+491705555555',
                'custom_analysis' => [
                    'appointment_requested' => true,
                    'customer_name' => 'Peter Schmidt',
                    'preferred_date' => '2025-06-26',
                    'preferred_time' => '15:00'
                ]
            ]
        ];
        
        $this->postJson('/api/retell/webhook', $callToBranch2, [
            'x-retell-signature' => $this->generateSignature($callToBranch2)
        ]);
        
        $this->processQueuedJobs();
        
        // Verify appointment at correct branch
        $appointment = Appointment::whereHas('customer', function($q) {
            $q->where('phone', '+491705555555');
        })->first();
        
        $this->assertNotNull($appointment);
        $this->assertEquals($this->branch2->id, $appointment->branch_id);
    }

    public function test_error_recovery_scenarios()
    {
        // Test Cal.com API failure with graceful degradation
        Http::fake([
            'api.cal.com/*' => Http::response(null, 503) // Service unavailable
        ]);
        
        $callWithApiFailure = [
            'event' => 'call_ended',
            'data' => [
                'call_id' => 'call_api_fail_001',
                'to' => '+493012345670',
                'from' => '+491707777777',
                'custom_analysis' => [
                    'appointment_requested' => true,
                    'customer_name' => 'Anna Klein',
                    'preferred_date' => '2025-06-27',
                    'preferred_time' => '11:00'
                ]
            ]
        ];
        
        $this->postJson('/api/retell/webhook', $callWithApiFailure, [
            'x-retell-signature' => $this->generateSignature($callWithApiFailure)
        ]);
        
        $this->processQueuedJobs();
        
        // Appointment should still be created locally
        $appointment = Appointment::whereHas('customer', function($q) {
            $q->where('phone', '+491707777777');
        })->first();
        
        $this->assertNotNull($appointment);
        $this->assertEquals('pending_sync', $appointment->status);
        $this->assertNull($appointment->calcom_booking_id);
        
        // Test retry mechanism
        Http::fake([
            'api.cal.com/*' => Http::response(['id' => 88888], 201)
        ]);
        
        $this->artisan('appointments:sync-pending')->assertExitCode(0);
        
        $appointment->refresh();
        $this->assertEquals('scheduled', $appointment->status);
        $this->assertEquals(88888, $appointment->calcom_booking_id);
    }

    private function mockExternalAPIs(): void
    {
        Http::fake([
            // Cal.com API responses
            'api.cal.com/v2/slots/available-slots*' => Http::response([
                'status' => 'success',
                'data' => [
                    'slots' => [
                        ['time' => '2025-06-25T10:00:00+02:00'],
                        ['time' => '2025-06-25T10:30:00+02:00'],
                        ['time' => '2025-06-25T11:00:00+02:00']
                    ]
                ]
            ], 200),
            
            'api.cal.com/v2/bookings' => Http::response([
                'status' => 'success',
                'data' => [
                    'id' => 12345,
                    'uid' => 'booking_' . uniqid(),
                    'startTime' => '2025-06-25T10:00:00+02:00',
                    'endTime' => '2025-06-25T10:30:00+02:00'
                ]
            ], 201),
            
            // Retell API responses
            'api.retellai.com/v2/get-call*' => Http::response([
                'call_id' => 'call_e2e_001',
                'status' => 'ended',
                'duration' => 180
            ], 200)
        ]);
    }

    private function processQueuedJobs(): void
    {
        // Get all queued jobs and process them
        $jobs = Queue::pushedJobs();
        
        foreach ($jobs as $jobClass => $jobInstances) {
            foreach ($jobInstances as $job) {
                $instance = new $jobClass($job['data']);
                $instance->handle(app($instance->getHandlerClass()));
            }
        }
    }

    private function generateSignature(array $payload): string
    {
        return hash_hmac('sha256', json_encode($payload), 'test_webhook_secret');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        parent::tearDown();
    }
}